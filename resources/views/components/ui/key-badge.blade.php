@props([
    'label',
])

<span {{ $attributes->class([
    'inline-flex items-center rounded bg-ink px-2 py-0.5 font-mono text-xs font-semibold text-white',
]) }}>{{ $label }}</span>
