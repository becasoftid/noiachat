<?php

use App\Modules\Conversations\Presentation\Controllers\ConversationController;
use Illuminate\Support\Facades\Route;

Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index')->middleware('can:viewAny,'.\App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation::class);
Route::get('/conversations/refresh', [ConversationController::class, 'refresh'])->name('conversations.refresh')->middleware('can:viewAny,'.\App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation::class);
Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show')->middleware('can:view,conversation');
Route::put('/conversations/{conversation}/assign', [ConversationController::class, 'assign'])->name('conversations.assign')->middleware('can:view,conversation');
Route::put('/conversations/{conversation}/assign-me', [ConversationController::class, 'assignToMe'])->name('conversations.assign-me')->middleware('can:view,conversation');
Route::post('/conversations/{conversation}/reply', [ConversationController::class, 'reply'])->name('conversations.reply')->middleware('can:view,conversation');
Route::post('/conversations/{conversation}/reply-media', [ConversationController::class, 'replyMedia'])->name('conversations.reply-media')->middleware('can:view,conversation');
Route::post('/conversations/{conversation}/reply-template', [ConversationController::class, 'replyTemplate'])->name('conversations.reply-template')->middleware('can:view,conversation');
