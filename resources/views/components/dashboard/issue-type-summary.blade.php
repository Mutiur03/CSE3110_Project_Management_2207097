@props([
    'typeLabels',
    'backlogCounts',
    'projectId',
])

<section {{ $attributes->class(['rounded-lg border border-hairline bg-white p-4 sm:p-5']) }}>
    <div class="mb-3 flex items-center justify-between gap-3">
        <h2 class="deck-label text-muted-foreground">Issues by type</h2>
        <a href="{{ route('projects.issues.index', $projectId) }}" wire:navigate
            class="shrink-0 text-sm font-semibold text-accent transition hover:text-accent-strong">
            View backlog
        </a>
    </div>

    <div class="grid grid-cols-2 gap-2 sm:grid-cols-5 sm:gap-0 sm:overflow-hidden sm:rounded-md sm:border sm:border-hairline">
        @foreach ($typeLabels as $type => $label)
            <div @class([
                'flex flex-col items-center justify-center rounded-md bg-canvas px-3 py-2.5 text-center',
                'sm:rounded-none sm:border-l sm:border-hairline sm:bg-white sm:py-3 sm:first:border-l-0',
            ])>
                <p class="deck-label text-muted-foreground">{{ $label }}</p>
                <p class="mt-1 font-display text-lg font-bold tabular-nums leading-none text-ink">
                    {{ $backlogCounts->get($type, 0) }}
                </p>
            </div>
        @endforeach
    </div>
</section>
