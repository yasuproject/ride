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
 * App customers are managed by CustomerController (AppUser table).
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
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')->toString()))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Users/Admins', [
            'users' => $users,
            'filters' => [
                'search' => $request->input('search'),
                'role' => $request->input('role'),
            ],
            'stats' => [
                'total' => User::query()->count(),
            ],
            'roles' => $this->roleOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'type' => 'admin',
            'roles' => $this->roleOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:'.User::class.',username'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class.',email'],
            'role' => ['required', 'string', Rule::in(User::roles())],
            'password' => ['required', 'confirmed', 'min:4', 'max:255'],
        ]);

        $user = new User();
        $user->forceFill([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => $validated['email'] ?? null,
            'role' => $validated['role'],
            'is_admin' => true,
            'email_verified_at' => now(),
            'password' => Hash::make($validated['password']),
        ]);
        $user->save();

        return redirect()->route('admin.admin-users.show', $user)
            ->with('status', 'Dashboard user created successfully.');
    }

    public function show(User $admin_user): Response
    {
        $user = $admin_user;
        $user->setAttribute('account_type', 'admin');
        $user->setAttribute('phone', null);

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'roles' => $this->roleOptions(),
        ]);
    }

    public function edit(User $admin_user): Response
    {
        $user = $admin_user;
        $user->setAttribute('account_type', 'admin');
        $user->setAttribute('phone', null);

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'roles' => $this->roleOptions(),
        ]);
    }

    public function update(Request $request, User $admin_user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique(User::class, 'username')->ignore($admin_user->id)],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($admin_user->id)],
            'role' => ['required', 'string', Rule::in(User::roles())],
            'password' => ['nullable', 'confirmed', 'min:4', 'max:255'],
        ]);

        $admin_user->forceFill([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => $validated['email'] ?? null,
            'role' => $validated['role'],
            'is_admin' => true,
        ]);

        if (! empty($validated['password'])) {
            $admin_user->forceFill(['password' => Hash::make($validated['password'])]);
        }

        $admin_user->save();

        return redirect()->route('admin.admin-users.show', $admin_user)
            ->with('status', 'Dashboard user updated successfully.');
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
                'user' => 'You cannot delete the last dashboard administrator account.',
            ]);
        }

        $admin_user->delete();

        return redirect()->route('admin.admin-users.index')
            ->with('status', 'Dashboard user deleted successfully.');
    }

    private function hasOtherAdmin(User $user): bool
    {
        return User::query()
            ->whereKeyNot($user->getKey())
            ->where(function ($q) {
                $q->where('is_admin', true)->orWhereIn('role', User::roles());
            })
            ->exists();
    }

    /**
     * @return list<array{value:string,label:string,description:string}>
     */
    private function roleOptions(): array
    {
        return [
            [
                'value' => 'administrator',
                'label' => 'Administrator',
                'description' => 'Full access to dashboard, users, customers, settings, and management.',
            ],
            [
                'value' => 'manager',
                'label' => 'Manager',
                'description' => 'Operational access to manage rides, customers, and day-to-day activities.',
            ],
            [
                'value' => 'support',
                'label' => 'Support',
                'description' => 'Support access to assist customers and review operational information.',
            ],
        ];
    }
}
