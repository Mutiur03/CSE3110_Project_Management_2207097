@php
    $statusTones = [
        'planned' => 'bg-amber-100 text-amber-700',
        'active' => 'bg-emerald-100 text-emerald-700',
        'completed' => 'bg-neutral-100 text-neutral-700',
    ];

    $issueTypeTones = [
        'epic' => 'bg-purple-100 text-purple-700',
        'story' => 'bg-sky-100 text-sky-700',
        'task' => 'bg-emerald-100 text-emerald-700',
        'subtask' => 'bg-amber-100 text-amber-700',
        'bug' => 'bg-rose-100 text-rose-700',
    ];

    $activeSprint = $sprints->firstWhere('status', 'active');
@endphp

<x-dashboard.layout title="Sprints" :eyebrow="$currentProject->name" :current-project="$currentProject" :projects="$projects">
    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <section class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-neutral-950 px-2.5 py-1 text-xs font-bold text-white">{{ $currentProject->key }}</span>
                        <span class="rounded-md bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">{{ $sprints->count() }} sprints</span>
                    </div>
                    <h2 class="mt-3 text-2xl font-bold tracking-normal text-neutral-950">Sprint planning</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-neutral-600">
                        Create sprints, pull issues from the backlog, start the active sprint, and complete it when the work cycle ends.
                    </p>
                </div>
                <div class="flex gap-2">
                    <button type="button" data-modal-target="create-sprint-modal"
                        class="inline-flex justify-center rounded-md bg-neutral-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                        Create sprint
                    </button>
                    <a href="{{ route('projects.issues.index', $currentProject) }}" wire:navigate
                        class="inline-flex justify-center rounded-md border border-neutral-200 bg-white px-4 py-3 text-sm font-semibold text-neutral-950 transition hover:border-neutral-950">
                        Backlog
                    </a>
                </div>
            </div>
        </section>

        <aside class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold text-neutral-950">Planning rule</h3>
            <p class="mt-2 text-sm leading-6 text-neutral-600">Sprints pull selected backlog issues into a focused work cycle.</p>
        </aside>
    </div>

    @error('sprint')
        <p class="mt-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</p>
    @enderror

    <x-dashboard.modal id="create-sprint-modal" title="Create sprint">
        <form method="POST" action="{{ route('projects.sprints.store', $currentProject) }}" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-semibold text-neutral-950">Sprint name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required
                        class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                    @error('name')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="goal" class="block text-sm font-semibold text-neutral-950">Goal</label>
                    <textarea id="goal" name="goal" rows="3"
                        class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">{{ old('goal') }}</textarea>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    <div>
                        <label for="start_date" class="block text-sm font-semibold text-neutral-950">Start</label>
                        <input id="start_date" name="start_date" type="date" value="{{ old('start_date') }}"
                            class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-semibold text-neutral-950">End</label>
                        <input id="end_date" name="end_date" type="date" value="{{ old('end_date') }}"
                            class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                    </div>
                </div>

                <button type="submit"
                    class="inline-flex w-full justify-center rounded-md bg-neutral-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                    Create sprint
                </button>
        </form>
    </x-dashboard.modal>

    <div class="mt-6 grid gap-6">
        @forelse ($sprints as $sprint)
            <article class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded px-2.5 py-1 text-xs font-bold {{ $statusTones[$sprint->status] ?? 'bg-neutral-100 text-neutral-700' }}">
                                {{ ucfirst($sprint->status) }}
                            </span>
                            <span class="rounded bg-purple-100 px-2.5 py-1 text-xs font-bold text-purple-700">{{ $sprint->issues_count }} issues</span>
                        </div>
                        <h3 class="mt-3 text-lg font-bold text-neutral-950">{{ $sprint->name }}</h3>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-neutral-600">{{ $sprint->goal ?: 'No sprint goal added.' }}</p>
                        <p class="mt-2 text-xs font-semibold text-neutral-500">
                            {{ $sprint->start_date?->format('M d, Y') ?? 'No start date' }} -
                            {{ $sprint->end_date?->format('M d, Y') ?? 'No end date' }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-modal-target="edit-sprint-{{ $sprint->id }}"
                            class="rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm font-semibold text-neutral-950 transition hover:border-neutral-950">
                            Edit
                        </button>

                        @if ($sprint->status !== 'active')
                            <form method="POST" action="{{ route('projects.sprints.start', [$currentProject, $sprint]) }}"
                                @if ($activeSprint)
                                    onsubmit="return confirm(@js($activeSprint->name . ' is already active. Starting ' . $sprint->name . ' will move ' . $activeSprint->name . ' back to planned. Continue?'))"
                                @endif>
                                @csrf
                                @if ($activeSprint)
                                    <input type="hidden" name="confirm_replace_active" value="1">
                                @endif
                                <button type="submit"
                                    class="rounded-md bg-neutral-950 px-3 py-2 text-sm font-semibold text-white transition hover:bg-neutral-800">
                                    Start
                                </button>
                            </form>
                        @endif

                        @if ($sprint->status !== 'completed')
                            <form method="POST" action="{{ route('projects.sprints.complete', [$currentProject, $sprint]) }}">
                                @csrf
                                <button type="submit"
                                    class="rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm font-semibold text-neutral-950 transition hover:border-neutral-950">
                                    Complete
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <x-dashboard.modal id="edit-sprint-{{ $sprint->id }}" title="Edit {{ $sprint->name }}">
                    <form method="POST" action="{{ route('projects.sprints.update', [$currentProject, $sprint]) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="sprint-{{ $sprint->id }}-name" class="block text-sm font-semibold text-neutral-950">Sprint name</label>
                            <input id="sprint-{{ $sprint->id }}-name" name="name" type="text" value="{{ old('name', $sprint->name) }}" required
                                class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                        </div>

                        <div>
                            <label for="sprint-{{ $sprint->id }}-goal" class="block text-sm font-semibold text-neutral-950">Goal</label>
                            <textarea id="sprint-{{ $sprint->id }}-goal" name="goal" rows="3"
                                class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">{{ old('goal', $sprint->goal) }}</textarea>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="sprint-{{ $sprint->id }}-start" class="block text-sm font-semibold text-neutral-950">Start</label>
                                <input id="sprint-{{ $sprint->id }}-start" name="start_date" type="date" value="{{ old('start_date', $sprint->start_date?->format('Y-m-d')) }}"
                                    class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                            </div>
                            <div>
                                <label for="sprint-{{ $sprint->id }}-end" class="block text-sm font-semibold text-neutral-950">End</label>
                                <input id="sprint-{{ $sprint->id }}-end" name="end_date" type="date" value="{{ old('end_date', $sprint->end_date?->format('Y-m-d')) }}"
                                    class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                            </div>
                        </div>

                        <button type="submit"
                            class="inline-flex w-full justify-center rounded-md bg-neutral-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                            Save sprint
                        </button>
                    </form>
                </x-dashboard.modal>

                <div class="mt-5 grid gap-5 xl:grid-cols-[1fr_20rem]">
                    <div>
                        <p class="text-sm font-bold text-neutral-950">Sprint issues</p>
                        <div class="mt-3 space-y-2">
                            @forelse ($sprint->issues as $issue)
                                <div class="flex flex-col gap-3 rounded-md border border-neutral-200 bg-stone-50 p-3 sm:flex-row sm:items-center">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-xs font-bold text-neutral-500">{{ $issue->key }}</span>
                                            <span class="rounded px-2 py-1 text-[10px] font-bold {{ $issueTypeTones[$issue->type] ?? 'bg-neutral-100 text-neutral-700' }}">{{ strtoupper($issue->type) }}</span>
                                        </div>
                                        <p class="mt-2 truncate text-sm font-semibold text-neutral-950">{{ $issue->title }}</p>
                                        <p class="mt-1 text-xs text-neutral-500">{{ $issue->assignee?->name ?? 'Unassigned' }} · {{ $issue->team?->name ?? 'No team' }}</p>
                                    </div>
                                    @if ($sprint->status !== 'completed')
                                        <form method="POST" action="{{ route('projects.sprints.issues.destroy', [$currentProject, $sprint, $issue]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-md px-3 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50">
                                                Remove
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <p class="rounded-md border border-dashed border-neutral-300 bg-stone-50 p-4 text-sm text-neutral-600">
                                    No issues selected for this sprint yet.
                                </p>
                            @endforelse
                        </div>
                    </div>

                    @if ($sprint->status !== 'completed')
                        <form method="POST" action="{{ route('projects.sprints.issues.store', [$currentProject, $sprint]) }}"
                            class="rounded-lg border border-neutral-200 bg-stone-50 p-4">
                            @csrf

                            <label for="sprint-{{ $sprint->id }}-issue" class="block text-sm font-bold text-neutral-950">Add backlog issue</label>
                            <select id="sprint-{{ $sprint->id }}-issue" name="issue_id" required
                                class="mt-3 w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-neutral-950 focus:ring-2 focus:ring-neutral-950/10">
                                <option value="">Select issue</option>
                                @foreach ($backlogIssues as $issue)
                                    <option value="{{ $issue->id }}">{{ $issue->key }} {{ $issue->title }}</option>
                                @endforeach
                            </select>

                            <button type="submit"
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-neutral-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-neutral-800">
                                Add to sprint
                            </button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-lg border border-dashed border-neutral-300 bg-white p-6 text-sm text-neutral-600">
                No sprints have been created for this project yet.
            </div>
        @endforelse
    </div>
</x-dashboard.layout>
