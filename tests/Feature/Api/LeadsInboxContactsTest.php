<?php

namespace Tests\Feature\Api;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\Patient;
use App\Models\WhatsappContact;

class LeadsInboxContactsTest extends ApiTestCase
{
    // ---- Leads ---------------------------------------------------------

    public function test_the_leads_board_has_leads_and_option_lists(): void
    {
        $this->actingAsEmail('info@engagebehavior.com');

        $response = $this->getJson($this->api('leads'))->assertOk()
            ->assertJsonStructure([
                'leads' => [['id', 'child_name', 'status', 'assigned_to', 'assigned_to_name', 'intake_steps_complete', 'has_patient', 'follow_up_due_at']],
                'assignable_users' => [['id', 'name']],
                'insurance_options',
                'intake_options' => ['clinicians' => [['id', 'name']], 'insurers', 'services', 'locations' => [['id', 'name']],
                    'packages' => [['id', 'name', 'location_id', 'summary']]],
            ]);
        $this->assertCount(Lead::count(), $response->json('leads'));
        $this->assertSame('Not sure yet', $response->json('insurance_options.0'));

        $converted = Patient::firstOrFail()->lead_id;
        $row = collect($response->json('leads'))->firstWhere('id', $converted);
        $this->assertTrue($row['has_patient']);
        $this->assertArrayNotHasKey('patient', $row);
    }

    public function test_a_lead_can_be_created_moved_noted_and_opened(): void
    {
        $owner = $this->actingAsEmail('info@engagebehavior.com');

        $lead = $this->postJson($this->api('leads'), ['child_name' => 'Api Child', 'parent_guardian_name' => 'Api Parent', 'phone' => '+971500000009', 'source' => 'Website'])
            ->assertCreated()->assertJsonPath('lead.child_name', 'Api Child')->assertJsonPath('lead.has_patient', false)->json('lead');
        $base = "leads/{$lead['id']}";

        // Contacted needs an owner.
        $this->patchJson($this->api("$base/status"), ['status' => 'contacted'])->assertStatus(422)
            ->assertJsonPath('message', 'A lead must have an owner before it can move to Contacted.');
        $this->putJson($this->api($base), ['assigned_to' => $owner->id, 'status' => 'contacted'])->assertOk()
            ->assertJsonPath('lead.status', 'contacted')->assertJsonPath('lead.assigned_to', $owner->id);

        $this->postJson($this->api("$base/notes"), ['body' => 'Called the parent.'])->assertCreated()->assertJsonPath('note.body', 'Called the parent.');

        $this->getJson($this->api($base))->assertOk()
            ->assertJsonStructure(['success', 'lead' => ['has_patient'], 'notes_log' => [['id', 'body', 'author_name']], 'assignment_log', 'agreed_packages', 'assessment_clinician_name']);

        // Not enrolled yet, so it can't be converted.
        $this->postJson($this->api("$base/convert-to-patient"))->assertStatus(422)
            ->assertJsonPath('message', 'Only enrolled leads can be converted to a patient.');
    }

    public function test_a_lead_saved_with_blank_optional_fields_does_not_break_the_board(): void
    {
        $this->actingAsEmail('info@engagebehavior.com');

        // What the app's New Lead form sends when only the required fields are filled in.
        $this->postJson($this->api('leads'), [
            'child_name' => 'Blank Value Child', 'child_age' => '', 'parent_guardian_name' => 'Parent', 'phone' => '', 'source' => null,
            'interested_in' => '', 'insurance' => null, 'estimated_value' => '', 'notes' => '', 'assigned_to' => null, 'follow_up_due_at' => '',
        ])->assertCreated()->assertJsonPath('lead.estimated_value', null);

        $this->getJson($this->api('leads'))->assertOk();
    }

    public function test_converting_an_enrolled_lead_returns_the_new_patient_id(): void
    {
        $this->actingAsEmail('info@engagebehavior.com');

        $lead = Lead::create(['child_name' => 'Ready Child', 'status' => Lead::STATUS_ENROLLED]
            + array_fill_keys(array_values(Lead::INTAKE_STEPS), now()));

        $response = $this->postJson($this->api("leads/{$lead->id}/convert-to-patient"))->assertOk()->assertJsonPath('message', 'Converted to patient.');
        $this->assertSame(Patient::where('lead_id', $lead->id)->value('id'), $response->json('patient_id'));

        // A second attempt is refused: the lead already has a patient record.
        $this->postJson($this->api("leads/{$lead->id}/convert-to-patient"))->assertStatus(422)
            ->assertJsonPath('message', 'This lead has already been converted to a patient.');

        // A role without the Leads module can't reach the pipeline at all.
        $this->actingAsEmail('indira@engagebehavior.com');
        $this->getJson($this->api('leads'))->assertForbidden();
    }

    // ---- Inbox ---------------------------------------------------------

    public function test_the_inbox_lists_conversations_and_opens_a_thread(): void
    {
        $this->actingAsEmail('coordinator@engagebehavior.com');
        $contact = WhatsappContact::whereNull('lead_id')->firstOrFail();
        $contact->update(['unread_count' => 3]);

        $this->getJson($this->api('inbox'))->assertOk()
            ->assertJsonStructure([['id', 'wa_id', 'channel', 'name', 'last_message_preview', 'unread_count', 'ai_state', 'last_responder']]);

        $this->getJson($this->api("inbox/{$contact->id}"))->assertOk()
            ->assertJsonStructure([
                'contact' => ['id', 'last_responder'],
                'messages' => [['id', 'direction', 'type', 'body', 'display_body', 'responder_label', 'status_label', 'sent_at']],
                'family' => ['child_name', 'child_age', 'interested_in', 'source', 'first_contact_at', 'insurance', 'in_pipeline'],
            ])
            ->assertJsonPath('family.in_pipeline', false);
        $this->assertSame(0, $contact->fresh()->unread_count);
    }

