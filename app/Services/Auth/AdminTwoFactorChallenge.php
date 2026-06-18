<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AdminTwoFactorChallenge
{
    public const SESSION_KEY = 'auth.two_factor';

    public function start(Request $request, User $user, bool $remember = false): void
    {
        $code = (string) random_int(100000, 999999);
        $ttlMinutes = (int) config('noiachat.two_factor.code_ttl_minutes', 10);

        $payload = [
            'user_id' => $user->id,
            'remember' => $remember,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($ttlMinutes)->toISOString(),
            'attempts' => 0,
        ];

        if ($this->shouldExposeCode()) {
            $payload['plain_code'] = $code;
        }

        $request->session()->put(self::SESSION_KEY, $payload);

        $this->sendCode($user, $code, $ttlMinutes);
    }

    public function pending(Request $request): bool
    {
        return (bool) $request->session()->get(self::SESSION_KEY.'.user_id');
    }

    public function verify(Request $request, string $code): ?User
    {
        $payload = $request->session()->get(self::SESSION_KEY, []);

        if (! isset($payload['user_id'], $payload['code_hash'], $payload['expires_at'])) {
            return null;
        }

        if (now()->greaterThan($payload['expires_at'])) {
            $this->clear($request);

            return null;
        }

        if (($payload['attempts'] ?? 0) >= (int) config('noiachat.two_factor.max_attempts', 5)) {
            $this->clear($request);

            return null;
        }

        if (! Hash::check($code, $payload['code_hash'])) {
            $payload['attempts'] = ($payload['attempts'] ?? 0) + 1;
            $request->session()->put(self::SESSION_KEY, $payload);

            return null;
        }

        return User::query()->whereKey($payload['user_id'])->where('is_active', true)->first();
    }

    public function remember(Request $request): bool
    {
        return (bool) $request->session()->get(self::SESSION_KEY.'.remember', false);
    }

    public function clear(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    private function sendCode(User $user, string $code, int $ttlMinutes): void
    {
        try {
            Mail::raw(
                "Tu codigo de acceso a NoiaChat es {$code}. Expira en {$ttlMinutes} minutos.",
                fn ($message) => $message
                    ->to($user->email)
                    ->subject('Codigo de acceso NoiaChat')
            );
        } catch (Throwable $exception) {
            Log::warning('No se pudo enviar el codigo 2FA de administrador.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function shouldExposeCode(): bool
    {
        return (bool) config('noiachat.two_factor.expose_code_in_non_production', true)
            && ! app()->environment('production');
    }
}
