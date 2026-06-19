<?php

use App\Modules\Tenancy\Presentation\Controllers\TenantAdminController;
use App\Modules\Tenancy\Presentation\Controllers\WhatsAppIntegrationController;
use Illuminate\Support\Facades\Route;

Route::get('/integrations/whatsapp', [WhatsAppIntegrationController::class, 'index'])
    ->name('whatsapp.channels.index')
    ->middleware(['can:whatsapp.integration.manage', 'feature:settings.whatsapp_channel']);
Route::post('/integrations/whatsapp/channels', [WhatsAppIntegrationController::class, 'store'])
    ->name('whatsapp.channels.store')
    ->middleware(['can:whatsapp.integration.manage', 'feature:settings.whatsapp_channel']);
Route::patch('/integrations/whatsapp/channels/{channel}', [WhatsAppIntegrationController::class, 'update'])
    ->name('whatsapp.channels.update')
    ->middleware(['can:whatsapp.integration.manage', 'feature:settings.whatsapp_channel']);
Route::post('/integrations/whatsapp/channels/{channel}/test', [WhatsAppIntegrationController::class, 'testConnection'])
    ->name('whatsapp.channels.test')
    ->middleware(['can:whatsapp.integration.manage', 'feature:settings.whatsapp_channel']);
Route::post('/integrations/whatsapp/channels/{channel}/sync-templates', [WhatsAppIntegrationController::class, 'syncTemplates'])
    ->name('whatsapp.channels.sync-templates')
    ->middleware(['can:whatsapp.integration.manage', 'feature:whatsapp.templates']);

Route::middleware(['can:admin.access', 'feature:branches.manage'])->group(function (): void {
    Route::get('/tenancy', [TenantAdminController::class, 'index'])->name('tenancy.index');
    Route::patch('/tenancy/company', [TenantAdminController::class, 'updateCompany'])->name('tenancy.company.update');
    Route::post('/tenancy/branches', [TenantAdminController::class, 'storeBranch'])->name('tenancy.branches.store');
    Route::patch('/tenancy/branches/{branch}', [TenantAdminController::class, 'updateBranch'])->name('tenancy.branches.update');
    Route::post('/tenancy/memberships', [TenantAdminController::class, 'storeMembership'])->name('tenancy.memberships.store');
    Route::patch('/tenancy/memberships/{membership}', [TenantAdminController::class, 'updateMembership'])->name('tenancy.memberships.update');
});

Route::middleware('can:super-admin.access')->group(function (): void {
    Route::post('/tenancy/companies', [TenantAdminController::class, 'storeCompany'])->name('tenancy.companies.store');
    Route::patch('/tenancy/companies/{company}', [TenantAdminController::class, 'updateGlobalCompany'])->name('tenancy.companies.update');
});
