<?php
namespace Modules\Files;

use Core\Controller;
use Core\Services\FileMoveService;
use Core\Services\FileValidator;
use Core\Services\ProjectQuotaService;
use Core\Services\SafePath;
use Core\Services\ZipImportService;
use Modules\Projects\Project;

class FilesController extends Controller
{
    private Project $projects;

    public function __construct()
    {
        $this->projects = new Project();
    }

    /**
     * Buka Editor halaman utama
     */
    public function editor(int $projectId): void
    {
        require_auth();

        $project = $this->projects->find($projectId);
        if (!$project || (int)$project['id_user'] !== auth_user()['id_user']) {
            $_SESSION['flash_error'] = 'Project tidak ditemukan.';
            redirect_to('dashboard');
        }

        // Ambil file tree list dari DB
        $stmt = $this->projects->query(
            "SELECT * FROM project_files WHERE id_project = ? ORDER BY relative_path ASC",
            [$projectId]
        );
        $files = $stmt->fetchAll();

        // Urutkan sebagai tree: folder parent dulu, anak tepat setelah parent, baru file root.
        $folderPaths = [];
        foreach ($files as $file) {
            if ((int)$file['is_folder'] === 1) {
                $folderPaths[$file['relative_path']] = true;
            }
        }

        usort($files, function (array $a, array $b) use ($folderPaths): int {
            $aParts = explode('/', trim($a['relative_path'], '/'));
            $bParts = explode('/', trim($b['relative_path'], '/'));

            $max = min(count($aParts), count($bParts));
            for ($i = 0; $i < $max; $i++) {
                if ($aParts[$i] === $bParts[$i]) {
                    continue;
                }

                $aPath = implode('/', array_slice($aParts, 0, $i + 1));
                $bPath = implode('/', array_slice($bParts, 0, $i + 1));
                $aIsFolderSegment = isset($folderPaths[$aPath]);
                $bIsFolderSegment = isset($folderPaths[$bPath]);

                if ($aIsFolderSegment !== $bIsFolderSegment) {
                    return $aIsFolderSegment ? -1 : 1;
                }

                return strnatcasecmp($aParts[$i], $bParts[$i]);
            }

            return count($aParts) <=> count($bParts);
        });

        // Cari file index.html default untuk diedit pertama kali
        $selectedPath = 'index.html';
        $selectedContent = '';
        
        $workspace = rtrim($project['workspace_path'], DIRECTORY_SEPARATOR);
        $indexPath = $workspace . DIRECTORY_SEPARATOR . 'index.html';
        if (file_exists($indexPath)) {
            $selectedContent = file_get_contents($indexPath);
        }

        $this->render('projects.editor', [
            'page_title' => $project['project_name'] . ' - Editor',
            'layout_variant' => 'editor',
            'project' => $project,
            'files' => $files,
            'selected_path' => $selectedPath,
            'selected_content' => $selectedContent,
            'preview_url' => $project['public_url'] ?: ''
        ], 'app');
    }

    /**
     * Get content file AJAX
     */
    public function getContent(): void
    {
        require_auth();
        verify_csrf();

        $projectId = (int) ($_POST['id_project'] ?? 0);
        $path = trim($_POST['relative_path'] ?? '');

        if ($projectId === 0 || $path === '') {
            $this->json(['success' => false, 'message' => 'Parameter tidak valid.'], 400);
        }

        $project = $this->projects->find($projectId);
        if (!$project || (int)$project['id_user'] !== auth_user()['id_user']) {
            $this->json(['success' => false, 'message' => 'Project tidak ditemukan.'], 404);
        }

        [$pathOk, $pathMessage, , $filePath] = SafePath::toProjectPath($project['workspace_path'], $path);
        if (!$pathOk) {
            $this->json(['success' => false, 'message' => $pathMessage], 400);
        }

        if (!is_file($filePath) || is_link($filePath) || !FileValidator::validateExtension($filePath)) {
            $this->json(['success' => false, 'message' => 'File tidak ditemukan atau tidak diizinkan.'], 404);
        }

        $content = file_get_contents($filePath);
        $this->json(['success' => true, 'content' => $content]);
    }

