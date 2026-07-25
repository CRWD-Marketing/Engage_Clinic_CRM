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

        if (Auth::guard('web')->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/engage-clinic-admin/dashboard');
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
        return view('admin.leads.leads');
    }

    public function whatsapp()
    {
        return view('admin.whatsapp.whatsapp');
    }

    public function patients()
    {
        return view('admin.patients.patients');
    }

    public function calendar()
    {
        return view('admin.calendar.calendar');
    }

    public function therapists()
    {
        return view('admin.therapists.therapists');
    }

    public function billing()
    {
        return view('admin.billing.billing');
    }

    public function reports()
    {
        return view('admin.reports.reports');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/engage-clinic-admin/login');
    }
}