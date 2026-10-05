<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * CRM -> Vendors: every registration submitted from the website's
 * vendor registration form.
 */
class VendorController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['all', ...array_keys(Vendor::getStatuses())])],
            'search' => 'nullable|string|max:120',
            'category' => ['nullable', Rule::in(Vendor::CATEGORIES)],
        ]);
        $status = $filters['status'] ?? 'all';

        // Terminated vendors stay out of the list unless their own tab is opened.
        $query = Vendor::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status), fn ($q) => $q->where('status', '!=', Vendor::STATUS_TERMINATED))
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
                $q->where(fn ($q) => $q
                    ->where('legal_name', 'like', $like)
                    ->orWhere('trade_name', 'like', $like)
                    ->orWhere('reference', 'like', $like)
                    ->orWhere('contact_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('primary_service', 'like', $like));
            });

        $vendors = $query->with('documents')->latest()->latest('id')->paginate(20)->withQueryString();

        $counts = Vendor::toBase()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $statusCounts = collect(Vendor::getStatuses())->mapWithKeys(fn ($label, $key) => [$key => (int) ($counts[$key] ?? 0)])->all();

        $warnBy = now()->addDays(VendorDocument::EXPIRY_WARNING_DAYS)->toDateString();

        return view('vendor.index', [
            'vendors' => $vendors,
            'filters' => $filters + ['status' => 'all'],
            'statuses' => Vendor::getStatuses(),
            'statusCounts' => $statusCounts,
            'totalCount' => array_sum($statusCounts) - $statusCounts[Vendor::STATUS_TERMINATED],
            'hasVendors' => array_sum($statusCounts) > 0,
            'categories' => Vendor::CATEGORIES,
            'stats' => [
                'new' => Vendor::whereNull('viewed_at')->count(),
                'critical' => Vendor::where('is_critical', true)->count(),
                // Expired documents count too - they need renewing just the same.
                'documents_expiring' => VendorDocument::whereNotNull('expiry_date')->where('expiry_date', '<=', $warnBy)
                    ->whereHas('vendor', fn ($q) => $q->where('status', '!=', Vendor::STATUS_TERMINATED))->count(),
                'contracts_expiring' => Vendor::whereNotNull('contract_end')->whereBetween('contract_end', [now()->toDateString(), $warnBy])
                    ->where('status', '!=', Vendor::STATUS_TERMINATED)->count(),
            ],
        ]);
    }

    public function show(Vendor $vendor)
    {
        $vendor->load(['documents', 'internalOwner']);

        // First open by anyone who can act on it clears the "new" marker.
        if ($vendor->isNew() && auth()->user()->levelFor('vendors') !== 'view') {
            $vendor->forceFill(['viewed_at' => now()])->saveQuietly();
        }

        return view('vendor.show', [
            'vendor' => $vendor,
            'statuses' => Vendor::getStatuses(),
            'owners' => User::where('is_active', true)->orderBy('first_name')->get(['id', 'first_name', 'middle_name', 'last_name']),
            'neighbours' => [
                'newer' => Vendor::where('id', '>', $vendor->id)->min('id'),
                'older' => Vendor::where('id', '<', $vendor->id)->max('id'),
            ],
        ]);
    }

    /**
     * Status, critical flag, internal owner and notes - the fields staff
     * manage after a registration comes in.
     */
    public function update(Request $request, Vendor $vendor)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Vendor::getStatuses()))],
            'is_critical' => 'nullable|boolean',
            'internal_owner_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:5000',
        ]);

        $vendor->update([
            'status' => $data['status'],
            'is_critical' => (bool) ($data['is_critical'] ?? false),
            'internal_owner_id' => $data['internal_owner_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('vendors.show', $vendor)->with('success', 'Vendor updated.');
    }

    public function destroy(Vendor $vendor)
    {
        Storage::disk(VendorRegistrationController::UPLOAD_DISK)->deleteDirectory("vendors/{$vendor->id}");

        $vendor->delete();

        return redirect()->route('vendors.index')->with('success', 'Vendor deleted.');
    }

    /**
     * Open a document in the browser (?download=1 saves it instead). The
     * files live on the private disk, so this gated route is the only way in.
     */
    public function document(Request $request, Vendor $vendor, VendorDocument $document)
    {
        $disk = Storage::disk(VendorRegistrationController::UPLOAD_DISK);

        abort_unless($document->vendor_id === $vendor->id && $disk->exists($document->file_path), 404);

        return $request->boolean('download')
            ? $disk->download($document->file_path, $document->file_original_name)
            : $disk->response($document->file_path, $document->file_original_name);
    }

    /**
     * Unopened-registration count for the sidebar badge.
     */
    public function count()
    {
        return response()->json(['success' => true, 'count' => Vendor::whereNull('viewed_at')->count()]);
    }
}
