<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $projects = Project::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('members', fn ($query) => $query->where('users.id', $user->id))
            ->orderBy('name')
            ->get();

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

        $currentProject->loadCount(['teams', 'issues', 'members']);

        $teams = $currentProject->teams()
            ->withCount(['members', 'issues'])
            ->orderBy('name')
            ->get();

        $activeSprint = $currentProject->sprints()
            ->where('status', 'active')
            ->withCount('issues')
            ->latest('start_date')
            ->first();

        $issuesQuery = $currentProject->issues();
        $sprintIssuesQuery = $activeSprint ? $activeSprint->issues() : $currentProject->issues();
        $sprintIssueCount = (clone $sprintIssuesQuery)->count();
        $doneIssueCount = (clone $sprintIssuesQuery)->where('status', 'done')->count();
        $sprintProgress = $sprintIssueCount > 0 ? round(($doneIssueCount / $sprintIssueCount) * 100) : 0;

        $stats = [
            [
                'label' => 'Project teams',
                'value' => (string) $currentProject->teams_count,
                'note' => $teams->pluck('name')->take(3)->join(', ') ?: 'No teams yet',
                'tone' => 'bg-sky-50 text-sky-700 border-sky-200',
            ],
            [
                'label' => 'Open issues',
                'value' => (string) (clone $issuesQuery)->whereNot('status', 'done')->count(),
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
                'value' => (string) (clone $issuesQuery)->where('status', 'review')->count(),
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

        $issuesByStatus = (clone $sprintIssuesQuery)
            ->with(['team', 'assignee'])
            ->orderBy('created_at')
            ->get()
            ->groupBy('status');

        $boardColumns = collect($workflow)->map(fn ($label, $status) => [
            'status' => $status,
            'stage' => $label,
            'issues' => $issuesByStatus->get($status, collect()),
        ]);

        $backlogCounts = $currentProject->issues()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $activities = $currentProject->activityLogs()
            ->with(['user', 'issue'])
            ->latest()
            ->limit(5)
            ->get();

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
