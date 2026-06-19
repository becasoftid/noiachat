<?php

use App\Modules\Settings\Presentation\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index')->middleware(['can:platform.access', 'feature:settings.whatsapp_channel']);
Route::put('/settings/channels/{channel}', [SettingsController::class, 'updateChannel'])->name('settings.channels.update')->middleware(['can:platform.access', 'feature:settings.whatsapp_channel']);

Route::middleware(['can:platform.access', 'feature:whatsapp.templates'])->group(function (): void {
    Route::post('/settings/templates/sync-whatsapp', [SettingsController::class, 'syncWhatsAppTemplates'])->name('settings.templates.sync-whatsapp');
    Route::post('/settings/templates', [SettingsController::class, 'storeTemplate'])->name('settings.templates.store');
    Route::put('/settings/templates/{template}', [SettingsController::class, 'updateTemplate'])->name('settings.templates.update');
    Route::patch('/settings/templates/{template}/toggle', [SettingsController::class, 'toggleTemplate'])->name('settings.templates.toggle');
    Route::delete('/settings/templates/{template}', [SettingsController::class, 'destroyTemplate'])->name('settings.templates.destroy');
});
