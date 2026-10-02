<?php

namespace Tests\Feature\Api;

use App\Models\CalendarSession;
use App\Models\Patient;
use App\Models\PatientNote;
use App\Models\User;

class PatientTest extends ApiTestCase
{
    private const DETAIL_KEYS = [
        'patient', 'authorizations', 'notes', 'recommended_goals', 'todays_session', 'todays_goal_ids', 'care_team',
        'upcoming_sessions', 'past_sessions', 'attendance_rate', 'is_profile_incomplete', 'missing_fields_label',
        'payments', 'documents', 'profile', 'edit_options',
    ];

    private function patientWithSessions(): Patient
    {
        return Patient::whereIn('lead_id', CalendarSession::select('patient_id'))->orderBy('id')->firstOrFail();
    }

    public function test_the_list_has_each_patient_with_lead_authorizations_and_attendance(): void
    {
        $this->actingAsEmail('indira@engagebehavior.com');

        $response = $this->getJson($this->api('patients'))->assertOk()
            ->assertJsonStructure([['id', 'lead_id', 'diagnosis', 'programme', 'enrolled_at', 'lead' => ['id', 'child_name', 'phone'],
                'authorizations', 'attendance_rate', 'is_profile_incomplete']]);
        $this->assertCount(Patient::count(), $response->json());

        $name = Patient::with('lead')->firstOrFail()->lead->child_name;
        $found = $this->getJson($this->api('patients').'?search='.urlencode($name))->assertOk()->json();
        $this->assertNotEmpty($found);
        $this->assertContains($name, array_column(array_column($found, 'lead'), 'child_name'));
    }

    public function test_a_therapist_only_sees_and_opens_their_own_patients(): void
    {
        $therapist = User::where('role', 'THERAPIST')->whereIn('id', CalendarSession::select('therapist_id'))->orderBy('id')->firstOrFail();
        $ownLeadIds = CalendarSession::where('therapist_id', $therapist->id)->distinct()->pluck('patient_id')->filter();
        $other = Patient::whereNotIn('lead_id', $ownLeadIds)->first();

        $this->actingAsEmail($therapist->email);
        $list = $this->getJson($this->api('patients'))->assertOk()->json();
        $this->assertEqualsCanonicalizing(
            Patient::whereIn('lead_id', $ownLeadIds)->pluck('id')->all(),
            array_column($list, 'id')
        );

        if ($other) {
            $this->getJson($this->api("patients/{$other->id}"))->assertForbidden()
                ->assertJsonPath('message', 'This patient is not assigned to you.');
        }
    }

    public function test_the_patient_page_has_every_tab(): void
    {
        $patient = $this->patientWithSessions();
        $this->actingAsEmail('indira@engagebehavior.com');

        $response = $this->getJson($this->api("patients/{$patient->id}"))->assertOk()
            ->assertJsonStructure([
                'patient' => ['id', 'lead' => ['child_name', 'funding_services_needed', 'package_ids']],
                'payments' => ['billed_total', 'collected_total', 'outstanding_total', 'invoices'],
                'profile' => ['package_names', 'package_hours_per_week', 'package_rate_per_hour', 'package_value_excl_vat', 'package_location',
                    'package_setting', 'funding_summary', 'funding_hours_needed', 'assessment_clinician_name', 'lead_owner_name'],
                'edit_options' => ['packages' => [['name', 'label']], 'insurances', 'document_types'],
            ]);
        $this->assertEqualsCanonicalizing(self::DETAIL_KEYS, array_keys($response->json()));
        $this->assertArrayNotHasKey('notes', $response->json('patient'));

        foreach ($response->json('past_sessions') as $session) {
            $this->assertArrayHasKey('status_label', $session);
        }
    }

