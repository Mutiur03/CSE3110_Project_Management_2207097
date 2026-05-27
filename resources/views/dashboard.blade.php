@php
    $hasProject = filled($currentProject);

    $features = [
        ['title' => 'Create project workspaces', 'description' => 'Start each Scrum workspace as a project with its own key, members, backlog, and sprint history.', 'tone' => 'bg-sky-50 text-sky-800 border-sky-200'],
        ['title' => 'Organize multiple teams', 'description' => 'Add frontend, backend, QA, or custom teams under a project and track their workload separately.', 'tone' => 'bg-emerald-50 text-emerald-800 border-emerald-200'],
        ['title' => 'Plan sprints from backlog', 'description' => 'Move project issues into a sprint and follow them through Jira-style workflow columns.', 'tone' => 'bg-amber-50 text-amber-800 border-amber-200'],
        ['title' => 'Review project activity', 'description' => 'Keep comments, status changes, assignments, and sprint updates visible for the current project.', 'tone' => 'bg-purple-50 text-purple-800 border-purple-200'],
    ];

    $typeLabels = [
        'epic' => ['label' => 'Epics', 'tone' => 'bg-purple-50 text-purple-700 border-purple-200'],
        'story' => ['label' => 'Stories', 'tone' => 'bg-sky-50 text-sky-700 border-sky-200'],
        'task' => ['label' => 'Tasks', 'tone' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'subtask' => ['label' => 'Subtasks', 'tone' => 'bg-amber-50 text-amber-700 border-amber-200'],
        'bug' => ['label' => 'Bugs', 'tone' => 'bg-rose-50 text-rose-700 border-rose-200'],
    ];

    $issueTypeTones = [
        'epic' => 'bg-purple-100 text-purple-700',
        'story' => 'bg-sky-100 text-sky-700',
        'task' => 'bg-emerald-100 text-emerald-700',
        'subtask' => 'bg-amber-100 text-amber-700',
        'bug' => 'bg-rose-100 text-rose-700',
    ];
@endphp

<x-dashboard.layout title="Dashboard" eyebrow="Project workspace" :current-project="$currentProject" :projects="$projects">
    @if (! $hasProject)
        <div class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold text-neutral-500">No project yet</p>
                <h2 class="mt-2 text-2xl font-bold tracking-normal text-neutral-950">Create your first Scrum workspace</h2>
                <p class="mt-3 text-sm leading-6 text-neutral-600">
                    Each workspace is a project. After creating one, Scrum teams, backlog items, sprints, board columns,
                    comments, and activity will load from that selected project.
                </p>
                <a href="{{ route('projects.create') }}" wire:navigate
                    class="mt-5 inline-flex rounded-md bg-neutral-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                    Create project
                </a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($features as $feature)
                <x-dashboard.feature-card :title="$feature['title']" :description="$feature['description']" :tone="$feature['tone']" />
            @endforeach
        </div>
    @else
        <div class="mb-6 grid gap-4 xl:grid-cols-[1fr_22rem]">
            <section class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-md bg-neutral-950 px-2.5 py-1 text-xs font-bold text-white">{{ $currentProject->key }}</span>
                            <span class="rounded-md bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">{{ ucfirst($currentProject->status) }}</span>
                        </div>
                        <h2 class="mt-3 text-2xl font-bold tracking-normal text-neutral-950">{{ $currentProject->name }}</h2>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-neutral-600">{{ $currentProject->description ?: 'No project description added yet.' }}</p>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        @if ($projects->count() > 1)
                            <form method="GET" action="{{ route('dashboard') }}">
                                <label class="sr-only" for="project-switcher">Switch project</label>
                                <select id="project-switcher" name="project" onchange="this.form.submit()"
                                    class="w-full rounded-md border border-neutral-200 bg-white px-3 py-3 text-sm font-semibold text-neutral-950 outline-none transition focus:border-neutral-950 focus:ring-2 focus:ring-neutral-950/10">
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->id }}" @selected($project->is($currentProject))>
                                            {{ $project->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                        <a href="{{ route('projects.issues.create', $currentProject) }}" wire:navigate
                            class="inline-flex justify-center rounded-md bg-neutral-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                            Create issue
                        </a>
                    </div>
                </div>
            </section>

            <aside class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-neutral-500">Project rule</p>
                <p class="mt-2 text-sm leading-6 text-neutral-700">
                    Changing the selected project changes the teams, backlog, sprint board, issues, members, and activity shown here.
                </p>
            </aside>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $stat)
                <x-dashboard.stat-card :label="$stat['label']" :value="$stat['value']" :note="$stat['note']" :tone="$stat['tone']" />
            @endforeach
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
            <section class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold">Teams in this project</h2>
                        <p class="text-sm text-neutral-500">Each team belongs to {{ $currentProject->name }}</p>
                    </div>
                    <a href="{{ route('projects.teams.index', $currentProject) }}" wire:navigate
                        class="text-sm font-semibold text-neutral-950 underline decoration-neutral-300 underline-offset-4">
                        Add team
                    </a>
                </div>

                @if ($teams->isEmpty())
                    <div class="rounded-lg border border-dashed border-neutral-300 bg-stone-50 p-5 text-sm text-neutral-600">
                        No teams have been added to this project yet.
                    </div>
                @else
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($teams as $team)
                            <article class="rounded-lg border border-neutral-200 bg-stone-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="text-sm font-bold text-neutral-950">{{ $team->name }}</h3>
                                    <span class="rounded bg-sky-100 px-2 py-1 text-[10px] font-bold text-sky-700">
                                        {{ $team->members_count }} members
                                    </span>
                                </div>
                                <p class="mt-3 text-sm leading-6 text-neutral-600">{{ $team->description ?: 'No team description added.' }}</p>
                                <p class="mt-3 text-xs font-semibold text-neutral-500">{{ $team->issues_count }} assigned issues</p>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold">Active sprint</h2>
                        <p class="text-sm text-neutral-500">
                            @if ($activeSprint)
                                {{ $activeSprint->name }} issues by workflow stage
                            @else
                                No active sprint for {{ $currentProject->key }}
                            @endif
                        </p>
                    </div>
                    <span class="rounded-md bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">{{ $sprintProgress }}%</span>
                </div>

                <div class="grid gap-3 lg:grid-cols-5">
                    @foreach ($boardColumns as $column)
                        <div class="rounded-md border border-neutral-200 bg-stone-50 p-3">
                            <p class="mb-3 text-xs font-bold text-neutral-500">{{ $column['stage'] }}</p>
                            <div class="space-y-3">
                                @forelse ($column['issues'] as $issue)
                                    <article class="rounded-md border border-neutral-200 bg-white p-3">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="rounded px-2 py-1 text-[10px] font-bold {{ $issueTypeTones[$issue->type] ?? 'bg-neutral-100 text-neutral-700' }}">
                                                {{ strtoupper($issue->type) }}
                                            </span>
                                            <span class="text-[10px] font-bold text-neutral-400">{{ $issue->key }}</span>
                                        </div>
                                        <p class="mt-3 text-sm font-semibold leading-5">{{ $issue->title }}</p>
                                        @if ($issue->team)
                                            <p class="mt-2 text-xs text-neutral-500">{{ $issue->team->name }}</p>
                                        @endif
                                    </article>
                                @empty
                                    <p class="rounded-md border border-dashed border-neutral-300 bg-white p-3 text-xs text-neutral-500">
                                        No issues
                                    </p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_0.75fr]">
            <section class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold">Project backlog focus</h2>
                <div class="mt-5 grid gap-3 md:grid-cols-4">
                    @foreach ($typeLabels as $type => $meta)
                        <div class="rounded-lg border p-4 {{ $meta['tone'] }}">
                            <p class="text-sm font-semibold">{{ $meta['label'] }}</p>
                            <p class="mt-2 text-2xl font-bold">{{ $backlogCounts->get($type, 0) }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold">Project activity</h2>
                <div class="mt-5 space-y-4">
                    @forelse ($activities as $activity)
                        <div class="flex gap-3">
                            <span class="mt-1 size-2 rounded-full bg-neutral-950"></span>
                            <div>
                                <p class="text-sm font-medium text-neutral-950">
                                    {{ $activity->user?->name ?? 'System' }} {{ $activity->action }}
                                    @if ($activity->issue)
                                        {{ $activity->issue->key }}
                                    @endif
                                </p>
                                <p class="text-xs text-neutral-500">{{ $activity->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-lg border border-dashed border-neutral-300 bg-stone-50 p-5 text-sm text-neutral-600">
                            No activity has been recorded for this project yet.
                        </p>
                    @endforelse
                </div>
            </section>
        </div>
    @endif
</x-dashboard.layout>
