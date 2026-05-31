<?php

namespace App\Modules\Compliance\Domain\Policies;

use App\Modules\Compliance\Application\Services\ComplianceDecisionService;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Messaging\Infrastructure\Persistence\Models\MessageTemplate;

class MessageEligibilityPolicy
{
    public function __construct(private readonly ComplianceDecisionService $service) {}

    public function evaluate(Contact $contact, int $channelId, ?MessageTemplate $template = null): string
    {
        return $this->service->decide($contact, $channelId, $template)->value;
    }
}
