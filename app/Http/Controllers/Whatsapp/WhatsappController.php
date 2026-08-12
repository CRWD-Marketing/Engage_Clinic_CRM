<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\WhatsappContact;
use App\Models\WhatsappMessage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappController extends Controller
{
    /**
     * Display the WhatsApp inbox: the contact list plus the active conversation.
     */
    public function index(Request $request)
    {
        $contacts = WhatsappContact::orderByDesc('last_message_at')->get();

        $activeContact = $request->filled('contact')
            ? $contacts->firstWhere('id', (int) $request->query('contact'))
            : $contacts->first();

        $messages = collect();
        $leadHints = [];

        if ($activeContact) {
            $messages = $activeContact->messages()->orderBy('sent_at')->get();

            if (! $activeContact->lead_id) {
                $leadHints = $this->extractLeadHints($messages);
            }

            if ($activeContact->unread_count > 0) {
                $activeContact->update(['unread_count' => 0]);
            }
        }

        return view('whatsapp.index', compact('contacts', 'activeContact', 'messages', 'leadHints'));
    }

    /**
     * Best-effort scan of a conversation's inbound messages for lead details a family
     * has already volunteered - child's name/age, service + hours interested in, and
     * any insurance provider mentioned - so "Convert to Lead" doesn't start blank.
     */
    private function extractLeadHints($messages): array
    {
        $text = $messages->where('direction', 'inbound')->pluck('body')->filter()->implode(' . ');

        $childName = null;
        $childAge = null;

        if (preg_match('/\bmy\s+(?:son|daughter|child|kid)\s+([A-Z][A-Za-z\'-]+)\s+is\s+(\d{1,2})\b/i', $text, $m)) {
            $childName = $m[1];
            $childAge = (int) $m[2];
        }

        $insurers = [
            'Daman', 'Thiqa', 'ADNIC', 'AXA', 'Bupa', 'Cigna', 'MetLife', 'NextCare',
            'Oman Insurance', 'Al Madallah', 'Almadallah', 'Saico', 'Orient Insurance',
            'Union Insurance', 'National Health Insurance', 'Neuron',
        ];
        $insurance = null;

        foreach ($insurers as $insurer) {
            if (stripos($text, $insurer) !== false) {
                $insurance = $insurer;
                break;
            }
        }

        $services = [
            'ABA' => 'ABA', 'Speech' => 'Speech', 'Occupational Therapy' => 'OT', 'OT' => 'OT',
            'Assessment' => 'Assessment', 'Parent training' => 'Parent training',
        ];
        $service = null;

        foreach ($services as $needle => $label) {
            if (stripos($text, $needle) !== false) {
                $service = $label;
                break;
            }
        }

        $hours = null;

        if (preg_match('/(\d{1,3})\s*(?:hours?|hrs?|h)\b(?:\s*(?:\/|per)\s*week)?/i', $text, $m)) {
            $hours = $m[1].'h/week';
        }

        $interestedIn = trim(collect([$service, $hours])->filter()->implode(' ')) ?: null;

        return [
            'child_name' => $childName,
            'child_age' => $childAge,
            'interested_in' => $interestedIn,
            'insurance' => $insurance,
        ];
    }

    /**
     * Handle the Meta webhook verification handshake (GET).
     * Meta calls this once with hub_mode/hub_verify_token/hub_challenge
     * when you save the Callback URL + Verify Token in the App Dashboard.
     */
    public function verifyWebhook(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * Receive events from Meta (POST): WhatsApp messages/statuses, or Instagram DMs.
     * Both products are subscribed under the same app, so they share this one callback URL.
     */
    public function handleWebhook(Request $request)
    {
        Log::info('Meta webhook payload', $request->all());

        $object = $request->input('object');

        foreach ($request->input('entry', []) as $entry) {
            if ($object === 'instagram') {
                // Instagram's `messages` field webhook: entry[].changes[].value = {sender, recipient, timestamp, message}.
                foreach ($entry['changes'] ?? [] as $change) {
                    if (($change['field'] ?? null) === 'messages') {
                        $this->storeInboundMessengerMessage($change['value'] ?? [], 'instagram');
                    }
                }

                // Older Messenger-style shape some events may still use: entry[].messaging[].
                foreach ($entry['messaging'] ?? [] as $messagingItem) {
                    $this->storeInboundMessengerMessage($messagingItem, 'instagram');
                }

                continue;
            }

            if ($object === 'page') {
                // Facebook Page Messenger webhook: entry[].messaging[] = {sender, recipient, timestamp, message}.
                foreach ($entry['messaging'] ?? [] as $messagingItem) {
                    $this->storeInboundMessengerMessage($messagingItem, 'facebook');
                }

                continue;
            }

            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                foreach ($value['messages'] ?? [] as $message) {
                    $this->storeInboundMessage($message, $value['contacts'][0] ?? null);
                }

                foreach ($value['statuses'] ?? [] as $status) {
                    $this->updateMessageStatus($status);
                }
            }
        }

        return response()->json(['status' => 'received']);
    }

    /**
     * Send an outbound text message via the WhatsApp Cloud API or the Instagram Messaging API,
     * depending on which channel the conversation is on.
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'contact_id' => ['required', 'exists:whatsapp_contacts,id'],
            'message' => ['required', 'string', 'max:4096'],
        ]);

        $contact = WhatsappContact::findOrFail($validated['contact_id']);

        $response = match ($contact->channel) {
            'instagram' => $this->sendInstagramMessage($contact, $validated['message']),
            'facebook' => $this->sendFacebookMessage($contact, $validated['message']),
            default => $this->sendWhatsappMessage($contact, $validated['message']),
        };

        if ($response->failed()) {
            Log::error(ucfirst($contact->channel).' send failed', $response->json() ?? ['status' => $response->status()]);

            return back()->withErrors(['message' => 'Failed to send message.'])->withInput();
        }

        $waMessageId = match ($contact->channel) {
            'instagram', 'facebook' => $response->json('message_id'),
            default => $response->json('messages.0.id'),
        };

        $contact->messages()->create([
            'wa_message_id' => $waMessageId,
            'direction' => 'outbound',
            'type' => 'text',
            'body' => $validated['message'],
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $contact->update([
            'last_message_preview' => $validated['message'],
            'last_message_at' => now(),
        ]);

        return redirect()->route('whatsapp.index', ['contact' => $contact->id]);
    }

    /**
     * Manually create a lead from a conversation, triggered by the "Convert to Lead" button.
     */
    public function convertToLead(WhatsappContact $contact)
    {
        if ($contact->lead_id) {
            return redirect()->route('whatsapp.index', ['contact' => $contact->id]);
        }

        $hints = $this->extractLeadHints($contact->messages()->get());

        $lead = Lead::create([
            'child_name' => $hints['child_name'],
            'child_age' => $hints['child_age'],
            'parent_guardian_name' => $contact->name,
            'phone' => $contact->channel === 'whatsapp' ? $contact->wa_id : null,
            'source' => ucfirst($contact->channel),
            'interested_in' => $hints['interested_in'],
            'insurance' => $hints['insurance'],
            'status' => Lead::STATUS_NEW,
        ]);

        $contact->update(['lead_id' => $lead->id]);

        return redirect()->route('whatsapp.index', ['contact' => $contact->id]);
    }

    /**
     * Create a lead from one specific inbound message, triggered by that message's
     * "Convert to Lead" hover action. The message's own text becomes the lead's notes,
     * while name/age/interested-in/insurance are still drawn from the whole conversation.
     */
    public function convertMessageToLead(WhatsappMessage $message)
    {
        $contact = $message->contact;

        if ($contact->lead_id) {
            return redirect()->route('whatsapp.index', ['contact' => $contact->id]);
        }

        $hints = $this->extractLeadHints($contact->messages()->get());

        $lead = Lead::create([
            'child_name' => $hints['child_name'],
            'child_age' => $hints['child_age'],
            'parent_guardian_name' => $contact->name,
            'phone' => $contact->channel === 'whatsapp' ? $contact->wa_id : null,
            'source' => ucfirst($contact->channel),
            'interested_in' => $hints['interested_in'],
            'insurance' => $hints['insurance'],
            'notes' => $message->body,
            'status' => Lead::STATUS_NEW,
        ]);

        $contact->update(['lead_id' => $lead->id]);

        return redirect()->route('whatsapp.index', ['contact' => $contact->id]);
    }

    private function sendWhatsappMessage(WhatsappContact $contact, string $message)
    {
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        return Http::withToken(config('services.whatsapp.access_token'))
            ->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $contact->wa_id,
                'type' => 'text',
                'text' => ['body' => $message],
            ]);
    }

    private function sendInstagramMessage(WhatsappContact $contact, string $message)
    {
        $igBusinessId = config('services.instagram.business_account_id');

        return Http::withToken(config('services.instagram.access_token'))
            ->post("https://graph.facebook.com/v21.0/{$igBusinessId}/messages", [
                'recipient' => ['id' => $contact->wa_id],
                'message' => ['text' => $message],
            ]);
    }

    private function sendFacebookMessage(WhatsappContact $contact, string $message)
    {
        $apiVersion = config('services.facebook.api_version');

        return Http::withToken(config('services.facebook.page_access_token'))
            ->post("https://graph.facebook.com/{$apiVersion}/me/messages", [
                'recipient' => ['id' => $contact->wa_id],
                'message' => ['text' => $message],
                'messaging_type' => 'RESPONSE',
            ]);
    }

    /**
     * Persist an inbound message, creating the contact (and an auto-captured lead) on first contact.
     */
    private function storeInboundMessage(array $message, ?array $contactPayload): void
    {
        $waId = $message['from'];
        $name = $contactPayload['profile']['name'] ?? null;

        $contact = WhatsappContact::firstOrNew(['wa_id' => $waId]);

        if ($name && ! $contact->name) {
            $contact->name = $name;
        }

        $contact->save();

        $body = match ($message['type'] ?? 'text') {
            'text' => $message['text']['body'] ?? null,
            'button' => $message['button']['text'] ?? null,
            'interactive' => $message['interactive']['button_reply']['title']
                ?? $message['interactive']['list_reply']['title']
                ?? null,
            default => null,
        };

        WhatsappMessage::updateOrCreate(
            ['wa_message_id' => $message['id']],
            [
                'whatsapp_contact_id' => $contact->id,
                'direction' => 'inbound',
                'type' => $message['type'] ?? 'text',
                'body' => $body,
                'status' => 'received',
                'sent_at' => Carbon::createFromTimestamp((int) $message['timestamp']),
            ]
        );

        $contact->update([
            'last_message_preview' => $body ?? '['.($message['type'] ?? 'message').']',
            'last_message_at' => Carbon::createFromTimestamp((int) $message['timestamp']),
            'unread_count' => $contact->unread_count + 1,
        ]);
    }

    /**
     * Update an outbound message's delivery status (sent/delivered/read/failed).
     */
    private function updateMessageStatus(array $status): void
    {
        WhatsappMessage::where('wa_message_id', $status['id'])
            ->update(['status' => $status['status']]);
    }

    /**
     * Persist an inbound Instagram DM or Facebook Page Messenger message, creating the contact
     * (and an auto-captured lead) on first contact.
     * Shape: {sender: {id}, recipient: {id}, timestamp, message: {mid, text}}.
     */
    private function storeInboundMessengerMessage(array $messagingItem, string $channel): void
    {
        // Skip echoes of our own outbound messages and non-message events (delivery/read receipts).
        if (! isset($messagingItem['message']) || ($messagingItem['message']['is_echo'] ?? false)) {
            return;
        }

        $senderId = $messagingItem['sender']['id'];
        $body = $messagingItem['message']['text'] ?? null;
        // Messenger Platform sends `timestamp` in milliseconds, unlike WhatsApp's Cloud API (seconds).
        $sentAt = Carbon::createFromTimestamp(intdiv((int) $messagingItem['timestamp'], 1000));

        $contact = WhatsappContact::firstOrNew(['wa_id' => $senderId]);
        $isNewContact = ! $contact->exists;

        if ($isNewContact) {
            $contact->channel = $channel;
        }

        $contact->save();

        if ($isNewContact) {
            $name = $this->fetchMessengerProfileName($senderId, $channel);

            if ($name) {
                $contact->update(['name' => $name]);
            }
        }

        WhatsappMessage::updateOrCreate(
            ['wa_message_id' => $messagingItem['message']['mid']],
            [
                'whatsapp_contact_id' => $contact->id,
                'direction' => 'inbound',
                'type' => 'text',
                'body' => $body,
                'status' => 'received',
                'sent_at' => $sentAt,
            ]
        );

        $contact->update([
            'last_message_preview' => $body ?? '[message]',
            'last_message_at' => $sentAt,
            'unread_count' => $contact->unread_count + 1,
        ]);
    }

    /**
     * Look up an Instagram or Facebook user's display name via the Graph API
     * (not included in the webhook payload).
     */
    private function fetchMessengerProfileName(string $psid, string $channel): ?string
    {
        $token = $channel === 'facebook'
            ? config('services.facebook.page_access_token')
            : config('services.instagram.access_token');

        $response = Http::withToken($token)
            ->get("https://graph.facebook.com/v21.0/{$psid}", [
                'fields' => $channel === 'facebook' ? 'first_name,last_name' : 'name,username',
            ]);

        if (! $response->successful()) {
            return null;
        }

        if ($channel === 'facebook') {
            $name = trim(($response->json('first_name') ?? '').' '.($response->json('last_name') ?? ''));

            return $name !== '' ? $name : null;
        }

        return $response->json('name') ?? $response->json('username');
    }

    /**
     * Instagram redirects here with ?code=... after the account owner approves business login.
     * Exchanges that code for a short-lived token, then a long-lived one (~60 days), for this exact app.
     */
    public function instagramOAuthCallback(Request $request)
    {
        if ($request->filled('error')) {
            return response('Instagram authorization was not completed: '.$request->query('error_description', $request->query('error')), 400);
        }

        if (! $request->filled('code')) {
            return response('Missing authorization code.', 400);
        }

        $shortLived = Http::asForm()->post('https://api.instagram.com/oauth/access_token', [
            'client_id' => config('services.instagram.app_id'),
            'client_secret' => config('services.instagram.app_secret'),
            'grant_type' => 'authorization_code',
            'redirect_uri' => route('instagram.callback'),
            'code' => $request->query('code'),
        ]);

        if ($shortLived->failed()) {
            Log::error('Instagram short-lived token exchange failed', $shortLived->json() ?? ['status' => $shortLived->status()]);

            return response('Failed to exchange authorization code: '.$shortLived->body(), 400);
        }

        $longLived = Http::get('https://graph.instagram.com/access_token', [
            'grant_type' => 'ig_exchange_token',
            'client_secret' => config('services.instagram.app_secret'),
            'access_token' => $shortLived->json('access_token'),
        ]);

        if ($longLived->failed()) {
            Log::error('Instagram long-lived token exchange failed', $longLived->json() ?? ['status' => $longLived->status()]);

            return response('Got a short-lived token but failed to exchange it for a long-lived one: '.$longLived->body(), 400);
        }

        Log::info('Instagram OAuth completed', [
            'ig_user_id' => $shortLived->json('user_id'),
            'expires_in' => $longLived->json('expires_in'),
        ]);

        return response()->json([
            'message' => 'Success. Copy access_token into INSTAGRAM_ACCESS_TOKEN and ig_user_id into INSTAGRAM_BUSINESS_ACCOUNT_ID in .env.',
            'ig_user_id' => $shortLived->json('user_id'),
            'access_token' => $longLived->json('access_token'),
            'expires_in_seconds' => $longLived->json('expires_in'),
        ]);
    }
}
