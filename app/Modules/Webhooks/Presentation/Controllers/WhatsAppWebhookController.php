<?php

namespace App\Modules\Webhooks\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Messaging\Application\Services\WhatsAppChannelConfig;
use App\Modules\Webhooks\Infrastructure\Jobs\ProcessWhatsAppWebhookJob;
use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    public function __construct(private readonly WhatsAppChannelConfig $channelConfig)
    {
    }

    public function verify(Request $request)
    {
        abort_unless($this->channelConfig->verifyTokens()->contains((string) $request->input('hub_verify_token')), 403);

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
        $appSecrets = $this->channelConfig->appSecrets();

        if ($appSecrets->isEmpty()) {
            return true;
        }

        $signature = (string) $request->header('X-Hub-Signature-256', '');

        if (! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        return $appSecrets->contains(function (string $appSecret) use ($request, $signature): bool {
            $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

            return hash_equals($expectedSignature, $signature);
        });
    }
}
