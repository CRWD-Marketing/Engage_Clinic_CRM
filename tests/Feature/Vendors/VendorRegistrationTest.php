<?php

namespace Tests\Feature\Vendors;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'FULL_ADMIN', 'department' => 'EXECUTIVE']);
    }

    private function pdf(string $name): UploadedFile
    {
        return UploadedFile::fake()->create($name, 200, 'application/pdf');
    }

    private function validPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'legal_name' => 'Al Noor Facility Services LLC',
            'trade_name' => 'Al Noor',
            'website' => 'https://alnoor.example',
            'address' => 'Office 12, Electra Street',
            'city' => 'Abu Dhabi',
            'emirate' => 'Abu Dhabi',
            'contact_name' => 'Sara Khan',
            'position' => 'Sales Manager',
            'phone' => '+971 50 123 4567',
            'email' => 'sara@alnoor.example',
            'category' => 'Cleaning and Housekeeping',
            'primary_service' => 'Daily cleaning and infection-control services',
            'years_in_business' => 8,
            'pricing' => 'AED 3,500 per month',
            'monthly_cost' => '3500',
            'payment_terms' => '30 days',
            'payment_method' => 'Bank transfer',
            'vat_registered' => 'Yes',
            'trn' => '100123456700003',
            'accepts_po' => 'Yes',
            'contract_start' => '2026-11-01',
            'contract_end' => '2027-10-31',
            'bank_name' => 'First Abu Dhabi Bank',
            'account_holder' => 'Al Noor Facility Services LLC',
            'iban' => 'AE07 0331 2345 6789 0123 456',
            'swift' => 'NBADAEAA',
            'bank_letter' => $this->pdf('bank-letter.pdf'),
            'declaration_accurate' => '1',
            'declaration_confidential' => '1',
            'declaration_review' => '1',
            'signatory_name' => 'Sara Khan',
            'documents' => [
                'trade_licence' => ['file' => $this->pdf('licence.pdf'), 'number' => 'CN-123456', 'issue_date' => '2026-01-10', 'expiry_date' => now()->addYear()->toDateString()],
                'vat_certificate' => ['file' => $this->pdf('vat.pdf'), 'expiry_date' => now()->addDays(10)->toDateString()],
            ],
        ], $overrides);
    }

    public function test_the_form_is_public_and_linked_in_the_footer(): void
    {
        $this->get(route('vendor-registration'))
            ->assertOk()
            ->assertSee('Become an')
            ->assertSee('Fire and Life Safety');

        $this->get(route('contact'))->assertSee(route('vendor-registration'), false);
    }

    public function test_a_submission_is_stored_and_shown_in_the_crm(): void
    {
        $this->postJson(route('vendor-registration.store'), $this->validPayload())
            ->assertCreated()
            ->assertJson(['success' => true]);

        $vendor = Vendor::with('documents')->firstOrFail();
        $this->assertSame(sprintf('ENG-VND-%d-%04d', now()->year, $vendor->id), $vendor->reference);
        $this->assertSame(Vendor::STATUS_UNDER_REVIEW, $vendor->status);
        $this->assertTrue($vendor->is_critical); // cleaning is a critical category
        $this->assertTrue($vendor->isNew());
        $this->assertSame('AE070331234567890123456', $vendor->iban);
        $this->assertNotSame($vendor->iban, DB::table('vendors')->value('iban')); // encrypted at rest

        $this->assertEqualsCanonicalizing(
            ['trade_licence', 'vat_certificate', 'bank_letter'],
            $vendor->documents->pluck('type')->all()
        );
        $vendor->documents->each(fn ($d) => Storage::disk('local')->assertExists($d->file_path));
        $this->assertSame('expiring', $vendor->documents->firstWhere('type', 'vat_certificate')->state);
        $this->assertSame('pending', $vendor->compliance_status);

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('vendors.index'))
            ->assertOk()
            ->assertSee('Al Noor Facility Services LLC')
            ->assertSee($vendor->reference);

        $this->actingAs($admin)->get(route('vendors.show', $vendor))
            ->assertOk()
            ->assertSee('AE07 0331 2345 6789 0123 456')
            ->assertSee('licence.pdf');
        $this->assertFalse($vendor->fresh()->isNew());

        $this->actingAs($admin)
            ->get(route('vendors.document', [$vendor, $vendor->documents->first()]))
            ->assertOk();
    }

    public function test_invalid_submissions_are_rejected(): void
    {
        $payload = $this->validPayload(['iban' => 'AE12', 'trn' => '123']);
        unset($payload['documents']['trade_licence']['file'], $payload['documents']['vat_certificate'], $payload['declaration_review']);
        $payload['documents']['insurance'] = ['file' => $this->pdf('insurance.pdf')]; // no expiry date

        $this->postJson(route('vendor-registration.store'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'iban', 'trn', 'declaration_review',
                'documents.trade_licence.file', 'documents.vat_certificate.file', 'documents.insurance.expiry_date',
            ]);

        $this->assertSame(0, Vendor::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_honeypot_submissions_are_dropped(): void
    {
        $this->postJson(route('vendor-registration.store'), $this->validPayload(['company_fax' => '12345']))
            ->assertCreated();

        $this->assertSame(0, Vendor::count());
    }

    public function test_staff_manage_and_delete_a_vendor(): void
    {
        $this->postJson(route('vendor-registration.store'), $this->validPayload())->assertCreated();
        $vendor = Vendor::firstOrFail();
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('vendors.update', $vendor), [
            'status' => Vendor::STATUS_ACTIVE,
            'is_critical' => '0',
            'internal_owner_id' => $admin->id,
            'notes' => 'Approved by management.',
        ])->assertRedirect(route('vendors.show', $vendor));

        $vendor->refresh();
        $this->assertSame(Vendor::STATUS_ACTIVE, $vendor->status);
        $this->assertFalse($vendor->is_critical);
        $this->assertSame($admin->id, $vendor->internal_owner_id);

        $this->actingAs($admin)->delete(route('vendors.destroy', $vendor))->assertRedirect(route('vendors.index'));
        $this->assertSame(0, Vendor::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_the_crm_pages_are_gated_by_the_vendors_module(): void
    {
        $this->get(route('vendors.index'))->assertRedirect(route('login'));

        $sales = User::factory()->create(['role' => 'SALES_STAFF', 'department' => 'SALES']);
        $this->actingAs($sales)->get(route('vendors.index'))->assertForbidden();
    }
}
