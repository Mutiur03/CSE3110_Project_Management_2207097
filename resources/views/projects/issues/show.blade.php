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

    $canHaveChildren = in_array($issue->type, ['epic', 'story', 'task'], true);
@endphp

<x-dashboard.layout title="{{ $issue->key }}" :eyebrow="$currentProject->name" :current-project="$currentProject" :projects="$projects">
    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <section class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-md bg-neutral-950 px-2.5 py-1 text-xs font-bold text-white">{{ $issue->key }}</span>
                <span class="rounded-md bg-purple-100 px-2.5 py-1 text-xs font-bold text-purple-700">{{ strtoupper($issue->type) }}</span>
            </div>
            <h2 class="mt-3 text-2xl font-bold tracking-normal text-neutral-950">{{ $issue->title }}</h2>
            <p class="mt-2 text-sm leading-6 text-neutral-600">{{ $issue->description ?: 'No description added.' }}</p>
        </section>

        <aside class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold text-neutral-950">Issue details</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-neutral-500">Status</dt>
                    <dd class="font-semibold text-neutral-950">{{ str_replace('_', ' ', ucfirst($issue->status)) }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-neutral-500">Priority</dt>
                    <dd class="font-semibold text-neutral-950">{{ ucfirst($issue->priority) }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-neutral-500">Assignee</dt>
                    <dd class="font-semibold text-neutral-950">{{ $issue->assignee?->name ?? 'Unassigned' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-neutral-500">Team</dt>
                    <dd class="font-semibold text-neutral-950">{{ $issue->team?->name ?? 'No team' }}</dd>
                </div>
                @if ($issue->type === 'bug')
                    <div class="flex justify-between gap-3">
                        <dt class="text-neutral-500">Severity</dt>
                        <dd class="font-semibold text-neutral-950">{{ ucfirst($issue->severity ?? 'major') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-neutral-500">Environment</dt>
                        <dd class="font-semibold text-neutral-950">{{ $issue->environment ?: 'Not specified' }}</dd>
                    </div>
                @endif
            </dl>
        </aside>
    </div>

    @if ($issue->type === 'bug')
        <section class="mt-6 rounded-lg border border-rose-100 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-md bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-700">Bug report</span>
                <span class="rounded-md bg-neutral-100 px-2.5 py-1 text-xs font-bold text-neutral-700">{{ ucfirst($issue->severity ?? 'major') }}</span>
            </div>

            <div class="mt-5 grid gap-5 lg:grid-cols-3">
                <div>
                    <h3 class="text-sm font-bold text-neutral-950">Steps to reproduce</h3>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-neutral-600">{{ $issue->steps_to_reproduce ?: 'No steps added.' }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-neutral-950">Expected result</h3>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-neutral-600">{{ $issue->expected_result ?: 'No expected result added.' }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-neutral-950">Actual result</h3>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-neutral-600">{{ $issue->actual_result ?: 'No actual result added.' }}</p>
                </div>
            </div>
        </section>
    @endif

    @if ($canHaveChildren)
        <section class="mt-6 overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-3 border-b border-neutral-200 px-5 py-4">
                <div>
                    <h3 class="text-sm font-bold text-neutral-950">Child issues</h3>
                    <p class="mt-1 text-xs text-neutral-500">{{ $issue->childIssues->count() }} linked under {{ $issue->key }}</p>
                </div>
            </div>

            @if ($issue->childIssues->isEmpty())
                <div class="p-5 text-sm text-neutral-500">No child issues yet.</div>
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

                        @foreach ($issue->childIssues as $childIssue)
                            <div>
                                @include('projects.issues.partials.backlog-row', [
                                    'issue' => $childIssue,
                                    'currentProject' => $currentProject,
                                    'typeTones' => $typeTones,
                                    'priorityTones' => $priorityTones,
                                    'statusLabels' => $statusLabels,
                                    'indent' => 0,
                                ])

                                @foreach ($childIssue->childIssues as $grandchildIssue)
                                    @include('projects.issues.partials.backlog-row', [
                                        'issue' => $grandchildIssue,
                                        'currentProject' => $currentProject,
                                        'typeTones' => $typeTones,
                                        'priorityTones' => $priorityTones,
                                        'statusLabels' => $statusLabels,
                                        'indent' => 1,
                                    ])
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    @endif

    <form method="POST" action="{{ route('projects.issues.update', [$currentProject, $issue]) }}"
        class="mt-6 rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
        @csrf
        @method('PATCH')

        @include('projects.issues.partials.form', [
            'issue' => $issue,
            'members' => $members,
            'teams' => $teams,
            'parentIssues' => $parentIssues,
            'submitLabel' => 'Save issue',
            'cancelUrl' => route('projects.issues.index', $currentProject),
            'fieldPrefix' => 'edit-issue',
        ])
    </form>
</x-dashboard.layout>
