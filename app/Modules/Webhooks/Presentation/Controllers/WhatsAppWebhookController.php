<?php

namespace App\Modules\Webhooks\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Webhooks\Infrastructure\Jobs\ProcessWhatsAppWebhookJob;
use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        abort_unless($request->input('hub_verify_token') === config('services.whatsapp.webhook_verify_token'), 403);

        return response($request->input('hub_challenge'), 200);
    }

    public function receive(Request $request)
    {
        ProcessWhatsAppWebhookJob::dispatch($request->all());

        return response()->json(['received' => true]);
    }
}
