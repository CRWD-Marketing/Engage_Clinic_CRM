@php
    $posting = $posting ?? null;
    $requirementsList = old('requirements', $posting->requirements ?? []);
    if (empty($requirementsList)) {
        $requirementsList = [''];
    }
@endphp

<div class="jp-form-section-title mb-4">Position Details</div>

<div class="mb-6">
    <label class="jp-form-label">Job Title<span class="required">*</span></label>
    <input type="text" name="title" value="{{ old('title', $posting->title ?? '') }}"
           class="jp-input h-9 px-3 text-sm @error('title') is-invalid @enderror"
           placeholder="e.g. Board Certified Behavior Analyst (BCBA)">
    @error('title') <p class="jp-field-error">{{ $message }}</p> @enderror
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div>
        <label class="jp-form-label">Employment Type</label>
        <input type="text" name="employment_type" value="{{ old('employment_type', $posting->employment_type ?? '') }}"
               class="jp-input h-9 px-3 text-sm @error('employment_type') is-invalid @enderror"
               placeholder="e.g. Full-time">
        @error('employment_type') <p class="jp-field-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="jp-form-label">Location</label>
        <input type="text" name="location" value="{{ old('location', $posting->location ?? '') }}"
               class="jp-input h-9 px-3 text-sm @error('location') is-invalid @enderror"
               placeholder="e.g. Abu Dhabi">
        @error('location') <p class="jp-field-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="jp-form-label">Status<span class="required">*</span></label>
        <select name="status" class="jp-select h-9 px-3 text-sm @error('status') is-invalid @enderror">
            @php $currentStatus = old('status', $posting->status ?? 'active'); @endphp
            <option value="active" {{ $currentStatus === 'active' ? 'selected' : '' }}>Active — visible</option>
            <option value="inactive" {{ $currentStatus === 'inactive' ? 'selected' : '' }}>Inactive — hidden</option>
        </select>
        @error('status') <p class="jp-field-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mb-6">
    <label class="jp-form-label">Description</label>
    <textarea name="description" rows="4" class="jp-textarea px-3 py-2 text-sm @error('description') is-invalid @enderror"
              placeholder="Summarize the role and responsibilities...">{{ old('description', $posting->description ?? '') }}</textarea>
    @error('description') <p class="jp-field-error">{{ $message }}</p> @enderror
</div>

<div class="mb-6">
    <label class="jp-form-label">Requirements</label>
    <div id="jp-requirements-list">
        @foreach ($requirementsList as $requirement)
            <div class="jp-req-row">
                <input type="text" name="requirements[]" value="{{ $requirement }}" class="jp-input h-9 px-3 text-sm" placeholder="e.g. Active BCBA certification">
                <button type="button" class="jp-req-remove" title="Remove"><i class="fas fa-xmark"></i></button>
            </div>
        @endforeach
    </div>
    <div id="jp-req-add" class="jp-add-req">+ Add requirement</div>
    @error('requirements') <p class="jp-field-error">{{ $message }}</p> @enderror
    @error('requirements.*') <p class="jp-field-error">{{ $message }}</p> @enderror
</div>

<!-- Requirement row template -->
<template id="jp-req-template">
    <div class="jp-req-row">
        <input type="text" name="requirements[]" class="jp-input h-9 px-3 text-sm" placeholder="e.g. Active BCBA certification">
        <button type="button" class="jp-req-remove" title="Remove"><i class="fas fa-xmark"></i></button>
    </div>
</template>

<style>
    .jp-req-row { display: flex; gap: 8px; align-items: center; margin-bottom: 8px; }
    .jp-req-row .jp-input { flex: 1; height: 38px; padding: 0 12px; }
    .jp-req-remove {
        background: #FDF1F3; border: none; border-radius: 8px; color: #A82348; cursor: pointer;
        width: 38px; height: 38px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
    }
    .jp-req-remove:hover { background: #F9DEE4; }
    .jp-add-req {
        background: #FFFFFF; border: 1px dashed #D9CDBD; border-radius: 8px; padding: 10px; text-align: center;
        font: 800 12px 'Nunito Sans'; color: #C8355F; cursor: pointer; margin-top: 4px;
    }
    .jp-add-req:hover { background: #FBF3E4; }
</style>

<script>
(function () {
    const list = document.getElementById('jp-requirements-list');
    const template = document.getElementById('jp-req-template');
    const addBtn = document.getElementById('jp-req-add');

    function updateRemoveVisibility() {
        const rows = list.querySelectorAll('.jp-req-row');
        rows.forEach(row => {
            row.querySelector('.jp-req-remove').style.visibility = rows.length > 1 ? 'visible' : 'hidden';
        });
    }

    addBtn.addEventListener('click', function () {
        const node = template.content.firstElementChild.cloneNode(true);
        list.appendChild(node);
        updateRemoveVisibility();
        node.querySelector('input').focus();
    });

    list.addEventListener('click', function (e) {
        if (e.target.closest('.jp-req-remove')) {
            const rows = list.querySelectorAll('.jp-req-row');
            if (rows.length > 1) {
                e.target.closest('.jp-req-row').remove();
                updateRemoveVisibility();
            }
        }
    });

    updateRemoveVisibility();
})();
</script>
