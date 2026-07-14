<?php
namespace Core\Services;

use Core\Database;
use Modules\Projects\Project;
use Exception;



class FileMoveService
{
    private Project $projects;

    public function __construct(Project $projects)
    {
        $this->projects = $projects;
    }

    /**
     * Move a file or folder safely within the workspace.
     */
    public function move(int $projectId, string $workspacePath, string $from, string $to, bool $overwrite = false): array
    {
        [$fromOk, $fromMsg, $safeFrom, $fullFrom] = SafePath::toProjectPath($workspacePath, $from);
        if (!$fromOk) {
            return [false, $fromMsg];
        }

        [$toOk, $toMsg, $safeTo, $fullTo] = SafePath::toProjectPath($workspacePath, $to);
        if (!$toOk) {
            return [false, $toMsg];
        }

        if ($safeFrom === $safeTo) {
            return [false, 'Path asal dan tujuan sama.'];
        }

        // Folder tidak boleh dipindahkan ke dalam dirinya sendiri
        if (str_starts_with($safeTo, $safeFrom . '/')) {
            return [false, 'Folder tidak boleh dipindahkan ke dalam dirinya sendiri.'];
        }

        $basePath = rtrim($workspacePath, DIRECTORY_SEPARATOR);

        if (!file_exists($fullFrom) || is_link($fullFrom)) {
            return [false, 'File/folder sumber tidak ditemukan atau tidak aman.'];
        }

        if (!is_dir($fullFrom) && !FileValidator::validateExtension($safeTo)) {
            return [false, 'Jenis file tujuan tidak diizinkan.'];
        }

        if (file_exists($fullTo) && !$overwrite) {
            return [false, 'File/folder tujuan sudah ada.'];
        }

        // Backup existing target jika overwrite true
        $backupPath = null;
        if (file_exists($fullTo)) {
            $backupPath = $fullTo . '.bak_' . uniqid();
            rename($fullTo, $backupPath);
        }

        // Pastikan folder parent dari target ada
        $dir = dirname($fullTo);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // DB Transaction and Move
        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();

            if (!rename($fullFrom, $fullTo)) {
                throw new Exception('Gagal memindahkan file di sistem secara fisik.');
            }

            // Update Database
            $isFolder = is_dir($fullTo) ? 1 : 0;
            $newName = basename($safeTo);
            $ext = $isFolder ? null : pathinfo($newName, PATHINFO_EXTENSION);

            if ($backupPath !== null) {
                // Delete existing DB records from old target
                if ($isFolder) {
                    $this->projects->query("DELETE FROM project_files WHERE id_project = ? AND (relative_path = ? OR relative_path LIKE ?)", [$projectId, $safeTo, $safeTo . '/%']);
                } else {
                    $this->projects->query("DELETE FROM project_files WHERE id_project = ? AND relative_path = ?", [$projectId, $safeTo]);
                }
            }

            if ($isFolder) {
                // Update parent
                $this->projects->query(
                    "UPDATE project_files SET relative_path = ?, file_name = ? WHERE id_project = ? AND relative_path = ?",
                    [$safeTo, $newName, $projectId, $safeFrom]
                );

                // Update children
                $oldPrefix = $safeFrom . '/';
                $newPrefix = $safeTo . '/';
                $this->projects->query(
                    "UPDATE project_files SET relative_path = CONCAT(?, SUBSTR(relative_path, ?)) WHERE id_project = ? AND relative_path LIKE ?",
                    [$newPrefix, strlen($oldPrefix) + 1, $projectId, $oldPrefix . '%']
                );
            } else {
                $this->projects->query(
                    "UPDATE project_files SET relative_path = ?, file_name = ?, file_extension = ? WHERE id_project = ? AND relative_path = ?",
                    [$safeTo, $newName, $ext, $projectId, $safeFrom]
                );
            }

            $pdo->commit();

            if ($backupPath !== null && file_exists($backupPath)) {
                $this->deleteDirOrFile($backupPath);
            }

            return [true, 'Berhasil dipindahkan.', $safeTo];

        } catch (Exception $e) {
            $pdo->rollBack();
            
            // File rollback
            if (file_exists($fullTo) && !file_exists($fullFrom)) {
                rename($fullTo, $fullFrom);
            }
            if ($backupPath !== null && file_exists($backupPath)) {
                rename($backupPath, $fullTo);
            }

            return [false, $e->getMessage()];
        }
    }

    private function deleteDirOrFile(string $path): void
    {
        if (is_dir($path)) {
            $files = array_diff(scandir($path), ['.', '..']);
            foreach ($files as $file) {
                $this->deleteDirOrFile($path . DIRECTORY_SEPARATOR . $file);
            }
            rmdir($path);
        } else {
            unlink($path);
        }
    }
}
