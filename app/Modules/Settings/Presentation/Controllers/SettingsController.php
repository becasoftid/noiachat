<?php

namespace App\Modules\Settings\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Billing\Application\Services\PlanLimitService;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Messaging\Application\Services\WhatsAppTemplateSyncService;
use App\Modules\Messaging\Infrastructure\Persistence\Models\MessageTemplate;
use App\Modules\Messaging\Infrastructure\Persistence\Models\TemplateVersion;
use App\Modules\Shared\Application\Services\AuditLogger;
use App\Modules\Settings\Presentation\Requests\StoreTemplateRequest;
use App\Modules\Settings\Presentation\Requests\UpdateChannelRequest;
use App\Modules\Settings\Presentation\Requests\UpdateTemplateRequest;
use App\Modules\Tenancy\Application\Services\TenantContext;
use RuntimeException;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->can('admin.access'), 403);

        return view('noia.settings.index', [
            'channels' => Channel::query()->forTenantContext()->withCount(['messages', 'conversations'])->get(),
            'templates' => MessageTemplate::query()->forTenantContext()->with(['channel', 'currentVersion'])->latest()->get(),
            'operators' => $this->tenantUsersQuery()->with('roles')->get(),
        ]);
    }

    public function updateChannel(UpdateChannelRequest $request, Channel $channel, PlanLimitService $planLimits)
    {
        abort_unless($channel->belongsToActiveTenant(), 403);

        if (! $channel->is_active && $request->boolean('is_active') && ! $planLimits->canCreate($channel->company_id, 'whatsapp_channels', actor: $request->user())) {
            throw ValidationException::withMessages([
                'is_active' => $planLimits->message($channel->company_id, 'whatsapp_channels'),
            ]);
        }

        $settings = $this->channelSettings($channel, $request->input('settings', []));

        $channel->update([
            'name' => $request->string('name')->toString(),
            'is_active' => (bool) $request->boolean('is_active'),
            'settings' => $settings,
        ]);

        return back()->with('status', 'Canal actualizado.');
    }

    public function syncWhatsAppTemplates(WhatsAppTemplateSyncService $syncService, AuditLogger $auditLogger)
    {
        abort_unless(auth()->user()?->can('admin.access'), 403);

        $channel = Channel::query()->forTenantContext()->where('slug', 'whatsapp')->firstOrFail();

        try {
            $result = $syncService->sync($channel);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $auditLogger->log(
            auth()->id(),
            'sync',
            'message_templates',
            Channel::class,
            $channel->id,
            null,
            $result,
            request(),
        );

        return back()->with(
            'status',
            "Sincronizacion finalizada: {$result['synced']} plantillas, {$result['created']} creadas, {$result['updated']} actualizadas, {$result['approved']} aprobadas, {$result['skipped']} omitidas."
        );
    }

    public function storeTemplate(StoreTemplateRequest $request)
    {
        $channel = Channel::query()
            ->forTenantContext()
            ->findOrFail((int) $request->integer('channel_id'));

        $template = MessageTemplate::create([
            'channel_id' => $channel->id,
            'name' => $request->string('name')->toString(),
            'external_template_id' => $request->input('external_template_id'),
            'is_active' => (bool) $request->boolean('is_active'),
        ]);

        $version = TemplateVersion::create([
            'message_template_id' => $template->id,
            'version' => 1,
            'language' => $request->string('language')->toString(),
            'body' => $request->string('body')->toString(),
            'variable_count' => $this->countTemplateVariables($request->string('body')->toString()),
            'is_active' => true,
        ]);

        $template->update(['current_version_id' => $version->id]);

        return back()->with('status', 'Plantilla creada.');
    }

    public function updateTemplate(UpdateTemplateRequest $request, MessageTemplate $template)
    {
        abort_unless($template->belongsToActiveTenant(), 403);

        $channel = Channel::query()
            ->forTenantContext()
            ->findOrFail((int) $request->integer('channel_id'));

        $template->update([
            'channel_id' => $channel->id,
            'name' => $request->string('name')->toString(),
            'external_template_id' => $request->input('external_template_id'),
            'is_active' => (bool) $request->boolean('is_active'),
        ]);

        $nextVersion = (int) $template->versions()->max('version') + 1;

        $version = TemplateVersion::create([
            'message_template_id' => $template->id,
            'version' => $nextVersion,
            'language' => $request->string('language')->toString(),
            'body' => $request->string('body')->toString(),
            'variable_count' => $this->countTemplateVariables($request->string('body')->toString()),
            'is_active' => true,
        ]);

        $template->update(['current_version_id' => $version->id]);

        return back()->with('status', 'Plantilla actualizada.');
    }

    public function toggleTemplate(MessageTemplate $template)
    {
        abort_unless($template->belongsToActiveTenant(), 403);

        $template->update(['is_active' => ! $template->is_active]);

        return back()->with('status', 'Estado de plantilla actualizado.');
    }

    public function destroyTemplate(MessageTemplate $template)
    {
        abort_unless($template->belongsToActiveTenant(), 403);

        $template->delete();

        return back()->with('status', 'Plantilla archivada.');
    }

    private function countTemplateVariables(string $body): int
    {
        preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $body, $matches);

        return count(array_unique($matches[1] ?? []));
    }

    private function channelSettings(Channel $channel, array $input): array
    {
        $current = $channel->settings ?? [];
        $settings = [
            'provider' => $input['provider'] ?? data_get($current, 'provider'),
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

    private function tenantUsersQuery()
    {
        $context = app(TenantContext::class);

        return User::query()
            ->whereHas('memberships', function ($query) use ($context): void {
                $query->where('company_id', $context->companyId())
                    ->where('is_active', true);

                if ($context->branchId() !== null) {
                    $query->where(function ($branchQuery) use ($context): void {
                        $branchQuery->where('branch_id', $context->branchId())
                            ->orWhereNull('branch_id');
                    });
                }
            })
            ->orderBy('name');
    }
}
