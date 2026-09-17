<?php

namespace App\Http\Controllers\Contact;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * Display a listing of the contact submissions.
     */
    public function index(Request $request)
    {
        $allContacts = Contact::with('lead')->latest()->get();

        $status = $request->query('status');
        $contacts = ($status && $status !== 'all')
            ? $allContacts->where('status', $status)->values()
            : $allContacts;

        // The details form should open only after the user chooses a row.
        // Defaulting to the first submission made the edit dialog appear as
        // soon as the Contacts page loaded.
        $activeContact = $request->filled('contact')
            ? $allContacts->firstWhere('id', (int) $request->query('contact'))
            : null;

        $statuses = Contact::getStatuses();
        $newCount = $allContacts->where('status', Contact::STATUS_NEW)->count();

        return view('contact.index', compact('contacts', 'activeContact', 'statuses', 'newCount'));
    }

    /**
     * Update a contact submission.
     */
    public function update(Request $request, Contact $contact)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'child_age' => 'nullable|string|max:10',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'interested_in' => 'nullable|string|max:100',
            'message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $contact->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Contact updated successfully!',
            'contact' => $contact,
        ]);
    }

    /**
     * Delete a contact submission.
     */
    public function destroy(Contact $contact)
    {
        // Same reasoning as Leads - Coordinator's access is intake/communication
        // support, not full ownership of the inbox.
        abort_if(auth()->user()->role === 'COORDINATOR', 403, 'Coordinators cannot delete contact submissions.');

        $contact->delete();

        return redirect()
            ->route('contacts.index')
            ->with('success', 'Contact deleted successfully!');
    }

    /**
     * Update a contact's status. Converting is handled separately by
     * convertToLead() - this only covers the manually selectable states
     * (new/approved/rejected/contacted/closed).
     */
    public function updateStatus(Request $request, Contact $contact)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:'.implode(',', array_keys(Contact::getSelectableStatuses())),
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $updates = ['status' => $request->status];

        // Record the approve/reject outcome separately from `status` so it
        // survives once status later moves on to `contacted` (the email step).
        if (in_array($request->status, [Contact::STATUS_APPROVED, Contact::STATUS_REJECTED], true)) {
            $updates['booking_decision'] = $request->status;
        }

        $contact->update($updates);

        return redirect()->route('contacts.index', ['contact' => $contact->id]);
    }

    /**
     * Email the family the approve/reject decision on their consultation
     * booking - mirrors InvoiceController::send(), minus the CC field, since
     * this is a single decision notice rather than a billing document with
     * an optional accounting recipient. Sending is what moves status on to
     * "contacted" - the decision itself only records intent, not outreach.
     */
    public function sendStatusEmail(Request $request, Contact $contact)
    {
        if (! $contact->canSendStatusEmail()) {
            return response()->json([
                'message' => 'Approve or reject this booking before emailing the family.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'to' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        // Same double-send guard as invoice emails - a repeat click must
        // never result in two emails to the family.
        $lock = Cache::lock("contact-status-email-{$contact->id}", 30);
        if (! $lock->get()) {
            return response()->json([
                'message' => 'This email is already being sent.',
                'contact' => $contact->fresh('lead'),
            ]);
        }

        try {
            $body = nl2br(e($request->message));

            try {
                Mail::html('<div style="font: 14px/1.5 Arial, sans-serif; color: #2B3A4C;">'.$body.'</div>', function ($m) use ($request) {
                    $m->to($request->to)->subject($request->subject);
                });
            } catch (\Throwable $e) {
                report($e);

                return response()->json(['message' => 'The email could not be sent: '.$e->getMessage()], 500);
            }

            $contact->update([
                'status' => Contact::STATUS_CONTACTED,
                'status_email_sent_at' => now(),
            ]);

            return response()->json([
                'message' => "Decision emailed to {$request->to}.",
                'contact' => $contact->fresh('lead'),
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Convert a contact submission into a Lead, entering it into the
     * consultation pipeline. The contact row is kept and marked converted,
     * linked to the new lead.
     */
    public function convertToLead(Contact $contact)
    {
        abort_if(auth()->user()->role === 'COORDINATOR', 403, 'Coordinators cannot convert contacts to leads.');

        if (! $contact->canConvertToLead()) {
            return redirect()->route('contacts.index', ['contact' => $contact->id]);
        }

        $lead = Lead::create([
            'child_age' => $contact->child_age,
            'parent_guardian_name' => $contact->name,
            'phone' => $contact->phone,
            'email' => $contact->email,
            'source' => 'Contact Us',
            'interested_in' => $contact->interested_in,
            'notes' => $contact->message ?: '',
        ]);

        $contact->update([
            'status' => Contact::STATUS_CONVERTED,
            'converted_lead_id' => $lead->id,
            'converted_at' => now(),
        ]);

        return redirect()->route('contacts.index', ['contact' => $contact->id]);
    }

    /**
     * Store a contact submission from the public landing page form.
     */
    public function apiStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'child_age' => 'nullable|string|max:10',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'interested_in' => 'nullable|string|max:100',
            'message' => 'nullable|string',
            'booking_date' => 'nullable|date',
            'booking_time' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $contact = Contact::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Message received successfully!',
            'contact' => $contact,
        ], 201);
    }

    /**
     * Get contact count for the sidebar badge.
     */
    public function getContactCount(Request $request)
    {
        $count = Contact::where('status', Contact::STATUS_NEW)->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }
}
