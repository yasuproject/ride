<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = AppUser::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->trim()->toString();
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Users/Registered', [
            'users' => $users,
            'filters' => [
                'search' => $request->input('search'),
            ],
            'stats' => [
                'total' => AppUser::query()->count(),
                'today' => AppUser::query()->whereDate('created_at', today())->count(),
                'week' => AppUser::query()->where('created_at', '>=', now()->subDays(7))->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'type' => 'registered',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:app_users,email'],
            'phone' => ['required', 'string', 'max:32', 'unique:app_users,phone'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user = new AppUser();
        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'],
            'email_verified_at' => now(),
        ]);

        if (! empty($validated['password'])) {
            $user->forceFill(['password' => Hash::make($validated['password'])]);
        }

        $user->save();

        return redirect()->route('admin.registered-users.show', $user)
            ->with('status', 'Registered user created successfully.');
    }

    public function show(AppUser $registered_user): Response
    {
        $user = $registered_user;
        $user->setAttribute('is_admin', false);
        $user->setAttribute('account_type', 'registered');

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
        ]);
    }

    public function edit(AppUser $registered_user): Response
    {
        $user = $registered_user;
        $user->setAttribute('is_admin', false);
        $user->setAttribute('account_type', 'registered');

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, AppUser $registered_user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', Rule::unique('app_users', 'email')->ignore($registered_user->id)],
            'phone' => ['required', 'string', 'max:32', Rule::unique('app_users', 'phone')->ignore($registered_user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'remove_password' => ['sometimes', 'boolean'],
        ]);

        $registered_user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'],
        ]);

        if (! empty($validated['password'])) {
            $registered_user->forceFill(['password' => Hash::make($validated['password'])]);
        } elseif ($request->boolean('remove_password')) {
            $registered_user->forceFill(['password' => null]);
        }

        $registered_user->save();

        return redirect()->route('admin.registered-users.show', $registered_user)
            ->with('status', 'Registered user updated successfully.');
    }

    public function destroy(AppUser $registered_user): RedirectResponse
    {
        if (method_exists($registered_user, 'tokens')) {
            $registered_user->tokens()->delete();
        }

        $registered_user->delete();

        return redirect()->route('admin.registered-users.index')
            ->with('status', 'Registered user deleted successfully.');
    }
}
