<x-dashboard.layout title="Create Project" eyebrow="Project management" :current-project="$currentProject" :projects="$projects">
    <div class="mx-auto max-w-3xl">
        <div class="mb-6 rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-neutral-500">New workspace</p>
            <h2 class="mt-2 text-2xl font-bold tracking-normal text-neutral-950">Create a project workspace</h2>
            <p class="mt-2 text-sm leading-6 text-neutral-600">
                A project contains its own teams, members, backlog, sprints, issues, comments, and activity.
            </p>
        </div>

        <form method="POST" action="{{ route('projects.store') }}"
            class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
            @csrf

            <div class="grid gap-5">
                <div>
                    <label for="name" class="block text-sm font-semibold text-neutral-950">Project name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                        class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                    @error('name')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-semibold text-neutral-950">Description</label>
                    <textarea id="description" name="description" rows="4"
                        class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('dashboard') }}" wire:navigate
                    class="inline-flex justify-center rounded-md border border-neutral-200 bg-white px-4 py-3 text-sm font-semibold text-neutral-950 transition hover:border-neutral-950">
                    Cancel
                </a>
                <button type="submit"
                    class="inline-flex justify-center rounded-md bg-neutral-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                    Create project
                </button>
            </div>
        </form>
    </div>
</x-dashboard.layout>
