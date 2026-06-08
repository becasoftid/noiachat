# Changelog

Todos los cambios relevantes de NoiaChat se documentaran en este archivo.

El formato sigue una estructura inspirada en [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) y el versionado propuesto es [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Gestion documental de funcionalidades con matriz de estado, backlog priorizado y plantilla de registro en `docs/`.
- Manual paso a paso de integracion con WhatsApp Cloud API en `docs/integracion-whatsapp.md`.
- Workflow de GitHub Actions para desplegar NoiaChat automaticamente en la droplet cuando se suben cambios a `main` o `master`.
- Validacion del secreto `DROPLET_SSH_KEY` antes de ejecutar el despliegue por SSH.
- Script de despliegue remoto con modo mantenimiento, sincronizacion con el remoto, instalacion de dependencias, migraciones, build de frontend, cacheo de Laravel, permisos y reinicio de servicios.
- Ajuste del despliegue automatico para ejecutarse solo desde `main`, leer host, usuario y puerto desde secretos de GitHub Actions, y sincronizar siempre contra `origin/main`.

### Changed

- Aplicacion de la ventana de atencion de 24 horas de WhatsApp para bloquear texto libre y adjuntos fuera de ventana, permitiendo plantillas aprobadas.
- Validacion opcional de firma `X-Hub-Signature-256` para webhooks de Meta usando `WHATSAPP_APP_SECRET`.
- Ajuste del flujo de mensajes multimedia para respetar compliance antes de subir adjuntos o encolar jobs de WhatsApp.
- Ajuste del registro de envios WhatsApp para marcar como fallidos los mensajes cuando Meta responde con un error de proveedor, evitando estados `sent` sin `provider_message_id`.
- Ajuste del webhook de WhatsApp para crear contactos provisionales con numeros entrantes desconocidos, relacionar mensajes recibidos con conversaciones y detectar contactos existentes aunque el telefono tenga formato local o internacional.
- Ajuste de la integracion de WhatsApp para excluir el webhook de la validacion CSRF y leer credenciales desde `config/services.php`, compatible con cache de configuracion en produccion.
- Estandarizacion visual de las vistas del panel, formularios, tablas, modales, navegacion, pantallas de cuenta y alertas globales para alinearlas con el nuevo lenguaje grafico aplicado al login.
- Redisenio de la pantalla de login con una interfaz moderna para NoiaChat, panel visual de marca, formulario responsive y estilos enfocados en una aplicacion operativa de mensajeria y compliance.
- Ajuste del workflow de despliegue para conectar directamente a la droplet `167.172.251.181` con usuario `root` y puerto `22`, manteniendo la autenticacion por el secreto `DROPLET_SSH_KEY`.
- Ajuste del despliegue remoto para validar la disponibilidad de Composer y ejecutar la instalacion de dependencias PHP usando la ruta detectada por `which composer`.

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
