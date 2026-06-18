<?php

namespace App\Modules\Messaging\Application\Services;

use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Messaging\Infrastructure\Persistence\Models\MessageTemplate;
use App\Modules\Messaging\Infrastructure\Persistence\Models\TemplateVersion;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WhatsAppTemplateSyncService
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly WhatsAppChannelConfig $channelConfig,
    )
    {
    }

    public function sync(Channel $channel): array
    {
        $config = $this->channelConfig->forChannel($channel);

        if ($this->channelConfig->missingForTemplateSync($config) !== []) {
            throw new RuntimeException('Faltan WHATSAPP_BUSINESS_ACCOUNT_ID o WHATSAPP_ACCESS_TOKEN en el canal WhatsApp o en .env para sincronizar plantillas.');
        }

        $synced = 0;
        $created = 0;
        $updated = 0;
        $approved = 0;
        $skipped = 0;
        $now = now();

        foreach ($this->fetchTemplates($config) as $metaTemplate) {
            $normalized = $this->normalizeTemplate($metaTemplate);

            if ($normalized['name'] === '' || $normalized['language'] === '') {
                $skipped++;
                continue;
            }

            DB::transaction(function () use ($channel, $normalized, $now, &$created, &$updated, &$approved, &$synced): void {
                $template = $this->findTemplate($channel, $normalized);
                $exists = $template !== null;
                $isApproved = $normalized['status'] === 'APPROVED';

                $template ??= new MessageTemplate([...$channel->tenantAttributes(), 'channel_id' => $channel->id]);
                $template->fill([
                    ...$channel->tenantAttributes(),
                    'channel_id' => $channel->id,
                    'name' => $normalized['name'],
                    'external_template_id' => $normalized['name'],
                    'meta_template_id' => $normalized['id'],
                    'meta_status' => $normalized['status'],
                    'meta_category' => $normalized['category'],
                    'meta_payload' => $normalized['payload'],
                    'synced_at' => $now,
                    'is_active' => $isApproved,
                ]);
                $template->save();

                $version = $this->upsertCurrentVersion($template, $normalized, $isApproved);
                $template->update(['current_version_id' => $version->id]);

                $exists ? $updated++ : $created++;
                $synced++;

                if ($isApproved) {
                    $approved++;
                }
            });
        }

        return compact('synced', 'created', 'updated', 'approved', 'skipped');
    }

    private function fetchTemplates(array $config): array
    {
        $baseUrl = rtrim($config['api_base_url'], '/');
        $url = $baseUrl.'/'.$config['business_account_id'].'/message_templates';
        $templates = [];

        do {
            $response = $this->http
                ->withToken($config['access_token'])
                ->acceptJson()
                ->get($url, [
                    'fields' => 'id,name,language,status,category,components',
                    'limit' => 100,
                ]);

            if (! $response->successful()) {
                $message = $response->json('error.message') ?: 'Meta no devolvio una respuesta exitosa.';
                throw new RuntimeException('No se pudieron sincronizar plantillas de Meta: '.$message);
            }

            $payload = $response->json() ?? [];
            $templates = array_merge($templates, Arr::wrap($payload['data'] ?? []));
            $url = data_get($payload, 'paging.next');
        } while (is_string($url) && $url !== '');

        return $templates;
    }

    private function normalizeTemplate(array $metaTemplate): array
    {
        $components = Arr::wrap($metaTemplate['components'] ?? []);
        $body = $this->extractBody($components);

        return [
            'id' => (string) ($metaTemplate['id'] ?? ''),
            'name' => (string) ($metaTemplate['name'] ?? ''),
            'language' => (string) ($metaTemplate['language'] ?? ''),
            'status' => strtoupper((string) ($metaTemplate['status'] ?? 'UNKNOWN')),
            'category' => (string) ($metaTemplate['category'] ?? ''),
            'body' => $body,
            'components' => $components,
            'variable_count' => $this->countVariables($body),
            'payload' => $metaTemplate,
        ];
    }

    private function findTemplate(Channel $channel, array $normalized): ?MessageTemplate
    {
        if ($normalized['id'] !== '') {
            $template = MessageTemplate::query()
                ->where('channel_id', $channel->id)
                ->where('meta_template_id', $normalized['id'])
                ->first();

            if ($template) {
                return $template;
            }
        }

        return MessageTemplate::query()
            ->where('channel_id', $channel->id)
            ->where('external_template_id', $normalized['name'])
            ->whereHas('currentVersion', fn ($query) => $query->where('language', $normalized['language']))
            ->first();
    }

    private function upsertCurrentVersion(MessageTemplate $template, array $normalized, bool $isApproved): TemplateVersion
    {
        $current = $template->currentVersion;

        if (
            $current
            && $current->language === $normalized['language']
            && $current->body === $normalized['body']
            && $current->variable_count === $normalized['variable_count']
        ) {
            $current->update([
                'components' => $normalized['components'],
                'is_active' => $isApproved,
            ]);

            return $current;
        }

        $nextVersion = (int) $template->versions()->max('version') + 1;

        return TemplateVersion::create([
            'message_template_id' => $template->id,
            'version' => $nextVersion,
            'language' => $normalized['language'],
            'body' => $normalized['body'],
            'components' => $normalized['components'],
            'variable_count' => $normalized['variable_count'],
            'is_active' => $isApproved,
        ]);
    }

    private function extractBody(array $components): string
    {
        foreach ($components as $component) {
            if (strtoupper((string) ($component['type'] ?? '')) === 'BODY') {
                return (string) ($component['text'] ?? '');
            }
        }

        return '';
    }

    private function countVariables(string $body): int
    {
        if ($body === '') {
            return 0;
        }

        preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $body, $matches);

        return count(array_unique($matches[1] ?? []));
    }
}
