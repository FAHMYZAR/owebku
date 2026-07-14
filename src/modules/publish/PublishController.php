<?php
namespace Modules\Publish;

use Core\Controller;
use Core\Services\FileValidator;
use Core\Services\SafePath;
use Modules\Projects\Project;

class PublishController extends Controller
{
    private Project $projects;

    public function __construct()
    {
        $this->projects = new Project();
    }

    /**
     * Jalankan proses publish project
     */
    public function publish(): void
    {
        require_auth();
        verify_csrf();

        $projectId = (int) ($_POST['id_project'] ?? 0);
        if ($projectId === 0) {
            $this->json(['success' => false, 'message' => 'Parameter tidak valid.'], 400);
        }

        $project = $this->projects->find($projectId);
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

        if (!is_dir($workspace)) {
            $this->json(['success' => false, 'message' => 'Workspace kosong.'], 400);
        }

        // Jalankan sinkronisasi folder (copy workspace -> published)
        $this->copyDir($workspace, $published);

        // Update status project di DB
        $this->projects->update($projectId, [
            'status' => 'published',
            'last_published_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Catat publish job
        $this->projects->query(
            "INSERT INTO publish_jobs (id_project, id_user, status, message, started_at, finished_at) VALUES (?, ?, ?, ?, ?, ?)",
            [$projectId, auth_user()['id_user'], 'success', 'Published successfully', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]
        );

        $this->projects->query(
            "INSERT INTO activity_logs (id_user, id_project, action, description) VALUES (?, ?, ?, ?)",
            [auth_user()['id_user'], $projectId, 'publish_project', "Publish static site untuk {$project['project_name']}"]
        );

        $this->json(['success' => true, 'message' => 'Project berhasil dipublish.', 'public_url' => $project['public_url']]);
    }

    private function copyDir(string $src, string $dst): void
    {
        if (is_dir($dst)) {
            $this->deleteDir($dst);
        }

        if (!@mkdir($dst, 0775, true) && !is_dir($dst)) {
            $this->json([
                'success' => false,
                'message' => 'Gagal membuat folder publish. Periksa permission folder sites di server.'
            ], 500);
        }

        @chmod($dst, 0755);

        $files = array_diff(scandir($src), ['.', '..']);
        foreach ($files as $file) {
            $srcPath = $src . DIRECTORY_SEPARATOR . $file;
            $dstPath = $dst . DIRECTORY_SEPARATOR . $file;

            if (is_link($srcPath)) {
                $this->json(['success' => false, 'message' => 'Symlink tidak boleh dipublish.'], 400);
            }

            if (is_dir($srcPath)) {
                $this->copyDir($srcPath, $dstPath);
                @chmod($dstPath, 0755);
            } else {
                if (!FileValidator::validateExtension($file)) {
                    $this->json(['success' => false, 'message' => "File {$file} tidak aman untuk dipublish."], 400);
                }
                [$contentOk, $contentMessage] = FileValidator::validateFileContent($srcPath);
                if (!$contentOk) {
                    $this->json(['success' => false, 'message' => "File {$file} ditolak: {$contentMessage}"], 400);
                }
                if (!@copy($srcPath, $dstPath)) {
                    $this->json([
                        'success' => false,
                        'message' => 'Gagal menyalin file publish. Periksa permission folder sites di server.'
                    ], 500);
                }
                @chmod($dstPath, 0644);
            }
        }
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
