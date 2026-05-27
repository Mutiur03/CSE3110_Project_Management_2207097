@php
    $roles = [
        'project_owner' => 'Project owner',
        'scrum_master' => 'Scrum master',
        'developer' => 'Developer',
        'viewer' => 'Viewer',
    ];
@endphp

<x-dashboard.layout title="Members" :eyebrow="$currentProject->name" :current-project="$currentProject" :projects="$projects">
    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <section class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-neutral-950 px-2.5 py-1 text-xs font-bold text-white">{{ $currentProject->key }}</span>
                        <span class="rounded-md bg-sky-100 px-2.5 py-1 text-xs font-bold text-sky-700">{{ $members->count() }} members</span>
                    </div>
                    <h2 class="mt-3 text-2xl font-bold tracking-normal text-neutral-950">Project members</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-neutral-600">
                        Members belong directly to the project. They can work without a team, or be assigned into teams later.
                    </p>
                </div>
                <div class="flex gap-2">
                    <button type="button" data-modal-target="add-project-member-modal"
                        class="inline-flex justify-center rounded-md bg-neutral-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                        Add member
                    </button>
                    <a href="{{ route('dashboard', ['project' => $currentProject->id]) }}" wire:navigate
                        class="inline-flex justify-center rounded-md border border-neutral-200 bg-white px-4 py-3 text-sm font-semibold text-neutral-950 transition hover:border-neutral-950">
                        Back
                    </a>
                </div>
            </div>
        </section>

        <aside class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold text-neutral-950">Access layer</h3>
            <p class="mt-2 text-sm leading-6 text-neutral-600">Project members can work directly in the project, with or without team assignment.</p>
        </aside>
    </div>

    <x-dashboard.modal id="add-project-member-modal" title="Add project member" :open="old('_form') === 'add-project-member'">
        <form method="POST" action="{{ route('projects.members.store', $currentProject) }}" class="space-y-4">
                @csrf
                <input type="hidden" name="_form" value="add-project-member">

                <div>
                    <label for="email" class="block text-sm font-semibold text-neutral-950">User email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                        class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                    <p class="mt-2 text-xs font-medium text-neutral-500">The user must already have a ScrumLab account.</p>
                    @error('email')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="role" class="block text-sm font-semibold text-neutral-950">Project role</label>
                    <select id="role" name="role" required
                        class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', 'developer') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="inline-flex w-full justify-center rounded-md bg-neutral-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                    Add to project
                </button>
        </form>
    </x-dashboard.modal>

    @error('member')
        <p class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</p>
    @enderror

    <section class="mt-6 rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
        <h3 class="text-lg font-bold text-neutral-950">Members</h3>

        <div class="mt-5 divide-y divide-neutral-200">
            @foreach ($members as $member)
                <div class="grid gap-4 py-4 lg:grid-cols-[1fr_16rem_auto] lg:items-center">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-neutral-950 text-sm font-bold text-white">
                            {{ strtoupper(substr($member->name, 0, 1)) }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-neutral-950">{{ $member->name }}</p>
                            <p class="truncate text-sm text-neutral-500">{{ $member->email }}</p>
                        </div>
                    </div>

                    <p class="text-sm font-semibold text-neutral-600">{{ $roles[$member->pivot->role] ?? $member->pivot->role }}</p>

                    <button type="button" data-modal-target="manage-member-{{ $member->id }}"
                        class="rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm font-semibold text-neutral-950 transition hover:border-neutral-950 lg:justify-self-end">
                        Manage
                    </button>

                    <x-dashboard.modal id="manage-member-{{ $member->id }}" title="Manage {{ $member->name }}">
                        <form method="POST" action="{{ route('projects.members.update', [$currentProject, $member]) }}" class="space-y-4">
                            @csrf
                            @method('PATCH')

                            <label class="block text-sm font-semibold text-neutral-950" for="member-{{ $member->id }}-role">Project role</label>
                            <select id="member-{{ $member->id }}-role" name="role"
                                class="w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                                @foreach ($roles as $value => $label)
                                    <option value="{{ $value }}" @selected($member->pivot->role === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="submit"
                                class="w-full rounded-md bg-neutral-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                                Save role
                            </button>
                        </form>

                        <form method="POST" action="{{ route('projects.members.destroy', [$currentProject, $member]) }}" class="mt-4 border-t border-neutral-200 pt-4">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                class="w-full rounded-md px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50">
                                Remove from project
                            </button>
                        </form>
                    </x-dashboard.modal>
                </div>
            @endforeach
        </div>
    </section>
</x-dashboard.layout>
