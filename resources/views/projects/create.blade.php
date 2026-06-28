<x-dashboard.layout title="Create Project" eyebrow="Project management" :current-project="$currentProject" :projects="$projects">
    <div class="mx-auto max-w-3xl">
        <div class="mb-6 rounded-lg border border-hairline bg-white p-5">
            <h2 class="font-display text-2xl font-bold tracking-tight text-ink">Create a project workspace</h2>
        </div>

        <form method="POST" action="{{ route('projects.store') }}"
            class="rounded-lg border border-hairline bg-white p-5">
            @csrf

            <div class="grid gap-5">
                <div>
                    <label for="name" class="block text-sm font-semibold text-ink">Project name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                        class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-2.5 text-sm text-ink outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                    @error('name')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-semibold text-ink">Description</label>
                    <textarea id="description" name="description" rows="4"
                        class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-2.5 text-sm text-ink outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('dashboard') }}" wire:navigate
                    class="inline-flex justify-center rounded-md border border-hairline bg-white px-4 py-2.5 text-sm font-semibold text-ink transition hover:border-ink">
                    Cancel
                </a>
                <button type="submit"
                    class="inline-flex justify-center rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong">
                    Create project
                </button>
            </div>
        </form>
    </div>
</x-dashboard.layout>
