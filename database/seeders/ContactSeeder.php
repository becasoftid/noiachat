<?php

namespace Database\Seeders;

use App\Modules\Contacts\Application\DTOs\UpsertContactDTO;
use App\Modules\Contacts\Application\Services\ContactService;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(ContactService::class);
        $adminId = \App\Models\User::where('email', env('NOIACHAT_ADMIN_EMAIL', env('AUDITCHAT_ADMIN_EMAIL', 'admin@noiachat.local')))->value('id');

        foreach ([new UpsertContactDTO('Ana', 'Gomez', 'ana@example.com', '573001112233'), new UpsertContactDTO('Luis', 'Perez', 'luis@example.com', '573004445566')] as $dto) {
            if (! Contact::where('primary_phone', preg_replace('/\D+/', '', $dto->primaryPhone))->exists()) {
                $service->create($dto, $adminId);
            }
        }
    }
}
