<?php

use App\Http\Controllers\ProfileController;
use App\Modules\Reports\Presentation\Controllers\DashboardController;
use App\Modules\Tenancy\Presentation\Controllers\TenantContextController;
use App\Modules\Tenancy\Presentation\Middleware\ResolveTenantContext;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware(['auth', ResolveTenantContext::class])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/tenant-context', [TenantContextController::class, 'update'])->name('tenant-context.update');

    require app_path('Modules/Contacts/Presentation/Routes/web.php');
    require app_path('Modules/Messaging/Presentation/Routes/web.php');
    require app_path('Modules/Conversations/Presentation/Routes/web.php');
    require app_path('Modules/Audit/Presentation/Routes/web.php');
    require app_path('Modules/Reports/Presentation/Routes/web.php');
    require app_path('Modules/Billing/Presentation/Routes/web.php');
    require app_path('Modules/Tenancy/Presentation/Routes/web.php');
    require app_path('Modules/Users/Presentation/Routes/web.php');
    require app_path('Modules/Settings/Presentation/Routes/web.php');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require app_path('Modules/Webhooks/Presentation/Routes/web.php');
require __DIR__.'/auth.php';
