<?php

use App\Modules\Audit\Presentation\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index')->middleware('can:viewAny,'.\App\Modules\Audit\Infrastructure\Persistence\Models\AuditLog::class);
Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show')->middleware('can:view,auditLog');
