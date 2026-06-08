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
        abort_unless($this->hasValidSignature($request), 403);

        ProcessWhatsAppWebhookJob::dispatch($request->all());

        return response()->json(['received' => true]);
    }

    private function hasValidSignature(Request $request): bool
    {
        $appSecret = (string) config('services.whatsapp.app_secret', '');

        if ($appSecret === '') {
            return true;
        }

        $signature = (string) $request->header('X-Hub-Signature-256', '');

        if (! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expectedSignature, $signature);
    }
}
