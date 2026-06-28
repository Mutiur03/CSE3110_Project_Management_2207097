@php
    use App\Support\BadgeTones;

    $issueTypeTones = BadgeTones::issueType();
    $issueTypeSpine = [
        'epic' => 'border-l-border',
        'story' => 'border-l-border',
        'task' => 'border-l-border',
        'subtask' => 'border-l-border',
        'bug' => 'border-l-border',
    ];

    // workflow column accents, backlog -> done
    $statusSpine = [
        'backlog' => 'border-t-border',
        'selected' => 'border-t-sky-400/50',
        'in_progress' => 'border-t-amber-400/50',
        'review' => 'border-t-violet-400/50',
        'done' => 'border-t-emerald-400/50',
    ];

    $priorityTones = BadgeTones::issuePriority();

    $canWrite = (bool) ($currentProject->can_write ?? false);
@endphp

<x-dashboard.layout title="Board" :eyebrow="$currentProject->name" :current-project="$currentProject" :projects="$projects">
    <section class="rounded-lg border border-hairline bg-white p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.key-badge :label="$currentProject->key" />
                    @if ($activeSprint)
                        <x-ui.badge :tone="BadgeTones::ACCENT">{{ $activeSprint->name }}</x-ui.badge>
                    @endif
                </div>
                <h2 class="mt-3 font-display text-2xl font-bold tracking-tight text-ink">Active sprint board</h2>
            </div>
            <a href="{{ route('projects.sprints.index', $currentProject->id) }}" wire:navigate
                class="inline-flex justify-center rounded-md border border-hairline bg-white px-4 py-2.5 text-sm font-semibold text-ink transition hover:border-ink">
                Manage sprints
            </a>
        </div>
    </section>

    @if (! $activeSprint)
        <div class="mt-6 rounded-lg border border-dashed border-hairline bg-white p-6 text-sm text-neutral-500">
            Start a sprint to use the board.
        </div>
    @else
        <div class="mt-6 grid gap-4 xl:grid-cols-4">
            @foreach ($columns as $column)
                <section data-board-column="{{ $column['status'] }}" class="min-h-80 rounded-lg border border-hairline border-t-2 bg-canvas p-4 transition {{ $statusSpine[$column['status']] ?? 'border-t-neutral-300' }}">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h3 class="deck-label text-neutral-400">{{ $column['label'] }}</h3>
                        <span class="font-mono text-xs text-neutral-400">{{ $column['issues']->count() }}</span>
                    </div>

                    <div data-board-drop-zone class="min-h-56 space-y-3">
                        @forelse ($column['issues'] as $issue)
                            <article @if ($canWrite) draggable="true" @endif data-board-card data-current-status="{{ $issue->status }}"
                                @class([
                                    'rounded-lg border border-hairline border-l-2 bg-white p-4 transition',
                                    ($issueTypeSpine[$issue->type] ?? 'border-l-neutral-300'),
                                    'cursor-grab active:cursor-grabbing' => $canWrite,
                                ])>
                                <form method="POST" action="{{ route('projects.board.issues.status', [$currentProject->id, $issue->id]) }}" data-board-drop-form>
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $issue->status }}" data-board-status-input>
                                </form>

                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-mono text-xs text-neutral-400">{{ $issue->key }}</span>
                                    <x-ui.badge :tone="$issueTypeTones[$issue->type] ?? BadgeTones::NEUTRAL">
                                        {{ strtoupper($issue->type) }}
                                    </x-ui.badge>
                                </div>

                                <a href="{{ route('projects.issues.show', [$currentProject->id, $issue->id]) }}" wire:navigate
                                    class="mt-3 block text-sm font-bold leading-5 text-ink underline-offset-4 hover:underline">
                                    {{ $issue->title }}
                                </a>

                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <x-ui.badge :tone="$priorityTones[$issue->priority] ?? BadgeTones::NEUTRAL">
                                        {{ strtoupper($issue->priority) }}
                                    </x-ui.badge>
                                    @if ($issue->story_points)
                                        <x-ui.badge :tone="BadgeTones::storyPoints()">{{ $issue->story_points }} pts</x-ui.badge>
                                    @endif
                                    @if ($issue->severity)
                                        <x-ui.badge :tone="BadgeTones::severity(in_array($issue->severity, ['blocker', 'critical'], true))">
                                            {{ strtoupper($issue->severity) }}
                                        </x-ui.badge>
                                    @endif
                                </div>

                                <p class="mt-2 font-mono text-xs text-neutral-400">{{ $issue->assignee_name ?? 'Unassigned' }} / {{ $issue->team_name ?? 'No team' }}</p>

                                @if ($canWrite)
                                <div class="mt-4 grid gap-2">
                                    @foreach ($workflow as $status => $label)
                                        @if ($status !== $issue->status)
                                            <form method="POST" action="{{ route('projects.board.issues.status', [$currentProject->id, $issue->id]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $status }}">
                                                <button type="submit"
                                                    class="w-full rounded-md border border-hairline bg-white px-3 py-2 text-xs font-semibold text-neutral-500 transition hover:border-ink hover:text-ink">
                                                    Move to {{ $label }}
                                                </button>
                                            </form>
                                        @endif
                                    @endforeach
                                </div>
                                @endif
                            </article>
                        @empty
                            <p class="rounded-md border border-dashed border-hairline bg-white p-4 text-sm text-neutral-500">
                                No issues
                            </p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-dashboard.layout>
