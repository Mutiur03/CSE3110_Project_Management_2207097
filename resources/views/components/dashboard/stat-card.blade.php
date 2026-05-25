@props([
    'label',
    'value',
    'tone' => 'bg-white text-neutral-950 border-neutral-200',
    'note' => null,
])

<article class="rounded-lg border p-5 shadow-sm {{ $tone }}">
    <p class="text-sm font-semibold">{{ $label }}</p>
    <p class="mt-3 text-3xl font-bold">{{ $value }}</p>
    @if ($note)
        <p class="mt-2 text-xs font-medium opacity-75">{{ $note }}</p>
    @endif
</article>
