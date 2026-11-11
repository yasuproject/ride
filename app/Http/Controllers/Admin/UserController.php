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

class UserController extends Controller
{
    public function registered(Request $request): Response
    {
        $users = User::query()
            ->where('is_admin', false)
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
                'total' => User::query()->where('is_admin', false)->count(),
                'today' => User::query()->where('is_admin', false)->whereDate('created_at', today())->count(),
                'week' => User::query()->where('is_admin', false)->where('created_at', '>=', now()->subDays(7))->count(),
            ],
        ]);
    }

    public function admins(Request $request): Response
    {
        $users = User::query()
            ->where('is_admin', true)
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

        return Inertia::render('Admin/Users/Admins', [
            'users' => $users,
            'filters' => [
                'search' => $request->input('search'),
            ],
            'stats' => [
                'total' => User::query()->where('is_admin', true)->count(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'type' => $request->query('type', 'registered'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['nullable', 'string', 'max:32', 'unique:'.User::class],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'is_admin' => ['sometimes', 'boolean'],
        ]);

        if (empty($validated['email']) && empty($validated['phone'])) {
            return back()->withErrors([
                'email' => 'Either email or phone number is required.',
                'phone' => 'Either email or phone number is required.',
            ])->withInput();
        }

        $type = $request->input('type');
        $isAdmin = (bool) ($validated['is_admin'] ?? false);
        if ($type === 'admin') {
            $isAdmin = true;
        } elseif ($type === 'registered') {
            $isAdmin = false;
        }

        $user = new User();
        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_admin' => $isAdmin,
            'email_verified_at' => now(),
        ]);

        if (! empty($validated['password'])) {
            $user->forceFill(['password' => Hash::make($validated['password'])]);
        }

        $user->save();

        return redirect()->route('admin.users.show', $user)
            ->with('status', $user->is_admin ? 'Admin user created successfully.' : 'Registered user created successfully.');
    }

    public function show(User $user): Response
    {
        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32', Rule::unique(User::class)->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'is_admin' => ['sometimes', 'boolean'],
            'remove_password' => ['sometimes', 'boolean'],
        ]);

        if (empty($validated['email']) && empty($validated['phone'])) {
            return back()->withErrors([
                'email' => 'Either email or phone number is required.',
                'phone' => 'Either email or phone number is required.',
            ])->withInput();
        }

        $updates = [
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ];

        if ($request->has('is_admin')) {
            $newAdmin = (bool) $validated['is_admin'];

            if ($user->id === $request->user()->id && ! $newAdmin) {
                return back()->withErrors([
                    'is_admin' => 'You cannot remove your own administrator access.',
                ])->withInput();
            }

            if (! $newAdmin && ! $this->hasOtherAdmin($user)) {
                return back()->withErrors([
                    'is_admin' => 'You cannot remove the last administrator account.',
                ])->withInput();
            }

            $updates['is_admin'] = $newAdmin;
        }

        $user->forceFill($updates);

        if (! empty($validated['password'])) {
            $user->forceFill(['password' => Hash::make($validated['password'])]);
        } elseif ($request->boolean('remove_password')) {
            $user->forceFill(['password' => null]);
        }

        $user->save();

        return redirect()->route('admin.users.show', $user)
            ->with('status', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors([
                'user' => 'You cannot delete your own account.',
            ]);
        }

        if ($user->is_admin && ! $this->hasOtherAdmin($user)) {
            return back()->withErrors([
                'user' => 'You cannot delete the last administrator account.',
            ]);
        }

        // Clear tokens if using Sanctum to avoid orphaned tokens
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        $wasAdmin = $user->is_admin;
        $user->delete();

        return redirect()->route($wasAdmin ? 'admin.admin-users.index' : 'admin.registered-users.index')
            ->with('status', $wasAdmin ? 'Admin user deleted successfully.' : 'Registered user deleted successfully.');
    }

    private function hasOtherAdmin(User $user): bool
    {
        return User::query()
            ->where('is_admin', true)
            ->whereKeyNot($user->getKey())
            ->exists();
    }
}
