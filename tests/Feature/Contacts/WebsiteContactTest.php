<?php

namespace Tests\Feature\Contacts;

use App\Models\Contact;
use App\Models\Insurance;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The website's "Send Us a Message" form and Free Consultation booking
 * (POST /api/contacts) - insurance and the preferred consultation slot.
 */
class WebsiteContactTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Salama Al Nuaimi',
            'phone' => '+971501230102',
            'email' => 'salama@example.com',
            'child_name' => 'Hamad',
            'child_age' => '4',
            'interested_in' => 'Speech therapy',
        ], $overrides);
    }

    public function test_contact_form_saves_insurance_and_preferred_slot(): void
    {
        $date = now()->addDays(3)->format('Y-m-d');

        $this->postJson('/api/contacts', $this->payload([
            'insurance' => 'Daman Enhanced',
            'booking_date' => $date,
            'booking_time' => '10:30 AM',
        ]))->assertCreated()->assertJsonPath('success', true);

        $contact = Contact::sole();
        $this->assertSame('Daman Enhanced', $contact->insurance);
        $this->assertSame($date, $contact->booking_date->format('Y-m-d'));
        $this->assertSame('10:30 AM', $contact->booking_time);
        $this->assertTrue($contact->hasBookingSlot());
    }

    public function test_insurance_and_slot_are_optional(): void
    {
        $this->postJson('/api/contacts', $this->payload())->assertCreated();

        $contact = Contact::sole();
        $this->assertNull($contact->insurance);
        $this->assertFalse($contact->hasBookingSlot());
    }

    public function test_a_day_needs_a_time_and_cannot_be_in_the_past(): void
    {
        $this->postJson('/api/contacts', $this->payload(['booking_date' => now()->addDay()->format('Y-m-d')]))
            ->assertStatus(422)->assertJsonValidationErrors('booking_time');

        $this->postJson('/api/contacts', $this->payload(['booking_date' => now()->subDay()->format('Y-m-d'), 'booking_time' => '9:00 AM']))
            ->assertStatus(422)->assertJsonValidationErrors('booking_date');

        $this->postJson('/api/contacts', $this->payload(['insurance' => str_repeat('x', 51)]))
            ->assertStatus(422)->assertJsonValidationErrors('insurance');

        $this->assertSame(0, Contact::count());
    }

    public function test_the_contact_page_lists_active_insurers_plus_self_pay_and_not_sure(): void
    {
        Insurance::create(['name' => 'Thiqa', 'is_active' => true]);
        Insurance::create(['name' => 'Daman Enhanced', 'is_active' => true]);
        Insurance::create(['name' => 'Old Payer', 'is_active' => false]);
        Insurance::create(['name' => 'Self-pay', 'is_active' => true]); // not listed twice

        $html = $this->get(route('contact'))->assertOk()->getContent();

        $this->assertStringContainsString('<option value="Thiqa">Thiqa</option>', $html);
        $this->assertStringContainsString('<option value="Daman Enhanced">Daman Enhanced</option>', $html);
        $this->assertStringNotContainsString('Old Payer', $html);
        $this->assertSame(2, substr_count($html, '<option value="Self-pay">Self-pay (no insurance)</option>'), 'once in the contact form, once in the booking modal');
        $this->assertStringContainsString('<option value="Not sure yet">Not sure yet</option>', $html);
        $this->assertStringContainsString('id="ctCalGrid"', $html);
        $this->assertSame(1, substr_count($html, 'id="bookingOverlay"'));
    }

    private function nextWorkingDay(int $after = 1): string
    {
        $d = now()->addDays($after);
        while ($d->isFriday() || $d->isSaturday()) {
            $d->addDay();
        }

        return $d->format('Y-m-d');
    }

    public function test_staff_can_set_move_and_clear_the_slot_until_the_decision_is_emailed(): void
    {
        $admin = User::factory()->create(['role' => 'FULL_ADMIN', 'department' => 'EXECUTIVE']);
        $this->postJson('/api/contacts', $this->payload())->assertCreated();
        $contact = Contact::sole();
        $day = $this->nextWorkingDay(2);

        $this->actingAs($admin)->patchJson(route('contacts.update', $contact), ['booking_date' => $day, 'booking_time' => '2:30 PM'])
            ->assertOk();
        $this->assertSame($day.' 2:30 PM', $contact->fresh()->booking_date->format('Y-m-d').' '.$contact->fresh()->booking_time);

        // A time outside the consultation list, a closed day, or a date without a time are refused.
        $this->actingAs($admin)->patchJson(route('contacts.update', $contact), ['booking_date' => $day, 'booking_time' => '8:15 PM'])
            ->assertStatus(422)->assertJsonValidationErrors('booking_time');
        $friday = now()->next(\Carbon\Carbon::FRIDAY)->format('Y-m-d');
        $this->actingAs($admin)->patchJson(route('contacts.update', $contact), ['booking_date' => $friday, 'booking_time' => '9:00 AM'])
            ->assertStatus(422)->assertJsonValidationErrors('booking_date');
        $this->actingAs($admin)->patchJson(route('contacts.update', $contact), ['booking_date' => $day])
            ->assertStatus(422)->assertJsonValidationErrors('booking_time');

        // Clearing the date clears the slot.
        $this->actingAs($admin)->patchJson(route('contacts.update', $contact), ['booking_date' => null, 'booking_time' => null])->assertOk();
        $this->assertFalse($contact->fresh()->hasBookingSlot());

        // Once the decision has been emailed, the slot is final.
        $contact->update(['status' => Contact::STATUS_CONTACTED]);
        $this->actingAs($admin)->patchJson(route('contacts.update', $contact), ['booking_date' => $day, 'booking_time' => '9:00 AM'])
            ->assertStatus(422)->assertJsonValidationErrors('booking_date');
    }

    public function test_insurance_is_shown_read_only_in_contacts(): void
    {
        $admin = User::factory()->create(['role' => 'FULL_ADMIN', 'department' => 'EXECUTIVE']);
        Insurance::create(['name' => 'Thiqa', 'is_active' => true]);
        $this->postJson('/api/contacts', $this->payload(['insurance' => 'Daman']))->assertCreated();
        $contact = Contact::sole();

        // What the family picked is shown; staff can't change it from here.
        $this->actingAs($admin)->get(route('contacts.index'))->assertOk()
            ->assertSee('Daman')
            ->assertDontSee('<option value="Thiqa">Thiqa</option>', false)
            ->assertSee('id="ctSlotEditor"', false);

        $this->actingAs($admin)->patchJson(route('contacts.update', $contact), ['insurance' => 'Thiqa'])->assertOk();
        $this->assertSame('Daman', $contact->fresh()->insurance, 'the update endpoint ignores insurance');
    }

    public function test_insurance_carries_over_when_converted_to_a_lead(): void
    {
        $admin = User::factory()->create(['role' => 'FULL_ADMIN', 'department' => 'EXECUTIVE']);
        $this->postJson('/api/contacts', $this->payload(['insurance' => 'Thiqa']))->assertCreated();
        $contact = Contact::sole();
        $contact->update(['status' => Contact::STATUS_APPROVED]); // conversion needs an approved enquiry

        $this->actingAs($admin)->get(route('contacts.index'))->assertOk()->assertSee('Thiqa');
        $this->actingAs($admin)->post(route('contacts.convert-to-lead', $contact))->assertRedirect();

        $this->assertSame('Thiqa', Lead::sole()->insurance);
    }
}
