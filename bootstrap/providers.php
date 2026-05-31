<?php

use App\Modules\Audit\Infrastructure\Providers\AuditServiceProvider;
use App\Modules\Contacts\Infrastructure\Providers\ContactsServiceProvider;
use App\Modules\Consents\Infrastructure\Providers\ConsentsServiceProvider;
use App\Modules\Conversations\Infrastructure\Providers\ConversationsServiceProvider;
use App\Modules\Messaging\Infrastructure\Providers\MessagingServiceProvider;
use App\Modules\Shared\Infrastructure\Providers\SharedServiceProvider;
use App\Modules\Users\Infrastructure\Providers\UsersAuthServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,
    AuditServiceProvider::class,
    ContactsServiceProvider::class,
    ConsentsServiceProvider::class,
    MessagingServiceProvider::class,
    ConversationsServiceProvider::class,
    UsersAuthServiceProvider::class,
];
