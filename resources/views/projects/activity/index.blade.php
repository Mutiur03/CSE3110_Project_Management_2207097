@php
    use App\Support\BadgeTones;

    $actionLabel = fn (?string $action): string => ucwords(str_replace('_', ' ', (string) $action));
@endphp

<x-dashboard.layout title="Activity" :eyebrow="$currentProject->name" :current-project="$currentProject" :projects="$projects">
    <section class="rounded-lg border border-hairline bg-white p-4 sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.key-badge :label="$currentProject->key" />
                    <x-ui.badge :tone="BadgeTones::NEUTRAL">Project history</x-ui.badge>
                </div>
                <h2 class="mt-2 font-display text-xl font-bold tracking-tight text-ink sm:text-2xl">Activity timeline</h2>
                <p class="mt-1 text-sm text-neutral-500">Status changes, issue updates, and team actions for this project.</p>
            </div>
            <p class="shrink-0 font-mono text-xs tabular-nums text-neutral-400">
                {{ number_format($activities->total()) }} {{ Str::plural('event', $activities->total()) }}
            </p>
        </div>
    </section>

    <section class="mt-4 overflow-hidden rounded-lg border border-hairline bg-white">
        @forelse ($activities as $activity)
            <article class="border-b border-hairline px-4 py-3.5 last:border-b-0 sm:px-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                    <div class="flex min-w-0 gap-3">
                        <span class="mt-2 size-1.5 shrink-0 rounded-full bg-accent" aria-hidden="true"></span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.badge :tone="BadgeTones::NEUTRAL">{{ $actionLabel($activity->action) }}</x-ui.badge>
                                @if ($activity->issue_id && $activity->issue_key)
                                    <a href="{{ route('projects.issues.show', [$currentProject->id, $activity->issue_id]) }}" wire:navigate
                                        class="font-mono text-xs text-accent transition-colors hover:text-accent-strong"
                                        translate="no">
                                        {{ $activity->issue_key }}
                                    </a>
                                @endif
                            </div>
                            <p class="mt-1.5 text-sm font-semibold text-ink">{{ $activity->user_name ?? 'System' }}</p>
                            @if ($activity->new_values || $activity->old_values)
                                <p class="mt-1 line-clamp-2 text-sm leading-5 text-neutral-500">
                                    {{ collect($activity->new_values ?? $activity->old_values)->map(fn ($value, $key) => str_replace('_', ' ', $key) . ': ' . (is_scalar($value) ? $value : json_encode($value)))->join(' · ') }}
                                </p>
                            @endif
                        </div>
                    </div>
                    <time class="shrink-0 pl-4 font-mono text-xs text-neutral-400 sm:pl-0" datetime="{{ $activity->created_at }}">
                        {{ $activity->created_at_human }}
                    </time>
                </div>
            </article>
        @empty
            <div class="px-4 py-10 text-center sm:px-5">
                <p class="text-sm font-medium text-ink">No activity yet</p>
                <p class="mt-1 text-sm text-neutral-500">Updates will show here when the team creates issues, changes status, or plans sprints.</p>
            </div>
        @endforelse
    </section>

    @if ($activities->hasPages())
        <div class="mt-4">
            {{ $activities->links() }}
        </div>
    @endif
</x-dashboard.layout>
