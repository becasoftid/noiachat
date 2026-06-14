<?php

use App\Modules\Settings\Presentation\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index')->middleware('can:admin.access');
Route::put('/settings/channels/{channel}', [SettingsController::class, 'updateChannel'])->name('settings.channels.update')->middleware('can:admin.access');
Route::post('/settings/templates/sync-whatsapp', [SettingsController::class, 'syncWhatsAppTemplates'])->name('settings.templates.sync-whatsapp')->middleware('can:admin.access');
Route::post('/settings/templates', [SettingsController::class, 'storeTemplate'])->name('settings.templates.store')->middleware('can:admin.access');
Route::put('/settings/templates/{template}', [SettingsController::class, 'updateTemplate'])->name('settings.templates.update')->middleware('can:admin.access');
Route::patch('/settings/templates/{template}/toggle', [SettingsController::class, 'toggleTemplate'])->name('settings.templates.toggle')->middleware('can:admin.access');
Route::delete('/settings/templates/{template}', [SettingsController::class, 'destroyTemplate'])->name('settings.templates.destroy')->middleware('can:admin.access');
