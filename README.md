# NoiaChat

NoiaChat es una aplicacion web en Laravel para operar mensajeria de WhatsApp con control de contactos, consentimientos, listas de bloqueo, conversaciones, plantillas, auditoria y trazabilidad de estados de mensajes.

El proyecto esta construido como un MVP funcional sobre Laravel, Breeze, Blade, Tailwind CSS, Vite y colas de base de datos. La integracion principal contemplada es WhatsApp Cloud API.

## Tabla de Contenido

- [Caracteristicas](#caracteristicas)
- [Stack Tecnico](#stack-tecnico)
- [Requisitos](#requisitos)
- [Instalacion Local](#instalacion-local)
- [Configuracion de Entorno](#configuracion-de-entorno)
- [Base de Datos y Datos Iniciales](#base-de-datos-y-datos-iniciales)
- [Ejecucion](#ejecucion)
- [Pruebas](#pruebas)
- [Arquitectura](#arquitectura)
- [Modulos Principales](#modulos-principales)
- [Roles y Permisos](#roles-y-permisos)
- [Flujos del MVP](#flujos-del-mvp)
- [WhatsApp Cloud API](#whatsapp-cloud-api)
- [Manual de Integracion WhatsApp](#manual-de-integracion-whatsapp)
- [Gestion Documental](#gestion-documental)
- [Rutas Relevantes](#rutas-relevantes)
- [Convenciones de Desarrollo](#convenciones-de-desarrollo)
- [Despliegue](#despliegue)
- [Seguridad](#seguridad)
- [Changelog](#changelog)

## Caracteristicas

- Autenticacion de usuarios con Laravel Breeze.
- Panel autenticado para administrar el ciclo operativo de mensajeria.
- Gestion de contactos con telefono principal normalizado.
- Gestion de consentimiento por canal.
- Lista de bloqueo por contacto y canal.
- Validacion de elegibilidad antes de enviar mensajes.
- Envio en cola de mensajes de texto, imagen, documento y plantilla.
- Conversaciones con asignacion a operadores y respuestas desde el panel.
- Plantillas versionadas con activacion y desactivacion.
- Webhooks de WhatsApp para estados e inbound messages.
- Deteccion de palabras de opt-out como `STOP` y `NO ENVIAR`.
- Auditoria de acciones clave.
- Seeders para datos iniciales del MVP.
- Suite de pruebas funcionales para reglas de negocio y flujos principales.

## Stack Tecnico

- PHP `^8.3`
- Laravel `^13.0`
- Laravel Breeze `^2.4`
- PHPUnit `^12.5`
- Laravel Pint `^1.27`
- Node.js y npm
- Vite `^8.0`
- Tailwind CSS `^3.1`
- Alpine.js `^3.4`
- SQLite por defecto en desarrollo
- Colas con driver `database`

## Requisitos

Antes de instalar, confirma que tienes:

- PHP 8.3 o superior.
- Composer.
- Node.js y npm.
- SQLite o una base de datos compatible con Laravel.
- Una cuenta y credenciales de WhatsApp Cloud API si vas a enviar mensajes reales.

## Instalacion Local

Clona el repositorio:

```bash
git clone https://github.com/becasoftid/noiachat.git
cd noiachat
```

Instala dependencias:

```bash
composer install
npm install
```

Crea el archivo de entorno:

```bash
cp .env.example .env
php artisan key:generate
```

Si vas a usar SQLite, crea el archivo de base de datos si no existe:

```bash
touch database/database.sqlite
```

Ejecuta migraciones y seeders:

```bash
php artisan migrate --seed
```

Compila assets:

```bash
npm run build
```

Tambien puedes usar el script de preparacion incluido:

```bash
composer run setup
```

## Configuracion de Entorno

Variables principales incluidas en `.env.example`:

```dotenv
APP_NAME=NoiaChat
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

NOIACHAT_ADMIN_NAME="Admin NoiaChat"
NOIACHAT_ADMIN_EMAIL=admin@noiachat.local
NOIACHAT_ADMIN_PASSWORD=Password

WHATSAPP_API_BASE_URL=https://graph.facebook.com/v21.0
WHATSAPP_ACCESS_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_BUSINESS_ACCOUNT_ID=
WHATSAPP_WEBHOOK_VERIFY_TOKEN=
WHATSAPP_APP_SECRET=
```

Notas:

- No subas `.env` al repositorio.
- Cambia la clave del usuario administrador en cualquier entorno compartido.
- Para produccion, configura una base de datos persistente, cache, sesiones y colas segun tu infraestructura.

## Base de Datos y Datos Iniciales

Las migraciones crean las tablas base de Laravel y el dominio de NoiaChat:

- Usuarios, sesiones, cache y jobs.
- Roles y relacion usuario-rol.
- Canales.
- Contactos y canales asociados.
- Consentimientos y listas de bloqueo.
- Conversaciones.
- Plantillas y versiones de plantillas.
- Medios y adjuntos.
- Mensajes, eventos de mensaje y mensajes entrantes.
- Solicitudes de opt-out.
- Logs de proveedor y auditoria.

Los seeders iniciales crean:

- Roles: `admin`, `operator`, `auditor`.
- Canal `whatsapp`.
- Usuario administrador configurable por variables `NOIACHAT_ADMIN_*`.
- Plantilla inicial `recordatorio_pago`.
- Contactos de ejemplo.
- Consentimientos iniciales para contactos sembrados.

## Ejecucion

Para desarrollo completo:

```bash
composer run dev
```

Este comando levanta en paralelo:

- Servidor HTTP de Laravel.
- Worker de cola.
- Logs con Laravel Pail.
- Servidor de desarrollo de Vite.

Alternativa manual:

```bash
php artisan serve
npm run dev
php artisan queue:listen --tries=1 --timeout=0
```

Acceso local habitual:

- Aplicacion: `http://127.0.0.1:8000`
- Login: `http://127.0.0.1:8000/login`

Credenciales por defecto despues de ejecutar seeders:

- Email: `admin@noiachat.local`
- Password: `Password`

## Pruebas

Ejecuta la suite:

```bash
composer test
```

O directamente:

```bash
php artisan test
```

La suite incluye pruebas para:

- Bloqueo de envio sin consentimiento.
- Bloqueo de envio a contactos en blacklist.
- Encolado de mensajes.
- Eventos de cambio de estado.
- Webhooks de entregado.
- Opt-out por mensajes entrantes.
- Permisos por rol.
- Gestion de conversaciones.
- Plantillas.
- Respuestas con documentos.
- Reintento de mensajes fallidos.

## Arquitectura

El proyecto usa una organizacion modular dentro de `app/Modules`, separando responsabilidades por dominio y capa.

Estructura general de un modulo:

```text
app/Modules/<Modulo>/
├── Application/
├── Domain/
├── Infrastructure/
└── Presentation/
```

Capas:

- `Domain`: enums, contratos, politicas y reglas del dominio.
- `Application`: casos de uso, servicios y DTOs.
- `Infrastructure`: modelos Eloquent, repositorios, integraciones, jobs y service providers.
- `Presentation`: controladores, requests y rutas HTTP.

Los service providers modulares se registran en `bootstrap/providers.php`.

## Modulos Principales

### Contacts

Administra contactos, telefonos, canales asociados y formularios de creacion o edicion.

### Consents

Gestiona consentimientos por contacto y canal. Se usa para decidir si un mensaje puede enviarse.

### Compliance

Centraliza reglas de elegibilidad:

- Contacto con consentimiento vigente.
- Contacto no bloqueado.
- Controles de frecuencia.
- Deteccion de opt-out.

### Messaging

Contiene el nucleo de envios:

- Casos de uso para texto, imagen, documento y plantilla.
- Jobs de envio por WhatsApp.
- Estados de mensaje.
- Eventos de mensaje.
- Logs del proveedor.
- Reintentos.

### Conversations

Agrupa interacciones por contacto y canal. Permite asignar conversaciones, responder con texto, multimedia o plantillas.

### Webhooks

Procesa webhooks de WhatsApp para:

- Verificacion del webhook.
- Estados de mensajes.
- Mensajes entrantes.
- Solicitudes de opt-out.

### Settings

Permite administrar canales y plantillas desde el panel.

### Audit

Expone el historial de auditoria de acciones relevantes.

### Users

Define politicas de autorizacion y roles.

## Roles y Permisos

Roles iniciales:

- `admin`: acceso administrativo, configuracion y operaciones completas.
- `operator`: operaciones sobre contactos, conversaciones y mensajes permitidos.
- `auditor`: acceso de consulta y auditoria, sin permisos de envio.

Las autorizaciones se aplican con policies y middlewares `can:*` en rutas de los modulos.

## Flujos del MVP

### Envio de Mensaje

1. El usuario autenticado selecciona un contacto y canal.
2. El sistema valida consentimiento y blacklist.
3. Se crea un mensaje con estado `queued`.
4. Se despacha un job de envio.
5. El proveedor responde.
6. El sistema registra eventos y logs.

### Webhook de Estado

1. WhatsApp envia un evento de estado.
2. NoiaChat localiza el mensaje por `provider_message_id`.
3. Se actualiza el estado.
4. Se registra un evento de mensaje.

### Opt-out

1. Un contacto envia una palabra de exclusion.
2. El webhook procesa el mensaje entrante.
3. Se crea una solicitud de opt-out.
4. El contacto se agrega a la blacklist del canal.
5. Nuevos envios quedan bloqueados por compliance.

### Conversacion

1. Un operador abre una conversacion.
2. Puede asignarla a un usuario y actualizar su estado.
3. Puede responder con texto, documento, imagen o plantilla.
4. Cada respuesta queda asociada a la conversacion y al contacto.

## WhatsApp Cloud API

Variables requeridas para integracion real:

- `WHATSAPP_API_BASE_URL`
- `WHATSAPP_ACCESS_TOKEN`
- `WHATSAPP_PHONE_NUMBER_ID`
- `WHATSAPP_BUSINESS_ACCOUNT_ID`
- `WHATSAPP_WEBHOOK_VERIFY_TOKEN`
- `WHATSAPP_APP_SECRET`

Endpoint de webhook:

```text
GET  /webhooks/whatsapp
POST /webhooks/whatsapp
```

En Meta Developers configura la URL publica del webhook y el token de verificacion definido en `WHATSAPP_WEBHOOK_VERIFY_TOKEN`. Para validar la firma de los POST del webhook, configura tambien `WHATSAPP_APP_SECRET` con el app secret de Meta.

## Manual de Integracion WhatsApp

Consulta la guia operativa paso a paso en [docs/integracion-whatsapp.md](docs/integracion-whatsapp.md).

## Gestion Documental

El control documental de funcionalidades, estados, prioridades y tareas pendientes se gestiona desde:

- [docs/gestion-documental.md](docs/gestion-documental.md)
- [docs/funcionalidades.md](docs/funcionalidades.md)
- [docs/plantilla-funcionalidad.md](docs/plantilla-funcionalidad.md)

## Rutas Relevantes

Rutas autenticadas:

- `/dashboard`
- `/contacts`
- `/messages`
- `/conversations`
- `/audit-logs`
- `/settings`
- `/profile`

Rutas publicas del webhook:

- `/webhooks/whatsapp`

La ruta raiz redirige al dashboard si hay sesion activa o al login si no hay autenticacion.

## Convenciones de Desarrollo

- Mantener la logica de negocio en `Application` y `Domain`.
- Mantener controladores delgados en `Presentation`.
- Usar requests dedicados para validacion HTTP.
- Registrar nuevas rutas dentro del modulo correspondiente.
- Registrar nuevos bindings en el service provider del modulo.
- Agregar pruebas funcionales cuando cambien reglas de negocio.
- No versionar secretos, dependencias instaladas ni builds generados.

Formateo recomendado:

```bash
./vendor/bin/pint
```

Build de frontend:

```bash
npm run build
```

## Despliegue

El workflow de GitHub Actions usa secretos del repositorio para conectarse al droplet. Configura estos valores en `Settings > Secrets and variables > Actions`:

- `DROPLET_HOST`
- `DROPLET_USERNAME`
- `DROPLET_PORT`
- `DROPLET_SSH_KEY`

El worker permanente de colas se configura con Supervisor. Consulta [docs/deploy-workers.md](docs/deploy-workers.md).

Los backups automaticos se generan con el comando `noiachat:backup` y el cron versionado en `deploy/cron/noiachat-backup`. Consulta [docs/deploy-backups.md](docs/deploy-backups.md).

Checklist sugerido:

1. Configurar `.env` de produccion.
2. Definir `APP_ENV=production` y `APP_DEBUG=false`.
3. Configurar base de datos persistente.
4. Configurar driver de cola y levantar workers con Supervisor.
5. Configurar backups automaticos.
6. Ejecutar `composer install --no-dev --optimize-autoloader`.
7. Ejecutar `npm ci && npm run build`.
8. Ejecutar `php artisan migrate --force`.
9. Ejecutar optimizaciones de Laravel:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

10. Configurar el webhook publico en Meta Developers.
11. Rotar credenciales y revisar permisos del usuario administrador.

## Seguridad

- `.env` esta ignorado por Git.
- Cambia `NOIACHAT_ADMIN_PASSWORD` antes de usar el sistema fuera de local.
- Usa tokens de WhatsApp con el menor alcance posible.
- Revisa logs de proveedor y auditoria para trazabilidad.
- Protege el endpoint publico de webhooks con verificacion de token.
- Mantiene las dependencias actualizadas y ejecuta pruebas antes de desplegar.

## Changelog

Consulta [CHANGELOG.md](CHANGELOG.md).

## Licencia

Este repositorio aun no define un archivo de licencia propio. Antes de distribuirlo publicamente, agrega una licencia acorde con la politica del proyecto.
