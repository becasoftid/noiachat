# Matriz de funcionalidades NoiaChat

Ultima actualizacion: 2026-06-22

Esta matriz controla el estado funcional del proyecto. Debe actualizarse cada vez que se implemente, cambie, valide o descarte una funcionalidad.

## Resumen ejecutivo

| Area | Estado general | Lectura rapida |
| --- | --- | --- |
| Autenticacion y acceso | MVP | Login funcional; registro crea empresa/sede en prueba basica, gestion de usuarios disponible para admin y 2FA OTP obligatorio para roles administrativos; falta validacion SMTP productiva y politicas avanzadas. |
| Multiempresa y sedes | MVP | Modelo base, contexto activo, aislamiento, canales WhatsApp, contactos, conversaciones, auditoria, metricas y administracion UI por empresa/sede creados; falta endurecimiento UX y validacion productiva con empresas reales. |
| Contactos | MVP | CRUD, importacion, deduplicacion basica y fusion controlada funcionan dentro del alcance de empresa/canal. |
| Consentimientos | MVP | Otorgar/revocar funciona; falta historial mas completo y reglas de expiracion si aplican. |
| WhatsApp Cloud API | Operativo | Entrantes, salientes, estados reales, firma de webhook y ventana 24h validados. |
| Conversaciones | Operativo | Inbox, no leidos, auto-refresh y vista tipo chat para atencion diaria. |
| Mensajeria saliente | MVP | Texto validado; multimedia y plantillas requieren endurecimiento. |
| Auditoria | MVP | Registro y filtros funcionan por empresa/sede; falta detalle expandido/exportacion. |
| Reportes | MVP | Dashboard basico segmentado por empresa/sede; faltan metricas operativas avanzadas y exportaciones. |
| Despliegue | Operativo | GitHub Actions funciona con secretos, worker permanente y backups automaticos locales. |
| Seguridad | MVP | Roles, usuarios activos, CSRF base, firma webhook y 2FA para administradores; falta validacion SMTP productiva y politicas productivas avanzadas. |

## Leyenda

- Estado: `Operativo`, `MVP`, `En progreso`, `Pendiente`, `Bloqueado`, `En revision`, `Descartado`.
- Prioridad: `P0`, `P1`, `P2`, `P3`.

## Funcionalidades por modulo

