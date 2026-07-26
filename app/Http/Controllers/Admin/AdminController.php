<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

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

    public function profile()
    {
        if (!Auth::check()) {
            return redirect()->route('admin.login');
        }

        $user = Auth::user();
        return view('admin.profile.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user->update($validated);

        return back()->with('success', 'Profile updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully!');
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
            'OTHER_STAFF', // For Invoice and Quotation Only
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

        $userRole = Auth::user()->role;
        
        // Check if user's role matches any allowed role
        if (!in_array($userRole, $roles)) {
            abort(403, 'You do not have permission to access this page.');
        }
    }
}