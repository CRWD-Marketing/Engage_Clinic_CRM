<?php

namespace App\Http\Controllers\Package;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Package;
use App\Models\Service;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * Custom client packages - service, location and funding type all come
     * from Settings; this page just bundles them with hours/rate.
     */
    public function index()
    {
        $packages = Package::with(['service', 'location'])->orderBy('name')->get();
        $services = Service::where('is_active', true)->orderBy('name')->get();
        $locations = Location::where('is_active', true)->orderBy('name')->get();

        return view('package.index', compact('packages', 'services', 'locations'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Package::create($data + ['is_active' => true]);

        return back()->with('success', 'Package created.');
    }

    public function update(Request $request, Package $package)
    {
        $package->update($this->validated($request));

        return back()->with('success', 'Package updated.');
    }

    public function destroy(Package $package)
    {
        $package->delete();

        return back()->with('success', 'Package removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'service_id' => 'nullable|exists:services,id',
            'location_id' => 'nullable|exists:locations,id',
            'funding_type' => 'nullable|string|in:Insurance,Self pay',
            'delivery_mode' => 'nullable|string|in:Home base,Clinic',
            'hours_per_week' => 'nullable|numeric|min:0',
            'rate' => 'nullable|numeric|min:0',
        ]);
    }
}
