<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Service;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Clinic setup - the service names and service zones (locations)
     * available when building a client package.
     */
    public function index()
    {
        $services = Service::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();

        return view('setting.index', compact('services', 'locations'));
    }

    public function storeService(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255']);

        Service::create([
            'name' => $data['name'],
            'default_rate' => 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Service added.');
    }

    public function updateService(Request $request, Service $service)
    {
        $data = $request->validate(['name' => 'required|string|max:255']);

        $service->update(['name' => $data['name']]);

        return back()->with('success', 'Service updated.');
    }

    public function toggleService(Service $service)
    {
        $service->update(['is_active' => ! $service->is_active]);

        return back();
    }

    public function destroyService(Service $service)
    {
        $service->delete();

        return back()->with('success', 'Service removed.');
    }

    public function storeLocation(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255']);

        Location::create([
            'name' => $data['name'],
            'is_active' => true,
        ]);

        return back()->with('success', 'Location added.');
    }

    public function updateLocation(Request $request, Location $location)
    {
        $data = $request->validate(['name' => 'required|string|max:255']);

        $location->update(['name' => $data['name']]);

        return back()->with('success', 'Location updated.');
    }

    public function toggleLocation(Location $location)
    {
        $location->update(['is_active' => ! $location->is_active]);

        return back();
    }

    public function destroyLocation(Location $location)
    {
        $location->delete();

        return back()->with('success', 'Location removed.');
    }
}
