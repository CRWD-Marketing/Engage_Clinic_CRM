<?php

use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\CalendarController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\InboxController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Controllers\Api\V1\NoteReviewController;
use App\Http\Controllers\Api\V1\PatientController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\TherapistController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Billing\BulkRunController;
use App\Http\Controllers\Billing\ClaimController;
use App\Http\Controllers\Billing\PreAuthController;
use App\Http\Controllers\Notification\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API module routes
|--------------------------------------------------------------------------
| Loaded inside the `auth:sanctum` group of routes/api.php. Each module
| carries the same `feature:` middleware as its web routes, so access rules
| (module access, and "view only" blocking every write) are identical.
*/

Route::get('dashboard', [DashboardController::class, 'index'])->middleware('feature:dashboard');

// The bell: new leads, new website submissions, unread conversations and "you were assigned" alerts.
// These are the web's own actions (already JSON); each feed is limited to the modules the user has.
Route::get('notifications', [NotificationController::class, 'index']);
Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
Route::post('notifications/{id}/read', [NotificationController::class, 'read']);

Route::middleware('feature:calendar')->prefix('calendar')->group(function () {
    Route::get('my-week', [CalendarController::class, 'myWeek']);
    Route::get('feed', [CalendarController::class, 'feed']);
    Route::get('booking-options', [CalendarController::class, 'bookingOptions']);
    Route::get('leave/impact', [CalendarController::class, 'leaveImpact']);
    Route::post('leave', [CalendarController::class, 'markLeave']);
    Route::delete('leave/{leave}', [CalendarController::class, 'removeLeave']);
    Route::post('/', [CalendarController::class, 'store']);
    Route::get('{calendarSession}', [CalendarController::class, 'show']);
    Route::put('{calendarSession}', [CalendarController::class, 'update']);
    Route::post('{calendarSession}/supervision', [CalendarController::class, 'supervise']);
    Route::delete('{calendarSession}/supervision', [CalendarController::class, 'unsupervise']);
    Route::patch('{calendarSession}/therapist-note', [CalendarController::class, 'therapistNote']);
});

Route::middleware('feature:patients')->group(function () {
    Route::get('patients', [PatientController::class, 'index']);
    Route::post('patients', [PatientController::class, 'store']);
    Route::get('patients/create-options', [PatientController::class, 'createOptions']);
    Route::get('patients/{patient}', [PatientController::class, 'show']);
    Route::put('patients/{patient}', [PatientController::class, 'update']);
    Route::post('patients/{patient}/notes', [PatientController::class, 'addNote']);
    Route::post('patients/{patient}/goals/today', [PatientController::class, 'updateGoalsForToday']);
    Route::post('patients/{patient}/documents', [PatientController::class, 'storeDocument']);
    Route::delete('patients/{patient}/documents/{document}', [PatientController::class, 'destroyDocument']);

    // Session-note review (sign-off and flags): Clinical Supervisor and Full Admin only.
    Route::get('patient-notes/review', [NoteReviewController::class, 'index']);
    Route::post('patient-notes/sign-off', [NoteReviewController::class, 'signOff']);
    Route::post('patient-notes/{note}/flag', [NoteReviewController::class, 'flag']);
    Route::delete('patient-notes/{note}/flag', [NoteReviewController::class, 'unflag']);
});

Route::middleware('feature:leads')->prefix('leads')->group(function () {
    Route::get('/', [LeadController::class, 'board']);
    Route::post('/', [LeadController::class, 'store']);
    Route::get('{lead}', [LeadController::class, 'show']);
    Route::put('{lead}', [LeadController::class, 'update']);
    Route::patch('{lead}/status', [LeadController::class, 'updateStatus']);
    Route::post('{lead}/notes', [LeadController::class, 'addNote']);
    Route::post('{lead}/restore', [LeadController::class, 'restore']);
    Route::post('{lead}/convert-to-patient', [LeadController::class, 'convertToPatient']);
});

Route::middleware('feature:whatsapp')->prefix('inbox')->group(function () {
    Route::get('/', [InboxController::class, 'index']);
    Route::get('poll', [InboxController::class, 'poll']);
    Route::post('send', [InboxController::class, 'send'])->middleware('throttle:20,1');
    Route::get('{contact}', [InboxController::class, 'thread']);
    Route::post('{contact}/ai-state', [InboxController::class, 'updateAiState']);
    Route::post('{contact}/convert-to-lead', [InboxController::class, 'convertToLead']);
});

Route::middleware('feature:contacts')->prefix('contacts')->group(function () {
    Route::get('/', [ContactController::class, 'index']);
    Route::get('count', [ContactController::class, 'getContactCount']);
    Route::patch('{contact}', [ContactController::class, 'update']);
    Route::patch('{contact}/status', [ContactController::class, 'updateStatus']);
    Route::post('{contact}/send-email', [ContactController::class, 'sendStatusEmail']);
    Route::post('{contact}/convert-to-lead', [ContactController::class, 'convertToLead']);
    Route::delete('{contact}', [ContactController::class, 'destroy']);
});

Route::get('therapists', [TherapistController::class, 'index'])->middleware('feature:therapists');

Route::get('reports', [ReportController::class, 'index'])->middleware('feature:reports');

// Billing & insurance. Raising and changing money documents needs the `create_invoice`
// action (checked in the web controller); a view-only level blocks every write.
Route::middleware('feature:billing')->prefix('billing')->group(function () {
    Route::get('/', [BillingController::class, 'index']);
    // New invoice: the client's delivered sessions, a preview of the document, then issue.
    Route::get('patients/{patient}/ledger', [InvoiceController::class, 'ledger']);
    Route::post('patients/{patient}/top-up', [InvoiceController::class, 'topUp']);
    Route::get('patients/{patient}/statement', [InvoiceController::class, 'statementJson']);
    Route::get('patients/{patient}/statement/pdf', [InvoiceController::class, 'statementPdf']);
    Route::post('invoices/preview', [InvoiceController::class, 'preview']);
    Route::post('invoices', [InvoiceController::class, 'store']);
    Route::get('invoices/{invoice}', [InvoiceController::class, 'data']);
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf']);
    Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'storePayment']);
    Route::post('invoices/{invoice}/credit', [InvoiceController::class, 'storeCredit']);
    Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void']);
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send']);
    // Insurance claims and pre-authorizations.
    Route::patch('claims/{claim}', [ClaimController::class, 'update']);
    Route::post('pre-authorizations', [PreAuthController::class, 'store']);
    Route::patch('pre-authorizations/{preAuthorization}', [PreAuthController::class, 'update']);
    // Month-end run: one invoice per family for every unbilled delivered session in a period.
    Route::get('bulk-run', [BulkRunController::class, 'preview']);
    Route::post('bulk-run', [BulkRunController::class, 'issue']);
});

// User Management (users/*). Create, update and delete are the web CreateAccount actions;
// only a Full Admin may delete, or hand out the Full Admin and Clinical Supervisor roles.
Route::middleware('feature:users')->prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('{user}', [UserController::class, 'show']);
    Route::put('{user}', [UserController::class, 'update']);
    Route::delete('{user}', [UserController::class, 'destroy']);
});
