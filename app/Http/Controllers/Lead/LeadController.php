<?php

namespace App\Http\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeadController extends Controller
{
    /**
     * Display a listing of the leads.
     */
    public function index(Request $request)
    {
        $query = Lead::query();

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('child_name', 'LIKE', "%{$search}%")
                    ->orWhere('parent_guardian_name', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('notes', 'LIKE', "%{$search}%");
            });
        }

        $leads = $query->latest()->get();

        $statuses = [
            'new',
            'contacted',
            'assessment_booked',
            'assessment_done',
            'enrolled'
        ];

        $totalValue = $leads->sum(function ($lead) {
            $value = preg_replace('/[^0-9.]/', '', $lead->estimated_value);

            return (float) $value;
        });

        return view('lead.index', compact(
            'leads',
            'statuses',
            'totalValue'
        ));
    }

    /**
     * Show create lead form.
     */
    public function create()
    {
        return view('lead.create');
    }

    /**
     * Store new lead.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_name' => 'nullable|string|max:255',
            'child_age' => 'nullable|string|max:10',
            'parent_guardian_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'source' => 'nullable|string|max:50',
            'interested_in' => 'nullable|string|max:100',
            'insurance' => 'nullable|string|max:50',
            'estimated_value' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->all();

        if (isset($data['estimated_value'])) {
            $data['estimated_value'] = preg_replace(
                '/[^0-9.]/',
                '',
                $data['estimated_value']
            );
        }

        $lead = Lead::create($data);

        if ($request->ajax() || $request->wantsJson()) {

            return response()->json([
                'success' => true,
                'message' => 'Lead created successfully!',
                'lead' => $lead
            ], 201);
        }

        return redirect()
            ->route('leads.index')
            ->with('success', 'Lead created successfully!');
    }

    /**
     * Display lead.
     */
    public function show(Request $request, Lead $lead)
    {
        if ($request->ajax() || $request->wantsJson()) {

            return response()->json([
                'success' => true,
                'lead' => $lead
            ]);
        }

        return view('lead.show', compact('lead'));
    }

    /**
     * Edit lead.
     */
    public function edit(Lead $lead)
    {
        return view('lead.edit', compact('lead'));
    }

    /**
     * Update lead.
     */
    public function update(Request $request, Lead $lead)
    {
        $validator = Validator::make($request->all(), [
            'child_name' => 'nullable|string|max:255',
            'child_age' => 'nullable|string|max:10',
            'parent_guardian_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'source' => 'nullable|string|max:50',
            'interested_in' => 'nullable|string|max:100',
            'insurance' => 'nullable|string|max:50',
            'estimated_value' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:new,contacted,assessment_booked,assessment_done,enrolled',
        ]);

        if ($validator->fails()) {

            if ($request->ajax() || $request->wantsJson()) {

                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->all();

        if (isset($data['estimated_value'])) {
            $data['estimated_value'] = preg_replace(
                '/[^0-9.]/',
                '',
                $data['estimated_value']
            );
        }

        $lead->update($data);

        if ($request->ajax() || $request->wantsJson()) {

            return response()->json([
                'success' => true,
                'message' => 'Lead updated successfully!',
                'lead' => $lead
            ]);
        }

        return redirect()
            ->route('leads.show', $lead)
            ->with('success', 'Lead updated successfully!');
    }

    /**
     * Delete lead.
     */
    public function destroy(Lead $lead)
    {
        $lead->delete();

        return redirect()
            ->route('leads.index')
            ->with('success', 'Lead deleted successfully!');
    }

    /**
     * Update lead status.
     */
    public function updateStatus(Request $request, Lead $lead)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:new,contacted,assessment_booked,assessment_done,enrolled',
        ]);

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Invalid status provided'
            ], 422);
        }

        $lead->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lead status updated successfully!',
            'lead' => $lead
        ]);
    }

    /**
     * Store lead from public frontend form.
     */
    public function apiStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_name' => 'nullable|string|max:255',
            'child_age' => 'nullable|string|max:10',
            'parent_guardian_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'source' => 'nullable|string|max:50',
            'interested_in' => 'nullable|string|max:100',
            'insurance' => 'nullable|string|max:50',
            'estimated_value' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();

        if (isset($data['estimated_value'])) {

            $data['estimated_value'] = preg_replace(
                '/[^0-9.]/',
                '',
                $data['estimated_value']
            );
        }

        $lead = Lead::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Lead saved successfully!',
            'lead' => $lead
        ], 201);
    }

    /**
     * Kanban lead data.
     */
    public function getKanbanData()
    {
        $leads = Lead::all();

        $statuses = [
            'new',
            'contacted',
            'assessment_booked',
            'assessment_done',
            'enrolled'
        ];

        $data = [];

        foreach ($statuses as $status) {

            $data[$status] = $leads
                ->where('status', $status)
                ->values();
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}