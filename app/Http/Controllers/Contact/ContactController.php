<?php

namespace App\Http\Controllers\Contact;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Lead;
use Illuminate\Http\Request;
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

        $activeContact = $request->filled('contact')
            ? $allContacts->firstWhere('id', (int) $request->query('contact'))
            : $contacts->first();

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
     * convertToLead() - this only covers the manual new/contacted/closed states.
     */
    public function updateStatus(Request $request, Contact $contact)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:new,contacted,closed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $contact->update(['status' => $request->status]);

        return redirect()->route('contacts.index', ['contact' => $contact->id]);
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
            'source' => 'Contact Us',
            'interested_in' => $contact->interested_in,
            'notes' => ($contact->email ? "Email: {$contact->email}\n" : '').($contact->message ?: ''),
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
