@php
    use App\Support\BadgeTones;
@endphp

<x-dashboard.layout title="Settings" :eyebrow="$currentProject->name" :current-project="$currentProject" :projects="$projects">
    <div class="mx-auto max-w-3xl">
        <div class="mb-6 rounded-lg border border-hairline bg-white p-5">
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.key-badge :label="$currentProject->key" />
                <x-ui.badge :tone="BadgeTones::projectStatus()[$currentProject->status] ?? BadgeTones::NEUTRAL">
                    {{ ucfirst($currentProject->status) }}
                </x-ui.badge>
            </div>
            <h2 class="mt-3 font-display text-2xl font-bold tracking-tight text-ink">Project settings</h2>
        </div>

        @if (session('status'))
            <p class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </p>
        @endif

        <form method="POST" action="{{ route('projects.update', $currentProject->id) }}"
            class="rounded-lg border border-hairline bg-white p-5">
            @csrf
            @method('PATCH')

            <div class="grid gap-5">
                <div>
                    <label for="name" class="block text-sm font-semibold text-ink">Project name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $currentProject->name) }}" required
                        class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-2.5 text-sm text-ink outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                    @error('name')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="project-key" class="block text-sm font-semibold text-ink">Project key</label>
                    <input id="project-key" type="text" value="{{ $currentProject->key }}" readonly
                        class="mt-2 w-full rounded-md border border-hairline bg-canvas px-3 py-2.5 text-sm text-neutral-600">
                    <p class="mt-2 text-xs text-neutral-500">Set at creation. Existing issues keep keys like {{ $currentProject->key }}-1.</p>
                </div>

                <div>
                    <label for="description" class="block text-sm font-semibold text-ink">Description</label>
                    <textarea id="description" name="description" rows="4"
                        class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-2.5 text-sm text-ink outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">{{ old('description', $currentProject->description) }}</textarea>
                    @error('description')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-semibold text-ink">Workspace status</label>
                    <select id="status" name="status"
                        class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-2.5 text-sm text-ink outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                        <option value="active" @selected(old('status', $currentProject->status) === 'active')>Active</option>
                        <option value="archived" @selected(old('status', $currentProject->status) === 'archived')>Archived</option>
                    </select>
                    <p class="mt-2 text-xs text-neutral-500">Archived projects are read-only until reactivated here.</p>
                    @error('status')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('dashboard', ['project' => $currentProject->id]) }}" wire:navigate
                    class="inline-flex justify-center rounded-md border border-hairline bg-white px-4 py-2.5 text-sm font-semibold text-ink transition hover:border-ink">
                    Back to dashboard
                </a>
                <button type="submit"
                    class="inline-flex justify-center rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong">
                    Save settings
                </button>
            </div>
        </form>
    </div>
</x-dashboard.layout>
