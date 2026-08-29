<div class="kb-row">
    <div class="kb-field" style="flex: 2;">
        <label>Title</label>
        <input type="text" name="title" value="{{ old('title', $entry->title ?? '') }}" required maxlength="255">
    </div>
    <div class="kb-field">
        <label>Category</label>
        <input type="text" name="category" value="{{ old('category', $entry->category ?? '') }}" maxlength="100" placeholder="e.g. Pricing, Services, Hours">
    </div>
</div>

<div class="kb-field">
    <label>Content</label>
    <textarea name="content" rows="4" required>{{ old('content', $entry->content ?? '') }}</textarea>
</div>

<div class="kb-row">
    <div class="kb-field">
        <label>Priority (1 = highest)</label>
        <input type="number" name="priority" min="1" max="10" value="{{ old('priority', $entry->priority ?? 5) }}" required>
    </div>
    <div class="kb-field">
        <label>Status</label>
        <select name="status" required>
            @foreach (\App\Models\KnowledgeBaseEntry::statusLabels() as $value => $label)
                <option value="{{ $value }}" {{ old('status', $entry->status ?? 'active') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