    public function test_sending_polling_ai_state_and_convert_to_lead(): void
    {
        $user = $this->actingAsEmail('coordinator@engagebehavior.com');
        $contact = WhatsappContact::whereNull('lead_id')->firstOrFail();
        $before = (int) $contact->messages()->max('id');

        $this->postJson($this->api('inbox/send'), ['contact_id' => $contact->id, 'message' => ''])->assertStatus(422);
        $sent = $this->postJson($this->api('inbox/send'), ['contact_id' => $contact->id, 'message' => 'Hello from the app'])->assertOk()
            ->assertJsonPath('message.body', 'Hello from the app')
            ->assertJsonPath('message.direction', 'outbound')
            ->assertJsonPath('message.sent_by_user_id', $user->id);
        $this->assertSame($sent->json('message.id'), $sent->json('latest_message_id'));

        $poll = $this->getJson($this->api('inbox/poll')."?contact={$contact->id}&after={$before}")->assertOk()
            ->assertJsonStructure(['contacts', 'messages', 'latest_message_id', 'statuses' => [['id', 'status', 'label', 'error']]]);
        $this->assertContains($sent->json('message.id'), array_column($poll->json('messages'), 'id'));
        $this->getJson($this->api('inbox/poll'))->assertOk()->assertJsonPath('messages', []);

        $this->postJson($this->api("inbox/{$contact->id}/ai-state"), ['ai_state' => 'nope'])->assertStatus(422);
        $this->postJson($this->api("inbox/{$contact->id}/ai-state"), ['ai_state' => 'human_takeover'])->assertOk()
            ->assertJsonPath('contact.ai_state', 'human_takeover')->assertJsonPath('contact.needs_human_attention', false);

        $converted = $this->postJson($this->api("inbox/{$contact->id}/convert-to-lead"))->assertOk()
            ->assertJsonStructure(['contact' => ['lead_id'], 'lead' => ['id', 'status', 'source']]);
        $this->assertSame($converted->json('lead.id'), $contact->fresh()->lead_id);
    }

    // ---- Contacts ------------------------------------------------------

    public function test_contacts_are_listed_with_their_flags(): void
    {
        $this->actingAsEmail('info@engagebehavior.com');

        $response = $this->getJson($this->api('contacts').'?status=new')->assertOk()
            ->assertJsonStructure([
                'contacts' => [['id', 'name', 'status', 'booking_date', 'booking_time', 'can_edit_slot', 'can_send_email', 'can_convert']],
                'statuses' => ['new', 'approved', 'rejected', 'contacted', 'converted', 'closed'],
                'new_count',
                'consultation_times',
            ]);
        // The status query is ignored: the app always gets every submission.
        $this->assertCount(Contact::count(), $response->json('contacts'));

        $this->getJson($this->api('contacts/count'))->assertOk()->assertJsonPath('count', Contact::where('status', 'new')->count());

        $this->actingAsEmail('indira@engagebehavior.com');
        $this->getJson($this->api('contacts'))->assertForbidden();
    }

    public function test_the_decision_flow_for_a_submission(): void
    {
        $contact = Contact::create(['name' => 'Api Parent', 'phone' => '+971500000010', 'email' => 'parent@example.com', 'status' => Contact::STATUS_NEW]);
        $base = "contacts/{$contact->id}";
        $monday = now()->next('Monday')->toDateString();

        $this->actingAsEmail('coordinator@engagebehavior.com');

        $this->patchJson($this->api($base), ['booking_date' => now()->next('Friday')->toDateString(), 'booking_time' => '9:00 AM'])->assertStatus(422);
        $this->patchJson($this->api($base), ['booking_date' => $monday, 'booking_time' => '1:30 PM'])->assertOk()
            ->assertJsonPath('contact.booking_time', '1:30 PM')->assertJsonPath('contact.can_edit_slot', true);

        $this->postJson($this->api("$base/send-email"), ['to' => 'parent@example.com', 'subject' => 's', 'message' => 'm'])->assertStatus(422)
            ->assertJsonPath('message', 'Approve or reject this booking before emailing the family.');
        $this->patchJson($this->api("$base/status"), ['status' => 'converted'])->assertStatus(422);
        $this->patchJson($this->api("$base/status"), ['status' => 'approved'])->assertOk()
            ->assertJsonPath('status', 'approved')->assertJsonPath('booking_decision', 'approved')->assertJsonPath('can_send_email', true);

        // Coordinators can't convert or delete.
        $this->postJson($this->api("$base/convert-to-lead"))->assertForbidden();
        $this->deleteJson($this->api($base))->assertForbidden();

        $this->postJson($this->api("$base/send-email"), ['to' => 'parent@example.com', 'subject' => 'Confirmed', 'message' => 'See you then.'])->assertOk()
            ->assertJsonPath('contact.status', 'contacted')->assertJsonPath('contact.can_edit_slot', false);

        $this->actingAsEmail('info@engagebehavior.com');
        $converted = $this->postJson($this->api("$base/convert-to-lead"))->assertOk()->assertJsonPath('status', 'converted');
        $this->assertSame('Contact Us', Lead::findOrFail($converted->json('converted_lead_id'))->source);

        $this->deleteJson($this->api($base))->assertOk()->assertJsonPath('message', 'Contact deleted successfully!');
        $this->assertNull(Contact::find($contact->id));
    }
}
