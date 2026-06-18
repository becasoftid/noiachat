<?php

namespace App\Modules\Tenancy\Application\Services;

use App\Modules\Tenancy\Infrastructure\Persistence\Models\Branch;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Membership;
use Illuminate\Support\Collection;

class TenantContext
{
    private ?Membership $membership = null;

    /** @var Collection<int, Membership> */
    private Collection $memberships;

    public function __construct()
    {
        $this->memberships = collect();
    }

    /**
     * @param  Collection<int, Membership>  $memberships
     */
    public function setMemberships(Collection $memberships): void
    {
        $this->memberships = $memberships->values();
    }

    public function setMembership(?Membership $membership): void
    {
        $this->membership = $membership;
    }

    public function membership(): ?Membership
    {
        return $this->membership;
    }

    /**
     * @return Collection<int, Membership>
     */
    public function memberships(): Collection
    {
        return $this->memberships;
    }

    public function company(): ?Company
    {
        return $this->membership?->company;
    }

    public function branch(): ?Branch
    {
        return $this->membership?->branch;
    }

    public function companyId(): ?string
    {
        return $this->membership?->company_id;
    }

    public function branchId(): ?string
    {
        return $this->membership?->branch_id;
    }

    public function hasMultipleMemberships(): bool
    {
        return $this->memberships->count() > 1;
    }
}
