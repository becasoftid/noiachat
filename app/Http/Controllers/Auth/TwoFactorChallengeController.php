<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AdminTwoFactorChallenge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function __construct(private readonly AdminTwoFactorChallenge $challenge)
    {
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->challenge->pending($request)) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $this->challenge->verify($request, $validated['code']);

        if ($user === null) {
            throw ValidationException::withMessages([
                'code' => __('El codigo ingresado no es valido o expiro.'),
            ]);
        }

        $remember = $this->challenge->remember($request);
        $intended = $request->session()->pull('url.intended', route('dashboard', absolute: false));

        Auth::guard('web')->login($user, $remember);
        $this->challenge->clear($request);
        $request->session()->regenerate();

        return redirect()->intended($intended);
    }
}
