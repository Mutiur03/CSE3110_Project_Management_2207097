<x-dashboard.layout title="Create Issue" :eyebrow="$currentProject->name" :current-project="$currentProject" :projects="$projects">
    <div class="mx-auto max-w-4xl">
        <div class="mb-6 rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-neutral-500">{{ $currentProject->key }}</p>
            <h2 class="mt-2 text-2xl font-bold tracking-normal text-neutral-950">Create backlog issue</h2>
            <p class="mt-2 text-sm leading-6 text-neutral-600">
                Create an epic, story, task, subtask, or bug for this project. Team and assignee can be added later.
            </p>
        </div>

        <form method="POST" action="{{ route('projects.issues.store', $currentProject->id) }}"
            class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
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
