<?php

namespace App\Modules\Billing\Infrastructure\Persistence\Models;

use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySubscription extends Model
{
    protected $fillable = [
        'company_id',
        'plan_id',
        'status',
        'trial_started_at',
        'trial_ends_at',
        'current_period_started_at',
        'current_period_ends_at',
        'cancelled_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'trial_started_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'current_period_started_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isOperational(): bool
    {
        return match ($this->status) {
            'active' => true,
            'trialing' => $this->trial_ends_at === null || $this->trial_ends_at->isFuture(),
            'past_due' => true,
            default => false,
        };
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->status === 'trialing' && $this->trial_ends_at !== null && $this->trial_ends_at->isPast());
    }
}
