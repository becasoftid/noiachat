<?php

namespace App\Modules\Reports\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Consents\Infrastructure\Persistence\Models\ContactBlacklist;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Messaging\Infrastructure\Persistence\Models\InboundMessage;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Tenancy\Application\Services\TenantContext;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $branches = $this->tenantBranches();
        $branchId = $request->string('branch_id')->toString();
        $branchId = $branchId !== '' && $branches->contains('id', $branchId) ? $branchId : null;

        $contactsQuery = Contact::query()
            ->forTenantContext()
            ->when($branchId, fn ($q, $value) => $q->where('branch_id', $value));

        $messagesQuery = Message::query()
            ->forTenantContext()
            ->when($branchId, fn ($q, $value) => $q->where('branch_id', $value))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo));

        $inboundQuery = InboundMessage::query()
            ->forTenantContext()
            ->when($branchId, fn ($q, $value) => $q->where('branch_id', $value))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo));

        $blacklistQuery = ContactBlacklist::query()
            ->forTenantContext()
            ->when($branchId, fn ($q, $value) => $q->where('branch_id', $value))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo));

        return view('noia.dashboard.index', [
            'stats' => [
                'Total contactos' => $contactsQuery->count(),
                'Mensajes enviados' => (clone $messagesQuery)->whereIn('status', ['sent', 'delivered', 'read'])->count(),
                'Mensajes fallidos' => (clone $messagesQuery)->whereIn('status', ['failed', 'bounced'])->count(),
                'No contactar' => $blacklistQuery->count(),
                'Mensajes recibidos' => $inboundQuery->count(),
            ],
            'branches' => $branches,
        ]);
    }

    private function tenantBranches(): Collection
    {
        $context = app(TenantContext::class);

        if ($context->companyId() === null || $context->branchId() !== null) {
            return collect();
        }

        return Branch::query()
            ->where('company_id', $context->companyId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
