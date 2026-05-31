<?php

namespace App\Modules\Contacts\Infrastructure\Persistence\Repositories;

use App\Modules\Contacts\Domain\Repositories\ContactRepositoryInterface;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentContactRepository implements ContactRepositoryInterface
{
    public function paginateWithSearch(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return Contact::query()
            ->with(['contactConsents', 'messages'])
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
        return Contact::query()->where('primary_phone', $phone)->first();
    }

    public function ordered(): Collection
    {
        return Contact::query()->orderBy('full_name')->get();
    }

    public function findById(string $id): ?Contact
    {
        return Contact::query()->find($id);
    }
}
