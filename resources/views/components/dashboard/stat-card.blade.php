@props([
    'label',
    'value',
    'note' => null,
])

<article class="rounded-lg border border-hairline bg-white p-5">
    <p class="deck-label text-muted-foreground">{{ $label }}</p>
    <p class="mt-2 font-display text-2xl font-bold tabular-nums leading-none text-ink">{{ $value }}</p>
    @if ($note)
        <p class="mt-2 line-clamp-2 text-xs font-medium text-muted-foreground">{{ $note }}</p>
    @endif
</article>
