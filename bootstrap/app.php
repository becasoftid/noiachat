<?php

use App\Console\Commands\BackupNoiaChatCommand;
use App\Console\Commands\HealthCheckCommand;
use App\Console\Commands\SubscriptionsCheckCommand;
use App\Console\Commands\ValidateCommercialWhatsAppChannelCommand;
use App\Console\Commands\ValidateTenantReadinessCommand;
use App\Modules\Billing\Presentation\Middleware\EnsureFeatureEnabled;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        BackupNoiaChatCommand::class,
        HealthCheckCommand::class,
        SubscriptionsCheckCommand::class,
        ValidateCommercialWhatsAppChannelCommand::class,
        ValidateTenantReadinessCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'feature' => EnsureFeatureEnabled::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/whatsapp',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
