<?php

namespace Database\Seeders;

use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['name' => 'admin', 'label' => 'Administrator'], ['name' => 'operator', 'label' => 'Operator'], ['name' => 'auditor', 'label' => 'Auditor']] as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
