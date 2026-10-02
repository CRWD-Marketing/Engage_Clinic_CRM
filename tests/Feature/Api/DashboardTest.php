<?php

namespace Tests\Feature\Api;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;

class DashboardTest extends ApiTestCase
{
    private const HEADER = ['role', 'user_full_name', 'location_label'];

    private const SCHEDULE = ['sessions_today_count', 'rooms_in_use_count', 'active_therapists_count', 'attendance_rate', 'attendance_delta', 'today_sessions'];

    private const LEADS = ['new_leads_count', 'new_leads_delta', 'lead_sources', 'lead_sources_scale'];

    private const BILLING = ['revenue_mtd', 'revenue_delta', 'last_month_name', 'claims_pending_amount', 'claims_pending_count', 'oldest_claim_days'];

    private const WAITLIST = ['waitlist_count', 'avg_wait_weeks', 'waitlist_next_up'];

    /** role => [seeded login, the keys the app's dashboard type for that role expects] */
    public static function roles(): array
    {
        return [
            'admin' => ['FULL_ADMIN', [...self::HEADER, ...self::LEADS, ...self::SCHEDULE, ...self::BILLING, ...self::WAITLIST, 'authorizations_expiring', 'whatsapp_inbox']],
            'hr' => ['HR_STAFF', [...self::HEADER, ...self::SCHEDULE, 'active_staff_count', 'new_hires_this_month', 'staff_by_role', 'recent_staff']],
            'sales' => ['SALES_STAFF', [...self::HEADER, ...self::LEADS, 'whatsapp_inbox', 'my_active_leads_count', 'awaiting_contact_count', 'my_leads_pipeline']],
            'finance' => ['FINANCE_STAFF', [...self::HEADER, ...self::BILLING, 'collected_mtd', 'aging_buckets', 'revenue_by_payer']],
            'coordinator' => ['COORDINATOR', [...self::HEADER, 'new_leads_count', 'new_leads_delta', ...self::SCHEDULE, 'whatsapp_inbox',
                'pending_intake_calls_count', 'overdue_intake_calls_count', 'intake_pipeline', 'no_shows_count', 'no_show_follow_ups_needed',
                'no_show_follow_up_list', 'waitlist_count', 'openings_this_week']],
            'supervisor' => ['CLINICAL_SUPERVISOR', [...self::HEADER, ...self::SCHEDULE, ...self::WAITLIST, 'pending_notes_count', 'overdue_notes_count',
                'notes_awaiting_signoff', 'flagged_notes', 'active_treatment_plans_count', 'plans_due_for_review_count', 'treatment_plans_due_list',
                'avg_caseload_per_therapist']],
            'therapist' => ['THERAPIST', [...self::HEADER, 'sessions_today_count', 'rooms_in_use_count', 'attendance_rate', 'attendance_delta', 'today_sessions',
                'my_active_patients_count', 'my_pending_notes_count', 'my_notes_awaiting_signoff_list', 'my_treatment_plans_due_count', 'my_treatment_plans_due_list']],
            'other staff' => ['OTHER_STAFF', [...self::HEADER, 'draft_quotations_count', 'awaiting_payment_count', 'paid_this_month_count', 'recent_invoices']],
        ];
    }

    #[DataProvider('roles')]
    public function test_each_role_gets_its_own_dashboard(string $role, array $keys): void
    {
        $user = User::where('role', $role)->where('is_active', true)->orderBy('id')->firstOrFail();
        $this->actingAsEmail($user->email);

        $response = $this->getJson($this->api('dashboard'))->assertOk()->assertJsonPath('role', $role);

        $this->assertEqualsCanonicalizing($keys, array_keys($response->json()));
    }

    public function test_nested_rows_have_the_shapes_the_app_reads(): void
    {
        $this->actingAsEmail('admin@gmail.com');
        $admin = $this->getJson($this->api('dashboard'))->assertOk()
            ->assertJsonStructure([
                'lead_sources' => [['source', 'count']],
                'waitlist_next_up' => [['id', 'child_name', 'waiting_weeks']],
                'whatsapp_inbox' => [['id', 'name', 'channel', 'last_message_preview', 'unread_count']],
            ]);
        $this->assertIsInt($admin->json('lead_sources_scale'));

        $this->actingAsEmail('indira@engagebehavior.com');
        $this->getJson($this->api('dashboard'))->assertOk()
            ->assertJsonStructure([
                'today_sessions' => [['id', 'patient_name', 'therapist_name', 'start_time', 'status', 'category']],
                'notes_awaiting_signoff' => [['id', 'body', 'author_name', 'created_at', 'patient' => ['id', 'lead' => ['child_name']]]],
            ]);

        $this->actingAsEmail('coordinator@engagebehavior.com');
        $this->getJson($this->api('dashboard'))->assertOk()
            ->assertJsonStructure(['intake_pipeline' => [['id', 'status', 'is_overdue']]]);

        $this->actingAsEmail('kavitha@engagebehavior.com');
        $this->getJson($this->api('dashboard'))->assertOk()
            ->assertJsonCount(4, 'aging_buckets')
            ->assertJsonStructure(['aging_buckets' => [['label', 'amount', 'count']]]);
    }
}
