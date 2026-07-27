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
                return view('dashboard.admin', [
                    'user' => $user
                ]);

            case 'HR_STAFF':
                return view('dashboard.hr', [
                    'user' => $user
                ]);

            case 'SALES_STAFF':
                return view('dashboard.sales', [
                    'user' => $user
                ]);

            case 'CLINICAL_SUPERVISOR':
            case 'THERAPIST':
                return view('dashboard.clinical', [
                    'user' => $user
                ]);

            case 'FINANCE_STAFF':
                return view('dashboard.finance', [
                    'user' => $user
                ]);

            default:
                return view('dashboard.staff', [
                    'user' => $user
                ]);

        }
    }
}