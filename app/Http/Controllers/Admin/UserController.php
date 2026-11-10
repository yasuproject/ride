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
    public function index(Request $request): Response
    {
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->trim()->toString();
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), function ($query) use ($request): void {
                if ($request->role === 'admin') {
                    $query->where('is_admin', true);
                } elseif ($request->role === 'user') {
                    $query->where('is_admin', false);
                }
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $request->input('search'),
                'role' => $request->input('role'),
            ],
            'stats' => [
                'total' => User::query()->count(),
                'admins' => User::query()->where('is_admin', true)->count(),
                'passengers' => User::query()->where('is_admin', false)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create');
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

        $user = new User();
        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_admin' => (bool) ($validated['is_admin'] ?? false),
            'email_verified_at' => now(),
        ]);

        if (! empty($validated['password'])) {
            $user->forceFill(['password' => Hash::make($validated['password'])]);
        }

        $user->save();

        return redirect()->route('admin.users.show', $user)
            ->with('status', 'User created successfully.');
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

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('status', 'User deleted successfully.');
    }

    private function hasOtherAdmin(User $user): bool
    {
        return User::query()
            ->where('is_admin', true)
            ->whereKeyNot($user->getKey())
            ->exists();
    }
}
