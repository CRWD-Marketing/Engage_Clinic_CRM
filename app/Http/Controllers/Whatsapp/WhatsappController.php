<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAiEmployeeReply;
use App\Models\AiEmployeeSettings;
use App\Models\Lead;
use App\Models\WhatsappContact;
use App\Models\WhatsappMessage;
use App\Services\Messaging\OutboundMessagingResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class WhatsappController extends Controller
{
    /**
     * Messenger's quick-tap "like" (thumbs-up) button sends one of these fixed
     * sticker ids depending on how many times it's tapped (small/medium/large).
     * These are Meta's own long-standing, publicly documented constants.
     */
    private const LIKE_STICKER_IDS = ['369239263222822', '369239383222811', '369239343222815'];

    public function __construct(private OutboundMessagingResolver $messagingResolver) {}

    /**
     * Display the WhatsApp inbox: the contact list plus the active conversation.
     */
    public function index(Request $request)
    {
        $contacts = WhatsappContact::with('latestMessage.sentBy')->orderByDesc('last_message_at')->get();

        $activeContact = $request->filled('contact')
            ? $contacts->firstWhere('id', (int) $request->query('contact'))
            : $contacts->first();

        $messages = collect();
        $leadHints = [];

        if ($activeContact) {
            $messages = $activeContact->messages()->with('sentBy')->orderBy('sent_at')->get();

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
     * Polled by the inbox every few seconds so new messages and contact updates
     * (unread counts, previews, reordering, brand-new conversations) show up without
     * a manual refresh. Returns rendered HTML fragments (reusing the same partials
     * as the initial page load) rather than raw JSON, so the two never drift apart.
     */
    public function poll(Request $request)
    {
        $activeContactId = $request->filled('contact') ? (int) $request->query('contact') : null;
        $afterMessageId = (int) $request->query('after', 0);

        $contacts = WhatsappContact::with('latestMessage.sentBy')->orderByDesc('last_message_at')->get();
        $contactsHtml = $contacts->map(fn ($contact) => view('whatsapp.partials.contact_row', [
            'contact' => $contact,
            'activeContactId' => $activeContactId,
        ])->render())->implode('');

        $messagesHtml = '';
        $latestMessageId = $afterMessageId;
        $statuses = [];

        if ($activeContactId) {
            $activeContact = $contacts->firstWhere('id', $activeContactId);

            if ($activeContact) {
                // Bubbles already on screen need their status kept current -
                // "Sending" only becomes "Sent" once the delivery that started
                // after the send response comes back, which is always after the
                // bubble was drawn. Only outbound messages carry a status.
                $statuses = $activeContact->messages()
                    ->where('direction', 'outbound')
                    ->orderByDesc('id')
                    ->limit(30)
                    ->get(['id', 'status', 'send_error'])
                    ->map(fn ($m) => [
                        'id' => $m->id,
                        'status' => $m->status,
                        'label' => WhatsappMessage::statusLabel($m->status),
                        'tick' => WhatsappMessage::tickIcon($m->status),
                        'error' => $m->send_error,
                    ])
                    ->values()
                    ->all();
                $newMessages = $activeContact->messages()->with('sentBy')->where('id', '>', $afterMessageId)->orderBy('sent_at')->get();

                if ($newMessages->isNotEmpty()) {
                    // Whatever was last shown on screen before this batch, so the
                    // shared date-divider logic can tell whether the first new
                    // message here needs one - without this, a message that
                    // arrives after the conversation has been open a while (or
                    // into a new day) would render with no divider at all.
                    $previousMessage = $activeContact->messages()
                        ->where('id', '<=', $afterMessageId)
                        ->orderByDesc('sent_at')
                        ->first();

                    $messagesHtml = view('whatsapp.partials.messages', [
                        'messages' => $newMessages,
                        'activeContact' => $activeContact,
                        'previousSentAt' => $previousMessage?->sent_at,
                    ])->render();

                    // max(id), not the sent_at-ordered last item's id: a webhook
                    // delivered slightly out of order (Meta doesn't guarantee
                    // delivery order) can insert an older-timestamped message
                    // after a newer one already exists, giving it a higher id
                    // despite an earlier sent_at. Cursoring on the sent_at-order
                    // "last" id would then regress the cursor backwards, causing
                    // the next poll to re-fetch and re-render messages already
                    // on screen.
                    $latestMessageId = (int) $newMessages->max('id');

                    // The conversation is open in front of the user right now, so
                    // anything that just arrived counts as read immediately.
                    if ($activeContact->unread_count > 0) {
                        $activeContact->update(['unread_count' => 0]);
                        $contactsHtml = $contacts->map(fn ($contact) => view('whatsapp.partials.contact_row', [
                            'contact' => $contact->id === $activeContact->id ? $activeContact->fresh() : $contact,
                            'activeContactId' => $activeContactId,
                        ])->render())->implode('');
                    }
                }
            }
        }

        return response()->json([
            'contacts_html' => $contactsHtml,
            'messages_html' => $messagesHtml,
            'latest_message_id' => $latestMessageId,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Best-effort scan of a conversation's inbound messages for lead details a family
     * has already volunteered - child's name/age, service interested in, and any
     * insurance provider mentioned. The Family details panel now shows these
     * read-only (no manual entry), so this is the only way those fields ever get
     * filled in before "Convert to Lead" - it needs to catch what it reasonably
     * can, not just the one rigid phrasing.
     */
    protected function extractLeadHints($messages): array
    {
        $text = $messages->where('direction', 'inbound')->pluck('body')->filter()->implode(' . ');

        $childName = null;
        $childAge = null;

        // Checked most-specific first: a pattern that also captures the age
        // wins over a name-only one, so a later "she is 3" isn't needed on
        // top of it.
        $namePatterns = [
            '/\bmy\s+(?:son|daughter|child|kid)\s+([A-Z][A-Za-z\'-]+)\s+is\s+(\d{1,2})\b/i',
            '/\bmy\s+(?:son|daughter|child|kid)(?:\'s)?\s+name\s+is\s+([A-Z][A-Za-z\'-]+)\b/i',
            '/\b(?:his|her)\s+name\s+is\s+([A-Z][A-Za-z\'-]+)\b/i',
            '/\bmy\s+(?:son|daughter|child|kid),?\s+([A-Z][A-Za-z\'-]+),/i',
        ];

        foreach ($namePatterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $childName = $m[1];
                $childAge = isset($m[2]) ? (int) $m[2] : null;
                break;
            }
        }

        if ($childAge === null && preg_match('/\b(?:he|she)\s+is\s+(\d{1,2})\b/i', $text, $m)) {
            $childAge = (int) $m[1];
        }

        $insurers = [
            'Daman Enhanced', 'Daman', 'Thiqa', 'ADNIC', 'AXA', 'Bupa', 'Cigna', 'MetLife', 'NextCare',
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

        // Longest/most specific phrase wins - checked before its shorter
        // substring (e.g. "diagnostic assessment" before bare "assessment"),
        // so the detected label is the more informative one when both appear.
        $services = [
            'early intervention' => 'Early intervention',
            'diagnostic assessment' => 'Diagnostic assessment',
            'combined program' => 'Combined program',
            'occupational therapy' => 'Occupational therapy',
            'speech therapy' => 'Speech therapy',
            'speech' => 'Speech therapy',
            'aba therapy' => 'ABA therapy',
            'aba' => 'ABA therapy',
            'parent training' => 'Parent training',
            'assessment' => 'Diagnostic assessment',
            ' ot ' => 'Occupational therapy',
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
        if (! $this->verifySignature($request)) {
            Log::warning('Rejected Meta webhook POST with invalid/missing X-Hub-Signature-256.');

            return response('Invalid signature', 403);
        }

        // Logged as a pre-encoded JSON string rather than passed as the array context -
        // Monolog's normalizer silently truncates nested arrays past 9 levels deep
        // ("Over 9 levels deep, aborting normalization"), and these payloads
        // (entry > messaging > message > attachments > payload > url) hit that
        // ceiling exactly, hiding the very data you need to debug attachments/stickers.
        Log::info('Meta webhook payload: '.json_encode($request->all()));

        $object = $request->input('object');

        Log::info('[Messaging] Incoming event received', ['object' => $object, 'entry_count' => count($request->input('entry', []))]);
        Log::info('[Messaging] Platform identified', ['platform' => match ($object) {
            'instagram' => 'instagram',
            'page' => 'facebook',
            default => 'whatsapp',
        }]);

        foreach ($request->input('entry', []) as $entry) {
            if ($object === 'instagram') {
                // Instagram's `messages` field webhook: entry[].changes[].value = {sender, recipient, timestamp, message}.
                foreach ($entry['changes'] ?? [] as $change) {
                    if (($change['field'] ?? null) === 'messages') {
                        $this->safely(function () use ($change) {
                            $this->dispatchAiReplyIfEligible($this->storeInboundMessengerMessage($change['value'] ?? [], 'instagram'));
                        });
                    }
                }

                // Older Messenger-style shape some events may still use: entry[].messaging[].
                foreach ($entry['messaging'] ?? [] as $messagingItem) {
                    $this->safely(function () use ($messagingItem) {
                        $this->dispatchAiReplyIfEligible($this->storeInboundMessengerMessage($messagingItem, 'instagram'));
                    });
                }

                continue;
            }

            if ($object === 'page') {
                // Facebook Page Messenger webhook: entry[].messaging[] = {sender, recipient, timestamp, message}.
                foreach ($entry['messaging'] ?? [] as $messagingItem) {
                    $this->safely(function () use ($messagingItem) {
                        $this->dispatchAiReplyIfEligible($this->storeInboundMessengerMessage($messagingItem, 'facebook'));
                    });
                }

                continue;
            }

            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                foreach ($value['messages'] ?? [] as $message) {
                    $this->safely(function () use ($message, $value) {
                        $this->dispatchAiReplyIfEligible($this->storeInboundMessage($message, $value['contacts'][0] ?? null));
                    });
                }

                foreach ($value['statuses'] ?? [] as $status) {
                    $this->safely(fn () => $this->updateMessageStatus($status));
                }
            }
        }

        return response()->json(['status' => 'received']);
    }

    /**
     * Verifies Meta's HMAC signature on the raw POST body using the app secret,
     * so this endpoint (necessarily CSRF-exempt and unauthenticated for Meta to
     * reach it) can't be forged into feeding arbitrary payloads to the AI Employee.
     * Uses getContent() (the exact raw bytes Meta signed), not $request->all() -
     * any re-encoding would produce a different HMAC and always fail verification.
     *
     * Checked against both app secrets, not just Facebook's: this app registers
     * Instagram under its own separate app ("Instagram API with Instagram Login",
     * see sendInstagramMessage()/fetchMessengerProfile()) with its own app secret,
     * while WhatsApp + Facebook Messenger are signed under the Meta/Facebook app.
     * Accepting either keeps all three product types verified without needing to
     * branch on $request->input('object') before the signature check runs.
     */
    private function verifySignature(Request $request): bool
    {
        $signatureHeader = $request->header('X-Hub-Signature-256');

        if (! $signatureHeader || ! str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $body = $request->getContent();

        foreach ([config('services.facebook.app_secret'), config('services.instagram.app_secret')] as $secret) {
            if (! $secret) {
                continue;
            }

            $expected = 'sha256='.hash_hmac('sha256', $body, (string) $secret);

            if (hash_equals($expected, $signatureHeader)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Queues the AI Employee for a freshly stored inbound message, if it's a type
     * the AI is allowed to respond to. Called from inside the same safely() closure
     * that stores the message, so a dispatch failure can't crash the webhook batch.
     */
    private function dispatchAiReplyIfEligible(?WhatsappMessage $message): void
    {
        // This runs strictly after the message above has already been
        // persisted and committed - nothing past this point can undo that.
        // AI eligibility/settings/dispatch failures are caught by the same
        // safely() wrapper this is called from, so they can never take the
        // stored client message down with them.
        if (! $message || ! $message->isEligibleForAutoReply()) {
            return;
        }

        $settings = AiEmployeeSettings::current();

        Log::info('[Messaging] AI enabled/disabled', ['message_id' => $message->id, 'ai_enabled' => (bool) $settings->is_enabled]);

        if (! $settings->is_enabled) {
            return;
        }

        // Every eligible inbound message gets its own fresh response delay -
        // this is a deliberate pacing requirement, not just anti-spam: each
        // message independently waits response_delay_seconds before the AI
        // replies to it, not a one-time delay for the whole conversation.
        $delaySeconds = $settings->response_delay_seconds;

        Log::info('[Messaging] AI processing started', ['message_id' => $message->id, 'delay_seconds' => $delaySeconds]);

        ProcessAiEmployeeReply::dispatch($message->id)->delay(now()->addSeconds($delaySeconds));
    }

    /**
     * Run one webhook entry's storage step in isolation. Meta batches multiple events into a
     * single POST, and (pre-existing behavior) an uncaught exception from one bad/malformed
     * event was bubbling up through the whole request - returning a 500 that both dropped every
     * other event in that same payload and told Meta to keep retrying, indefinitely re-sending
     * an event that will never succeed (e.g. the "String data, right truncated" case above).
     */
    private function safely(\Closure $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::error('Failed to process a Meta webhook event: '.$e->getMessage(), ['exception' => $e]);
        }
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

        // Write the message first and hand it straight back, so the bubble shows
        // up the moment it is typed rather than after a round trip to Meta. It
        // starts as "Sending"; the delivery below flips it to "Sent" or "Not
        // sent", and the inbox's poll picks that up. Sending first meant a
        // message that existed nowhere until Meta answered - a slow or failed
        // call simply swallowed it.
        $message = $contact->messages()->create([
            'direction' => 'outbound',
            'type' => 'text',
            'body' => $validated['message'],
            'status' => 'pending',
            'sent_at' => now(),
            'sent_by_user_id' => auth()->id(),
        ]);

        // afterResponse rather than a queue: it runs once the response is on its
        // way, so the UI is never waiting on Meta, and it needs no worker
        // running to get a message out of the door.
        $resolver = $this->messagingResolver;
        dispatch(function () use ($resolver, $contact, $message) {
            try {
                $response = $resolver->resolve($contact->channel)->sendText($contact, $message->body);
            } catch (\Throwable $e) {
                Log::error(ucfirst($contact->channel).' send threw', ['error' => $e->getMessage()]);
                $message->update(['status' => 'failed', 'send_error' => $e->getMessage()]);

                return;
            }

            if ($response->failed()) {
                Log::error(ucfirst($contact->channel).' send failed', $response->json() ?? ['status' => $response->status()]);

                // Keep Meta's actual rejection reason (e.g. "Error validating
                // access token", "This message is sent outside of allowed
                // window") on the row - it is the only way to tell an expired
                // token from a wrong recipient id without reading the logs, and
                // the response has already gone by the time we know.
                $message->update([
                    'status' => 'failed',
                    'send_error' => $response->json('error.message') ?: 'HTTP '.$response->status(),
                ]);

                return;
            }

            $message->update([
                'status' => 'sent',
                'wa_message_id' => match ($contact->channel) {
                    'instagram', 'facebook' => $response->json('message_id'),
                    default => $response->json('messages.0.id'),
                },
            ]);
        })->afterResponse();

        $contact->update([
            'last_message_preview' => $validated['message'],
            'last_message_at' => now(),
        ]);

        if ($request->wantsJson()) {
            // Same divider logic the poll endpoint uses: whatever was already on
            // screen right before this message, keyed by id (not sent_at-order)
            // for the same out-of-order-webhook reason documented in poll().
            $previousMessage = $contact->messages()->where('id', '<', $message->id)->orderByDesc('sent_at')->first();

            return response()->json([
                'message_html' => view('whatsapp.partials.messages', [
                    'messages' => collect([$message]),
                    'activeContact' => $contact,
                    'previousSentAt' => $previousMessage?->sent_at,
                ])->render(),
                'latest_message_id' => $message->id,
            ]);
        }

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
            // A coordinator's manual entry in the Family details panel wins over
            // the auto-detected guess - that's the whole point of letting them edit it.
            'child_name' => $contact->child_name ?: $hints['child_name'],
            'child_age' => $hints['child_age'],
            'parent_guardian_name' => $contact->name,
            'phone' => $contact->channel === 'whatsapp' ? $contact->wa_id : null,
            'source' => ucfirst($contact->channel),
            'interested_in' => $contact->interested_in ?: $hints['interested_in'],
            'insurance' => $contact->insurance ?: $hints['insurance'],
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
            'child_name' => $contact->child_name ?: $hints['child_name'],
            'child_age' => $hints['child_age'],
            'parent_guardian_name' => $contact->name,
            'phone' => $contact->channel === 'whatsapp' ? $contact->wa_id : null,
            'source' => ucfirst($contact->channel),
            'interested_in' => $contact->interested_in ?: $hints['interested_in'],
            'insurance' => $contact->insurance ?: $hints['insurance'],
            'notes' => $message->body,
            'status' => Lead::STATUS_NEW,
        ]);

        $contact->update(['lead_id' => $lead->id]);

        return redirect()->route('whatsapp.index', ['contact' => $contact->id]);
    }

    /**
     * Change a conversation's AI/human handoff state (AI Active / Human Assigned /
     * Human Takeover / Closed). Acting on a conversation this way also clears any
     * "needs human attention" flag, since a staff member looking at it is exactly
     * what that flag was raising.
     */
    public function updateAiState(Request $request, WhatsappContact $contact)
    {
        $validated = $request->validate([
            'ai_state' => ['required', Rule::in([
                WhatsappContact::AI_STATE_ACTIVE,
                WhatsappContact::AI_STATE_HUMAN_ASSIGNED,
                WhatsappContact::AI_STATE_HUMAN_TAKEOVER,
                WhatsappContact::AI_STATE_CLOSED,
            ])],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $contact->update([
            'ai_state' => $validated['ai_state'],
            'assigned_user_id' => $validated['assigned_user_id'] ?? $contact->assigned_user_id,
            'ai_state_changed_by' => auth()->id(),
            'ai_state_changed_at' => now(),
            'needs_human_attention' => false,
        ]);

        return redirect()->route('whatsapp.index', ['contact' => $contact->id]);
    }

    /**
     * Persist an inbound message, creating the contact (and an auto-captured lead) on first contact.
     * Returns the stored message so the caller can decide whether to trigger the AI Employee.
     */
    private function storeInboundMessage(array $message, ?array $contactPayload): ?WhatsappMessage
    {
        $waId = $message['from'];
        $name = $contactPayload['profile']['name'] ?? null;

        $contact = WhatsappContact::firstOrNew(['wa_id' => $waId]);

        if ($name && ! $contact->name) {
            $contact->name = $name;
        }

        $contact->save();

        Log::info('[Messaging] Contact identified', ['contact_id' => $contact->id, 'channel' => 'whatsapp']);

        $body = match ($message['type'] ?? 'text') {
            'text' => $message['text']['body'] ?? null,
            'button' => $message['button']['text'] ?? null,
            'interactive' => $message['interactive']['button_reply']['title']
                ?? $message['interactive']['list_reply']['title']
                ?? null,
            default => null,
        };

        Log::info('[Messaging] Message identified', ['wa_message_id' => $message['id'], 'type' => $message['type'] ?? 'text']);

        $wasExisting = WhatsappMessage::where('wa_message_id', $message['id'])->exists();

        $stored = WhatsappMessage::updateOrCreate(
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

        if ($wasExisting) {
            Log::info('[Messaging] Message already exists / duplicate', ['wa_message_id' => $message['id'], 'message_id' => $stored->id]);
        } else {
            Log::info('[Messaging] Message saved successfully', ['message_id' => $stored->id, 'contact_id' => $contact->id]);
        }

        $contact->update([
            'last_message_preview' => $body ?? WhatsappMessage::fallbackLabel($message['type'] ?? 'text'),
            'last_message_at' => Carbon::createFromTimestamp((int) $message['timestamp']),
            'unread_count' => $contact->unread_count + 1,
        ]);

        return $stored;
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
     * Returns the stored message so the caller can decide whether to trigger the AI Employee,
     * or null for events that aren't a new stored message (echoes, unsends, deletes).
     */
    private function storeInboundMessengerMessage(array $messagingItem, string $channel): ?WhatsappMessage
    {
        // Skip echoes of our own outbound messages and non-message events (delivery/read receipts).
        if (! isset($messagingItem['message']) || ($messagingItem['message']['is_echo'] ?? false)) {
            return null;
        }

        // Instagram sends an "unsend" event with the *same* mid as the original message,
        // just {mid, is_deleted: true} with no text/attachments. Without this check it falls
        // through to the normal path below and updateOrCreate() overwrites the already-stored
        // message's real body with null, permanently losing what the contact actually sent.
        if ($messagingItem['message']['is_deleted'] ?? false) {
            return null;
        }

        $senderId = $messagingItem['sender']['id'];
        $message = $messagingItem['message'];
        $body = $message['text'] ?? null;

        // Tapping "Reply" on the business's own story and sending text arrives with
        // both the real reply text *and* this pointer back to the story - capture the
        // story thumbnail so the bubble can show what they were replying to, without
        // losing the actual words they typed.
        $storyUrl = $message['reply_to']['story']['url'] ?? null;

        // Stickers (incl. the emoji sticker tray), images, GIFs etc. arrive as `attachments`
        // instead of `text` - there's no literal character to store, so label the type instead
        // of leaving both `type` and `body` looking like a blank/failed text message.
        $type = 'text';
        $stickerId = null;
        $mediaUrl = null;

        // Meta marks certain shares this way (most often a Reel/video carrying
        // licensed music) and, deliberately, sends nothing else about it at all -
        // no url, no text, no attachments. There's no follow-up API call that
        // recovers the content; only the Instagram/WhatsApp app itself can show it.
        if ($message['is_unsupported'] ?? false) {
            $type = 'unsupported_type';
        } elseif ($body === null && ! empty($message['attachments'])) {
            $attachments = collect($message['attachments']);
            $attachmentTypes = $attachments->pluck('type')->filter();
            $stickerId = $attachments->first(fn ($a) => ($a['type'] ?? null) === 'sticker')['payload']['sticker_id'] ?? null;
            $mediaUrl = $attachments->first()['payload']['url'] ?? null;

            if ($stickerId && in_array((string) $stickerId, self::LIKE_STICKER_IDS, true)) {
                // Messenger's quick-tap "like" thumbs-up (tapping it repeatedly sends
                // progressively larger variants, all in this fixed set of sticker ids).
                $type = 'like';
                $body = '👍';
            } elseif ($attachmentTypes->contains('sticker')) {
                $type = 'sticker';
            } else {
                // image/video/audio/file arrive with a directly-usable CDN url and get
                // their own player/preview in the bubble. "share"/"template" is a
                // shared post or Reel - its payload.url is a permalink to view on
                // Instagram/Facebook, not an image, so it gets a link-card instead of
                // being shoved into an <img> tag (which would just show as broken).
                $type = match ($attachmentTypes->first()) {
                    'image', 'video', 'audio', 'file' => $attachmentTypes->first(),
                    'story_mention' => 'story_mention',
                    'share', 'template', 'ig_reel' => 'shared_post',
                    // Meta's own literal attachment type name for "no first-class UI for
                    // this in our app" - despite the name, the url still resolves to a
                    // real, publicly-fetchable video file (verified against a live
                    // payload: HTTP 200, video/mp4, no auth needed), not a dead end.
                    'unsupported_type' => 'video',
                    default => $attachmentTypes->first() ?? 'attachment',
                };
            }
        } elseif ($storyUrl) {
            $mediaUrl = $storyUrl;
            $type = 'story_reply';
        }

        // Messenger Platform sends `timestamp` in milliseconds, unlike WhatsApp's Cloud API (seconds).
        $sentAt = Carbon::createFromTimestamp(intdiv((int) $messagingItem['timestamp'], 1000));

        $contact = WhatsappContact::firstOrNew(['wa_id' => $senderId]);
        $isNewContact = ! $contact->exists;

        if ($isNewContact) {
            $contact->channel = $channel;
        }

        $contact->save();

        Log::info('[Messaging] Contact identified', ['contact_id' => $contact->id, 'channel' => $channel, 'is_new' => $isNewContact]);

        Log::info('[Messaging] Message identified', ['wa_message_id' => $message['mid'], 'type' => $type]);

        // Message storage is the critical path and must complete before, and
        // regardless of, the best-effort profile (name/avatar) lookup below.
        //
        // ROOT CAUSE (fixed here): this used to run AFTER fetchMessengerProfile()'s
        // Graph API call. That call has no error handling of its own - a timeout,
        // DNS failure, rate limit, or expired/invalid access token throws a
        // ConnectionException that propagated straight out of this method, so the
        // message below never got saved at all. Because handleWebhook()'s safely()
        // wrapper catches it, the webhook still returned HTTP 200 to Meta - meaning
        // Meta considered the delivery successful and never retried, so the
        // client's message was silently and permanently lost, not just delayed.
        // Confirmed against production logs: two Instagram DMs logged as received
        // on 2026-08-19 have no corresponding contact or message row in the
        // database to this day.
        $wasExisting = WhatsappMessage::where('wa_message_id', $message['mid'])->exists();

        $stored = WhatsappMessage::updateOrCreate(
            ['wa_message_id' => $message['mid']],
            [
                'whatsapp_contact_id' => $contact->id,
                'direction' => 'inbound',
                'type' => $type,
                'sticker_id' => $stickerId,
                'media_url' => $mediaUrl,
                'body' => $body,
                'status' => 'received',
                'sent_at' => $sentAt,
            ]
        );

        if ($wasExisting) {
            Log::info('[Messaging] Message already exists / duplicate', ['wa_message_id' => $message['mid'], 'message_id' => $stored->id]);
        } else {
            Log::info('[Messaging] Message saved successfully', ['message_id' => $stored->id, 'contact_id' => $contact->id, 'type' => $type]);
        }

        $contact->update([
            'last_message_preview' => $body ?? WhatsappMessage::fallbackLabel($type),
            'last_message_at' => $sentAt,
            'unread_count' => $contact->unread_count + 1,
        ]);

        // Best-effort profile enrichment (display name + avatar), now strictly
        // after the message is already safely stored above. Isolated in its own
        // try/catch so a Graph API failure here can only ever cost the contact
        // their display name/avatar for now (fixed on the next inbound message,
        // or by the whatsapp:refresh-profiles command) - never the message itself.
        if ($isNewContact) {
            try {
                $profile = $this->fetchMessengerProfile($senderId, $channel);

                if ($profile) {
                    $contact->update(array_filter($profile, fn ($v) => $v !== null));
                }
            } catch (\Throwable $e) {
                Log::warning('[Messaging] Profile lookup failed for new contact - message already saved, continuing', [
                    'contact_id' => $contact->id,
                    'channel' => $channel,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stored;
    }

    /**
     * Look up an Instagram or Facebook user's display name + profile picture via
     * the Graph API (neither is included in the webhook payload). Returns
     * ['name' => ?string, 'avatar_url' => ?string] or null on a failed lookup.
     * Public so the whatsapp:refresh-profiles command can re-run it for
     * contacts that were created before a fix to this lookup (e.g. a name that
     * was missing a middle name), or whose cached avatar URL has expired.
     */
    public function fetchMessengerProfile(string $psid, string $channel): ?array
    {
        $token = $channel === 'facebook'
            ? config('services.facebook.page_access_token')
            : config('services.instagram.access_token');

        // Facebook Page Messenger tokens are only valid against graph.facebook.com.
        // This app's Instagram connection uses the "Instagram API with Instagram
        // Login" flow instead (see instagramOAuthCallback), whose tokens are only
        // valid against graph.instagram.com - graph.facebook.com rejects them
        // outright with a 401 "Cannot parse access token" error.
        $host = $channel === 'facebook' ? 'graph.facebook.com' : 'graph.instagram.com';

        $response = Http::withToken($token)
            ->get("https://{$host}/v21.0/{$psid}", [
                'fields' => $channel === 'facebook' ? 'first_name,middle_name,last_name,name,profile_pic' : 'name,username,profile_pic',
            ]);

        if (! $response->successful()) {
            return null;
        }

        $avatarUrl = $response->json('profile_pic');

        if ($channel === 'facebook') {
            // Prefer the API's own full `name` field - a first_name + last_name
            // concatenation silently drops a middle name (e.g. "Perry Philip
            // Lozano" would collapse to "Perry Lozano"), since Facebook only
            // includes middle_name in that pair when it's explicitly requested.
            $name = $response->json('name') ?? trim(implode(' ', array_filter([
                $response->json('first_name'),
                $response->json('middle_name'),
                $response->json('last_name'),
            ])));

            return ['name' => $name !== '' ? $name : null, 'avatar_url' => $avatarUrl];
        }

        $name = $response->json('name') ?? $response->json('username');

        return ['name' => $name, 'avatar_url' => $avatarUrl];
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
