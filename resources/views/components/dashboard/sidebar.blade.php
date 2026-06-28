@props([
    'currentProject' => null,
    'projects' => collect(),
])

@php
    $currentUser = auth()->user();
    $userName = $currentUser->name ?? 'User';
    $userInitials = collect(explode(' ', trim($userName)))
        ->filter()
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->take(2)
        ->implode('');
    $projectName = data_get($currentProject, 'name');
    $projectKey = data_get($currentProject, 'key');
    $isDashboardActive = request()->routeIs('dashboard');
    $isMembersActive = request()->routeIs('projects.members.*');
    $isTeamsActive = request()->routeIs('projects.teams.*');
    $isIssuesActive = request()->routeIs('projects.issues.*');
    $isSprintsActive = request()->routeIs('projects.sprints.*');
    $isBoardActive = request()->routeIs('projects.board.*');
    $isActivityActive = request()->routeIs('projects.activity.*');
    $isSettingsActive = request()->routeIs('projects.settings.*');
    $isProfileActive = request()->routeIs('profile.*');
    $canManageProject = (bool) ($currentProject->can_manage ?? false);

    $navBase = 'group relative flex items-center gap-3 rounded-md py-2.5 pl-4 pr-3 text-sm transition';
    $navIdle = 'font-medium text-muted-foreground hover:bg-spine-soft hover:text-ink';
    $navOn = 'bg-accent font-semibold text-accent-fg';
@endphp

