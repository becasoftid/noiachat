<?php

namespace App\Modules\Billing\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'price_cents',
        'currency',
        'billing_period',
        'trial_days',
        'max_users',
        'max_branches',
        'max_contacts',
        'max_whatsapp_channels',
        'metadata',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'trial_days' => 'integer',
            'max_users' => 'integer',
            'max_branches' => 'integer',
            'max_contacts' => 'integer',
            'max_whatsapp_channels' => 'integer',
            'metadata' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'plan_features')
            ->withPivot(['enabled', 'limits'])
            ->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(CompanySubscription::class);
    }
}
