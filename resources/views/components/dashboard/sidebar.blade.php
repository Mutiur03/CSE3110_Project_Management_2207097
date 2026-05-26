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
@endphp

<aside id="dashboard-sidebar"
    class="fixed inset-y-0 left-0 z-40 hidden w-72 flex flex-col border-r border-neutral-200 bg-white p-4 lg:sticky lg:top-0 lg:flex lg:h-screen">
    <div class="mb-5 flex items-center gap-3 px-2">
        <img src="{{ asset('scrumlab-icon.svg') }}" alt="" class="size-10 shrink-0">
        <div class="min-w-0">
            <p class="truncate text-sm font-bold">{{ config('app.name') }}</p>
            {{-- <p class="truncate text-xs text-neutral-500">Project workspace</p> --}}
        </div>
    </div>

    @if ($currentProject)
        <div class="relative mb-5" data-project-switcher>
            <button type="button" data-project-switcher-button
                class="flex w-full items-center gap-3 rounded-lg border border-neutral-200 bg-white px-2 py-2 text-left shadow-sm transition hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-neutral-950/10">
                <span class="grid size-9 shrink-0 place-items-center rounded-md bg-neutral-950 text-xs font-bold text-white">
                    {{ strtoupper(substr($projectKey, 0, 1)) }}
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-neutral-950">{{ $projectName }}</span>
                </span>
                <svg data-project-switcher-icon class="size-4 shrink-0 text-neutral-600 transition" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                </svg>
            </button>

            <div data-project-switcher-menu
                class="absolute left-0 right-0 top-full z-50 mt-2 hidden overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-lg">
                <div class="py-1.5">
                    @foreach ($projects as $project)
                        <a href="{{ route('dashboard', ['project' => $project->id]) }}" wire:navigate
                            class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition hover:bg-neutral-100 {{ $project->is($currentProject) ? 'text-neutral-950' : 'text-neutral-700' }}">
                            <span class="min-w-0 flex-1 truncate">{{ $project->name }}</span>
                            @if ($project->is($currentProject))
                                <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                                </svg>
                            @endif
                        </a>
                    @endforeach
                </div>

                <a href="{{ route('projects.create') }}" wire:navigate
                    class="flex items-center justify-center gap-3 border-t border-neutral-200 px-4 py-3 text-sm font-semibold text-neutral-950 transition hover:bg-neutral-100">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="2.1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                    </svg>
                    New Workspace
                </a>
            </div>
        </div>
    @endif

    <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto pr-1">
        <a href="{{ route('dashboard', $currentProject ? ['project' => $currentProject->id] : []) }}" wire:navigate
            class="flex items-center gap-3 rounded-md px-3 py-3 text-sm font-semibold transition {{ $isDashboardActive ? 'bg-neutral-950 text-white' : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-950' }}">
            <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 11.25 12 4l9 7.25M5.25 10.5v8.25h4.5V14.5h4.5v4.25h4.5V10.5" />
            </svg>
            Dashboard
        </a>

        @if ($currentProject)
            <a href="{{ route('projects.members.index', $currentProject) }}" wire:navigate
                class="flex items-center gap-3 rounded-md px-3 py-3 text-sm font-semibold transition {{ $isMembersActive ? 'bg-neutral-950 text-white' : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-950' }}">
                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16 19.5v-1.25A3.25 3.25 0 0 0 12.75 15h-5.5A3.25 3.25 0 0 0 4 18.25v1.25M10 11.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM18.5 11.75a2.75 2.75 0 1 0 0-5.5" />
                </svg>
                Members
            </a>

            <a href="{{ route('projects.teams.index', $currentProject) }}" wire:navigate
                class="flex items-center gap-3 rounded-md px-3 py-3 text-sm font-semibold transition {{ $isTeamsActive ? 'bg-neutral-950 text-white' : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-950' }}">
                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16 19.5v-1.25A3.25 3.25 0 0 0 12.75 15h-5.5A3.25 3.25 0 0 0 4 18.25v1.25M10 11.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM18.5 11.75a2.75 2.75 0 1 0 0-5.5" />
                </svg>
                Teams
            </a>

            <a href="{{ route('projects.issues.index', $currentProject) }}" wire:navigate
                class="flex items-center gap-3 rounded-md px-3 py-3 text-sm font-semibold transition {{ $isIssuesActive ? 'bg-neutral-950 text-white' : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-950' }}">
                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 6.75h10.5M9 12h10.5M9 17.25h10.5M4.5 6.75h.01M4.5 12h.01M4.5 17.25h.01" />
                </svg>
                Issues
            </a>
        @endif
    </nav>

    <div class="mt-4 border-t border-neutral-200 pt-4">
        <div class="flex w-full items-center gap-3 rounded-lg px-2 py-2">
            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-black text-xs font-bold text-white">
                {{ $userInitials ?: 'U' }}
            </span>

            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-semibold text-neutral-950">{{ $userName }}</span>
                <span class="block truncate text-xs text-neutral-500">ScrumLab workspace</span>
            </span>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit"
                    class="grid size-9 place-items-center rounded-md text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-950 focus:outline-none focus:ring-2 focus:ring-neutral-950/10">
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