    /**
     * Save content file AJAX
     */
    public function saveContent(): void
    {
        require_auth();
        verify_csrf();

        $projectId = (int) ($_POST['id_project'] ?? 0);
        $path = trim($_POST['relative_path'] ?? '');
        $content = $_POST['content'] ?? '';

        if ($projectId === 0 || $path === '') {
            $this->json(['success' => false, 'message' => 'Parameter tidak valid.'], 400);
        }

        $project = $this->projects->find($projectId);
        if (!$project || (int)$project['id_user'] !== auth_user()['id_user']) {
            $this->json(['success' => false, 'message' => 'Project tidak ditemukan.'], 404);
        }

        [$pathOk, $pathMessage, $safePath, $filePath] = SafePath::toProjectPath($project['workspace_path'], $path);
        if (!$pathOk) {
            $this->json(['success' => false, 'message' => $pathMessage], 400);
        }

        if (!is_file($filePath) || is_link($filePath) || !FileValidator::validateExtension($safePath)) {
            $this->json(['success' => false, 'message' => 'File tidak ditemukan atau tidak diizinkan.'], 404);
        }

        if (strlen($content) > 5 * 1024 * 1024) {
            $this->json(['success' => false, 'message' => 'Isi file maksimal 5 MB.'], 400);
        }

        if (file_put_contents($filePath, $content, LOCK_EX) === false) {
            $this->json(['success' => false, 'message' => 'Gagal menulis ke file.'], 500);
        }

        // Update size di DB
        $newSize = filesize($filePath);
        $this->projects->query(
            "UPDATE project_files SET file_size = ? WHERE id_project = ? AND relative_path = ?",
            [$newSize, $projectId, $path]
        );

        $this->projects->query(
            "INSERT INTO activity_logs (id_user, id_project, action, description) VALUES (?, ?, ?, ?)",
            [auth_user()['id_user'], $projectId, 'edit_file', "Edit file {$path}"]
        );

        // Update timestamps project
        $this->projects->update($projectId, ['updated_at' => date('Y-m-d H:i:s')]);

        $this->json(['success' => true, 'message' => 'File berhasil disimpan.']);
    }

    /**
     * Delete file/folder AJAX
     */
    public function deleteFile(): void
    {
        require_auth();
        verify_csrf();

        $projectId = (int) ($_POST['id_project'] ?? 0);
        $path = trim($_POST['relative_path'] ?? '');

        if ($projectId === 0 || $path === '') {
            $this->json(['success' => false, 'message' => 'Parameter tidak valid.'], 400);
        }

        $project = $this->projects->find($projectId);
        if (!$project || (int)$project['id_user'] !== auth_user()['id_user']) {
            $this->json(['success' => false, 'message' => 'Project tidak ditemukan.'], 404);
        }

        [$pathOk, $pathMessage, $safePath, $fullPath] = SafePath::toProjectPath($project['workspace_path'], $path);
        if (!$pathOk) {
            $this->json(['success' => false, 'message' => $pathMessage], 400);
        }

        if (!file_exists($fullPath) || is_link($fullPath)) {
            $this->json(['success' => false, 'message' => 'File tidak ditemukan.'], 404);
        }

        $wasDirectory = is_dir($fullPath);
        if ($wasDirectory) {
            $this->deleteDir($fullPath);
            $this->projects->query("DELETE FROM project_files WHERE id_project = ? AND (relative_path = ? OR relative_path LIKE ?)", [$projectId, $safePath, $safePath . '/%']);
        } else {
            unlink($fullPath);
            $this->projects->query("DELETE FROM project_files WHERE id_project = ? AND relative_path = ?", [$projectId, $safePath]);
        }

        $this->projects->query(
            "INSERT INTO activity_logs (id_user, id_project, action, description) VALUES (?, ?, ?, ?)",
            [auth_user()['id_user'], $projectId, 'delete_file', "Hapus " . ($wasDirectory ? 'folder' : 'file') . " {$safePath}"]
        );

        $this->projects->update($projectId, ['updated_at' => date('Y-m-d H:i:s')]);
        $this->json(['success' => true, 'message' => 'File berhasil dihapus.']);
    }

