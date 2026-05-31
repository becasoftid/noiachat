<?php

namespace App\Modules\Consents\Application\UseCases;

use App\Modules\Audit\Domain\Enums\AuditActionType;
use App\Modules\Consents\Domain\Enums\ConsentStatus;
use App\Modules\Consents\Domain\Repositories\ConsentRepositoryInterface;
use App\Modules\Consents\Infrastructure\Persistence\Models\ContactConsent;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Shared\Application\Services\AuditLogger;
use Illuminate\Http\Request;

class GrantConsentUseCase
{
    public function __construct(private readonly AuditLogger $auditLogger, private readonly ConsentRepositoryInterface $consents) {}

    public function execute(Contact $contact, int $channelId, string $source, int $userId, ?Request $request = null): ContactConsent
    {
        $consent = $this->consents->create([
            'contact_id' => $contact->id,
            'channel_id' => $channelId,
            'status' => ConsentStatus::GRANTED->value,
            'source' => $source,
            'granted_by_user_id' => $userId,
            'granted_at' => now(),
        ]);

        $this->auditLogger->log($userId, AuditActionType::CREATE->value, 'consents', ContactConsent::class, $consent->id, null, $consent->toArray(), $request);

        return $consent;
    }
}
