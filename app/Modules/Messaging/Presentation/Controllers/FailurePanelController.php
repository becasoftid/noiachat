<?php

namespace App\Modules\Messaging\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Messaging\Infrastructure\Persistence\Models\ProviderLog;
use App\Modules\Tenancy\Application\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FailurePanelController extends Controller
{
    public function index(Request $request, TenantContext $tenantContext)
    {
        $search = $request->string('search')->trim()->toString();

        $messages = Message::query()
            ->forTenantContext($tenantContext)
            ->with(['contact', 'providerLogs' => fn ($query) => $query->latest()])
            ->whereIn('status', ['failed', 'bounced', 'blocked_by_policy'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($messageQuery) use ($search): void {
                    $messageQuery
                        ->where('body', 'like', "%{$search}%")
                        ->orWhere('provider_message_id', 'like', "%{$search}%")
                        ->orWhereHas('contact', function ($contactQuery) use ($search): void {
                            $contactQuery
                                ->where('full_name', 'like', "%{$search}%")
                                ->orWhere('primary_phone', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('failed_at')
            ->latest()
            ->paginate(10, ['*'], 'messages_page');

        $providerLogs = ProviderLog::query()
            ->forTenantContext($tenantContext)
            ->with(['message.contact'])
            ->where(function ($query): void {
                $query
                    ->where('event_type', 'like', '%failed%')
                    ->orWhere('payload', 'like', '%error%')
                    ->orWhere('payload', 'like', '%details%');
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($logQuery) use ($search): void {
                    $logQuery
                        ->where('event_type', 'like', "%{$search}%")
                        ->orWhere('external_event_id', 'like', "%{$search}%")
                        ->orWhere('payload', 'like', "%{$search}%")
                        ->orWhereHas('message.contact', function ($contactQuery) use ($search): void {
                            $contactQuery
                                ->where('full_name', 'like', "%{$search}%")
                                ->orWhere('primary_phone', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10, ['*'], 'logs_page');

        $failedJobs = DB::table('failed_jobs')
            ->latest('failed_at')
            ->limit(10)
            ->get()
            ->map(fn ($job) => [
                'id' => $job->id,
                'uuid' => $job->uuid,
                'connection' => $job->connection,
                'queue' => $job->queue,
                'name' => $this->jobName($job->payload),
                'error' => $this->exceptionSummary($job->exception),
                'failed_at' => $job->failed_at,
            ]);

        return view('noia.failures.index', [
            'messages' => $messages,
            'providerLogs' => $providerLogs,
            'failedJobs' => $failedJobs,
            'search' => $search,
        ]);
    }

    private function jobName(string $payload): string
    {
        $decoded = json_decode($payload, true);

        return data_get($decoded, 'displayName')
            ?? data_get($decoded, 'data.commandName')
            ?? 'Job sin nombre';
    }

    private function exceptionSummary(string $exception): string
    {
        $firstLine = Str::of($exception)->replace(["\r\n", "\r"], "\n")->explode("\n")->first();

        return Str::limit((string) $firstLine, 180);
    }
}
