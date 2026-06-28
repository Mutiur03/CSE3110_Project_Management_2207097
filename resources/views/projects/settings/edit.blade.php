<x-dashboard.layout title="Settings" :eyebrow="$currentProject->name" :current-project="$currentProject" :projects="$projects">
    <div class="mx-auto max-w-3xl">
        <div class="mb-6 rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-md bg-neutral-950 px-2.5 py-1 text-xs font-bold text-white">{{ $currentProject->key }}</span>
                <span @class([
                    'rounded-md px-2.5 py-1 text-xs font-bold',
                    'bg-emerald-100 text-emerald-700' => $currentProject->status === 'active',
                    'bg-neutral-200 text-neutral-700' => $currentProject->status === 'archived',
                ])>{{ ucfirst($currentProject->status) }}</span>
            </div>
            <h2 class="mt-3 text-2xl font-bold tracking-normal text-neutral-950">Project settings</h2>
            <p class="mt-2 text-sm leading-6 text-neutral-600">
                Update workspace details and archive the project when work is complete. Issue keys keep the original project key prefix.
            </p>
        </div>

        @if (session('status'))
            <p class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </p>
        @endif

        <form method="POST" action="{{ route('projects.update', $currentProject->id) }}"
            class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
            @csrf
            @method('PATCH')

            <div class="grid gap-5">
                <div>
                    <label for="name" class="block text-sm font-semibold text-neutral-950">Project name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $currentProject->name) }}" required
                        class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                    @error('name')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="project-key" class="block text-sm font-semibold text-neutral-950">Project key</label>
                    <input id="project-key" type="text" value="{{ $currentProject->key }}" readonly
                        class="mt-2 w-full rounded-md border border-neutral-200 bg-neutral-100 px-3 py-3 text-sm text-neutral-600">
                    <p class="mt-2 text-xs text-neutral-500">Set at creation. Existing issues keep keys like {{ $currentProject->key }}-1.</p>
                </div>

                <div>
                    <label for="description" class="block text-sm font-semibold text-neutral-950">Description</label>
                    <textarea id="description" name="description" rows="4"
                        class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">{{ old('description', $currentProject->description) }}</textarea>
                    @error('description')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-semibold text-neutral-950">Workspace status</label>
                    <select id="status" name="status"
                        class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
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
                    class="inline-flex justify-center rounded-md border border-neutral-200 bg-white px-4 py-3 text-sm font-semibold text-neutral-950 transition hover:border-neutral-950">
                    Back to dashboard
                </a>
                <button type="submit"
                    class="inline-flex justify-center rounded-md bg-neutral-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                    Save settings
                </button>
            </div>
        </form>
    </div>
</x-dashboard.layout>
