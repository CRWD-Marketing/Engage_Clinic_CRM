<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\PresentsNotes;
use App\Http\Controllers\Controller;
use App\Models\PatientNote;
use Illuminate\Http\Request;

/**
 * Reviewing therapists' session notes: the sign-off queue, sign-off, and
 * flagging a note for attention. The web dashboard only lists unsigned and
 * flagged notes; these actions exist for the mobile app.
 *
 * Open to the Clinical Supervisor and Full Admin only.
 */
class NoteReviewController extends Controller
{
    use PresentsNotes;

    private const REVIEWER_ROLES = ['CLINICAL_SUPERVISOR', 'FULL_ADMIN'];

    /** GET /patient-notes/review?filter=unsigned|flagged */
    public function index(Request $request)
    {
        $this->assertReviewer();

        $query = PatientNote::with(['patient.lead', 'user', 'signedOffBy']);

        // Unsigned notes oldest first (the queue); flagged notes newest first.
        $notes = $request->query('filter') === 'flagged'
            ? $query->flagged()->latest()->get()
            : $query->unsigned()->oldest()->get();

        return response()->json($notes->map(fn (PatientNote $note) => $this->presentNote($note))->values());
    }

    /** POST /patient-notes/sign-off {note_ids} */
    public function signOff(Request $request)
    {
        $this->assertReviewer();

        $data = $request->validate([
            'note_ids' => ['required', 'array', 'min:1'],
            'note_ids.*' => ['integer', 'exists:patient_notes,id'],
        ], [
            'note_ids.required' => 'Select at least one note.',
            'note_ids.min' => 'Select at least one note.',
        ]);

        // Notes already signed keep their original signer and time.
        $count = PatientNote::whereIn('id', $data['note_ids'])->unsigned()->update([
            'signed_off_at' => now(),
            'signed_off_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => $count.' '.($count === 1 ? 'note' : 'notes').' signed off.',
            'signed_off_count' => $count,
        ]);
    }

    /** POST /patient-notes/{note}/flag {flag_reason} */
    public function flag(Request $request, PatientNote $note)
    {
        $this->assertReviewer();

        $data = $request->validate([
            'flag_reason' => ['required', 'string', 'max:255'],
        ], [
            'flag_reason.required' => 'Add a reason for flagging this note.',
        ]);

        $note->forceFill(['flagged' => true, 'flag_reason' => trim($data['flag_reason'])])->save();

        return response()->json(['success' => true, 'note' => $this->presentNote($note->load('patient.lead'))]);
    }

    /** DELETE /patient-notes/{note}/flag */
    public function unflag(PatientNote $note)
    {
        $this->assertReviewer();

        $note->forceFill(['flagged' => false, 'flag_reason' => null])->save();

        return response()->json(['success' => true, 'note' => $this->presentNote($note->load('patient.lead'))]);
    }

    private function assertReviewer(): void
    {
        abort_unless(
            in_array(auth()->user()->role, self::REVIEWER_ROLES, true),
            403,
            'Only the Clinical Supervisor can review session notes.'
        );
    }
}
