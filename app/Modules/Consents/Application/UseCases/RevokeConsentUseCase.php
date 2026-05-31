<?php

namespace App\Modules\Consents\Application\UseCases;

use App\Modules\Audit\Domain\Enums\AuditActionType;
use App\Modules\Consents\Domain\Enums\ConsentStatus;
use App\Modules\Consents\Domain\Repositories\BlacklistRepositoryInterface;
use App\Modules\Consents\Domain\Repositories\ConsentRepositoryInterface;
use App\Modules\Consents\Infrastructure\Persistence\Models\ContactConsent;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Shared\Application\Services\AuditLogger;
use Illuminate\Http\Request;

class RevokeConsentUseCase
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly ConsentRepositoryInterface $consents,
        private readonly BlacklistRepositoryInterface $blacklist,
    ) {}

    public function execute(Contact $contact, int $channelId, int $userId, ?Request $request = null): ContactConsent
    {
        $consent = $this->consents->create([
            'contact_id' => $contact->id,
            'channel_id' => $channelId,
            'status' => ConsentStatus::REVOKED->value,
            'source' => 'manual',
            'revoked_by_user_id' => $userId,
            'revoked_at' => now(),
        ]);

        $this->blacklist->upsert(
            ['contact_id' => $contact->id, 'channel_id' => $channelId],
            ['reason' => 'consent_revoked', 'created_by_user_id' => $userId, 'created_at' => now()],
        );

        $this->auditLogger->log($userId, AuditActionType::REVOKE_CONSENT->value, 'consents', ContactConsent::class, $consent->id, null, $consent->toArray(), $request);

        return $consent;
    }
}
