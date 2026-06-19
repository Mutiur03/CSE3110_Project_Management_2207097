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
    $activities = $issue->activityLogs->sortByDesc('created_at');
    $canWrite = $currentProject->userCanWrite(auth()->user());
    $hasChildIssues = $issue->childIssues->isNotEmpty();
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

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_22rem]">
        <section class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-neutral-950">Comments</h3>
                    <p class="mt-1 text-xs text-neutral-500">{{ $issue->comments->count() }} discussion notes</p>
                </div>
            </div>

            @if ($canWrite)
            <form method="POST" action="{{ route('projects.issues.comments.store', [$currentProject, $issue]) }}" class="mt-4">
                @csrf
                <label for="comment-body" class="sr-only">Add comment</label>
                <textarea id="comment-body" name="body" rows="3" required placeholder="Add an update, question, or testing note..."
                    class="w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">{{ old('body') }}</textarea>
                @error('body')
                    <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                @enderror
                <div class="mt-3 flex justify-end">
                    <button type="submit" class="rounded-md bg-neutral-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-neutral-800">
                        Comment
                    </button>
                </div>
            </form>
            @else
                <p class="mt-4 rounded-md border border-dashed border-neutral-300 bg-stone-50 p-4 text-sm text-neutral-600">
                    Viewers can read comments but cannot add new ones.
                </p>
            @endif

            <div class="mt-5 space-y-3">
                @forelse ($issue->comments->sortByDesc('created_at') as $comment)
                    @php
                        $commentInitials = collect(explode(' ', $comment->user?->name ?? 'User'))
                            ->filter()
                            ->take(2)
                            ->map(fn ($part) => substr($part, 0, 1))
                            ->implode('');
                    @endphp
                    <article class="rounded-lg border border-neutral-200 bg-stone-50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-neutral-950 text-xs font-bold text-white">
                                    {{ strtoupper($commentInitials ?: 'U') }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-neutral-950">{{ $comment->user?->name ?? 'Unknown user' }}</p>
                                    <p class="text-xs font-semibold text-neutral-500">{{ $comment->created_at?->diffForHumans() }}</p>
                                </div>
                            </div>
                            @if ($canWrite)
                            <form method="POST" action="{{ route('projects.issues.comments.destroy', [$currentProject, $issue, $comment]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 transition hover:bg-red-50">
                                    Delete
                                </button>
                            </form>
                            @endif
                        </div>
                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-neutral-700">{{ $comment->body }}</p>
                    </article>
                @empty
                    <p class="rounded-md border border-dashed border-neutral-300 bg-stone-50 p-4 text-sm text-neutral-600">
                        No comments yet. Add first discussion note for this issue.
                    </p>
                @endforelse
            </div>
        </section>

        <aside class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold text-neutral-950">Issue activity</h3>
            <div class="mt-4 space-y-4">
                @forelse ($activities->take(8) as $activity)
                    <article class="border-l-2 border-neutral-200 pl-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded bg-neutral-100 px-2 py-1 text-[10px] font-bold uppercase text-neutral-700">{{ $activity->action }}</span>
                        </div>
                        <p class="mt-2 text-sm font-semibold text-neutral-950">{{ $activity->user?->name ?? 'System' }}</p>
                        @if ($activity->new_values || $activity->old_values)
                            <p class="mt-1 text-xs leading-5 text-neutral-500">
                                {{ collect($activity->new_values ?? $activity->old_values)->map(fn ($value, $key) => str_replace('_', ' ', $key) . ': ' . (is_scalar($value) ? $value : json_encode($value)))->join(' | ') }}
                            </p>
                        @endif
                        <p class="mt-1 text-xs font-semibold text-neutral-400">{{ $activity->created_at?->diffForHumans() }}</p>
                    </article>
                @empty
                    <p class="text-sm text-neutral-500">No activity yet.</p>
                @endforelse
            </div>
        </aside>
    </div>

    @if ($canWrite)
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

    <section class="mt-6 rounded-lg border border-rose-100 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-bold text-neutral-950">Delete issue</h3>
        @if ($hasChildIssues)
            <p class="mt-2 text-sm leading-6 text-neutral-600">
                This issue still has child issues. Remove or reassign them before deleting {{ $issue->key }}.
            </p>
        @else
            <p class="mt-2 text-sm leading-6 text-neutral-600">
                Permanently remove {{ $issue->key }} and its comments from this project.
            </p>
            @error('issue')
                <p class="mt-3 text-sm font-medium text-red-600">{{ $message }}</p>
            @enderror
            <form method="POST" action="{{ route('projects.issues.destroy', [$currentProject, $issue]) }}"
                class="mt-4"
                onsubmit="return confirm('Delete {{ $issue->key }}? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="rounded-md px-4 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-50">
                    Delete issue
                </button>
            </form>
        @endif
    </section>
    @endif
</x-dashboard.layout>
