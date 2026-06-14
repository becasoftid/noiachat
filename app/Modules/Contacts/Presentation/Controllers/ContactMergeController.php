<?php

namespace App\Modules\Contacts\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contacts\Application\Services\ContactMergeService;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Contacts\Presentation\Requests\MergeContactRequest;

class ContactMergeController extends Controller
{
    public function store(MergeContactRequest $request, Contact $contact, ContactMergeService $merger)
    {
        $target = Contact::query()->findOrFail($request->string('target_contact_id')->toString());

        $merger->merge($contact, $target, $request->user()->id, $request);

        return redirect()->route('contacts.show', $target)->with('status', 'Contactos fusionados.');
    }
}
