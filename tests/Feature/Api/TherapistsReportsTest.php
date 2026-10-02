<?php

namespace Tests\Feature\Api;

use App\Models\CalendarSession;
use App\Models\User;

class TherapistsReportsTest extends ApiTestCase
{
    public function test_the_roster_and_a_therapists_week(): void
    {
        $this->actingAsEmail('indira@engagebehavior.com');

        $response = $this->getJson($this->api('therapists'))->assertOk()
            ->assertJsonStructure(['can_view_all', 'week_start', 'week_end', 'is_current_week', 'selected_therapist_id',
                'therapists' => [['id', 'name', 'department_label', 'weekly_hours', 'session_count']], 'sessions'])
            ->assertJsonPath('can_view_all', true)
            ->assertJsonPath('is_current_week', true)
            ->assertJsonCount(User::where('role', 'THERAPIST')->count(), 'therapists');

        $busy = collect($response->json('therapists'))->sortByDesc('session_count')->first();
        $week = $this->getJson($this->api('therapists')."?therapist_id={$busy['id']}")->assertOk()
            ->assertJsonPath('selected_therapist_id', $busy['id'])
            ->assertJsonCount($busy['session_count'], 'sessions');
        foreach ($week->json('sessions') as $session) {
            $this->assertSame($busy['id'], $session['therapist_id']);
            $this->assertArrayHasKey('status_label', $session);
        }

        $next = now()->addWeek()->toDateString();
        $this->getJson($this->api('therapists')."?week={$next}")->assertOk()->assertJsonPath('is_current_week', false);
    }

    public function test_closing_a_session_drops_it_from_the_calendar_but_not_from_the_roster(): void
    {
        $this->actingAsEmail('indira@engagebehavior.com');
        $session = CalendarSession::where('status', 'scheduled')->whereBetween('session_date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])->firstOrFail();
        $query = '?'.http_build_query(['start' => $session->session_date->toDateString(), 'end' => $session->session_date->toDateString()]);

        $this->putJson($this->api("calendar/{$session->id}"), ['status' => 'closed'])->assertOk();

        $this->assertNotContains($session->id, array_column($this->getJson($this->api('calendar/feed').$query)->json('sessions'), 'id'));
        $week = $this->getJson($this->api('therapists')."?therapist_id={$session->therapist_id}")->assertOk();
        $this->assertSame('closed', collect($week->json('sessions'))->firstWhere('id', $session->id)['status']);
    }

    public function test_therapists_and_coordinators_do_not_get_the_roster(): void
    {
        $this->actingAsEmail('coordinator@engagebehavior.com');
        $this->getJson($this->api('therapists'))->assertForbidden();
    }

    public function test_reports_have_every_section(): void
    {
        $this->actingAsEmail('admin@gmail.com');

        $response = $this->getJson($this->api('reports'))->assertOk()
            ->assertJsonStructure([
                'period_start_label', 'period_end_label', 'updated_at',
                'vat_filing_label', 'vat_trn', 'vat_rate', 'vat_standard_rated_supplies', 'vat_output_tax', 'vat_credit_notes_issued', 'vat_net_payable',
                'collection_invoiced_total', 'collection_collected_total', 'collection_rate_pct', 'collection_billable_hours', 'collection_revenue_per_hour',
                'revenue_by_service', 'revenue_by_setting', 'revenue_by_therapist',
                'revenue_by_month' => [['label', 'amount', 'thousands', 'is_current']],
                'revenue_total_6mo', 'revenue_average_6mo', 'revenue_best_month' => ['label', 'amount', 'thousands', 'is_current'], 'revenue_mom_delta',
                'funnel_stages' => [['label', 'count', 'pct']], 'captured_count', 'conversion_rate', 'lead_sources', 'best_source', 'worst_source', 'median_enroll_days',
                'lost_leads_count', 'lost_leads_value', 'lost_reasons',
                'therapy_month_label', 'therapy_total_hours', 'therapy_hours_by_type',
            ])
            ->assertJsonCount(6, 'revenue_by_month')
            ->assertJsonCount(4, 'funnel_stages');

        $months = $response->json('revenue_by_month');
        $this->assertTrue($months[5]['is_current']);
        $this->assertStringNotContainsString('*', $months[5]['label']);

        $this->actingAsEmail('coordinator@engagebehavior.com');
        $this->getJson($this->api('reports'))->assertForbidden();
    }
}
