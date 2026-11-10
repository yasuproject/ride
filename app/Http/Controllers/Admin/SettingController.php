<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(): Response
    {
        $settings = AppSetting::current();

        return Inertia::render('Admin/Settings/Edit', [
            'settings' => [
                'app_name' => $settings->app_name,
                'otp_login_enabled' => (bool) $settings->otp_login_enabled,
                'phone_country_code' => $settings->phone_country_code,
                'otp_length' => (int) $settings->otp_length,
                'otp_expiry_seconds' => (int) $settings->otp_expiry_seconds,
                'resend_cooldown_seconds' => (int) $settings->resend_cooldown_seconds,
                'max_verify_attempts' => (int) $settings->max_verify_attempts,
                'sms_provider' => $settings->sms_provider,
                'sms_sender_id' => $settings->sms_sender_id,
                'sms_message_template' => $settings->sms_message_template,
                'sms_api_key_set' => filled($settings->sms_api_key),
            ],
            'status' => session('status'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:120'],
            'otp_login_enabled' => ['required', 'boolean'],
            'phone_country_code' => ['required', 'string', 'max:8'],
            'otp_length' => ['required', 'integer', 'min:4', 'max:8'],
            'otp_expiry_seconds' => ['required', 'integer', 'min:30', 'max:3600'],
            'resend_cooldown_seconds' => ['required', 'integer', 'min:10', 'max:600'],
            'max_verify_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'sms_provider' => ['nullable', 'string', 'max:50'],
            'sms_sender_id' => ['nullable', 'string', 'max:50'],
            'sms_message_template' => ['nullable', 'string', 'max:1000'],
            'sms_api_key' => ['nullable', 'string', 'max:500'],
        ]);

        $settings = AppSetting::current();
        $data = $validated;
        if (! filled($data['sms_api_key'] ?? null)) {
            unset($data['sms_api_key']);
        }

        $settings->fill($data);
        $settings->save();

        return redirect()->route('admin.settings.edit')->with('status', 'Settings saved successfully.');
    }
}
