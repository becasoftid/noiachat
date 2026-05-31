# Changelog

Todos los cambios relevantes de NoiaChat se documentaran en este archivo.

El formato sigue una estructura inspirada en [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) y el versionado propuesto es [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Workflow de GitHub Actions para desplegar NoiaChat automaticamente en la droplet cuando se suben cambios a `main` o `master`.
- Validacion del secreto `DROPLET_SSH_KEY` antes de ejecutar el despliegue por SSH.
- Script de despliegue remoto con modo mantenimiento, sincronizacion con el remoto, instalacion de dependencias, migraciones, build de frontend, cacheo de Laravel, permisos y reinicio de servicios.
- Ajuste del despliegue automatico para ejecutarse solo desde `main`, leer host, usuario y puerto desde secretos de GitHub Actions, y sincronizar siempre contra `origin/main`.

### Changed

- Ajuste del workflow de despliegue para conectar directamente a la droplet `167.172.251.181` con usuario `root` y puerto `22`, manteniendo la autenticacion por el secreto `DROPLET_SSH_KEY`.

### Pendiente

- Definir version publica inicial.
- Agregar archivo de licencia del proyecto.
- Completar documentacion de despliegue especifica para el entorno final.

## [0.1.0] - 2026-05-31

### Added

- Base del proyecto NoiaChat sobre Laravel 13.
- Autenticacion con Laravel Breeze.
- Estructura modular en `app/Modules` por capas `Domain`, `Application`, `Infrastructure` y `Presentation`.
- Modulo de contactos con creacion, edicion, visualizacion y normalizacion de telefono.
- Modulo de consentimientos con otorgamiento y revocacion por canal.
- Lista de bloqueo por contacto y canal.
- Reglas de compliance para bloquear envios sin consentimiento o a contactos excluidos.
- Modulo de mensajeria con casos de uso para texto, imagen, documento y plantillas.
- Jobs para envio de mensajes por WhatsApp Cloud API.
- Registro de estados, eventos de mensaje, adjuntos y logs de proveedor.
- Modulo de conversaciones con asignacion, respuesta de texto, respuesta multimedia y respuesta con plantilla.
- Modulo de plantillas versionadas administrables desde configuracion.
- Webhook de WhatsApp para verificacion, eventos de estado y mensajes entrantes.
- Deteccion de opt-out por palabras clave como `STOP` y `NO ENVIAR`.
- Modulo de auditoria para trazabilidad de acciones.
- Roles iniciales `admin`, `operator` y `auditor` con policies de acceso.
- Dashboard autenticado y navegacion del panel.
- Migraciones para el modelo de datos del MVP.
- Seeders para roles, canal WhatsApp, administrador, contactos, consentimientos y plantilla inicial.
- Configuracion de entorno de ejemplo para NoiaChat y WhatsApp Cloud API.
- Pruebas funcionales del MVP para compliance, webhooks, conversaciones, permisos, plantillas y reintentos.
- README detallado del proyecto.

### Security

- Variables sensibles excluidas del repositorio mediante `.gitignore`.
- Usuario administrador configurable por variables de entorno.
- Validaciones de autorizacion por rol en rutas operativas.
