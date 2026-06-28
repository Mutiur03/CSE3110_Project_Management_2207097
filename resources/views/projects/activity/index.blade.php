@php
    use App\Support\BadgeTones;
@endphp

<x-dashboard.layout title="Activity" :eyebrow="$currentProject->name" :current-project="$currentProject" :projects="$projects">
    <section class="rounded-lg border border-hairline bg-white p-5">
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.key-badge :label="$currentProject->key" />
            <x-ui.badge :tone="BadgeTones::NEUTRAL">Project history</x-ui.badge>
        </div>
        <h2 class="mt-3 font-display text-2xl font-bold tracking-tight text-ink">Activity timeline</h2>
    </section>

    <section class="mt-6 overflow-hidden rounded-lg border border-hairline bg-white">
        @forelse ($activities as $activity)
            <article class="border-b border-hairline p-5 last:border-b-0">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex min-w-0 gap-3">
                        <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-accent"></span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.badge :tone="BadgeTones::NEUTRAL">{{ $activity->action }}</x-ui.badge>
                                @if ($activity->issue_id && $activity->issue_key)
                                    <a href="{{ route('projects.issues.show', [$currentProject->id, $activity->issue_id]) }}" wire:navigate
                                        class="font-mono text-xs text-accent transition hover:text-accent-strong">
                                        {{ $activity->issue_key }}
                                    </a>
                                @endif
                            </div>
                            <p class="mt-2 text-sm font-semibold text-ink">{{ $activity->user_name ?? 'System' }}</p>
                            @if ($activity->new_values || $activity->old_values)
                                <p class="mt-2 line-clamp-2 text-sm leading-6 text-neutral-500">
                                    {{ collect($activity->new_values ?? $activity->old_values)->map(fn ($value, $key) => str_replace('_', ' ', $key) . ': ' . (is_scalar($value) ? $value : json_encode($value)))->join(' | ') }}
                                </p>
                            @endif
                        </div>
                    </div>
                    <time class="shrink-0 font-mono text-xs text-neutral-400" datetime="{{ $activity->created_at }}">
                        {{ $activity->created_at_human }}
                    </time>
                </div>
            </article>
        @empty
            <div class="p-6 text-sm text-neutral-500">No activity yet.</div>
        @endforelse
    </section>

    <div class="mt-6">
        {{ $activities->links() }}
    </div>
</x-dashboard.layout>
