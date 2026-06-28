<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesProjectMembership;
use App\Support\SqlDialect;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProjectActivityController extends Controller
{
    use AuthorizesProjectMembership;

    public function __invoke(Request $request, string $project): View
    {
        $currentProject = $this->authorizeProjectAccess($request, $project);

        $perPage = 20;
        $total = (int) (DB::selectOne(
            'SELECT COUNT(*) AS total FROM activity_logs WHERE project_id = ?',
            [$project],
        )->total ?? 0);

        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $perPage;

        $items = SqlDialect::mapActivities(DB::select(
            $this->applyLimitSql(
                'SELECT al.*, u.name AS user_name, i.key AS issue_key
             FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id
             LEFT JOIN issues i ON i.id = al.issue_id
             WHERE al.project_id = ?
             ORDER BY al.created_at DESC',
                $perPage,
                $offset,
            ),
            [$project],
        ));

        $activities = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('projects.activity.index', [
            'projects' => $this->userProjects($request),
            'currentProject' => $currentProject,
            'activities' => $activities,
        ]);
    }
}
