<?php

namespace Tests\Feature\Api;

use App\Models\AppSetting;
use App\Models\AppUser;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\Sms\SmsDeliveryException;
use App\Services\Sms\SmsEthiopiaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OtpAuthTest extends TestCase
{
    use RefreshDatabase;

    private object $sms;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sms = new class extends SmsEthiopiaService
        {
            /** @var list<array{phone:string, message:string}> */
            public array $sent = [];

            public bool $shouldFail = false;

            public function send(string $phone, string $message, AppSetting $settings): void
            {
                if ($this->shouldFail) {
                    throw new SmsDeliveryException('Provider unavailable.');
                }

                $this->sent[] = ['phone' => $phone, 'message' => $message];
            }
        };

        $this->app->instance(SmsEthiopiaService::class, $this->sms);
    }

    public function test_app_config_returns_only_safe_dynamic_settings(): void
    {
        AppSetting::current()->update([
            'app_name' => 'MkRide Test',
            'otp_login_enabled' => true,
            'phone_country_code' => '+251',
            'otp_length' => 5,
            'otp_expiry_seconds' => 240,
            'resend_cooldown_seconds' => 45,
            'max_verify_attempts' => 7,
            'sms_provider' => 'smsethiopia',
            'sms_api_key' => 'super-secret-provider-key',
            'sms_sender_id' => 'PRIVATE-SENDER',
            'sms_message_template' => 'Private template {code}',
        ]);

        $response = $this->getJson('/api/app-config');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.otp_login_enabled', true)
            ->assertJsonPath('data.phone_country_code', '+251')
            ->assertJsonPath('data.otp_length', 5)
            ->assertJsonPath('data.otp_expiry_seconds', 240)
            ->assertJsonPath('data.resend_cooldown_seconds', 45)
            ->assertJsonPath('data.app_name', 'MkRide Test')
            ->assertJsonMissingPath('data.sms_api_key')
            ->assertJsonMissingPath('data.sms_provider')
            ->assertJsonMissingPath('data.sms_sender_id')
            ->assertJsonMissingPath('data.sms_message_template')
            ->assertJsonMissingPath('data.max_verify_attempts');

        $content = $response->getContent();
        $this->assertStringNotContainsString('super-secret-provider-key', $content);
        $this->assertStringNotContainsString('PRIVATE-SENDER', $content);
        $this->assertStringNotContainsString('Private template', $content);
    }

    public function test_app_config_reflects_admin_changes_immediately(): void
    {
        AppSetting::current()->update([
            'otp_length' => 4,
            'otp_expiry_seconds' => 120,
            'resend_cooldown_seconds' => 30,
        ]);

        $this->getJson('/api/app-config')
            ->assertOk()
            ->assertJsonPath('data.otp_length', 4)
            ->assertJsonPath('data.otp_expiry_seconds', 120)
            ->assertJsonPath('data.resend_cooldown_seconds', 30);

        AppSetting::current()->update([
            'otp_length' => 8,
            'otp_expiry_seconds' => 600,
            'resend_cooldown_seconds' => 90,
            'otp_login_enabled' => false,
        ]);

        $this->getJson('/api/app-config')
            ->assertOk()
            ->assertJsonPath('data.otp_login_enabled', false)
            ->assertJsonPath('data.otp_length', 8)
            ->assertJsonPath('data.otp_expiry_seconds', 600)
            ->assertJsonPath('data.resend_cooldown_seconds', 90);
    }

    public function test_admin_settings_update_is_reflected_by_public_config(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->put('/admin/settings', [
                'app_name' => 'MkRide Admin',
                'otp_login_enabled' => true,
                'phone_country_code' => '+251',
                'otp_length' => 7,
                'otp_expiry_seconds' => 420,
                'resend_cooldown_seconds' => 75,
                'max_verify_attempts' => 4,
                'sms_provider' => 'smsethiopia',
                'sms_sender_id' => 'MKRIDE',
                'sms_message_template' => 'Your {app_name} code is {code}.',
                'sms_api_key' => 'new-secret-key',
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('new-secret-key', AppSetting::current()->sms_api_key);

        $response = $this->getJson('/api/app-config');

        $response->assertOk()
            ->assertJsonPath('data.app_name', 'MkRide Admin')
            ->assertJsonPath('data.otp_length', 7)
            ->assertJsonPath('data.otp_expiry_seconds', 420)
            ->assertJsonPath('data.resend_cooldown_seconds', 75);

        $this->assertStringNotContainsString('new-secret-key', $response->getContent());
    }

    public function test_request_otp_is_rejected_when_admin_disables_login(): void
    {
        AppSetting::current()->update(['otp_login_enabled' => false]);

        $this->getJson('/api/app-config')
            ->assertOk()
            ->assertJsonPath('data.otp_login_enabled', false);

        $response = $this->postJson('/api/auth/request-otp', [
            'phone' => '0911000000',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.otp_login_enabled', false);

        $this->assertDatabaseCount('otp_codes', 0);
        $this->assertSame([], $this->sms->sent);
    }

    public function test_request_otp_uses_dynamic_length_and_enforces_cooldown(): void
    {
        AppSetting::current()->update([
            'otp_length' => 4,
            'otp_expiry_seconds' => 180,
            'resend_cooldown_seconds' => 60,
            'sms_message_template' => 'Code: {code}',
        ]);

        $response = $this->postJson('/api/auth/request-otp', [
            'phone' => '0911000000',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.phone', '+251911000000')
            ->assertJsonPath('data.expires_in', 180)
            ->assertJsonPath('data.resend_cooldown_seconds', 60)
            ->assertJsonPath('data.otp_length', 4);

        $this->assertDatabaseCount('otp_codes', 1);
        $this->assertSame('+251911000000', $this->sms->sent[0]['phone']);

        $code = $this->sentCode();
        $this->assertSame(4, strlen($code));

        $cooldown = $this->postJson('/api/auth/request-otp', [
            'phone' => '0911000000',
        ]);

        $cooldown->assertStatus(429)
            ->assertJsonPath('message', 'Please wait before requesting another code.');

        $this->assertIsInt($cooldown->json('retry_after_seconds'));
        $this->assertGreaterThan(0, $cooldown->json('retry_after_seconds'));
    }

    public function test_verify_otp_creates_user_and_returns_sanctum_token(): void
    {
        AppSetting::current()->update([
            'otp_length' => 6,
            'otp_expiry_seconds' => 300,
            'sms_message_template' => 'Code: {code}',
        ]);

        $this->postJson('/api/auth/request-otp', ['phone' => '0911000001'])->assertOk();
        $code = $this->sentCode();

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => '0911000001',
            'code' => $code,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.phone', '+251911000001')
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'user' => ['id', 'name', 'phone', 'email', 'is_admin'],
                ],
            ]);

        $this->assertDatabaseHas('customers', ['phone' => '+251911000001']);
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $token = $response->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.phone', '+251911000001');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    public function test_verify_otp_accepts_registration_profile_fields(): void
    {
        AppSetting::current()->update(['sms_message_template' => 'Code: {code}']);

        $this->postJson('/api/auth/request-otp', ['phone' => '0911000004'])->assertOk();

        $this->postJson('/api/auth/verify-otp', [
            'phone' => '0911000004',
            'code' => $this->sentCode(),
            'name' => 'Hanna Girma',
            'email' => 'hanna@example.com',
        ])->assertOk()
            ->assertJsonPath('data.user.name', 'Hanna Girma')
            ->assertJsonPath('data.user.email', 'hanna@example.com');

        $this->assertDatabaseHas('customers', [
            'phone' => '+251911000004',
            'name' => 'Hanna Girma',
            'email' => 'hanna@example.com',
        ]);
    }

    public function test_verify_otp_rejects_expired_code(): void
    {
        $phone = '+251911000002';
        OtpCode::query()->create([
            'phone' => $phone,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->subMinute(),
            'last_sent_at' => now()->subMinutes(6),
            'attempts' => 0,
            'max_attempts' => 5,
        ]);

        $this->postJson('/api/auth/verify-otp', [
            'phone' => $phone,
            'code' => '123456',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'This verification code has expired. Please request a new one.');
    }

    public function test_verify_otp_rejects_wrong_code_and_enforces_attempt_limit(): void
    {
        AppSetting::current()->update(['max_verify_attempts' => 2]);

        $phone = '+251911000003';
        OtpCode::query()->create([
            'phone' => $phone,
            'code_hash' => Hash::make('999999'),
            'expires_at' => now()->addMinutes(5),
            'last_sent_at' => now(),
            'attempts' => 0,
            'max_attempts' => 2,
        ]);

        $this->postJson('/api/auth/verify-otp', [
            'phone' => $phone,
            'code' => '000000',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'The verification code is incorrect.');

        $this->assertSame(1, OtpCode::query()->first()->attempts);

        $this->postJson('/api/auth/verify-otp', [
            'phone' => $phone,
            'code' => '000000',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Too many attempts. Please request a new code.');

        $this->assertSame(2, OtpCode::query()->first()->attempts);

        $this->postJson('/api/auth/verify-otp', [
            'phone' => $phone,
            'code' => '999999',
        ])->assertStatus(429)
            ->assertJsonPath('message', 'Too many attempts. Please request a new code.');
    }

    public function test_sms_delivery_failure_returns_safe_error_and_allows_retry(): void
    {
        AppSetting::current()->update(['sms_message_template' => 'Code: {code}']);
        $this->sms->shouldFail = true;

        $this->postJson('/api/auth/request-otp', ['phone' => '0911000005'])
            ->assertStatus(503)
            ->assertJsonPath('message', 'Unable to send the verification code. Please try again later.');

        $this->assertDatabaseCount('otp_codes', 0);

        $this->sms->shouldFail = false;

        $this->postJson('/api/auth/request-otp', ['phone' => '0911000005'])
            ->assertOk();

        $this->assertDatabaseCount('otp_codes', 1);
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = AppUser::factory()->create(['phone' => '+251911000006']);
        $current = $user->createToken('mobile')->plainTextToken;
        $other = $user->createToken('tablet')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$current)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withHeader('Authorization', 'Bearer '.$other)
            ->getJson('/api/auth/me')
            ->assertOk();
    }

    public function test_me_requires_a_bearer_token(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_verify_otp_rejects_email_belonging_to_another_account(): void
    {
        AppUser::factory()->create(['email' => 'taken@example.com']);

        AppSetting::current()->update(['sms_message_template' => 'Code: {code}']);

        $this->postJson('/api/auth/request-otp', ['phone' => '0911000007'])->assertOk();

        $this->postJson('/api/auth/verify-otp', [
            'phone' => '0911000007',
            'code' => $this->sentCode(),
            'email' => 'taken@example.com',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseMissing('customers', [
            'phone' => '+251911000007',
            'email' => 'taken@example.com',
        ]);
    }

    private function sentCode(): string
    {
        $message = $this->sms->sent[array_key_last($this->sms->sent)]['message'];

        preg_match('/\b(\d{4,8})\b/', $message, $matches);

        return $matches[1] ?? '';
    }
}
