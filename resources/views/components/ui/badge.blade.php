@props([
    'tone' => \App\Support\BadgeTones::NEUTRAL,
])

<span {{ $attributes->class([
    'inline-flex items-center rounded-md px-2 py-0.5 font-mono text-[10px] font-semibold uppercase tracking-wide',
    $tone,
]) }}>{{ $slot }}</span>
