<x-layout>
    <div class="min-h-screen bg-stone-50 font-sans text-neutral-950 antialiased">
        <div class="pointer-events-none fixed inset-0 -z-10 bg-linear-to-br from-white via-stone-50 to-neutral-100">
        </div>

        <header class="sticky top-0 z-30 border-b border-neutral-200/80 bg-stone-50/90 backdrop-blur">
            <div
                class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-4 gap-y-3 px-4 py-3 sm:px-6 sm:py-4 lg:px-8">
                <a href="{{ url('/') }}"
                    class="flex min-w-0 items-center gap-3 font-semibold tracking-tight text-neutral-950">
                    <img src="{{ asset('scrumlab-icon.svg') }}" alt="" class="size-9 shrink-0 sm:size-10">
                    <span class="text-lg">{{ config('app.name') }}</span>
                </a>

                <nav class="hidden items-center gap-7 text-sm font-medium text-neutral-500 lg:flex">
                    <a class="transition hover:text-neutral-950" href="#features">Features</a>
                    <a class="transition hover:text-neutral-950" href="#workflow">Workflow</a>
                    <a class="transition hover:text-neutral-950" href="#roles">Roles</a>
                </nav>

                @if (Route::has('login'))
                    <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                        @auth
                            <a class="rounded-md border border-neutral-300 px-3 py-2 text-sm font-semibold text-neutral-950 transition hover:border-neutral-950 sm:px-4"
                                href="{{ url('/dashboard') }}" wire:navigate>Dashboard</a>
                        @else
                            <a class="rounded-md px-2.5 py-2 text-sm font-semibold text-neutral-500 transition hover:text-neutral-950 sm:px-4"
                                href="{{ route('login') }}" wire:navigate>Log in</a>

                            @if (Route::has('register'))
                                <a class="rounded-md bg-neutral-950 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-neutral-800 sm:px-4"
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
                    <p
                        class="mb-5 inline-flex max-w-full items-center gap-2 rounded-md border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-sky-700 sm:text-xs sm:tracking-[0.18em]">
                        Scrum project management
                    </p>

                    <h1
                        class="max-w-4xl text-4xl font-bold leading-[1.05] tracking-normal text-neutral-950 min-[420px]:text-5xl sm:text-6xl lg:text-7xl">
                        Plan sprints, track issues, and move team work forward.
                    </h1>

                    <p class="mt-5 max-w-2xl text-base leading-7 text-neutral-600 sm:mt-6 sm:text-lg sm:leading-8">
                        ScrumLab gives teams a Jira-style workspace to organize projects, plan sprints,
                        assign issues, discuss progress, and move work from backlog to done.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a class="inline-flex items-center justify-center rounded-md bg-neutral-950 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-neutral-800"
                            href="{{ Route::has('register') ? route('register') : '#features' }}">
                            Create workspace
                        </a>
                        <a class="inline-flex items-center justify-center rounded-md border border-neutral-300 bg-white/80 px-6 py-3 text-sm font-bold text-neutral-950 transition hover:border-neutral-950"
                            href="#workflow">
                            Preview board
                        </a>
                    </div>

                    <dl class="mt-10 grid max-w-2xl grid-cols-1 gap-3 min-[460px]:grid-cols-3">
                        <div class="rounded-lg border border-neutral-200 bg-white/75 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Issue types
                            </dt>
                            <dd class="mt-2 text-2xl font-bold text-neutral-950 sm:text-3xl">4</dd>
                        </div>
                        <div class="rounded-lg border border-neutral-200 bg-white/75 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Stages</dt>
                            <dd class="mt-2 text-2xl font-bold text-neutral-950 sm:text-3xl">5</dd>
                        </div>
                        <div class="rounded-lg border border-neutral-200 bg-white/75 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Roles</dt>
                            <dd class="mt-2 text-2xl font-bold text-neutral-950 sm:text-3xl">5</dd>
                        </div>
                    </dl>
                </div>

                <div class="relative min-w-0">
                    <div
                        class="absolute -left-5 top-10 hidden h-28 w-28 rounded-full border border-neutral-200 lg:block">
                    </div>
                    <div class="relative overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-sm">
                        <div
                            class="flex items-center justify-between gap-3 border-b border-neutral-200 bg-stone-100 px-4 py-4 text-neutral-950 sm:px-5">
                            <div>
                                <p class="text-sm font-bold">Campus Portal</p>
                                <p class="text-xs text-neutral-500">Sprint 02 &middot; 8 days left</p>
                            </div>
                            <span
                                class="rounded-md bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Active</span>
                        </div>

                        <div class="grid gap-4 p-3 min-[560px]:grid-cols-3 sm:p-4">
                            <div class="rounded-md border border-neutral-200 bg-stone-50 p-3">
                                <div class="mb-3 flex items-center justify-between text-xs font-bold text-neutral-500">
                                    <span>Backlog</span>
                                    <span>12</span>
                                </div>
                                <div class="space-y-3">
                                    <article class="rounded-md border border-neutral-200 bg-white p-3 shadow-sm">
                                        <span
                                            class="rounded bg-purple-100 px-2 py-1 text-[10px] font-bold text-purple-700">EPIC</span>
                                        <h2 class="mt-3 text-sm font-bold text-neutral-950">Project workspace setup</h2>
                                        <p class="mt-1 text-xs text-neutral-500">Create project keys and ownership.</p>
                                    </article>
                                    <article class="rounded-md border border-neutral-200 bg-white p-3 shadow-sm">
                                        <span
                                            class="rounded bg-sky-100 px-2 py-1 text-[10px] font-bold text-sky-700">STORY</span>
                                        <h2 class="mt-3 text-sm font-bold text-neutral-950">Invite team members</h2>
                                    </article>
                                </div>
                            </div>

                            <div class="rounded-md border border-neutral-200 bg-stone-50 p-3">
                                <div class="mb-3 flex items-center justify-between text-xs font-bold text-neutral-500">
                                    <span>In progress</span>
                                    <span>5</span>
                                </div>
                                <div class="space-y-3">
                                    <article class="rounded-md border border-neutral-200 bg-white p-3 shadow-sm">
                                        <span
                                            class="rounded bg-emerald-100 px-2 py-1 text-[10px] font-bold text-emerald-700">TASK</span>
                                        <h2 class="mt-3 text-sm font-bold text-neutral-950">Build sprint planning panel
                                        </h2>
                                        <div class="mt-3 h-2 rounded-full bg-neutral-200">
                                            <div class="h-2 w-2/3 rounded-full bg-sky-600"></div>
                                        </div>
                                    </article>
                                    <article class="rounded-md border border-neutral-200 bg-white p-3 shadow-sm">
                                        <span
                                            class="rounded bg-rose-100 px-2 py-1 text-[10px] font-bold text-rose-700">BUG</span>
                                        <h2 class="mt-3 text-sm font-bold text-neutral-950">Fix status transition rule
                                        </h2>
                                    </article>
                                </div>
                            </div>

                            <div class="rounded-md border border-neutral-200 bg-stone-50 p-3">
                                <div class="mb-3 flex items-center justify-between text-xs font-bold text-neutral-500">
                                    <span>Done</span>
                                    <span>9</span>
                                </div>
                                <div class="space-y-3">
                                    <article class="rounded-md border border-neutral-200 bg-white p-3 shadow-sm">
                                        <span
                                            class="rounded bg-sky-100 px-2 py-1 text-[10px] font-bold text-sky-700">STORY</span>
                                        <h2 class="mt-3 text-sm font-bold text-neutral-950">Issue detail comments</h2>
                                    </article>
                                    <article class="rounded-md border border-neutral-200 bg-white p-3 shadow-sm">
                                        <span
                                            class="rounded bg-emerald-100 px-2 py-1 text-[10px] font-bold text-emerald-700">TASK</span>
                                        <h2 class="mt-3 text-sm font-bold text-neutral-950">Activity log seed data</h2>
                                    </article>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-neutral-200 bg-stone-100 px-5 py-4">
                            <div class="flex items-center justify-between text-xs font-semibold text-neutral-500">
                                <span>Sprint completion</span>
                                <span>68%</span>
                            </div>
                            <div class="mt-2 h-2 rounded-full bg-neutral-200">
                                <div class="h-2 w-[68%] rounded-full bg-sky-600"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="features" class="border-y border-neutral-200 bg-white/70 py-14 sm:py-16 lg:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="max-w-3xl">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-neutral-500 sm:text-sm">Workspace
                            capabilities</p>
                        <h2 class="mt-3 text-3xl font-bold leading-tight text-neutral-950 sm:text-4xl">Everything teams
                            need to manage Scrum work.</h2>
                    </div>

                    <div class="mt-8 grid gap-4 sm:mt-10 md:grid-cols-3">
                        <article class="rounded-lg border border-neutral-200 bg-white p-6">
                            <div
                                class="mb-5 grid size-11 place-items-center rounded-md border border-sky-200 bg-sky-50 text-lg font-bold text-sky-700">
                                01</div>
                            <h3 class="text-xl font-bold text-neutral-950">Project workspaces</h3>
                            <p class="mt-3 leading-7 text-neutral-600">Organize project keys, descriptions, owners,
                                members, and team access in one shared space.</p>
                        </article>

                        <article class="rounded-lg border border-neutral-200 bg-white p-6">
                            <div
                                class="mb-5 grid size-11 place-items-center rounded-md border border-amber-200 bg-amber-50 text-lg font-bold text-amber-700">
                                02</div>
                            <h3 class="text-xl font-bold text-neutral-950">Sprint planning</h3>
                            <p class="mt-3 leading-7 text-neutral-600">Move stories from backlog to sprint, track
                                capacity, and keep sprint goals visible.</p>
                        </article>

                        <article class="rounded-lg border border-neutral-200 bg-white p-6">
                            <div
                                class="mb-5 grid size-11 place-items-center rounded-md border border-emerald-200 bg-emerald-50 text-lg font-bold text-emerald-700">
                                03</div>
                            <h3 class="text-xl font-bold text-neutral-950">Issue discussions</h3>
                            <p class="mt-3 leading-7 text-neutral-600">Use comments, assignments, and activity history
                                to practice transparent collaboration.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="workflow" class="py-14 sm:py-16 lg:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-neutral-500 sm:text-sm">
                                Workflow preview</p>
                            <h2 class="mt-3 text-3xl font-bold leading-tight text-neutral-950 sm:text-4xl">From backlog
                                to done, the path stays visible.</h2>
                            <p class="mt-4 text-base leading-7 text-neutral-600">Teams can see where work sits, what is
                                blocked, and what needs attention before the sprint closes.</p>
                        </div>

                        <div class="-mx-4 overflow-x-auto px-4 pb-2 sm:mx-0 sm:px-0">
                            <div class="grid min-w-190 grid-cols-5 gap-3 lg:min-w-0">
                                @foreach (['Backlog', 'Selected', 'In Progress', 'Review', 'Done'] as $index => $status)
                                    <div class="rounded-lg border border-neutral-200 bg-white/75 p-3">
                                        <h3 class="mb-4 text-sm font-bold text-neutral-950">{{ $status }}</h3>
                                        <div class="space-y-3">
                                            <div class="rounded-md border border-neutral-200 bg-white p-3">
                                                <span @class([
                                                    'text-[10px] font-bold uppercase tracking-[0.14em]',
                                                    'text-purple-700' => $index === 0,
                                                    'text-amber-700' => $index === 1,
                                                    'text-sky-700' => $index === 2,
                                                    'text-indigo-700' => $index === 3,
                                                    'text-emerald-700' => $index === 4,
                                                ])>SCRUM-{{ $index + 1 }}</span>
                                                <p class="mt-2 text-sm font-semibold text-neutral-950">
                                                    {{ ['Write epic', 'Plan sprint', 'Build task', 'Peer review', 'Close story'][$index] }}
                                                </p>
                                            </div>
                                            @if ($index === 0 || $index === 2)
                                                <div class="rounded-md border border-neutral-200 bg-white p-3">
                                                    <span @class([
                                                        'text-[10px] font-bold uppercase tracking-[0.14em]',
                                                        'text-purple-700' => $index === 0,
                                                        'text-sky-700' => $index === 2,
                                                    ])>SCRUM-{{ $index + 7 }}</span>
                                                    <p class="mt-2 text-sm font-semibold text-neutral-950">
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
                class="border-y border-neutral-200 bg-stone-100 py-14 text-neutral-950 sm:py-16 lg:py-20">
                <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:gap-10 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:px-8">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-neutral-500 sm:text-sm">Team roles
                        </p>
                        <h2 class="mt-3 text-3xl font-bold leading-tight sm:text-4xl">Clear ownership for every project
                            role.</h2>
                        <p class="mt-4 leading-7 text-neutral-600">Role-aware workflows keep ownership, facilitation,
                            implementation, and visibility connected to the work.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ([['Project owner', 'Defines scope, priorities, and project outcomes.'], ['Scrum master', 'Protects the process and removes blockers.'], ['Developer', 'Ships issues and updates progress clearly.'], ['Viewer', 'Follows team progress and learns from the workflow.']] as [$role, $description])
                            <article @class([
                                'rounded-lg border bg-white/80 p-5',
                                'border-sky-200' => $loop->index === 0,
                                'border-amber-200' => $loop->index === 1,
                                'border-emerald-200' => $loop->index === 2,
                                'border-purple-200' => $loop->index === 3,
                            ])>
                                <span @class([
                                    'mb-4 inline-flex rounded-md px-2.5 py-1 text-xs font-bold',
                                    'bg-sky-100 text-sky-700' => $loop->index === 0,
                                    'bg-amber-100 text-amber-700' => $loop->index === 1,
                                    'bg-emerald-100 text-emerald-700' => $loop->index === 2,
                                    'bg-purple-100 text-purple-700' => $loop->index === 3,
                                ])>Role</span>
                                <h3 class="text-lg font-bold">{{ $role }}</h3>
                                <p class="mt-3 leading-7 text-neutral-600">{{ $description }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="px-4 py-14 sm:px-6 sm:py-16 lg:px-8 lg:py-20">
                <div
                    class="mx-auto flex max-w-7xl flex-col gap-6 rounded-lg border border-neutral-200 bg-white p-6 shadow-sm sm:p-8 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-neutral-500 sm:text-sm">Ready for
                            the next sprint</p>
                        <h2 class="mt-3 text-3xl font-bold text-neutral-950 sm:text-4xl lg:text-3xl">Bring projects,
                            sprints, issues, and teams into one workflow.</h2>
                    </div>
                    <a class="inline-flex items-center justify-center rounded-md bg-neutral-950 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-neutral-800"
                        href="{{ Route::has('register') ? route('register') : '#features' }}">
                        Start building
                    </a>
                </div>
            </section>
        </main>

        <footer class="border-t border-neutral-200 px-4 py-8 sm:px-6 lg:px-8">
            <div
                class="mx-auto flex max-w-7xl flex-col gap-3 text-sm text-neutral-500 sm:flex-row sm:items-center sm:justify-between">
                <p>Project planning, sprint tracking, and team collaboration in one workspace.</p>
                <p class="font-semibold text-neutral-950">{{ config('app.name', 'ScrumLab') }}</p>
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
