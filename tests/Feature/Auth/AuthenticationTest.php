<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_admin_users_can_authenticate(): void
    {
        $user = User::factory()->create(['is_admin' => true, 'username' => 'adminuser']);

        $response = $this->post('/login', [
            'username' => 'adminuser',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_non_admin_users_cannot_authenticate(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'role' => 'none', 'username' => 'regular']);

        $response = $this->from('/login')->post('/login', [
            'username' => 'regular',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
        $response->assertRedirect('/login');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create(['is_admin' => true, 'username' => 'adminuser2']);

        $this->post('/login', [
            'username' => 'adminuser2',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
