<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesProjectMembership;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/*
 * Analytics area. Each section showcases a core SQL feature from the syllabus:
 * set operators (UNION / UNION ALL / INTERSECT / MINUS), window functions
 * (RANK / DENSE_RANK / SUM OVER), and HAVING.
 */
class ReportController extends Controller
{
    use AuthorizesProjectMembership;

    public function __invoke(Request $request, string $project): View
    {
        $currentProject = $this->authorizeProjectAccess($request, $project);
        $userId = $request->user()->id;

        return view('projects.reports.index', [
            'projects' => $this->userProjects($request),
            'currentProject' => $currentProject,
            'leaderboard' => $this->leaderboard($project),        // RANK / DENSE_RANK
            'onboarded' => $this->onboardedMembers($project),     // INTERSECT
            'unassigned' => $this->membersOnNoTeam($project),     // MINUS
            'myWork' => $this->myWork($project, $userId),         // UNION
            'timeline' => $this->unifiedTimeline($project),       // UNION ALL
            'velocity' => $this->cumulativeVelocity($project),    // SUM() OVER
            'busyTeams' => $this->busyTeams($project),            // HAVING
        ]);
    }

    /** RANK() / DENSE_RANK() analytic ranking of contributors. */
    private function leaderboard(string $project): Collection
    {
        return collect(DB::select(
            "SELECT u.name,
                    COUNT(i.id) AS assigned,
                    NVL(SUM(CASE WHEN i.status = 'done' THEN 1 ELSE 0 END), 0) AS done,
                    NVL(SUM(i.story_points), 0) AS points,
                    RANK() OVER (ORDER BY NVL(SUM(CASE WHEN i.status = 'done' THEN 1 ELSE 0 END), 0) DESC) AS rnk,
                    DENSE_RANK() OVER (ORDER BY NVL(SUM(i.story_points), 0) DESC) AS points_rnk
             FROM project_members pm
             JOIN users u ON u.id = pm.user_id
             LEFT JOIN issues i ON i.assignee_id = u.id AND i.project_id = pm.project_id
             WHERE pm.project_id = ?
             GROUP BY u.id, u.name
             ORDER BY rnk, u.name",
            [$project],
        ));
    }

    /** INTERSECT: members that belong to the project AND to at least one team. */
    private function onboardedMembers(string $project): Collection
    {
        return collect(DB::select(
            'SELECT u.name FROM users u JOIN (
                SELECT user_id FROM project_members WHERE project_id = ?
                INTERSECT
                SELECT tm.user_id FROM team_members tm
                JOIN teams t ON t.id = tm.team_id
                WHERE t.project_id = ?
             ) x ON x.user_id = u.id
             ORDER BY u.name',
            [$project, $project],
        ));
    }

    /** MINUS: project members that are on no team. */
    private function membersOnNoTeam(string $project): Collection
    {
        return collect(DB::select(
            'SELECT u.name FROM users u JOIN (
                SELECT user_id FROM project_members WHERE project_id = ?
                MINUS
                SELECT tm.user_id FROM team_members tm
                JOIN teams t ON t.id = tm.team_id
                WHERE t.project_id = ?
             ) x ON x.user_id = u.id
             ORDER BY u.name',
            [$project, $project],
        ));
    }

    /** UNION: issues where the current user is assignee or reporter. */
    private function myWork(string $project, string $userId): Collection
    {
        return collect(DB::select(
            "SELECT key, title, status, 'Assignee' AS role
             FROM issues WHERE project_id = ? AND assignee_id = ?
             UNION
             SELECT key, title, status, 'Reporter' AS role
             FROM issues WHERE project_id = ? AND reporter_id = ?
             ORDER BY key",
            [$project, $userId, $project, $userId],
        ));
    }

    /** UNION ALL: comments and activity merged into one time-ordered stream. */
    private function unifiedTimeline(string $project): Collection
    {
        return collect(DB::select(
            "SELECT * FROM (
                SELECT 'comment' AS kind, c.created_at AS ts, u.name AS actor,
                       i.key AS issue_key, SUBSTR(c.body, 1, 120) AS detail
                FROM comments c
                JOIN issues i ON i.id = c.issue_id
                LEFT JOIN users u ON u.id = c.user_id
                WHERE i.project_id = ?
                UNION ALL
                SELECT 'activity' AS kind, al.created_at AS ts, u.name AS actor,
                       i.key AS issue_key, al.action AS detail
                FROM activity_logs al
                LEFT JOIN issues i ON i.id = al.issue_id
                LEFT JOIN users u ON u.id = al.user_id
                WHERE al.project_id = ? AND al.user_id IS NOT NULL
             ) WHERE ROWNUM <= 25
             ORDER BY ts DESC",
            [$project, $project],
        ));
    }

    /** SUM() OVER (ORDER BY ...): running cumulative of completed story points. */
    private function cumulativeVelocity(string $project): Collection
    {
        return collect(DB::select(
            "SELECT s.name,
                    NVL(SUM(CASE WHEN i.status = 'done' THEN i.story_points END), 0) AS points,
                    SUM(NVL(SUM(CASE WHEN i.status = 'done' THEN i.story_points END), 0))
                        OVER (ORDER BY s.start_date, s.id) AS cumulative
             FROM sprints s
             LEFT JOIN issues i ON i.sprint_id = s.id
             WHERE s.project_id = ?
             GROUP BY s.id, s.name, s.start_date
             ORDER BY s.start_date, s.id",
            [$project],
        ));
    }

    /** HAVING: teams that currently carry open issues. */
    private function busyTeams(string $project): Collection
    {
        return collect(DB::select(
            "SELECT t.name, COUNT(i.id) AS open_issues
             FROM teams t
             LEFT JOIN issues i ON i.team_id = t.id AND i.status != 'done'
             WHERE t.project_id = ?
             GROUP BY t.id, t.name
             HAVING COUNT(i.id) > 0
             ORDER BY open_issues DESC",
            [$project],
        ));
    }
}
