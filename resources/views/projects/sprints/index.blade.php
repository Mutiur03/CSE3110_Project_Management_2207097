@php
    use App\Support\BadgeTones;

    $statusTones = BadgeTones::sprintStatus();
    $issueTypeTones = BadgeTones::issueType();

    $issueTypeSpine = [
        'epic' => 'border-l-border',
        'story' => 'border-l-border',
        'task' => 'border-l-border',
        'subtask' => 'border-l-border',
        'bug' => 'border-l-border',
    ];

    $activeSprint = $sprints->firstWhere('status', 'active');
    $canWrite = (bool) ($currentProject->can_write ?? false);
@endphp

<x-dashboard.layout title="Sprints" :eyebrow="$currentProject->name" :current-project="$currentProject" :projects="$projects">
    <div>
        <section class="rounded-lg border border-hairline bg-white p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.key-badge :label="$currentProject->key" />
                        <span class="font-mono text-xs text-neutral-400">{{ $sprints->count() }} sprints</span>
                    </div>
                    <h2 class="mt-3 font-display text-2xl font-bold tracking-tight text-ink">Sprint planning</h2>
                </div>
                <div class="flex gap-2">
                    @if ($canWrite)
                    <button type="button" data-modal-target="create-sprint-modal"
                        class="inline-flex justify-center rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong">
                        Create sprint
                    </button>
                    @endif
                    <a href="{{ route('projects.issues.index', $currentProject->id) }}" wire:navigate
                        class="inline-flex justify-center rounded-md border border-hairline bg-white px-4 py-2.5 text-sm font-semibold text-ink transition hover:border-ink">
                        Backlog
                    </a>
                </div>
            </div>
        </section>
    </div>

    @error('sprint')
        <p class="mt-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</p>
    @enderror

    @if ($canWrite)
    <x-dashboard.modal id="create-sprint-modal" title="Create sprint">
        <form method="POST" action="{{ route('projects.sprints.store', $currentProject->id) }}" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-semibold text-ink">Sprint name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required
                        class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                    @error('name')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="goal" class="block text-sm font-semibold text-ink">Goal</label>
                    <textarea id="goal" name="goal" rows="3"
                        class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">{{ old('goal') }}</textarea>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    <div>
                        <label for="start_date" class="block text-sm font-semibold text-ink">Start</label>
                        <input id="start_date" name="start_date" type="date" value="{{ old('start_date') }}"
                            class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-semibold text-ink">End</label>
                        <input id="end_date" name="end_date" type="date" value="{{ old('end_date') }}"
                            class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                    </div>
                </div>

                <button type="submit"
                    class="inline-flex w-full justify-center rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong">
                    Create sprint
                </button>
        </form>
    </x-dashboard.modal>
    @endif

    <div class="mt-6 grid gap-6">
        @forelse ($sprints as $sprint)
            @php
                $sprintPoints = $sprint->issues->sum('story_points');
                $doneCount = $sprint->issues->where('status', 'done')->count();
                $openCount = $sprint->issues->where('status', '!=', 'done')->count();
            @endphp
            <article class="rounded-lg border border-hairline bg-white p-5">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-ui.badge :tone="$statusTones[$sprint->status] ?? BadgeTones::NEUTRAL">
                                {{ ucfirst($sprint->status) }}
                            </x-ui.badge>
                            <span class="font-mono text-xs text-neutral-400">{{ $sprint->issues_count }} issues</span>
                            <span class="font-mono text-xs text-neutral-400">{{ $sprintPoints }} pts</span>
                            <span class="font-mono text-xs text-neutral-400">{{ $doneCount }} done</span>
                            <span class="font-mono text-xs text-neutral-400">{{ $openCount }} open</span>
                        </div>
                        <h3 class="mt-3 font-display text-lg font-bold text-ink">{{ $sprint->name }}</h3>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-neutral-500">{{ $sprint->goal ?: 'No sprint goal added.' }}</p>
                        <p class="mt-2 font-mono text-xs text-neutral-400">
                            {{ $sprint->start_date?->format('M d, Y') ?? 'No start date' }} -
                            {{ $sprint->end_date?->format('M d, Y') ?? 'No end date' }}
                        </p>
                        @php $sprintPct = $sprint->issues_count > 0 ? (int) round($doneCount / $sprint->issues_count * 100) : 0; @endphp
                        <div class="mt-4 max-w-md">
                            <div class="mb-1 flex items-center justify-between font-mono text-[11px] text-neutral-400">
                                <span>{{ $doneCount }}/{{ $sprint->issues_count }} done</span>
                                <span>{{ $sprintPct }}%</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-canvas">
                                <div class="h-full rounded-full bg-accent" style="width: {{ max(2, $sprintPct) }}%"></div>
                            </div>
                        </div>
                    </div>

                    @if ($canWrite)
                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-modal-target="edit-sprint-{{ $sprint->id }}"
                            class="rounded-md border border-hairline bg-white px-3 py-2 text-sm font-semibold text-ink transition hover:border-ink">
                            Edit
                        </button>

                        @if ($sprint->status !== 'active')
                            <form method="POST" action="{{ route('projects.sprints.start', [$currentProject->id, $sprint->id]) }}"
                                @if ($activeSprint)
                                    onsubmit="return confirm(@js($activeSprint->name . ' is already active. Starting ' . $sprint->name . ' will move ' . $activeSprint->name . ' back to planned. Continue?'))"
                                @endif>
                                @csrf
                                @if ($activeSprint)
                                    <input type="hidden" name="confirm_replace_active" value="1">
                                @endif
                                <button type="submit"
                                    @disabled($sprint->issues_count === 0)
                                    class="rounded-md bg-accent px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong disabled:cursor-not-allowed disabled:bg-neutral-300">
                                    Start
                                </button>
                            </form>
                        @endif

                        @if ($sprint->status !== 'completed')
                            <form method="POST" action="{{ route('projects.sprints.complete', [$currentProject->id, $sprint->id]) }}">
                                @csrf
                                <button type="submit"
                                    class="rounded-md border border-hairline bg-white px-3 py-2 text-sm font-semibold text-ink transition hover:border-ink">
                                    Complete
                                </button>
                            </form>
                        @endif
                    </div>
                    @endif
                </div>

                @if ($canWrite)
                <x-dashboard.modal id="edit-sprint-{{ $sprint->id }}" title="Edit {{ $sprint->name }}">
                    <form method="POST" action="{{ route('projects.sprints.update', [$currentProject->id, $sprint->id]) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="sprint-{{ $sprint->id }}-name" class="block text-sm font-semibold text-ink">Sprint name</label>
                            <input id="sprint-{{ $sprint->id }}-name" name="name" type="text" value="{{ old('name', $sprint->name) }}" required
                                class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                        </div>

                        <div>
                            <label for="sprint-{{ $sprint->id }}-goal" class="block text-sm font-semibold text-ink">Goal</label>
                            <textarea id="sprint-{{ $sprint->id }}-goal" name="goal" rows="3"
                                class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">{{ old('goal', $sprint->goal) }}</textarea>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="sprint-{{ $sprint->id }}-start" class="block text-sm font-semibold text-ink">Start</label>
                                <input id="sprint-{{ $sprint->id }}-start" name="start_date" type="date" value="{{ old('start_date', $sprint->start_date?->format('Y-m-d')) }}"
                                    class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                            </div>
                            <div>
                                <label for="sprint-{{ $sprint->id }}-end" class="block text-sm font-semibold text-ink">End</label>
                                <input id="sprint-{{ $sprint->id }}-end" name="end_date" type="date" value="{{ old('end_date', $sprint->end_date?->format('Y-m-d')) }}"
                                    class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                            </div>
                        </div>

                        <button type="submit"
                            class="inline-flex w-full justify-center rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong">
                            Save sprint
                        </button>
                    </form>
                </x-dashboard.modal>
                @endif

                <div class="mt-5 grid gap-5 xl:grid-cols-[1fr_20rem]">
                    <div>
                        <p class="deck-label text-neutral-400">Sprint issues</p>
                        <div class="mt-3 space-y-2">
                            @forelse ($sprint->issues as $issue)
                                <div class="flex flex-col gap-3 rounded-md border border-hairline border-l-2 bg-canvas p-3 sm:flex-row sm:items-center {{ $issueTypeSpine[$issue->type] ?? 'border-l-neutral-300' }}">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-mono text-xs text-neutral-400">{{ $issue->key }}</span>
                                            <x-ui.badge :tone="$issueTypeTones[$issue->type] ?? BadgeTones::NEUTRAL">{{ strtoupper($issue->type) }}</x-ui.badge>
                                            @if ($issue->story_points)
                                                <span class="font-mono text-xs text-neutral-400">{{ $issue->story_points }} pts</span>
                                            @endif
                                        </div>
                                        <p class="mt-2 truncate text-sm font-semibold text-ink">{{ $issue->title }}</p>
                                        <p class="mt-1 text-xs text-neutral-500">{{ $issue->assignee_name ?? 'Unassigned' }} · {{ $issue->team_name ?? 'No team' }}</p>
                                    </div>
                                    @if ($canWrite && $sprint->status !== 'completed')
                                        <form method="POST" action="{{ route('projects.sprints.issues.destroy', [$currentProject->id, $sprint->id, $issue->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-md px-3 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50">
                                                Remove
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <p class="rounded-md border border-dashed border-hairline bg-canvas p-4 text-sm text-neutral-500">
                                    No issues in this sprint yet.
                                </p>
                            @endforelse
                        </div>
                    </div>

                    @if ($canWrite && $sprint->status !== 'completed')
                        <form method="POST" action="{{ route('projects.sprints.issues.store', [$currentProject->id, $sprint->id]) }}"
                            class="rounded-lg border border-hairline bg-canvas p-4">
                            @csrf

                            <label for="sprint-{{ $sprint->id }}-issue" class="deck-label block text-neutral-400">Add backlog issue</label>
                            @error('issue_id')
                                <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                            <select id="sprint-{{ $sprint->id }}-issue" name="issue_id" required
                                class="mt-3 w-full rounded-md border border-hairline bg-white px-3 py-2 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                                <option value="">Select issue</option>
                                @foreach ($backlogIssues as $issue)
                                    <option value="{{ $issue->id }}">{{ $issue->key }} [{{ strtoupper($issue->type) }}] {{ $issue->title }}{{ $issue->story_points ? ' - ' . $issue->story_points . ' pts' : '' }}</option>
                                @endforeach
                            </select>

                            <button type="submit"
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong">
                                Add to sprint
                            </button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-lg border border-dashed border-hairline bg-white p-6 text-sm text-neutral-500">
                No sprints yet.
            </div>
        @endforelse
    </div>
</x-dashboard.layout>
