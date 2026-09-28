<?php

namespace Database\Seeders;

use App\Models\CalendarSession;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Location;
use App\Models\Package;
use App\Models\Patient;
use App\Models\PatientAuthorization;
use App\Models\Service;
use App\Models\User;
use App\Models\WhatsappContact;
use App\Models\WhatsappMessage;
use App\Services\Billing\DocumentNumbers;
use App\Services\Billing\InvoiceBuilder;
use App\Services\Billing\SessionLedger;
use Illuminate\Database\Seeder;

class TargetedSampleDataSeeder extends Seeder
{
    /**
     * One-off, precisely-scoped sample data drop (not the full reset the
     * other seeders do): 5 leads (2 with every intake step filled), 3
     * contacts with a booking slot, 4 WhatsApp/Instagram/Facebook threads
     * carrying real family details in the chat text (for the Family details
     * auto-detect panel to pick up), 3 patients with a real schedule and
     * mixed attendance, and 2 invoices (one fully paid, one partial).
     */
    public function run(): void
    {
        $owners = User::whereIn('role', ['SALES_STAFF', 'FULL_ADMIN'])->pluck('id')->all();
        $coordinator = User::whereIn('role', ['COORDINATOR', 'FULL_ADMIN'])->first();
        $therapists = User::where('role', 'THERAPIST')->pluck('id')->all();

        $this->seedLeads($owners);
        $this->seedContacts();
        $this->seedWhatsapp();
        $this->seedPatientsWithSchedule($coordinator, $therapists);
        $this->seedBilling();
    }

    /**
     * 5 pipeline leads, spread across stages. Two of them (Maya, Sultan) get
     * every one of the 7 intake-checklist fields filled in, matching what
     * fullyFilledShowcaseLead() does in LeadSeeder - "form filled completed."
     */
    private function seedLeads(array $owners): void
    {
        $leads = [
            ['Maya Al Hosani', 'Reem Al Hosani', 4, Lead::STATUS_ASSESSMENT_BOOKED, 'Instagram', true],
            ['Sultan Bin Zayed', 'Mansoor Bin Zayed', 6, Lead::STATUS_CONTACTED, 'Referral', true],
            ['Ayesha Malik', 'Farah Malik', 3, Lead::STATUS_NEW, 'Website', false],
            ['Hamdan Al Qassimi', 'Rashid Al Qassimi', 5, Lead::STATUS_NEW, 'Google', false],
            ['Noor Fatima', 'Aisha Fatima', 5, Lead::STATUS_ASSESSMENT_DONE, 'WhatsApp', false],
        ];

        foreach ($leads as $i => [$child, $parent, $age, $status, $source, $fullyFilled]) {
            $createdAt = now()->subDays(20 - $i * 3);

            $lead = Lead::updateOrCreate(
                ['child_name' => $child],
                [
                    'child_age' => (string) $age,
                    'parent_guardian_name' => $parent,
                    'phone' => '+9715'.random_int(0, 9).random_int(1000000, 9999999),
                    'email' => strtolower(str_replace(' ', '.', $parent)).'@gmail.com',
                    'source' => $source,
                    'campaign' => 'Home Based Speech Therapy',
                    'city' => 'Abu Dhabi',
                    'child_age_band' => $age <= 4 ? '3-4' : ($age <= 6 ? '5-6' : '7-8'),
                    'interested_in' => 'ABA therapy',
                    'insurance' => 'Not sure yet',
                    'estimated_value' => (string) (random_int(8, 30) * 1000),
                    'notes' => 'Parent reached out asking about availability and pricing.',
                    'status' => $status,
                ]
            );

            if ($fullyFilled) {
                $lead->forceFill([
                    'parent_contact_completed_at' => $createdAt->copy()->addDay(),
                    'parent_relationship' => 'Mother',
                    'parent_alternate_phone' => '+9714'.random_int(1000000, 9999999),
                    'preferred_language' => 'English',

                    'child_details_completed_at' => $createdAt->copy()->addDays(2),
                    'child_date_of_birth' => now()->subYears($age)->subMonths(3),
                    'child_gender' => $i % 2 === 0 ? 'Female' : 'Male',
                    'child_emirates_id' => '784-2020-'.random_int(1000000, 9999999).'-1',
                    'child_emirates_id_expiry' => now()->addYears(2),
                    'diagnosis_suspected' => 'Autism Spectrum Disorder',
                    'nursery_school' => 'Little Falcons Nursery',
                    'main_concern' => 'Limited eye contact, delayed speech milestones.',

                    'intake_form_completed_at' => $createdAt->copy()->addDays(4),
                    'intake_form_received_on' => $createdAt->copy()->addDays(4),
                    'intake_form_received_via' => 'Email',
                    'allergies' => 'None known',
                    'medical_history' => 'No significant history.',

                    'assessment_completed_at' => $createdAt->copy()->addDays(7),
                    'assessment_date' => $createdAt->copy()->addDays(7),
                    'assessment_tool' => 'ADOS-2',
                    'assessment_report_reference' => 'ASM-'.now()->format('Y').'-'.(200 + $i),
                    'assessment_report_summary' => 'Expressive language below age-expected range. Therapy recommended.',

                    'funding_completed_at' => $createdAt->copy()->addDays(9),
                    'funding_type' => 'Insurance',
                    'funding_insurer' => 'Daman',
                    'funding_policy_number' => 'DA-'.random_int(10, 99).'-'.random_int(100000, 999999),
                    'funding_approval_valid_until' => now()->addMonths(9),

                    'package_completed_at' => $createdAt->copy()->addDays(11),
                    'package_start_date' => $createdAt->copy()->addDays(14),
                    'package_sessions_per_week' => 4,
                    'package_agreed_by' => 'Emily',

                    'consent_completed_at' => $createdAt->copy()->addDays(13),
                    'consent_signed_date' => $createdAt->copy()->addDays(13),
                    'consent_signed_by' => 'Mother',
                    'consent_data_photo' => 'Yes',
                    'consent_signature_method' => 'In person',
                ])->save();
            }

            $lead->assigned_to = $owners ? $owners[$i % count($owners)] : null;
            $lead->created_at = $createdAt;
            $lead->save();
        }
    }

