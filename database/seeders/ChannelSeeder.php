<?php

namespace Database\Seeders;

use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Tenancy\Application\Support\TenancyDefaults;
use Illuminate\Database\Seeder;

class ChannelSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = TenancyDefaults::companyId();
        $branchId = TenancyDefaults::branchId($companyId);

        Channel::updateOrCreate(
            ['company_id' => $companyId, 'branch_id' => $branchId, 'slug' => 'whatsapp'],
            ['name' => 'WhatsApp', 'is_active' => true, 'settings' => ['provider' => 'whatsapp_cloud']],
        );
    }
}
