<?php

namespace App\Modules\Billing\Infrastructure\Persistence\Models;

use App\Models\User;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionChangeRequest extends Model
{
    protected $fillable = [
        'company_id',
        'requested_by',
        'current_plan_id',
        'requested_plan_id',
        'status',
        'message',
        'admin_notes',
        'resolved_by',
        'resolved_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function currentPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'current_plan_id');
    }

    public function requestedPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'requested_plan_id');
    }
}