    /**
     * 3 "Contact us" submissions, each with a requested consultation slot
     * (booking_date/booking_time) - so they show a real slot in the Contacts
     * list rather than "No slot requested."
     */
    private function seedContacts(): void
    {
        $entries = [
            ['Khalid Al Marri', 5, 'khalid.almarri@example.com', '+971501230101', 'ABA therapy', 'Would like to book a consultation for my son.', now()->addDays(3)->toDateString(), '9:00 AM'],
            ['Salama Al Nuaimi', 4, 'salama.n@example.com', '+971501230102', 'Speech therapy', 'Referred by our paediatrician, looking to book an assessment.', now()->addDays(5)->toDateString(), '1:00 PM'],
            ['Yusuf Al Hammadi', 6, 'yusuf.h@example.com', '+971501230103', 'Occupational therapy', 'Requesting a consultation slot this week.', now()->subDays(2)->toDateString(), '3:30 PM'],
        ];

        foreach ($entries as [$name, $age, $email, $phone, $interest, $message, $bookingDate, $bookingTime]) {
            $contact = Contact::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'child_age' => (string) $age,
                    'phone' => $phone,
                    'interested_in' => $interest,
                    'message' => $message,
                    'status' => Contact::STATUS_NEW,
                    'booking_date' => $bookingDate,
                    'booking_time' => $bookingTime,
                ]
            );
            $contact->created_at = now()->subDays(random_int(1, 6));
            $contact->save();
        }
    }

    /**
     * 4 chat threads across WhatsApp (x2), Instagram and Facebook - each
     * inbound message carries real family details (child name/age,
     * diagnosis/interest, insurer) in the text itself, so extractLeadHints()
     * picks it up and the Family details panel shows it read-only without
     * anyone typing it in by hand.
     */
    private function seedWhatsapp(): void
    {
        $threads = [
            [
                'wa_id' => '971502223401', 'channel' => 'whatsapp', 'name' => 'Mansoor Bin Zayed',
                'messages' => [
                    'Hi, my son Zayed is 6, recently diagnosed with autism. We are looking into ABA therapy.',
                    'We have Daman insurance if that helps with coverage.',
                ],
            ],
            [
                'wa_id' => '971502223402', 'channel' => 'whatsapp', 'name' => 'Farah Malik',
                'messages' => [
                    "Hello, my daughter's name is Mira. She is 4 and mostly non-verbal.",
                    'We are interested in speech therapy. Our insurance is Thiqa.',
                ],
            ],
            [
                'wa_id' => '28920101', 'channel' => 'instagram', 'name' => 'rashid.parent',
                'messages' => [
                    'Hi! My son Rashid is 5, we are looking for occupational therapy support.',
                    'Insurance: ADNIC. When can we book an assessment?',
                ],
            ],
            [
                'wa_id' => '48920102', 'channel' => 'facebook', 'name' => 'Alya Guardian',
                'messages' => [
                    'Hello, my daughter Alya is 3. We are interested in early intervention.',
                    'We will be self-pay for now, no insurance yet.',
                ],
            ],
        ];

        foreach ($threads as $t) {
            $contact = WhatsappContact::updateOrCreate(
                ['wa_id' => $t['wa_id']],
                [
                    'channel' => $t['channel'],
                    'name' => $t['name'],
                    'ai_state' => WhatsappContact::AI_STATE_ACTIVE,
                    'unread_count' => 0,
                ]
            );

            $sentAt = now()->subDays(2)->setTime(9, 0);
            foreach ($t['messages'] as $i => $body) {
                $sentAt = $sentAt->copy()->addMinutes(random_int(5, 30));
                $message = $contact->messages()->updateOrCreate(
                    ['wa_message_id' => 'sample_'.$t['wa_id'].'_'.$i],
                    [
                        'direction' => 'inbound',
                        'type' => 'text',
                        'body' => $body,
                        'status' => 'received',
                        'sent_at' => $sentAt,
                    ]
                );
                $message->sent_at = $sentAt;
                $message->save();
            }

            $contact->update([
                'last_message_preview' => end($t['messages']),
                'last_message_at' => $sentAt,
            ]);
        }
    }

    /**
     * 3 patients - each with its own enrolled lead - given a real weekly
     * schedule (calendar_sessions, tied to the patient via the lead) and
     * mixed attendance: some completed, one no-show, one cancelled, plus a
     * couple of upcoming ones still scheduled.
     */
    private function seedPatientsWithSchedule(?User $coordinator, array $therapists): void
    {
        $abaService = Service::where('name', 'ABA therapy session')->first();
        $speechService = Service::where('name', 'Speech & language therapy')->first();
        $insideAbuDhabi = Location::where('name', 'Inside Abu Dhabi')->first();

        $patients = [
            ['Lina Al Kaabi', 'Saeed Al Kaabi', 5, 'P-20 hrs ABA and 10 hr speech', 'Daman', 'ABA'],
            ['Omar Al Dhaheri', 'Khalid Al Dhaheri', 4, 'Package — 40 hrs ABA', 'Self-pay', 'ABA'],
            ['Fatima Al Shehhi', 'Hamdan Al Shehhi', 6, 'P-10 hrs speech / OT', 'Thiqa', 'Speech'],
        ];

        foreach ($patients as $i => [$child, $parent, $age, $programme, $payer, $activityType]) {
            $lead = Lead::updateOrCreate(
                ['child_name' => $child],
                [
                    'child_age' => (string) $age,
                    'parent_guardian_name' => $parent,
                    'phone' => '+9715'.random_int(0, 9).random_int(1000000, 9999999),
                    'email' => strtolower(str_replace(' ', '.', $parent)).'@gmail.com',
                    'source' => 'Manual entry',
                    'status' => Lead::STATUS_ENROLLED,
                ]
            );
            $lead->created_at = now()->subDays(60 - $i * 10);
            $lead->save();

            $patient = Patient::updateOrCreate(
                ['lead_id' => $lead->id],
                [
                    'diagnosis' => 'Autism Spectrum Disorder (Level 2)',
                    'programme' => $programme,
                    'enrolled_at' => now()->subDays(45 - $i * 10),
                    'treatment_plan_review_due_at' => now()->addDays(60),
                ]
            );

            PatientAuthorization::updateOrCreate(
                ['patient_id' => $patient->id, 'sort_order' => 0],
                [
                    'payer_name' => $payer,
                    'coverage_percent' => $payer === 'Self-pay' ? 0 : 85,
                    'covers_services' => ['ABA', 'Speech'],
                    'authorized_hours_total' => 40,
                    'renews_at' => now()->addMonths(6),
                ]
            );

            $therapistId = $therapists[$i % max(count($therapists), 1)] ?? null;
            if (! $therapistId) {
                continue;
            }

            $service = $activityType === 'Speech' ? $speechService : $abaService;

            // A schedule spanning last week through next week: 2 completed,
            // 1 no-show, 1 cancelled, 2 still upcoming.
            $slots = [
                ['offset' => -7, 'time' => '09:00', 'status' => 'completed'],
                ['offset' => -5, 'time' => '10:00', 'status' => 'completed'],
                ['offset' => -3, 'time' => '09:00', 'status' => 'no_show'],
                ['offset' => -1, 'time' => '11:00', 'status' => 'cancelled'],
                ['offset' => 2, 'time' => '09:00', 'status' => 'scheduled'],
                ['offset' => 5, 'time' => '10:00', 'status' => 'scheduled'],
            ];

            foreach ($slots as $slot) {
                CalendarSession::updateOrCreate(
                    [
                        'therapist_id' => $therapistId,
                        'patient_id' => $lead->id,
                        'session_date' => now()->addDays($slot['offset'])->toDateString(),
                        'start_time' => $slot['time'],
                    ],
                    [
                        'activity_type' => $activityType,
                        'duration_minutes' => 60,
                        'room' => 'Room '.($i + 1),
                        'status' => $slot['status'],
                        'cancel_reason' => $slot['status'] === 'cancelled' ? 'family' : null,
                        'created_by' => $coordinator?->id,
                    ]
                );
            }
        }
    }

    /**
     * 2 invoices off the patients' completed sessions above - one paid in
     * full, one only partly paid - via the real SessionLedger +
     * InvoiceBuilder services, same as the "+ New invoice" button. The
     * fully-paid one deliberately uses the self-pay patient (Omar): with an
     * insurer on the invoice, the family only ever owes their coverage
     * shortfall, so paying that in full still leaves the invoice's overall
     * balance (which also carries the insurer's yet-uncollected share)
     * showing outstanding - "fully paid" only reads as fully paid, balance
     * zero, status "paid", when there's no insurer split to begin with.
     */
    private function seedBilling(): void
    {
        $ledger = app(SessionLedger::class);
        $builder = app(InvoiceBuilder::class);
        $biller = User::where('email', 'kavitha@engagebehavior.com')->first()
            ?? User::whereIn('role', ['FINANCE_STAFF', 'FULL_ADMIN'])->first();

        // [child_name => fully paid?]
        $plan = ['Omar Al Dhaheri' => true, 'Lina Al Kaabi' => false];

        $patients = Patient::whereIn('id', function ($q) use ($plan) {
            $q->select('id')->from('patients')->whereIn('lead_id', function ($q2) use ($plan) {
                $q2->select('id')->from('leads')->whereIn('child_name', array_keys($plan));
            });
        })->with(['lead', 'authorizations'])->get();

        foreach ($patients as $patient) {
            $fullyPaid = $plan[$patient->lead->child_name] ?? false;
            $sessions = $patient->calendarSessions()
                ->whereNull('invoice_id')
                ->where('status', 'completed')
                ->get();

            if ($sessions->isEmpty()) {
                continue;
            }

            $rows = $ledger->rows($patient, $sessions);
            $composed = $builder->compose($patient, $rows);

            if (! $composed['lines']) {
                continue;
            }

            $invoice = $builder->issue($patient, $composed, null, $biller?->id);

            $issueDate = now()->subDays(20);
            $invoice->forceFill(['issue_date' => $issueDate, 'due_date' => $issueDate->copy()->addDays(30)])->save();
            $invoice->claims()->update(['submitted_on' => $issueDate]);

            $familyOwed = round((float) $invoice->patient_responsibility, 2);
            if ($familyOwed <= 0) {
                continue;
            }

            $amount = $fullyPaid ? $familyOwed : round($familyOwed * 0.5, 2);

            $invoice->payments()->create([
                'receipt_number' => DocumentNumbers::nextReceipt(),
                'amount' => $amount,
                'method' => 'Card',
                'received_on' => $issueDate->copy()->addDays(5),
                'reference' => null,
                'recorded_by' => $biller?->id,
            ]);
            $invoice->unsetRelation('payments')->syncPaymentColumns();
        }
    }
}
