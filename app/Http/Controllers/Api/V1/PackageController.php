<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Package\PackageController as WebPackageController;
use App\Models\Package;
use Illuminate\Http\Request;

/**
 * Packages for the mobile app, with the web controller's own validation.
 * The web actions answer with a redirect and a flash message; these answer
 * with that message and the package.
 */
class PackageController extends WebPackageController
{
    /** GET /packages — package/index.blade.php's data. */
    public function index()
    {
        $d = parent::index()->getData();

        return response()->json([
            'packages' => $d['packages']->map(fn (Package $p) => self::row($p))->values(),
            'services' => $d['services']->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
            'locations' => $d['locations']->map(fn ($l) => ['id' => $l->id, 'name' => $l->name])->values(),
            'funding_types' => ['Insurance', 'Self pay'],
            'delivery_modes' => ['Home base', 'Clinic'],
        ]);
    }

    /** POST /packages */
    public function store(Request $request)
    {
        $package = Package::create($this->validated($request) + ['is_active' => true]);

        return response()->json(['message' => 'Package created.', 'package' => self::row($package->load(['service', 'location']))], 201);
    }

    /** PUT /packages/{id} */
    public function update(Request $request, Package $package)
    {
        parent::update($request, $package);

        return response()->json(['message' => 'Package updated.', 'package' => self::row($package->fresh(['service', 'location']))]);
    }

    /** DELETE /packages/{id} */
    public function destroy(Package $package)
    {
        parent::destroy($package);

        return response()->json(['message' => 'Package removed.']);
    }

    public static function row(Package $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'service_id' => $p->service_id,
            'service' => $p->service?->name,
            'location_id' => $p->location_id,
            'location' => $p->location?->name,
            'funding_type' => $p->funding_type,
            'delivery_mode' => $p->delivery_mode,
            'hours_per_week' => (float) $p->hours_per_week,
            'rate' => (float) $p->rate,
            'total_excl_vat' => $p->total_excl_vat,
            'is_active' => (bool) $p->is_active,
            'summary' => $p->summaryLabel(),
        ];
    }
}
