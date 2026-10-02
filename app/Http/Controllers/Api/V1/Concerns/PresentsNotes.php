<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\PatientNote;

trait PresentsNotes
{
    /**
     * A session note as the app reads it: the row with `author_name`, the
     * signer's name, and (when loaded) its patient and that patient's lead.
     */
    protected function presentNote(PatientNote $note): array
    {
        $note->loadMissing(['user', 'signedOffBy']);
        $signer = $note->signedOffBy;

        $row = $note->attributesToArray() + [
            'signed_off_by_name' => $signer ? trim($signer->first_name.' '.$signer->last_name) : null,
        ];

        if ($note->relationLoaded('patient') && $note->patient) {
            $row['patient'] = $note->patient->attributesToArray() + ['lead' => $note->patient->lead];
        }

        return $row;
    }
}
