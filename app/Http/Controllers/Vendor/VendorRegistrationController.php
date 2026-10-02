<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Public "Become an Engage vendor" page (linked from the website footer).
 * Every submission lands in CRM -> Vendors as Under Review.
 */
class VendorRegistrationController extends Controller
{
    /**
     * Per-file cap in KB. Seven uploads have to fit inside PHP's
     * post_max_size (40M on this server), so keep 7 x this below it.
     */
    const MAX_FILE_KB = 5120;

    const UPLOAD_DISK = 'local';

    public function show()
    {
        return view('landing_page.vendor_registration', [
            'categories' => Vendor::CATEGORIES,
            'emirates' => Vendor::EMIRATES,
            'paymentTerms' => Vendor::PAYMENT_TERMS,
            'paymentMethods' => Vendor::PAYMENT_METHODS,
            'documentTypes' => VendorDocument::TYPES,
            'maxFileMb' => (int) (self::MAX_FILE_KB / 1024),
        ]);
    }

    public function store(Request $request)
    {
        // Honeypot: real visitors never see this field, bots fill it in.
        // Answer as if it worked so they don't retry with it empty.
        if ($request->filled('company_fax')) {
            return response()->json(['success' => true, 'reference' => 'ENG-VND-'.now()->year.'-0000'], 201);
        }

        $request->merge(['iban' => strtoupper(preg_replace('/\s+/', '', (string) $request->input('iban')))]);

        $file = ['file', 'mimes:pdf,jpg,jpeg,png', 'max:'.self::MAX_FILE_KB];

        $rules = [
            'legal_name' => 'required|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:120',
            'emirate' => ['required', Rule::in(Vendor::EMIRATES)],

            'contact_name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:40', 'regex:/^(?=(?:\D*\d){9,})[0-9+()\-\s]+$/'],
            'email' => 'required|email|max:255',
            'alt_contact' => 'nullable|string|max:255',
            'finance_email' => 'nullable|email|max:255',

            'category' => ['required', Rule::in(Vendor::CATEGORIES)],
            'category_other' => 'nullable|required_if:category,Other|string|max:255',
            'primary_service' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'years_in_business' => 'nullable|integer|min:0|max:300',
            'referred_by' => 'nullable|string|max:255',

            'pricing' => 'required|string|max:255',
            'monthly_cost' => 'nullable|numeric|min:0|max:9999999999',
            'payment_terms' => ['required', Rule::in(Vendor::PAYMENT_TERMS)],
            'payment_method' => ['required', Rule::in(Vendor::PAYMENT_METHODS)],
            'vat_registered' => 'required|in:Yes,No',
            'trn' => 'nullable|required_if:vat_registered,Yes|digits:15',
            'accepts_po' => 'nullable|in:Yes,No',
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date|after_or_equal:contract_start',

            'bank_name' => 'required|string|max:255',
            'account_holder' => 'required|string|max:255',
            'iban' => ['required', 'string', 'max:34', 'regex:/^(AE\d{21}|(?!AE)[A-Z]{2}\d{2}[A-Z0-9]{10,30})$/'],
            'swift' => ['nullable', 'string', 'regex:/^[A-Za-z0-9]{8}([A-Za-z0-9]{3})?$/'],
            'bank_letter' => ['required', ...$file],

            'declaration_accurate' => 'accepted',
            'declaration_confidential' => 'accepted',
            'declaration_review' => 'accepted',
            'signatory_name' => 'required|string|max:255',

            'documents' => 'nullable|array',
        ];

        $attributes = [
            'legal_name' => 'registered company name',
            'contact_name' => 'full name',
            'phone' => 'mobile number',
            'category_other' => 'category description',
            'primary_service' => 'primary service or product',
            'trn' => 'TRN',
            'iban' => 'IBAN',
            'swift' => 'SWIFT / BIC',
            'bank_letter' => 'bank letter',
            'signatory_name' => 'authorised signatory name',
            'vat_registered' => 'VAT registered',
        ];

