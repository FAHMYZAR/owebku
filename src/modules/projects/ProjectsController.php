<?php
namespace Modules\Projects;

use Core\Controller;
use Core\Services\SafePath;

class ProjectsController extends Controller
{
    private Project $projects;

    public function __construct()
    {
        $this->projects = new Project();
    }

    /**
     * Buat project baru
     */
    public function create(): void
    {
        require_auth();
        verify_csrf();

        $name = trim($_POST['project_name'] ?? '');
        if ($name === '') {
            $this->json(['success' => false, 'message' => 'Nama project tidak boleh kosong.'], 400);
        }

        $userId = auth_user()['id_user'];
        $slug = $this->createSlug($name);
        
        $baseWorkspace = app_config('workspace_path') . DIRECTORY_SEPARATOR . auth_user()['username'] . DIRECTORY_SEPARATOR . $slug;
        $basePublished = app_config('sites_path') . DIRECTORY_SEPARATOR . auth_user()['username'] . DIRECTORY_SEPARATOR . $slug;
        $publicUrl = site_url("sites/" . auth_user()['username'] . "/" . $slug);

        // Buat folder fisik sebelum insert DB agar error permission tidak menghasilkan response HTML/warning.
        if (!is_dir($baseWorkspace) && !@mkdir($baseWorkspace, 0775, true) && !is_dir($baseWorkspace)) {
            $this->json([
                'success' => false,
                'message' => 'Gagal membuat folder workspace. Periksa permission folder storage/workspaces di server.'
            ], 500);
        }

        $initialFile = $baseWorkspace . DIRECTORY_SEPARATOR . 'index.html';
        $initialContent = "<h1>Welcome to {$name}</h1>\n<p>Start editing!</p>";
        if (@file_put_contents($initialFile, $initialContent) === false) {
            $this->deleteDir($baseWorkspace);
            $this->json([
                'success' => false,
                'message' => 'Gagal membuat file awal project. Periksa permission folder workspace di server.'
            ], 500);
        }

        $fileSize = filesize($initialFile) ?: 0;

        $id = $this->projects->insert([
            'id_user' => $userId,
            'project_name' => $name,
            'slug' => $slug,
            'workspace_path' => $baseWorkspace . DIRECTORY_SEPARATOR,
            'published_path' => $basePublished . DIRECTORY_SEPARATOR,
            'public_url' => $publicUrl,
            'status' => 'draft'
        ]);

        if ($id) {
            // Simpan info ke DB files
            $this->projects->query(
                "INSERT INTO project_files (id_project, file_name, relative_path, file_extension, is_editable, file_size) VALUES (?, ?, ?, ?, ?, ?)",
                [$id, 'index.html', 'index.html', 'html', 1, $fileSize]
            );

            // Log activity
            $this->projects->query(
                "INSERT INTO activity_logs (id_user, id_project, action, description) VALUES (?, ?, ?, ?)",
                [$userId, $id, 'create_project', "Membuat project {$name}"]
            );

            $_SESSION['flash_success'] = 'Project berhasil dibuat.';
            $this->json(['success' => true, 'message' => 'Project berhasil dibuat.', 'data' => ['redirect' => site_url('dashboard')]]);
        }

        $this->deleteDir($baseWorkspace);
        $this->json(['success' => false, 'message' => 'Gagal membuat project.'], 500);
    }

    /**
     * Rename project
     */
    public function rename(): void
    {
        require_auth();
        verify_csrf();

        $id = (int) ($_POST['id_project'] ?? 0);
        $name = trim($_POST['project_name'] ?? '');

        if ($id === 0 || $name === '') {
            $this->json(['success' => false, 'message' => 'Data tidak valid.'], 400);
        }

        $project = $this->projects->find($id);
        if (!$project || (int)$project['id_user'] !== auth_user()['id_user']) {
            $this->json(['success' => false, 'message' => 'Project tidak ditemukan.'], 404);
        }

        $this->projects->update($id, ['project_name' => $name]);

        $this->projects->query(
            "INSERT INTO activity_logs (id_user, id_project, action, description) VALUES (?, ?, ?, ?)",
            [auth_user()['id_user'], $id, 'rename_project', "Ubah nama project ke {$name}"]
        );

        $_SESSION['flash_success'] = 'Project berhasil diganti nama.';
        $this->json(['success' => true, 'message' => 'Nama project diperbarui.', 'data' => ['redirect' => site_url('dashboard')]]);
    }

    /**
     * Hapus project
     */
    public function delete(): void
    {
        require_auth();
        verify_csrf();

        $id = (int) ($_POST['id_project'] ?? 0);
        if ($id === 0) {
            $this->json(['success' => false, 'message' => 'ID tidak valid.'], 400);
        }

        $project = $this->projects->find($id);
        if (!$project || (int)$project['id_user'] !== auth_user()['id_user']) {
            $this->json(['success' => false, 'message' => 'Project tidak ditemukan.'], 404);
        }

        $workspace = rtrim($project['workspace_path'], DIRECTORY_SEPARATOR);
        $published = rtrim($project['published_path'], DIRECTORY_SEPARATOR);
        [$workspaceOk, $workspaceMessage] = SafePath::validateOwnedDirectory($workspace, app_config('workspace_path'));
        [$publishedOk, $publishedMessage] = SafePath::validateOwnedDirectory($published, app_config('sites_path'));
        if (!$workspaceOk || (!$publishedOk && file_exists($published))) {
            $this->json(['success' => false, 'message' => $workspaceOk ? $publishedMessage : $workspaceMessage], 400);
        }

        $this->projects->delete($id);

        $this->deleteDir($workspace);
        $this->deleteDir($published);

        $this->projects->query(
            "INSERT INTO activity_logs (id_user, id_project, action, description) VALUES (?, ?, ?, ?)",
            [auth_user()['id_user'], null, 'delete_project', "Hapus project {$project['project_name']}"]
        );

        $_SESSION['flash_success'] = 'Project berhasil dihapus.';
        $this->json(['success' => true, 'message' => 'Project dihapus.', 'data' => ['redirect' => site_url('dashboard')]]);
    }

    /**
     * Helper slug
     */
    private function createSlug(string $string): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
        $slug = preg_replace('/-+/', '-', $slug);
        return $slug . '-' . substr(md5(uniqid()), 0, 4);
    }

    /**
     * Helper recursive delete directory
     */
    private function deleteDir(?string $dirPath): void
    {
        if (empty($dirPath) || !is_dir($dirPath) || is_link($dirPath)) {
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
