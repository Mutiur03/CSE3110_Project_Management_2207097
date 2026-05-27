@php
    $typeTones = [
        'epic' => 'bg-purple-100 text-purple-700',
        'story' => 'bg-sky-100 text-sky-700',
        'task' => 'bg-emerald-100 text-emerald-700',
        'bug' => 'bg-rose-100 text-rose-700',
    ];

    $statusLabels = [
        'backlog' => 'Backlog',
        'selected' => 'Selected',
        'in_progress' => 'In Progress',
        'review' => 'Review',
        'done' => 'Done',
    ];
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
            {{-- <button type="button" data-modal-target="create-issue-modal"
                class="inline-flex justify-center rounded-md bg-neutral-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                Create issue
            </button> --}}
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

    <section class="mt-6 rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
        @if ($issues->isEmpty())
            <div class="rounded-lg border border-dashed border-neutral-300 bg-stone-50 p-6 text-sm text-neutral-600">
                No issues have been created for this project yet.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-neutral-200 text-xs font-bold uppercase text-neutral-500">
                            <th class="py-3 pr-4">Key</th>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Assignee</th>
                            <th class="px-4 py-3">Team</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200">
                        @foreach ($issues as $issue)
                            <tr>
                                <td class="py-3 pr-4 font-bold text-neutral-950">
                                    <a href="{{ route('projects.issues.show', [$currentProject, $issue]) }}" wire:navigate
                                        class="underline decoration-neutral-300 underline-offset-4">
                                        {{ $issue->key }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 font-semibold text-neutral-950">{{ $issue->title }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded px-2 py-1 text-[10px] font-bold {{ $typeTones[$issue->type] ?? 'bg-neutral-100 text-neutral-700' }}">
                                        {{ strtoupper($issue->type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-neutral-600">{{ $statusLabels[$issue->status] ?? $issue->status }}</td>
                                <td class="px-4 py-3 text-neutral-600">{{ $issue->assignee?->name ?? 'Unassigned' }}</td>
                                <td class="px-4 py-3 text-neutral-600">{{ $issue->team?->name ?? 'No team' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-dashboard.layout>
