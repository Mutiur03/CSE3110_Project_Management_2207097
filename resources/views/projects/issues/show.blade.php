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
