<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Support\Facades\Storage;

class VendorsTest extends ApiTestCase
{
    private function vendor(array $overrides = []): Vendor
    {
        $vendor = Vendor::create($overrides + [
            'legal_name' => 'Gulf Medical Supplies LLC', 'trade_name' => 'GMS', 'contact_name' => 'Rami Haddad', 'email' => 'rami@gms.ae', 'phone' => '+971501234567',
            'category' => 'Medical and Therapy Supplies', 'primary_service' => 'Therapy equipment', 'status' => 'under_review', 'is_critical' => true,
            'iban' => 'AE070331234567890123456', 'declared_at' => now(),
            'address' => 'Office 12, Mussafah', 'city' => 'Abu Dhabi', 'emirate' => 'Abu Dhabi', 'position' => 'Sales Manager', 'pricing' => 'Per quote',
            'payment_terms' => '30 days', 'payment_method' => 'Bank transfer', 'bank_name' => 'ADCB', 'account_holder' => 'Gulf Medical Supplies LLC', 'signatory_name' => 'Rami Haddad',
        ]);
        $vendor->update(['reference' => sprintf('ENG-VND-%d-%04d', now()->year, $vendor->id)]);

        return $vendor;
    }

    public function test_the_list_filters_and_counts(): void
    {
        $this->actingAsEmail('admin@gmail.com');
        $this->vendor();
        $this->vendor(['legal_name' => 'Sparkle Cleaning', 'email' => 'hi@sparkle.ae', 'category' => 'Cleaning and Housekeeping', 'status' => 'active']);

        $this->getJson($this->api('vendors'))->assertOk()
            ->assertJsonStructure([
                'vendors' => [['id', 'reference', 'legal_name', 'trade_name', 'contact_name', 'email', 'phone', 'category', 'category_label', 'primary_service', 'compliance_status', 'status', 'status_label', 'is_critical', 'is_new', 'created_at']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'statuses', 'status_counts', 'total_count', 'categories',
                'stats' => ['new', 'critical', 'documents_expiring', 'contracts_expiring'],
                'registration_url', 'can_edit',
            ])
            ->assertJsonPath('total_count', 2)
            ->assertJsonPath('stats.new', 2)
            ->assertJsonPath('status_counts.active', 1);

        $this->getJson($this->api('vendors').'?status=active')->assertOk()->assertJsonCount(1, 'vendors')->assertJsonPath('vendors.0.legal_name', 'Sparkle Cleaning');
        $this->getJson($this->api('vendors').'?search=sparkle')->assertOk()->assertJsonCount(1, 'vendors')->assertJsonPath('vendors.0.email', 'hi@sparkle.ae');
        $this->getJson($this->api('vendors').'?category=Medical+and+Therapy+Supplies')->assertOk()->assertJsonCount(1, 'vendors');
        $this->getJson($this->api('vendors').'?status=lost')->assertStatus(422);
        $this->getJson($this->api('vendors/count'))->assertOk()->assertJsonPath('count', 2);
    }

    public function test_opening_a_vendor_marks_it_seen_and_shows_documents(): void
    {
        $this->actingAsEmail('admin@gmail.com');
        Storage::fake('local');
        $vendor = $this->vendor();
        Storage::disk('local')->put("vendors/{$vendor->id}/licence.pdf", '%PDF-1.4 licence');
        $doc = VendorDocument::create(['vendor_id' => $vendor->id, 'type' => 'trade_licence', 'document_number' => 'TL-1', 'expiry_date' => now()->addDays(10)->toDateString(),
            'file_path' => "vendors/{$vendor->id}/licence.pdf", 'file_original_name' => 'Trade licence.pdf', 'file_mime' => 'application/pdf', 'file_size' => 2048]);

        $this->getJson($this->api("vendors/{$vendor->id}"))->assertOk()
            ->assertJsonPath('legal_name', 'Gulf Medical Supplies LLC')
            ->assertJsonPath('iban', 'AE07 0331 2345 6789 0123 456')
            ->assertJsonPath('compliance_status', 'pending')
            ->assertJsonPath('documents.0.label', 'Trade Licence')
            ->assertJsonPath('documents.0.state', 'expiring')
            ->assertJsonStructure(['owners' => [['id', 'name']], 'neighbours' => ['newer', 'older'], 'statuses', 'can_edit']);
        $this->assertNotNull($vendor->fresh()->viewed_at);

        $file = $this->get($this->api("vendors/{$vendor->id}/documents/{$doc->id}").'?download=1')->assertOk();
        $this->assertStringContainsString('Trade licence.pdf', $file->headers->get('content-disposition'));
    }

    public function test_status_owner_notes_and_delete(): void
    {
        $this->actingAsEmail('admin@gmail.com');
        $vendor = $this->vendor();
        $owner = User::where('email', 'kavitha@engagebehavior.com')->firstOrFail();

        $this->patchJson($this->api("vendors/{$vendor->id}"), ['status' => 'approved'])->assertStatus(422);
        $this->patchJson($this->api("vendors/{$vendor->id}"), ['status' => 'active', 'is_critical' => false, 'internal_owner_id' => $owner->id, 'notes' => 'Approved after site visit'])->assertOk()
            ->assertJsonPath('message', 'Vendor updated.')
            ->assertJsonPath('vendor.status', 'active')
            ->assertJsonPath('vendor.is_critical', false)
            ->assertJsonPath('vendor.internal_owner_id', $owner->id)
            ->assertJsonPath('vendor.notes', 'Approved after site visit');

        $this->deleteJson($this->api("vendors/{$vendor->id}"))->assertOk()->assertJsonPath('message', 'Vendor deleted.');
        $this->assertDatabaseMissing('vendors', ['id' => $vendor->id]);
    }

    public function test_only_roles_with_vendors_get_in(): void
    {
        $this->actingAsEmail('hr@engagebehavior.com');
        $this->getJson($this->api('vendors'))->assertForbidden();
    }
}