<aside id="dashboard-sidebar"
    class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r border-spine-line bg-spine p-4 text-ink lg:sticky lg:top-0 lg:flex lg:h-screen">
    {{-- brand --}}
    <div class="mb-6 flex items-center gap-3 px-2">
        <img src="{{ asset('scrumlab-icon.svg') }}" alt="" class="size-9 shrink-0">
        <div class="min-w-0">
            <p class="truncate font-display text-sm font-bold tracking-tight text-ink">{{ config('app.name') }}</p>
        </div>
    </div>

    @if ($currentProject)
        <div class="relative mb-6" data-project-switcher>
            <button type="button" data-project-switcher-button
                class="flex w-full items-center gap-3 rounded-md border border-spine-line bg-card px-2.5 py-2.5 text-left transition hover:bg-spine-soft focus:outline-none focus:ring-2 focus:ring-accent/40">
                <span class="grid size-8 shrink-0 place-items-center rounded bg-accent font-mono text-xs font-bold text-accent-fg">
                    {{ strtoupper(substr($projectKey, 0, 1)) }}
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-ink">{{ $projectName }}</span>
                    <span class="block truncate text-[0.7rem] text-muted-foreground">Workspace</span>
                </span>
                <svg data-project-switcher-icon class="size-4 shrink-0 text-muted-foreground transition" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                </svg>
            </button>

            <div data-project-switcher-menu
                class="absolute left-0 right-0 top-full z-50 mt-2 hidden overflow-hidden rounded-md border border-hairline bg-card shadow-lg">
                <div class="py-1.5">
                    @foreach ($projects as $project)
                        <a href="{{ route('dashboard', ['project' => $project->id]) }}" wire:navigate
                            class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition hover:bg-spine-soft {{ $currentProject && $project->id === $currentProject->id ? 'text-ink' : 'text-muted-foreground' }}">
                            <span class="min-w-0 flex-1 truncate">{{ $project->name }}</span>
                            @if ($currentProject && $project->id === $currentProject->id)
                                <svg class="size-4 shrink-0 text-accent" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                                </svg>
                            @endif
                        </a>
                    @endforeach
                </div>

                <a href="{{ route('projects.create') }}" wire:navigate
                    class="flex items-center justify-center gap-2 border-t border-hairline px-4 py-3 text-sm font-semibold text-accent transition hover:bg-spine-soft">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="2.1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                    </svg>
                    New workspace
                </a>
            </div>
        </div>
    @endif

    <nav class="min-h-0 flex-1 space-y-6 overflow-y-auto pr-1">
        <div class="space-y-1">
            <a href="{{ route('dashboard', $currentProject ? ['project' => $currentProject->id] : []) }}" wire:navigate
                class="{{ $navBase }} {{ $isDashboardActive ? $navOn : $navIdle }}">
                <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 11.25 12 4l9 7.25M5.25 10.5v8.25h4.5V14.5h4.5v4.25h4.5V10.5" />
                </svg>
                Dashboard
            </a>
        </div>

        @if ($currentProject)
            <div class="space-y-1">
                <p class="deck-label mb-2 px-4 text-xs uppercase tracking-wider text-muted-foreground">Project</p>

                <a href="{{ route('projects.board.index', $currentProject->id) }}" wire:navigate
                    class="{{ $navBase }} {{ $isBoardActive ? $navOn : $navIdle }}">
                    <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5h16M6.5 8.5v10M12 8.5v10M17.5 8.5v10" />
                    </svg>
                    Board
                </a>

                <a href="{{ route('projects.issues.index', $currentProject->id) }}" wire:navigate
                    class="{{ $navBase }} {{ $isIssuesActive ? $navOn : $navIdle }}">
                    <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 6.75h10.5M9 12h10.5M9 17.25h10.5M4.5 6.75h.01M4.5 12h.01M4.5 17.25h.01" />
                    </svg>
                    Issues
                </a>

                <a href="{{ route('projects.sprints.index', $currentProject->id) }}" wire:navigate
                    class="{{ $navBase }} {{ $isSprintsActive ? $navOn : $navIdle }}">
                    <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M7 3.75v3M17 3.75v3M4.75 8.25h14.5M6.5 5.25h11A2.25 2.25 0 0 1 19.75 7.5v10.25A2.25 2.25 0 0 1 17.5 20h-11a2.25 2.25 0 0 1-2.25-2.25V7.5A2.25 2.25 0 0 1 6.5 5.25Z" />
                    </svg>
                    Sprints
                </a>

                <a href="{{ route('projects.activity.index', $currentProject->id) }}" wire:navigate
                    class="{{ $navBase }} {{ $isActivityActive ? $navOn : $navIdle }}">
                    <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6v6l3.5 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Activity
                </a>
            </div>

            <div class="space-y-1">
                <p class="deck-label mb-2 px-4 text-xs uppercase tracking-wider text-muted-foreground">People</p>

                <a href="{{ route('projects.members.index', $currentProject->id) }}" wire:navigate
                    class="{{ $navBase }} {{ $isMembersActive ? $navOn : $navIdle }}">
                    <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16 19.5v-1.25A3.25 3.25 0 0 0 12.75 15h-5.5A3.25 3.25 0 0 0 4 18.25v1.25M10 11.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM18.5 11.75a2.75 2.75 0 1 0 0-5.5" />
                    </svg>
                    Members
                </a>

                <a href="{{ route('projects.teams.index', $currentProject->id) }}" wire:navigate
                    class="{{ $navBase }} {{ $isTeamsActive ? $navOn : $navIdle }}">
                    <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16 19.5v-1.25A3.25 3.25 0 0 0 12.75 15h-5.5A3.25 3.25 0 0 0 4 18.25v1.25M10 11.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM18.5 11.75a2.75 2.75 0 1 0 0-5.5" />
                    </svg>
                    Teams
                </a>

                @if ($canManageProject)
                    <a href="{{ route('projects.settings.edit', $currentProject->id) }}" wire:navigate
                        class="{{ $navBase }} {{ $isSettingsActive ? $navOn : $navIdle }}">
                        <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9.594 3.94a2.25 2.25 0 0 1 3.182 0l1.036 1.036a2.25 2.25 0 0 0 3.182 0l1.036-1.036a2.25 2.25 0 0 1 3.182 0l1.036 1.036a2.25 2.25 0 0 0 3.182 0M4.5 20.25h15a1.5 1.5 0 0 0 1.5-1.5V6.75a1.5 1.5 0 0 0-1.5-1.5h-15a1.5 1.5 0 0 0-1.5 1.5v12a1.5 1.5 0 0 0 1.5 1.5Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Z" />
                        </svg>
                        Settings
                    </a>
                @endif
            </div>
        @endif
    </nav>

    <div class="mt-4 border-t border-spine-line pt-3">
        <div class="flex w-full items-center gap-3 rounded-md px-1 py-1">
            <a href="{{ route('profile.edit', $currentProject ? ['project' => $currentProject->id] : []) }}" wire:navigate
                class="flex min-w-0 flex-1 items-center gap-3 rounded-md px-2 py-2 transition hover:bg-spine-soft {{ $isProfileActive ? 'bg-spine-soft' : '' }}">
                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-accent/10 font-mono text-xs font-bold text-accent">
                    {{ $userInitials ?: 'U' }}
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-ink">{{ $userName }}</span>
                </span>
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="grid size-9 place-items-center rounded-md text-muted-foreground transition hover:bg-spine-soft hover:text-ink focus:outline-none focus:ring-2 focus:ring-accent/40">
                    <span class="sr-only">Log out</span>
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 9V5.75A1.75 1.75 0 0 0 14 4H6.75A1.75 1.75 0 0 0 5 5.75v12.5A1.75 1.75 0 0 0 6.75 20H14a1.75 1.75 0 0 0 1.75-1.75V15M12 12h7.5m0 0-2.75-2.75M19.5 12l-2.75 2.75" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>