| ID | Modulo | Funcionalidad | Estado | Prioridad | Evidencia actual | Siguiente accion |
| --- | --- | --- | --- | --- | --- | --- |
| AUTH-001 | Autenticacion | Login de administrador | Operativo | P0 | Login validado en produccion; tests de autenticacion | Cambiar contrasena inicial y configurar recuperacion por email real. |
| AUTH-002 | Autenticacion | Recuperacion de contrasena | MVP | P1 | Breeze y tests existentes | Configurar mailer real y probar flujo productivo. |
| AUTH-003 | Usuarios | Gestion de usuarios desde panel | MVP | P1 | CRUD admin, asignacion de roles, bloqueo de inactivos, aislamiento por empresa activa y ocultamiento/bloqueo de administradores globales en Usuarios y Membresias para usuarios comerciales; pruebas en `tests/Feature/UserManagementTest.php` y `tests/Feature/Auth/RegistrationTest.php` | Validar flujo manual en panel y mejorar gestion de sesiones activas. |
| AUTH-004 | Usuarios | 2FA para administradores | MVP | P2 | Desafio OTP por email obligatorio para `admin`, `super_admin`, `company_admin` y `branch_manager`; pruebas en `tests/Feature/Auth/AdminTwoFactorAuthenticationTest.php` | Configurar mailer SMTP real y validar entrega/copy en produccion. |
| ONBOARD-001 | Onboarding | Registro con empresa, sede y plan basico de prueba | MVP | P1 | `/login` enlaza a `Crear cuenta de prueba`; `/register` tiene vista dedicada, toggle de contrasena, validaciones en espanol, crea usuario `company_admin`, empresa, sede inicial, membresia activa y suscripcion `trialing` al plan `basic_trial`; menu comercial oculta y bloquea modulos tecnicos; documentado en `docs/onboarding-registro-trial.md`; pruebas en `tests/Feature/Auth/RegistrationTest.php` | Validar UX en movil/produccion y mantener copy comercial del trial alineado con ventas. |
| BILLING-001 | Planes | Modelo de planes y suscripciones por empresa | MVP | P1 | Tablas `plans` y `company_subscriptions`, modelos, seeder de planes y registro usando suscripcion real; pruebas en `tests/Feature/BillingPlansTest.php` | Implementar servicio de evaluacion de suscripcion y estados vencidos. |
| BILLING-002 | Planes | Funcionalidades permitidas por plan | MVP | P1 | Tablas `features` y `plan_features`, catalogo inicial y matriz por `basic_trial`, `basic`, `pro`, `enterprise`; pruebas en `tests/Feature/BillingPlansTest.php` | Implementar `SubscriptionFeatureService` y middleware `feature`. |
| BILLING-003 | Planes | Servicio de evaluacion de suscripcion y features | MVP | P1 | `SubscriptionFeatureService` resuelve suscripcion, estado operativo, features incluidas, limites y dias restantes de trial; pruebas en `tests/Feature/BillingPlansTest.php` | Implementar middleware `feature` y aplicarlo a rutas clave. |
| BILLING-004 | Planes | Middleware y restricciones por feature | MVP | P1 | Middleware `feature` registrado, bypass para `super_admin`, rutas de usuarios, sedes/membresias y settings/plantillas protegidas por plan; pruebas en `tests/Feature/BillingPlansTest.php` | Aplicar features a importaciones, exportaciones, multimedia y health. |
| BILLING-005 | Planes | Limites por plan | MVP | P1 | `PlanLimitService` valida usuarios, sedes, contactos y activacion de canales WhatsApp contra el plan activo; `super_admin` conserva bypass operativo; pruebas en `tests/Feature/BillingPlansTest.php` | Implementar vencimiento efectivo del trial, avisos y conversion a plan pago. |
| BILLING-006 | Planes | Panel de plan y suscripcion | MVP | P2 | `/billing` muestra plan, estado, dias restantes, consumo de limites y features; `super_admin` cambia plan/estado/fechas con auditoria; pruebas en `tests/Feature/BillingPlansTest.php` | Conectar flujo comercial real de upgrade/pasarela si aplica. |
| BILLING-007 | Planes | Vencimiento de trial y estados | MVP | P1 | Comando `noiachat:subscriptions-check`, auditoria de expiracion, bloqueo operativo por trial vencido y banner de vencimiento/proximo vencimiento; pruebas en `tests/Feature/BillingPlansTest.php` | Conectar el comando a cron/productivo y definir flujo comercial de renovacion. |
| BILLING-008 | Planes | Documentacion y checklist comercial | MVP | P2 | `docs/billing-checklist-comercial.md` documenta matriz de planes/features, estados, procedimientos y checklist comercial | Validar tarifas/copy con el equipo comercial antes de produccion publica. |
| BILLING-PRO-001 | Planes | Catalogo comercial profesional | MVP | P1 | Planes con metadata comercial (`audience`, `commercial_label`, `price_note`, orden y destacado) y comparador visible en `/billing`; pruebas en `tests/Feature/BillingPlansTest.php` | Definir tarifas reales en `price_cents` con el equipo comercial. |
| BILLING-PRO-002 | Planes | Flujo interno de upgrade | MVP | P1 | Solicitudes persistidas en `subscription_change_requests`, bandeja para `super_admin`, aprobacion/rechazo y auditoria; pruebas en `tests/Feature/BillingPlansTest.php` | Conectar notificaciones comerciales por email/Slack si aplica. |
| MT-00 | Multiempresa | Definicion funcional multiempresa/multisede | Operativo | P0 | Roles, alcances, reglas de sede y criterio de enrutamiento aprobados; roles finales sembrados y gates/policies usan membresia activa | Mantener pruebas de aislamiento al ampliar permisos. |
| MT-01 | Multiempresa | Modelo base de empresas, sedes y membresias | MVP | P0 | Migracion `companies`, `branches`, `memberships`, modelos, seeder por defecto y `TenancyBaseTest` | Mantener compatibilidad de seeders y relaciones al ampliar permisos. |
| MT-02 | Multiempresa | Migracion de datos existentes a empresa/sede por defecto | MVP | P0 | Columnas `company_id` y `branch_id` agregadas a tablas operativas, backfill a default/principal, trait de tenant por defecto y pruebas en `TenancyBaseTest` | Validar migracion sobre copia productiva antes de deploy real. |
| MT-03 | Multiempresa | Contexto de empresa activa | MVP | P0 | `TenantContext`, middleware, cambio de membresia activa, sesion y selector compacto en layout operativo | Mejorar UX del selector si un usuario opera muchas empresas/sedes. |
| MT-04 | Multiempresa | Aislamiento por policies y scopes | MVP | P0 | Scope `forTenantContext`, gates/policies con rol y membresia activa, filtros en listados operativos y pruebas de acceso cruzado en `TenancyBaseTest` | Mantener pruebas de aislamiento al agregar nuevas consultas o exportaciones. |
| MT-05 | Multiempresa | Canales WhatsApp por empresa/sede | MVP | P0 | `WhatsAppChannelConfig`, provider con credenciales del canal, webhook por `phone_number_id`, sync de plantillas por canal y pruebas en `TenancyBaseTest` | Validar con dos numeros reales de Meta y ocultar/rotar secretos desde UI. |
| MT-06 | Multiempresa | Contactos, importacion y fusion por empresa | MVP | P1 | Importacion permite el mismo telefono en otra empresa, edicion bloquea duplicados del canal activo y fusion bloquea origen/destino de empresas distintas; pruebas en `ContactImportTest` y `ContactMergeTest` | Mostrar empresa/sede con mas claridad en detalle y mejorar previsualizacion de fusion. |
| MT-07 | Multiempresa | Conversaciones por sede y asignacion segura | MVP | P1 | Inbox filtrable por sede para membresias de empresa, asignacion validada por membresia de la sede y pruebas en `TenancyBaseTest` | Agregar reasignacion manual de sede con auditoria si el negocio lo requiere. |
| MT-08 | Multiempresa | Auditoria y reportes por empresa/sede | MVP | P1 | Auditoria y dashboard usan tenant activo; usuarios con alcance de empresa pueden filtrar por sede; pruebas en `TenancyBaseTest` | Agregar exportaciones y detalle expandido respetando el mismo alcance. |
| MT-09 | Multiempresa | Administracion UI de empresas, sedes y membresias | MVP | P1 | `super_admin` crea/edita empresas globales; empresa activa, sedes y membresias se administran con validacion por tenant | Mejorar UX de seleccion de empresa y alta completa de usuarios por empresa/sede. |
| MT-10 | Multiempresa | Pruebas, migracion productiva y despliegue | MVP | P0 | Checklist productivo, backup verificable, comando `noiachat:tenant-validate`, pruebas de aislamiento y rollback documentado | Ejecutar checklist en staging/copia productiva antes de activar varias empresas reales. |
| CONTACT-001 | Contactos | Crear, editar y consultar contactos | MVP | P0 | CRUD funcional y auditoria visible | Mejorar validaciones y mensajes de error. |
| CONTACT-002 | Contactos | Normalizacion de telefonos | Operativo | P0 | Entrantes WhatsApp relacionan contacto aunque haya formato local/internacional | Agregar pruebas adicionales para formatos internacionales. |
| CONTACT-003 | Contactos | Creacion automatica desde webhook | Operativo | P0 | Conversacion real creada desde mensaje entrante | Mejorar nombre provisional y captura de perfil si Meta lo envia. |
| CONTACT-004 | Contactos | Importacion masiva CSV/Excel | MVP | P1 | Importador CSV/XLSX, validacion por fila, descarga de errores, auditoria y `ContactImportTest` | Agregar vista previa y confirmacion antes de importar. |
| CONTACT-005 | Contactos | Deduplicacion/fusion de contactos | MVP | P1 | Importador detecta duplicados; fusion admin mueve historial y archiva origen; pruebas en `ContactImportTest` y `ContactMergeTest` | Mejorar UI de seleccion y previsualizar impacto antes de fusionar. |
| CONSENT-001 | Consentimientos | Otorgar consentimiento por canal | Operativo | P0 | Respuesta WhatsApp desbloqueada despues de otorgar consentimiento; historial visible en contacto | Agregar filtros/exportacion de consentimientos. |
| CONSENT-002 | Consentimientos | Revocar consentimiento | MVP | P0 | Caso de uso, rutas e historial visible con usuario/fecha | Probar flujo operativo completo y estados posteriores. |
| CONSENT-003 | Consentimientos | Lista de exclusion | MVP | P0 | Bloqueo por blacklist implementado; motivo visible en mensajes y conversaciones | Agregar reportes de exclusiones. |
| CONSENT-004 | Consentimientos | Opt-out automatico por palabra clave | MVP | P0 | `STOP` y `NO ENVIAR` cubiertos por pruebas | Ampliar diccionario y confirmar texto de respuesta operativa. |
| WA-001 | WhatsApp | Verificacion de webhook | Operativo | P0 | Challenge manual y Meta verificado | Agregar monitoreo periodico del endpoint. |
| WA-002 | WhatsApp | Recepcion de mensajes entrantes | Operativo | P0 | Mensajes reales visibles en conversaciones | Agregar indicador de no leidos y auto-refresh. |
| WA-003 | WhatsApp | Envio de texto libre | Operativo | P0 | Mensaje real recibido en WhatsApp; ventana 24h aplicada por compliance y aviso preventivo en conversacion | Mantener prueba real tras cambios de token/proveedor. |
| WA-004 | WhatsApp | Estados enviado/entregado/leido | Operativo | P0 | Estados reales visibles en conversacion con indicador de checks y hora local de lectura cuando Meta reporta `read` | Mantener prueba real tras cambios de token/proveedor. |
| WA-005 | WhatsApp | Firma de webhook `X-Hub-Signature-256` | Operativo | P0 | Validacion HMAC-SHA256 con `WHATSAPP_APP_SECRET` y pruebas automatizadas | Configurar app secret en produccion y verificar evento real. |
| WA-006 | WhatsApp | Manejo de errores de proveedor | Operativo | P0 | Errores se marcan como `failed`, se muestran en detalle/timeline y se consolidan en `/failures` con codigo, descripcion y accion sugerida | Agregar alertas proactivas si se repite el mismo error. |
| WA-007 | WhatsApp | Token permanente y rotacion | Operativo | P0 | Settings registra token enmascarado, expiracion, ultima rotacion, responsable y procedimiento; `/health` alerta tokens vencidos o sin gobierno | Conectar alerta externa si se requiere aviso antes del vencimiento. |
| WA-COM-001 | WhatsApp comercial | Pantalla comercial de canales WhatsApp | MVP | P1 | Ruta `/integrations/whatsapp`, menu comercial `WhatsApp`, listado de canales del tenant activo, credenciales enmascaradas y pruebas en `tests/Feature/Auth/RegistrationTest.php` | Implementar crear/editar canal y credenciales en `WA-COM-003/004`. |
| WA-COM-002 | WhatsApp comercial | Permisos comerciales para integraciones | MVP | P1 | Gate `whatsapp.integration.manage` aplicado a rutas/controlador comerciales; `company_admin` lo tiene sin `platform.access`, `operator` no; pruebas en `tests/Feature/Auth/RegistrationTest.php` | Mantenerlo separado de `platform.access` al ampliar integraciones comerciales. |
| WA-COM-003 | WhatsApp comercial | Crear/editar canal por empresa/sede | MVP | P1 | Rutas comerciales `POST/PATCH /integrations/whatsapp/channels`, aislamiento por empresa/sede, limite de plan y pruebas en `tests/Feature/Auth/RegistrationTest.php` | Validar UX con varias sedes y conectar prueba de conexion Meta. |
| WA-COM-004 | WhatsApp comercial | Formulario seguro de credenciales Meta | MVP | P1 | Formulario captura IDs Meta, secretos, URL Graph API y metadata de rotacion; conserva secretos vacios y audita sin exponer valores completos | Agregar accion de prueba de conexion y mejorar ayudas de captura desde Meta. |
| WA-COM-005 | WhatsApp comercial | Prueba de conexion con Meta | MVP | P1 | Ruta comercial por canal valida credenciales contra Meta, guarda `last_connection_test`, muestra errores legibles y tiene pruebas en `RegistrationTest` | Validar contra una app/numero real de Meta. |
| WA-COM-006 | WhatsApp comercial | Sincronizacion comercial de plantillas | MVP | P1 | Boton comercial por canal reutiliza `WhatsAppTemplateSyncService`, sincroniza dentro de empresa/sede y tiene pruebas en `RegistrationTest` | Validar sync con plantillas aprobadas reales. |
| WA-COM-007 | WhatsApp comercial | Estado operativo del canal | MVP | P2 | Vista comercial muestra `Listo para operar`, `Requiere revision` o `Configuracion incompleta` segun credenciales, prueba de conexion, estado y expiracion | Validar copy con usuario comercial y conectar alertas externas si aplica. |
| WA-COM-008 | WhatsApp comercial | Guia en pantalla y documentacion operativa | MVP | P2 | Checklist Meta en `/integrations/whatsapp` explica datos necesarios y orden de validacion sin exponer secretos | Convertir checklist en ayuda expandible si la pantalla crece. |
| WA-COM-009 | WhatsApp comercial | Pruebas de aislamiento y permisos | MVP | P1 | Pruebas cubren listado aislado por empresa, bloqueo de editar/probar/sincronizar canal ajeno y bloqueo de sede ajena en `RegistrationTest` | Mantener estas pruebas al ampliar integraciones comerciales. |
| WA-COM-010 | WhatsApp comercial | Validacion con numero real | MVP | P1 | Comando `noiachat:whatsapp-commercial-validate {channel_id} --sync-templates` y checklist documentado en `docs/whatsapp-comercial-empresa.md` | Ejecutar en staging/produccion con numero real de Meta y registrar evidencia. |
| MSG-001 | Mensajeria | Cola de mensajes de texto | Operativo | P0 | Envio real por WhatsApp | Mantener worker permanente. |
| MSG-002 | Mensajeria | Envio de imagen/documento | Operativo | P0 | Compliance cubierto; jobs usan URL publica HTTPS y fallan con motivo claro si el archivo no es accesible | Validar envio real en produccion con dominio final y certificado vigente. |
| MSG-003 | Mensajeria | Envio por plantilla | Operativo | P0 | Plantillas sincronizadas con Meta, estado visible, no aprobadas bloqueadas y variables exactas validadas antes de encolar | Probar envio real con plantilla aprobada en Meta. |
| MSG-004 | Mensajeria | Reintento de mensajes fallidos | Operativo | P1 | Ruta, prueba y accion visible desde detalle de mensaje y panel `/failures` | Agregar limite/configuracion de reintentos si el volumen lo exige. |
| MSG-005 | Mensajeria | Ventana 24h de WhatsApp | Operativo | P0 | Texto libre/multimedia bloqueado fuera de ventana; motivos visibles; aviso preventivo; plantillas permitidas; pruebas automatizadas | Revisar copy operativo con usuarios finales. |
| CONV-001 | Conversaciones | Listado de conversaciones | Operativo | P0 | Inbox redisenado como panel de chats con filtros compactos, no leidos, auto-refresh, sonido opcional de nuevos mensajes y pruebas automatizadas | Validar con operadores en produccion y ajustar densidad si aumenta el volumen. |
| CONV-002 | Conversaciones | Timeline entrante/saliente | Operativo | P0 | Vista tipo chat integrada en `/conversations?conversation=...` con lista lateral, cabecera de contacto, burbujas, fechas, estados, errores y compositor inferior | Agregar scroll automatico al ultimo mensaje si el volumen lo exige. |
| CONV-003 | Conversaciones | Asignacion a operador | MVP | P1 | Select, accion "Asignar a mi" y pruebas en `NoiaChatMvpTest` | Crear filtros por equipo y reglas operativas de reasignacion. |
| CONV-004 | Conversaciones | Estados abierta/pendiente/resuelta/cerrada | MVP | P1 | Select existe | Definir reglas operativas y automatizaciones. |
| CONV-005 | Conversaciones | Auto-refresh o tiempo real | MVP | P1 | Polling simple del inbox con endpoint parcial y `NoiaChatMvpTest` | Evaluar tiempo real con Echo/Reverb si el volumen lo exige. |
| CONV-006 | Conversaciones | Nuevo chat como flujo principal de envio | MVP | P1 | `/messages` lleva a `/conversations?new=1`; `POST /conversations/start` crea o reutiliza conversacion por contacto/canal; documentado en `docs/conversaciones-flujo-operativo.md` | Mejorar selector con busqueda/autocomplete cuando crezca el volumen de contactos. |
| UI-001 | Interfaz | Menu lateral colapsable con iconos | Operativo | P1 | Layout principal permite contraer/expandir, recuerda preferencia local e identifica opciones por icono | Validar usabilidad con usuarios y reemplazar SVG inline por libreria de iconos si se adopta una. |
| AUDIT-001 | Auditoria | Registro de acciones principales | MVP | P0 | `/audit-logs` muestra contactos y acciones | Agregar auditoria de mas eventos operativos. |
| AUDIT-002 | Auditoria | Filtros de auditoria | MVP | P1 | Modal de filtros implementado con sede disponible para alcance de empresa | Agregar exportacion CSV/Excel. |
| AUDIT-003 | Auditoria | Detalle de cambios old/new | MVP | P1 | Vista de detalle `/audit-logs/{id}` muestra contexto, old/new comparado y JSON tecnico respetando empresa/sede | Agregar exportacion CSV/Excel. |
| SETTINGS-001 | Configuracion | Gestion de canal WhatsApp | MVP | P1 | Vista settings guarda credenciales por canal sin exponer secretos, conserva secretos vacios y valida HTTPS/IDs Meta; envios/webhooks/sync las consumen por empresa/sede | Agregar rotacion asistida y registro de responsable/fecha de expiracion. |
| SETTINGS-002 | Plantillas | CRUD/versionado de plantillas | MVP | P1 | Implementado en settings | Sincronizar con Meta y estado de aprobacion. |
| REPORT-001 | Dashboard | Contadores basicos | MVP | P2 | Dashboard con totales y filtro de sede para alcance de empresa | Agregar tendencias, tasa respuesta y filtros avanzados. |
| REPORT-002 | Reportes | Exportacion de datos | MVP | P2 | CSV de auditoria, contactos, mensajes y conversaciones con filtros y alcance de empresa/sede | Agregar XLSX y programacion de reportes si el negocio lo requiere. |
| DEPLOY-001 | Deploy | GitHub Actions a droplet | Operativo | P0 | Workflow usa secretos para host, usuario, puerto y llave; debug retirado | Verificar nuevo run de Actions con secretos configurados. |
| DEPLOY-002 | Deploy | Worker permanente | Operativo | P0 | Configuracion Supervisor versionada, deploy reinicia workers y manual operativo creado | Verificar `supervisorctl status noiachat-worker:*` en produccion. |
| DEPLOY-003 | Deploy | Backups | Operativo | P0 | Comando `noiachat:backup`, cron versionado, deploy instala cron y manual de restauracion | Sincronizar backups a almacenamiento externo. |
| DEPLOY-004 | Deploy | Monitoreo | MVP | P1 | Panel `/health` y comando `noiachat:health-check` revisan jobs fallidos, cola pendiente, disco, webhook, backups y errores recientes | Conectar alertas externas por cron/Slack/email si el entorno productivo lo requiere. |
| DOC-001 | Documentacion | README | Operativo | P1 | README actualizado | Mantener con cada release. |
| DOC-002 | Documentacion | Changelog | Operativo | P1 | CHANGELOG actualizado | Crear version publica inicial. |
| DOC-003 | Documentacion | Manual WhatsApp | Operativo | P1 | `docs/integracion-whatsapp.md` | Actualizar si cambia Meta o flujo productivo. |
| DOC-004 | Documentacion | Gestion documental de funcionalidades | Operativo | P1 | `docs/gestion-documental.md`, `docs/funcionalidades.md` y `docs/plantilla-funcionalidad.md` | Revisar semanalmente y cerrar estados. |

