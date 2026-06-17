<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesProjectMembership;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProjectActivityController extends Controller
{
    use AuthorizesProjectMembership;

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
}
