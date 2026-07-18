@props([
    'title' => 'Dashboard',
    'eyebrow' => 'Workspace',
    'currentProject' => null,
    'projects' => collect(),
])

@php
    use App\Support\SqlDialect;
    use Illuminate\Support\Facades\DB;

    $globalIssueMembers = collect();
    $globalIssueTeams = collect();
    $globalParentIssues = collect();
    $canWriteProject = false;

    if ($currentProject) {
        $canWriteProject = (bool) ($currentProject->can_write ?? false);

        $globalIssueMembers = collect(DB::select(
            'SELECT u.id, u.name, u.email,
                    '.SqlDialect::groupConcat('t.id').'
             FROM users u
             INNER JOIN project_members pm ON pm.user_id = u.id
             LEFT JOIN team_members tm ON tm.user_id = u.id
             LEFT JOIN teams t ON t.id = tm.team_id AND t.project_id = ?
             WHERE pm.project_id = ?
             GROUP BY u.id, u.name, u.email
             ORDER BY u.name',
            [$currentProject->id, $currentProject->id],
        ));

        $globalIssueTeams = collect(DB::select(
            'SELECT id, name FROM teams WHERE project_id = ? ORDER BY name',
            [$currentProject->id],
        ));

        $globalParentIssues = collect(DB::select(
            "SELECT id, key, title, type FROM issues
             WHERE project_id = ? AND type IN ('epic', 'story', 'task')
             ORDER BY key",
            [$currentProject->id],
        ));
    }
@endphp