## Backlog priorizado

### P0 - Critico

- Probar envio multimedia real con URL publica HTTPS.
- Ejecutar checklist multiempresa en staging/copia productiva antes de operar varias empresas reales.
- Mantener pruebas de aislamiento de datos por empresa/sede en queries, policies, webhooks, jobs y exportaciones.

### P1 - Importante

- CRUD de usuarios y roles.
- Importacion masiva de contactos.
- Deduplicacion y fusion de contactos.
- Vista tipo WhatsApp para conversaciones.
- Sincronizacion de plantillas con Meta.
- Exportacion de auditoria.
- Mejoras UX de administracion de empresas, sedes y membresias.
- Validacion productiva de despliegue multiempresa.
- Reglas de upgrade y conversion comercial del plan basico de prueba.
- Validar tarifas, copy comercial y flujo de upgrade antes de produccion publica.
- Crear configuracion comercial de WhatsApp por empresa/sede (`WA-COM-001` a `WA-COM-010`) separada de `/settings` tecnico.

### P2 - Mejora

- Dashboard avanzado.
- Reportes por operador/contacto.
- Validacion productiva de 2FA con SMTP real.
- Monitor de salud WhatsApp.
- Alertas proactivas por errores repetidos.

## Criterios para marcar una funcionalidad como Operativo

