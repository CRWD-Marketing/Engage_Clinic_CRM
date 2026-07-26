<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\LeadController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Public login page (for regular users)
Route::view('/login', 'login.login')->name('login');

/*
|--------------------------------------------------------------------------
| Admin Routes (Engage-Clinic prefix)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    // Authentication routes (public)
    Route::get('/login', [AdminController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminController::class, 'login']);
    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');

    //profile area
    Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
    Route::put('/profile', [AdminController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AdminController::class, 'updatePassword'])->name('profile.password');
    
    // Protected admin routes (requires authentication)
    Route::middleware(['auth'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        
        // Lead Management Routes
        Route::prefix('leads')->name('leads.')->group(function () {
            Route::get('/', [LeadController::class, 'index'])->name('index');
            Route::post('/', [LeadController::class, 'store'])->name('store');
            Route::get('/create', [LeadController::class, 'create'])->name('create');
            Route::get('/{lead}', [LeadController::class, 'show'])->name('show');
            Route::get('/{lead}/edit', [LeadController::class, 'edit'])->name('edit');
            Route::put('/{lead}', [LeadController::class, 'update'])->name('update');
            Route::patch('/{lead}/status', [LeadController::class, 'updateStatus'])->name('update-status');
            Route::delete('/{lead}', [LeadController::class, 'destroy'])->name('destroy');
        });
        
        // Alias for leads index (for sidebar compatibility)
        Route::get('/leads', [LeadController::class, 'index'])->name('leads');
        
        // WhatsApp page
        Route::get('/whatsapp', function () {
            return view('admin.whatsapp.whatsapp');
        })->name('whatsapp');
        
        // Patients page
        Route::get('/patients', function () {
            return view('admin.patients.patients');
        })->name('patients');
        
        // Calendar page
        Route::get('/calendar', function () {
            return view('admin.calendar.calendar');
        })->name('calendar');
        
        // Therapists page
        Route::get('/therapists', function () {
            return view('admin.therapists.therapists');
        })->name('therapists');
        
        // Billing page
        Route::get('/billing', function () {
            return view('admin.billing.billing');
        })->name('billing');
        
        // Reports page
        Route::get('/reports', function () {
            return view('admin.reports.reports');
        })->name('reports');
    });
});

/*
|--------------------------------------------------------------------------
| API Routes (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('api')->name('api.')->group(function () {
    Route::post('leads', [LeadController::class, 'apiStore'])->name('leads.store');
});