<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.login.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors([
            'email' => 'Invalid credentials.',
        ])->onlyInput('email');
    }

    public function dashboard()
    {
        if (!Auth::check()) {
            return redirect()->route('admin.login');
        }

        return view('admin.dashboard.dashboard');
    }

    public function leads()
    {
        $this->authorizeRoles([
            'FULL_ADMIN',
            'SALES',
        ]);

        return view('admin.leads.leads');
    }

    public function whatsapp()
    {
        $this->authorizeRoles([
            'FULL_ADMIN',
            'SALES',
            'COORDINATOR',
        ]);

        return view('admin.whatsapp.whatsapp');
    }

    public function patients()
    {
        $this->authorizeRoles([
            'FULL_ADMIN',
            'CLINICAL_SUPERVISOR',
            'THERAPIST',
            'COORDINATOR',
        ]);

        return view('admin.patients.patients');
    }

    public function calendar()
    {
        $this->authorizeRoles([
            'FULL_ADMIN',
            'COORDINATOR',
            'THERAPIST',
            'CLINICAL_SUPERVISOR',
        ]);

        return view('admin.calendar.calendar');
    }

    public function therapists()
    {
        $this->authorizeRoles([
            'FULL_ADMIN',
            'CLINICAL_SUPERVISOR',
        ]);

        return view('admin.therapists.therapists');
    }

    public function billing()
    {
        $this->authorizeRoles([
            'FULL_ADMIN',
            'FINANCE',
            'CUSTOM', // Example: Invoice & Quotation only
        ]);

        return view('admin.billing.billing');
    }

    public function reports()
    {
        $this->authorizeRoles([
            'FULL_ADMIN',
        ]);

        return view('admin.reports.reports');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Check if the authenticated user has one of the allowed roles.
     */
    private function authorizeRoles(array $roles)
    {
        if (!Auth::check()) {
            redirect()->route('admin.login')->send();
        }

        if (!in_array(Auth::user()->role, $roles)) {
            abort(403, 'You do not have permission to access this page.');
        }
    }
}