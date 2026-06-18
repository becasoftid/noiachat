<?php

use App\Modules\Messaging\Presentation\Controllers\MessageController;
use App\Modules\Messaging\Presentation\Controllers\FailurePanelController;
use Illuminate\Support\Facades\Route;

Route::get('/failures', [FailurePanelController::class, 'index'])->name('failures.index')->middleware('can:admin.access');
Route::get('/messages', [MessageController::class, 'index'])->name('messages.index')->middleware('can:viewAny,'.\App\Modules\Messaging\Infrastructure\Persistence\Models\Message::class);
Route::get('/messages/create', [MessageController::class, 'create'])->name('messages.create')->middleware('can:create,'.\App\Modules\Messaging\Infrastructure\Persistence\Models\Message::class);
Route::post('/messages/send-text', [MessageController::class, 'sendText'])->name('messages.send-text')->middleware('can:create,'.\App\Modules\Messaging\Infrastructure\Persistence\Models\Message::class);
Route::post('/messages/send-image', [MessageController::class, 'sendImage'])->name('messages.send-image')->middleware('can:create,'.\App\Modules\Messaging\Infrastructure\Persistence\Models\Message::class);
Route::post('/messages/send-document', [MessageController::class, 'sendDocument'])->name('messages.send-document')->middleware('can:create,'.\App\Modules\Messaging\Infrastructure\Persistence\Models\Message::class);
Route::post('/messages/{message}/retry', [MessageController::class, 'retry'])->name('messages.retry')->middleware('can:create,'.\App\Modules\Messaging\Infrastructure\Persistence\Models\Message::class);
Route::get('/messages/{message}', [MessageController::class, 'show'])->name('messages.show')->middleware('can:view,message');
