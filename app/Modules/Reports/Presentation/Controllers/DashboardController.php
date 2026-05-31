<?php

namespace App\Modules\Reports\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Consents\Infrastructure\Persistence\Models\ContactBlacklist;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Messaging\Infrastructure\Persistence\Models\InboundMessage;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $messagesQuery = Message::query()
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo));

        $inboundQuery = InboundMessage::query()
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo));

        $blacklistQuery = ContactBlacklist::query()
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo));

        return view('noia.dashboard.index', [
            'stats' => [
                'Total contactos' => Contact::count(),
                'Mensajes enviados' => (clone $messagesQuery)->whereIn('status', ['sent', 'delivered', 'read'])->count(),
                'Mensajes fallidos' => (clone $messagesQuery)->whereIn('status', ['failed', 'bounced'])->count(),
                'No contactar' => $blacklistQuery->count(),
                'Mensajes recibidos' => $inboundQuery->count(),
            ],
        ]);
    }
}
