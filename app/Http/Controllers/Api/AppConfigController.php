<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;

class AppConfigController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = AppSetting::current();

        // Only non-sensitive, client-safe values leave the server. Provider
        // credentials, templates and admin-only limits stay private.
        return response()
            ->json([
                'success' => true,
                'data' => $settings->toPublicConfig(),
            ])
            ->header('Cache-Control', 'no-store, max-age=0');
    }
}
