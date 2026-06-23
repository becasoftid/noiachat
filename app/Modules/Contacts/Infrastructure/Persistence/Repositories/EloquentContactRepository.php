<?php

namespace App\Modules\Contacts\Infrastructure\Persistence\Repositories;

use App\Modules\Contacts\Domain\Repositories\ContactRepositoryInterface;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Tenancy\Application\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentContactRepository implements ContactRepositoryInterface
{
    public function paginateWithSearch(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->with(['contactChannels.channel', 'contactConsents', 'messages'])
            ->when($search, function ($query, $term): void {
                $query->where(function ($builder) use ($term): void {
                    $builder->where('full_name', 'like', "%{$term}%")
                        ->orWhere('primary_phone', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $attributes): Contact
    {
        return Contact::create($attributes);
    }

    public function update(Contact $contact, array $attributes): Contact
    {
        $contact->update($attributes);

        return $contact->fresh();
    }

    public function findByPrimaryPhone(string $phone): ?Contact
    {
        $contact = $this->query()->where('primary_phone', $phone)->first();

        if ($contact) {
            return $contact;
        }

        $normalizedPhone = $this->normalizePhone($phone);

        if ($normalizedPhone === '') {
            return null;
        }

        return $this->query()
            ->with('contactChannels')
            ->get()
            ->first(function (Contact $contact) use ($normalizedPhone): bool {
                $phones = collect([$contact->primary_phone])
                    ->merge($contact->contactChannels->pluck('phone'))
                    ->map(fn (?string $phone): string => $this->normalizePhone((string) $phone))
                    ->filter();

                return $phones->contains(fn (string $phone): bool => $this->phonesMatch($phone, $normalizedPhone));
            });
    }

    public function ordered(): Collection
    {
        return $this->query()->orderBy('full_name')->get();
    }

    public function findById(string $id): ?Contact
    {
        return $this->query()->find($id);
    }

    private function query(): Builder
    {
        $query = Contact::query();
        $context = app(TenantContext::class);

        return $context->companyId() !== null ? $query->forTenantContext($context) : $query;
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    private function phonesMatch(string $storedPhone, string $incomingPhone): bool
    {
        if ($storedPhone === $incomingPhone) {
            return true;
        }

        return strlen($storedPhone) >= 7
            && strlen($incomingPhone) >= 7
            && (str_ends_with($storedPhone, $incomingPhone) || str_ends_with($incomingPhone, $storedPhone));
    }
}
