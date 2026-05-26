@php
    $issueType = old('type', $issue?->type ?? 'task');
    $issueStatus = old('status', $issue?->status ?? 'backlog');
    $issuePriority = old('priority', $issue?->priority ?? 'medium');
@endphp

<div class="grid gap-5" data-issue-form>
    <div>
        <label for="title" class="block text-sm font-semibold text-neutral-950">Title</label>
        <input id="title" name="title" type="text" value="{{ old('title', $issue?->title) }}" required
            class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
        @error('title')
            <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-semibold text-neutral-950">Description</label>
        <textarea id="description" name="description" rows="4"
            class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">{{ old('description', $issue?->description) }}</textarea>
        @error('description')
            <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-5 md:grid-cols-3">
        <div>
            <label for="type" class="block text-sm font-semibold text-neutral-950">Type</label>
            <select id="type" name="type" required data-issue-type
                class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                @foreach (['epic' => 'Epic', 'story' => 'Story', 'task' => 'Task', 'bug' => 'Bug'] as $value => $label)
                    <option value="{{ $value }}" @selected($issueType === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="status" class="block text-sm font-semibold text-neutral-950">Status</label>
            <select id="status" name="status" required
                class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                @foreach (['backlog' => 'Backlog', 'selected' => 'Selected', 'in_progress' => 'In Progress', 'review' => 'Review', 'done' => 'Done'] as $value => $label)
                    <option value="{{ $value }}" @selected($issueStatus === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="priority" class="block text-sm font-semibold text-neutral-950">Priority</label>
            <select id="priority" name="priority" required
                class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'] as $value => $label)
                    <option value="{{ $value }}" @selected($issuePriority === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid gap-5 md:grid-cols-4">
        <div>
            <label for="assignee_id" class="block text-sm font-semibold text-neutral-950">Assignee</label>
            <select id="assignee_id" name="assignee_id" data-issue-assignee
                class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                <option value="">Unassigned</option>
                @foreach ($members as $member)
                    @php
                        $memberTeamIds = $member->teams
                            ->where('project_id', $currentProject->id)
                            ->pluck('id')
                            ->implode(',');
                    @endphp
                    <option value="{{ $member->id }}" data-team-ids="{{ $memberTeamIds }}" @selected(old('assignee_id', $issue?->assignee_id) === $member->id)>{{ $member->name }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-neutral-500">When a team is selected, only that team's members can be assigned.</p>
            @error('assignee_id')
                <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="team_id" class="block text-sm font-semibold text-neutral-950">Team</label>
            <select id="team_id" name="team_id" data-issue-team
                class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                <option value="">No team</option>
                @foreach ($teams as $team)
                    <option value="{{ $team->id }}" @selected(old('team_id', $issue?->team_id) === $team->id)>{{ $team->name }}</option>
                @endforeach
            </select>
            @error('team_id')
                <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div data-issue-parent-field>
            <label for="parent_issue_id" class="block text-sm font-semibold text-neutral-950">Parent</label>
            <select id="parent_issue_id" name="parent_issue_id"
                class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                <option value="">No parent</option>
                @foreach ($parentIssues as $parentIssue)
                    <option value="{{ $parentIssue->id }}" @selected(old('parent_issue_id', $issue?->parent_issue_id) === $parentIssue->id)>
                        {{ $parentIssue->key }} {{ $parentIssue->title }}
                    </option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-neutral-500">Stories can sit under epics. Tasks can sit under stories or epics.</p>
        </div>

        <div data-issue-points-field>
            <label for="story_points" class="block text-sm font-semibold text-neutral-950">Points</label>
            <input id="story_points" name="story_points" type="number" min="1" max="100" value="{{ old('story_points', $issue?->story_points) }}"
                class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
            <p class="mt-2 text-xs text-neutral-500">Useful for stories and tasks during sprint planning.</p>
        </div>
    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
    @if (! empty($modalCancel))
        <button type="button" data-modal-close
            class="inline-flex justify-center rounded-md border border-neutral-200 bg-white px-4 py-3 text-sm font-semibold text-neutral-950 transition hover:border-neutral-950">
            Cancel
        </button>
    @else
        <a href="{{ $cancelUrl }}" wire:navigate
            class="inline-flex justify-center rounded-md border border-neutral-200 bg-white px-4 py-3 text-sm font-semibold text-neutral-950 transition hover:border-neutral-950">
            Cancel
        </a>
    @endif
    <button type="submit"
        class="inline-flex justify-center rounded-md bg-neutral-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
        {{ $submitLabel }}
    </button>
</div>
