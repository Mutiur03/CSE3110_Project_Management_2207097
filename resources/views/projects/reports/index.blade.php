@php
    use App\Support\BadgeTones;

    $th = 'px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-neutral-400';
    $td = 'px-3 py-2 text-sm text-ink tabular-nums';
    $tdText = 'px-3 py-2 text-sm text-ink';
@endphp

<x-dashboard.layout title="Reports" :eyebrow="$currentProject->name" :current-project="$currentProject" :projects="$projects">
    <section class="rounded-lg border border-hairline bg-white p-4">
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.key-badge :label="$currentProject->key" />
            <x-ui.badge :tone="BadgeTones::NEUTRAL">Reports</x-ui.badge>
        </div>
        <h2 class="mt-2 font-display text-xl font-bold tracking-tight text-ink">Project reports</h2>
        <p class="mt-0.5 text-sm text-neutral-500">See who is shipping, where work piles up, and how the sprint is trending.</p>
    </section>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">

        <section class="rounded-lg border border-hairline bg-white p-4" aria-labelledby="report-leaderboard">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 id="report-leaderboard" class="min-w-0 font-display text-lg font-bold text-ink">Contributor leaderboard</h3>
                <x-ui.badge :tone="BadgeTones::ACCENT">RANK() OVER</x-ui.badge>
            </div>
            <p class="mt-1 text-sm text-neutral-500">Members ranked by completed work.</p>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full">
                    <caption class="sr-only">Contributor leaderboard by completed issues</caption>
                    <thead><tr>
                        <th scope="col" class="{{ $th }}">#</th>
                        <th scope="col" class="{{ $th }}">Member</th>
                        <th scope="col" class="{{ $th }}">Done</th>
                        <th scope="col" class="{{ $th }}">Assigned</th>
                        <th scope="col" class="{{ $th }}">Points</th>
                    </tr></thead>
                    <tbody class="divide-y divide-hairline">
                        @forelse ($leaderboard as $row)
                            <tr>
                                <td class="{{ $td }} font-mono text-neutral-400">{{ $row->rnk }}</td>
                                <td class="{{ $tdText }} min-w-0 max-w-[12rem] truncate font-semibold">{{ $row->name }}</td>
                                <td class="{{ $td }}">{{ $row->done }}</td>
                                <td class="{{ $td }} text-neutral-500">{{ $row->assigned }}</td>
                                <td class="{{ $td }} text-neutral-500">{{ $row->points }}</td>
                            </tr>
                        @empty
                            <tr><td class="{{ $tdText }} text-neutral-500" colspan="5">Add members and complete issues to build a leaderboard.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-lg border border-hairline bg-white p-4" aria-labelledby="report-busy-teams">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 id="report-busy-teams" class="min-w-0 font-display text-lg font-bold text-ink">Teams with open work</h3>
                <x-ui.badge :tone="BadgeTones::ACCENT">HAVING</x-ui.badge>
            </div>
            <p class="mt-1 text-sm text-neutral-500">Teams that still carry unfinished issues.</p>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full">
                    <caption class="sr-only">Teams with open issues</caption>
                    <thead><tr>
                        <th scope="col" class="{{ $th }}">Team</th>
                        <th scope="col" class="{{ $th }}">Open issues</th>
                    </tr></thead>
                    <tbody class="divide-y divide-hairline">
                        @forelse ($busyTeams as $row)
                            <tr>
                                <td class="{{ $tdText }} min-w-0 truncate font-semibold">{{ $row->name }}</td>
                                <td class="{{ $td }}">{{ $row->open_issues }}</td>
                            </tr>
                        @empty
                            <tr><td class="{{ $tdText }} text-neutral-500" colspan="2">No teams carry open issues.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-lg border border-hairline bg-white p-4" aria-labelledby="report-on-team">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 id="report-on-team" class="min-w-0 font-display text-lg font-bold text-ink">On a team</h3>
                <x-ui.badge :tone="BadgeTones::ACCENT">INTERSECT</x-ui.badge>
            </div>
            <p class="mt-1 text-sm text-neutral-500">Project members who also sit on a team.</p>
            <ul class="mt-3 space-y-1.5">
                @forelse ($onboarded as $row)
                    <li class="min-w-0 truncate text-sm font-medium text-ink">{{ $row->name }}</li>
                @empty
                    <li class="text-sm text-neutral-500">Assign members to a team to list them here.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-lg border border-hairline bg-white p-4" aria-labelledby="report-no-team">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 id="report-no-team" class="min-w-0 font-display text-lg font-bold text-ink">On no team</h3>
                <x-ui.badge :tone="BadgeTones::ACCENT">MINUS</x-ui.badge>
            </div>
            <p class="mt-1 text-sm text-neutral-500">Members who still need a team assignment.</p>
            <ul class="mt-3 space-y-1.5">
                @forelse ($unassigned as $row)
                    <li class="min-w-0 truncate text-sm font-medium text-ink">{{ $row->name }}</li>
                @empty
                    <li class="text-sm text-neutral-500">Every member is on a team.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-lg border border-hairline bg-white p-4" aria-labelledby="report-my-work">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 id="report-my-work" class="min-w-0 font-display text-lg font-bold text-ink">My work</h3>
                <x-ui.badge :tone="BadgeTones::ACCENT">UNION</x-ui.badge>
            </div>
            <p class="mt-1 text-sm text-neutral-500">Issues you own or reported.</p>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full">
                    <caption class="sr-only">Issues assigned to or reported by you</caption>
                    <thead><tr>
                        <th scope="col" class="{{ $th }}">Key</th>
                        <th scope="col" class="{{ $th }}">Title</th>
                        <th scope="col" class="{{ $th }}">Status</th>
                        <th scope="col" class="{{ $th }}">Role</th>
                    </tr></thead>
                    <tbody class="divide-y divide-hairline">
                        @forelse ($myWork as $row)
                            <tr>
                                <td class="{{ $td }} font-mono text-xs text-accent" translate="no">{{ $row->key }}</td>
                                <td class="{{ $tdText }} max-w-xs truncate">{{ $row->title }}</td>
                                <td class="{{ $tdText }} text-neutral-500">{{ str_replace('_', ' ', $row->status) }}</td>
                                <td class="{{ $tdText }}"><x-ui.badge :tone="BadgeTones::NEUTRAL">{{ $row->role }}</x-ui.badge></td>
                            </tr>
                        @empty
                            <tr><td class="{{ $tdText }} text-neutral-500" colspan="4">Nothing assigned to or reported by you yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-lg border border-hairline bg-white p-4 lg:col-span-2" aria-labelledby="report-velocity">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 id="report-velocity" class="min-w-0 font-display text-lg font-bold text-ink">Cumulative velocity by sprint</h3>
                <x-ui.badge :tone="BadgeTones::ACCENT">SUM() OVER</x-ui.badge>
            </div>
            <p class="mt-1 text-sm text-neutral-500">Points completed each sprint, plus the running total.</p>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full">
                    <caption class="sr-only">Sprint velocity and cumulative points</caption>
                    <thead><tr>
                        <th scope="col" class="{{ $th }}">Sprint</th>
                        <th scope="col" class="{{ $th }}">Points done</th>
                        <th scope="col" class="{{ $th }}">Cumulative</th>
                    </tr></thead>
                    <tbody class="divide-y divide-hairline">
                        @forelse ($velocity as $row)
                            <tr>
                                <td class="{{ $tdText }} min-w-0 truncate font-semibold">{{ $row->name }}</td>
                                <td class="{{ $td }}">{{ $row->points }}</td>
                                <td class="{{ $td }} font-mono text-neutral-500">{{ $row->cumulative }}</td>
                            </tr>
                        @empty
                            <tr><td class="{{ $tdText }} text-neutral-500" colspan="3">Complete sprint work to see velocity.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-lg border border-hairline bg-white p-4 lg:col-span-2" aria-labelledby="report-timeline">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 id="report-timeline" class="min-w-0 font-display text-lg font-bold text-ink">Unified timeline</h3>
                <x-ui.badge :tone="BadgeTones::ACCENT">UNION ALL</x-ui.badge>
            </div>
            <p class="mt-1 text-sm text-neutral-500">Recent comments, status changes, and project activity.</p>
            <ul class="mt-3 divide-y divide-hairline">
                @forelse ($timeline as $row)
                    <li class="flex items-center gap-3 py-2.5">
                        <x-ui.badge :tone="BadgeTones::NEUTRAL">{{ $row->kind }}</x-ui.badge>
                        @if ($row->issue_key)
                            <span class="shrink-0 font-mono text-xs text-accent" translate="no">{{ $row->issue_key }}</span>
                        @endif
                        <span class="min-w-0 flex-1 truncate text-sm text-ink">{{ $row->detail }}</span>
                        <span class="shrink-0 text-xs text-neutral-400">{{ $row->actor ?? 'System' }}</span>
                    </li>
                @empty
                    <li class="py-2.5 text-sm text-neutral-500">Activity will appear as the team works.</li>
                @endforelse
            </ul>
        </section>

    </div>
</x-dashboard.layout>
