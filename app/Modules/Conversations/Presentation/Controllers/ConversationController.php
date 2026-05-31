<?php

namespace App\Modules\Conversations\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Conversations\Domain\Repositories\ConversationRepositoryInterface;
use App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation;
use App\Modules\Conversations\Presentation\Requests\AssignConversationRequest;
use App\Modules\Conversations\Presentation\Requests\ReplyConversationRequest;
use App\Modules\Conversations\Presentation\Requests\ReplyConversationTemplateRequest;
use App\Modules\Messaging\Application\UseCases\QueueMediaMessageUseCase;
use App\Modules\Messaging\Application\UseCases\QueueTemplateMessageUseCase;
use App\Modules\Messaging\Application\UseCases\QueueTextMessageUseCase;
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
    ) {}

    public function index()
    {
        return view('noia.conversations.index', [
            'conversations' => $this->conversations->paginateLatest(20, request()->only(['status', 'assigned_user_id', 'search', 'date_from', 'date_to'])),
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function show(Conversation $conversation)
    {
        $conversation = $this->conversations->loadDetail($conversation);

        return view('noia.conversations.show', [
            'conversation' => $conversation,
            'timeline' => $this->buildTimeline($conversation),
            'templates' => MessageTemplate::query()
                ->with('currentVersion')
                ->where('channel_id', $conversation->channel_id)
                ->where('is_active', true)
                ->get(),
            'users' => \App\Models\User::query()->orderBy('name')->get(),
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

    public function reply(ReplyConversationRequest $request, Conversation $conversation)
    {
        $this->queueTextMessage->execute(
            $conversation->contact,
            $conversation->channel_id,
            $request->string('body')->toString(),
            $request->user()->id,
            $request,
        );

        $conversation->update(['last_message_at' => now()]);

        return back()->with('status', 'Respuesta encolada.');
    }

    public function replyMedia(SendConversationMediaMessageRequest $request, Conversation $conversation)
    {
        $this->queueMediaMessage->execute(
            $conversation->contact,
            $conversation->channel_id,
            $request->string('type')->toString(),
            $request->file('file'),
            $request->input('body', ''),
            $request->user()->id,
            $request,
        );

        $conversation->update(['last_message_at' => now()]);

        return back()->with('status', 'Adjunto encolado en la conversación.');
    }

    public function replyTemplate(ReplyConversationTemplateRequest $request, Conversation $conversation)
    {
        $template = MessageTemplate::query()
            ->with('currentVersion')
            ->where('channel_id', $conversation->channel_id)
            ->findOrFail((int) $request->integer('message_template_id'));

        $variables = collect(explode('|', (string) $request->input('variables', '')))
            ->map(fn ($value) => trim($value))
            ->filter(fn ($value) => $value !== '')
            ->values()
            ->all();

        $this->queueTemplateMessage->execute(
            $conversation->contact,
            $template,
            $request->user()->id,
            $variables,
            $request,
        );

        $conversation->update(['last_message_at' => now()]);

        return back()->with('status', 'Plantilla encolada en la conversación.');
    }

    private function buildTimeline(Conversation $conversation): Collection
    {
        $outbound = $conversation->messages->map(function ($message) {
            return (object) [
                'direction' => 'outbound',
                'type' => $message->type,
                'body' => $message->body,
                'status' => $message->status,
                'created_at' => $message->created_at,
                'attachments' => $message->attachments,
            ];
        });

        $inbound = $conversation->inboundMessages->map(function ($message) {
            return (object) [
                'direction' => 'inbound',
                'type' => 'text',
                'body' => $message->body,
                'status' => null,
                'created_at' => $message->created_at,
                'attachments' => collect(),
            ];
        });

        return $outbound
            ->concat($inbound)
            ->sortBy('created_at')
            ->values();
    }
}
