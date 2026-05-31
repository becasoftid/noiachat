<?php

namespace App\Modules\Messaging\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contacts\Domain\Repositories\ChannelRepositoryInterface;
use App\Modules\Contacts\Domain\Repositories\ContactRepositoryInterface;
use App\Modules\Messaging\Application\UseCases\QueueMediaMessageUseCase;
use App\Modules\Messaging\Application\UseCases\QueueTextMessageUseCase;
use App\Modules\Messaging\Domain\Repositories\MessageRepositoryInterface;
use App\Modules\Messaging\Domain\Enums\MessageStatus;
use App\Modules\Messaging\Domain\Enums\MessageType;
use App\Modules\Messaging\Infrastructure\Jobs\SendWhatsAppDocumentJob;
use App\Modules\Messaging\Infrastructure\Jobs\SendWhatsAppImageJob;
use App\Modules\Messaging\Infrastructure\Jobs\SendWhatsAppTemplateJob;
use App\Modules\Messaging\Infrastructure\Jobs\SendWhatsAppTextJob;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Messaging\Presentation\Requests\RetryMessageRequest;
use App\Modules\Messaging\Presentation\Requests\SendMediaMessageRequest;
use App\Modules\Messaging\Presentation\Requests\SendTextMessageRequest;
use App\Modules\Shared\Domain\Exceptions\BusinessRuleException;

class MessageController extends Controller
{
    public function __construct(
        private readonly QueueTextMessageUseCase $queueTextMessage,
        private readonly QueueMediaMessageUseCase $queueMediaMessage,
        private readonly MessageRepositoryInterface $messages,
        private readonly ContactRepositoryInterface $contacts,
        private readonly ChannelRepositoryInterface $channels,
    ) {}

    public function index()
    {
        return view('noia.messages.index', [
            'messages' => $this->messages->paginateLatest(20, request()->only(['status', 'type', 'search', 'date_from', 'date_to'])),
        ]);
    }

    public function create()
    {
        return view('noia.messages.create', ['contacts' => $this->contacts->ordered(), 'channels' => $this->channels->active()]);
    }

    public function sendText(SendTextMessageRequest $request)
    {
        $contact = $this->contacts->findById($request->string('contact_id')->toString()) ?? abort(404);
        $message = $this->queueTextMessage->execute($contact, (int) $request->integer('channel_id'), $request->string('body')->toString(), $request->user()->id, $request);

        return redirect()->route('messages.show', $message)->with('status', 'Mensaje encolado.');
    }

    public function sendImage(SendMediaMessageRequest $request)
    {
        return $this->queueMedia($request, MessageType::IMAGE->value);
    }

    public function sendDocument(SendMediaMessageRequest $request)
    {
        return $this->queueMedia($request, MessageType::DOCUMENT->value);
    }

    public function show(Message $message)
    {
        $message = $this->messages->loadDetail($message);

        return view('noia.messages.show', compact('message'));
    }

    public function retry(RetryMessageRequest $request, Message $message)
    {
        if (! in_array($message->status, $this->messages->retryableStatuses(), true)) {
            throw new BusinessRuleException('El mensaje no se puede reintentar en su estado actual.');
        }

        $message->increment('retry_count');
        $message->update(['status' => MessageStatus::QUEUED->value, 'failed_at' => null]);
        $message->events()->create([
            'status' => $message->status,
            'event_type' => 'retry_requested',
            'payload' => ['requested_by' => $request->user()->id],
            'occurred_at' => now(),
        ]);

        if ($message->type === MessageType::IMAGE->value) {
            SendWhatsAppImageJob::dispatch($message->id);
        } elseif ($message->type === MessageType::DOCUMENT->value) {
            SendWhatsAppDocumentJob::dispatch($message->id);
        } elseif ($message->type === MessageType::TEMPLATE->value) {
            SendWhatsAppTemplateJob::dispatch($message->id);
        } else {
            SendWhatsAppTextJob::dispatch($message->id);
        }

        return redirect()->route('messages.show', $message->fresh())->with('status', 'Mensaje reencolado.');
    }

    private function queueMedia(SendMediaMessageRequest $request, string $type)
    {
        $contact = $this->contacts->findById($request->string('contact_id')->toString()) ?? abort(404);
        $message = $this->queueMediaMessage->execute(
            $contact,
            (int) $request->integer('channel_id'),
            $type,
            $request->file('file'),
            $request->input('body', ''),
            $request->user()->id,
            $request,
        );

        return redirect()->route('messages.show', $message)->with('status', 'Mensaje multimedia encolado.');
    }
}
