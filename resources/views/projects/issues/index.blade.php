@php
    $typeTones = [
        'epic' => 'text-purple-600',
        'story' => 'text-sky-600',
        'task' => 'text-emerald-600',
        'subtask' => 'text-blue-600',
        'bug' => 'text-rose-600',
    ];

    $priorityTones = [
        'low' => 'text-neutral-500',
        'medium' => 'text-amber-600',
        'high' => 'text-orange-600',
        'urgent' => 'text-rose-600',
    ];

    $statusLabels = [
        'backlog' => 'Backlog',
        'selected' => 'Selected',
        'in_progress' => 'In Progress',
        'review' => 'Review',
        'done' => 'Done',
    ];

    $rootIssues = $issues->whereNull('parent_issue_id');
    $childrenByParent = $issues->whereNotNull('parent_issue_id')->groupBy('parent_issue_id');
    $epics = $rootIssues->where('type', 'epic');
    $standaloneStories = $rootIssues->where('type', 'story');
    $standaloneTasks = $rootIssues->where('type', 'task');
    $standaloneBugs = $rootIssues->where('type', 'bug');
    $standaloneSubtasks = $rootIssues->where('type', 'subtask');
@endphp

<x-dashboard.layout title="Issues" :eyebrow="$currentProject->name" :current-project="$currentProject" :projects="$projects">
    <section class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-md bg-neutral-950 px-2.5 py-1 text-xs font-bold text-white">{{ $currentProject->key }}</span>
                    <span class="rounded-md bg-purple-100 px-2.5 py-1 text-xs font-bold text-purple-700">{{ $issues->count() }} issues</span>
                </div>
                <h2 class="mt-3 text-2xl font-bold tracking-normal text-neutral-950">Project backlog</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-neutral-600">
                    Backlog is the project issue list. Team and assignee are optional, so work can start before teams are formed.
                </p>
            </div>
        </div>
    </section>

    <x-dashboard.modal id="create-issue-modal" title="Create issue">
        <form method="POST" action="{{ route('projects.issues.store', $currentProject) }}">
            @csrf

            @include('projects.issues.partials.form', [
                'issue' => null,
                'members' => $members,
                'teams' => $teams,
                'parentIssues' => $parentIssues,
                'submitLabel' => 'Create issue',
                'cancelUrl' => route('projects.issues.index', $currentProject),
                'modalCancel' => true,
                'fieldPrefix' => 'page-create-issue',
            ])
        </form>
    </x-dashboard.modal>

    <section class="mt-6 overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-sm">
        @if ($issues->isEmpty())
            <div class="rounded-lg border border-dashed border-neutral-300 bg-stone-50 p-6 text-sm text-neutral-600">
                No issues have been created for this project yet.
            </div>
        @else
            <div class="overflow-x-auto">
                <div class="min-w-[860px]">
                    <div class="grid grid-cols-[minmax(24rem,1fr)_13rem_13rem_9rem_8rem] border-b border-neutral-200 bg-stone-50 text-xs font-bold text-neutral-500">
                        <span class="border-r border-neutral-200 px-3 py-3">Work</span>
                        <span class="border-r border-neutral-200 px-3 py-3">Assignee</span>
                        <span class="border-r border-neutral-200 px-3 py-3">Reporter</span>
                        <span class="border-r border-neutral-200 px-3 py-3">Priority</span>
                        <span class="px-3 py-3">Status</span>
                    </div>

                    <div class="divide-y divide-neutral-100">
                        @foreach ($epics as $epic)
                            <div>
                                @include('projects.issues.partials.backlog-row', [
                                    'issue' => $epic,
                                    'currentProject' => $currentProject,
                                    'typeTones' => $typeTones,
                                    'priorityTones' => $priorityTones,
                                    'statusLabels' => $statusLabels,
                                    'indent' => 0,
                                ])

                                @foreach ($childrenByParent->get($epic->id, collect())->where('type', 'story') as $story)
                                    @include('projects.issues.partials.backlog-row', [
                                        'issue' => $story,
                                        'currentProject' => $currentProject,
                                        'typeTones' => $typeTones,
                                        'priorityTones' => $priorityTones,
                                        'statusLabels' => $statusLabels,
                                        'indent' => 1,
                                    ])

                                    @foreach ($childrenByParent->get($story->id, collect())->where('type', 'subtask') as $subtask)
                                        @include('projects.issues.partials.backlog-row', [
                                            'issue' => $subtask,
                                            'currentProject' => $currentProject,
                                            'typeTones' => $typeTones,
                                            'priorityTones' => $priorityTones,
                                            'statusLabels' => $statusLabels,
                                            'indent' => 2,
                                        ])
                                    @endforeach
                                @endforeach
                            </div>
                        @endforeach

                        @foreach ($standaloneStories as $story)
                            @include('projects.issues.partials.backlog-row', [
                                'issue' => $story,
                                'currentProject' => $currentProject,
                                'typeTones' => $typeTones,
                                'priorityTones' => $priorityTones,
                                'statusLabels' => $statusLabels,
                                'indent' => 0,
                            ])
                        @endforeach

                        @foreach ($standaloneTasks as $task)
                            <div>
                                @include('projects.issues.partials.backlog-row', [
                                    'issue' => $task,
                                    'currentProject' => $currentProject,
                                    'typeTones' => $typeTones,
                                    'priorityTones' => $priorityTones,
                                    'statusLabels' => $statusLabels,
                                    'indent' => 0,
                                ])

                                @foreach ($childrenByParent->get($task->id, collect())->where('type', 'subtask') as $subtask)
                                    @include('projects.issues.partials.backlog-row', [
                                        'issue' => $subtask,
                                        'currentProject' => $currentProject,
                                        'typeTones' => $typeTones,
                                        'priorityTones' => $priorityTones,
                                        'statusLabels' => $statusLabels,
                                        'indent' => 1,
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
                                'indent' => 0,
                            ])
                        @endforeach

                        @foreach ($standaloneSubtasks as $subtask)
                            @include('projects.issues.partials.backlog-row', [
                                'issue' => $subtask,
                                'currentProject' => $currentProject,
                                'typeTones' => $typeTones,
                                'priorityTones' => $priorityTones,
                                'statusLabels' => $statusLabels,
                                'indent' => 0,
                            ])
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </section>
</x-dashboard.layout>
