<?php

namespace App\Http\Controllers;

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
        $statuses = ['new', 'contacted', 'assessment_booked', 'assessment_done', 'enrolled'];
        
        // Calculate total value by cleaning the data
        $totalValue = $leads->sum(function ($lead) {
            // Remove commas and any non-numeric characters except decimal point
            $value = preg_replace('/[^0-9.]/', '', $lead->estimated_value);
            return (float) $value;
        });
        
        // Check if the view exists - try both names
        if (view()->exists('admin.leads.leads')) {
            return view('admin.leads.leads', compact('leads', 'statuses', 'totalValue'));
        } else {
            return view('admin.leads.index', compact('leads', 'statuses', 'totalValue'));
        }
    }

    /**
     * Show the form for creating a new lead.
     */
    public function create()
    {
        return view('admin.leads.create');
    }

    /**
     * Store a newly created lead in storage.
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
            
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Clean estimated_value before saving
        $data = $request->all();
        if (isset($data['estimated_value'])) {
            $data['estimated_value'] = preg_replace('/[^0-9.]/', '', $data['estimated_value']);
        }

        $lead = Lead::create($data);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Lead created successfully!',
                'lead' => $lead
            ], 201);
        }

        return redirect()->route('admin.leads')
            ->with('success', 'Lead created successfully!');
    }

    /**
     * Display the specified lead.
     */
    public function show(Request $request, Lead $lead)
    {
        // If the request is AJAX or wants JSON, return JSON response
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'lead' => $lead
            ]);
        }
        
        // Otherwise return the view (for normal page loads)
        return view('admin.leads.show', compact('lead'));
    }

    /**
     * Show the form for editing the specified lead.
     */
    public function edit(Lead $lead)
    {
        return view('admin.leads.edit', compact('lead'));
    }

    /**
     * Update the specified lead in storage.
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
            
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Clean estimated_value before updating
        $data = $request->all();
        if (isset($data['estimated_value'])) {
            $data['estimated_value'] = preg_replace('/[^0-9.]/', '', $data['estimated_value']);
        }

        $lead->update($data);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Lead updated successfully!',
                'lead' => $lead
            ]);
        }

        return redirect()->route('admin.leads.show', $lead)
            ->with('success', 'Lead updated successfully!');
    }

    /**
     * Remove the specified lead from storage.
     */
    public function destroy(Lead $lead)
    {
        $lead->delete();

        return redirect()->route('admin.leads')
            ->with('success', 'Lead deleted successfully!');
    }

    /**
     * Update lead status - moved to next stage.
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

        $lead->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Lead status updated successfully!',
            'lead' => $lead
        ]);
    }

    /**
     * API endpoint to store lead from frontend form.
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

        // Clean estimated_value before saving
        $data = $request->all();
        if (isset($data['estimated_value'])) {
            $data['estimated_value'] = preg_replace('/[^0-9.]/', '', $data['estimated_value']);
        }

        $lead = Lead::create($data);

        // You can send email notification here
        // Mail::to('admin@engageclinic.ae')->send(new NewLeadNotification($lead));

        return response()->json([
            'success' => true,
            'message' => 'Lead saved successfully!',
            'lead' => $lead
        ], 201);
    }

    /**
     * Get leads grouped by status for kanban view.
     */
    public function getKanbanData()
    {
        $leads = Lead::all();
        $statuses = ['new', 'contacted', 'assessment_booked', 'assessment_done', 'enrolled'];
        
        $data = [];
        foreach ($statuses as $status) {
            $data[$status] = $leads->where('status', $status)->values();
        }
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}