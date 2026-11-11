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

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $group = $request->string('group')->toString();
        if (! in_array($group, ['all', 'active', 'blocked'], true)) {
            $group = 'all';
        }

        $base = AppUser::query();

        $stats = [
            'all' => (clone $base)->count(),
            'active' => (clone $base)->where('is_blocked', false)->count(),
            'blocked' => (clone $base)->where('is_blocked', true)->count(),
            'today' => (clone $base)->whereDate('created_at', today())->count(),
            'week' => (clone $base)->where('created_at', '>=', now()->subDays(7))->count(),
        ];

        $users = AppUser::query()
            ->when($group === 'active', fn ($q) => $q->where('is_blocked', false))
            ->when($group === 'blocked', fn ($q) => $q->where('is_blocked', true))
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

        return Inertia::render('Admin/Customers/Index', [
            'users' => $users,
            'filters' => [
                'search' => $request->input('search'),
                'group' => $group,
            ],
            'stats' => $stats,
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
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:customers,email'],
            'phone' => ['required', 'string', 'max:32', 'unique:customers,phone'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user = new AppUser();
        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'],
            'email_verified_at' => now(),
            'is_blocked' => false,
            'blocked_at' => null,
        ]);

        if (! empty($validated['password'])) {
            $user->forceFill(['password' => Hash::make($validated['password'])]);
        }

        $user->save();

        return redirect()->route('admin.customers.show', $user)
            ->with('status', 'Customer created successfully.');
    }

    public function show(AppUser $customer): Response
    {
        $user = $customer;
        $user->setAttribute('is_admin', false);
        $user->setAttribute('account_type', 'registered');

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
        ]);
    }

    public function edit(AppUser $customer): Response
    {
        $user = $customer;
        $user->setAttribute('is_admin', false);
        $user->setAttribute('account_type', 'registered');

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, AppUser $customer): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer->id)],
            'phone' => ['required', 'string', 'max:32', Rule::unique('customers', 'phone')->ignore($customer->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'remove_password' => ['sometimes', 'boolean'],
        ]);

        $customer->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'],
        ]);

        if (! empty($validated['password'])) {
            $customer->forceFill(['password' => Hash::make($validated['password'])]);
        } elseif ($request->boolean('remove_password')) {
            $customer->forceFill(['password' => null]);
        }

        $customer->save();

        return redirect()->route('admin.customers.show', $customer)
            ->with('status', 'Customer updated successfully.');
    }

    public function destroy(AppUser $customer): RedirectResponse
    {
        if (method_exists($customer, 'tokens')) {
            $customer->tokens()->delete();
        }

        $customer->delete();

        return redirect()->route('admin.customers.index')
            ->with('status', 'Customer deleted successfully.');
    }

    public function block(AppUser $customer): RedirectResponse
    {
        $customer->forceFill([
            'is_blocked' => true,
            'blocked_at' => now(),
        ])->save();

        if (method_exists($customer, 'tokens')) {
            $customer->tokens()->delete();
        }

        return back()->with('status', 'Customer has been blocked.');
    }

    public function unblock(AppUser $customer): RedirectResponse
    {
        $customer->forceFill([
            'is_blocked' => false,
            'blocked_at' => null,
        ])->save();

        return back()->with('status', 'Customer has been unblocked.');
    }
}
