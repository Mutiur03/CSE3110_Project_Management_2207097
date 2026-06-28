@props([
    'title',
    'description',
    'tone' => '',
])

<article class="rounded-lg border border-hairline bg-white p-5">
    <h3 class="font-display text-base font-semibold text-ink">{{ $title }}</h3>
    <p class="mt-2 text-sm leading-6 text-neutral-500">{{ $description }}</p>
</article>
