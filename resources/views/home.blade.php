<x-layout>
    <div class="min-h-screen bg-canvas font-sans text-ink antialiased">
        <header class="sticky top-0 z-30 border-b border-hairline bg-canvas/90 backdrop-blur">
            <div
                class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-4 gap-y-3 px-4 py-3 sm:px-6 sm:py-4 lg:px-8">
                <a href="{{ url('/') }}"
                    class="flex min-w-0 items-center gap-3 font-semibold tracking-tight text-ink">
                    <img src="{{ asset('scrumlab-icon.svg') }}" alt="" class="size-9 shrink-0 sm:size-10">
                    <span class="font-display text-lg tracking-tight">{{ config('app.name') }}</span>
                </a>

                <nav class="hidden items-center gap-7 text-sm font-medium text-neutral-500 lg:flex">
                    <a class="transition hover:text-ink" href="#features">Features</a>
                    <a class="transition hover:text-ink" href="#workflow">Workflow</a>
                    <a class="transition hover:text-ink" href="#roles">Roles</a>
                </nav>

                @if (Route::has('login'))
                    <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                        @auth
                            <a class="rounded-md border border-hairline bg-white px-3 py-2 text-sm font-semibold text-ink transition hover:border-ink sm:px-4"
                                href="{{ url('/dashboard') }}" wire:navigate>Dashboard</a>
                        @else
                            <a class="rounded-md px-2.5 py-2 text-sm font-semibold text-neutral-500 transition hover:text-ink sm:px-4"
                                href="{{ route('login') }}" wire:navigate>Log in</a>

                            @if (Route::has('register'))
                                <a class="rounded-md bg-accent px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong sm:px-4"
                                    href="{{ route('register') }}" wire:navigate>Get started</a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>
        </header>

        <main>
            <section
                class="mx-auto grid max-w-7xl items-center gap-10 px-4 pb-14 pt-10 sm:px-6 sm:pb-16 sm:pt-12 lg:grid-cols-[1fr_0.95fr] lg:gap-12 lg:px-8 lg:pb-24 lg:pt-20">
                <div>
                    <p class="deck-label text-accent">Scrum workspace</p>
                    <h1
                        class="mt-4 max-w-4xl font-display text-4xl font-bold leading-[1.05] tracking-tight text-ink min-[420px]:text-5xl sm:text-6xl lg:text-7xl">
                        Plan sprints, track issues, and move team work forward.
                    </h1>

                    <p class="mt-5 max-w-2xl text-base leading-7 text-neutral-500 sm:mt-6 sm:text-lg sm:leading-8">
                        A Jira-style workspace for projects, sprints, and issues.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a class="inline-flex items-center justify-center rounded-md bg-accent px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-accent-strong"
                            href="{{ Route::has('register') ? route('register') : '#features' }}">
                            Create workspace
                        </a>
                        <a class="inline-flex items-center justify-center rounded-md border border-hairline bg-white px-6 py-3 text-sm font-bold text-ink transition hover:border-ink"
                            href="#workflow">
                            Preview board
                        </a>
                    </div>

                    <dl class="mt-10 grid max-w-2xl grid-cols-1 gap-3 min-[460px]:grid-cols-3">
                        <div class="rounded-lg border border-hairline bg-white p-4">
                            <dt class="deck-label text-neutral-400">Issue types</dt>
                            <dd class="mt-2 font-display text-2xl font-bold tabular-nums text-ink sm:text-3xl">4</dd>
                        </div>
                        <div class="rounded-lg border border-hairline bg-white p-4">
                            <dt class="deck-label text-neutral-400">Stages</dt>
                            <dd class="mt-2 font-display text-2xl font-bold tabular-nums text-ink sm:text-3xl">5</dd>
                        </div>
                        <div class="rounded-lg border border-hairline bg-white p-4">
                            <dt class="deck-label text-neutral-400">Roles</dt>
                            <dd class="mt-2 font-display text-2xl font-bold tabular-nums text-ink sm:text-3xl">5</dd>
                        </div>
                    </dl>
                </div>

                <div class="relative min-w-0">
                    <div class="relative overflow-hidden rounded-lg border border-hairline bg-white">
                        <div
                            class="flex items-center justify-between gap-3 border-b border-hairline bg-canvas px-4 py-4 text-ink sm:px-5">
                            <div>
                                <p class="font-display text-sm font-bold tracking-tight">Campus Portal</p>
                                <p class="font-mono text-xs text-neutral-400">Sprint 02 &middot; 8 days left</p>
                            </div>
                            <span
                                class="rounded-md bg-accent/10 px-3 py-1 font-mono text-xs font-bold uppercase text-accent">Active</span>
                        </div>

                        <div class="grid gap-4 p-3 min-[560px]:grid-cols-3 sm:p-4">
                            <div class="rounded-md border border-hairline bg-canvas p-3">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="deck-label text-neutral-400">Backlog</span>
                                    <span class="font-mono text-xs text-neutral-400">12</span>
                                </div>
                                <div class="space-y-3">
                                    <article class="rounded-md border border-l-2 border-hairline border-l-border bg-white p-3">
                                        <span
                                            class="rounded bg-muted px-2 py-1 font-mono text-[10px] font-bold uppercase text-muted-foreground">EPIC</span>
                                        <h2 class="mt-3 text-sm font-bold text-ink">Project workspace setup</h2>
                                        <p class="mt-1 text-xs text-neutral-500">Create project keys and ownership.</p>
                                    </article>
                                    <article class="rounded-md border border-l-2 border-hairline border-l-border bg-white p-3">
                                        <span
                                            class="rounded bg-muted px-2 py-1 font-mono text-[10px] font-bold uppercase text-muted-foreground">STORY</span>
                                        <h2 class="mt-3 text-sm font-bold text-ink">Invite team members</h2>
                                    </article>
                                </div>
                            </div>

                            <div class="rounded-md border border-hairline bg-canvas p-3">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="deck-label text-neutral-400">In progress</span>
                                    <span class="font-mono text-xs text-neutral-400">5</span>
                                </div>
                                <div class="space-y-3">
                                    <article class="rounded-md border border-l-2 border-hairline border-l-border bg-white p-3">
                                        <span
                                            class="rounded bg-muted px-2 py-1 font-mono text-[10px] font-bold uppercase text-muted-foreground">TASK</span>
                                        <h2 class="mt-3 text-sm font-bold text-ink">Build sprint planning panel
                                        </h2>
                                        <div class="mt-3 h-2 rounded-full bg-neutral-200">
                                            <div class="h-2 w-2/3 rounded-full bg-accent"></div>
                                        </div>
                                    </article>
                                    <article class="rounded-md border border-l-2 border-hairline border-l-border bg-white p-3">
                                        <span
                                            class="rounded bg-muted px-2 py-1 font-mono text-[10px] font-bold uppercase text-muted-foreground">BUG</span>
                                        <h2 class="mt-3 text-sm font-bold text-ink">Fix status transition rule
                                        </h2>
                                    </article>
                                </div>
                            </div>

                            <div class="rounded-md border border-hairline bg-canvas p-3">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="deck-label text-neutral-400">Done</span>
                                    <span class="font-mono text-xs text-neutral-400">9</span>
                                </div>
                                <div class="space-y-3">
                                    <article class="rounded-md border border-l-2 border-hairline border-l-border bg-white p-3">
                                        <span
                                            class="rounded bg-muted px-2 py-1 font-mono text-[10px] font-bold uppercase text-muted-foreground">STORY</span>
                                        <h2 class="mt-3 text-sm font-bold text-ink">Issue detail comments</h2>
                                    </article>
                                    <article class="rounded-md border border-l-2 border-hairline border-l-border bg-white p-3">
                                        <span
                                            class="rounded bg-muted px-2 py-1 font-mono text-[10px] font-bold uppercase text-muted-foreground">TASK</span>
                                        <h2 class="mt-3 text-sm font-bold text-ink">Activity log seed data</h2>
                                    </article>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-hairline bg-canvas px-5 py-4">
                            <div class="flex items-center justify-between">
                                <span class="deck-label text-neutral-400">Sprint completion</span>
                                <span class="font-mono text-xs text-neutral-400">68%</span>
                            </div>
                            <div class="mt-2 h-2 rounded-full bg-neutral-200">
                                <div class="h-2 w-[68%] rounded-full bg-accent"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="features" class="border-y border-hairline bg-white py-14 sm:py-16 lg:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="max-w-3xl">
                        <p class="deck-label text-accent">Capabilities</p>
                        <h2 class="mt-3 font-display text-3xl font-bold leading-tight tracking-tight text-ink sm:text-4xl">Everything teams
                            need to manage Scrum work.</h2>
                    </div>

                    <div class="mt-8 grid gap-4 sm:mt-10 md:grid-cols-3">
                        <article class="rounded-lg border border-hairline bg-white p-6">
                            <h3 class="font-display text-xl font-bold tracking-tight text-ink">Project workspaces</h3>
                            <p class="mt-3 leading-7 text-neutral-500">Organize project keys, descriptions, owners,
                                members, and team access in one shared space.</p>
                        </article>

                        <article class="rounded-lg border border-hairline bg-white p-6">
                            <h3 class="font-display text-xl font-bold tracking-tight text-ink">Sprint planning</h3>
                            <p class="mt-3 leading-7 text-neutral-500">Move stories from backlog to sprint, track
                                capacity, and keep sprint goals visible.</p>
                        </article>

                        <article class="rounded-lg border border-hairline bg-white p-6">
                            <h3 class="font-display text-xl font-bold tracking-tight text-ink">Issue discussions</h3>
                            <p class="mt-3 leading-7 text-neutral-500">Use comments, assignments, and activity history
                                to practice transparent collaboration.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="workflow" class="py-14 sm:py-16 lg:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
                        <div>
                            <p class="deck-label text-accent">Workflow</p>
                            <h2 class="mt-3 font-display text-3xl font-bold leading-tight tracking-tight text-ink sm:text-4xl">From backlog
                                to done, the path stays visible.</h2>
                            <p class="mt-4 text-base leading-7 text-neutral-500">Teams can see where work sits, what is
                                blocked, and what needs attention before the sprint closes.</p>
                        </div>

                        <div class="-mx-4 overflow-x-auto px-4 pb-2 sm:mx-0 sm:px-0">
                            <div class="grid min-w-190 grid-cols-5 gap-3 lg:min-w-0">
                                @foreach (['Backlog', 'Selected', 'In Progress', 'Review', 'Done'] as $index => $status)
                                    <div class="rounded-lg border border-hairline bg-white p-3">
                                        <h3 class="deck-label mb-4 text-neutral-400">{{ $status }}</h3>
                                        <div class="space-y-3">
                                            <div @class([
                                                'rounded-md border border-l-2 border-hairline bg-white p-3',
                                                'border-l-border' => $index === 0,
                                                'border-l-border' => $index === 1,
                                                'border-l-border' => $index === 2,
                                                'border-l-border' => $index === 3,
                                                'border-l-border' => $index === 4,
                                            ])>
                                                <span class="font-mono text-xs text-neutral-400">SCRUM-{{ $index + 1 }}</span>
                                                <p class="mt-2 text-sm font-semibold text-ink">
                                                    {{ ['Write epic', 'Plan sprint', 'Build task', 'Peer review', 'Close story'][$index] }}
                                                </p>
                                            </div>
                                            @if ($index === 0 || $index === 2)
                                                <div @class([
                                                    'rounded-md border border-l-2 border-hairline bg-white p-3',
                                                    'border-l-border' => $index === 0,
                                                    'border-l-border' => $index === 2,
                                                ])>
                                                    <span class="font-mono text-xs text-neutral-400">SCRUM-{{ $index + 7 }}</span>
                                                    <p class="mt-2 text-sm font-semibold text-ink">
                                                        {{ $index === 0 ? 'Refine backlog' : 'Update assignee' }}
                                                    </p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="roles"
                class="border-y border-hairline bg-white py-14 text-ink sm:py-16 lg:py-20">
                <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:gap-10 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:px-8">
                    <div>
                        <p class="deck-label text-accent">Roles</p>
                        <h2 class="mt-3 font-display text-3xl font-bold leading-tight tracking-tight sm:text-4xl">Clear ownership for every project
                            role.</h2>
                        <p class="mt-4 leading-7 text-neutral-500">Role-aware workflows keep ownership, facilitation,
                            implementation, and visibility connected to the work.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ([['Project owner', 'Defines scope, priorities, and project outcomes.'], ['Scrum master', 'Protects the process and removes blockers.'], ['Developer', 'Ships issues and updates progress clearly.'], ['Viewer', 'Follows team progress and learns from the workflow.']] as [$role, $description])
                            <article @class([
                                'rounded-lg border border-l-2 border-hairline bg-white p-5',
                                'border-l-border' => $loop->index === 0,
                                'border-l-border' => $loop->index === 1,
                                'border-l-border' => $loop->index === 2,
                                'border-l-border' => $loop->index === 3,
                            ])>
                                <span class="mb-4 inline-flex rounded-md bg-muted px-2.5 py-1 font-mono text-xs font-bold uppercase text-muted-foreground">Role</span>
                                <h3 class="font-display text-lg font-bold tracking-tight">{{ $role }}</h3>
                                <p class="mt-3 leading-7 text-neutral-500">{{ $description }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="px-4 py-14 sm:px-6 sm:py-16 lg:px-8 lg:py-20">
                <div
                    class="mx-auto flex max-w-7xl flex-col gap-6 rounded-lg border border-hairline bg-white p-6 sm:p-8 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl lg:text-3xl">Bring projects,
                            sprints, issues, and teams into one workflow.</h2>
                    </div>
                    <a class="inline-flex items-center justify-center rounded-md bg-accent px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-accent-strong"
                        href="{{ Route::has('register') ? route('register') : '#features' }}">
                        Start building
                    </a>
                </div>
            </section>
        </main>

        <footer class="border-t border-hairline px-4 py-8 sm:px-6 lg:px-8">
            <div
                class="mx-auto flex max-w-7xl flex-col gap-3 text-sm text-neutral-500 sm:flex-row sm:items-center sm:justify-between">
                <p>Projects, sprints, and teams in one workspace.</p>
                <p class="font-display font-semibold tracking-tight text-ink">{{ config('app.name', 'ScrumLab') }}</p>
            </div>
        </footer>

        <script>
            document.querySelectorAll('a[href^="#"]').forEach((link) => {
                link.addEventListener('click', (event) => {
                    const target = document.querySelector(link.getAttribute('href'));

                    if (!target) {
                        return;
                    }

                    event.preventDefault();

                    const headerHeight = document.querySelector('header')?.offsetHeight ?? 0;
                    const targetTop = target.getBoundingClientRect().top + window.scrollY - headerHeight;

                    window.scrollTo({
                        top: targetTop,
                        behavior: 'smooth',
                    });

                    history.pushState(null, '', link.getAttribute('href'));
                });
            });
        </script>
    </div>
</x-layout>
