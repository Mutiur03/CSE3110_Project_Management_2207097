@props([
    'id',
    'title',
    'open' => false,
])

<div id="{{ $id }}" data-modal @class([
    'fixed inset-0 z-50 overflow-y-auto bg-neutral-950/40 px-4 py-6',
    'hidden' => ! $open,
])>
    <div class="mx-auto min-h-full w-full max-w-xl content-center">
        <section class="rounded-lg border border-neutral-200 bg-white shadow-xl">
            <div class="flex items-center justify-between gap-4 border-b border-neutral-200 px-5 py-4">
                <h2 class="text-base font-bold text-neutral-950">{{ $title }}</h2>
                <button type="button" data-modal-close
                    class="grid size-8 place-items-center rounded-md text-muted-foreground transition hover:bg-muted hover:text-ink">
                    <span class="sr-only">Close</span>
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-5">
                {{ $slot }}
            </div>
        </section>
    </div>
</div>
