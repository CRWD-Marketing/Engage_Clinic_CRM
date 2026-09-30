<?php

namespace App\Http\Controllers\Contact;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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

        // Per-status totals for the filter dropdown, counted off the unfiltered
        // set so every option keeps showing its own total while a filter is on.
        $totalCount = $allContacts->count();
        $statusCounts = collect($statuses)->mapWithKeys(
            fn ($label, $key) => [$key => $allContacts->where('status', $key)->count()]
        )->all();

        $consultationTimes = Contact::CONSULTATION_TIMES;

        return view('contact.index', compact('contacts', 'activeContact', 'statuses', 'newCount', 'totalCount', 'statusCounts', 'consultationTimes'));
    }

    /**
     * Update a contact submission.
     */
    public function update(Request $request, Contact $contact)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'child_name' => 'nullable|string|max:255',
            'child_age' => 'nullable|string|max:10',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'interested_in' => 'nullable|string|max:100',
            'message' => 'nullable|string',
            // Staff set or move the requested consultation from the Contacts dialog.
            'booking_date' => 'nullable|date|after_or_equal:today',
            'booking_time' => ['nullable', 'required_with:booking_date', Rule::in(Contact::CONSULTATION_TIMES)],
        ], [
            'booking_date.after_or_equal' => 'Pick today or a later date.',
            'booking_time.required_with' => 'Choose a time for the consultation.',
            'booking_time.in' => 'Choose one of the consultation times.',
        ]);

        $validator->after(function ($v) use ($request, $contact) {
            if ($request->has('booking_date') && ! $contact->isSlotEditable()) {
                $v->errors()->add('booking_date', 'The decision has already been emailed, so this slot can no longer be changed.');
            }
            if ($request->has('booking_date') && $request->filled('booking_date')
                && in_array(\Carbon\Carbon::parse($request->input('booking_date'))->dayOfWeek, [\Carbon\Carbon::FRIDAY, \Carbon\Carbon::SATURDAY], true)) {
                $v->errors()->add('booking_date', 'The clinic is closed on Fridays and Saturdays.');
            }        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        if (array_key_exists('booking_date', $data) && ! $data['booking_date']) {
            $data['booking_time'] = null; // clearing the date clears the slot
        }
        $contact->update($data);

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
            'child_name' => $contact->child_name,
            'child_age' => $contact->child_age,
            'parent_guardian_name' => $contact->name,
            'phone' => $contact->phone,
            'email' => $contact->email,
            'source' => 'Contact Us',
            'interested_in' => $contact->interested_in,
            'insurance' => $contact->insurance,
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
            'child_name' => 'nullable|string|max:255',
            'child_age' => 'nullable|string|max:10',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'interested_in' => 'nullable|string|max:100',
            'insurance' => 'nullable|string|max:50',
            'message' => 'nullable|string',
            'booking_date' => 'nullable|date|after_or_equal:today',
            'booking_time' => ['nullable', 'required_with:booking_date', Rule::in(Contact::CONSULTATION_TIMES)],
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
