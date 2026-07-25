<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;

// Public routes (main website)
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Public login page (for regular users)
Route::view('/login', 'login.login')->name('login');

// Admin routes
Route::prefix('Engage-Clinic')->name('admin.')->group(function () {
    Route::get('/login', [AdminController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminController::class, 'login']);
    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
    
    // Protected admin routes (requires authentication)
    Route::middleware(['auth'])->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        
        // Leads page - Updated path
        Route::get('/leads', function () {
            return view('admin.leads.leads');
        })->name('leads');
        
        // WhatsApp page - Updated path
        Route::get('/whatsapp', function () {
            return view('admin.whatsapp.whatsapp');
        })->name('whatsapp');
        
        // Patients page - Updated path
        Route::get('/patients', function () {
            return view('admin.patients.patients');
        })->name('patients');
        
        // Calendar page - Updated path
        Route::get('/calendar', function () {
            return view('admin.calendar.calendar');
        })->name('calendar');
        
        // Therapists page - Updated path
        Route::get('/therapists', function () {
            return view('admin.therapists.therapists');
        })->name('therapists');
        
        // Billing page - Updated path
        Route::get('/billing', function () {
            return view('admin.billing.billing');
        })->name('billing');
        
        // Reports page - Updated path
        Route::get('/reports', function () {
            return view('admin.reports.reports');
        })->name('reports');
    });
});