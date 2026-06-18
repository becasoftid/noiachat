<?php

namespace App\Modules\Tenancy\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TenantContextController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'membership_id' => ['required', 'integer', 'exists:memberships,id'],
        ]);

        $membership = Membership::query()
            ->with(['company', 'branch'])
            ->where('id', $validated['membership_id'])
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->whereHas('company', fn ($query) => $query->where('status', 'active'))
            ->where(function ($query): void {
                $query->whereNull('branch_id')
                    ->orWhereHas('branch', fn ($branchQuery) => $branchQuery->where('is_active', true));
            })
            ->first();

        if ($membership === null) {
            throw ValidationException::withMessages([
                'membership_id' => 'No tienes acceso a la empresa o sede seleccionada.',
            ]);
        }

        $request->session()->put([
            'tenant.membership_id' => $membership->id,
            'tenant.company_id' => $membership->company_id,
            'tenant.branch_id' => $membership->branch_id,
        ]);

        return back()->with('status', 'Contexto operativo actualizado.');
    }
}
