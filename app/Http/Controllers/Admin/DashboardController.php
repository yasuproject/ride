<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\Ride;
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
                'total' => AppUser::query()->count(),
                'today' => AppUser::query()->whereDate('created_at', today())->count(),
                'week' => AppUser::query()->where('created_at', '>=', now()->subDays(7))->count(),
            ],
            'recentRides' => Ride::query()->latest('requested_at')->limit(5)->get(),
            'recentCustomers' => AppUser::query()
                ->latest()
                ->limit(8)
                ->get(['id', 'name', 'phone', 'email', 'created_at']),
        ]);
    }
}
