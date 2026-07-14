<?php
namespace Core\Services;

use Core\Database;
use Modules\Projects\Project;
use ZipArchive;
use Exception;

class ZipImportService
{
    private Project $projects;

    public function __construct(Project $projects)
    {
        $this->projects = $projects;
    }

    /**
     * Import a ZIP file safely.
     */
    public function import(int $projectId, string $workspacePath, string $zipPath, string $targetRelativeDir): array
    {
        $filesize = filesize($zipPath);
        if ($filesize > ProjectQuotaService::ZIP_MAX_BYTES) {
            return [false, 'File ZIP terlalu besar (maks 100MB).'];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return [false, 'Gagal membuka atau membaca file ZIP. Pastikan file valid dan tidak dienkripsi (corrupt).'];
        }

        // Cek Quota/Limit dan struktur ZIP
        $totalFiles = 0;
        $totalUncompressed = 0;
        $validEntries = [];
        $hasErrors = false;
        $errorMessage = '';

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!$stat) continue;

            $name = $stat['name'];
            
            // Skip folder entries explicitly if they have no name (usually trailing slash)
            $isDir = str_ends_with($name, '/');
            $cleanName = SafePath::normalize($name);

            if ($cleanName === '' && !$isDir) {
                continue; // skip
            }

            [$isSafe, $msg] = FileValidator::validateZipEntryPath($name);
            if (!$isSafe) {
                $hasErrors = true;
                $errorMessage = "Entry tidak aman ditemukan: {$name} ({$msg})";
                break;
            }

            if (isset($stat['encryption_method']) && (int)$stat['encryption_method'] !== ZipArchive::EM_NONE) {
                $hasErrors = true;
                $errorMessage = "Encrypted ZIP tidak diperbolehkan: {$name}";
                break;
            }

            if ($this->isUnsafeZipEntryType($zip, $i, $isDir)) {
                $hasErrors = true;
                $errorMessage = "Symlink/hardlink/device file tidak diperbolehkan di ZIP: {$name}";
                break;
            }