    /**
     * Create file/folder AJAX
     */
    public function createFile(): void
    {
        require_auth();
        verify_csrf();

        $projectId = (int) ($_POST['id_project'] ?? 0);
        $path = trim($_POST['relative_path'] ?? '');
        $isFolder = (int) ($_POST['is_folder'] ?? 0);

        if ($projectId === 0 || $path === '') {
            $this->json(['success' => false, 'message' => 'Parameter tidak valid.'], 400);
        }

        $project = $this->projects->find($projectId);
        if (!$project || (int)$project['id_user'] !== auth_user()['id_user']) {
            $this->json(['success' => false, 'message' => 'Project tidak ditemukan.'], 404);
        }

        [$pathOk, $pathMessage, $safePath, $fullPath] = SafePath::toProjectPath($project['workspace_path'], $path);
        if (!$pathOk) {
            $this->json(['success' => false, 'message' => $pathMessage], 400);
        }

        if (!$isFolder && !FileValidator::validateExtension($safePath)) {
            $this->json(['success' => false, 'message' => 'Jenis file tidak diizinkan.'], 400);
        }

        if (file_exists($fullPath)) {
            $this->json(['success' => false, 'message' => 'Nama sudah ada.'], 400);
        }

        // Pastikan folder parent ada
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $name = basename($safePath);
        $ext = $isFolder ? null : pathinfo($name, PATHINFO_EXTENSION);

        if ($isFolder) {
            mkdir($fullPath, 0777, true);
        } else {
            file_put_contents($fullPath, "");
        }

        $this->projects->query(
            "INSERT INTO project_files (id_project, file_name, relative_path, file_extension, is_folder, is_editable, file_size) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$projectId, $name, $safePath, $ext, $isFolder, $isFolder ? 0 : 1, 0]
        );

        $this->projects->query(
            "INSERT INTO activity_logs (id_user, id_project, action, description) VALUES (?, ?, ?, ?)",
            [auth_user()['id_user'], $projectId, 'create_file', "Buat " . ($isFolder ? 'folder' : 'file') . " {$safePath}"]
        );

        $this->projects->update($projectId, ['updated_at' => date('Y-m-d H:i:s')]);
        $this->json(['success' => true, 'message' => $isFolder ? 'Folder dibuat.' : 'File dibuat.']);
    }

    /**
     * Rename file/folder AJAX
     */
    public function renameFile(): void
    {
        require_auth();
        verify_csrf();

        $projectId = (int) ($_POST['id_project'] ?? 0);
        $oldPath = trim($_POST['old_path'] ?? '');
        $newPath = trim($_POST['new_path'] ?? '');

        if ($projectId === 0 || $oldPath === '' || $newPath === '') {
            $this->json(['success' => false, 'message' => 'Parameter tidak valid.'], 400);
        }

        $project = $this->projects->find($projectId);
        if (!$project || (int)$project['id_user'] !== auth_user()['id_user']) {
            $this->json(['success' => false, 'message' => 'Project tidak ditemukan.'], 404);
        }

        [$oldOk, $oldMessage, $safeOldPath, $fullOldPath] = SafePath::toProjectPath($project['workspace_path'], $oldPath);
        [$newOk, $newMessage, $safeNewPath, $fullNewPath] = SafePath::toProjectPath($project['workspace_path'], $newPath);
        if (!$oldOk || !$newOk) {
            $this->json(['success' => false, 'message' => $oldOk ? $newMessage : $oldMessage], 400);
        }

        if (!file_exists($fullOldPath) || is_link($fullOldPath)) {
            $this->json(['success' => false, 'message' => 'File lama tidak ditemukan.'], 404);
        }
        if (!is_dir($fullOldPath) && !FileValidator::validateExtension($safeNewPath)) {
            $this->json(['success' => false, 'message' => 'Jenis file tujuan tidak diizinkan.'], 400);
        }
        if (file_exists($fullNewPath)) {
            $this->json(['success' => false, 'message' => 'Nama tujuan sudah ada.'], 400);
        }

        $dir = dirname($fullNewPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!rename($fullOldPath, $fullNewPath)) {
            $this->json(['success' => false, 'message' => 'Gagal mengubah nama file/folder.'], 500);
        }

        $isFolder = is_dir($fullNewPath) ? 1 : 0;
        $newName = basename($safeNewPath);
        $ext = $isFolder ? null : pathinfo($newName, PATHINFO_EXTENSION);

        if ($isFolder) {
            $this->projects->query(
                "UPDATE project_files SET relative_path = ?, file_name = ? WHERE id_project = ? AND relative_path = ?",
                [$safeNewPath, $newName, $projectId, $safeOldPath]
            );

            $oldPrefix = $safeOldPath . '/';
            $newPrefix = $safeNewPath . '/';
            $this->projects->query(
                "UPDATE project_files SET relative_path = REPLACE(relative_path, ?, ?) WHERE id_project = ? AND relative_path LIKE ?",
                [$oldPrefix, $newPrefix, $projectId, $oldPrefix . '%']
            );
        } else {
            $this->projects->query(
                "UPDATE project_files SET relative_path = ?, file_name = ?, file_extension = ? WHERE id_project = ? AND relative_path = ?",
                [$safeNewPath, $newName, $ext, $projectId, $safeOldPath]
            );
        }

        $this->projects->query(
            "INSERT INTO activity_logs (id_user, id_project, action, description) VALUES (?, ?, ?, ?)",
            [auth_user()['id_user'], $projectId, 'rename_file', "Ubah nama dari {$safeOldPath} ke {$safeNewPath}"]
        );

        $this->projects->update($projectId, ['updated_at' => date('Y-m-d H:i:s')]);
        $this->json(['success' => true, 'message' => 'Nama berhasil diubah.', 'new_path' => $safeNewPath]);
    }

