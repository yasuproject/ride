<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use Inertia\Inertia;
use Inertia\Response;

class RideController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Rides/Index', [
            'rides' => Ride::query()->latest('requested_at')->paginate(10)->withQueryString(),
            'filters' => [
                'status' => request('status'),
            ],
        ]);
    }
}