            if (!$isDir) {
                if (!FileValidator::validateExtension($name)) {
                    $hasErrors = true;
                    $errorMessage = "Ekstensi file tidak diizinkan ditemukan di dalam ZIP: {$name}";
                    break;
                }

                $size = $stat['size'];
                if ($size > ProjectQuotaService::ZIP_MAX_SINGLE_FILE_BYTES) {
                    $hasErrors = true;
                    $errorMessage = "Ukuran file satuan terlalu besar (maks 20MB): {$name}";
                    break;
                }

                $totalUncompressed += $size;
                $totalFiles++;

                if ($totalFiles > ProjectQuotaService::ZIP_MAX_FILES) {
                    $hasErrors = true;
                    $errorMessage = "Jumlah file dalam ZIP melebihi batas (maks 1000).";
                    break;
                }
                
                if ($totalUncompressed > ProjectQuotaService::ZIP_MAX_UNCOMPRESSED_BYTES) {
                    $hasErrors = true;
                    $errorMessage = "Total uncompressed size melebihi batas (maks 100MB).";
                    break;
                }
                
                $validEntries[] = ['index' => $i, 'path' => $cleanName, 'is_dir' => false, 'size' => $size];
            } else {
                if ($cleanName !== '') {
                    $validEntries[] = ['index' => $i, 'path' => $cleanName, 'is_dir' => true, 'size' => 0];
                }
            }
        }

        if ($hasErrors) {
            $zip->close();
            return [false, $errorMessage];
        }

        // Auto-strip single top-level folder: jika semua entry diawali folder yang sama, strip prefix-nya.
        $stripPrefix = $this->detectStripPrefix($validEntries, $zip);

        // Rebuild validEntries dengan prefix yang sudah di-strip
        if ($stripPrefix !== '') {
            foreach ($validEntries as &$entry) {
                $entry['path'] = substr($entry['path'], strlen($stripPrefix));
                if ($entry['path'] === '') {
                    $entry['path'] = '.'; // root folder entry
                }
            }
            unset($entry);
        }

        // Create Quarantine Dir
        $storageDir = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'storage';
        $quarantineDir = $storageDir . DIRECTORY_SEPARATOR . 'quarantine' . DIRECTORY_SEPARATOR . uniqid('import_', true);

        if (!is_dir($quarantineDir)) {
            mkdir($quarantineDir, 0777, true);
        }

        // Extract to quarantine
        try {
            foreach ($validEntries as $entry) {
                if ($entry['path'] === '.' || $entry['path'] === '') continue;

                $destPath = $quarantineDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $entry['path']);
                
                if ($entry['is_dir']) {
                    if (!is_dir($destPath)) {
                        mkdir($destPath, 0777, true);
                    }
                } else {
                    $dir = dirname($destPath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }

                    $stream = $zip->getStream($zip->getNameIndex($entry['index']));
                    if (!$stream) {
                        throw new Exception("Gagal membaca entry ZIP: {$entry['path']}");
                    }
                    
                    $out = fopen($destPath, 'wb');
                    if (!$out) {
                        throw new Exception("Gagal menulis file quarantine: {$entry['path']}");
                    }

                    stream_copy_to_stream($stream, $out);
                    fclose($out);
                    fclose($stream);

                    // Validate Magic Number / Binary check
                    [$contentValid, $contentMsg] = FileValidator::validateFileContent($destPath);
                    if (!$contentValid) {
                        throw new Exception("Isi file tidak valid atau berbahaya ({$entry['path']}): {$contentMsg}");
                    }
                }
            }
        } catch (Exception $e) {
            $zip->close();
            $this->deleteDir($quarantineDir);
            return [false, $e->getMessage()];
        }
        $zip->close();

        // Preflight: target dir harus aman dan import ZIP tidak overwrite file existing.
        $rawTargetRelativeDir = trim($targetRelativeDir);
        if ($rawTargetRelativeDir !== '') {
            [$targetOk, $targetMsg] = SafePath::validateRelativeInput($rawTargetRelativeDir);
            if (!$targetOk) {
                $this->deleteDir($quarantineDir);
                return [false, $targetMsg];
            }
        }
        $targetRelativeDir = $rawTargetRelativeDir === '' ? '' : SafePath::normalize($rawTargetRelativeDir);

        foreach ($validEntries as $entry) {
            if ($entry['path'] === '.' || $entry['path'] === '') continue;

            $relPath = $targetRelativeDir === '' ? $entry['path'] : ltrim($targetRelativeDir . '/' . $entry['path'], '/');
            [$pathOk, $pathMsg, , $destPath] = SafePath::toProjectPath($workspacePath, $relPath);
            if (!$pathOk) {
                $this->deleteDir($quarantineDir);
                return [false, $pathMsg];
            }

            if (file_exists($destPath)) {
                $this->deleteDir($quarantineDir);
                return [false, "Import ZIP ditolak karena target sudah ada: {$relPath}"];
            }
        }

        // Copy to workspace and update DB atomically if possible
        // using PDO transaction + filesystem rollback list.
        $pdo = Database::getConnection();
        $createdPaths = [];
        try {
            $pdo->beginTransaction();

            // Upsert entries in DB
            foreach ($validEntries as $entry) {
                if ($entry['path'] === '.' || $entry['path'] === '') continue;

                $relPath = $targetRelativeDir === '' ? $entry['path'] : ltrim($targetRelativeDir . '/' . $entry['path'], '/');
                [, , , $destPath] = SafePath::toProjectPath($workspacePath, $relPath);

                $srcPath = $quarantineDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $entry['path']);

                if ($entry['is_dir']) {
                    if (!is_dir($destPath)) {
                        mkdir($destPath, 0777, true);
                        $createdPaths[] = $destPath;
                    }
                } else {
                    $dir = dirname($destPath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    if (!rename($srcPath, $destPath)) {
                        throw new Exception("Gagal memindahkan file dari quarantine: {$relPath}");
                    }
                    $createdPaths[] = $destPath;
                }

                $name = basename($relPath);
                $ext = $entry['is_dir'] ? null : pathinfo($name, PATHINFO_EXTENSION);
                
                $exists = $this->projects->query(
                    "SELECT id_file FROM project_files WHERE id_project = ? AND relative_path = ? LIMIT 1",
                    [$projectId, $relPath]
                )->fetch();

                if ($exists) {
                    $this->projects->query(
                        "UPDATE project_files SET file_name = ?, file_extension = ?, is_folder = ?, file_size = ? WHERE id_file = ?",
                        [$name, $ext, $entry['is_dir'] ? 1 : 0, $entry['size'], $exists['id_file']]
                    );
                } else {
                    $this->projects->query(
                        "INSERT INTO project_files (id_project, file_name, relative_path, file_extension, is_folder, is_editable, file_size) VALUES (?, ?, ?, ?, ?, ?, ?)",
                        [$projectId, $name, $relPath, $ext, $entry['is_dir'] ? 1 : 0, $entry['is_dir'] ? 0 : (in_array(strtolower($ext ?? ''), ['html', 'css', 'js', 'json', 'txt', 'md', 'svg'], true) ? 1 : 0), $entry['size']]
                    );
                }
            }

            $pdo->commit();
            $this->deleteDir($quarantineDir);

            return [true, 'File ZIP berhasil diekstrak dan divalidasi.'];
        } catch (Exception $e) {
            $pdo->rollBack();
            foreach (array_reverse($createdPaths) as $createdPath) {
                if (is_dir($createdPath)) {
                    $this->deleteDir($createdPath);
                } elseif (is_file($createdPath)) {
                    unlink($createdPath);
                }
            }
            $this->deleteDir($quarantineDir);
            return [false, $e->getMessage()];
        }
    }

    private function isUnsafeZipEntryType(ZipArchive $zip, int $index, bool $isDir): bool
    {
        $opsys = 0;
        $attr = 0;
        if (!$zip->getExternalAttributesIndex($index, $opsys, $attr)) {
            return false;
        }

        // Unix mode bits live in high 16 bits.
        $mode = ($attr >> 16) & 0xF000;
        if ($mode === 0) {
            return false;
        }

        $unixFile = 0x8000;
        $unixDir = 0x4000;
        $unixSymlink = 0xA000;

        if ($mode === $unixSymlink) {
            return true;
        }

        if ($isDir) {
            return $mode !== $unixDir;
        }

        return $mode !== $unixFile;
    }

    private function detectStripPrefix(array $entries, ZipArchive $zip): string
    {
        if (empty($entries)) return '';

        // Collect all top-level elements
        $topLevelElements = [];
        foreach ($entries as $entry) {
            $path = $entry['path'];
            if ($path === '' || $path === '.') continue;

            $parts = explode('/', $path);
            $topLevelElements[$parts[0]] = true;
        }

        // Jika hanya ada tepat 1 elemen top-level
        if (count($topLevelElements) === 1) {
            $root = array_key_first($topLevelElements);
            
            // Periksa apakah root ini adalah sebuah folder.
            // Bisa jadi file tunggal bernama 'root'
            $isFolder = false;
            foreach ($entries as $entry) {
                if ($entry['path'] === $root && $entry['is_dir']) {
                    $isFolder = true;
                    break;
                }
                if (str_starts_with($entry['path'], $root . '/')) {
                    $isFolder = true;
                    break;
                }
            }

            if ($isFolder) {
                return $root . '/';
            }
        }

        return '';
    }

    private function deleteDir(string $dirPath): void
    {
        if (!is_dir($dirPath) || is_link($dirPath)) {
            return;
        }
        foreach (array_diff(scandir($dirPath) ?: [], ['.', '..']) as $file) {
            $path = $dirPath . DIRECTORY_SEPARATOR . $file;
            if (is_link($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                $this->deleteDir($path);
            } elseif (is_file($path)) {
                unlink($path);
            }
        }
        rmdir($dirPath);
    }
}
