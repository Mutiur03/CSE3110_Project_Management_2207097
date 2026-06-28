<?php

namespace App\Http\Controllers\Concerns;

use App\Support\SqlDialect;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait AuthorizesProjectMembership
{
    protected function authorizeProjectAccess(Request $request, string $projectId): object
    {
        $project = SqlDialect::normalizeProject(DB::selectOne('SELECT * FROM projects WHERE id = ?', [$projectId]));
        abort_if($project === null, 404);

        $userId = $request->user()->id;

        if ($project->owner_id === $userId) {
            return $this->withProjectAccessFlags($project, $userId);
        }

        $member = DB::selectOne(
            'SELECT role FROM project_members WHERE project_id = ? AND user_id = ?',
            [$projectId, $userId],
        );

        abort_if($member === null, 403);

        return $this->withProjectAccessFlags($project, $userId, $member->role);
    }

    protected function authorizeProjectWrite(Request $request, string $projectId): object
    {
        $project = $this->authorizeProjectAccess($request, $projectId);
        abort_unless($project->can_write, 403);

        return $project;
    }

    protected function authorizeProjectManagement(Request $request, string $projectId): object
    {
        $project = $this->authorizeProjectAccess($request, $projectId);
        abort_unless($project->can_manage, 403);

        return $project;
    }

    protected function userProjects(Request $request): Collection
    {
        $userId = $request->user()->id;

        return SqlDialect::mapProjects(DB::select(
            'SELECT p.*
             FROM projects p
             WHERE p.owner_id = ?
                OR EXISTS (
                    SELECT 1
                    FROM project_members pm
                    WHERE pm.project_id = p.id
                      AND pm.user_id = ?
                )
             ORDER BY p.name',
            [$userId, $userId],
        ))->map(fn ($project) => $this->withProjectAccessFlags($project, $userId));
    }

    protected function projectMemberRole(string $projectId, string $userId): ?string
    {
        $project = DB::selectOne('SELECT owner_id FROM projects WHERE id = ?', [$projectId]);

        if ($project === null) {
            return null;
        }

        if ($project->owner_id === $userId) {
            return 'project_owner';
        }

        $member = DB::selectOne(
            'SELECT role FROM project_members WHERE project_id = ? AND user_id = ?',
            [$projectId, $userId],
        );

        return $member->role ?? null;
    }

    protected function withProjectAccessFlags(object $project, string $userId, ?string $role = null): object
    {
        $role ??= $this->projectMemberRole($project->id, $userId);
        $project->member_role = $role;
        $project->can_write = $project->status !== 'archived'
            && in_array($role, ['project_owner', 'scrum_master', 'developer', 'admin'], true);
        $project->can_manage = in_array($role, ['project_owner', 'scrum_master', 'admin'], true);

        return $project;
    }

    protected function logActivity(
        string $projectId,
        string $userId,
        string $action,
        string $subjectType,
        string $subjectId,
        ?string $issueId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): void {
        $now = now()->toDateTimeString();

        DB::insert(
            'INSERT INTO activity_logs (id, project_id, issue_id, user_id, action, subject_type, subject_id, old_values, new_values, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                (string) Str::uuid(),
                $projectId,
                $issueId,
                $userId,
                $action,
                $subjectType,
                $subjectId,
                $oldValues !== null ? json_encode($oldValues) : null,
                $newValues !== null ? json_encode($newValues) : null,
                $now,
                $now,
            ],
        );
    }

    protected function pushNotification(
        string $userId,
        string $title,
        string $message,
        string $url,
        ?string $projectId = null,
        ?string $issueId = null,
    ): void {
        $now = now()->toDateTimeString();

        DB::insert(
            'INSERT INTO notifications (id, type, notifiable_type, notifiable_id, data, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                (string) Str::uuid(),
                'App\Notifications\ProjectEventNotification',
                'App\Models\User',
                $userId,
                json_encode([
                    'title' => $title,
                    'message' => $message,
                    'url' => $url,
                    'project_id' => $projectId,
                    'issue_id' => $issueId,
                ]),
                $now,
                $now,
            ],
        );

        try {
            event(new \App\Events\ProjectNotificationPushed($userId));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
