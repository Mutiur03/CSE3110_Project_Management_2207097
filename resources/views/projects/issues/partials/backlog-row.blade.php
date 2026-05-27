@props([
    'issue',
    'currentProject',
    'typeTones',
    'priorityTones',
    'statusLabels',
    'indent' => 0,
])

@php
    $indentClass = [
        0 => '',
        1 => 'pl-8',
        2 => 'pl-14',
    ][$indent] ?? 'pl-14';

    $lineClass = [
        0 => '',
        1 => 'before:absolute before:left-3 before:top-0 before:h-full before:border-l before:border-neutral-300 after:absolute after:left-3 after:top-1/2 after:w-4 after:border-t after:border-neutral-300',
        2 => 'before:absolute before:left-8 before:top-0 before:h-full before:border-l before:border-neutral-300 after:absolute after:left-8 after:top-1/2 after:w-4 after:border-t after:border-neutral-300',
    ][$indent] ?? 'before:absolute before:left-8 before:top-0 before:h-full before:border-l before:border-neutral-300 after:absolute after:left-8 after:top-1/2 after:w-4 after:border-t after:border-neutral-300';

    $typeIcons = [
        'epic' => 'M12 3l7 7-7 11-7-11 7-7z',
        'story' => 'M6 4h12v16l-6-3-6 3V4z',
        'task' => 'M8 6h10M8 12h10M8 18h10M4 6h.01M4 12h.01M4 18h.01',
        'subtask' => 'M7 7h5v5H7V7zm5 5h5v5h-5v-5z',
        'bug' => 'M9 9h6m-7 4h8m-4-9v3m-5 1-2-2m14 0-2 2m-10 8-2 2m14 0-2-2M8 8a4 4 0 0 1 8 0v7a4 4 0 0 1-8 0V8z',
    ];

    $initials = collect(explode(' ', $issue->reporter?->name ?? 'User'))
        ->filter()
        ->take(2)
        ->map(fn ($part) => substr($part, 0, 1))
        ->implode('');
@endphp

<div class="group grid grid-cols-[minmax(24rem,1fr)_13rem_13rem_9rem_8rem] border-b border-neutral-100 text-sm text-neutral-700 transition hover:bg-stone-50">
    <div class="relative min-w-0 border-r border-neutral-100 px-3 py-2.5 {{ $indentClass }} {{ $lineClass }}">
        <div class="flex min-w-0 items-center gap-2">
            <svg class="size-4 shrink-0 {{ $typeTones[$issue->type] ?? 'text-neutral-500' }}" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $typeIcons[$issue->type] ?? $typeIcons['task'] }}" />
            </svg>

            <a href="{{ route('projects.issues.show', [$currentProject, $issue]) }}" wire:navigate
                class="shrink-0 font-semibold text-blue-600 underline-offset-4 hover:underline">
                {{ $issue->key }}
            </a>

            <a href="{{ route('projects.issues.show', [$currentProject, $issue]) }}" wire:navigate
                class="min-w-0 flex-1 truncate font-semibold text-neutral-950">
                {{ $issue->title }}
            </a>

            @if ($issue->story_points)
                <span class="shrink-0 rounded bg-neutral-100 px-1.5 py-0.5 text-[10px] font-bold text-neutral-600">{{ $issue->story_points }}</span>
            @endif
        </div>
    </div>

    <div class="flex min-w-0 items-center gap-2 border-r border-neutral-100 px-3 py-2.5">
        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-neutral-500">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0zM4.5 20.25a7.5 7.5 0 0 1 15 0" />
            </svg>
        </span>
        <span class="truncate font-medium">{{ $issue->assignee?->name ?? 'Unassigned' }}</span>
    </div>

    <div class="flex min-w-0 items-center gap-2 border-r border-neutral-100 px-3 py-2.5">
        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">
            {{ strtoupper($initials) }}
        </span>
        <span class="truncate font-medium">{{ $issue->reporter?->name ?? 'Unknown' }}</span>
    </div>

    <div class="flex items-center gap-2 border-r border-neutral-100 px-3 py-2.5">
        <svg class="size-4 {{ $priorityTones[$issue->priority] ?? 'text-neutral-500' }}" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 12h14M5 16h14" />
        </svg>
        <span class="font-medium capitalize">{{ $issue->priority }}</span>
    </div>

    <div class="flex items-center px-3 py-2.5">
        <span class="rounded-md border border-neutral-200 bg-neutral-50 px-2 py-1 text-xs font-bold uppercase text-neutral-700">
            {{ $statusLabels[$issue->status] ?? $issue->status }}
        </span>
    </div>
</div>
