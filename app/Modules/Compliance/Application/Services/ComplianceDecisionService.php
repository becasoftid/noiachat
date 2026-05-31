<?php

namespace App\Modules\Compliance\Application\Services;

use App\Modules\Compliance\Domain\Enums\EligibilityStatus;
use App\Modules\Contacts\Domain\Enums\ContactStatus;
use App\Modules\Contacts\Domain\Repositories\ChannelRepositoryInterface;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Consents\Domain\Enums\ConsentStatus;
use App\Modules\Consents\Domain\Repositories\BlacklistRepositoryInterface;
use App\Modules\Consents\Domain\Repositories\ConsentRepositoryInterface;
use App\Modules\Messaging\Infrastructure\Persistence\Models\MessageTemplate;

class ComplianceDecisionService
{
    public function __construct(
        private readonly FrequencyControlService $frequencyControl,
        private readonly ChannelRepositoryInterface $channels,
        private readonly ConsentRepositoryInterface $consents,
        private readonly BlacklistRepositoryInterface $blacklist,
    ) {}

    public function decide(Contact $contact, int $channelId, ?MessageTemplate $template = null): EligibilityStatus
    {
        $channel = $this->channels->findBySlug('whatsapp');

        if (! $channel || $channel->id !== $channelId || ! $channel->is_active) {
            return EligibilityStatus::BLOCKED_CHANNEL_INACTIVE;
        }

        if ($contact->status !== ContactStatus::ACTIVE->value) {
            return EligibilityStatus::BLOCKED_INVALID_CONTACT;
        }

        if (! $this->consents->grantedExists($contact->id, $channelId)) {
            return EligibilityStatus::BLOCKED_NO_CONSENT;
        }

        if ($this->blacklist->existsForContactAndChannel($contact->id, $channelId)) {
            return EligibilityStatus::BLOCKED_BLACKLIST;
        }

        if ($this->frequencyControl->exceeded($contact->id)) {
            return EligibilityStatus::BLOCKED_FREQUENCY;
        }

        if ($template && ! $template->is_active) {
            return EligibilityStatus::BLOCKED_TEMPLATE_INACTIVE;
        }

        return EligibilityStatus::ALLOWED;
    }
}
