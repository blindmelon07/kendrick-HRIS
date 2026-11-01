<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('users/index', [
            'users' => User::query()->with('employee:id,user_id,employee_number')->orderBy('name')->get(['id', 'name', 'email', 'role']),
            'roles' => UserRole::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => ['required', Password::defaults()],
        ]);

        User::create($validated);

        return Inertia::flash('success', 'User account created.')->back();
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => ['nullable', Password::defaults()],
        ]);

        if ($user->is($request->user()) && $validated['role'] !== UserRole::Admin->value) {
            return Inertia::flash('error', 'You cannot remove your own administrator role.')->back();
        }

        $user->update(array_filter($validated));

        return Inertia::flash('success', 'User account updated.')->back();
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return Inertia::flash('error', 'You cannot delete your own account.')->back();
        }

        $user->delete();

        return Inertia::flash('success', 'User account deleted.')->back();
    }
}
