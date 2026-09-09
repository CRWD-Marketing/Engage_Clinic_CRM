<?php

namespace App\Http\Controllers\KnowledgeBase;

use App\Http\Controllers\Controller;
use App\Models\AiEmployeeSettings;
use App\Models\KnowledgeBaseEntry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KnowledgeBaseController extends Controller
{
    public function index()
    {
        $entries = KnowledgeBaseEntry::orderBy('priority')->orderByDesc('updated_at')->get();
        $settings = AiEmployeeSettings::current();

        return view('knowledge_base.index', compact('entries', 'settings'));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        KnowledgeBaseEntry::create([
            ...$validated,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('knowledge_base.index')->with('status', 'Knowledge base entry created.');
    }

    public function update(Request $request, KnowledgeBaseEntry $entry)
    {
        $validated = $this->validated($request);

        $entry->update([
            ...$validated,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('knowledge_base.index')->with('status', 'Knowledge base entry updated.');
    }

    public function destroy(KnowledgeBaseEntry $entry)
    {
        // Archive rather than hard-delete: ai_employee_logs references entries by
        // id for the "what did the AI use" audit trail, which stays meaningful
        // only if the entry row still exists.
        $entry->update(['status' => KnowledgeBaseEntry::STATUS_ARCHIVED, 'updated_by' => auth()->id()]);

        return redirect()->route('knowledge_base.index')->with('status', 'Knowledge base entry archived.');
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'is_enabled' => ['nullable', 'boolean'],
            'model_name' => ['nullable', 'string', 'max:255'],
            'max_history_messages' => ['required', 'integer', 'min:1', 'max:50'],
            'max_kb_entries' => ['required', 'integer', 'min:1', 'max:20'],
            'response_delay_seconds' => ['required', 'integer', 'min:0', 'max:600'],
            'system_prompt_override' => ['nullable', 'string'],
            'voice_enabled' => ['nullable', 'boolean'],
            'voice_greeting' => ['nullable', 'string', 'max:2000'],
            'human_handoff_phone_number' => ['nullable', 'string', 'max:32'],
        ]);

        $validated['is_enabled'] = $request->boolean('is_enabled');
        $validated['voice_enabled'] = $request->boolean('voice_enabled');

        AiEmployeeSettings::current()->update($validated);
        AiEmployeeSettings::forgetCache();

        return redirect()->route('knowledge_base.index')->with('status', 'AI Employee settings saved.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'content' => ['required', 'string'],
            'status' => ['required', Rule::in(array_keys(KnowledgeBaseEntry::statusLabels()))],
            'priority' => ['required', 'integer', 'min:1', 'max:10'],
        ]);
    }
}
