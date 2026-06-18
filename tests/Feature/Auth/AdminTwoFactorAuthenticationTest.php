<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminTwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_administrators_must_complete_two_factor_challenge(): void
    {
        $admin = $this->adminUser();

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response
            ->assertRedirect(route('two-factor.login'))
            ->assertSessionHas('auth.two_factor.user_id', $admin->id)
            ->assertSessionHas('auth.two_factor.plain_code');

        $this->assertGuest();
    }

    public function test_administrators_can_authenticate_with_valid_two_factor_code(): void
    {
        $admin = $this->adminUser();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $code = session('auth.two_factor.plain_code');

        $response = $this->post(route('two-factor.login'), [
            'code' => $code,
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($admin);
        $this->assertFalse(session()->has('auth.two_factor'));
    }

    public function test_invalid_two_factor_code_keeps_administrator_out(): void
    {
        $admin = $this->adminUser();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response = $this->post(route('two-factor.login'), [
            'code' => '000000',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_non_admin_users_keep_standard_login_flow(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    private function adminUser(): User
    {
        $admin = User::factory()->create();
        $role = Role::query()->where('name', 'admin')->firstOrFail();

        $admin->roles()->attach($role);

        return $admin->refresh();
    }
}
