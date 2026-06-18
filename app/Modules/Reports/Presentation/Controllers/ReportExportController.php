<?php

namespace App\Modules\Reports\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Infrastructure\Persistence\Models\AuditLog;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Tenancy\Application\Services\TenantContext;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function auditLogs(Request $request, TenantContext $tenantContext): StreamedResponse
    {
        $filters = $request->only(['module', 'action', 'user_id', 'target_type', 'date_from', 'date_to']);
        $branchId = $request->string('branch_id')->toString();

        if ($branchId !== '' && $this->canUseBranchFilter($tenantContext, $branchId)) {
            $filters['branch_id'] = $branchId;
        }

        $query = AuditLog::query()
            ->forTenantContext($tenantContext)
            ->with(['user', 'company', 'branch'])
            ->when($filters['module'] ?? null, fn ($q, $value) => $q->where('module', $value))
            ->when($filters['action'] ?? null, fn ($q, $value) => $q->where('action', $value))
            ->when($filters['user_id'] ?? null, fn ($q, $value) => $q->where('user_id', $value))
            ->when(array_key_exists('branch_id', $filters), function ($q) use ($filters): void {
                blank($filters['branch_id'])
                    ? $q->whereNull('branch_id')
                    : $q->where('branch_id', $filters['branch_id']);
            })
            ->when($filters['target_type'] ?? null, fn ($q, $value) => $q->where('target_type', 'like', "%{$value}%"))
            ->when($filters['date_from'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '<=', $value))
            ->latest();

        return $this->csv('auditoria.csv', [
            'fecha', 'empresa', 'sede', 'usuario', 'modulo', 'accion', 'objetivo_tipo', 'objetivo_id', 'ip', 'old_json', 'new_json',
        ], $query, fn (AuditLog $log) => [
            $log->created_at?->format('Y-m-d H:i:s'),
            $log->company?->name,
            $log->branch?->name,
            $log->user?->name ?? 'Sistema',
            $log->module,
            $log->action,
            class_basename((string) $log->target_type),
            $log->target_id,
            $log->ip_address,
            $this->json($log->old_values_json),
            $this->json($log->new_values_json),
        ]);
    }

    public function contacts(Request $request, TenantContext $tenantContext): StreamedResponse
    {
        $search = $request->string('search')->trim()->toString();
        $query = Contact::query()
            ->forTenantContext($tenantContext)
            ->with(['company', 'branch'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('full_name', 'like', "%{$search}%")
                        ->orWhere('primary_phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest();

        return $this->csv('contactos.csv', [
            'id', 'empresa', 'sede', 'nombre', 'email', 'telefono', 'estado', 'creado',
        ], $query, fn (Contact $contact) => [
            $contact->id,
            $contact->company?->name,
            $contact->branch?->name,
            $contact->full_name,
            $contact->email,
            $contact->primary_phone,
            $contact->status,
            $contact->created_at?->format('Y-m-d H:i:s'),
        ]);
    }

    public function messages(Request $request, TenantContext $tenantContext): StreamedResponse
    {
        $filters = $request->only(['status', 'type', 'search', 'date_from', 'date_to']);
        $query = Message::query()
            ->forTenantContext($tenantContext)
            ->with(['company', 'branch', 'contact', 'channel'])
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->when($filters['type'] ?? null, fn ($q, $value) => $q->where('type', $value))
            ->when($filters['date_from'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '<=', $value))
            ->when($filters['search'] ?? null, function ($query, $value): void {
                $query->whereHas('contact', function ($contactQuery) use ($value): void {
                    $contactQuery->where('full_name', 'like', "%{$value}%")
                        ->orWhere('primary_phone', 'like', "%{$value}%");
                });
            })
            ->latest();

        return $this->csv('mensajes.csv', [
            'id', 'empresa', 'sede', 'contacto', 'telefono', 'canal', 'tipo', 'estado', 'provider_message_id', 'reintentos', 'creado', 'fallido',
        ], $query, fn (Message $message) => [
            $message->id,
            $message->company?->name,
            $message->branch?->name,
            $message->contact?->full_name,
            $message->contact?->primary_phone,
            $message->channel?->name,
            $message->type,
            $message->status,
            $message->provider_message_id,
            $message->retry_count,
            $message->created_at?->format('Y-m-d H:i:s'),
            $message->failed_at?->format('Y-m-d H:i:s'),
        ]);
    }

    public function conversations(Request $request, TenantContext $tenantContext): StreamedResponse
    {
        $filters = $request->only(['status', 'assigned_user_id', 'search', 'date_from', 'date_to']);
        $branchId = $request->string('branch_id')->toString();

        if ($branchId !== '' && $this->canUseBranchFilter($tenantContext, $branchId)) {
            $filters['branch_id'] = $branchId;
        }

        if ($request->boolean('mine')) {
            $filters['assigned_user_id'] = $request->user()->id;
        }

        $query = Conversation::query()
            ->forTenantContext($tenantContext)
            ->with(['company', 'branch', 'contact', 'channel', 'assignedUser'])
            ->when(array_key_exists('branch_id', $filters), function ($q) use ($filters): void {
                blank($filters['branch_id'])
                    ? $q->whereNull('branch_id')
                    : $q->where('branch_id', $filters['branch_id']);
            })
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->when($filters['assigned_user_id'] ?? null, fn ($q, $value) => $q->where('assigned_user_id', $value))
            ->when($filters['date_from'] ?? null, fn ($q, $value) => $q->whereDate('last_message_at', '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($q, $value) => $q->whereDate('last_message_at', '<=', $value))
            ->when($filters['search'] ?? null, function ($query, $value): void {
                $query->whereHas('contact', function ($contactQuery) use ($value): void {
                    $contactQuery->where('full_name', 'like', "%{$value}%")
                        ->orWhere('primary_phone', 'like', "%{$value}%");
                });
            })
            ->latest('last_message_at');

        return $this->csv('conversaciones.csv', [
            'id', 'empresa', 'sede', 'contacto', 'telefono', 'canal', 'estado', 'asignado', 'ultimo_mensaje', 'leido_hasta',
        ], $query, fn (Conversation $conversation) => [
            $conversation->id,
            $conversation->company?->name,
            $conversation->branch?->name,
            $conversation->contact?->full_name,
            $conversation->contact?->primary_phone,
            $conversation->channel?->name,
            $conversation->status,
            $conversation->assignedUser?->name,
            $conversation->last_message_at?->format('Y-m-d H:i:s'),
            $conversation->last_read_at?->format('Y-m-d H:i:s'),
        ]);
    }

    private function csv(string $filename, array $headers, Builder $query, callable $row): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $query, $row): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            $query->chunk(200, function ($records) use ($handle, $row): void {
                foreach ($records as $record) {
                    fputcsv($handle, $row($record));
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function canUseBranchFilter(TenantContext $tenantContext, string $branchId): bool
    {
        return $tenantContext->companyId() !== null
            && $tenantContext->branchId() === null
            && Branch::query()
                ->where('company_id', $tenantContext->companyId())
                ->where('is_active', true)
                ->whereKey($branchId)
                ->exists();
    }

    private function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
