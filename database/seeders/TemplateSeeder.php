<?php

namespace Database\Seeders;

use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Messaging\Infrastructure\Persistence\Models\MessageTemplate;
use App\Modules\Messaging\Infrastructure\Persistence\Models\TemplateVersion;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $channel = Channel::where('slug', 'whatsapp')->firstOrFail();
        $template = MessageTemplate::updateOrCreate(['name' => 'recordatorio_pago', 'channel_id' => $channel->id], ['external_template_id' => 'recordatorio_pago', 'is_active' => true]);
        $version = TemplateVersion::updateOrCreate(['message_template_id' => $template->id, 'version' => 1], ['language' => 'es', 'body' => 'Hola {{1}}, este es un recordatorio de pago.', 'variable_count' => 1, 'is_active' => true]);
        $template->update(['current_version_id' => $version->id]);
    }
}
