<?php

namespace App\Modules\Users\Infrastructure\Persistence\Models;

use App\Models\User;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Membership;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['name', 'label'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }
}
