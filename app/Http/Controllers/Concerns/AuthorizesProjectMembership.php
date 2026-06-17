<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Project;
use Illuminate\Http\Request;

trait AuthorizesProjectMembership
{
    protected function authorizeProjectAccess(Request $request, Project $project): void
    {
        abort_unless(
            $project->owner_id === $request->user()->id
                || $project->members()->where('users.id', $request->user()->id)->exists(),
            403
        );
    }

    protected function authorizeProjectWrite(Request $request, Project $project): void
    {
        $this->authorizeProjectAccess($request, $project);

        abort_unless($project->userCanWrite($request->user()), 403);
    }

    protected function authorizeProjectManagement(Request $request, Project $project): void
    {
        $this->authorizeProjectAccess($request, $project);

        abort_unless($project->userCanManage($request->user()), 403);
    }

    protected function userProjects(Request $request)
    {
        return Project::query()
            ->where('owner_id', $request->user()->id)
            ->orWhereHas('members', fn ($query) => $query->where('users.id', $request->user()->id))
            ->orderBy('name')
            ->get();
    }
}