    /**
     * Upload file asset AJAX
     */
    public function uploadFile(): void
    {
        require_auth();
        verify_csrf();

        $projectId = (int) ($_POST['id_project'] ?? 0);
        $path = trim($_POST['relative_path'] ?? '');
        $file = $_FILES['file'] ?? null;

        if ($projectId === 0 || $path === '' || !$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'Upload tidak valid.'], 400);
        }

        $uploadedName = $file['name'] ?? '';
        $uploadedExt = strtolower(pathinfo($uploadedName, PATHINFO_EXTENSION));
        if ($uploadedExt === 'zip') {
            $this->json(['success' => false, 'message' => 'ZIP harus diimport melalui ZIP Importer.'], 400);
        }
        if (!is_uploaded_file($file['tmp_name']) || !FileValidator::validateExtension($uploadedName)) {
            $this->json(['success' => false, 'message' => 'Jenis file upload tidak diizinkan.'], 400);
        }

        if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
            $this->json(['success' => false, 'message' => 'Ukuran file maksimal 10 MB.'], 400);
        }

        $project = $this->projects->find($projectId);
        if (!$project || (int)$project['id_user'] !== auth_user()['id_user']) {
            $this->json(['success' => false, 'message' => 'Project tidak ditemukan.'], 404);
        }

        [$pathOk, $pathMessage, $safePath, $fullPath] = SafePath::toProjectPath($project['workspace_path'], $path);
        if (!$pathOk || !FileValidator::validateExtension($safePath)) {
            $this->json(['success' => false, 'message' => $pathOk ? 'Jenis file tujuan tidak diizinkan.' : $pathMessage], 400);
        }
        if (strtolower(pathinfo($safePath, PATHINFO_EXTENSION)) !== $uploadedExt) {
            $this->json(['success' => false, 'message' => 'Ekstensi file upload dan tujuan harus sama.'], 400);
        }

        [$destinationOk, $destinationMessage] = SafePath::validateDestinationPath($fullPath, $project['workspace_path']);
        if (!$destinationOk) {
            $this->json(['success' => false, 'message' => $destinationMessage], 400);
        }

        $quarantineDir = app_config('storage_path') . DIRECTORY_SEPARATOR . 'quarantine';
        if (!is_dir($quarantineDir) && !mkdir($quarantineDir, 0700, true) && !is_dir($quarantineDir)) {
            $this->json(['success' => false, 'message' => 'Folder quarantine tidak tersedia.'], 500);
        }
        $quarantinePath = $quarantineDir . DIRECTORY_SEPARATOR . 'upload_' . bin2hex(random_bytes(16)) . '.' . $uploadedExt;
        if (!move_uploaded_file($file['tmp_name'], $quarantinePath)) {
            $this->json(['success' => false, 'message' => 'Gagal menyimpan upload sementara.'], 500);
        }

        [$contentOk, $contentMessage] = FileValidator::validateFileContent($quarantinePath);
        if (!$contentOk) {
            @unlink($quarantinePath);
            $this->json(['success' => false, 'message' => $contentMessage], 400);
        }

        $dir = dirname($fullPath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            @unlink($quarantinePath);
            $this->json(['success' => false, 'message' => 'Gagal membuat folder upload.'], 500);
        }
        if (!rename($quarantinePath, $fullPath)) {
            @unlink($quarantinePath);
            $this->json(['success' => false, 'message' => 'Gagal memindahkan upload tervalidasi.'], 500);
        }
        @chmod($fullPath, 0640);

        $name = basename($safePath);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $size = filesize($fullPath);

        $exists = $this->projects->query(
            "SELECT id_file FROM project_files WHERE id_project = ? AND relative_path = ? LIMIT 1",
            [$projectId, $safePath]
        )->fetch();

        if ($exists) {
            $this->projects->query(
                "UPDATE project_files SET file_name = ?, file_extension = ?, is_folder = 0, file_size = ? WHERE id_file = ?",
                [$name, $ext, $size, $exists['id_file']]
            );
        } else {
            $this->projects->query(
                "INSERT INTO project_files (id_project, file_name, relative_path, file_extension, is_folder, is_editable, file_size) VALUES (?, ?, ?, ?, 0, ?, ?)",
                [$projectId, $name, $safePath, $ext, in_array(strtolower($ext), ['html', 'css', 'js', 'json', 'txt', 'md', 'svg'], true) ? 1 : 0, $size]
            );
        }

        $this->projects->query(
            "INSERT INTO activity_logs (id_user, id_project, action, description) VALUES (?, ?, ?, ?)",
            [auth_user()['id_user'], $projectId, 'upload_file', "Upload file {$safePath}"]
        );

        $this->projects->update($projectId, ['updated_at' => date('Y-m-d H:i:s')]);
        $this->json(['success' => true, 'message' => 'File berhasil diupload.']);
    }

    /**
     * Import ZIP via quarantine + validation.
     */
    public function importZip(): void
    {
        require_auth();
        verify_csrf();

        $projectId = (int) ($_POST['id_project'] ?? 0);
        $targetDir = trim($_POST['target_dir'] ?? '');
        $file = $_FILES['file'] ?? null;

        if ($projectId === 0 || !$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'Upload ZIP tidak valid.'], 400);
        }

        if (($file['size'] ?? 0) > ProjectQuotaService::ZIP_MAX_BYTES) {
            $this->json(['success' => false, 'message' => 'File ZIP maksimal 100 MB.'], 400);
        }

        $uploadedExt = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if ($uploadedExt !== 'zip') {
            $this->json(['success' => false, 'message' => 'File harus berformat .zip.'], 400);
        }

        $project = $this->projects->find($projectId);
        if (!$project || (int)$project['id_user'] !== auth_user()['id_user']) {
            $this->json(['success' => false, 'message' => 'Project tidak ditemukan.'], 404);
        }

        $service = new ZipImportService($this->projects);
        [$success, $message] = $service->import($projectId, $project['workspace_path'], $file['tmp_name'], $targetDir);

        if (!$success) {
            $this->json(['success' => false, 'message' => $message], 400);
        }

        $safeTargetDir = SafePath::normalize($targetDir);
        $this->projects->query(
            "INSERT INTO activity_logs (id_user, id_project, action, description) VALUES (?, ?, ?, ?)",
            [auth_user()['id_user'], $projectId, 'import_zip', "Import ZIP ke " . ($safeTargetDir ?: 'root')]
        );
        $this->projects->update($projectId, ['updated_at' => date('Y-m-d H:i:s')]);

        $this->json(['success' => true, 'message' => $message]);
    }

    /**
     * Move file/folder safely.
     */
    public function moveFile(): void
    {
        require_auth();
        verify_csrf();

        $projectId = (int) ($_POST['id_project'] ?? 0);
        $from = trim($_POST['from'] ?? '');
        $to = trim($_POST['to'] ?? '');
        $overwrite = (int) ($_POST['overwrite'] ?? 0) === 1;

        if ($projectId === 0 || $from === '' || $to === '') {
            $this->json(['success' => false, 'message' => 'Parameter move tidak valid.'], 400);
        }

        $project = $this->projects->find($projectId);
        if (!$project || (int)$project['id_user'] !== auth_user()['id_user']) {
            $this->json(['success' => false, 'message' => 'Project tidak ditemukan.'], 404);
        }

        $service = new FileMoveService($this->projects);
        [$success, $message, $newPath] = array_pad($service->move($projectId, $project['workspace_path'], $from, $to, $overwrite), 3, null);

        if (!$success) {
            $this->json(['success' => false, 'message' => $message], 400);
        }

        $safeFrom = SafePath::normalize($from);
        $safeTo = SafePath::normalize($to);
        $this->projects->query(
            "INSERT INTO activity_logs (id_user, id_project, action, description) VALUES (?, ?, ?, ?)",
            [auth_user()['id_user'], $projectId, 'move_file', "Pindah {$safeFrom} ke {$safeTo}"]
        );
        $this->projects->update($projectId, ['updated_at' => date('Y-m-d H:i:s')]);

        $this->json(['success' => true, 'message' => $message, 'new_path' => $newPath]);
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