Una funcionalidad solo debe pasar a `Operativo` si cumple:

- Tiene flujo principal validado.
- Tiene permisos/roles revisados.
- Tiene evidencia de prueba manual o automatizada.
- No depende de datos quemados o credenciales temporales.
- Tiene manejo de errores basico.
- Esta documentada si requiere operacion recurrente.

## Historial de revisiones

| Fecha | Cambio | Responsable |
| --- | --- | --- |
| 2026-06-15 | Analisis de brecha multiempresa/multisede y plan de actividades `MT-00` a `MT-10`. | Equipo NoiaChat |
| 2026-06-15 | Inicio de base multiempresa con empresas, sedes, membresias, seeder por defecto y pruebas iniciales. | Equipo NoiaChat |
| 2026-06-15 | Migracion base de datos operativos a empresa/sede por defecto con columnas tenant y pruebas de esquema/semillas. | Equipo NoiaChat |
| 2026-06-15 | Contexto activo de empresa/sede por request con seleccion persistida en sesion y compatibilidad para usuarios legacy. | Equipo NoiaChat |
| 2026-06-16 | MT-05 implementado como base: canales WhatsApp, credenciales, webhooks y plantillas resueltos por empresa/sede. | Equipo NoiaChat |
| 2026-06-16 | MT-06 implementado como base: duplicados y fusiones de contactos operan dentro de empresa/canal. | Equipo NoiaChat |
| 2026-06-16 | MT-07 implementado como base: inbox y asignacion de conversaciones respetan empresa/sede. | Equipo NoiaChat |
| 2026-06-16 | MT-08 implementado como base: auditoria y dashboard filtran por empresa/sede respetando permisos. | Equipo NoiaChat |
| 2026-06-16 | MT-09 y MT-10 implementados como base: administracion UI global/tenant activo, checklist productivo, backup, pruebas de aislamiento y rollback. | Equipo NoiaChat |
| 2026-06-16 | Limpieza documental de matriz multiempresa MT-00 a MT-10 para retirar siguientes acciones obsoletas. | Equipo NoiaChat |
| 2026-06-16 | P1-16 implementado: panel `/failures` para mensajes reintentables, errores de proveedor y jobs fallidos. | Equipo NoiaChat |
| 2026-06-16 | AUDIT-003 implementado: detalle de auditoria con comparacion old/new, contexto y permisos multiempresa. | Equipo NoiaChat |
| 2026-06-16 | REPORT-002 implementado como MVP: exportaciones CSV filtradas para auditoria, contactos, mensajes y conversaciones. | Equipo NoiaChat |
| 2026-06-16 | DEPLOY-004 implementado como MVP: monitor de salud `/health` y comando `noiachat:health-check`. | Equipo NoiaChat |
| 2026-06-17 | SETTINGS-001 endurecido: secretos de canal enmascarados, preservacion de valores existentes y validaciones HTTPS/Meta ID. | Equipo NoiaChat |
| 2026-06-17 | WA-007 implementado: metadata de rotacion de token WhatsApp y alertas en monitor de salud. | Equipo NoiaChat |
| 2026-06-17 | AUTH-004 implementado como MVP: 2FA OTP por email obligatorio para roles administrativos. | Equipo NoiaChat |
| 2026-06-17 | ONBOARD-001 implementado como MVP: registro publico crea empresa, sede inicial, membresia `company_admin` y trial basico configurable. | Equipo NoiaChat |
| 2026-06-17 | Plan de trabajo `BILLING-001` a `BILLING-008` documentado para planes, suscripciones, features y limites por empresa. | Equipo NoiaChat |
| 2026-06-17 | BILLING-001 y BILLING-002 implementados como MVP: planes, suscripciones y features por plan persistidos en base de datos. | Equipo NoiaChat |
| 2026-06-17 | BILLING-003 implementado como MVP: servicio central para evaluar suscripcion, features, limites y trial vigente. | Equipo NoiaChat |
| 2026-06-17 | BILLING-004 implementado como MVP: middleware `feature` aplicado a rutas administrativas iniciales. | Equipo NoiaChat |
| 2026-06-18 | BILLING-005 implementado como MVP: limites por plan aplicados a usuarios, sedes, contactos y activacion de canales WhatsApp. | Equipo NoiaChat |
| 2026-06-18 | BILLING-007 implementado como MVP: comando de vencimiento de trial, auditoria, bloqueo operativo y avisos en panel. | Equipo NoiaChat |
| 2026-06-18 | BILLING-006 implementado como MVP: panel `/billing` para plan, limites, features y administracion manual de suscripciones. | Equipo NoiaChat |
| 2026-06-18 | BILLING-008 implementado como MVP: matriz comercial de planes/features, estados de suscripcion y checklist operativo documentados. | Equipo NoiaChat |
| 2026-06-18 | BILLING-PRO-001 y BILLING-PRO-002 implementados como MVP: catalogo comercial en `/billing` y solicitudes internas de upgrade auditadas. | Equipo NoiaChat |
| 2026-06-19 | ONBOARD-001 ajustado: login enlaza al registro trial, `/register` reorganizado, campos de contrasena con mostrar/ocultar, validaciones en espanol y manual `docs/onboarding-registro-trial.md`. | Equipo NoiaChat |
| 2026-06-19 | ONBOARD-001 endurecido: usuario comercial de trial ve menu operativo y no accede a modulos tecnicos de plataforma por menu ni URL directa. | Equipo NoiaChat |
| 2026-06-19 | AUTH-003 endurecido: usuarios comerciales no ven ni editan administradores globales en Usuarios/Membresias y no pueden asignar roles globales. | Equipo NoiaChat |
| 2026-06-19 | WA-COM-001 a WA-COM-010 documentados: configuracion comercial de WhatsApp por empresa/sede separada de configuracion tecnica de plataforma. | Equipo NoiaChat |
| 2026-06-19 | WA-COM-002 iniciado: permiso `whatsapp.integration.manage` creado para configuracion WhatsApp empresarial sin conceder acceso tecnico de plataforma. | Equipo NoiaChat |
| 2026-06-19 | WA-COM-001 implementado como base: pantalla comercial `/integrations/whatsapp` lista canales WhatsApp por empresa/sede y no depende de `/settings`. | Equipo NoiaChat |
| 2026-06-19 | WA-COM-003/004 implementados como MVP: alta/edicion comercial de canales WhatsApp con credenciales Meta seguras, limites de plan y auditoria. | Equipo NoiaChat |
| 2026-06-19 | WA-COM-005/006 implementados como MVP: prueba de conexion Meta y sincronizacion comercial de plantillas por canal WhatsApp empresarial. | Equipo NoiaChat |
| 2026-06-19 | WA-COM-007/008 implementados como MVP: estado operativo por canal y checklist comercial de configuracion Meta en pantalla. | Equipo NoiaChat |
| 2026-06-19 | WA-COM-009/010 implementados como MVP: pruebas de aislamiento comercial y comando de validacion real de canal WhatsApp Meta. | Equipo NoiaChat |
| 2026-06-22 | CONV-006 implementado como MVP: el inicio de nuevos envios pasa a `/conversations` mediante Nuevo chat, reutilizando o creando conversaciones por contacto/canal. | Equipo NoiaChat |
| 2026-06-15 | Redisenio operativo de conversaciones, carga del chat activo en `/conversations` y menu lateral colapsable con iconos. | Equipo NoiaChat |
| 2026-06-15 | Indicadores de lectura en mensajes salientes y sonido opcional para mensajes entrantes nuevos. | Equipo NoiaChat |
| 2026-06-08 | Creacion de matriz inicial de funcionalidades y backlog priorizado. | Equipo NoiaChat |
