@php
    $issueTypeTones = [
        'epic' => 'bg-purple-100 text-purple-700',
        'story' => 'bg-sky-100 text-sky-700',
        'task' => 'bg-emerald-100 text-emerald-700',
        'subtask' => 'bg-amber-100 text-amber-700',
        'bug' => 'bg-rose-100 text-rose-700',
    ];
@endphp

<x-dashboard.layout title="Board" :eyebrow="$currentProject->name" :current-project="$currentProject" :projects="$projects">
    <section class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-md bg-neutral-950 px-2.5 py-1 text-xs font-bold text-white">{{ $currentProject->key }}</span>
                    @if ($activeSprint)
                        <span class="rounded-md bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">{{ $activeSprint->name }}</span>
                    @endif
                </div>
                <h2 class="mt-3 text-2xl font-bold tracking-normal text-neutral-950">Active sprint board</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-neutral-600">
                    Move active sprint issues through the workflow. Backlog items appear after they are selected into an active sprint.
                </p>
            </div>
            <a href="{{ route('projects.sprints.index', $currentProject) }}" wire:navigate
                class="inline-flex justify-center rounded-md border border-neutral-200 bg-white px-4 py-3 text-sm font-semibold text-neutral-950 transition hover:border-neutral-950">
                Manage sprints
            </a>
        </div>
    </section>

    @if (! $activeSprint)
        <div class="mt-6 rounded-lg border border-dashed border-neutral-300 bg-white p-6 text-sm text-neutral-600">
            Start a sprint to use the board.
        </div>
    @else
        <div class="mt-6 grid gap-4 xl:grid-cols-4">
            @foreach ($columns as $column)
                <section class="min-h-80 rounded-lg border border-neutral-200 bg-stone-50 p-4">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h3 class="text-sm font-bold text-neutral-950">{{ $column['label'] }}</h3>
                        <span class="rounded bg-white px-2 py-1 text-xs font-bold text-neutral-500">{{ $column['issues']->count() }}</span>
                    </div>

                    <div class="space-y-3">
                        @forelse ($column['issues'] as $issue)
                            <article class="rounded-lg border border-neutral-200 bg-white p-4 shadow-sm">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-neutral-500">{{ $issue->key }}</span>
                                    <span class="rounded px-2 py-1 text-[10px] font-bold {{ $issueTypeTones[$issue->type] ?? 'bg-neutral-100 text-neutral-700' }}">
                                        {{ strtoupper($issue->type) }}
                                    </span>
                                </div>
                                <a href="{{ route('projects.issues.show', [$currentProject, $issue]) }}" wire:navigate
                                    class="mt-3 block text-sm font-bold leading-5 text-neutral-950 underline-offset-4 hover:underline">
                                    {{ $issue->title }}
                                </a>
                                <p class="mt-2 text-xs text-neutral-500">{{ $issue->assignee?->name ?? 'Unassigned' }} · {{ $issue->team?->name ?? 'No team' }}</p>

                                <div class="mt-4 grid gap-2">
                                    @foreach ($workflow as $status => $label)
                                        @if ($status !== $issue->status)
                                            <form method="POST" action="{{ route('projects.board.issues.status', [$currentProject, $issue]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $status }}">
                                                <button type="submit"
                                                    class="w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-xs font-semibold text-neutral-700 transition hover:border-neutral-950 hover:text-neutral-950">
                                                    Move to {{ $label }}
                                                </button>
                                            </form>
                                        @endif
                                    @endforeach
                                </div>
                            </article>
                        @empty
                            <p class="rounded-md border border-dashed border-neutral-300 bg-white p-4 text-sm text-neutral-500">
                                No issues
                            </p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-dashboard.layout>
