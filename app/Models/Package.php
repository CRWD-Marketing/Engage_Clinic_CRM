<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'name',
        'service_id',
        'location_id',
        'funding_type',
        'delivery_mode',
        'hours_per_week',
        'rate',
        'is_active',
    ];

    protected $casts = [
        'hours_per_week' => 'decimal:1',
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Hours × rate, excluding VAT - VAT is applied later, at billing time.
     */
    public function getTotalExclVatAttribute(): float
    {
        return (float) $this->hours_per_week * (float) $this->rate;
    }

    /**
     * Which calendar activity_type codes this package's service actually
     * matches. The service is picked from the billing catalogue (e.g. "ABA
     * therapy — 1:1 session (120 min)") rather than the short activity_type
     * code CalendarSession stores ("ABA") - same substring rule
     * PatientAuthorization::matchingActivityTypes() and SessionLedger::coversType()
     * use, so a package lines up with the same sessions billing already does.
     */
    public function matchingActivityTypes(): array
    {
        $label = $this->service?->name;
        if (! $label) {
            return [];
        }

        return array_values(array_filter(
            CalendarSession::THERAPY_TYPES,
            fn ($type) => stripos($label, $type) !== false || stripos($type, $label) !== false
        ));
    }

    /**
     * "ABA therapy · Home base · 30 h/wk · AED 337/hr" - the caption line shown
     * under a package's name wherever it's picked from a list.
     */
    public function summaryLabel(): string
    {
        $parts = array_filter([
            $this->service?->name,
            $this->delivery_mode,
            $this->hours_per_week ? rtrim(rtrim($this->hours_per_week, '0'), '.').' h/wk' : null,
            $this->rate ? 'AED '.rtrim(rtrim($this->rate, '0'), '.').'/hr' : null,
        ]);

        return implode(' · ', $parts);
    }
}
