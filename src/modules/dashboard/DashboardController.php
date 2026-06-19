<?php
namespace Modules\Dashboard;

use Core\Controller;
use Modules\Projects\Project;

class DashboardController extends Controller
{
    private Project $projects;

    public function __construct()
    {
        $this->projects = new Project();
    }

    public function index(): void
    {
        require_auth();

        $userId = auth_user()['id_user'];
        
        $userProjects = $this->projects->getDashboardProjects($userId);
        $usageBytes = $this->projects->getUserStorageBytes($userId);
        $activities = $this->projects->getRecentActivities($userId, 2);

        $this->render('dashboard.index', [
            'page_title' => 'Dashboard',
            'layout_variant' => 'app',
            'projects' => $userProjects,
            'activities' => $activities,
            'account_usage_bytes' => $usageBytes,
        ]);
    }

    /**
     * Halaman full activities
     */
    public function activities(): void
    {
        require_auth();

        $userId = auth_user()['id_user'];
        // Tampilkan 100 log terakhir
        $activities = $this->projects->getRecentActivities($userId, 100);

        $this->render('dashboard.activities', [
            'page_title' => 'Activities',
            'layout_variant' => 'app',
            'activities' => $activities,
        ]);
    }
}
