<?php

namespace Tests\Feature\Api;

use App\Models\CalendarSession;
use App\Models\Lead;
use App\Models\Patient;
use App\Models\StaffLeave;
use App\Models\User;

class NotificationsLeavePatientCreateTest extends ApiTestCase
{
    public function test_the_bell_lists_items_and_tracks_read_state_per_user(): void
    {
        $this->actingAsEmail('info@engagebehavior.com');
        $lead = Lead::create(['child_name' => 'Bell Child', 'phone' => '+971500000020', 'status' => Lead::STATUS_NEW]);

        $bell = $this->getJson($this->api('notifications'))->assertOk()
            ->assertJsonStructure(['count', 'items' => [['id', 'icon', 'title', 'subtitle', 'url', 'read', 'weight', 'created_at']]]);
        $before = $bell->json('count');
        $item = collect($bell->json('items'))->firstWhere('id', "lead:{$lead->id}");
        $this->assertNotNull($item);
        $this->assertFalse($item['read']);

        $this->postJson($this->api("notifications/lead:{$lead->id}/read"))->assertOk()->assertJsonPath('count', $before - 1);
        $this->postJson($this->api('notifications/read-all'))->assertOk()->assertJsonPath('count', 0);

        // Another user's badge is untouched.
        $this->actingAsEmail('admin@gmail.com');
        $this->assertGreaterThan(0, $this->getJson($this->api('notifications'))->json('count'));

        // A therapist has none of the lead / contact / inbox modules, so those feeds are empty.
        $therapist = User::where('role', 'THERAPIST')->where('is_active', true)->firstOrFail();
        $this->actingAsEmail($therapist->email);
        foreach ($this->getJson($this->api('notifications'))->assertOk()->json('items') as $row) {
            $this->assertDoesNotMatchRegularExpression('/^(lead|contact|whatsapp):/', $row['id']);
        }
    }

    public function test_marking_leave_cancels_that_days_sessions_and_can_be_removed(): void
    {
        $session = CalendarSession::where('status', 'scheduled')->where('session_date', '>', now()->toDateString())->firstOrFail();
        $date = $session->session_date->toDateString();
        $scheduled = CalendarSession::where('therapist_id', $session->therapist_id)->whereDate('session_date', $date)->where('status', 'scheduled')->count();
        $payload = ['user_id' => $session->therapist_id, 'leave_date' => $date, 'leave_type' => 'Sick leave', 'reason' => 'Flu'];

        // A therapist can't mark leave.
        $this->actingAsEmail(User::findOrFail($session->therapist_id)->email);
        $this->postJson($this->api('calendar/leave'), $payload)->assertForbidden();

        $this->actingAsEmail('indira@engagebehavior.com');
        $this->getJson($this->api('calendar/booking-options'))->assertOk()->assertJsonPath('leave_types', StaffLeave::TYPES);
        $this->getJson($this->api('calendar/leave/impact')."?user_id={$session->therapist_id}&date={$date}")->assertOk()->assertJsonPath('count', $scheduled);

        $this->postJson($this->api('calendar/leave'), ['user_id' => $session->therapist_id])->assertStatus(422)->assertJsonPath('message', 'Check the leave details.');
        $leave = $this->postJson($this->api('calendar/leave'), $payload)->assertOk()
            ->assertJsonPath('cancelled', $scheduled)
            ->assertJsonStructure(['message', 'leave' => ['id', 'user_id', 'leave_type', 'reason'], 'cancelled']);
        $this->assertSame('cancelled', $session->fresh()->status);
        $this->assertSame('clinic', $session->fresh()->cancel_reason);

        $this->deleteJson($this->api('calendar/leave/'.$leave->json('leave.id')))->assertOk()->assertJsonPath('message', 'Leave removed.');
        $this->assertNull(StaffLeave::find($leave->json('leave.id')));
    }

    public function test_a_patient_can_be_added_directly(): void
    {
        $this->actingAsEmail('indira@engagebehavior.com');

        $this->getJson($this->api('patients/create-options'))->assertOk()
            ->assertJsonStructure(['packages' => [['name', 'label']], 'insurances']);

        $this->postJson($this->api('patients'), ['child_name' => 'Direct Child'])->assertStatus(422)
            ->assertJsonStructure(['errors' => ['diagnosis', 'programme', 'phone']]);

        $response = $this->postJson($this->api('patients'), [
            'child_name' => 'Direct Child', 'child_age' => 6, 'diagnosis' => 'ASD', 'programme' => 'ABA 20h/wk', 'phone' => '+971500000021',
            'payer_name' => 'Thiqa', 'authorized_hours_total' => 80, 'clinical_note' => 'Added from the app.',
        ])->assertCreated()->assertJsonPath('message', 'Patient added.');

        $patient = Patient::findOrFail($response->json('patient_id'));
        $this->assertSame('Direct Child', $patient->lead->child_name);
        $this->assertSame('Manual entry', $patient->lead->source);
        $this->assertSame('Thiqa', $patient->authorizations()->first()->payer_name);
        $this->getJson($this->api("patients/{$patient->id}"))->assertOk()->assertJsonPath('notes.0.body', 'Added from the app.');

        // Coordinators and therapists can't add a patient.
        $this->actingAsEmail('coordinator@engagebehavior.com');
        $this->postJson($this->api('patients'), ['child_name' => 'x', 'diagnosis' => 'x', 'programme' => 'x', 'phone' => '1'])->assertForbidden();
    }
}
