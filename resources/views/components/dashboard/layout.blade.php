@props([
    'title' => 'Dashboard',
    'eyebrow' => 'Workspace',
    'currentProject' => null,
    'projects' => collect(),
])

@php
    $globalIssueMembers = $currentProject
        ? $currentProject->members()
            ->with(['teams' => fn ($query) => $query->where('project_id', $currentProject->id)])
            ->orderBy('name')
            ->get()
        : collect();
    $globalIssueTeams = $currentProject
        ? $currentProject->teams()->orderBy('name')->get()
        : collect();
    $globalParentIssues = $currentProject
        ? $currentProject->issues()->whereIn('type', ['epic', 'story'])->orderBy('key')->get()
        : collect();
@endphp

<x-layout>
    <x-slot:title>
        {{ $title }}
    </x-slot:title>

    <main class="min-h-screen bg-stone-50 font-sans text-neutral-950">
        <div class="flex min-h-screen">
            <x-dashboard.sidebar :current-project="$currentProject" :projects="$projects" />

            <div class="min-w-0 flex-1">
                <header class="sticky top-0 z-30 border-b border-neutral-200 bg-white/90 backdrop-blur">
                    <div class="grid items-center gap-4 px-4 py-3 sm:px-6 lg:grid-cols-[1fr_minmax(24rem,42rem)_1fr] lg:px-8">
                        <div class="flex min-w-0 items-center gap-4">
                            <button type="button" id="sidebar-toggle"
                                class="rounded-md border border-neutral-200 bg-white p-2 text-neutral-700 lg:hidden">
                                <span class="sr-only">Open sidebar</span>
                                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>

                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-neutral-500">{{ $eyebrow }}</p>
                                <h1 class="truncate text-lg font-bold text-neutral-950 sm:text-xl">{{ $title }}</h1>
                            </div>
                        </div>

                        <div class="hidden items-center gap-2 md:flex lg:justify-center">
                            <label class="relative min-w-0 flex-1">
                                <span class="sr-only">Search</span>
                                <svg class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" />
                                </svg>
                                <input type="search" placeholder="Search projects, teams, issues"
                                    class="w-full rounded-md border border-neutral-200 bg-stone-50 py-2 pl-9 pr-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                            </label>
                            @if ($currentProject)
                                <button type="button" data-modal-target="global-create-issue-modal"
                                    class="inline-flex shrink-0 items-center gap-2 rounded-md bg-neutral-950 px-3 py-2 text-sm font-semibold text-white transition hover:bg-neutral-800">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                                    </svg>
                                    Create
                                </button>
                            @else
                                <a href="{{ route('projects.create') }}" wire:navigate
                                    class="inline-flex shrink-0 items-center gap-2 rounded-md bg-neutral-950 px-3 py-2 text-sm font-semibold text-white transition hover:bg-neutral-800">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                                    </svg>
                                    Create
                                </a>
                            @endif
                        </div>

                        <div class="hidden lg:block"></div>
                    </div>
                </header>

                <section class="px-4 py-6 sm:px-6 lg:px-8">
                    {{ $slot }}
                </section>
            </div>
        </div>

        @if ($currentProject)
            <x-dashboard.modal id="global-create-issue-modal" title="Create backlog item" :open="old('_form') === 'global-create-issue'">
                <form method="POST" action="{{ route('projects.issues.store', $currentProject) }}">
                    @csrf
                    <input type="hidden" name="_form" value="global-create-issue">

                    @include('projects.issues.partials.form', [
                        'issue' => null,
                        'members' => $globalIssueMembers,
                        'teams' => $globalIssueTeams,
                        'parentIssues' => $globalParentIssues,
                        'submitLabel' => 'Create backlog item',
                        'cancelUrl' => route('projects.issues.index', $currentProject),
                        'modalCancel' => true,
                        'fieldPrefix' => 'global-issue',
                    ])
                </form>
            </x-dashboard.modal>
        @endif
    </main>

    <script>
        (() => {
            if (window.scrumlabDashboardReady) {
                return;
            }

            window.scrumlabDashboardReady = true;

            const closeProjectSwitchers = () => {
                document.querySelectorAll('[data-project-switcher-menu]').forEach(menu => {
                    menu.classList.add('hidden');
                });
                document.querySelectorAll('[data-project-switcher-icon]').forEach(icon => {
                    icon.classList.remove('rotate-180');
                });
            };

            const updateIssueForms = () => {
                document.querySelectorAll('[data-issue-form]').forEach(form => {
                    const type = form.querySelector('[data-issue-type]')?.value;
                    const teamId = form.querySelector('[data-issue-team]')?.value;
                    const assigneeInput = form.querySelector('[data-issue-assignee]');
                    const parentSelect = form.querySelector('[data-issue-parent]');
                    const parentField = form.querySelector('[data-issue-parent-field]');
                    const pointsField = form.querySelector('[data-issue-points-field]');
                    const bugField = form.querySelector('[data-issue-bug-field]');
                    const parentInput = parentField?.querySelector('select, input, textarea');
                    const pointsInput = pointsField?.querySelector('select, input, textarea');
                    const showParent = type === 'story' || type === 'task';
                    const showPoints = type === 'story' || type === 'task';
                    const showBugFields = type === 'bug';

                    parentField?.classList.toggle('hidden', ! showParent);
                    pointsField?.classList.toggle('hidden', ! showPoints);
                    bugField?.classList.toggle('hidden', ! showBugFields);

                    if (parentInput) {
                        parentInput.disabled = ! showParent;
                    }

                    if (pointsInput) {
                        pointsInput.disabled = ! showPoints;
                    }

                    bugField?.querySelectorAll('select, input, textarea').forEach(input => {
                        input.disabled = ! showBugFields;
                    });

                    if (parentSelect) {
                        let selectedParentIsAvailable = ! parentSelect.value;

                        parentSelect.querySelectorAll('option').forEach(option => {
                            if (! option.value) {
                                option.hidden = false;
                                option.disabled = false;
                                return;
                            }

                            const parentType = option.dataset.parentType;
                            const isAvailable = type === 'story'
                                ? parentType === 'epic'
                                : type === 'task'
                                    ? parentType === 'epic' || parentType === 'story'
                                    : false;

                            option.hidden = ! isAvailable;
                            option.disabled = ! isAvailable;

                            if (option.selected && isAvailable) {
                                selectedParentIsAvailable = true;
                            }
                        });

                        if (! selectedParentIsAvailable) {
                            parentSelect.value = '';
                        }
                    }

                    if (assigneeInput) {
                        let selectedAssigneeIsAvailable = ! assigneeInput.value;

                        assigneeInput.querySelectorAll('option').forEach(option => {
                            if (! option.value) {
                                option.hidden = false;
                                option.disabled = false;
                                return;
                            }

                            const teamIds = (option.dataset.teamIds || '').split(',').filter(Boolean);
                            const isAvailable = ! teamId || teamIds.includes(teamId);

                            option.hidden = ! isAvailable;
                            option.disabled = ! isAvailable;

                            if (option.selected && isAvailable) {
                                selectedAssigneeIsAvailable = true;
                            }
                        });

                        if (! selectedAssigneeIsAvailable) {
                            assigneeInput.value = '';
                        }
                    }
                });
            };

            updateIssueForms();
            document.addEventListener('livewire:navigated', updateIssueForms);

            document.addEventListener('click', event => {
                const modalTrigger = event.target.closest('[data-modal-target]');

                if (modalTrigger) {
                    event.preventDefault();
                    document.getElementById(modalTrigger.dataset.modalTarget)?.classList.remove('hidden');
                    return;
                }

                if (event.target.matches('[data-modal]') || event.target.closest('[data-modal-close]')) {
                    event.target.closest('[data-modal]')?.classList.add('hidden');
                    return;
                }

                const sidebarToggle = event.target.closest('#sidebar-toggle');

                if (sidebarToggle) {
                    document.getElementById('dashboard-sidebar')?.classList.toggle('hidden');
                    return;
                }

                const switcherButton = event.target.closest('[data-project-switcher-button]');

                if (switcherButton) {
                    const switcher = switcherButton.closest('[data-project-switcher]');
                    const menu = switcher?.querySelector('[data-project-switcher-menu]');
                    const icon = switcher?.querySelector('[data-project-switcher-icon]');

                    menu?.classList.toggle('hidden');
                    icon?.classList.toggle('rotate-180');
                    return;
                }

                if (! event.target.closest('[data-project-switcher]')) {
                    closeProjectSwitchers();
                }
            });

            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') {
                    closeProjectSwitchers();
                    document.querySelectorAll('[data-modal]').forEach(modal => modal.classList.add('hidden'));
                }
            });

            document.addEventListener('change', event => {
                if (event.target.matches('[data-issue-type], [data-issue-team]')) {
                    updateIssueForms();
                }
            });
        })();
    </script>
</x-layout>
