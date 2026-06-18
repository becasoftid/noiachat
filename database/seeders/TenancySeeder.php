<?php

namespace Database\Seeders;

use App\Modules\Tenancy\Infrastructure\Persistence\Models\Branch;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;
use Illuminate\Database\Seeder;

class TenancySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::updateOrCreate(
            ['slug' => env('NOIACHAT_DEFAULT_COMPANY_SLUG', 'default')],
            [
                'name' => env('NOIACHAT_DEFAULT_COMPANY_NAME', 'Empresa principal'),
                'legal_name' => env('NOIACHAT_DEFAULT_COMPANY_LEGAL_NAME'),
                'tax_id' => env('NOIACHAT_DEFAULT_COMPANY_TAX_ID'),
                'status' => 'active',
                'timezone' => config('app.display_timezone', 'America/Bogota'),
                'settings' => [],
            ],
        );

        Branch::updateOrCreate(
            [
                'company_id' => $company->id,
                'code' => env('NOIACHAT_DEFAULT_BRANCH_CODE', 'principal'),
            ],
            [
                'name' => env('NOIACHAT_DEFAULT_BRANCH_NAME', 'Sede principal'),
                'timezone' => $company->timezone,
                'is_active' => true,
                'settings' => [],
            ],
        );
    }
}
