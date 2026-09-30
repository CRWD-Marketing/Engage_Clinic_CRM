<?php

namespace App\Http\Controllers\CmsForm;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Services\CmsForms\FormSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * The client-facing side of a published form: /forms/{slug}. No CRM account
 * needed. `?embed=1` renders it chrome-free for an <iframe> on another site.
 */
class PublicFormController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $form = Form::published()->where('slug', $slug)->with('fields')->firstOrFail();

        return view('cms_forms.public', [
            'form' => $form,
            'preview' => false,
            'embed' => $request->boolean('embed'),
            'submitted' => $request->boolean('submitted'),
        ]);
    }

    public function submit(Request $request, string $slug, FormSubmissionService $service)
    {
        $form = Form::published()->where('slug', $slug)->with('fields')->first();

        if (! $form) {
            $message = 'This form is no longer accepting responses.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 404)
                : abort(404, $message);
        }

        // Honeypot: real visitors never see this input, so a value means a bot.
        // Answer like a success so it doesn't learn to avoid the trap.
        if (filled($request->input('website'))) {
            return $this->success($request, $form);
        }

        try {
            $service->store($form, $request);
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Please check the highlighted fields.', 'errors' => $e->errors()], 422);
            }
            throw $e;
        }

        return $this->success($request, $form);
    }

    private function success(Request $request, Form $form)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $form->successMessage()], 201);
        }

        return redirect()->route('forms.public.show', array_filter([
            'slug' => $form->slug,
            'submitted' => 1,
            'embed' => $request->boolean('embed') ? 1 : null,
        ]));
    }
}
