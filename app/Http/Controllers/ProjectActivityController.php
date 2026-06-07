<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProjectActivityController extends Controller
{
    public function __invoke(Request $request, Project $project): View
    {
        $this->authorizeProjectAccess($request, $project);

        $activities = $project->activityLogs()
            ->with(['user', 'issue'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('projects.activity.index', [
            'projects' => $this->userProjects($request),
            'currentProject' => $project,
            'activities' => $activities,
        ]);
    }

    private function authorizeProjectAccess(Request $request, Project $project): void
    {
        abort_unless(
            $project->owner_id === $request->user()->id
                || $project->members()->where('users.id', $request->user()->id)->exists(),
            403
        );
    }

    private function userProjects(Request $request)
    {
        return Project::query()
            ->where('owner_id', $request->user()->id)
            ->orWhereHas('members', fn ($query) => $query->where('users.id', $request->user()->id))
            ->orderBy('name')
            ->get();
    }
}