        foreach (VendorDocument::TYPES as $type => $meta) {
            $label = strtolower($meta['label']);

            $rules["documents.$type.file"] = match ($type) {
                'trade_licence' => ['required', ...$file],
                'vat_certificate' => ['nullable', 'required_if:vat_registered,Yes', ...$file],
                default => ['nullable', ...$file],
            };
            $rules["documents.$type.number"] = 'nullable|string|max:120';
            $rules["documents.$type.issue_date"] = 'nullable|date';
            // "We record the expiry so we can remind you" only works if the
            // dated documents actually arrive with one.
            $rules["documents.$type.expiry_date"] = $meta['expires']
                ? ['nullable', "required_with:documents.$type.file", 'date', "after_or_equal:documents.$type.issue_date"]
                : ['nullable', 'date'];

            $attributes["documents.$type.file"] = $label;
            $attributes["documents.$type.number"] = "$label number";
            $attributes["documents.$type.issue_date"] = "$label issue date";
            $attributes["documents.$type.expiry_date"] = "$label expiry date";
        }

        $validator = Validator::make($request->all(), $rules, [
            'phone.regex' => 'Enter a full phone number with country code.',
            'iban.regex' => 'Check the IBAN. UAE IBANs are AE followed by 21 digits.',
            'swift.regex' => 'SWIFT / BIC codes are 8 or 11 characters.',
            'documents.trade_licence.file.required' => 'Upload your trade licence.',
            'documents.vat_certificate.file.required_if' => 'Upload your VAT certificate.',
            'documents.*.expiry_date.required_with' => 'Add the expiry date.',
            'declaration_accurate.accepted' => 'Please confirm this declaration.',
            'declaration_confidential.accepted' => 'Please confirm this declaration.',
            'declaration_review.accepted' => 'Please confirm this declaration.',
        ], $attributes);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $stored = [];

        try {
            $vendor = DB::transaction(function () use ($request, $data, &$stored) {
                $vendor = Vendor::create([
                    ...collect($data)->only([
                        'legal_name', 'trade_name', 'website', 'address', 'city', 'emirate',
                        'contact_name', 'position', 'phone', 'email', 'alt_contact', 'finance_email',
                        'category', 'primary_service', 'description', 'years_in_business', 'referred_by',
                        'pricing', 'monthly_cost', 'payment_terms', 'payment_method',
                        'contract_start', 'contract_end',
                        'bank_name', 'account_holder', 'iban', 'signatory_name',
                    ])->all(),
                    'category_other' => $data['category'] === 'Other' ? ($data['category_other'] ?? null) : null,
                    'vat_registered' => $data['vat_registered'] === 'Yes',
                    'trn' => $data['vat_registered'] === 'Yes' ? $data['trn'] : null,
                    'accepts_po' => isset($data['accepts_po']) ? $data['accepts_po'] === 'Yes' : null,
                    'swift' => isset($data['swift']) ? strtoupper($data['swift']) : null,
                    'declared_at' => now(),
                    'status' => Vendor::STATUS_UNDER_REVIEW,
                    'is_critical' => in_array($data['category'], Vendor::CRITICAL_CATEGORIES, true),
                    'ip_address' => $request->ip(),
                ]);

                $vendor->update(['reference' => sprintf('ENG-VND-%d-%04d', now()->year, $vendor->id)]);

                foreach (array_keys(VendorDocument::TYPES) as $type) {
                    if ($upload = $request->file("documents.$type.file")) {
                        $vendor->documents()->create($this->storeFile($upload, $vendor, $stored) + [
                            'type' => $type,
                            'document_number' => $data['documents'][$type]['number'] ?? null,
                            'issue_date' => $data['documents'][$type]['issue_date'] ?? null,
                            'expiry_date' => $data['documents'][$type]['expiry_date'] ?? null,
                        ]);
                    }
                }

                $vendor->documents()->create($this->storeFile($request->file('bank_letter'), $vendor, $stored) + [
                    'type' => VendorDocument::TYPE_BANK_LETTER,
                ]);

                return $vendor;
            });
        } catch (\Throwable $e) {
            // The rows rolled back - don't leave their uploads behind.
            Storage::disk(self::UPLOAD_DISK)->delete($stored);

            throw $e;
        }

        return response()->json([
            'success' => true,
            'reference' => $vendor->reference,
        ], 201);
    }

    /**
     * Files go on the private "local" disk; the CRM's vendors routes are the
     * only way to read them back.
     */
    private function storeFile(UploadedFile $upload, Vendor $vendor, array &$stored): array
    {
        $path = $upload->storeAs(
            "vendors/{$vendor->id}",
            (string) Str::uuid().'.'.strtolower($upload->getClientOriginalExtension()),
            self::UPLOAD_DISK
        );
        $stored[] = $path;

        return [
            'file_path' => $path,
            'file_original_name' => $upload->getClientOriginalName(),
            'file_mime' => $upload->getClientMimeType(),
            'file_size' => $upload->getSize(),
        ];
    }
}
