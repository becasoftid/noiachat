<?php

namespace Database\Seeders;

use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'super_admin', 'label' => 'Super admin'],
            ['name' => 'company_admin', 'label' => 'Company admin'],
            ['name' => 'branch_manager', 'label' => 'Branch manager'],
            ['name' => 'operator', 'label' => 'Operator'],
            ['name' => 'auditor', 'label' => 'Auditor'],
            ['name' => 'admin', 'label' => 'Administrator'],
        ] as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
