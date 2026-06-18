<?php

namespace App\Modules\Tenancy\Application\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenancyDefaults
{
    public static function companyId(): string
    {
        $slug = env('NOIACHAT_DEFAULT_COMPANY_SLUG', 'default');
        $company = DB::table('companies')->where('slug', $slug)->first(['id']);

        if ($company !== null) {
            return $company->id;
        }

        $id = (string) Str::uuid();

        DB::table('companies')->insert([
            'id' => $id,
            'name' => env('NOIACHAT_DEFAULT_COMPANY_NAME', 'Empresa principal'),
            'legal_name' => env('NOIACHAT_DEFAULT_COMPANY_LEGAL_NAME'),
            'tax_id' => env('NOIACHAT_DEFAULT_COMPANY_TAX_ID'),
            'slug' => $slug,
            'status' => 'active',
            'timezone' => config('app.display_timezone', 'America/Bogota'),
            'settings' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    public static function branchId(?string $companyId = null): string
    {
        $companyId ??= self::companyId();
        $code = env('NOIACHAT_DEFAULT_BRANCH_CODE', 'principal');
        $branch = DB::table('branches')
            ->where('company_id', $companyId)
            ->where('code', $code)
            ->first(['id']);

        if ($branch !== null) {
            return $branch->id;
        }

        $id = (string) Str::uuid();

        DB::table('branches')->insert([
            'id' => $id,
            'company_id' => $companyId,
            'name' => env('NOIACHAT_DEFAULT_BRANCH_NAME', 'Sede principal'),
            'code' => $code,
            'timezone' => config('app.display_timezone', 'America/Bogota'),
            'is_active' => true,
            'settings' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    public static function attributes(): array
    {
        $companyId = self::companyId();

        return [
            'company_id' => $companyId,
            'branch_id' => self::branchId($companyId),
        ];
    }
}
