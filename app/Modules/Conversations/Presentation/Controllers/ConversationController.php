<?php

namespace App\Modules\Conversations\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Compliance\Application\Services\ComplianceDecisionService;
use App\Modules\Contacts\Domain\Repositories\ChannelRepositoryInterface;
use App\Modules\Contacts\Domain\Repositories\ContactRepositoryInterface;
use App\Modules\Conversations\Domain\Repositories\ConversationRepositoryInterface;
use App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation;
use App\Modules\Conversations\Presentation\Requests\AssignConversationRequest;
use App\Modules\Conversations\Presentation\Requests\ReplyConversationRequest;
use App\Modules\Conversations\Presentation\Requests\ReplyConversationTemplateRequest;
use App\Modules\Messaging\Application\UseCases\QueueMediaMessageUseCase;
use App\Modules\Messaging\Application\UseCases\QueueTemplateMessageUseCase;
use App\Modules\Messaging\Application\UseCases\QueueTextMessageUseCase;
use App\Modules\Messaging\Domain\Enums\MessageStatus;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Messaging\Infrastructure\Persistence\Models\MessageTemplate;
use App\Modules\Messaging\Presentation\Requests\SendConversationMediaMessageRequest;
use App\Modules\Tenancy\Application\Services\TenantContext;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversations,
        private readonly ContactRepositoryInterface $contacts,
        private readonly ChannelRepositoryInterface $channels,
        private readonly QueueTextMessageUseCase $queueTextMessage,
        private readonly QueueMediaMessageUseCase $queueMediaMessage,
        private readonly QueueTemplateMessageUseCase $queueTemplateMessage,
        private readonly ComplianceDecisionService $compliance,
    ) {}

    public function index()
    {
        $conversation = $this->activeConversation();
        $filters = $this->filters();

        return view('noia.conversations.index', [
            'conversations' => $this->conversations->paginateLatest(20, $filters),
            'users' => $this->tenantUsersQuery(branchId: $conversation?->branch_id ?? ($filters['branch_id'] ?? null))->get(),
            'branches' => $this->tenantBranches(),
            'contacts' => $this->contacts->ordered(),
            'channels' => $this->channels->active(),
            ...$this->conversationViewData($conversation),
        ]);
    }

    public function start(Request $request)
    {
        abort_unless($request->user()?->can('messages.send'), 403);

        $validated = $request->validate([
            'contact_id' => ['required', 'uuid'],
            'channel_id' => ['required', 'integer'],
        ], [
            'contact_id.required' => 'Selecciona un contacto.',
            'contact_id.uuid' => 'El contacto seleccionado no es valido.',
            'channel_id.required' => 'Selecciona un canal.',
            'channel_id.integer' => 'El canal seleccionado no es valido.',
        ]);

        $contact = $this->contacts->findById($validated['contact_id']) ?? abort(404);
        $channel = $this->channels->active()->firstWhere('id', (int) $validated['channel_id']) ?? abort(404);
        $conversation = app(\App\Modules\Conversations\Application\Services\ConversationService::class)
            ->findOrCreate($contact->id, $channel->id);

        return redirect()
            ->route('conversations.index', ['conversation' => $conversation->id])
            ->with('status', 'Conversacion lista para operar.');
    }

    public function refresh()
    {
        return view('noia.conversations.partials.list', [
            'conversations' => $this->conversations->paginateLatest(20, $this->filters()),
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function show(Conversation $conversation)
    {
        $conversation->update(['last_read_at' => now()]);
        $conversation = $this->conversations->loadDetail($conversation);

        return view('noia.conversations.show', [
            'users' => $this->tenantUsersQuery(branchId: $conversation->branch_id)->get(),
            'sideConversations' => $this->conversations->paginateLatest(20, []),
            ...$this->conversationViewData($conversation),
        ]);
    }

    public function assign(AssignConversationRequest $request, Conversation $conversation)
    {
        if ($request->filled('assigned_user_id')) {
            abort_unless($this->userCanBeAssignedToConversation((int) $request->integer('assigned_user_id'), $conversation), 403);
        }

        $conversation->update([
            'assigned_user_id' => $request->input('assigned_user_id'),
            'status' => $request->string('status')->toString(),
        ]);

        return back()->with('status', 'Conversación actualizada.');
    }

    public function assignToMe(Conversation $conversation)
    {
        abort_unless(request()->user()?->can('messages.send'), 403);
        abort_unless($this->userCanBeAssignedToConversation(request()->user()->id, $conversation), 403);

        $conversation->update([
            'assigned_user_id' => request()->user()->id,
            'status' => 'pending',
        ]);

        return back()->with('status', 'Conversación asignada a ti.');
    }

    public function reply(ReplyConversationRequest $request, Conversation $conversation)
    {
        $message = $this->queueTextMessage->execute(
            $conversation->contact,
            $conversation->channel_id,
            $request->string('body')->toString(),
            $request->user()->id,
            $request,
        );

        $conversation->update(['last_message_at' => now()]);

        return $this->backAfterQueue($message, 'Respuesta encolada.');
    }

    public function replyMedia(SendConversationMediaMessageRequest $request, Conversation $conversation)
    {
        $message = $this->queueMediaMessage->execute(
            $conversation->contact,
            $conversation->channel_id,
            $request->string('type')->toString(),
            $request->file('file'),
            $request->input('body', ''),
            $request->user()->id,
            $request,
        );

        $conversation->update(['last_message_at' => now()]);

        return $this->backAfterQueue($message, 'Adjunto encolado en la conversación.');
    }

    public function replyTemplate(ReplyConversationTemplateRequest $request, Conversation $conversation)
    {
        $template = MessageTemplate::query()
            ->forTenantContext()
            ->with('currentVersion')
            ->where('channel_id', $conversation->channel_id)
            ->findOrFail((int) $request->integer('message_template_id'));

        $message = $this->queueTemplateMessage->execute(
            $conversation->contact,
            $template,
            $request->user()->id,
            $request->parsedVariables(),
            $request,
        );

        $conversation->update(['last_message_at' => now()]);

        return $this->backAfterQueue($message, 'Plantilla encolada en la conversación.');
    }

    private function buildTimeline(Conversation $conversation): Collection
    {
        $outbound = $conversation->messages->map(function ($message) {
            return (object) [
                'direction' => 'outbound',
                'type' => $message->type,
                'body' => $message->body,
                'status' => $message->status,
                'meta' => $message->meta,
                'compliance_block_label' => $message->complianceBlockLabel(),
                'compliance_block_description' => $message->complianceBlockDescription(),
                'created_at' => $message->created_at,
                'sent_at' => $message->sent_at,
                'delivered_at' => $message->delivered_at,
                'read_at' => $message->read_at,
                'attachments' => $message->attachments,
                'provider_logs' => $message->providerLogs,
            ];
        });

        $inbound = $conversation->inboundMessages->map(function ($message) {
            return (object) [
                'direction' => 'inbound',
                'type' => 'text',
                'body' => $message->body,
                'status' => null,
                'meta' => [],
                'compliance_block_label' => null,
                'compliance_block_description' => null,
                'created_at' => $message->created_at,
                'sent_at' => null,
                'delivered_at' => null,
                'read_at' => null,
                'attachments' => collect(),
                'provider_logs' => collect(),
            ];
        });

        return $outbound
            ->concat($inbound)
            ->sortBy('created_at')
            ->values();
    }

    private function backAfterQueue(Message $message, string $successMessage)
    {
        if ($message->status === MessageStatus::BLOCKED_BY_POLICY->value) {
            return back()->with('error', 'Envio bloqueado: '.$message->complianceBlockDescription());
        }

        return back()->with('status', $successMessage);
    }

    private function activeConversation(): ?Conversation
    {
        if (! request()->filled('conversation')) {
            return null;
        }

        $conversation = Conversation::query()->findOrFail(request()->string('conversation')->toString());

        Gate::authorize('view', $conversation);

        $conversation->update(['last_read_at' => now()]);

        return $this->conversations->loadDetail($conversation);
    }

    private function conversationViewData(?Conversation $conversation): array
    {
        if (! $conversation) {
            return [
                'conversation' => null,
                'timeline' => collect(),
                'freeFormEligibility' => null,
                'templates' => collect(),
            ];
        }

        return [
            'conversation' => $conversation,
            'timeline' => $this->buildTimeline($conversation),
            'freeFormEligibility' => $this->compliance->decide($conversation->contact, $conversation->channel_id),
            'templates' => MessageTemplate::query()
                ->forTenantContext()
                ->with('currentVersion')
                ->where('channel_id', $conversation->channel_id)
                ->where('is_active', true)
                ->get(),
        ];
    }

    private function filters(): array
    {
        $filters = request()->only(['status', 'assigned_user_id', 'search', 'date_from', 'date_to']);
        $branchId = request()->string('branch_id')->toString();

        if ($this->canFilterByBranch() && $branchId !== '' && $this->tenantBranches()->contains('id', $branchId)) {
            $filters['branch_id'] = $branchId;
        }

        if (request()->boolean('mine')) {
            $filters['assigned_user_id'] = request()->user()->id;
        }

        return $filters;
    }

    private function statusLabels(): array
    {
        return [
            'open' => 'Abierta',
            'pending' => 'Pendiente',
            'resolved' => 'Resuelta',
            'closed' => 'Cerrada',
        ];
    }

    private function tenantUsersQuery(?string $branchId = null): Builder
    {
        $context = app(TenantContext::class);
        $branchId = $branchId !== '' ? $branchId : null;

        return User::query()
            ->whereHas('memberships', function ($query) use ($context, $branchId): void {
                $query->where('company_id', $context->companyId())
                    ->where('is_active', true);

                if ($branchId !== null) {
                    $query->where(function ($branchQuery) use ($branchId): void {
                        $branchQuery->where('branch_id', $branchId)
                            ->orWhereNull('branch_id');
                    });
                } elseif ($context->branchId() !== null) {
                    $query->where(function ($branchQuery) use ($context): void {
                        $branchQuery->where('branch_id', $context->branchId())
                            ->orWhereNull('branch_id');
                    });
                }
            })
            ->orderBy('name');
    }

    private function userCanBeAssignedToConversation(int $userId, Conversation $conversation): bool
    {
        return $this->tenantUsersQuery(branchId: $conversation->branch_id)->whereKey($userId)->exists();
    }

    private function tenantBranches(): Collection
    {
        $context = app(TenantContext::class);

        if (! $this->canFilterByBranch()) {
            return collect();
        }

        return Branch::query()
            ->where('company_id', $context->companyId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function canFilterByBranch(): bool
    {
        return app(TenantContext::class)->companyId() !== null
            && app(TenantContext::class)->branchId() === null;
    }
}
