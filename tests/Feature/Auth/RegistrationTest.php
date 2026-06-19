<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Billing\Infrastructure\Persistence\Models\CompanySubscription;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Branch;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Membership;
use Database\Seeders\BillingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BillingSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'company_name' => 'Clinica Aurora',
            'company_legal_name' => 'Clinica Aurora SAS',
            'company_tax_id' => '900123456',
            'branch_name' => 'Sede Principal',
            'branch_city' => 'Bogota',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $company = Company::query()->where('name', 'Clinica Aurora')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->where('name', 'Sede Principal')->firstOrFail();
        $membership = Membership::query()
            ->where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();
        $subscription = CompanySubscription::query()
            ->with('plan')
            ->where('company_id', $company->id)
            ->firstOrFail();

        $this->assertTrue($user->hasRole('company_admin'));
        $this->assertTrue($membership->is_default);
        $this->assertTrue($membership->is_active);
        $this->assertSame('basic_trial', $subscription->plan->code);
        $this->assertSame('trialing', $subscription->status);
        $this->assertNotNull($subscription->trial_ends_at);
        $this->assertSame($membership->id, session('tenant.membership_id'));
    }

    public function test_registration_requires_company_and_branch(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['company_name', 'branch_name']);
        $this->assertGuest();
    }

    public function test_registration_validation_messages_are_in_spanish(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'company_name' => 'Clinica Aurora',
            'branch_name' => 'Sede Principal',
            'password' => 'password',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors([
            'password' => 'La confirmacion de contrasena no coincide.',
        ]);
        $this->assertGuest();
    }
}
