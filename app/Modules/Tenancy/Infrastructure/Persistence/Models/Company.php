<?php

namespace App\Modules\Tenancy\Infrastructure\Persistence\Models;

use App\Modules\Billing\Infrastructure\Persistence\Models\CompanySubscription;
use App\Modules\Billing\Infrastructure\Persistence\Models\SubscriptionChangeRequest;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['name', 'legal_name', 'tax_id', 'slug', 'status', 'timezone', 'settings'];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(CompanySubscription::class);
    }

    public function subscriptionChangeRequests(): HasMany
    {
        return $this->hasMany(SubscriptionChangeRequest::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(CompanySubscription::class)
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->latestOfMany();
    }
}