    public function test_notes_goals_documents_and_edit_details(): void
    {
        $patient = $this->patientWithSessions();
        $this->actingAsEmail('indira@engagebehavior.com');
        $base = "patients/{$patient->id}";

        $this->postJson($this->api("$base/notes"), ['body' => ''])->assertStatus(422)->assertJsonStructure(['errors' => ['body']]);
        $this->postJson($this->api("$base/notes"), ['body' => 'Good session today.'])->assertCreated()
            ->assertJsonPath('note.body', 'Good session today.')->assertJsonStructure(['note' => ['author_name']]);

        $this->postJson($this->api("$base/goals/today"), ['goal_ids' => [], 'new_goal_titles' => ['Sit for circle time']])->assertOk()
            ->assertJsonPath('created_goals.0.title', 'Sit for circle time');

        $this->postJson($this->api("$base/documents"), ['name' => ''])->assertStatus(422);
        $document = $this->postJson($this->api("$base/documents"), ['name' => 'Passport copy.pdf', 'type' => 'Identity', 'expires_at' => now()->addYear()->toDateString()])
            ->assertCreated()
            ->assertJsonStructure(['document' => ['id', 'name', 'type', 'uploader_label', 'created_at', 'expires_at', 'expiry_label', 'expiry_variant', 'has_file']])
            ->assertJsonPath('document.uploader_label', 'You');
        $this->deleteJson($this->api("$base/documents/".$document->json('document.id')))->assertOk();

        $this->putJson($this->api($base), ['child_age' => 40])->assertStatus(422)->assertJsonStructure(['errors' => ['child_age']]);
        $this->putJson($this->api($base), ['diagnosis' => 'ASD Level 2', 'phone' => '+971500000001', 'authorized_hours_total' => 200])
            ->assertOk()->assertJsonPath('message', 'Patient details updated.');
        $this->assertSame('ASD Level 2', $patient->fresh()->diagnosis);

        // A coordinator's clinical fields are dropped; contact fields are saved.
        $this->actingAsEmail('coordinator@engagebehavior.com');
        $this->putJson($this->api($base), ['diagnosis' => 'Changed', 'phone' => '+971500000002'])->assertOk();
        $this->assertSame('ASD Level 2', $patient->fresh()->diagnosis);
        $this->assertSame('+971500000002', $patient->fresh()->lead->phone);
        $this->postJson($this->api("$base/notes"), ['body' => 'x'])->assertForbidden();
    }

    public function test_note_review_is_for_the_supervisor_and_admin(): void
    {
        $note = PatientNote::whereNull('signed_off_at')->firstOrFail();

        $this->actingAsEmail('coordinator@engagebehavior.com');
        $this->getJson($this->api('patient-notes/review'))->assertForbidden();

        $this->actingAsEmail('indira@engagebehavior.com');
        $this->getJson($this->api('patient-notes/review').'?filter=unsigned')->assertOk()
            ->assertJsonStructure([['id', 'body', 'author_name', 'signed_off_by_name', 'flagged', 'patient' => ['id', 'lead' => ['child_name']]]]);

        $this->postJson($this->api('patient-notes/sign-off'), ['note_ids' => []])->assertStatus(422)
            ->assertJsonPath('message', 'Select at least one note.');
        $this->postJson($this->api('patient-notes/sign-off'), ['note_ids' => [$note->id]])->assertOk()
            ->assertJsonPath('signed_off_count', 1)->assertJsonPath('message', '1 note signed off.');
        $this->postJson($this->api('patient-notes/sign-off'), ['note_ids' => [$note->id]])->assertOk()->assertJsonPath('signed_off_count', 0);

        $this->postJson($this->api("patient-notes/{$note->id}/flag"), ['flag_reason' => ''])->assertStatus(422)
            ->assertJsonPath('message', 'Add a reason for flagging this note.');
        $this->postJson($this->api("patient-notes/{$note->id}/flag"), ['flag_reason' => 'Needs more detail'])->assertOk()
            ->assertJsonPath('note.flagged', true)->assertJsonPath('note.flag_reason', 'Needs more detail')
            ->assertJsonPath('note.signed_off_by_name', fn ($name) => is_string($name) && $name !== '');
        $this->assertContains($note->id, array_column($this->getJson($this->api('patient-notes/review').'?filter=flagged')->json(), 'id'));
        $this->deleteJson($this->api("patient-notes/{$note->id}/flag"))->assertOk()->assertJsonPath('note.flagged', false);
    }
}
