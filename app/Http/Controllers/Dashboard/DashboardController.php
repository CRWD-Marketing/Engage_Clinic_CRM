<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        switch ($user->role) {

            case 'FULL_ADMIN':
                return view('dashboard.admin', compact('user'));

            case 'HR_STAFF':
                return view('dashboard.hr_staff', compact('user'));

            case 'SALES_STAFF':
                return view('dashboard.sales_staff', compact('user'));

            case 'COORDINATOR':
                return view('dashboard.coordinator', compact('user'));

            case 'FINANCE_STAFF':
                return view('dashboard.finance_staff', compact('user'));

            case 'CLINICAL_SUPERVISOR':
                return view('dashboard.clinical_supervisor', compact('user'));

            case 'THERAPIST':
                return view('dashboard.therapist', compact('user'));

            case 'OTHER_STAFF':
                return view('dashboard.other_staff', compact('user'));

            default:
                abort(403, 'Unauthorized.');
        }
    }
}