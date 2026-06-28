<x-dashboard.layout title="Create Issue" :eyebrow="$currentProject->name" :current-project="$currentProject" :projects="$projects">
    <div class="mx-auto max-w-4xl">
        <div class="mb-6 rounded-lg border border-hairline bg-white p-5">
            <span class="rounded bg-ink px-2 py-0.5 font-mono text-xs font-semibold text-white">{{ $currentProject->key }}</span>
            <h2 class="mt-3 font-display text-2xl font-bold tracking-tight text-ink">Create backlog issue</h2>
        </div>

        <form method="POST" action="{{ route('projects.issues.store', $currentProject->id) }}"
            class="rounded-lg border border-hairline bg-white p-5">
            @csrf

            @include('projects.issues.partials.form', [
                'issue' => null,
                'members' => $members,
                'teams' => $teams,
                'parentIssues' => $parentIssues,
                'submitLabel' => 'Create issue',
                'cancelUrl' => route('projects.issues.index', $currentProject->id),
                'fieldPrefix' => 'create-issue',
            ])
        </form>
    </div>
</x-dashboard.layout>
