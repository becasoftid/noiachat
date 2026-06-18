# Changelog

Todos los cambios relevantes de NoiaChat se documentaran en este archivo.

El formato sigue una estructura inspirada en [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) y el versionado propuesto es [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- CRUD de usuarios desde el panel para administradores, con asignacion de roles, estado activo/inactivo y auditoria de cambios.
- Accion "Asignar a mi" y filtro "Mis conversaciones" en el inbox de conversaciones.
- Indicador de mensajes entrantes sin leer en conversaciones y marcado automatico como leida al abrir una conversacion.
- Auto-refresh simple del inbox de conversaciones mediante polling y endpoint parcial.
- Indicadores visuales de estado para mensajes salientes en conversaciones, incluyendo enviado, entregado, leido y hora local de lectura cuando Meta la reporta.
- Sonido opcional de nuevo mensaje entrante en el inbox, activable por operador y disparado cuando aumenta el contador de no leidos durante el auto-refresh.
- Motivos visibles de bloqueo por compliance en flashes, listado/detalle de mensajes y timeline de conversaciones.
- Importacion masiva de contactos desde CSV/XLSX con validacion por fila, resumen de creados/omitidos y auditoria.
- Descarga CSV de errores de importacion de contactos para corregir archivos con filas invalidas.
- Deteccion de telefonos duplicados durante importacion, tanto dentro del archivo como contra contactos existentes.
- Fusion controlada de contactos para administradores, moviendo historial operativo al contacto destino y archivando el origen con auditoria.
- Historial visible de consentimiento en el detalle de contacto con canal, fuente, fechas, usuarios y notas.
- Sincronizacion de plantillas WhatsApp desde Meta, con estado real, categoria, fecha de sync, componentes y conteo de variables visibles en configuracion.
- Validacion de cantidad exacta de variables al enviar plantillas, con errores visibles en conversacion y proteccion en el caso de uso.
- Aviso preventivo en conversaciones cuando la ventana de atencion WhatsApp de 24h esta cerrada, deshabilitando texto libre y adjuntos para orientar al uso de plantillas.
- Validacion de URL publica HTTPS para envio multimedia por WhatsApp, con fallos claros cuando el archivo no es accesible para Meta.
- Gestion documental de funcionalidades con matriz de estado, backlog priorizado y plantilla de registro en `docs/`.
- Plan de trabajo y reglas de negocio en `docs/plan-trabajo-reglas-negocio.md`.
- Analisis y plan multiempresa/multisede en `docs/multiempresa-multisede.md`, con brecha actual, reglas de negocio y actividades `MT-00` a `MT-10`.
- Modelo base multiempresa con tablas `companies`, `branches` y `memberships`, relaciones de usuario/rol, empresa/sede por defecto y pruebas iniciales.
- Columnas tenant `company_id` y `branch_id` en tablas operativas, backfill a empresa/sede por defecto y trait para asignar contexto por defecto a nuevas entidades.
- Contexto activo multiempresa por request con middleware, sesion, cambio de membresia y selector compacto de empresa/sede en el layout operativo.
- Aislamiento inicial multiempresa con scope `forTenantContext`, policies por empresa/sede activa, listados filtrados y pruebas de acceso cruzado.
- Canales WhatsApp por empresa/sede: envios, webhooks, firmas y sincronizacion de plantillas resuelven credenciales desde el canal operativo, con pruebas de aislamiento por `phone_number_id`.
- Reglas base de contactos por empresa/canal: importacion permite telefonos repetidos en empresas distintas, edicion bloquea duplicados dentro del canal activo y fusion prohibe mezclar empresas.
- Reglas base de conversaciones por sede: inbox filtrable por sede para alcance de empresa y asignacion validada contra membresias de la sede de la conversacion.
- Filtros base de auditoria y dashboard por sede para usuarios con alcance de empresa, respetando el scope de empresa/sede activa.
- Administracion UI global para `super_admin` y del tenant activo para `company_admin`: empresas, sedes y membresias con validacion cross-company, auditoria y sincronizacion de roles desde membresias activas.
- Roles finales multiempresa `super_admin`, `company_admin`, `branch_manager`, `operator` y `auditor`, con gates y policies que combinan rol con membresia activa y conservan compatibilidad legacy.
- Comando `noiachat:tenant-validate` para validar preparacion multiempresa antes de despliegue: roles, tablas tenant, filas operativas sin empresa, consistencia de membresias y backup reciente.
- Checklist productivo multiempresa en `docs/multiempresa-checklist-produccion.md`, con backup obligatorio, validacion en copia, pruebas de aislamiento, deploy, verificacion posterior y rollback.
- Panel de fallos recientes en `/failures` para administradores, consolidando mensajes reintentables, errores de proveedor y ultimos jobs fallidos.
- Detalle de auditoria en `/audit-logs/{id}` con contexto operativo, comparacion old/new y JSON tecnico respetando permisos por empresa/sede.
- Exportaciones CSV de auditoria, contactos, mensajes y conversaciones, aplicando filtros actuales y alcance de empresa/sede.
- Monitor de salud operativo en `/health` y comando `noiachat:health-check` para revisar jobs fallidos, cola pendiente, disco, webhook, backups y errores recientes.
- Endurecimiento de settings de canal WhatsApp: secretos enmascarados, preservacion al dejar campos vacios y validaciones HTTPS/Meta ID.
- Gobierno de rotacion del token WhatsApp por canal: expiracion, ultima rotacion, responsable, procedimiento y alertas en monitor de salud.
- 2FA OTP por email para roles administrativos (`admin`, `super_admin`, `company_admin` y `branch_manager`) antes de acceder al panel.
- Onboarding de prueba basica desde `/register`: crea usuario responsable, empresa, sede inicial, membresia `company_admin` y suscripcion `trialing` al plan `basic_trial`.
- Modelo inicial de planes, suscripciones y features por plan: tablas `plans`, `company_subscriptions`, `features` y `plan_features`, con seeders de `basic_trial`, `basic`, `pro` y `enterprise`.
- Servicio central de evaluacion de planes y suscripciones para validar estado operativo, features incluidas, limites y dias restantes de trial.
- Middleware `feature` para bloquear rutas cuando el plan de la empresa no incluye una funcionalidad, aplicado inicialmente a usuarios, sedes/membresias y settings/plantillas.
- Servicio de limites por plan para impedir exceder usuarios, sedes, contactos y canales WhatsApp activos segun la suscripcion de la empresa.
- Comando `noiachat:subscriptions-check` para vencer trials expirados, auditar el cambio y mostrar avisos de trial vencido o proximo a vencer en el panel.
- Panel `/billing` de plan y suscripcion con estado, dias restantes, limites consumidos, features incluidas y cambio manual auditado para `super_admin`.
- Manual comercial de billing en `docs/billing-checklist-comercial.md` con matriz de planes/features, estados de suscripcion, procedimientos y checklist productivo.
- Catalogo comercial comparativo en `/billing` usando metadata de planes y flujo interno de solicitudes de upgrade con aprobacion/rechazo auditados.
- Configuracion Supervisor para worker permanente de colas en `deploy/supervisor/noiachat-worker.conf` y manual operativo en `docs/deploy-workers.md`.
- Comando `noiachat:backup`, cron de backups automaticos y manual operativo/restauracion en `docs/deploy-backups.md`.
- Manual paso a paso de integracion con WhatsApp Cloud API en `docs/integracion-whatsapp.md`.
- Workflow de GitHub Actions para desplegar NoiaChat automaticamente en la droplet cuando se suben cambios a `main` o `master`.
- Validacion del secreto `DROPLET_SSH_KEY` antes de ejecutar el despliegue por SSH.
- Script de despliegue remoto con modo mantenimiento, sincronizacion con el remoto, instalacion de dependencias, migraciones, build de frontend, cacheo de Laravel, permisos y reinicio de servicios.
- Ajuste del despliegue automatico para ejecutarse solo desde `main`, leer host, usuario y puerto desde secretos de GitHub Actions, y sincronizar siempre contra `origin/main`.
- Menu lateral colapsable/expandible con iconos por seccion y preferencia persistida en el navegador.

### Changed

- El login ahora rechaza usuarios inactivos mediante el campo `is_active`.
- Aplicacion de la ventana de atencion de 24 horas de WhatsApp para bloquear texto libre y adjuntos fuera de ventana, permitiendo plantillas aprobadas.
- El envio por plantilla ahora bloquea plantillas sincronizadas que Meta no reporte como `APPROVED`.
- Visualizacion de errores de Meta en el detalle del mensaje y en el timeline de conversaciones, incluyendo codigo, mensaje, detalle y payload tecnico.
- Endurecimiento del workflow de despliegue para usar secretos `DROPLET_HOST`, `DROPLET_USERNAME`, `DROPLET_PORT` y `DROPLET_SSH_KEY`, retirando host quemado y debug.
- Ajuste del workflow de despliegue para instalar o actualizar la configuracion del worker y reiniciar procesos de Supervisor cuando este disponible.
- Ajuste del workflow de despliegue para instalar el cron de backups automaticos.
- Validacion opcional de firma `X-Hub-Signature-256` para webhooks de Meta usando `WHATSAPP_APP_SECRET`.
- Ajuste del flujo de mensajes multimedia para respetar compliance antes de subir adjuntos o encolar jobs de WhatsApp.
- Ajuste del registro de envios WhatsApp para marcar como fallidos los mensajes cuando Meta responde con un error de proveedor, evitando estados `sent` sin `provider_message_id`.
- Ajuste del webhook de WhatsApp para crear contactos provisionales con numeros entrantes desconocidos, relacionar mensajes recibidos con conversaciones y detectar contactos existentes aunque el telefono tenga formato local o internacional.
- Ajuste del webhook de WhatsApp para resolver empresa, sede y canal por `metadata.phone_number_id`, evitando mezclar contactos cuando un mismo telefono existe en varias empresas.
- Ajuste de jobs y logs de WhatsApp para conservar `company_id` y `branch_id` en envios, errores de proveedor, inbound, opt-outs y blacklist.
- Ajuste de la fusion de contactos para validar tenant del origen, conservar aislamiento por empresa y evitar colisiones de telefonos activos por canal.
- Ajuste de asignacion de conversaciones para impedir asignar operadores sin acceso a la empresa/sede de la conversacion.
- Ajuste de consultas de auditoria y metricas para aplicar filtros de sede solo dentro del alcance permitido por la membresia activa.
- Ajuste de pruebas de backup para validar que el manifiesto incluye metadatos utiles antes de una migracion productiva.
- Ajuste de la integracion de WhatsApp para excluir el webhook de la validacion CSRF y leer credenciales desde `config/services.php`, compatible con cache de configuracion en produccion.
- Estandarizacion visual de las vistas del panel, formularios, tablas, modales, navegacion, pantallas de cuenta y alertas globales para alinearlas con el nuevo lenguaje grafico aplicado al login.
- Redisenio de la pantalla de login con una interfaz moderna para NoiaChat, panel visual de marca, formulario responsive y estilos enfocados en una aplicacion operativa de mensajeria y compliance.
- Redisenio operativo de conversaciones con distribucion tipo WhatsApp Web: inbox lateral, filas compactas, conversacion activa, burbujas, agrupacion por fecha y compositor inferior.
- Ajuste del inbox de conversaciones para operar como panel de trabajo con filtros compactos, listado con scroll y estado vacio cuando no hay conversacion seleccionada.
- Ajuste del flujo de conversaciones para cargar el chat activo dentro de `/conversations?conversation=...`, manteniendo una sola vista operativa.
- Ajuste inicial del workflow de despliegue para conectar por SSH a la droplet usando autenticacion por secreto.
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
