<?php

namespace App\Modules\Contacts\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Domain\Enums\AuditActionType;
use App\Modules\Consents\Infrastructure\Persistence\Models\ContactBlacklist;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Contacts\Presentation\Requests\StoreBlacklistRequest;
use App\Modules\Shared\Application\Services\AuditLogger;

class ContactBlacklistController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function store(StoreBlacklistRequest $request, Contact $contact)
    {
        $entry = ContactBlacklist::updateOrCreate(
            ['contact_id' => $contact->id, 'channel_id' => (int) $request->integer('channel_id')],
            ['reason' => $request->string('reason')->toString(), 'created_by_user_id' => $request->user()->id, 'created_at' => now()],
        );

        $this->auditLogger->log($request->user()->id, AuditActionType::BLOCK->value, 'compliance', ContactBlacklist::class, $entry->id, null, $entry->toArray(), $request);

        return back()->with('status', 'Contacto agregado a lista de exclusión.');
    }

    public function destroy(Contact $contact, ContactBlacklist $blacklist)
    {
        abort_unless($blacklist->contact_id === $contact->id, 404);

        $old = $blacklist->toArray();
        $blacklist->delete();
        $this->auditLogger->log(auth()->id(), AuditActionType::UPDATE->value, 'compliance', ContactBlacklist::class, $old['id'] ?? null, $old, ['removed' => true], request());

        return back()->with('status', 'Contacto retirado de lista de exclusión.');
    }
}
