<?php

namespace App\Modules\Shared\Application\Services;

use App\Modules\Audit\Infrastructure\Persistence\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogger
{
    public function log(
        ?int $userId,
        string $action,
        string $module,
        ?string $targetType = null,
        mixed $targetId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Request $request = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'target_type' => $targetType,
            'target_id' => $targetId ? (string) $targetId : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'old_values_json' => $oldValues,
            'new_values_json' => $newValues,
        ]);
    }
}
