<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Branch;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Membership;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => env('NOIACHAT_ADMIN_EMAIL', env('AUDITCHAT_ADMIN_EMAIL', 'admin@noiachat.local'))],
            ['name' => env('NOIACHAT_ADMIN_NAME', env('AUDITCHAT_ADMIN_NAME', 'Admin NoiaChat')), 'password' => Hash::make(env('NOIACHAT_ADMIN_PASSWORD', env('AUDITCHAT_ADMIN_PASSWORD', 'Password'))), 'is_active' => true],
        );

        $adminRole = Role::where('name', 'admin')->firstOrFail();

        $user->roles()->syncWithoutDetaching([$adminRole->id]);

        $company = Company::query()->where('slug', env('NOIACHAT_DEFAULT_COMPANY_SLUG', 'default'))->first();
        $branch = $company
            ? Branch::query()->where('company_id', $company->id)->where('code', env('NOIACHAT_DEFAULT_BRANCH_CODE', 'principal'))->first()
            : null;

        if ($company !== null) {
            Membership::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'branch_id' => $branch?->id,
                    'role_id' => $adminRole->id,
                ],
                [
                    'is_default' => true,
                    'is_active' => true,
                ],
            );
        }
    }
}
