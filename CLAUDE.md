# CLAUDE.md — NoiaChat Project Context

## What is NoiaChat?

NoiaChat is a **Laravel 13 + Alpine.js** web application for managing WhatsApp Business API messaging, contacts, and conversations. It provides a CRM-style interface for sending/receiving messages via the WhatsApp Cloud API, with full compliance (GDPR consents, blacklists, frequency control) and audit trail capabilities.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3+, Laravel 13 |
| Frontend | Alpine.js 3.4.2, Tailwind CSS 3.1.0 |
| Build tool | Vite 8.0 + laravel-vite-plugin |
| Database | SQLite (default), configurable to MySQL/PostgreSQL |
| Queue/Cache | Database driver (default), configurable to Redis |
| Auth | Laravel Breeze (email/password) |
| Testing | PHPUnit 12 + Mockery + FakerPHP |
| Code style | Laravel Pint |
| Logs | Laravel Pail |

---

## Architecture

The application uses a **modular Domain-Driven Design (DDD)** architecture. Each module has four layers:

```
app/Modules/<ModuleName>/
├── Domain/          # Enums, interfaces, policies, exceptions (pure business logic)
├── Application/     # Use cases, DTOs, application services
├── Infrastructure/  # Eloquent models, repositories, jobs, API integrations, providers
└── Presentation/    # Controllers, form requests, routes, Blade views
```

### Modules (14 total)

| Module | Responsibility |
|---|---|
| `Contacts` | Contact CRUD, channels per contact, metadata |
| `Messaging` | Send text/image/document/template messages, retry logic, status tracking |
| `Conversations` | Thread management, assignment to users, status (open/closed) |
| `Webhooks` | Receive and verify WhatsApp Cloud API webhooks |
| `Consents` | GDPR consent grant/revoke, expiry |
| `Compliance` | Message eligibility decisions, frequency control, opt-out handling |
| `Audit` | Comprehensive audit trail for all actions |
| `Settings` | Channel config, message template CRUD with versioning |
| `Users` | RBAC policies, authorization |
| `Reports` | Dashboard and analytics views |
| `Media` | File upload and storage |
| `Shared` | Cross-module contracts, exceptions, utilities |
| `Automations` | Placeholder (not yet implemented) |
| `Campaigns` | Placeholder (not yet implemented) |

### Key Patterns in Use

- **Repository Pattern** — All data access via interfaces (`MessageRepositoryInterface`, etc.)
- **Use Case Pattern** — Each business workflow is an explicit class (e.g., `QueueTextMessageUseCase`)
- **Policy Pattern** — Authorization via Laravel policies; business rules via `MessageEligibilityPolicy`
- **DTO Pattern** — Data transfer between layers; no Eloquent models cross layer boundaries
- **Service Provider per Module** — Each module bootstraps itself via its own `ServiceProvider`

---

## Message Flow (Core Feature)

```
HTTP Request
  → MessageController
    → QueueTextMessageUseCase (checks compliance)
      → ComplianceDecisionService → MessageEligibilityPolicy
        → [if allowed] MessageRepository::save (status=QUEUED)
          → SendWhatsAppTextJob (queued)
            → WhatsAppCloudApiProvider::sendText()
              → [updates status: SENT / FAILED]

Inbound webhook
  → WhatsAppWebhookController
    → ProcessWhatsAppWebhookJob (async)
      → ProcessWhatsAppWebhookUseCase
        → InboundMessageRepository::save
        → ConversationService::findOrCreate
```

---

## Database Schema Summary

All UUIDs used for distributed-friendly identifiers. Soft deletes on contacts, messages, media, and templates.

**Key tables:**
- `contacts` — uuid PK, name, email, phone, status, meta JSON
- `contact_channels` — contact ↔ channel mapping with phone number
- `contact_consents` — GDPR consent per contact+channel (granted/revoked/expired)
- `contact_blacklist` — hard block per contact+channel
- `conversations` — uuid PK, contact+channel thread, assigned user, status
- `messages` — uuid PK, full status lifecycle, retry_count, meta JSON
- `message_events` — status transition log per message
- `message_templates` — versioned templates with `current_version_id`
- `template_versions` — language+body per version
- `inbound_messages` — raw inbound messages from WhatsApp
- `media_files` — uuid PK, disk/path/mime/size, soft deletes
- `provider_logs` — outbound/inbound API call log
- `audit_logs` — full action trail (user, action, module, target, ip, old/new values)
- `opt_out_requests` — keyword-triggered opt-outs from inbound messages
- `roles` / `user_roles` — simple RBAC junction tables

**Single migration file:** `database/migrations/2026_04_23_000100_create_noiachat_core_tables.php`

---

## Environment Variables

