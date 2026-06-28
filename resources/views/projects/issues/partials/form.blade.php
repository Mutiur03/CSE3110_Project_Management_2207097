@php
    use App\Support\BadgeTones;

    $issueType = old('type', $issue?->type ?? 'task');
    $issueStatus = old('status', $issue?->status ?? 'backlog');
    $issuePriority = old('priority', $issue?->priority ?? 'medium');
    $issueSeverity = old('severity', $issue?->severity ?? 'major');
    $fieldPrefix = $fieldPrefix ?? 'issue';
@endphp

<div class="grid gap-5" data-issue-form>
    <div>
        <label for="{{ $fieldPrefix }}-title" class="block text-sm font-semibold text-ink">Title <span class="text-red-600">*</span></label>
        <input id="{{ $fieldPrefix }}-title" name="title" type="text" value="{{ old('title', $issue?->title) }}" required
            class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
        @error('title')
            <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $fieldPrefix }}-description" class="block text-sm font-semibold text-ink">Description</label>
        <textarea id="{{ $fieldPrefix }}-description" name="description" rows="4"
            class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">{{ old('description', $issue?->description) }}</textarea>
        @error('description')
            <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-5 md:grid-cols-3">
        <div>
            <label for="{{ $fieldPrefix }}-type" class="block text-sm font-semibold text-ink">Type <span class="text-red-600">*</span></label>
            <select id="{{ $fieldPrefix }}-type" name="type" required data-issue-type
                class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                @foreach (['epic' => 'Epic', 'story' => 'Story', 'task' => 'Task', 'subtask' => 'Subtask', 'bug' => 'Bug'] as $value => $label)
                    <option value="{{ $value }}" @selected($issueType === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="{{ $fieldPrefix }}-status" class="block text-sm font-semibold text-ink">Status <span class="text-red-600">*</span></label>
            <select id="{{ $fieldPrefix }}-status" name="status" required
                class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                @foreach (['backlog' => 'Backlog', 'selected' => 'Selected', 'in_progress' => 'In Progress', 'review' => 'Review', 'done' => 'Done'] as $value => $label)
                    <option value="{{ $value }}" @selected($issueStatus === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="{{ $fieldPrefix }}-priority" class="block text-sm font-semibold text-ink">Priority <span class="text-red-600">*</span></label>
            <select id="{{ $fieldPrefix }}-priority" name="priority" required
                class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'] as $value => $label)
                    <option value="{{ $value }}" @selected($issuePriority === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid gap-5 md:grid-cols-4">
        <div>
            <label for="{{ $fieldPrefix }}-assignee-id" class="block text-sm font-semibold text-ink">Assignee</label>
            <select id="{{ $fieldPrefix }}-assignee-id" name="assignee_id" data-issue-assignee
                class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                <option value="">Unassigned</option>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}" data-team-ids="{{ $member->team_ids ?? '' }}" @selected(old('assignee_id', $issue?->assignee_id) === $member->id)>{{ $member->name }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-neutral-500">When a team is selected, only that team's members can be assigned.</p>
            @error('assignee_id')
                <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="{{ $fieldPrefix }}-team-id" class="block text-sm font-semibold text-ink">Team</label>
            <select id="{{ $fieldPrefix }}-team-id" name="team_id" data-issue-team
                class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
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
            <label for="{{ $fieldPrefix }}-parent-issue-id" class="block text-sm font-semibold text-ink">Parent <span class="text-red-600">*</span></label>
            <select id="{{ $fieldPrefix }}-parent-issue-id" name="parent_issue_id" data-issue-parent
                class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                <option value="">No parent</option>
                @foreach ($parentIssues as $parentIssue)
                    <option value="{{ $parentIssue->id }}" data-parent-type="{{ $parentIssue->type }}" @selected(old('parent_issue_id', $issue?->parent_issue_id) === $parentIssue->id)>
                        {{ $parentIssue->key }} {{ $parentIssue->title }}
                    </option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-neutral-500">Stories sit under epics. Subtasks sit under stories or tasks.</p>
            @error('parent_issue_id')
                <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div data-issue-points-field>
            <label for="{{ $fieldPrefix }}-story-points" class="block text-sm font-semibold text-ink">Points <span class="text-red-600">*</span></label>
            <input id="{{ $fieldPrefix }}-story-points" name="story_points" type="number" min="1" max="100" value="{{ old('story_points', $issue?->story_points) }}"
                class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
            <p class="mt-2 text-xs text-neutral-500">Useful for stories and standalone tasks during sprint planning.</p>
        </div>
    </div>

    <div class="rounded-lg border border-hairline bg-canvas p-4" data-issue-bug-field>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-ink">Bug details</h3>
            </div>
            <x-ui.badge :tone="BadgeTones::NEUTRAL">Bug</x-ui.badge>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label for="{{ $fieldPrefix }}-severity" class="block text-sm font-semibold text-ink">Severity <span class="text-red-600">*</span></label>
                <select id="{{ $fieldPrefix }}-severity" name="severity"
                    class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                    @foreach (['minor' => 'Minor', 'major' => 'Major', 'critical' => 'Critical', 'blocker' => 'Blocker'] as $value => $label)
                        <option value="{{ $value }}" @selected($issueSeverity === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('severity')
                    <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="{{ $fieldPrefix }}-environment" class="block text-sm font-semibold text-ink">Environment <span class="text-red-600">*</span></label>
                <input id="{{ $fieldPrefix }}-environment" name="environment" type="text" value="{{ old('environment', $issue?->environment) }}"
                    placeholder="Chrome, Windows, staging"
                    class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                @error('environment')
                    <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-4">
            <label for="{{ $fieldPrefix }}-steps-to-reproduce" class="block text-sm font-semibold text-ink">Steps to reproduce <span class="text-red-600">*</span></label>
            <textarea id="{{ $fieldPrefix }}-steps-to-reproduce" name="steps_to_reproduce" rows="3"
                class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">{{ old('steps_to_reproduce', $issue?->steps_to_reproduce) }}</textarea>
            @error('steps_to_reproduce')
                <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label for="{{ $fieldPrefix }}-expected-result" class="block text-sm font-semibold text-ink">Expected result <span class="text-red-600">*</span></label>
                <textarea id="{{ $fieldPrefix }}-expected-result" name="expected_result" rows="3"
                    class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">{{ old('expected_result', $issue?->expected_result) }}</textarea>
                @error('expected_result')
                    <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="{{ $fieldPrefix }}-actual-result" class="block text-sm font-semibold text-ink">Actual result <span class="text-red-600">*</span></label>
                <textarea id="{{ $fieldPrefix }}-actual-result" name="actual_result" rows="3"
                    class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-3 text-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">{{ old('actual_result', $issue?->actual_result) }}</textarea>
                @error('actual_result')
                    <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
    @if (! empty($modalCancel))
        <button type="button" data-modal-close
            class="inline-flex justify-center rounded-md border border-hairline bg-white px-4 py-3 text-sm font-semibold text-ink transition hover:border-ink">
            Cancel
        </button>
    @else
        <a href="{{ $cancelUrl }}" wire:navigate
            class="inline-flex justify-center rounded-md border border-hairline bg-white px-4 py-3 text-sm font-semibold text-ink transition hover:border-ink">
            Cancel
        </a>
    @endif
    <button type="submit"
        class="inline-flex justify-center rounded-md bg-accent px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong">
        {{ $submitLabel }}
    </button>
</div>
