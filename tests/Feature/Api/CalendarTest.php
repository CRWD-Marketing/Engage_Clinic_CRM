<?php

namespace Tests\Feature\Api;

use App\Models\CalendarSession;
use App\Models\User;

class CalendarTest extends ApiTestCase
{
    private const SESSION_KEYS = [
        'id', 'therapist_id', 'therapist_name', 'cover_for_user_id', 'cover_for_name', 'patient_id', 'patient_ids',
        'patient_name', 'is_custom_patient', 'activity_label', 'activity_type', 'activity_types', 'session_date',
        'start_time', 'end_time', 'duration_minutes', 'room', 'status', 'cancel_reason', 'cancel_notice_hours',
        'status_label', 'category', 'notes', 'recurrence_group', 'is_past', 'can_supervise', 'supervised',
        'supervised_by_name', 'supervised_at', 'supervision_notes', 'therapist_note',
    ];

    private function therapist(): User
    {
        return User::where('role', 'THERAPIST')->where('is_active', true)
            ->whereIn('id', CalendarSession::select('therapist_id'))->orderBy('id')->firstOrFail();
    }

    public function test_a_therapist_gets_their_own_week(): void
    {
        $therapist = $this->therapist();
        $this->actingAsEmail($therapist->email);

        $response = $this->getJson($this->api('calendar/my-week'))->assertOk()
            ->assertJsonStructure(['therapist_name', 'designation', 'monday', 'sunday', 'prev_date', 'next_date', 'therapy_hours',
                'days' => [['iso', 'is_today', 'is_weekend', 'sessions', 'leave']]])
            ->assertJsonCount(7, 'days');

        foreach ($response->json('days') as $day) {
            foreach ($day['sessions'] as $session) {
                $this->assertSame($therapist->id, $session['therapist_id']);
                $this->assertEqualsCanonicalizing(self::SESSION_KEYS, array_keys($session));
            }
        }
    }

    public function test_the_feed_is_scoped_to_a_therapists_own_sessions(): void
    {
        $therapist = $this->therapist();
        $range = ['start' => now()->subDays(7)->toDateString(), 'end' => now()->addDays(7)->toDateString()];

        $this->actingAsEmail($therapist->email);
        $own = $this->getJson($this->api('calendar/feed').'?'.http_build_query($range))->assertOk()
            ->assertJsonStructure(['sessions' => [self::SESSION_KEYS], 'leaves']);
        $this->assertNotEmpty($own->json('sessions'));
        $this->assertSame([$therapist->id], array_values(array_unique(array_column($own->json('sessions'), 'therapist_id'))));

        $this->actingAsEmail('indira@engagebehavior.com');
        $all = $this->getJson($this->api('calendar/feed').'?'.http_build_query($range))->assertOk();
        $this->assertGreaterThan(1, count(array_unique(array_column($all->json('sessions'), 'therapist_id'))));

        $this->getJson($this->api('calendar/feed'))->assertStatus(422);
    }

    public function test_a_therapist_cannot_open_someone_elses_session_and_only_sees_their_own_note(): void
    {
        $therapist = $this->therapist();
        $mine = CalendarSession::where('therapist_id', $therapist->id)->firstOrFail();
        $mine->update(['therapist_note' => 'Private note']);
        $other = CalendarSession::where('therapist_id', '!=', $therapist->id)->firstOrFail();

        $this->actingAsEmail($therapist->email);
        $this->getJson($this->api("calendar/{$mine->id}"))->assertOk()->assertJsonPath('therapist_note', 'Private note');
        $this->getJson($this->api("calendar/{$other->id}"))->assertForbidden();

        $this->patchJson($this->api("calendar/{$mine->id}/therapist-note"), ['note' => 'Updated'])
            ->assertOk()->assertJsonPath('therapist_note', 'Updated');

        $this->actingAsEmail('indira@engagebehavior.com');
        $this->getJson($this->api("calendar/{$mine->id}"))->assertOk()->assertJsonPath('therapist_note', null);
    }

    public function test_booking_options_list_therapists_patients_types_and_durations(): void
    {
        $this->actingAsEmail('indira@engagebehavior.com');

        $this->getJson($this->api('calendar/booking-options'))->assertOk()
            ->assertJsonStructure([
                'therapists' => [['id', 'name']],
                'leads' => [['id', 'name', 'upcoming_count', 'next_session', 'last_session', 'auth', 'packages']],
                'activity_types',
                'durations',
            ])
            ->assertJsonPath('durations', [30, 45, 60, 90, 120, 150, 180]);
    }

    public function test_booking_editing_and_supervision(): void
    {
        $therapist = $this->therapist();
        $date = now()->addDays(40)->toDateString();
        $booking = [
            'therapist_ids' => [$therapist->id], 'custom_patient' => 'Team meeting', 'activity_types' => ['Admin time'],
            'session_date' => $date, 'start_time' => '18:00', 'duration_minutes' => 60,
        ];

        // A therapist can neither book nor change the schedule.
        $this->actingAsEmail($therapist->email);
        $this->postJson($this->api('calendar'), $booking)->assertForbidden();

        $this->actingAsEmail('indira@engagebehavior.com');
        $created = $this->postJson($this->api('calendar'), $booking)->assertCreated()
            ->assertJsonPath('created', 1)
            ->assertJsonStructure(['message', 'created', 'skipped', 'authorization_warning', 'sessions']);
        $id = $created->json('sessions.0.id');

        // The same slot again: skipped, nothing created.
        $this->postJson($this->api('calendar'), $booking)->assertStatus(422)->assertJsonPath('created', 0);
        $this->postJson($this->api('calendar'), ['therapist_ids' => [$therapist->id]])->assertStatus(422)->assertJsonStructure(['message', 'errors']);

        $this->putJson($this->api("calendar/{$id}"), ['duration_minutes' => 90, 'room' => 'Room 2'])->assertOk()
            ->assertJsonPath('session.end_time', '19:30')->assertJsonPath('session.room', 'Room 2');
        $this->putJson($this->api("calendar/{$id}"), ['status' => 'closed'])->assertOk()->assertJsonPath('session.status', 'closed');

        $past = CalendarSession::where('session_date', '<', now()->startOfDay())->where('status', 'completed')->firstOrFail();
        $this->postJson($this->api("calendar/{$past->id}/supervision"), ['notes' => ''])->assertStatus(422);
        $this->postJson($this->api("calendar/{$past->id}/supervision"), ['notes' => 'Observed the full session.'])->assertOk()
            ->assertJsonPath('session.supervised', true);
        $this->deleteJson($this->api("calendar/{$past->id}/supervision"))->assertOk();

        // A coordinator has no calendar-management rights unless granted the action.
        $this->actingAsEmail($therapist->email);
        $this->putJson($this->api("calendar/{$id}"), ['room' => 'x'])->assertForbidden();
    }
}
