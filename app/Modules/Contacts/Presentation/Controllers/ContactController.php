<?php

namespace App\Modules\Contacts\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contacts\Application\DTOs\UpsertContactDTO;
use App\Modules\Contacts\Application\Services\ContactService;
use App\Modules\Contacts\Domain\Repositories\ContactRepositoryInterface;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Contacts\Presentation\Requests\StoreContactRequest;
use App\Modules\Contacts\Presentation\Requests\UpdateContactRequest;
use App\Modules\Shared\Domain\Exceptions\BusinessRuleException;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __construct(
        private readonly ContactService $service,
        private readonly ContactRepositoryInterface $contacts,
    ) {}

    public function index(Request $request)
    {
        return view('noia.contacts.index', [
            'contacts' => $this->contacts->paginateWithSearch($request->string('search')->toString() ?: null),
        ]);
    }

    public function create()
    {
        return view('noia.contacts.create');
    }

    public function store(StoreContactRequest $request)
    {
        try {
            $contact = $this->service->create(new UpsertContactDTO(
                $request->string('first_name')->toString(),
                $request->input('last_name'),
                $request->input('email'),
                $request->string('primary_phone')->toString(),
                $request->input('status', 'active'),
            ), $request->user()->id, $request);
        } catch (BusinessRuleException $exception) {
            return back()->withInput()->withErrors(['primary_phone' => $exception->getMessage()]);
        }

        return redirect()->route('contacts.show', $contact)->with('status', 'Contacto creado.');
    }

    public function show(Contact $contact)
    {
        $contact->load([
            'contactConsents.channel',
            'contactConsents.grantedBy',
            'contactConsents.revokedBy',
            'contactBlacklist',
            'messages.events',
        ]);
        $channels = Channel::query()->forTenantContext()->where('is_active', true)->get();
        $mergeCandidates = Contact::query()
            ->forTenantContext()
            ->whereKeyNot($contact->id)
            ->orderBy('full_name')
            ->get();

        return view('noia.contacts.show', compact('contact', 'channels', 'mergeCandidates'));
    }

    public function edit(Contact $contact)
    {
        return view('noia.contacts.edit', compact('contact'));
    }

    public function update(UpdateContactRequest $request, Contact $contact)
    {
        try {
            $this->service->update($contact, new UpsertContactDTO(
                $request->string('first_name')->toString(),
                $request->input('last_name'),
                $request->input('email'),
                $request->string('primary_phone')->toString(),
                $request->input('status', 'active'),
            ), $request->user()->id, $request);
        } catch (BusinessRuleException $exception) {
            return back()->withInput()->withErrors(['primary_phone' => $exception->getMessage()]);
        }

        return redirect()->route('contacts.show', $contact)->with('status', 'Contacto actualizado.');
    }
}
