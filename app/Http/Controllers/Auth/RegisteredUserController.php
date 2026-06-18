<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\TrialCompanyRegistration;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(private readonly TrialCompanyRegistration $trialCompanyRegistration)
    {
    }

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'company_name' => ['required', 'string', 'max:255'],
            'company_legal_name' => ['nullable', 'string', 'max:255'],
            'company_tax_id' => ['nullable', 'string', 'max:80'],
            'branch_name' => ['required', 'string', 'max:255'],
            'branch_city' => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        [$user, $membership] = DB::transaction(function () use ($request): array {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $membership = $this->trialCompanyRegistration->createFor($user, $request->only([
                'company_name',
                'company_legal_name',
                'company_tax_id',
                'branch_name',
                'branch_city',
            ]));

            return [$user, $membership];
        });

        event(new Registered($user));

        Auth::login($user);

        $request->session()->put([
            'tenant.membership_id' => $membership->id,
            'tenant.company_id' => $membership->company_id,
            'tenant.branch_id' => $membership->branch_id,
        ]);

        return redirect(route('dashboard', absolute: false));
    }
}
