<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesProjectMembership;
use App\Support\SqlDialect;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use AuthorizesProjectMembership;

    public function __invoke(Request $request): View
    {
        $userId = $request->user()->id;
        $projects = $this->userProjects($request);
        $currentProject = $projects->firstWhere('id', $request->query('project')) ?? $projects->first();

        if (! $currentProject) {
            return view('dashboard', [
                'projects' => $projects,
                'currentProject' => null,
                'stats' => [],
                'teams' => collect(),
                'activeSprint' => null,
                'sprintProgress' => 0,
                'boardColumns' => collect(),
                'backlogCounts' => collect(),
                'activities' => collect(),
            ]);
        }

        $projectId = $currentProject->id;
        $currentProject = $this->withProjectAccessFlags($currentProject, $userId);

        $counts = DB::selectOne(
            'SELECT
                (SELECT COUNT(*) FROM teams WHERE project_id = ?) AS teams_count,
                (SELECT COUNT(*) FROM issues WHERE project_id = ?) AS issues_count,
                (SELECT COUNT(*) FROM project_members WHERE project_id = ?) AS members_count'
            .SqlDialect::dualFrom(),
            [$projectId, $projectId, $projectId],
        );

        $currentProject->teams_count = (int) ($counts->teams_count ?? 0);
        $currentProject->issues_count = (int) ($counts->issues_count ?? 0);
        $currentProject->members_count = (int) ($counts->members_count ?? 0);

        $teams = SqlDialect::mapTeams(DB::select(
            'SELECT t.*,
                    (SELECT COUNT(*) FROM team_members tm WHERE tm.team_id = t.id) AS members_count,
                    (SELECT COUNT(*) FROM issues i WHERE i.team_id = t.id) AS issues_count
             FROM teams t
             WHERE t.project_id = ?
             ORDER BY t.name',
            [$projectId],
        ));

        $activeSprint = SqlDialect::normalizeSprint(DB::selectOne(
            "SELECT s.*,
                    (SELECT COUNT(*) FROM issues i WHERE i.sprint_id = s.id) AS issues_count
             FROM sprints s
             WHERE s.project_id = ? AND s.status = 'active'
             ORDER BY s.start_date DESC
            ",
            [$projectId],
        ));

        $sprintIssueCount = (int) (DB::selectOne(
            $activeSprint
                ? 'SELECT COUNT(*) AS total FROM issues WHERE sprint_id = ?'
                : 'SELECT COUNT(*) AS total FROM issues WHERE project_id = ?',
            $activeSprint ? [$activeSprint->id] : [$projectId],
        )->total ?? 0);

        $doneIssueCount = (int) (DB::selectOne(
            $activeSprint
                ? "SELECT COUNT(*) AS total FROM issues WHERE sprint_id = ? AND status = 'done'"
                : "SELECT COUNT(*) AS total FROM issues WHERE project_id = ? AND status = 'done'",
            $activeSprint ? [$activeSprint->id] : [$projectId],
        )->total ?? 0);

        $sprintProgress = $sprintIssueCount > 0 ? round(($doneIssueCount / $sprintIssueCount) * 100) : 0;

        $openIssues = (int) (DB::selectOne(
            "SELECT COUNT(*) AS total FROM issues WHERE project_id = ? AND status != 'done'",
            [$projectId],
        )->total ?? 0);

        $reviewIssues = (int) (DB::selectOne(
            "SELECT COUNT(*) AS total FROM issues WHERE project_id = ? AND status = 'review'",
            [$projectId],
        )->total ?? 0);

        $stats = [
            [
                'label' => 'Project teams',
                'value' => (string) $currentProject->teams_count,
                'note' => $teams->pluck('name')->take(3)->join(', ') ?: 'No teams yet',
                'tone' => 'bg-sky-50 text-sky-700 border-sky-200',
            ],
            [
                'label' => 'Open issues',
                'value' => (string) $openIssues,
                'note' => 'Across this project only',
                'tone' => 'bg-purple-50 text-purple-700 border-purple-200',
            ],
            [
                'label' => 'Sprint progress',
                'value' => $sprintProgress . '%',
                'note' => $activeSprint ? $activeSprint->name : 'No active sprint',
                'tone' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ],
            [
                'label' => 'Pending review',
                'value' => (string) $reviewIssues,
                'note' => 'Waiting for team review',
                'tone' => 'bg-amber-50 text-amber-700 border-amber-200',
            ],
        ];

        $workflow = [
            'backlog' => 'Backlog',
            'selected' => 'Selected',
            'in_progress' => 'In Progress',
            'review' => 'Review',
            'done' => 'Done',
        ];

        $issues = SqlDialect::mapIssues(DB::select(
            $activeSprint
                ? 'SELECT i.*, assignee.name AS assignee_name, team.name AS team_name
                   FROM issues i
                   LEFT JOIN users assignee ON assignee.id = i.assignee_id
                   LEFT JOIN teams team ON team.id = i.team_id
                   WHERE i.sprint_id = ?
                   ORDER BY i.created_at'
                : 'SELECT i.*, assignee.name AS assignee_name, team.name AS team_name
                   FROM issues i
                   LEFT JOIN users assignee ON assignee.id = i.assignee_id
                   LEFT JOIN teams team ON team.id = i.team_id
                   WHERE i.project_id = ?
                   ORDER BY i.created_at',
            $activeSprint ? [$activeSprint->id] : [$projectId],
        ));

        $issuesByStatus = $issues->groupBy('status');

        $boardColumns = collect($workflow)->map(fn ($label, $status) => [
            'status' => $status,
            'stage' => $label,
            'issues' => $issuesByStatus->get($status, collect()),
        ]);

        $backlogCounts = collect(DB::select(
            'SELECT type, COUNT(*) AS total FROM issues WHERE project_id = ? GROUP BY type',
            [$projectId],
        ))->pluck('total', 'type');

        $activities = SqlDialect::mapActivities(DB::select(
            $this->applyLimitSql(
                'SELECT al.*, u.name AS user_name, i.key AS issue_key
             FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id
             LEFT JOIN issues i ON i.id = al.issue_id
             WHERE al.project_id = ?
             ORDER BY al.created_at DESC',
                5,
            ),
            [$projectId],
        ));

        return view('dashboard', [
            'projects' => $projects,
            'currentProject' => $currentProject,
            'stats' => $stats,
            'teams' => $teams,
            'activeSprint' => $activeSprint,
            'sprintProgress' => $sprintProgress,
            'boardColumns' => $boardColumns,
            'backlogCounts' => $backlogCounts,
            'activities' => $activities,
        ]);
    }
}
