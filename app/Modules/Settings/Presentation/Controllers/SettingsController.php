<?php

namespace App\Modules\Settings\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Messaging\Application\Services\WhatsAppTemplateSyncService;
use App\Modules\Messaging\Infrastructure\Persistence\Models\MessageTemplate;
use App\Modules\Messaging\Infrastructure\Persistence\Models\TemplateVersion;
use App\Modules\Shared\Application\Services\AuditLogger;
use App\Modules\Settings\Presentation\Requests\StoreTemplateRequest;
use App\Modules\Settings\Presentation\Requests\UpdateChannelRequest;
use App\Modules\Settings\Presentation\Requests\UpdateTemplateRequest;
use RuntimeException;

class SettingsController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->can('admin.access'), 403);

        return view('noia.settings.index', [
            'channels' => Channel::query()->withCount(['messages', 'conversations'])->get(),
            'templates' => MessageTemplate::query()->with(['channel', 'currentVersion'])->latest()->get(),
            'operators' => User::query()->with('roles')->get(),
        ]);
    }

    public function updateChannel(UpdateChannelRequest $request, Channel $channel)
    {
        $channel->update([
            'name' => $request->string('name')->toString(),
            'is_active' => (bool) $request->boolean('is_active'),
            'settings' => array_filter([
                'provider' => $request->input('settings.provider', data_get($channel->settings, 'provider')),
            ]),
        ]);

        return back()->with('status', 'Canal actualizado.');
    }

    public function syncWhatsAppTemplates(WhatsAppTemplateSyncService $syncService, AuditLogger $auditLogger)
    {
        abort_unless(auth()->user()?->can('admin.access'), 403);

        $channel = Channel::query()->where('slug', 'whatsapp')->firstOrFail();

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
        $template = MessageTemplate::create([
            'channel_id' => (int) $request->integer('channel_id'),
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
        $template->update([
            'channel_id' => (int) $request->integer('channel_id'),
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
        $template->update(['is_active' => ! $template->is_active]);

        return back()->with('status', 'Estado de plantilla actualizado.');
    }

    public function destroyTemplate(MessageTemplate $template)
    {
        $template->delete();

        return back()->with('status', 'Plantilla archivada.');
    }

    private function countTemplateVariables(string $body): int
    {
        preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $body, $matches);

        return count(array_unique($matches[1] ?? []));
    }
}
