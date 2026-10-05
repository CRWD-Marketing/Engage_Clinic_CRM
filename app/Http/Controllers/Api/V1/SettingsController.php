<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Settings\SettingsController as WebSettingsController;
use App\Models\Insurance;
use App\Models\Location;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Settings (services, locations, insurances) for the mobile app. Every
 * action is the web controller's own - it validates and redirects back -
 * and these answer with the message and the changed item instead.
 */
class SettingsController extends WebSettingsController
{
    /** GET /settings — setting/index.blade.php's three lists, by name. */
    public function index()
    {
        $d = parent::index()->getData();

        return response()->json([
            'services' => $d['services']->map(fn ($m) => self::item($m))->values(),
            'locations' => $d['locations']->map(fn ($m) => self::item($m))->values(),
            'insurances' => $d['insurances']->map(fn ($m) => self::item($m))->values(),
        ]);
    }

    public function storeService(Request $request)
    {
        parent::storeService($request);

        return $this->done('Service added.', Service::latest('id')->first(), 201);
    }

    public function updateService(Request $request, Service $service)
    {
        parent::updateService($request, $service);

        return $this->done('Service updated.', $service->fresh());
    }

    public function toggleService(Service $service)
    {
        parent::toggleService($service);

        return $this->toggled($service->fresh());
    }

    public function destroyService(Service $service)
    {
        parent::destroyService($service);

        return response()->json(['message' => 'Service removed.']);
    }

    public function storeLocation(Request $request)
    {
        parent::storeLocation($request);

        return $this->done('Location added.', Location::latest('id')->first(), 201);
    }

    public function updateLocation(Request $request, Location $location)
    {
        parent::updateLocation($request, $location);

        return $this->done('Location updated.', $location->fresh());
    }

    public function toggleLocation(Location $location)
    {
        parent::toggleLocation($location);

        return $this->toggled($location->fresh());
    }

    public function destroyLocation(Location $location)
    {
        parent::destroyLocation($location);

        return response()->json(['message' => 'Location removed.']);
    }

    public function storeInsurance(Request $request)
    {
        parent::storeInsurance($request);

        return $this->done('Insurance added.', Insurance::latest('id')->first(), 201);
    }

    public function updateInsurance(Request $request, Insurance $insurance)
    {
        parent::updateInsurance($request, $insurance);

        return $this->done('Insurance updated.', $insurance->fresh());
    }

    public function toggleInsurance(Insurance $insurance)
    {
        parent::toggleInsurance($insurance);

        return $this->toggled($insurance->fresh());
    }

    public function destroyInsurance(Insurance $insurance)
    {
        parent::destroyInsurance($insurance);

        return response()->json(['message' => 'Insurance removed.']);
    }

    private function done(string $message, Model $item, int $status = 200)
    {
        return response()->json(['message' => $message, 'item' => self::item($item)], $status);
    }

    /** The web toggle has no message; inactive items drop out of the pickers. */
    private function toggled(Model $item)
    {
        return $this->done($item->is_active ? "{$item->name} is active." : "{$item->name} is inactive — it no longer shows in pickers.", $item);
    }

    public static function item(Model $m): array
    {
        return ['id' => $m->id, 'name' => $m->name, 'is_active' => (bool) $m->is_active]
            + ($m instanceof Insurance ? ['default_coverage_percent' => (int) $m->default_coverage_percent] : []);
    }
}
