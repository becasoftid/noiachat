<?php

namespace App\Modules\Contacts\Application\Services;

use App\Modules\Audit\Domain\Enums\AuditActionType;
use App\Modules\Contacts\Application\DTOs\UpsertContactDTO;
use App\Modules\Contacts\Domain\Repositories\ChannelRepositoryInterface;
use App\Modules\Contacts\Domain\Repositories\ContactRepositoryInterface;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Shared\Application\Services\AuditLogger;
use App\Modules\Shared\Domain\Exceptions\BusinessRuleException;
use App\Modules\Shared\Domain\ValueObjects\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContactService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly ContactRepositoryInterface $contacts,
        private readonly ChannelRepositoryInterface $channels,
    ) {}

    public function create(UpsertContactDTO $dto, int $userId, ?Request $request = null): Contact
    {
        $phone = PhoneNumber::from($dto->primaryPhone)->value();
        $channel = $this->channels->findBySlug('whatsapp');

        if (! $channel) {
            throw new BusinessRuleException('WhatsApp channel is not configured.');
        }

        if ($this->contacts->findByPrimaryPhone($phone)?->contactChannels()->where('channel_id', $channel->id)->where('is_active', true)->exists()) {
            throw new BusinessRuleException('The phone number is already active for this channel.');
        }

        return DB::transaction(function () use ($dto, $phone, $channel, $userId, $request): Contact {
            $contact = $this->contacts->create([
                'first_name' => $dto->firstName,
                'last_name' => $dto->lastName,
                'full_name' => trim($dto->firstName.' '.($dto->lastName ?? '')),
                'email' => $dto->email,
                'primary_phone' => $phone,
                'status' => $dto->status,
            ]);

            $contact->contactChannels()->create([
                'channel_id' => $channel->id,
                'phone' => $phone,
                'is_primary' => true,
                'is_active' => true,
            ]);

            $this->auditLogger->log($userId, AuditActionType::CREATE->value, 'contacts', Contact::class, $contact->id, null, $contact->toArray(), $request);

            return $contact;
        });
    }

    public function update(Contact $contact, UpsertContactDTO $dto, int $userId, ?Request $request = null): Contact
    {
        $oldValues = $contact->toArray();
        $phone = PhoneNumber::from($dto->primaryPhone)->value();

        $contact = $this->contacts->update($contact, [
            'first_name' => $dto->firstName,
            'last_name' => $dto->lastName,
            'full_name' => trim($dto->firstName.' '.($dto->lastName ?? '')),
            'email' => $dto->email,
            'primary_phone' => $phone,
            'status' => $dto->status,
        ]);

        $contact->contactChannels()->first()?->update(['phone' => $phone]);

        $this->auditLogger->log($userId, AuditActionType::UPDATE->value, 'contacts', Contact::class, $contact->id, $oldValues, $contact->toArray(), $request);

        return $contact;
    }
}
