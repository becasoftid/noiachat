<?php

namespace App\Modules\Conversations\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Compliance\Application\Services\ComplianceDecisionService;
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
use Illuminate\Support\Collection;

class ConversationController extends Controller
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversations,
        private readonly QueueTextMessageUseCase $queueTextMessage,
        private readonly QueueMediaMessageUseCase $queueMediaMessage,
        private readonly QueueTemplateMessageUseCase $queueTemplateMessage,
        private readonly ComplianceDecisionService $compliance,
    ) {}

    public function index()
    {
        return view('noia.conversations.index', [
            'conversations' => $this->conversations->paginateLatest(20, $this->filters()),
            'users' => User::query()->orderBy('name')->get(),
        ]);
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
            'conversation' => $conversation,
            'timeline' => $this->buildTimeline($conversation),
            'freeFormEligibility' => $this->compliance->decide($conversation->contact, $conversation->channel_id),
            'templates' => MessageTemplate::query()
                ->with('currentVersion')
                ->where('channel_id', $conversation->channel_id)
                ->where('is_active', true)
                ->get(),
            'users' => \App\Models\User::query()->orderBy('name')->get(),
            'sideConversations' => $this->conversations->paginateLatest(20, []),
        ]);
    }

    public function assign(AssignConversationRequest $request, Conversation $conversation)
    {
        $conversation->update([
            'assigned_user_id' => $request->input('assigned_user_id'),
            'status' => $request->string('status')->toString(),
        ]);

        return back()->with('status', 'Conversación actualizada.');
    }

    public function assignToMe(Conversation $conversation)
    {
        abort_unless(request()->user()?->can('messages.send'), 403);

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

    private function filters(): array
    {
        $filters = request()->only(['status', 'assigned_user_id', 'search', 'date_from', 'date_to']);

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
}
