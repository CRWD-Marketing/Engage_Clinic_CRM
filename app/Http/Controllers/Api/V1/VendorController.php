<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Vendor\VendorController as WebVendorController;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Http\Request;

/**
 * Vendors for the mobile app: the data vendor/index.blade.php and
 * vendor/show.blade.php are rendered with, as JSON. Saving and deleting are
 * the web actions (they redirect); these answer with the message and the
 * vendor. Documents download through the web action.
 */
class VendorController extends WebVendorController
{
    /** GET /vendors — 20 per page, newest first, with the tab counts and tiles. */
    public function index(Request $request)
    {
        $d = parent::index($request)->getData();
        $page = $d['vendors'];

        return response()->json([
            'vendors' => collect($page->items())->map(fn (Vendor $v) => self::row($v))->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()],
            'statuses' => $d['statuses'],
            'status_counts' => $d['statusCounts'],
            'total_count' => $d['totalCount'],
            'categories' => $d['categories'],
            'stats' => $d['stats'],
            'registration_url' => route('vendor-registration'),
            'can_edit' => $request->user()->levelFor('vendors') !== 'view',
        ]);
    }

    /** GET /vendors/{vendor} — opening it clears the "new" marker, as on the web. */
    public function show(Vendor $vendor)
    {
        $d = parent::show($vendor)->getData();

        return response()->json(self::detail($d['vendor']) + [
            'owners' => $d['owners']->map(fn ($u) => ['id' => $u->id, 'name' => $u->full_name])->values(),
            'neighbours' => $d['neighbours'],
            'statuses' => $d['statuses'],
            'can_edit' => auth()->user()->levelFor('vendors') !== 'view',
        ]);
    }

    /** PATCH /vendors/{vendor} — status, critical flag, internal owner and notes. */
    public function update(Request $request, Vendor $vendor)
    {
        parent::update($request, $vendor);

        return response()->json(['message' => 'Vendor updated.', 'vendor' => self::detail($vendor->fresh(['documents', 'internalOwner']))]);
    }

    /** DELETE /vendors/{vendor} — and its uploaded files. */
    public function destroy(Vendor $vendor)
    {
        parent::destroy($vendor);

        return response()->json(['message' => 'Vendor deleted.']);
    }

    public static function row(Vendor $v): array
    {
        return [
            'id' => $v->id,
            'reference' => $v->reference,
            'legal_name' => $v->legal_name,
            'trade_name' => $v->trade_name,
            'contact_name' => $v->contact_name,
            'email' => $v->email,
            'phone' => $v->phone,
            'category' => $v->category,
            'category_label' => $v->category_label,
            'primary_service' => $v->primary_service,
            'compliance_status' => $v->compliance_status,
            'status' => $v->status,
            'status_label' => $v->status_label,
            'is_critical' => (bool) $v->is_critical,
            'is_new' => $v->isNew(),
            'created_at' => $v->created_at?->toIso8601String(),
        ];
    }

    /** Everything vendor/show.blade.php shows about one vendor. */
    public static function detail(Vendor $v): array
    {
        return self::row($v) + [
            'website' => $v->website,
            'address' => $v->address,
            'city' => $v->city,
            'emirate' => $v->emirate,
            'position' => $v->position,
            'alt_contact' => $v->alt_contact,
            'finance_email' => $v->finance_email,
            'description' => $v->description,
            'years_in_business' => $v->years_in_business,
            'referred_by' => $v->referred_by,
            'pricing' => $v->pricing,
            'monthly_cost' => $v->monthly_cost !== null ? (float) $v->monthly_cost : null,
            'payment_terms' => $v->payment_terms,
            'payment_method' => $v->payment_method,
            'vat_registered' => $v->vat_registered,
            'trn' => $v->trn,
            'accepts_po' => $v->accepts_po,
            'contract_start' => $v->contract_start?->format('Y-m-d'),
            'contract_end' => $v->contract_end?->format('Y-m-d'),
            'bank_name' => $v->bank_name,
            'account_holder' => $v->account_holder,
            // Shown on the vendor page to whoever can open Vendors (Full Admin).
            'iban' => $v->iban ? trim(chunk_split((string) $v->iban, 4, ' ')) : null,
            'swift' => $v->swift,
            'signatory_name' => $v->signatory_name,
            'declared_at' => $v->declared_at?->toIso8601String(),
            'internal_owner_id' => $v->internal_owner_id,
            'internal_owner' => $v->internalOwner?->full_name,
            'notes' => $v->notes,
            'documents' => $v->documents->map(fn (VendorDocument $doc) => [
                'id' => $doc->id,
                'type' => $doc->type,
                'label' => $doc->label,
                'document_number' => $doc->document_number,
                'issue_date' => $doc->issue_date?->format('Y-m-d'),
                'expiry_date' => $doc->expiry_date?->format('Y-m-d'),
                'state' => $doc->state,
                'days_to_expiry' => $doc->daysToExpiry(),
                'file_name' => $doc->file_original_name,
                'file_mime' => $doc->file_mime,
                'file_size' => $doc->file_size,
            ])->values(),
        ];
    }
}
