<?php

namespace App\Modules\Tenancy\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Domain\Enums\AuditActionType;
use App\Modules\Billing\Application\Services\PlanLimitService;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Messaging\Application\Services\WhatsAppConnectionTestService;
use App\Modules\Messaging\Application\Services\WhatsAppTemplateSyncService;
use App\Modules\Shared\Application\Services\AuditLogger;
use App\Modules\Tenancy\Application\Services\TenantContext;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Branch;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;
use App\Modules\Tenancy\Presentation\Requests\CommercialWhatsAppChannelRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class WhatsAppIntegrationController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly PlanLimitService $planLimits,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index()
    {
        $company = $this->activeCompany();

        return view('noia.tenancy.whatsapp.index', [
            'company' => $company,
            'branches' => Branch::query()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'channels' => Channel::query()
                ->forTenantContext($this->tenantContext)
                ->where('slug', 'whatsapp')
                ->with(['branch'])
                ->withCount(['messages', 'conversations'])
                ->orderBy('name')
                ->get()
                ->map(function (Channel $channel): Channel {
                    $channel->setAttribute('operational_status', $this->operationalStatus($channel));

                    return $channel;
                }),
        ]);
    }

    public function store(CommercialWhatsAppChannelRequest $request)
    {
        $company = $this->activeCompany();
        $branchId = $this->validatedBranchId($request, $company);

        $alreadyExists = Channel::query()
            ->where('company_id', $company->id)
            ->where('slug', 'whatsapp')
            ->where('branch_id', $branchId)
            ->exists();

        if ($alreadyExists) {
            throw ValidationException::withMessages([
                'branch_id' => 'Ya existe un canal WhatsApp para este alcance. Edita el canal existente.',
            ]);
        }

        if ($request->boolean('is_active', true) && ! $this->planLimits->canCreate($company, 'whatsapp_channels', actor: $request->user())) {
            throw ValidationException::withMessages([
                'is_active' => $this->planLimits->message($company, 'whatsapp_channels'),
            ]);
        }

        $channel = Channel::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'name' => $request->string('name')->toString(),
            'slug' => 'whatsapp',
            'is_active' => $request->boolean('is_active', true),
            'settings' => $this->channelSettings(null, $request->input('settings', [])),
        ]);

        $this->auditLogger->log(
            $request->user()->id,
            AuditActionType::CREATE->value,
            'whatsapp_integrations',
            Channel::class,
            $channel->id,
            null,
            $this->channelSnapshot($channel),
            $request,
        );

        return redirect()->route('whatsapp.channels.index')->with('status', 'Canal WhatsApp creado.');
    }

    public function update(CommercialWhatsAppChannelRequest $request, Channel $channel)
    {
        $company = $this->activeCompany();
        $this->ensureChannelBelongsToActiveCompany($channel);
        $branchId = $this->validatedBranchId($request, $company);

        $alreadyExists = Channel::query()
            ->where('company_id', $company->id)
            ->where('slug', 'whatsapp')
            ->where('branch_id', $branchId)
            ->whereKeyNot($channel->id)
            ->exists();

        if ($alreadyExists) {
            throw ValidationException::withMessages([
                'branch_id' => 'Ya existe otro canal WhatsApp para este alcance.',
            ]);
        }

        if (! $channel->is_active && $request->boolean('is_active') && ! $this->planLimits->canCreate($company, 'whatsapp_channels', actor: $request->user())) {
            throw ValidationException::withMessages([
                'is_active' => $this->planLimits->message($company, 'whatsapp_channels'),
            ]);
        }

        $oldValues = $this->channelSnapshot($channel);
        $channel->update([
            'branch_id' => $branchId,
            'name' => $request->string('name')->toString(),
            'is_active' => $request->boolean('is_active'),
            'settings' => $this->channelSettings($channel, $request->input('settings', [])),
        ]);

        $this->auditLogger->log(
            $request->user()->id,
            AuditActionType::UPDATE->value,
            'whatsapp_integrations',
            Channel::class,
            $channel->id,
            $oldValues,
            $this->channelSnapshot($channel->fresh()),
            $request,
        );

        return redirect()->route('whatsapp.channels.index')->with('status', 'Canal WhatsApp actualizado.');
    }

    public function testConnection(Request $request, Channel $channel, WhatsAppConnectionTestService $tester)
    {
        $this->ensureChannelBelongsToActiveCompany($channel);

        try {
            $result = $tester->test($channel);
        } catch (RuntimeException $exception) {
            return redirect()->route('whatsapp.channels.index')->with('error', $exception->getMessage());
        }

        $settings = $channel->settings ?? [];
        $settings['last_connection_test'] = $result;
        $channel->update(['settings' => $settings]);

        $this->auditLogger->log(
            $request->user()->id,
            'test',
            'whatsapp_integrations',
            Channel::class,
            $channel->id,
            null,
            $result,
            $request,
        );

        return redirect()->route('whatsapp.channels.index')->with('status', 'Conexion con Meta validada.');
    }

    public function syncTemplates(Request $request, Channel $channel, WhatsAppTemplateSyncService $syncService)
    {
        $this->ensureChannelBelongsToActiveCompany($channel);

        try {
            $result = $syncService->sync($channel);
        } catch (RuntimeException $exception) {
            return redirect()->route('whatsapp.channels.index')->with('error', $exception->getMessage());
        }

        $this->auditLogger->log(
            $request->user()->id,
            'sync',
            'message_templates',
            Channel::class,
            $channel->id,
            null,
            $result,
            $request,
        );

        return redirect()->route('whatsapp.channels.index')->with(
            'status',
            "Sincronizacion finalizada: {$result['synced']} plantillas, {$result['created']} creadas, {$result['updated']} actualizadas, {$result['approved']} aprobadas, {$result['skipped']} omitidas."
        );
    }

    private function activeCompany(): Company
    {
        $company = $this->tenantContext->company();

        abort_unless($company instanceof Company, 403);

        return $company;
    }

    private function validatedBranchId(CommercialWhatsAppChannelRequest $request, Company $company): ?string
    {
        $branchId = $request->input('branch_id');

        if (blank($branchId)) {
            return null;
        }

        $exists = Branch::query()
            ->where('company_id', $company->id)
            ->whereKey($branchId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'branch_id' => 'La sede seleccionada no pertenece a la empresa activa.',
            ]);
        }

        return (string) $branchId;
    }

    private function ensureChannelBelongsToActiveCompany(Channel $channel): void
    {
        abort_unless($channel->company_id === $this->activeCompany()->id && $channel->slug === 'whatsapp', 403);
    }

    private function channelSettings(?Channel $channel, array $input): array
    {
        $current = $channel?->settings ?? [];
        $settings = [
            'provider' => 'whatsapp_cloud',
            'api_base_url' => $input['api_base_url'] ?? data_get($current, 'api_base_url'),
            'phone_number_id' => $input['phone_number_id'] ?? data_get($current, 'phone_number_id'),
            'business_account_id' => $input['business_account_id'] ?? data_get($current, 'business_account_id'),
            'access_token_expires_at' => $input['access_token_expires_at'] ?? data_get($current, 'access_token_expires_at'),
            'access_token_rotated_at' => $input['access_token_rotated_at'] ?? data_get($current, 'access_token_rotated_at'),
            'access_token_responsible' => $input['access_token_responsible'] ?? data_get($current, 'access_token_responsible'),
            'access_token_rotation_procedure' => $input['access_token_rotation_procedure'] ?? data_get($current, 'access_token_rotation_procedure'),
        ];

        foreach (['access_token', 'app_secret', 'webhook_verify_token'] as $secretKey) {
            $settings[$secretKey] = filled($input[$secretKey] ?? null)
                ? $input[$secretKey]
                : data_get($current, $secretKey);
        }

        return collect($settings)
            ->filter(fn ($value): bool => filled($value))
            ->all();
    }

    private function channelSnapshot(Channel $channel): array
    {
        return [
            'company_id' => $channel->company_id,
            'branch_id' => $channel->branch_id,
            'name' => $channel->name,
            'slug' => $channel->slug,
            'is_active' => $channel->is_active,
            'settings' => [
                'provider' => data_get($channel->settings, 'provider'),
                'api_base_url' => data_get($channel->settings, 'api_base_url'),
                'phone_number_id' => data_get($channel->settings, 'phone_number_id'),
                'business_account_id' => data_get($channel->settings, 'business_account_id'),
                'has_access_token' => filled(data_get($channel->settings, 'access_token')),
                'has_webhook_verify_token' => filled(data_get($channel->settings, 'webhook_verify_token')),
                'has_app_secret' => filled(data_get($channel->settings, 'app_secret')),
                'access_token_expires_at' => data_get($channel->settings, 'access_token_expires_at'),
                'access_token_rotated_at' => data_get($channel->settings, 'access_token_rotated_at'),
                'access_token_responsible' => data_get($channel->settings, 'access_token_responsible'),
            ],
        ];
    }

    private function operationalStatus(Channel $channel): array
    {
        $labels = [
            'phone_number_id' => 'Phone Number ID',
            'business_account_id' => 'WABA ID',
            'access_token' => 'Access token',
            'webhook_verify_token' => 'Webhook token',
        ];

        $missing = collect(array_keys($labels))
            ->filter(fn (string $key): bool => blank(data_get($channel->settings, $key)))
            ->map(fn (string $key): string => $labels[$key])
            ->values();

        $warnings = collect();
        $expiresAt = data_get($channel->settings, 'access_token_expires_at');
        $lastTest = data_get($channel->settings, 'last_connection_test.tested_at');

        if (! $channel->is_active) {
            $warnings->push('El canal esta inactivo.');
        }

        if ($lastTest === null) {
            $warnings->push('Falta probar conexion con Meta.');
        }

        if (filled($expiresAt)) {
            $expiration = \Illuminate\Support\Carbon::parse($expiresAt)->startOfDay();

            if ($expiration->isPast()) {
                $warnings->push('El token esta vencido.');
            } elseif (now()->diffInDays($expiration, false) <= 15) {
                $warnings->push('El token vence pronto.');
            }
        } else {
            $warnings->push('Falta fecha de expiracion del token.');
        }

        $ready = $channel->is_active && $missing->isEmpty() && $warnings->isEmpty();
        $level = $ready ? 'ready' : ($missing->isNotEmpty() ? 'incomplete' : 'attention');

        return [
            'level' => $level,
            'label' => match ($level) {
                'ready' => 'Listo para operar',
                'attention' => 'Requiere revision',
                default => 'Configuracion incompleta',
            },
            'missing' => $missing->all(),
            'warnings' => $warnings->all(),
        ];
    }
}
