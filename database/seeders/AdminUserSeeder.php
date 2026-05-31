<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => env('NOIACHAT_ADMIN_EMAIL', env('AUDITCHAT_ADMIN_EMAIL', 'admin@noiachat.local'))],
            ['name' => env('NOIACHAT_ADMIN_NAME', env('AUDITCHAT_ADMIN_NAME', 'Admin NoiaChat')), 'password' => Hash::make(env('NOIACHAT_ADMIN_PASSWORD', env('AUDITCHAT_ADMIN_PASSWORD', 'password'))), 'is_active' => true],
        );

        $user->roles()->syncWithoutDetaching([Role::where('name', 'admin')->firstOrFail()->id]);
    }
}
