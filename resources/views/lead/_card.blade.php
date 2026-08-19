@php
    $badge = $sourceBadgeColors[$lead->source] ?? $defaultBadgeColor;
    $tagColor = $statusTagColors[$lead->status] ?? ['bg' => '#EDEFF1', 'color' => '#5A6B7E'];
    $tagLabel = $statusTagLabels[$lead->status] ?? ucfirst($lead->status);

    $dueLabel = null;
    $dueColor = ['bg' => '#FBF0DC', 'color' => '#8A5A10'];
    if ($lead->follow_up_due_at) {
        $diffDays = (int) now()->startOfDay()->diffInDays($lead->follow_up_due_at->copy()->startOfDay(), false);
        if ($diffDays < 0) {
            $dueLabel = 'Overdue '.abs($diffDays).'d';
            $dueColor = ['bg' => '#F9E4E2', 'color' => '#B3261E'];
        } elseif ($diffDays === 0) {
            $dueLabel = 'Due today';
        } else {
            $dueLabel = 'Due in '.$diffDays.'d';
        }
    }

    $ownerName = $lead->owner ? trim($lead->owner->first_name.' '.$lead->owner->last_name) : null;
@endphp

<div class="lead-card" data-id="{{ $lead->id }}" data-status="{{ $lead->status }}" onclick="openActionPanel({{ $lead->id }})">
    <div class="lead-card-top">
        <div class="lead-card-name js-name">{{ $lead->child_name ?? 'N/A' }} · {{ $lead->child_age ?? 'N/A' }}</div>
        <span class="lead-card-source-badge js-source" style="background: {{ $badge['bg'] }}; color: {{ $badge['color'] }};">{{ $lead->source ?? 'N/A' }}</span>
    </div>
    <div style="display: flex; align-items: center; gap: 7px;">
        <div class="lead-card-tags js-tags">
            <span class="lead-card-tag" style="background: {{ $tagColor['bg'] }}; color: {{ $tagColor['color'] }};">{{ $tagLabel }}</span>
            @if ($dueLabel)
                <span class="lead-card-tag" style="background: {{ $dueColor['bg'] }}; color: {{ $dueColor['color'] }};">{{ $dueLabel }}</span>
            @endif
        </div>
        <div class="lead-card-parent js-parent">{{ $lead->parent_guardian_name ?? 'N/A' }}</div>
    </div>
    <div class="lead-card-notes js-notes">{{ $lead->notes ?? 'No notes' }}</div>
    <div class="lead-card-bottom">
        <div class="lead-card-value js-value">AED {{ number_format($lead->estimated_value_numeric ?? 0, 0) }}/mo</div>
        <div class="lead-card-time js-time">{{ $lead->created_at->diffForHumans() }}</div>
        <div class="lead-card-actions">
            <button class="btn-view" onclick="event.stopPropagation(); viewLead({{ $lead->id }})" title="View details">👁</button>
        </div>
    </div>
    <div class="lead-card-owner js-owner {{ $ownerName ? '' : 'is-unassigned' }}">
        {{ $ownerName ? 'Assigned to '.$ownerName : 'Unassigned — open to assign' }}
    </div>
</div>
