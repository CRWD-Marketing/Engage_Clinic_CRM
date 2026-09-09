<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PatientDocumentController extends Controller
{
    /**
     * File a document record against the patient (name, type, expiry) - a
     * metadata log, not an upload; no file is required or attached.
     */
    public function store(Request $request, Patient $patient)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $document = $patient->documents()->create([
            'uploaded_by' => auth()->id(),
            'name' => $request->input('name'),
            'type' => $request->input('type'),
            'expires_at' => $request->input('expires_at'),
        ]);

        $expiry = $document->expiryStatus();

        return response()->json([
            'success' => true,
            'document' => [
                'id' => $document->id,
                'name' => $document->name,
                'type' => $document->type,
                'uploader_label' => $document->uploaderLabel(),
                'created_at_label' => $document->created_at->format('d M Y'),
                'expiry_label' => $expiry['label'],
                'expiry_variant' => $expiry['variant'],
                'delete_url' => route('patient.documents.destroy', [$patient, $document]),
            ],
        ], 201);
    }

    /**
     * Download a patient document's attached file - only present on legacy
     * rows created back when this tab required an upload.
     */
    public function download(Patient $patient, PatientDocument $document)
    {
        abort_unless($document->patient_id === $patient->id, 404);
        abort_unless($document->file_path && Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
    }

    /**
     * Delete a patient document record (and its stored file, if any). AJAX
     * only (no redirect) - a server redirect would drop the #tab hash the
     * page uses to restore which tab the user was on.
     */
    public function destroy(Patient $patient, PatientDocument $document)
    {
        abort_unless($document->patient_id === $patient->id, 404);

        if ($document->file_path) {
            Storage::disk('local')->delete($document->file_path);
        }
        $document->delete();

        return response()->json(['success' => true]);
    }
}
