<?php

namespace Database\Seeders;

use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use Illuminate\Database\Seeder;

class ChannelSeeder extends Seeder
{
    public function run(): void
    {
        Channel::updateOrCreate(['slug' => 'whatsapp'], ['name' => 'WhatsApp', 'is_active' => true, 'settings' => ['provider' => 'whatsapp_cloud']]);
    }
}