```bash
# App
APP_NAME=NoiaChat
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database (SQLite default — no extra config needed for dev)
DB_CONNECTION=sqlite

# Queue & Cache
QUEUE_CONNECTION=database
CACHE_STORE=database

# WhatsApp Cloud API
WHATSAPP_API_BASE_URL=https://graph.facebook.com/v21.0
WHATSAPP_ACCESS_TOKEN=           # Required: Meta API access token
WHATSAPP_PHONE_NUMBER_ID=        # Required: WhatsApp phone number ID
WHATSAPP_BUSINESS_ACCOUNT_ID=    # Required: Business account ID
WHATSAPP_WEBHOOK_VERIFY_TOKEN=   # Required: Custom token for webhook verification

# Seeded admin user
NOIACHAT_ADMIN_NAME="Admin NoiaChat"
NOIACHAT_ADMIN_EMAIL=admin@noiachat.local
NOIACHAT_ADMIN_PASSWORD=password
```

---

## Routes Summary

| Prefix | Module | Auth required |
|---|---|---|
| `/` | Dashboard redirect | Yes |
| `/dashboard` | Reports | Yes |
| `/contacts` | Contacts (full CRUD + consents + blacklist) | Yes |
| `/conversations` | Conversations (view, assign, reply) | Yes |
| `/messages` | Messaging (send text/image/doc/template, retry) | Yes |
| `/audit-logs` | Audit | Yes |
| `/settings` | Channels + templates | Yes |
| `/profile` | User profile | Yes |
| `/webhooks/whatsapp` | Webhook receiver (GET verify + POST receive) | **No** |
| `/register`, `/login`, etc. | Auth (Breeze) | No |

---

## Common Commands

```bash
# First-time setup
composer setup          # installs deps, runs migrations, npm install + build

# Development
composer dev            # starts server + queue worker + log viewer + Vite (concurrently)

# Individual services
php artisan serve       # web server (localhost:8000)
php artisan queue:listen # process queue jobs
php artisan pail        # stream logs
npm run dev             # Vite HMR

# Database
php artisan migrate
php artisan db:seed
php artisan tinker

# Tests
php artisan test
php artisan test --filter=NoiaChatMvp

# Code style
./vendor/bin/pint

# Production build
npm run build
```

---

## Testing

- **Framework:** PHPUnit 12
- **Test database:** SQLite `:memory:` (fast, isolated)
- **Suites:**
  - `tests/Feature/Auth/` — Breeze auth flows
  - `tests/Feature/NoiaChatMvpTest.php` — core feature tests
  - `tests/Unit/` — unit tests

---

## Frontend

- **Blade templates** in `resources/views/noia/` (per module subdirectory)
- **Alpine.js** for reactive UI (initialized in `resources/js/app.js`)
- **Tailwind CSS** with custom component classes in `resources/css/app.css`:
  - `noia-card`, `noia-input`, `noia-label`, `noia-btn-primary`, `noia-btn-secondary`, etc.
- **Layouts:** `AppLayout` (authenticated) and `GuestLayout` (public) in `app/View/Components/`

---

## Key Domain Enums

```php
MessageStatus:    QUEUED | SENDING | SENT | DELIVERED | READ | FAILED | BLOCKED_BY_POLICY
MessageType:      TEXT | IMAGE | DOCUMENT | TEMPLATE
ConsentStatus:    GRANTED | REVOKED | EXPIRED
ContactStatus:    ACTIVE | INACTIVE
ConversationStatus: OPEN | CLOSED
EligibilityStatus: ALLOWED | BLOCKED_BY_CONSENT | BLOCKED_BY_FREQUENCY | BLOCKED_BY_BLACKLIST
AuditActionType:  CREATE | UPDATE | DELETE | SEND | GRANT | REVOKE | ...
```

---

## Important Files to Know

| File | Purpose |
|---|---|
| `app/Providers/AppServiceProvider.php` | Main DI bindings |
| `app/Modules/Shared/` | Shared contracts and exceptions |
| `app/Modules/Compliance/Domain/Policies/MessageEligibilityPolicy.php` | Core business rule engine |
| `app/Modules/Messaging/Infrastructure/Integrations/WhatsAppCloudApiProvider.php` | WhatsApp API client |
| `app/Modules/Webhooks/Presentation/Controllers/WhatsAppWebhookController.php` | Webhook entry point |
| `database/migrations/2026_04_23_000100_create_noiachat_core_tables.php` | Full DB schema |
| `database/seeders/AdminUserSeeder.php` | Default admin credentials |
| `routes/web.php` | Route aggregator (includes module routes) |
| `resources/css/app.css` | Tailwind + custom Noia component classes |

---

## Development Notes

- The project was built with agentic development tooling in mind (Claude Code, Cursor, Copilot).
- Modules under `Automations` and `Campaigns` are scaffolded but not yet implemented.
- All external integrations go through `Infrastructure/Integrations/` — never call WhatsApp API directly from controllers.
- Compliance check is **mandatory** before queuing any outbound message — always route through a Use Case, not directly to a job.
- Repository interfaces live in `Domain/Repositories/`; implementations live in `Infrastructure/Persistence/Repositories/`.
- Do not put Eloquent models outside of `Infrastructure/Persistence/Models/` — they must not leak into Domain or Application layers.
