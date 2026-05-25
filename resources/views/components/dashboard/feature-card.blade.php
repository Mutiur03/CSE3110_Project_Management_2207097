@props([
    'title',
    'description',
    'tone' => 'bg-stone-50 text-neutral-950 border-neutral-200',
])

<article class="rounded-lg border p-4 {{ $tone }}">
    <h3 class="text-sm font-bold">{{ $title }}</h3>
    <p class="mt-2 text-sm leading-6 opacity-75">{{ $description }}</p>
</article>