<x-layout>
    <x-slot:title>
        {{ $title }}
    </x-slot:title>

    <main id="main-content" class="min-h-screen bg-canvas font-sans text-ink">
        <div class="flex min-h-screen">
            <x-dashboard.sidebar :current-project="$currentProject" :projects="$projects" />

            <div class="min-w-0 flex-1">
                <header class="sticky top-0 z-30 border-b border-hairline bg-canvas/85 backdrop-blur">
                    <div class="grid items-center gap-4 px-4 py-3 sm:px-6 lg:grid-cols-[1fr_minmax(20rem,38rem)_1fr] lg:px-8">
                        <div class="flex min-w-0 items-center gap-3">
                            <button type="button" id="sidebar-toggle" aria-label="Open sidebar"
                                class="rounded-md border border-hairline bg-white p-2 text-neutral-700 transition-colors hover:border-ink lg:hidden">
                                <span class="sr-only">Open sidebar</span>
                                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>

                            <div class="min-w-0">
                                @if ($currentProject)
                                    <p class="deck-label text-neutral-400" translate="no">{{ $currentProject->key }}</p>
                                @endif
                                <h1 class="truncate font-display text-lg font-bold tracking-tight text-ink sm:text-xl">{{ $title }}</h1>
                            </div>
                        </div>

                        <div class="hidden items-center gap-2 md:flex lg:justify-center">
                            <label class="relative min-w-0 flex-1">
                                <span class="sr-only">Search</span>
                                <svg class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" />
                                </svg>
                                <input type="search" name="q" autocomplete="off" placeholder="Search projects, teams, issues…"
                                    class="w-full rounded-md border border-hairline bg-white py-2 pl-9 pr-3 text-sm transition-colors focus:border-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent/30">
                            </label>
                            @if ($currentProject && $canWriteProject)
                                <button type="button" data-modal-target="global-create-issue-modal"
                                    class="inline-flex shrink-0 items-center gap-2 rounded-md bg-accent px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-accent-strong">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2.2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                                    </svg>
                                    Create
                                </button>
                            @else
                                <a href="{{ route('projects.create') }}" wire:navigate
                                    class="inline-flex shrink-0 items-center gap-2 rounded-md bg-accent px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-accent-strong">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2.2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                                    </svg>
                                    Create
                                </a>
                            @endif
                        </div>

                        <div class="hidden justify-end lg:flex">
                            <livewire:notification-bell />
                        </div>
                    </div>
                </header>

                <section class="px-4 py-6 sm:px-6 lg:px-8">
                    {{ $slot }}
                </section>
            </div>
        </div>

        @if ($currentProject && $canWriteProject)
            <x-dashboard.modal id="global-create-issue-modal" title="Create backlog item" :open="old('_form') === 'global-create-issue'">
                <form method="POST" action="{{ route('projects.issues.store', $currentProject->id) }}">
                    @csrf
                    <input type="hidden" name="_form" value="global-create-issue">

                    @include('projects.issues.partials.form', [
                        'issue' => null,
                        'members' => $globalIssueMembers,
                        'teams' => $globalIssueTeams,
                        'parentIssues' => $globalParentIssues,
                        'submitLabel' => 'Create backlog item',
                        'cancelUrl' => route('projects.issues.index', $currentProject->id),
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

            const closeNotificationPanels = () => {
                document.querySelectorAll('[data-notification-panel]').forEach(panel => {
                    panel.classList.add('hidden');
                });
            };

            const startRealtimeNotifications = () => {
                const menu = document.querySelector('[data-notification-menu]');

                if (! menu) {
                    return;
                }

                const userId = menu.dataset.notificationsUserId;

                const refreshNotifications = () => {
                    window.Livewire?.dispatch('refresh-notifications');
                };

                if (window.scrumlabNotificationsReady) {
                    return;
                }

                window.scrumlabNotificationsReady = true;
                refreshNotifications();

                if (window.Echo && userId) {
                    window.Echo.private(`App.Models.User.${userId}`)
                        .listen('.project.notification.pushed', () => {
                            refreshNotifications();
                        });
                }

                setInterval(() => {
                    if (document.visibilityState === 'visible') {
                        refreshNotifications();
                    }
                }, 10000);

                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') {
                        refreshNotifications();
                    }
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
                    const showParent = type === 'story' || type === 'subtask';
                    const showPoints = type === 'story' || type === 'task';
                    const showBugFields = type === 'bug';
                    const requiresParent = type === 'story' || type === 'subtask';
                    const requiresPoints = type === 'story' || type === 'task';

                    parentField?.classList.toggle('hidden', ! showParent);
                    pointsField?.classList.toggle('hidden', ! showPoints);
                    bugField?.classList.toggle('hidden', ! showBugFields);

                    if (parentInput) {
                        parentInput.disabled = ! showParent;
                        parentInput.required = requiresParent;
                    }

                    if (pointsInput) {
                        pointsInput.disabled = ! showPoints;
                        pointsInput.required = requiresPoints;
                    }

                    bugField?.querySelectorAll('select, input, textarea').forEach(input => {
                        input.disabled = ! showBugFields;
                        input.required = showBugFields;
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
                                : type === 'subtask'
                                    ? parentType === 'story' || parentType === 'task'
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

            const initBacklogFolding = () => {
                const toggles = document.querySelectorAll('.fold-toggle');
                if (toggles.length === 0) return;

                const collapsedIssues = new Set();

                // Initialize all parent issues as collapsed by default
                toggles.forEach(toggle => {
                    const issueId = toggle.dataset.toggleFor;
                    collapsedIssues.add(issueId);

                    const svg = toggle.querySelector('svg');
                    if (svg) {
                        svg.style.transform = 'rotate(-90deg)';
                    }
                });

                const updateVisibility = () => {
                    document.querySelectorAll('[data-backlog-row]').forEach(row => {
                        let parentId = row.dataset.parentId;
                        let shouldHide = false;
                        while (parentId) {
                            if (collapsedIssues.has(parentId)) {
                                shouldHide = true;
                                break;
                            }
                            const parentRow = document.querySelector(`[data-issue-id="${parentId}"]`);
                            parentId = parentRow ? parentRow.dataset.parentId : null;
                        }
                        row.classList.toggle('hidden', shouldHide);
                    });
                };

                // Run visibility update immediately to apply initial collapsed state
                updateVisibility();

                toggles.forEach(toggle => {
                    if (toggle.dataset.bound) return;
                    toggle.dataset.bound = 'true';

                    toggle.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();

                        const issueId = toggle.dataset.toggleFor;
                        const svg = toggle.querySelector('svg');

                        if (collapsedIssues.has(issueId)) {
                            // Expand
                            collapsedIssues.delete(issueId);
                            if (svg) {
                                svg.style.transform = 'rotate(0deg)';
                            }
                        } else {
                            // Collapse
                            collapsedIssues.add(issueId);
                            if (svg) {
                                svg.style.transform = 'rotate(-90deg)';
                            }
                        }

                        updateVisibility();
                    });
                });
            };

            updateIssueForms();
            initBacklogFolding();
            startRealtimeNotifications();
            document.addEventListener('livewire:navigated', () => {
                updateIssueForms();
                initBacklogFolding();
            });

            let draggedBoardCard = null;

            document.addEventListener('dragstart', event => {
                const card = event.target.closest('[data-board-card]');

                if (! card) {
                    return;
                }

                draggedBoardCard = card;
                card.classList.add('opacity-60');
                event.dataTransfer.effectAllowed = 'move';
            });

            document.addEventListener('dragend', event => {
                event.target.closest('[data-board-card]')?.classList.remove('opacity-60');
                document.querySelectorAll('[data-board-column]').forEach(column => {
                    column.classList.remove('border-neutral-950', 'bg-white');
                });
                draggedBoardCard = null;
            });

            document.addEventListener('dragover', event => {
                const column = event.target.closest('[data-board-column]');

                if (! column || ! draggedBoardCard) {
                    return;
                }

                event.preventDefault();
                column.classList.add('border-neutral-950', 'bg-white');
                event.dataTransfer.dropEffect = 'move';
            });

            document.addEventListener('dragleave', event => {
                const column = event.target.closest('[data-board-column]');

                if (column && ! column.contains(event.relatedTarget)) {
                    column.classList.remove('border-neutral-950', 'bg-white');
                }
            });

            document.addEventListener('drop', event => {
                const column = event.target.closest('[data-board-column]');

                if (! column || ! draggedBoardCard) {
                    return;
                }

                event.preventDefault();
                const nextStatus = column.dataset.boardColumn;
                const currentStatus = draggedBoardCard.dataset.currentStatus;

                if (! nextStatus || nextStatus === currentStatus) {
                    return;
                }

                const form = draggedBoardCard.querySelector('[data-board-drop-form]');
                const statusInput = draggedBoardCard.querySelector('[data-board-status-input]');

                if (form && statusInput) {
                    statusInput.value = nextStatus;
                    form.submit();
                }
            });

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

                const notificationButton = event.target.closest('[data-notification-button]');

                if (notificationButton) {
                    notificationButton.closest('[data-notification-menu]')?.querySelector('[data-notification-panel]')?.classList.toggle('hidden');
                    return;
                }

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

                if (! event.target.closest('[data-notification-menu]')) {
                    closeNotificationPanels();
                }
            });

            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') {
                    closeProjectSwitchers();
                    closeNotificationPanels();
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
