<x-dashboard.layout title="Teams" :eyebrow="$currentProject->name" :current-project="$currentProject" :projects="$projects">
    @php
        $canWrite = (bool) ($currentProject->can_write ?? false);
    @endphp
    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <section class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-neutral-950 px-2.5 py-1 text-xs font-bold text-white">{{ $currentProject->key }}</span>
                        <span class="rounded-md bg-sky-100 px-2.5 py-1 text-xs font-bold text-sky-700">{{ $teams->count() }} teams</span>
                    </div>
                    <h2 class="mt-3 text-2xl font-bold tracking-normal text-neutral-950">Project teams</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-neutral-600">
                        Teams organize the people working inside this project. Issues can be assigned to a team so workload stays visible.
                    </p>
                </div>
                <div class="flex gap-2">
                    @if ($canWrite)
                    <button type="button" data-modal-target="create-team-modal"
                        class="inline-flex justify-center rounded-md bg-neutral-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                        Create team
                    </button>
                    @endif
                    <a href="{{ route('dashboard', ['project' => $currentProject->id]) }}" wire:navigate
                        class="inline-flex justify-center rounded-md border border-neutral-200 bg-white px-4 py-3 text-sm font-semibold text-neutral-950 transition hover:border-neutral-950">
                        Back
                    </a>
                </div>
            </div>
        </section>

        <aside class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold text-neutral-950">Team workflow</h3>
            <p class="mt-2 text-sm leading-6 text-neutral-600">Create teams when the project needs grouped ownership. Project members can still work without teams.</p>
        </aside>
    </div>

    @if ($canWrite)
    <x-dashboard.modal id="create-team-modal" title="Create team">
        <form method="POST" action="{{ route('projects.teams.store', $currentProject->id) }}" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-semibold text-neutral-950">Team name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required
                        class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                    @error('name')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-semibold text-neutral-950">Description</label>
                    <textarea id="description" name="description" rows="3"
                        class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="inline-flex w-full justify-center rounded-md bg-neutral-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                    Create team
                </button>
        </form>
    </x-dashboard.modal>
    @endif

    <div class="mt-6">
        @if ($teams->isEmpty())
            <div class="rounded-lg border border-dashed border-neutral-300 bg-white p-6 text-sm text-neutral-600">
                No teams have been created for this project yet.
            </div>
        @else
            <div class="grid gap-4 xl:grid-cols-2">
                @foreach ($teams as $team)
                    <article class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-neutral-950">{{ $team->name }}</h3>
                                <p class="mt-2 text-sm leading-6 text-neutral-600">{{ $team->description ?: 'No team description added.' }}</p>
                            </div>
                            <div class="flex gap-2">
                                <span class="rounded-md bg-sky-100 px-2.5 py-1 text-xs font-bold text-sky-700">{{ $team->members_count }} members</span>
                                <span class="rounded-md bg-purple-100 px-2.5 py-1 text-xs font-bold text-purple-700">{{ $team->issues_count }} issues</span>
                            </div>
                        </div>

                        <div class="mt-5 border-t border-neutral-200 pt-4">
                            <p class="text-sm font-bold text-neutral-950">Members</p>
                            <div class="mt-3 space-y-2">
                                @forelse ($team->members as $member)
                                    <div class="flex items-center gap-3 rounded-md bg-stone-50 px-3 py-2">
                                        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-neutral-950 text-xs font-bold text-white">
                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-semibold text-neutral-950">{{ $member->name }}</p>
                                            <p class="truncate text-xs text-neutral-500">{{ $member->role }}</p>
                                        </div>
                                        @if ($canWrite)
                                            <form method="POST" action="{{ route('projects.teams.members.destroy', [$currentProject->id, $team->id, $member->id]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 transition hover:bg-red-50">
                                                    Remove
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @empty
                                    <p class="rounded-md border border-dashed border-neutral-300 bg-stone-50 p-3 text-sm text-neutral-600">
                                        No members assigned to this team yet.
                                    </p>
                                @endforelse
                            </div>
                        </div>

                        @if ($canWrite)
                        <div class="mt-5 border-t border-neutral-200 pt-4">
                            <button type="button" data-modal-target="add-team-member-{{ $team->id }}"
                                class="rounded-md bg-neutral-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-neutral-800">
                                Add member
                            </button>
                        </div>

                        <x-dashboard.modal id="add-team-member-{{ $team->id }}" title="Add member to {{ $team->name }}">
                            <form method="POST" action="{{ route('projects.teams.members.store', [$currentProject->id, $team->id]) }}"
                                class="space-y-4">
                            @csrf

                            <label class="block text-sm font-semibold text-neutral-950" for="team-{{ $team->id }}-user">Project member</label>
                            <select id="team-{{ $team->id }}-user" name="user_id" required
                                class="w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                                <option value="">Select member</option>
                                @foreach ($projectMembers as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                                @endforeach
                            </select>

                            <label class="block text-sm font-semibold text-neutral-950" for="team-{{ $team->id }}-role">Role</label>
                            <select id="team-{{ $team->id }}-role" name="role" required
                                class="w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                                <option value="developer">Developer</option>
                                <option value="scrum_master">Scrum master</option>
                                <option value="qa">QA</option>
                                <option value="viewer">Viewer</option>
                            </select>

                            <button type="submit"
                                class="w-full rounded-md bg-neutral-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                                Add
                            </button>
                            </form>
                        </x-dashboard.modal>

                        <div class="mt-5 border-t border-neutral-200 pt-4">
                            <h4 class="text-sm font-bold text-neutral-950">Delete team</h4>
                            <p class="mt-1 text-xs leading-5 text-neutral-500">
                                @if ($team->issues_count > 0)
                                    {{ $team->issues_count }} linked {{ str('issue')->plural($team->issues_count) }} will become unassigned from this team.
                                @else
                                    Remove this team from the project.
                                @endif
                            </p>
                            <form method="POST" action="{{ route('projects.teams.destroy', [$currentProject->id, $team->id]) }}"
                                class="mt-3"
                                onsubmit="return confirm('Delete {{ $team->name }}? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="rounded-md px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50">
                                    Delete team
                                </button>
                            </form>
                        </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-dashboard.layout>
