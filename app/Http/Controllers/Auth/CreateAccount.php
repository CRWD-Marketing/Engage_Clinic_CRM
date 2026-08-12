<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class CreateAccount extends Controller
{
    /**
     * Shared option lists for department/role selects.
     */
    protected array $departments = [
        'EXECUTIVE', 'HUMAN_RESOURCES', 'SALES', 'COORDINATOR',
        'FINANCE', 'CLINICAL', 'CONTENT_MANAGEMENT', 'OTHER',
    ];

    protected array $roles = [
        'FULL_ADMIN', 'HR_STAFF', 'SALES_STAFF', 'COORDINATOR',
        'FINANCE_STAFF', 'CLINICAL_SUPERVISOR', 'THERAPIST', 'OTHER_STAFF',
    ];

    /**
     * Determine if this request expects a JSON response
     * (API clients, fetch/axios, or ?format=json for manual testing).
     */
    protected function wantsJsonResponse(Request $request): bool
    {
        return $request->wantsJson() || $request->query('format') === 'json';
    }

    /**
     * GET /users
     * List users with search, filters, and pagination.
     */
    public function index(Request $request): JsonResponse|View
    {
        $query = User::with('manager');

        // Search across name and email
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        // Department filter
        if ($department = $request->input('department')) {
            $query->where('department', $department);
        }

        // Role filter
        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $users = $query->latest()
            ->paginate(10)
            ->withQueryString();

        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully.',
                'data' => $users->items(),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'last_page'    => $users->lastPage(),
                    'per_page'     => $users->perPage(),
                    'total'        => $users->total(),
                ],
                'links' => [
                    'first' => $users->url(1),
                    'last'  => $users->url($users->lastPage()),
                    'prev'  => $users->previousPageUrl(),
                    'next'  => $users->nextPageUrl(),
                ],
            ]);
        }

        // Stats reflect the whole table, not just the current filter/page
        $stats = [
            'total'       => User::count(),
            'active'      => User::where('is_active', true)->count(),
            'inactive'    => User::where('is_active', false)->count(),
            'departments' => User::distinct()->count('department'),
        ];

        return view('user.index', [
            'users' => $users,
            'stats' => $stats,
            'departments' => $this->departments,
            'roles' => $this->roles,
        ]);
    }

    /**
     * GET /users/create
     * Show the create-user form (HTML only — API clients POST directly to store()).
     */
    public function create(): View
    {
        return view('user.create', [
            'managers' => User::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'departments' => $this->departments,
            'roles' => $this->roles,
        ]);
    }

    /**
     * POST /users
     * Create a new user.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:20'],

            'password' => ['required', 'confirmed', Password::defaults()],

            'department' => [
                'required',
                'in:EXECUTIVE,HUMAN_RESOURCES,SALES,COORDINATOR,FINANCE,CLINICAL,CONTENT_MANAGEMENT,OTHER',
            ],

            'role' => [
                'required',
                'in:FULL_ADMIN,HR_STAFF,SALES_STAFF,COORDINATOR,FINANCE_STAFF,CLINICAL_SUPERVISOR,THERAPIST,OTHER_STAFF',
            ],

            'manager_id' => ['nullable', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $user = User::create($validated);

        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'success' => true,
                'message' => 'User created successfully.',
                'data' => $user->load('manager'),
            ], 201);
        }

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'User created successfully.');
    }

    /**
     * GET /users/{user}
     * Show a specific user.
     */
    public function show(Request $request, User $user): JsonResponse|View
    {
        $user->load(['manager', 'subordinates']);

        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'success' => true,
                'message' => 'User retrieved successfully.',
                'data' => $user,
            ]);
        }

        return view('user.[id]', compact('user'));
    }

    /**
     * GET /users/{user}/edit
     * Show the edit-user form (HTML only — API clients PUT/PATCH directly to update()).
     */
    public function edit(User $user): View
    {
        return view('user.edit', [
            'user' => $user,
            'managers' => User::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'departments' => $this->departments,
            'roles' => $this->roles,
        ]);
    }

    /**
     * PUT/PATCH /users/{user}
     * Update a user.
     */
    /**
 * PUT/PATCH /users/{user}
 * Update a user.
 */
public function update(Request $request, User $user): JsonResponse|RedirectResponse
{
    $validated = $request->validate([
        'first_name' => ['sometimes', 'required', 'string', 'max:255'],
        'middle_name' => ['nullable', 'string', 'max:255'],
        'last_name' => ['sometimes', 'required', 'string', 'max:255'],
        'email' => ['sometimes', 'required', 'email', 'unique:users,email,' . $user->id],
        'phone_number' => ['nullable', 'string', 'max:20'],

        'password' => ['nullable', 'confirmed', Password::defaults()],

        'department' => [
            'sometimes',
            'required',
            'in:EXECUTIVE,HUMAN_RESOURCES,SALES,COORDINATOR,FINANCE,CLINICAL,CONTENT_MANAGEMENT,OTHER',
        ],

        'role' => [
            'sometimes',
            'required',
            'in:FULL_ADMIN,HR_STAFF,SALES_STAFF,COORDINATOR,FINANCE_STAFF,CLINICAL_SUPERVISOR,THERAPIST,OTHER_STAFF',
        ],

        'manager_id' => ['nullable', 'exists:users,id'],
        'start_date' => ['nullable', 'date'],
        'notes' => ['nullable', 'string'],
        'is_active' => ['boolean'],
    ]);

    // Only touch the password if one was actually submitted —
    // otherwise drop the key so it doesn't overwrite the existing hash with null.
    if (empty($validated['password'])) {
        unset($validated['password']);
    }

    $user->update($validated);

    if ($this->wantsJsonResponse($request)) {
        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => $user->fresh()->load('manager'),
        ]);
    }

    return redirect()
        ->route('users.show', $user)
        ->with('success', 'User updated successfully.');
}

    /**
     * DELETE /users/{user}
     * Delete a user.
     */
    public function destroy(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $user->delete();

        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.',
            ]);
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}