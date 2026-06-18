<?php

use App\Modules\Audit\Infrastructure\Persistence\Models\AuditLog;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Reports\Presentation\Controllers\ReportExportController;
use App\Modules\Reports\Presentation\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->name('health.index')->middleware('can:admin.access');
Route::get('/reports/exports/audit-logs', [ReportExportController::class, 'auditLogs'])->name('reports.exports.audit-logs')->middleware('can:viewAny,'.AuditLog::class);
Route::get('/reports/exports/contacts', [ReportExportController::class, 'contacts'])->name('reports.exports.contacts')->middleware('can:viewAny,'.Contact::class);
Route::get('/reports/exports/messages', [ReportExportController::class, 'messages'])->name('reports.exports.messages')->middleware('can:viewAny,'.Message::class);
Route::get('/reports/exports/conversations', [ReportExportController::class, 'conversations'])->name('reports.exports.conversations')->middleware('can:viewAny,'.Conversation::class);
