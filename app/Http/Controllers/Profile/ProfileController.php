<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    private const TIMEZONES = ['Asia/Dubai (GST)', 'Asia/Riyadh (AST)', 'Europe/London (BST)'];

    /**
     * Display the current user's own profile. Role, status and module access are
     * shown read-only here - only an admin (User Management) can change those.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $totalModules = collect(config('role_permissions'))->flatten()->unique()->count();
        $userModuleCount = count(config("role_permissions.{$user->role}", []));
        $timezones = self::TIMEZONES;

        return view('profile.index', compact('user', 'totalModules', 'userModuleCount', 'timezones'));
    }

    /**
     * Update the personal-details fields a user may change about themselves.
     * Role, status, department and module access are deliberately excluded.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'job_title' => 'nullable|string|max:100',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone_number' => 'nullable|string|max:30',
            'timezone' => 'nullable|string|max:60',
            'message_signature' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $user->update($validator->validated());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'user' => $user->fresh()]);
        }

        return redirect()->route('profile.index')->with('success', 'Profile updated.');
    }
}
