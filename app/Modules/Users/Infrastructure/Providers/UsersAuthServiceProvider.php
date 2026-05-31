<?php

namespace App\Modules\Users\Infrastructure\Providers;

use App\Modules\Audit\Infrastructure\Persistence\Models\AuditLog;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Users\Domain\Policies\AuditLogPolicy;
use App\Modules\Users\Domain\Policies\ContactPolicy;
use App\Modules\Users\Domain\Policies\ConversationPolicy;
use App\Modules\Users\Domain\Policies\MessagePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class UsersAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Contact::class, ContactPolicy::class);
        Gate::policy(Message::class, MessagePolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);

        Gate::define('admin.access', fn ($user) => $user->hasRole('admin'));
        Gate::define('contacts.manage', fn ($user) => $user->hasAnyRole(['admin', 'operator']));
        Gate::define('contacts.viewAny', fn ($user) => $user->hasAnyRole(['admin', 'operator', 'auditor']));
        Gate::define('messages.send', fn ($user) => $user->hasAnyRole(['admin', 'operator']));
        Gate::define('messages.view', fn ($user) => $user->hasAnyRole(['admin', 'operator', 'auditor']));
        Gate::define('conversations.view', fn ($user) => $user->hasAnyRole(['admin', 'operator', 'auditor']));
        Gate::define('audit.view', fn ($user) => $user->hasAnyRole(['admin', 'auditor']));
    }
}
