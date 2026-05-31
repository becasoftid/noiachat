<?php

namespace Database\Seeders;

use App\Modules\Consents\Application\UseCases\GrantConsentUseCase;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use Illuminate\Database\Seeder;

class ConsentSeeder extends Seeder
{
    public function run(): void
    {
        $grant = app(GrantConsentUseCase::class);
        $adminId = \App\Models\User::where('email', env('NOIACHAT_ADMIN_EMAIL', env('AUDITCHAT_ADMIN_EMAIL', 'admin@noiachat.local')))->value('id');
        $channelId = Channel::where('slug', 'whatsapp')->value('id');

        Contact::all()->each(function (Contact $contact) use ($grant, $channelId, $adminId): void {
            if (! $contact->contactConsents()->where('channel_id', $channelId)->where('status', 'granted')->exists()) {
                $grant->execute($contact, $channelId, 'manual', $adminId);
            }
        });
    }
}
