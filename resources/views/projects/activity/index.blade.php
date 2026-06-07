<x-dashboard.layout title="Activity" :eyebrow="$currentProject->name" :current-project="$currentProject" :projects="$projects">
    <section class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-md bg-neutral-950 px-2.5 py-1 text-xs font-bold text-white">{{ $currentProject->key }}</span>
            <span class="rounded-md bg-sky-100 px-2.5 py-1 text-xs font-bold text-sky-700">Project history</span>
        </div>
        <h2 class="mt-3 text-2xl font-bold tracking-normal text-neutral-950">Activity timeline</h2>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-neutral-600">
            Track important project, sprint, issue, board, and comment changes in one place.
        </p>
    </section>

    <section class="mt-6 overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-sm">
        @forelse ($activities as $activity)
            <article class="border-b border-neutral-100 p-5 last:border-b-0">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-md bg-neutral-100 px-2.5 py-1 text-xs font-bold text-neutral-700">{{ $activity->action }}</span>
                            @if ($activity->issue)
                                <a href="{{ route('projects.issues.show', [$currentProject, $activity->issue]) }}" wire:navigate
                                    class="text-xs font-bold text-blue-600 underline-offset-4 hover:underline">
                                    {{ $activity->issue->key }}
                                </a>
                            @endif
                        </div>
                        <p class="mt-2 text-sm font-semibold text-neutral-950">{{ $activity->user?->name ?? 'System' }}</p>
                        @if ($activity->new_values || $activity->old_values)
                            <p class="mt-2 line-clamp-2 text-sm leading-6 text-neutral-600">
                                {{ collect($activity->new_values ?? $activity->old_values)->map(fn ($value, $key) => str_replace('_', ' ', $key) . ': ' . (is_scalar($value) ? $value : json_encode($value)))->join(' | ') }}
                            </p>
                        @endif
                    </div>
                    <time class="shrink-0 text-xs font-semibold text-neutral-500" datetime="{{ $activity->created_at?->toISOString() }}">
                        {{ $activity->created_at?->diffForHumans() }}
                    </time>
                </div>
            </article>
        @empty
            <div class="p-6 text-sm text-neutral-500">No activity recorded yet.</div>
        @endforelse
    </section>

    <div class="mt-6">
        {{ $activities->links() }}
    </div>
</x-dashboard.layout>
