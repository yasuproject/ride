<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total' => Ride::query()->count(),
                'active' => Ride::query()->whereIn('status', ['requested', 'assigned', 'in_progress'])->count(),
                'completed' => Ride::query()->where('status', 'completed')->count(),
                'cancelled' => Ride::query()->where('status', 'cancelled')->count(),
            ],
            'customerStats' => [
                'total' => User::query()->where('is_admin', false)->count(),
                'today' => User::query()->where('is_admin', false)->whereDate('created_at', today())->count(),
                'week' => User::query()->where('is_admin', false)->where('created_at', '>=', now()->subDays(7))->count(),
            ],
            'recentRides' => Ride::query()->latest('requested_at')->limit(5)->get(),
            'recentCustomers' => User::query()
                ->where('is_admin', false)
                ->latest()
                ->limit(8)
                ->get(['id', 'name', 'phone', 'email', 'created_at']),
        ]);
    }
}
