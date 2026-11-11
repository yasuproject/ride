<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages dashboard/admin panel accounts (User table).
 * App-registered users are managed by RegisteredUserController (AppUser table).
 */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->trim()->toString();
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Users/Admins', [
            'users' => $users,
            'filters' => [
                'search' => $request->input('search'),
            ],
            'stats' => [
                'total' => User::query()->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'type' => 'admin',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = new User();
        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_admin' => true,
            'email_verified_at' => now(),
            'password' => Hash::make($validated['password']),
        ]);
        $user->save();

        return redirect()->route('admin.admin-users.show', $user)
            ->with('status', 'Admin user created successfully.');
    }

    public function show(User $admin_user): Response
    {
        $user = $admin_user;
        $user->setAttribute('account_type', 'admin');
        $user->setAttribute('phone', $user->phone ?? null);

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
        ]);
    }

    public function edit(User $admin_user): Response
    {
        $user = $admin_user;
        $user->setAttribute('account_type', 'admin');
        $user->setAttribute('phone', $user->phone ?? null);

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $admin_user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($admin_user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $admin_user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_admin' => true,
        ]);

        if (! empty($validated['password'])) {
            $admin_user->forceFill(['password' => Hash::make($validated['password'])]);
        }

        $admin_user->save();

        return redirect()->route('admin.admin-users.show', $admin_user)
            ->with('status', 'Admin user updated successfully.');
    }

    public function destroy(Request $request, User $admin_user): RedirectResponse
    {
        if ($admin_user->id === $request->user()->id) {
            return back()->withErrors([
                'user' => 'You cannot delete your own account.',
            ]);
        }

        if (! $this->hasOtherAdmin($admin_user)) {
            return back()->withErrors([
                'user' => 'You cannot delete the last administrator account.',
            ]);
        }

        $admin_user->delete();

        return redirect()->route('admin.admin-users.index')
            ->with('status', 'Admin user deleted successfully.');
    }

    private function hasOtherAdmin(User $user): bool
    {
        return User::query()
            ->where('is_admin', true)
            ->whereKeyNot($user->getKey())
            ->exists();
    }
}
