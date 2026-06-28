@php
    use App\Support\BadgeTones;

    $typeTones = BadgeTones::issueTypeIcon();
    $priorityTones = BadgeTones::issuePriority();

    $statusLabels = [
        'backlog' => 'Backlog',
        'selected' => 'Selected',
        'in_progress' => 'In Progress',
        'review' => 'Review',
        'done' => 'Done',
    ];

    $issueTypeSpine = [
        'epic' => 'border-l-border',
        'story' => 'border-l-border',
        'task' => 'border-l-border',
        'subtask' => 'border-l-border',
        'bug' => 'border-l-border',
    ];

    $statusTones = BadgeTones::issueStatus();

    $rootIssues = $issues->whereNull('parent_issue_id');
    $childrenByParent = $issues->whereNotNull('parent_issue_id')->groupBy('parent_issue_id');
    $epics = $rootIssues->where('type', 'epic');
    $standaloneStories = $rootIssues->where('type', 'story');
    $standaloneTasks = $rootIssues->where('type', 'task');
    $standaloneBugs = $rootIssues->where('type', 'bug');
    $standaloneSubtasks = $rootIssues->where('type', 'subtask');
    $canWrite = (bool) ($currentProject->can_write ?? false);
@endphp

<x-dashboard.layout title="Issues" :eyebrow="$currentProject->name" :current-project="$currentProject" :projects="$projects">
    <section class="rounded-lg border border-hairline bg-white p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.key-badge :label="$currentProject->key" />
                    <span class="font-mono text-xs text-neutral-400">{{ $issues->count() }} issues</span>
                </div>
                <h2 class="mt-3 font-display text-2xl font-bold tracking-tight text-ink">Project backlog</h2>
            </div>
        </div>
    </section>

    <section class="mt-6 rounded-lg border border-hairline bg-white p-5">
        <form method="GET" action="{{ route('projects.issues.index', $currentProject->id) }}" class="grid gap-3 lg:grid-cols-[1.6fr_repeat(5,1fr)_auto]">
            <label>
                <span class="sr-only">Search issues</span>
                <input name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Search key or title"
                    class="w-full rounded-md border border-hairline bg-white px-3 py-2.5 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
            </label>

            <select name="type" class="rounded-md border border-hairline bg-white px-3 py-2.5 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                <option value="">All types</option>
                @foreach (['epic' => 'Epic', 'story' => 'Story', 'task' => 'Task', 'subtask' => 'Subtask', 'bug' => 'Bug'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="status" class="rounded-md border border-hairline bg-white px-3 py-2.5 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                <option value="">All status</option>
                @foreach ($statusLabels as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="priority" class="rounded-md border border-hairline bg-white px-3 py-2.5 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                <option value="">All priority</option>
                @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['priority'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="assignee_id" class="rounded-md border border-hairline bg-white px-3 py-2.5 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                <option value="">All assignees</option>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}" @selected(($filters['assignee_id'] ?? '') === $member->id)>{{ $member->name }}</option>
                @endforeach
            </select>

            <select name="sprint_id" class="rounded-md border border-hairline bg-white px-3 py-2.5 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                <option value="">All sprints</option>
                @foreach ($sprints as $sprint)
                    <option value="{{ $sprint->id }}" @selected(($filters['sprint_id'] ?? '') === $sprint->id)>{{ $sprint->name }}</option>
                @endforeach
            </select>

            <div class="flex gap-2">
                <button type="submit" class="rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong">Filter</button>
                <a href="{{ route('projects.issues.index', $currentProject->id) }}" wire:navigate
                    class="rounded-md border border-hairline bg-white px-4 py-2.5 text-sm font-semibold text-ink transition hover:border-ink">Clear</a>
            </div>
        </form>
    </section>

    @if ($canWrite)
    <x-dashboard.modal id="create-issue-modal" title="Create issue">
        <form method="POST" action="{{ route('projects.issues.store', $currentProject->id) }}">
            @csrf

            @include('projects.issues.partials.form', [
                'issue' => null,
                'members' => $members,
                'teams' => $teams,
                'parentIssues' => $parentIssues,
                'submitLabel' => 'Create issue',
                'cancelUrl' => route('projects.issues.index', $currentProject->id),
                'modalCancel' => true,
                'fieldPrefix' => 'page-create-issue',
            ])
        </form>
    </x-dashboard.modal>
    @endif

    <section class="mt-6 overflow-hidden rounded-lg border border-hairline bg-white">
        @if ($issues->isEmpty())
            <div class="rounded-lg border border-dashed border-hairline bg-canvas p-6 text-sm text-neutral-500">
                No issues yet.
            </div>
        @else
            <div class="overflow-x-auto">
                <div class="min-w-[860px]">
                    <div class="grid grid-cols-[minmax(24rem,1fr)_13rem_13rem_9rem_8rem] border-b border-hairline bg-canvas">
                        <span class="deck-label border-r border-hairline px-3 py-3 text-neutral-400">Work</span>
                        <span class="deck-label border-r border-hairline px-3 py-3 text-neutral-400">Assignee</span>
                        <span class="deck-label border-r border-hairline px-3 py-3 text-neutral-400">Reporter</span>
                        <span class="deck-label border-r border-hairline px-3 py-3 text-neutral-400">Priority</span>
                        <span class="deck-label px-3 py-3 text-neutral-400">Status</span>
                    </div>

                    <div class="divide-y divide-hairline">
                        @foreach ($epics as $epic)
                            <div>
                                @include('projects.issues.partials.backlog-row', [
                                    'issue' => $epic,
                                    'currentProject' => $currentProject,
                                    'typeTones' => $typeTones,
                                    'priorityTones' => $priorityTones,
                                    'statusLabels' => $statusLabels,
                                    'issueTypeSpine' => $issueTypeSpine,
                                    'statusTones' => $statusTones,
                                    'indent' => 0,
                                    'hasChildren' => $childrenByParent->get($epic->id, collect())->where('type', 'story')->isNotEmpty(),
                                ])

                                @foreach ($childrenByParent->get($epic->id, collect())->where('type', 'story') as $story)
                                    @include('projects.issues.partials.backlog-row', [
                                        'issue' => $story,
                                        'currentProject' => $currentProject,
                                        'typeTones' => $typeTones,
                                        'priorityTones' => $priorityTones,
                                        'statusLabels' => $statusLabels,
                                    'issueTypeSpine' => $issueTypeSpine,
                                    'statusTones' => $statusTones,
                                        'indent' => 1,
                                        'hasChildren' => $childrenByParent->get($story->id, collect())->where('type', 'subtask')->isNotEmpty(),
                                    ])

                                    @foreach ($childrenByParent->get($story->id, collect())->where('type', 'subtask') as $subtask)
                                        @include('projects.issues.partials.backlog-row', [
                                            'issue' => $subtask,
                                            'currentProject' => $currentProject,
                                            'typeTones' => $typeTones,
                                            'priorityTones' => $priorityTones,
                                            'statusLabels' => $statusLabels,
                                    'issueTypeSpine' => $issueTypeSpine,
                                    'statusTones' => $statusTones,
                                            'indent' => 2,
                                            'hasChildren' => false,
                                        ])
                                    @endforeach
                                @endforeach
                            </div>
                        @endforeach

                        @foreach ($standaloneStories as $story)
                            <div>
                                @include('projects.issues.partials.backlog-row', [
                                    'issue' => $story,
                                    'currentProject' => $currentProject,
                                    'typeTones' => $typeTones,
                                    'priorityTones' => $priorityTones,
                                    'statusLabels' => $statusLabels,
                                    'issueTypeSpine' => $issueTypeSpine,
                                    'statusTones' => $statusTones,
                                    'indent' => 0,
                                    'hasChildren' => $childrenByParent->get($story->id, collect())->where('type', 'subtask')->isNotEmpty(),
                                ])

                                @foreach ($childrenByParent->get($story->id, collect())->where('type', 'subtask') as $subtask)
                                    @include('projects.issues.partials.backlog-row', [
                                        'issue' => $subtask,
                                        'currentProject' => $currentProject,
                                        'typeTones' => $typeTones,
                                        'priorityTones' => $priorityTones,
                                        'statusLabels' => $statusLabels,
                                    'issueTypeSpine' => $issueTypeSpine,
                                    'statusTones' => $statusTones,
                                        'indent' => 1,
                                        'hasChildren' => false,
                                    ])
                                @endforeach
                            </div>
                        @endforeach

                        @foreach ($standaloneTasks as $task)
                            <div>
                                @include('projects.issues.partials.backlog-row', [
                                    'issue' => $task,
                                    'currentProject' => $currentProject,
                                    'typeTones' => $typeTones,
                                    'priorityTones' => $priorityTones,
                                    'statusLabels' => $statusLabels,
                                    'issueTypeSpine' => $issueTypeSpine,
                                    'statusTones' => $statusTones,
                                    'indent' => 0,
                                    'hasChildren' => $childrenByParent->get($task->id, collect())->where('type', 'subtask')->isNotEmpty(),
                                ])

                                @foreach ($childrenByParent->get($task->id, collect())->where('type', 'subtask') as $subtask)
                                    @include('projects.issues.partials.backlog-row', [
                                        'issue' => $subtask,
                                        'currentProject' => $currentProject,
                                        'typeTones' => $typeTones,
                                        'priorityTones' => $priorityTones,
                                        'statusLabels' => $statusLabels,
                                    'issueTypeSpine' => $issueTypeSpine,
                                    'statusTones' => $statusTones,
                                        'indent' => 1,
                                        'hasChildren' => false,
                                    ])
                                @endforeach
                            </div>
                        @endforeach

                        @foreach ($standaloneBugs as $bug)
                            @include('projects.issues.partials.backlog-row', [
                                'issue' => $bug,
                                'currentProject' => $currentProject,
                                'typeTones' => $typeTones,
                                'priorityTones' => $priorityTones,
                                'statusLabels' => $statusLabels,
                                'issueTypeSpine' => $issueTypeSpine,
                                'statusTones' => $statusTones,
                                'indent' => 0,
                                'hasChildren' => false,
                            ])
                        @endforeach

                        @foreach ($standaloneSubtasks as $subtask)
                            @include('projects.issues.partials.backlog-row', [
                                'issue' => $subtask,
                                'currentProject' => $currentProject,
                                'typeTones' => $typeTones,
                                'priorityTones' => $priorityTones,
                                'statusLabels' => $statusLabels,
                                'issueTypeSpine' => $issueTypeSpine,
                                'statusTones' => $statusTones,
                                'indent' => 0,
                                'hasChildren' => false,
                            ])
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </section>
</x-dashboard.layout>
