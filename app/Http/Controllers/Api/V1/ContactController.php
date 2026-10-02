<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Contact\ContactController as WebContactController;
use App\Models\Contact;
use Illuminate\Http\Request;

/**
 * Website submissions (contact form and booking widget) for the mobile app.
 * Each action runs the web controller's own logic and answers with the
 * submission as JSON where the web redirects back to the page.
 */
class ContactController extends WebContactController
{
    /** GET /contacts — every submission, newest first; the app filters by status itself. */
    public function index(Request $request)
    {
        $request->query->remove('status');
        $data = parent::index($request)->getData();

        return response()->json([
            'contacts' => $data['contacts']->map(fn (Contact $contact) => $this->contactRow($contact))->values(),
            'statuses' => $data['statuses'],
            'new_count' => $data['newCount'],
            'consultation_times' => $data['consultationTimes'],
        ]);
    }

    /** PATCH /contacts/{contact} — set, move or remove the consultation slot. */
    public function update(Request $request, Contact $contact)
    {
        $response = parent::update($request, $contact);

        if ($response->getStatusCode() === 200) {
            $response->setData(['success' => true, 'message' => 'Contact updated successfully!', 'contact' => $this->contactRow($contact->fresh())]);
        }

        return $response;
    }

    /** PATCH /contacts/{contact}/status — any status except "converted", which only convertToLead sets. */
    public function updateStatus(Request $request, Contact $contact)
    {
        $request->validate([
            'status' => 'required|string|in:'.implode(',', array_keys(Contact::getSelectableStatuses())),
        ]);

        parent::updateStatus($request, $contact);

        return response()->json($this->contactRow($contact->fresh()));
    }

    /** POST /contacts/{contact}/send-email — emails the decision and moves the submission to "contacted". */
    public function sendStatusEmail(Request $request, Contact $contact)
    {
        $response = parent::sendStatusEmail($request, $contact);

        if ($response->getStatusCode() === 200) {
            $data = $response->getData(true);
            $response->setData(['message' => $data['message'], 'contact' => $this->contactRow($contact->fresh())]);
        }

        return $response;
    }

    /** POST /contacts/{contact}/convert-to-lead — unchanged when the submission isn't eligible. */
    public function convertToLead(Contact $contact)
    {
        parent::convertToLead($contact);

        return response()->json($this->contactRow($contact->fresh()));
    }

    /** DELETE /contacts/{contact} */
    public function destroy(Contact $contact)
    {
        parent::destroy($contact);

        return response()->json(['message' => 'Contact deleted successfully!']);
    }

    /** The submission plus the model checks the web dialog relies on. */
    private function contactRow(Contact $contact): array
    {
        return $contact->attributesToArray() + [
            'can_edit_slot' => $contact->isSlotEditable(),
            'can_send_email' => $contact->canSendStatusEmail(),
            'can_convert' => $contact->canConvertToLead(),
        ];
    }
}
