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

        $statsRow = DB::selectOne(
            'SELECT issue_count, open_count, progress_pct, health, team_count, member_count
             FROM v_project_stats WHERE project_id = ?',
            [$projectId],
        );

        $currentProject->teams_count = (int) ($statsRow->team_count ?? 0);
        $currentProject->issues_count = (int) ($statsRow->issue_count ?? 0);
        $currentProject->members_count = (int) ($statsRow->member_count ?? 0);

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

        $openIssues = (int) ($statsRow->open_count ?? 0);
        $projectHealth = SqlDialect::clobToString($statsRow->health ?? null) ?? 'empty';

        $reviewIssues = (int) (DB::selectOne(
            "SELECT COUNT(*) AS total FROM issues WHERE project_id = ? AND status = 'review'",
            [$projectId],
        )->total ?? 0);

        // Story points completed in the active sprint.
        $velocity = $activeSprint
            ? (int) (DB::selectOne(
                'SELECT fn_sprint_velocity(?) AS points'.SqlDialect::dualFrom(),
                [$activeSprint->id],
            )->points ?? 0)
            : 0;

        $stats = [
            [
                'label' => 'Teams',
                'value' => (string) $currentProject->teams_count,
                'note' => $teams->pluck('name')->take(2)->join(', ') ?: 'No teams yet',
            ],
            [
                'label' => 'Open issues',
                'value' => (string) $openIssues,
                'note' => 'Not done or in review',
            ],
            [
                'label' => 'Sprint progress',
                'value' => $sprintProgress . '%',
                'note' => $activeSprint ? $activeSprint->name : 'No active sprint',
            ],
            [
                'label' => 'In review',
                'value' => (string) $reviewIssues,
                'note' => 'Awaiting team review',
            ],
            [
                'label' => 'Velocity',
                'value' => (string) $velocity,
                'note' => $activeSprint ? 'Story points done this sprint' : 'No active sprint',
            ],
            [
                'label' => 'Health',
                'value' => ucfirst(str_replace('_', ' ', $projectHealth)),
                'note' => 'Overall project health',
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
                ? 'SELECT * FROM v_issue_full WHERE sprint_id = ? ORDER BY created_at'
                : 'SELECT * FROM v_issue_full WHERE project_id = ? ORDER BY created_at',
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
