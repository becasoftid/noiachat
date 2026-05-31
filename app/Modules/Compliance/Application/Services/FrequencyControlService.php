<?php

namespace App\Modules\Compliance\Application\Services;

use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use Carbon\CarbonImmutable;

class FrequencyControlService
{
    public function exceeded(string $contactId, int $limit = 10, int $hours = 24): bool
    {
        return Message::query()
            ->where('contact_id', $contactId)
            ->whereIn('status', ['queued', 'sending', 'sent', 'delivered', 'read'])
            ->where('created_at', '>=', CarbonImmutable::now()->subHours($hours))
            ->count() >= $limit;
    }
}
