<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            'recentRides' => Ride::query()->latest('requested_at')->limit(5)->get(),
        ]);
    }
}
