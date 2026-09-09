<?php // resources/views/knowledge_base/index.blade.php ?>
@extends('layouts.admin-sidebar')

@section('title', 'AI Employee · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width')

@section('content')
<style>
    .kb-wrap { display: flex; flex-direction: column; gap: 20px; padding: 4px 4px 28px; width: 100%; box-sizing: border-box; }

    /* Page header */
    .kb-header { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
    .kb-header-icon {
        width: 46px; height: 46px; border-radius: 13px; background: linear-gradient(145deg, #16436E, #24619C);
        display: flex; align-items: center; justify-content: center; font-size: 21px; flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(22,67,110,0.25);
    }
    .kb-header-title { font: 600 22px 'Baloo 2'; color: #16436E; line-height: 1.2; }
    .kb-header-sub { font: 600 12.5px 'Nunito Sans'; color: #98897A; margin-top: 2px; }
    .kb-header-status {
        margin-left: auto; display: flex; align-items: center; gap: 7px; padding: 7px 14px; border-radius: 20px;
        font: 800 11.5px 'Nunito Sans'; white-space: nowrap;
    }
    .kb-header-status.is-on { background: #E4F6EB; color: #1E8A4C; }
    .kb-header-status.is-off { background: #F3EDE3; color: #7A6E60; }
    .kb-header-status .kb-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .kb-header-status.is-on .kb-dot { box-shadow: 0 0 0 3px rgba(31,168,85,0.18); }

    .kb-status-flash { background: #E4F6EB; color: #1E8A4C; border: 1px solid #C9E9D4; border-radius: 10px; padding: 10px 16px; font: 700 12.5px 'Nunito Sans'; display: flex; align-items: center; gap: 8px; }

    /* Cards */
    .kb-card { background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 16px; padding: 22px 24px; display: flex; flex-direction: column; gap: 16px; box-shadow: 0 1px 3px rgba(43,58,76,0.05); }
    .kb-card-title-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .kb-card-title { font: 600 17px 'Baloo 2'; color: #16436E; display: flex; align-items: center; gap: 8px; }
    .kb-card-sub { font: 600 12px/1.5 'Nunito Sans'; color: #98897A; max-width: 640px; }
    .kb-count-pill { background: #F6F3EE; color: #5A6B7E; border-radius: 20px; padding: 3px 11px; font: 800 11px 'Nunito Sans'; white-space: nowrap; }

    /* Form fields */
    .kb-field { display: flex; flex-direction: column; gap: 6px; }
    .kb-field label { font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.5px; }
    .kb-field input[type=text], .kb-field input[type=number], .kb-field select, .kb-field textarea {
        padding: 10px 13px; border: 1px solid #E2DACE; border-radius: 10px; background: #F6F3EE;
        font: 600 13px 'Nunito Sans'; color: #2B3A4C; outline: none; width: 100%; box-sizing: border-box;
        transition: border-color 0.12s ease, background 0.12s ease;
    }
    .kb-field input:focus, .kb-field select:focus, .kb-field textarea:focus { border-color: #B7C7D6; background: #FFFDFA; }
    .kb-field textarea { resize: vertical; min-height: 70px; font-family: 'Nunito Sans', sans-serif; line-height: 1.5; }
    .kb-field-hint { font: 600 11px 'Nunito Sans'; color: #B0A493; }

    .kb-row { display: flex; gap: 14px; flex-wrap: wrap; }
    .kb-row > .kb-field { flex: 1; min-width: 170px; }
    .kb-divider { border: none; border-top: 1px solid #F0E9DE; margin: 2px 0; }

    /* Toggle switch */
    .kb-toggle-row { display: flex; align-items: center; gap: 13px; background: #F9F6F0; border: 1px solid #EFE7DA; border-radius: 12px; padding: 13px 16px; }
    .kb-switch { position: relative; width: 42px; height: 24px; flex-shrink: 0; }
    .kb-switch input { position: absolute; opacity: 0; width: 100%; height: 100%; margin: 0; cursor: pointer; }
    .kb-switch-track { position: absolute; inset: 0; background: #D8CFC0; border-radius: 999px; transition: background 0.15s ease; }
    .kb-switch-thumb { position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 50%; background: #FFFFFF; box-shadow: 0 1px 3px rgba(0,0,0,0.25); transition: transform 0.15s ease; }
    .kb-switch input:checked + .kb-switch-track { background: #1FA855; }
    .kb-switch input:checked + .kb-switch-track + .kb-switch-thumb { transform: translateX(18px); }
    .kb-toggle-label { font: 800 13px 'Nunito Sans'; color: #2B3A4C; }
    .kb-toggle-sub { font: 600 11.5px 'Nunito Sans'; color: #98897A; margin-top: 1px; }

    /* Buttons */
    .kb-btn {
        display: inline-flex; align-items: center; gap: 7px; background: #16436E; color: #fff; border: none; border-radius: 10px;
        padding: 10px 18px; font: 800 12.5px 'Nunito Sans'; cursor: pointer; align-self: flex-start;
        transition: background 0.15s ease, transform 0.05s ease;
    }
    .kb-btn:hover { background: #123657; }
    .kb-btn:active { transform: translateY(1px); }
    .kb-btn-secondary { background: #F6F3EE; color: #2B3A4C; box-shadow: none; }
    .kb-btn-secondary:hover { background: #EFE8DD; }
    .kb-btn-danger { background: none; color: #C8355F; padding: 6px 0; }
    .kb-btn-danger:hover { text-decoration: underline; background: none; }
    .kb-btn-sm { padding: 6px 12px; font-size: 11px; border-radius: 8px; }

    /* Table */
    .kb-table-wrap { overflow-x: auto; }
    .kb-table { width: 100%; border-collapse: collapse; min-width: 720px; }
    .kb-table th { text-align: left; font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.5px; padding: 9px 10px; border-bottom: 1px solid #EBE4DA; }
    .kb-table tbody tr.kb-row-main { transition: background-color 0.1s ease; }
    .kb-table tbody tr.kb-row-main:hover { background-color: #FBF8F3; }
    .kb-table td { padding: 12px 10px; border-bottom: 1px solid #F3EDE3; font: 600 13px 'Nunito Sans'; color: #2B3A4C; vertical-align: top; }
    .kb-entry-title { font: 800 13px 'Nunito Sans'; color: #2B3A4C; }
    .kb-chip { display: inline-block; border-radius: 7px; padding: 2.5px 9px; font: 800 10.5px 'Nunito Sans'; white-space: nowrap; }
    .kb-priority-dot { display: inline-flex; align-items: center; gap: 6px; font: 700 12px 'Nunito Sans'; color: #5A6B7E; }
    .kb-priority-dot span.dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .kb-content-preview { color: #5A6B7E; max-width: 380px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5; }

    .kb-entry-form { display: none; background: #F9F6F0; border: 1px solid #EFE7DA; border-radius: 12px; padding: 16px; margin-top: 8px; flex-direction: column; gap: 13px; }
    .kb-entry-form.is-open { display: flex; }
    .kb-entry-form-actions { display: flex; align-items: center; gap: 16px; }

    .kb-empty-state { text-align: center; color: #98897A; padding: 44px 20px; font: 600 12.5px/1.6 'Nunito Sans'; }
</style>

<div class="kb-wrap">

    <!-- Page header -->
    <div class="kb-header">
        <div class="kb-header-icon">🤖</div>
        <div>
            <div class="kb-header-title">AI Employee</div>
            <div class="kb-header-sub">Manage what your AI Employee knows, and how it behaves across WhatsApp, Instagram, and Facebook.</div>
        </div>
        <span class="kb-header-status {{ $settings->is_enabled ? 'is-on' : 'is-off' }}">
            <span class="kb-dot"></span>{{ $settings->is_enabled ? 'Live - auto-replying' : 'Off' }}
        </span>
    </div>

    @if (session('status'))
        <div class="kb-status-flash">✓ {{ session('status') }}</div>
    @endif

    <!-- Global AI Employee settings -->
    <div class="kb-card">
        <div>
            <div class="kb-card-title">⚙️ Settings</div>
            <div class="kb-card-sub">Controls whether the AI auto-responds at all, and how it's tuned. The kill switch starts off on purpose - only enable it once you've confirmed a queue worker is running and you've reviewed the knowledge base below.</div>
        </div>
        <form action="{{ route('knowledge_base.updateSettings') }}" method="POST" style="display: flex; flex-direction: column; gap: 16px;">
            @csrf
            @method('PUT')

            <div class="kb-toggle-row">
                <label class="kb-switch">
                    <input type="checkbox" name="is_enabled" value="1" onchange="this.form.querySelector('.kb-toggle-label').textContent = this.checked ? 'AI Employee is ON' : 'AI Employee is OFF';" {{ $settings->is_enabled ? 'checked' : '' }}>
                    <span class="kb-switch-track"></span>
                    <span class="kb-switch-thumb"></span>
                </label>
                <div>
                    <div class="kb-toggle-label">AI Employee is {{ $settings->is_enabled ? 'ON' : 'OFF' }}</div>
                    <div class="kb-toggle-sub">Auto-reply to WhatsApp, Instagram, and Facebook messages</div>
                </div>
            </div>

            <hr class="kb-divider">

            <div class="kb-row">
                <div class="kb-field">
                    <label for="model_name">Model override</label>
                    <input type="text" id="model_name" name="model_name" value="{{ $settings->model_name }}" placeholder="{{ config('ai_employee.llm.model') }}">
                    <span class="kb-field-hint">Optional - leave blank to use the default model</span>
                </div>
                <div class="kb-field">
                    <label for="max_history_messages">Max history messages</label>
                    <input type="number" id="max_history_messages" name="max_history_messages" min="1" max="50" value="{{ $settings->max_history_messages }}" required>
                    <span class="kb-field-hint">How many prior messages give it context</span>
                </div>
                <div class="kb-field">
                    <label for="max_kb_entries">Max knowledge entries / reply</label>
                    <input type="number" id="max_kb_entries" name="max_kb_entries" min="1" max="20" value="{{ $settings->max_kb_entries }}" required>
                    <span class="kb-field-hint">How many knowledge entries it can cite at once</span>
                </div>
                <div class="kb-field">
                    <label for="response_delay_seconds">AI response interval (seconds)</label>
                    <input type="number" id="response_delay_seconds" name="response_delay_seconds" min="0" max="600" value="{{ $settings->response_delay_seconds }}" required>
                    <span class="kb-field-hint">How long it waits before responding to each new message. Only honored exactly with a real queue worker/cron running - without one, it's capped at 15s to avoid the request timing out.</span>
                </div>
            </div>

            <div class="kb-field">
                <label for="system_prompt_override">System prompt override</label>
                <textarea id="system_prompt_override" name="system_prompt_override" rows="4" placeholder="Leave blank to use the built-in default prompt">{{ $settings->system_prompt_override }}</textarea>
                <span class="kb-field-hint">Optional - fully replaces the built-in prompt when set</span>
            </div>

            <hr class="kb-divider">

            <div class="kb-toggle-row">
                <label class="kb-switch">
                    <input type="checkbox" name="voice_enabled" value="1" onchange="this.form.querySelector('.kb-toggle-label-voice').textContent = this.checked ? 'Voice calling is ON' : 'Voice calling is OFF';" {{ $settings->voice_enabled ? 'checked' : '' }}>
                    <span class="kb-switch-track"></span>
                    <span class="kb-switch-thumb"></span>
                </label>
                <div>
                    <div class="kb-toggle-label kb-toggle-label-voice">Voice calling is {{ $settings->voice_enabled ? 'ON' : 'OFF' }}</div>
                    <div class="kb-toggle-sub">Separate switch from chat - answer inbound phone calls with a live AI conversation</div>
                </div>
            </div>

            <div class="kb-row">
                <div class="kb-field">
                    <label for="voice_greeting">Voice greeting</label>
                    <textarea id="voice_greeting" name="voice_greeting" rows="2" placeholder="Thanks for calling. How can I help you today?">{{ $settings->voice_greeting }}</textarea>
                    <span class="kb-field-hint">Spoken automatically when the AI answers a call</span>
                </div>
                <div class="kb-field">
                    <label for="human_handoff_phone_number">Human handoff number</label>
                    <input type="text" id="human_handoff_phone_number" name="human_handoff_phone_number" value="{{ $settings->human_handoff_phone_number }}" placeholder="+971501234567">
                    <span class="kb-field-hint">Live calls transfer here on escalation. Leave blank to end the call with a follow-up message instead of transferring.</span>
                </div>
            </div>

            <button type="submit" class="kb-btn">💾 Save settings</button>
        </form>
    </div>

    <!-- Knowledge base entries -->
    <div class="kb-card">
        <div class="kb-card-title-row">
            <div>
                <div class="kb-card-title">
                    📚 Knowledge base
                    <span class="kb-count-pill">{{ $entries->count() }} total · {{ $entries->where('status', 'active')->count() }} active</span>
                </div>
                <div class="kb-card-sub">Only active entries are used by the AI. It answers strictly from what's written here - never invent pricing, policies, or clinical claims that aren't included below.</div>
            </div>
            <button type="button" class="kb-btn" onclick="document.getElementById('kb-new-form').classList.toggle('is-open')">+ Add entry</button>
        </div>

        <div id="kb-new-form" class="kb-entry-form">
            <form action="{{ route('knowledge_base.store') }}" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
                @csrf
                @include('knowledge_base.partials.entry_fields', ['entry' => null])
                <button type="submit" class="kb-btn">Create entry</button>
            </form>
        </div>

        <div class="kb-table-wrap">
            <table class="kb-table">
                <thead>
                    <tr>
                        <th style="width: 24%;">Title</th>
                        <th style="width: 14%;">Category</th>
                        <th>Content</th>
                        <th style="width: 90px;">Priority</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 90px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $statusColors = [
                            'active' => ['bg' => '#E4F6EB', 'fg' => '#1E8A4C'],
                            'draft' => ['bg' => '#FDF3D6', 'fg' => '#96751B'],
                            'archived' => ['bg' => '#EFEBE3', 'fg' => '#7A6E60'],
                        ];
                        $categoryPalette = ['#2563AE', '#C8355F', '#1F8FA8', '#6E4FA8', '#B97F24', '#1E8A4C', '#A8461F'];
                        $categoryColor = fn ($seed) => $categoryPalette[crc32((string) $seed) % count($categoryPalette)];
                    @endphp
                    @forelse ($entries as $entry)
                        @php
                            $colors = $statusColors[$entry->status] ?? $statusColors['draft'];
                            $catColor = $categoryColor($entry->category ?: 'General');
                            $priorityColor = match (true) {
                                $entry->priority <= 2 => '#C8355F',
                                $entry->priority <= 5 => '#B97F24',
                                default => '#98897A',
                            };
                        @endphp
                        <tr class="kb-row-main">
                            <td><span class="kb-entry-title">{{ $entry->title }}</span></td>
                            <td>
                                @if ($entry->category)
                                    <span class="kb-chip" style="background: {{ $catColor }}1A; color: {{ $catColor }};">{{ $entry->category }}</span>
                                @else
                                    <span style="color: #B0A493;">—</span>
                                @endif
                            </td>
                            <td><div class="kb-content-preview">{{ $entry->content }}</div></td>
                            <td>
                                <span class="kb-priority-dot"><span class="dot" style="background: {{ $priorityColor }};"></span>P{{ $entry->priority }}</span>
                            </td>
                            <td><span class="kb-chip" style="background: {{ $colors['bg'] }}; color: {{ $colors['fg'] }};">{{ \App\Models\KnowledgeBaseEntry::statusLabels()[$entry->status] ?? $entry->status }}</span></td>
                            <td>
                                <button type="button" class="kb-btn kb-btn-secondary kb-btn-sm" onclick="document.getElementById('kb-edit-form-{{ $entry->id }}').classList.toggle('is-open')">✎ Edit</button>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="6" style="border-bottom: none; padding: 0;">
                                <div id="kb-edit-form-{{ $entry->id }}" class="kb-entry-form">
                                    <form action="{{ route('knowledge_base.update', $entry->id) }}" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
                                        @csrf
                                        @method('PUT')
                                        @include('knowledge_base.partials.entry_fields', ['entry' => $entry])
                                        <div class="kb-entry-form-actions">
                                            <button type="submit" class="kb-btn">Save changes</button>
                                        </div>
                                    </form>
                                    <form action="{{ route('knowledge_base.destroy', $entry->id) }}" method="POST" onsubmit="return confirm('Archive this entry? The AI will stop using it.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="kb-btn-danger">Archive entry</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="kb-empty-state">
                                    No knowledge base entries yet.<br>
                                    Add the clinic's services, pricing, hours, and policies here so the AI Employee has something to answer from.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
