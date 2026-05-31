<?php

namespace App\Modules\Contacts\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Consents\Application\UseCases\GrantConsentUseCase;
use App\Modules\Consents\Application\UseCases\RevokeConsentUseCase;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Contacts\Presentation\Requests\RevokeConsentRequest;
use App\Modules\Contacts\Presentation\Requests\StoreConsentRequest;

class ContactConsentController extends Controller
{
    public function __construct(private readonly GrantConsentUseCase $grantConsent, private readonly RevokeConsentUseCase $revokeConsent) {}

    public function store(StoreConsentRequest $request, Contact $contact)
    {
        $this->grantConsent->execute($contact, (int) $request->integer('channel_id'), $request->string('source')->toString(), $request->user()->id, $request);

        return back()->with('status', 'Consentimiento registrado.');
    }

    public function revoke(RevokeConsentRequest $request, Contact $contact)
    {
        $this->revokeConsent->execute($contact, (int) $request->integer('channel_id'), $request->user()->id, $request);

        return back()->with('status', 'Consentimiento revocado.');
    }
}
