<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use App\Models\CalendarSession;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Patient;
use App\Models\User;
use App\Models\WhatsappContact;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Global topbar search across leads, patients, contacts, and WhatsApp contacts.
     */
    public function search(Request $request)
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $user = $request->user();
        $results = [];

        if ($user->canAccessFeature('leads')) {
            $results = array_merge($results, Lead::query()
                ->where(function ($q) use ($term) {
                    $q->where('child_name', 'like', "%{$term}%")
                        ->orWhere('parent_guardian_name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                })
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (Lead $lead) => [
                    'type' => 'Lead',
                    'icon' => 'fa-filter',
                    'title' => $lead->child_name ?: $lead->parent_guardian_name,
                    'subtitle' => trim(($lead->parent_guardian_name ? $lead->parent_guardian_name.' · ' : '').$lead->phone),
                    'url' => route('leads.index', ['lead' => $lead->id]),
                ])
                ->all());
        }

        if ($user->canAccessFeature('patients')) {
            $results = array_merge($results, Patient::query()
                ->whereHas('lead', function ($q) use ($term) {
                    $q->where('child_name', 'like', "%{$term}%")
                        ->orWhere('parent_guardian_name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                })
                ->with('lead')
                ->limit(6)
                ->get()
                ->map(fn (Patient $patient) => [
                    'type' => 'Patient',
                    'icon' => 'fa-users',
                    'title' => $patient->lead->child_name ?? ('Patient #'.$patient->id),
                    'subtitle' => trim(($patient->lead->parent_guardian_name ?? '').' · '.($patient->lead->phone ?? ''), ' ·'),
                    'url' => route('patient.show', $patient),
                ])
                ->all());
        }

        if ($user->canAccessFeature('contacts')) {
            $results = array_merge($results, Contact::query()
                ->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                })
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (Contact $contact) => [
                    'type' => 'Contact',
                    'icon' => 'fa-envelope',
                    'title' => $contact->name,
                    'subtitle' => $contact->phone ?: $contact->email,
                    'url' => route('contacts.index', ['contact' => $contact->id]),
                ])
                ->all());
        }

        if ($user->canAccessFeature('whatsapp')) {
            $results = array_merge($results, WhatsappContact::query()
                ->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('child_name', 'like', "%{$term}%")
                        ->orWhere('wa_id', 'like', "%{$term}%");
                })
                ->latest('last_message_at')
                ->limit(6)
                ->get()
                ->map(fn (WhatsappContact $contact) => [
                    'type' => 'WhatsApp',
                    'icon' => 'fa-whatsapp',
                    'title' => $contact->name ?: $contact->wa_id,
                    'subtitle' => $contact->child_name ?: $contact->wa_id,
                    'url' => route('whatsapp.index', ['contact' => $contact->id]),
                ])
                ->all());
        }

        if ($user->canAccessFeature('therapists')) {
            $results = array_merge($results, User::query()
                ->where('role', 'THERAPIST')
                ->where(function ($q) use ($term) {
                    $q->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone_number', 'like', "%{$term}%");
                })
                ->limit(6)
                ->get()
                ->map(fn (User $therapist) => [
                    'type' => 'Therapist',
                    'icon' => 'fa-user-md',
                    'title' => trim($therapist->first_name.' '.$therapist->last_name),
                    'subtitle' => $therapist->job_title ?: $therapist->phone_number,
                    'url' => route('therapist.index', ['therapist_id' => $therapist->id]),
                ])
                ->all());
        }

        if ($user->canAccessFeature('calendar')) {
            $results = array_merge($results, CalendarSession::query()
                ->where(function ($q) use ($term) {
                    $q->where('patient_name', 'like', "%{$term}%")
                        ->orWhere('activity_label', 'like', "%{$term}%")
                        ->orWhereHas('therapist', function ($tq) use ($term) {
                            $tq->where('first_name', 'like', "%{$term}%")
                                ->orWhere('last_name', 'like', "%{$term}%");
                        });
                })
                ->with('therapist')
                ->latest('session_date')
                ->limit(6)
                ->get()
                ->map(fn (CalendarSession $session) => [
                    'type' => 'Session',
                    'icon' => 'fa-calendar-alt',
                    'title' => $session->displayName(),
                    'subtitle' => trim($session->session_date->format('d M Y').($session->therapist ? ' · '.trim($session->therapist->first_name.' '.$session->therapist->last_name) : '')),
                    'url' => route('calendar.index', ['session' => $session->id]),
                ])
                ->all());
        }

        return response()->json(['results' => array_slice($results, 0, 20)]);
    }
}
