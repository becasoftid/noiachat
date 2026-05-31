<?php

namespace App\Modules\Contacts\Domain\Repositories;

use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ContactRepositoryInterface
{
    public function paginateWithSearch(?string $search = null, int $perPage = 15): LengthAwarePaginator;

    public function create(array $attributes): Contact;

    public function update(Contact $contact, array $attributes): Contact;

    public function findByPrimaryPhone(string $phone): ?Contact;

    public function ordered(): Collection;

    public function findById(string $id): ?Contact;
}
