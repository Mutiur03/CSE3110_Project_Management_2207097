@php
    use App\Support\BadgeTones;

    $hasProject = filled($currentProject);
    $canWriteDashboard = $hasProject && (bool) ($currentProject->can_write ?? false);

    $features = [
        ['title' => 'Create project workspaces', 'description' => 'Start each Scrum workspace as a project with its own key, members, backlog, and sprint history.'],
        ['title' => 'Organize multiple teams', 'description' => 'Add frontend, backend, QA, or custom teams under a project and track their workload separately.'],
        ['title' => 'Plan sprints from backlog', 'description' => 'Move project issues into a sprint and follow them through Jira-style workflow columns.'],
        ['title' => 'Review project activity', 'description' => 'Keep comments, status changes, assignments, and sprint updates visible for the current project.'],
    ];

    $typeLabels = [
        'epic' => 'Epics',
        'story' => 'Stories',
        'task' => 'Tasks',
        'subtask' => 'Subtasks',
        'bug' => 'Bugs',
    ];

    $statusLabels = [
        'backlog' => 'Backlog',
        'selected' => 'Selected',
        'in_progress' => 'In progress',
        'review' => 'Review',
        'done' => 'Done',
    ];
@endphp

<x-dashboard.layout title="Dashboard" eyebrow="Project workspace" :current-project="$currentProject" :projects="$projects">
    @if (! $hasProject)
        <div class="overflow-hidden rounded-lg border border-hairline bg-white">
            <div class="border-l-2 border-l-accent p-6 sm:p-8">
                <div class="max-w-2xl">
                    <p class="deck-label text-accent">Get started</p>
                    <h2 class="mt-2 font-display text-2xl font-bold tracking-tight text-ink">Create your first workspace</h2>
                    <p class="mt-3 text-sm leading-6 text-muted-foreground">
                        Every workspace is a project — its teams, backlog, sprints, board, and activity all live inside it.
                    </p>
                    <a href="{{ route('projects.create') }}" wire:navigate
                        class="mt-5 inline-flex rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong">
                        Create project
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($features as $feature)
                <x-dashboard.feature-card :title="$feature['title']" :description="$feature['description']" />
            @endforeach
        </div>
    @else
        @if ($currentProject->status === 'archived')
            <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                This project is archived. Work is read-only until a project owner or scrum master reactivates it
                @if ($currentProject->can_manage ?? false)
                    in <a href="{{ route('projects.settings.edit', $currentProject->id) }}" wire:navigate class="font-semibold underline-offset-4 hover:underline">settings</a>.
                @else
                    from project settings.
                @endif
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $stat)
                <x-dashboard.stat-card :label="$stat['label']" :value="$stat['value']" :note="$stat['note']" />
            @endforeach
        </div>

        <x-dashboard.issue-type-summary class="mt-4" :type-labels="$typeLabels" :backlog-counts="$backlogCounts"
            :project-id="$currentProject->id" />

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <section class="rounded-lg border border-hairline bg-white p-5 lg:col-span-2">
                <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="deck-label text-muted-foreground">Active sprint</h2>
                        <p class="mt-1 truncate text-sm font-semibold text-ink">
                            {{ $activeSprint?->name ?? 'No active sprint' }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($activeSprint)
                            <span class="font-display text-xl font-bold tabular-nums text-ink">{{ $sprintProgress }}%</span>
                            <span class="text-muted-foreground">·</span>
                        @endif
                        <a href="{{ route('projects.board.index', $currentProject->id) }}" wire:navigate
                            class="text-sm font-semibold text-accent transition hover:text-accent-strong">Board</a>
                        <a href="{{ route('projects.sprints.index', $currentProject->id) }}" wire:navigate
                            class="text-sm font-semibold text-accent transition hover:text-accent-strong">Sprints</a>
                    </div>
                </div>

                @if ($activeSprint)
                    <div class="mb-5 h-1.5 overflow-hidden rounded-full bg-canvas">
                        <div class="h-full rounded-full bg-accent transition-all" style="width: {{ max(2, (int) $sprintProgress) }}%"></div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                        @foreach ($boardColumns as $column)
                            <div class="rounded-md border border-hairline bg-canvas px-3 py-2.5 text-center">
                                <p class="deck-label text-muted-foreground">{{ $column['stage'] }}</p>
                                <p class="mt-1 font-display text-lg font-bold tabular-nums text-ink">{{ $column['issues']->count() }}</p>
                            </div>
                        @endforeach
                    </div>

                    @php
                        $sprintIssues = $boardColumns->flatMap(fn ($column) => $column['issues'])->take(5);
                    @endphp

                    @if ($sprintIssues->isNotEmpty())
                        <div class="mt-5 border-t border-hairline pt-4">
                            <p class="deck-label mb-3 text-muted-foreground">In this sprint</p>
                            <ul class="divide-y divide-hairline">
                                @foreach ($sprintIssues as $issue)
                                    <li class="flex items-center justify-between gap-3 py-2.5 first:pt-0 last:pb-0">
                                        <div class="min-w-0">
                                            <a href="{{ route('projects.issues.show', [$currentProject->id, $issue->id]) }}" wire:navigate
                                                class="truncate text-sm font-semibold text-ink transition hover:text-accent">
                                                {{ $issue->title }}
                                            </a>
                                            <p class="mt-0.5 font-mono text-[11px] text-muted-foreground">{{ $issue->key }}</p>
                                        </div>
                                        <x-ui.badge class="shrink-0" :tone="BadgeTones::issueStatus()[$issue->status] ?? BadgeTones::NEUTRAL">
                                            {{ $statusLabels[$issue->status] ?? $issue->status }}
                                        </x-ui.badge>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @else
                    <div class="rounded-md border border-dashed border-hairline px-5 py-8 text-center">
                        <p class="text-sm text-muted-foreground">No sprint is active yet.</p>
                        <a href="{{ route('projects.sprints.index', $currentProject->id) }}" wire:navigate
                            class="mt-2 inline-block text-sm font-semibold text-accent transition hover:text-accent-strong">
                            Plan a sprint &rarr;
                        </a>
                    </div>
                @endif
            </section>

            <section class="rounded-lg border border-hairline bg-white p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="deck-label text-muted-foreground">Recent activity</h2>
                    <a href="{{ route('projects.activity.index', $currentProject->id) }}" wire:navigate
                        class="text-sm font-semibold text-accent transition hover:text-accent-strong">
                        View all
                    </a>
                </div>

                <div class="space-y-3.5">
                    @forelse ($activities as $activity)
                        <div class="flex gap-3">
                            <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-accent"></span>
                            <div class="min-w-0">
                                <p class="text-sm leading-5 text-ink">
                                    <span class="font-semibold">{{ $activity->user_name ?? 'System' }}</span> {{ $activity->action }}
                                    @if ($activity->issue_key)
                                        <span class="font-mono text-xs text-muted-foreground">{{ $activity->issue_key }}</span>
                                    @endif
                                </p>
                                <p class="mt-0.5 font-mono text-[11px] text-muted-foreground">{{ $activity->created_at_human }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-md border border-dashed border-hairline px-4 py-8 text-center">
                            <p class="text-sm text-muted-foreground">Activity will show up here as the team works.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="mt-6">
            <section class="rounded-lg border border-hairline bg-white p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="deck-label text-muted-foreground">Teams</h2>
                    <a href="{{ route('projects.teams.index', $currentProject->id) }}" wire:navigate
                        class="text-sm font-semibold text-accent transition hover:text-accent-strong">
                        Manage
                    </a>
                </div>

                @if ($teams->isEmpty())
                    <div class="rounded-md border border-dashed border-hairline px-5 py-8 text-center">
                        <p class="text-sm text-muted-foreground">No teams yet.</p>
                        <a href="{{ route('projects.teams.index', $currentProject->id) }}" wire:navigate
                            class="mt-2 inline-block text-sm font-semibold text-accent transition hover:text-accent-strong">
                            Add your first team &rarr;
                        </a>
                    </div>
                @else
                    <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($teams as $team)
                            <li class="rounded-md border border-hairline bg-canvas p-4">
                                <h3 class="truncate text-sm font-semibold text-ink">{{ $team->name }}</h3>
                                <p class="mt-1 line-clamp-2 text-sm text-muted-foreground">{{ $team->description ?: 'No description.' }}</p>
                                <p class="mt-3 font-mono text-xs text-muted-foreground">{{ $team->members_count }} members · {{ $team->issues_count }} issues</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    @endif
</x-dashboard.layout>
