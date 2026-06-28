@php
    use App\Support\BadgeTones;

    $roles = [
        'project_owner' => 'Project owner',
        'scrum_master' => 'Scrum master',
        'developer' => 'Developer',
        'viewer' => 'Viewer',
        'admin' => 'Admin',
    ];
    $canManage = (bool) ($currentProject->can_manage ?? false);
@endphp

<x-dashboard.layout title="Members" :eyebrow="$currentProject->name" :current-project="$currentProject" :projects="$projects">
    <div>
        <section class="rounded-lg border border-hairline bg-white p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded bg-ink px-2 py-0.5 font-mono text-xs font-semibold text-white">{{ $currentProject->key }}</span>
                        <span class="font-mono text-xs text-neutral-400">{{ $members->count() }} members</span>
                    </div>
                    <h2 class="mt-3 font-display text-2xl font-bold tracking-tight text-ink">Project members</h2>
                </div>
                <div class="flex gap-2">
                    @if ($canManage)
                    <button type="button" data-modal-target="add-project-member-modal"
                        class="inline-flex justify-center rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong">
                        Add member
                    </button>
                    @endif
                    <a href="{{ route('dashboard', ['project' => $currentProject->id]) }}" wire:navigate
                        class="inline-flex justify-center rounded-md border border-hairline bg-white px-4 py-2.5 text-sm font-semibold text-ink transition hover:border-ink">
                        Back
                    </a>
                </div>
            </div>
        </section>
    </div>

    @if ($canManage)
    <x-dashboard.modal id="add-project-member-modal" title="Add project member" :open="old('_form') === 'add-project-member'">
        <form method="POST" action="{{ route('projects.members.store', $currentProject->id) }}" class="space-y-4">
                @csrf
                <input type="hidden" name="_form" value="add-project-member">

                <div>
                    <label for="email" class="block text-sm font-semibold text-ink">User email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                        class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-2.5 text-sm text-ink outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                    <p class="mt-2 text-xs font-medium text-neutral-500">The user must already have a ScrumLab account.</p>
                    @error('email')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="role" class="block text-sm font-semibold text-ink">Project role</label>
                    <select id="role" name="role" required
                        class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-2.5 text-sm text-ink outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', 'developer') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="inline-flex w-full justify-center rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong">
                    Add to project
                </button>
        </form>
    </x-dashboard.modal>
    @endif

    @error('member')
        <p class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</p>
    @enderror

    <section class="mt-6 rounded-lg border border-hairline bg-white p-5">
        <h3 class="deck-label text-neutral-400">Members</h3>

        <div class="mt-5 divide-y divide-hairline">
            @foreach ($members as $member)
                <div class="grid gap-4 py-4 lg:grid-cols-[1fr_16rem_auto] lg:items-center">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-accent text-sm font-bold text-accent-fg">
                            {{ strtoupper(substr($member->name, 0, 1)) }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-ink">{{ $member->name }}</p>
                            <p class="truncate text-sm text-neutral-500">{{ $member->email }}</p>
                        </div>
                    </div>

                    <x-ui.badge :tone="BadgeTones::NEUTRAL">{{ $roles[$member->role] ?? $member->role }}</x-ui.badge>

                    @if ($canManage)
                    <button type="button" data-modal-target="manage-member-{{ $member->id }}"
                        class="rounded-md border border-hairline bg-white px-3 py-2 text-sm font-semibold text-ink transition hover:border-ink lg:justify-self-end">
                        Manage
                    </button>

                    <x-dashboard.modal id="manage-member-{{ $member->id }}" title="Manage {{ $member->name }}">
                        <form method="POST" action="{{ route('projects.members.update', [$currentProject->id, $member->id]) }}" class="space-y-4">
                            @csrf
                            @method('PATCH')

                            <label class="block text-sm font-semibold text-ink" for="member-{{ $member->id }}-role">Project role</label>
                            <select id="member-{{ $member->id }}-role" name="role"
                                class="w-full rounded-md border border-hairline bg-white px-3 py-2.5 text-sm text-ink outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                                @foreach ($roles as $value => $label)
                                    <option value="{{ $value }}" @selected($member->role === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="submit"
                                class="w-full rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong">
                                Save role
                            </button>
                        </form>

                        <form method="POST" action="{{ route('projects.members.destroy', [$currentProject->id, $member->id]) }}" class="mt-4 border-t border-hairline pt-4">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                class="w-full rounded-md px-4 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-50">
                                Remove from project
                            </button>
                        </form>
                    </x-dashboard.modal>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
</x-dashboard.layout>
