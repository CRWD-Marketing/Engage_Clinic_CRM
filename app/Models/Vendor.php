<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A vendor registered through the public website form (/vendor-registration)
 * and managed in CRM -> Vendors. Field set and status list follow the
 * "Vendor Management - CRM" framework document.
 */
class Vendor extends Model
{
    const STATUS_PROSPECT = 'prospect';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_ACTIVE = 'active';
    const STATUS_ON_HOLD = 'on_hold';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_TERMINATED = 'terminated';

    const CATEGORIES = [
        'Clinical Services and Clinical Suppliers',
        'Medical and Therapy Supplies',
        'Office and Administrative Supplies',
        'Cleaning and Housekeeping',
        'Maintenance and Facility Management',
        'IT, Software and Technology',
        'CCTV, Security and Access Control',
        'Fire and Life Safety',
        'Medical Waste Management',
        'Marketing, Advertising and Branding',
        'Printing and Production',
        'Transportation and Logistics',
        'Training and Consultancy',
        'Events and Community Partnerships',
        'Insurance, Legal and Professional Services',
        'Other',
    ];

    /**
     * Categories whose interruption affects patient safety, compliance or
     * continuity of essential services - a registration in one of these is
     * flagged critical on arrival; staff can change the flag afterwards.
     */
    const CRITICAL_CATEGORIES = [
        'Clinical Services and Clinical Suppliers',
        'Medical and Therapy Supplies',
        'Cleaning and Housekeeping',
        'Maintenance and Facility Management',
        'IT, Software and Technology',
        'CCTV, Security and Access Control',
        'Fire and Life Safety',
        'Medical Waste Management',
    ];

    const EMIRATES = ['Abu Dhabi', 'Dubai', 'Sharjah', 'Ajman', 'Umm Al Quwain', 'Ras Al Khaimah', 'Fujairah', 'Outside UAE'];

    const PAYMENT_TERMS = ['Advance payment', 'On delivery', '15 days', '30 days', '45 days', '60 days', '90 days'];

    const PAYMENT_METHODS = ['Bank transfer', 'Cheque', 'Card', 'Cash'];

    protected $fillable = [
        'reference',
        'legal_name', 'trade_name', 'website', 'address', 'city', 'emirate',
        'contact_name', 'position', 'phone', 'email', 'alt_contact', 'finance_email',
        'category', 'category_other', 'primary_service', 'description', 'years_in_business', 'referred_by',
        'pricing', 'monthly_cost', 'payment_terms', 'payment_method', 'vat_registered', 'trn', 'accepts_po', 'contract_start', 'contract_end',
        'bank_name', 'account_holder', 'iban', 'swift',
        'signatory_name', 'declared_at',
        'status', 'is_critical', 'internal_owner_id', 'notes', 'viewed_at', 'ip_address',
    ];

    protected $casts = [
        'monthly_cost' => 'decimal:2',
        'vat_registered' => 'boolean',
        'accepts_po' => 'boolean',
        'is_critical' => 'boolean',
        'contract_start' => 'date',
        'contract_end' => 'date',
        'declared_at' => 'datetime',
        'viewed_at' => 'datetime',
        'iban' => 'encrypted',
    ];

    protected $hidden = ['iban'];

    public function documents()
    {
        return $this->hasMany(VendorDocument::class);
    }

    public function internalOwner()
    {
        return $this->belongsTo(User::class, 'internal_owner_id');
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PROSPECT => 'Prospect',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_TERMINATED => 'Terminated',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? ucfirst((string) $this->status);
    }

    /**
     * "Other" registrations show what the vendor typed instead.
     */
    public function getCategoryLabelAttribute(): string
    {
        return $this->category === 'Other' && $this->category_other ? $this->category_other : $this->category;
    }

    /**
     * Complete / Pending / Expired across the vendor's dated documents
     * (the "Compliance Status" field of the vendor master record).
     */
    public function getComplianceStatusAttribute(): string
    {
        $states = $this->documents->map->state;

        if ($states->contains('expired')) {
            return 'expired';
        }

        return $states->contains('pending') || $states->contains('expiring') ? 'pending' : 'complete';
    }

    public function isNew(): bool
    {
        return $this->viewed_at === null;
    }
}
