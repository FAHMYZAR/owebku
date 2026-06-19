<?php
namespace Modules\Projects;

use Core\Model;

class Project extends Model
{
    protected string $table = 'projects';
    protected string $primaryKey = 'id_project';

    /**
     * Ambil project milik user beserta statistik file
     */
    public function getDashboardProjects(int $userId): array
    {
        $sql = "
            SELECT 
                p.*,
                COALESCE(SUM(CASE WHEN pf.is_folder = 0 THEN 1 ELSE 0 END), 0) AS total_files,
                COALESCE(SUM(CASE WHEN pf.is_folder = 1 THEN 1 ELSE 0 END), 0) AS total_folders,
                COALESCE(SUM(pf.file_size), 0) AS total_size_bytes
            FROM projects p
            LEFT JOIN project_files pf ON pf.id_project = p.id_project
            WHERE p.id_user = ?
            GROUP BY p.id_project
            ORDER BY COALESCE(p.updated_at, p.created_at) DESC
        ";

        return $this->query($sql, [$userId])->fetchAll();
    }

    /**
     * Hitung total storage user
     */
    public function getUserStorageBytes(int $userId): int
    {
        $sql = "
            SELECT COALESCE(SUM(pf.file_size), 0) AS total_bytes
            FROM projects p
            LEFT JOIN project_files pf ON pf.id_project = p.id_project
            WHERE p.id_user = ?
        ";

        $row = $this->query($sql, [$userId])->fetch();
        return (int) ($row['total_bytes'] ?? 0);
    }

    /**
     * Ambil aktivitas terbaru user
     */
    public function getRecentActivities(int $userId, int $limit = 2): array
    {
        $limit = max(1, min(100, $limit));

        $sql = "
            SELECT al.*, p.project_name
            FROM activity_logs al
            LEFT JOIN projects p ON p.id_project = al.id_project
            WHERE al.id_user = ?
            ORDER BY al.created_at DESC
            LIMIT {$limit}
        ";

        return $this->query($sql, [$userId])->fetchAll();
    }
}
