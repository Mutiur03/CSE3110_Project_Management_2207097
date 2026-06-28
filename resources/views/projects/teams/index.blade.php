@php
    use App\Support\BadgeTones;

    $canWrite = (bool) ($currentProject->can_write ?? false);
@endphp

<x-dashboard.layout title="Teams" :eyebrow="$currentProject->name" :current-project="$currentProject" :projects="$projects">
    <div>
        <section class="rounded-lg border border-hairline bg-white p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.key-badge :label="$currentProject->key" />
                        <span class="font-mono text-xs text-neutral-400">{{ $teams->count() }} teams</span>
                    </div>
                    <h2 class="mt-3 font-display text-2xl font-bold tracking-tight text-ink">Project teams</h2>
                </div>
                <div class="flex gap-2">
                    @if ($canWrite)
                    <button type="button" data-modal-target="create-team-modal"
                        class="inline-flex justify-center rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong">
                        Create team
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

    @if ($canWrite)
    <x-dashboard.modal id="create-team-modal" title="Create team">
        <form method="POST" action="{{ route('projects.teams.store', $currentProject->id) }}" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-semibold text-ink">Team name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required
                        class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-2.5 text-sm text-ink outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                    @error('name')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-semibold text-ink">Description</label>
                    <textarea id="description" name="description" rows="3"
                        class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-2.5 text-sm text-ink outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="inline-flex w-full justify-center rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong">
                    Create team
                </button>
        </form>
    </x-dashboard.modal>
    @endif

    <div class="mt-6">
        @if ($teams->isEmpty())
            <div class="rounded-lg border border-dashed border-hairline bg-white p-6 text-sm text-neutral-500">
                No teams yet.
            </div>
        @else
            <div class="grid gap-4 xl:grid-cols-2">
                @foreach ($teams as $team)
                    <article class="rounded-lg border border-hairline bg-white p-5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="font-display text-lg font-bold text-ink">{{ $team->name }}</h3>
                                <p class="mt-2 text-sm leading-6 text-neutral-500">{{ $team->description ?: 'No team description added.' }}</p>
                            </div>
                            <div class="flex gap-3">
                                <span class="font-mono text-xs text-neutral-400">{{ $team->members_count }} members</span>
                                <span class="font-mono text-xs text-neutral-400">{{ $team->issues_count }} issues</span>
                            </div>
                        </div>

                        <div class="mt-5 border-t border-hairline pt-4">
                            <p class="deck-label text-neutral-400">Members</p>
                            <div class="mt-3 space-y-2">
                                @forelse ($team->members as $member)
                                    <div class="flex items-center gap-3 rounded-md bg-canvas px-3 py-2">
                                        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-accent text-xs font-bold text-accent-fg">
                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-semibold text-ink">{{ $member->name }}</p>
                                            <x-ui.badge class="mt-1" :tone="BadgeTones::NEUTRAL">{{ $member->role }}</x-ui.badge>
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
                                    <p class="rounded-md border border-dashed border-hairline bg-canvas p-3 text-sm text-neutral-500">
                                        No members assigned to this team yet.
                                    </p>
                                @endforelse
                            </div>
                        </div>

                        @if ($canWrite)
                        <div class="mt-5 border-t border-hairline pt-4">
                            <button type="button" data-modal-target="add-team-member-{{ $team->id }}"
                                class="rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong">
                                Add member
                            </button>
                        </div>

                        <x-dashboard.modal id="add-team-member-{{ $team->id }}" title="Add member to {{ $team->name }}">
                            <form method="POST" action="{{ route('projects.teams.members.store', [$currentProject->id, $team->id]) }}"
                                class="space-y-4">
                            @csrf

                            <label class="block text-sm font-semibold text-ink" for="team-{{ $team->id }}-user">Project member</label>
                            <select id="team-{{ $team->id }}-user" name="user_id" required
                                class="w-full rounded-md border border-hairline bg-white px-3 py-2.5 text-sm text-ink outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                                <option value="">Select member</option>
                                @foreach ($projectMembers as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                                @endforeach
                            </select>

                            <label class="block text-sm font-semibold text-ink" for="team-{{ $team->id }}-role">Role</label>
                            <select id="team-{{ $team->id }}-role" name="role" required
                                class="w-full rounded-md border border-hairline bg-white px-3 py-2.5 text-sm text-ink outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                                <option value="developer">Developer</option>
                                <option value="scrum_master">Scrum master</option>
                                <option value="qa">QA</option>
                                <option value="viewer">Viewer</option>
                            </select>

                            <button type="submit"
                                class="w-full rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong">
                                Add
                            </button>
                            </form>
                        </x-dashboard.modal>

                        <div class="mt-5 border-t border-hairline pt-4">
                            <h4 class="deck-label text-neutral-400">Delete team</h4>
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
