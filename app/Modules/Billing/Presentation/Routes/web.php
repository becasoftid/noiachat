<?php

use App\Modules\Billing\Presentation\Controllers\BillingController;
use Illuminate\Support\Facades\Route;

Route::get('/billing', [BillingController::class, 'index'])->name('billing.index')->middleware('can:admin.access');
Route::post('/billing/change-requests', [BillingController::class, 'requestPlanChange'])->name('billing.change-requests.store')->middleware('can:admin.access');
Route::patch('/billing/change-requests/{changeRequest}', [BillingController::class, 'resolvePlanChange'])->name('billing.change-requests.resolve')->middleware('can:super-admin.access');
Route::patch('/billing/subscription', [BillingController::class, 'updateSubscription'])->name('billing.subscription.update')->middleware('can:super-admin.access');
