# Analisis multiempresa y multisede

Ultima actualizacion: 2026-06-16

## Objetivo

NoiaChat debe evolucionar de una aplicacion operativa para una sola empresa a una plataforma capaz de atender multiples empresas, cada una con una o mas sedes, usuarios, canales, contactos, conversaciones, plantillas, reportes y reglas de acceso independientes.

El objetivo principal es evitar mezcla de datos entre empresas y permitir operacion por sede sin perder la trazabilidad actual de WhatsApp, consentimiento, auditoria y conversaciones.

## Estado actual

La aplicacion ya tiene una base funcional importante:

- Autenticacion con usuarios activos/inactivos.
- Roles `admin`, `operator` y `auditor`.
- Gestion de usuarios desde el panel.
- Contactos, canales, consentimientos, blacklist, conversaciones, mensajes, plantillas, adjuntos, logs de proveedor y auditoria.
- Conversaciones tipo WhatsApp en una sola vista operativa.
- Asignacion de conversaciones a operadores.
- Integracion WhatsApp Cloud API con mensajes entrantes, salientes, estados, plantillas y errores visibles.
- Worker de colas, deploy y backups documentados.

La base multiempresa ya esta en marcha:

- Existen empresas, sedes y membresias.
- Las tablas operativas principales tienen `company_id` y `branch_id`.
- El request autenticado resuelve empresa/sede activa desde la membresia del usuario.
- Listados y policies iniciales aplican alcance por empresa/sede activa.
- Los canales WhatsApp pertenecen a empresa/sede.
- Los envios WhatsApp, la sincronizacion de plantillas, los tokens, los secretos de firma y los numeros se resuelven desde el canal cuando existen, con fallback a `.env` para compatibilidad de la instalacion actual.
- El webhook de WhatsApp resuelve el canal por `metadata.phone_number_id` recibido desde Meta y crea contactos, conversaciones, inbound, logs, opt-outs y blacklist dentro de la empresa/sede de ese canal.

Brechas todavia abiertas:

- Los roles finales multiempresa (`super_admin`, `company_admin`, `branch_manager`, `operator`, `auditor`) ya estan definidos y tienen base de autorizacion en gates/policies.
- La administracion de empresas/sedes/membresias desde panel aun no existe.
- Importacion, deduplicacion y fusion requieren reglas finales por empresa/canal.
- La asignacion avanzada de conversaciones por sede ya tiene base: el inbox puede filtrarse por sede para usuarios con alcance de empresa y la asignacion valida membresias compatibles con la sede.
- Reportes y auditoria requieren filtros/exportaciones multiempresa mas completos.

## Riesgo si se opera con varias empresas como esta hoy

No se recomienda usar la misma instalacion para varias empresas reales sin esta adaptacion, porque puede ocurrir:

- Un operador de una empresa podria ver conversaciones o contactos de otra.
- Un telefono duplicado en dos empresas chocaria por restricciones globales como `contact_channels.channel_id + phone`.
- Una plantilla, canal o token de WhatsApp podria usarse para el cliente equivocado.
- La auditoria no podria demostrar claramente que un dato pertenece a una empresa/sede.
- Los reportes mezclarian metricas operativas.
- Un webhook entrante podria asociar el mensaje al contacto incorrecto si el numero existe en mas de una empresa.

## Modelo objetivo

### Entidades nuevas

`companies`

- Representa la empresa cliente o unidad legal principal.
- Campos sugeridos: `id`, `name`, `legal_name`, `tax_id`, `status`, `timezone`, `settings`, timestamps.
- Una empresa puede tener multiples sedes, usuarios, canales, contactos y reportes.

`branches`

- Representa una sede, punto operativo o sucursal.
- Campos sugeridos: `id`, `company_id`, `name`, `code`, `city`, `address`, `timezone`, `is_active`, timestamps.
- Una sede pertenece a una empresa.

`company_user` o `memberships`

- Define que usuario pertenece a que empresa y, opcionalmente, a que sedes.
- Campos sugeridos: `user_id`, `company_id`, `branch_id nullable`, `role_id`, `is_default`, timestamps.
- Permite que un usuario tenga permisos diferentes por empresa/sede.

### Entidades existentes que deben quedar segmentadas

Deben incluir `company_id`:

- `users` o membresias de usuario.
- `channels`.
- `contacts`.
- `contact_channels`.
- `contact_consents`.
- `contact_blacklist`.
- `conversations`.
- `message_templates`.
- `template_versions` por relacion con plantilla.
- `messages`.
- `inbound_messages`.
- `media_files`.
- `provider_logs`.
- `audit_logs`.
- `opt_out_requests`.

Deben incluir `branch_id` cuando aplique operativamente:

- `contacts`, si un contacto pertenece o se gestiona desde una sede principal.
- `conversations`, para enrutar atencion por sede.
- `messages`, por herencia de la conversacion.
- `users/memberships`, para saber en que sedes puede trabajar un usuario.
- `audit_logs`, para trazabilidad por sede cuando exista contexto.

## Reglas de negocio multiempresa/multisede

### Aislamiento de datos

- Todo dato operativo debe pertenecer a una empresa.
- Ninguna consulta de panel debe devolver datos de otra empresa.
- Toda creacion desde UI debe tomar la empresa activa del usuario, no del formulario libre.
- Toda creacion automatica desde webhook debe resolver empresa por canal/numero de WhatsApp.
- Los identificadores externos de Meta deben mapearse a un canal de una empresa especifica.

### Usuarios y roles

- Un usuario puede pertenecer a una o varias empresas.
- Un usuario puede tener permisos por empresa y por sede.
- Regla base aprobada: el rol define que puede hacer el usuario y la membresia define donde puede hacerlo.
- Un `super_admin` interno puede administrar todas las empresas.
- Un `company_admin` administra solo su empresa.
- Un `branch_manager` administra solo sus sedes asignadas.
- Un `operator` atiende conversaciones de sus sedes o las que se le asignen segun regla.
- Un `auditor` consulta solo la empresa/sede permitida.
- `branch_id = null` en una membresia significa alcance de empresa completa.
- `branch_id` con valor significa alcance limitado a esa sede.
- El rol global `admin` actual se mantiene por compatibilidad y equivale operativamente a `company_admin` en la empresa por defecto hasta migrar gates finales.

#### Alcances aprobados por rol

| Rol | Alcance | Permisos aprobados |
| --- | --- | --- |
| `super_admin` | Todas las empresas y sedes | Crear/editar empresas, sedes, canales, usuarios, membresias, settings, auditoria y reportes globales. Puede cambiar a cualquier contexto. |
| `company_admin` | Empresa completa o sedes asignadas segun membresia | Administra sedes, usuarios, membresias, canales, plantillas, contactos, conversaciones, auditoria y reportes de su empresa. No accede a otras empresas. |
| `branch_manager` | Una o varias sedes dentro de una empresa | Supervisa contactos, conversaciones, operadores, reportes y auditoria de sus sedes. No administra credenciales globales ni membresias de toda la empresa. |
| `operator` | Sedes asignadas | Atiende contactos y conversaciones de su alcance, responde mensajes, se asigna conversaciones y cambia estados operativos. |
| `auditor` | Empresa o sedes asignadas | Solo lectura de contactos, conversaciones, mensajes, auditoria y reportes de su alcance. No modifica ni envia mensajes. |

#### Matriz funcional aprobada

| Accion | `super_admin` | `company_admin` | `branch_manager` | `operator` | `auditor` |
| --- | --- | --- | --- | --- | --- |
| Crear empresas | Si | No | No | No | No |
| Editar empresa propia | Si | Si | No | No | No |
| Crear/editar sedes | Si | Si | Limitado a sus sedes si se habilita | No | No |
| Gestionar membresias | Si | Si, dentro de su empresa | Opcional, solo sus sedes | No | No |
| Configurar canales | Si | Si, dentro de su empresa | No | No | No |
| Ver contactos | Si | Si, empresa | Si, sedes | Si, sedes | Si, lectura |
| Importar contactos | Si | Si, empresa | Opcional, sedes | Opcional segun operacion | No |
| Fusionar contactos | Si | Si, empresa | No | No | No |
| Ver conversaciones | Si | Si, empresa | Si, sedes | Si, sedes | Si, lectura |
| Asignar conversaciones | Si | Si, empresa | Si, sedes | A si mismo o equipo permitido | No |
| Enviar mensajes | Opcional soporte | Si | Si | Si | No |
| Ver auditoria | Si, global | Si, empresa | Si, sedes | No | Si, alcance |
| Exportar reportes | Si, global | Si, empresa | Si, sedes | No | Si, alcance |

### Sedes

- Una empresa debe tener al menos una sede activa.
- Una conversacion puede pertenecer a una sede por asignacion manual, canal, contacto o regla de enrutamiento.
- Si no se puede determinar sede, la conversacion debe quedar en una bandeja "sin sede" visible para administradores de la empresa.
- Los filtros operativos deben permitir empresa, sede, estado, asignado y no leidos.
- `company_admin` y `super_admin` pueden ver conversaciones sin sede dentro de su alcance.
- `branch_manager`, `operator` y `auditor` solo ven conversaciones de sus sedes asignadas.
- Un usuario con alcance de empresa completa puede filtrar por sede; un usuario con alcance de sede no puede quitar ese alcance.

### Contactos

- Un mismo telefono puede existir en empresas diferentes.
- Dentro de una misma empresa/canal no debe existir duplicado operativo sin fusion controlada.
- La deduplicacion y fusion deben operar dentro de la misma empresa por defecto.
- Fusionar contactos entre empresas debe estar prohibido salvo tarea tecnica controlada.

### WhatsApp y canales

- Cada canal WhatsApp debe pertenecer a una empresa.
- Un canal puede asociarse a una sede o ser compartido por varias sedes de la misma empresa.
- Los tokens, phone number IDs y WhatsApp Business Account IDs no deben ser globales si hay varias empresas.
- La sincronizacion de plantillas debe ejecutarse por canal/empresa.
- Los webhooks deben resolver el canal usando el `phone_number_id` recibido de Meta.
- Una empresa puede tener varios numeros/canales WhatsApp.
- Criterio aprobado de sede para mensajes entrantes: usar primero la sede del canal; si no existe, usar la sede del contacto; si no existe, dejar la conversacion sin sede dentro de la empresa.

### Auditoria

- Todo evento auditado debe registrar `company_id`.
- Cuando aplique, tambien debe registrar `branch_id`.
- Un auditor solo ve eventos de sus empresas/sedes.
- Cambios de membresia, roles, empresa, sede, canal y credenciales deben auditarse.

### Reportes

- Todo reporte debe poder filtrarse por empresa y sede.
- Los reportes globales solo deben estar disponibles para `super_admin`.
- Las exportaciones deben respetar el mismo alcance que la vista.

## Decisiones tecnicas recomendadas

### Enfoque recomendado: single database con `company_id`

Para el estado actual del proyecto, conviene usar una sola base de datos con columna `company_id` en tablas operativas.

Ventajas:

- Menor complejidad de despliegue.
- Mantiene el deploy y backups actuales.
- Facilita reportes globales internos.
- Permite migrar incrementalmente.

Riesgo:

- Requiere disciplina fuerte en scopes, policies y pruebas para evitar fugas de datos.

### Alternativas no recomendadas por ahora

Base de datos por empresa:

- Mas aislamiento, pero mucho mas compleja para deploy, backups, migraciones, reportes y soporte.

Subdominio por empresa:

- Puede agregarse despues, pero no resuelve por si solo el aislamiento de datos.

## Plan de actividades

### MT-00 Definicion funcional

Prioridad: P0

Objetivo: cerrar reglas antes de tocar migraciones.

Tareas:

- Definir si el sistema tendra `super_admin` interno.
- Definir roles finales: `super_admin`, `company_admin`, `branch_manager`, `operator`, `auditor`.
- Definir si un contacto puede pertenecer a varias sedes o solo a una sede principal.
- Definir si una empresa puede tener varios numeros WhatsApp.
- Definir criterio de enrutamiento de conversacion a sede.

Criterios de aceptacion:

- Documento de reglas aprobado.
- Lista de roles y alcances validada.
- No quedan decisiones bloqueantes para migrar datos.

Estado 2026-06-16:

- Aprobado funcionalmente.
- Roles finales aprobados: `super_admin`, `company_admin`, `branch_manager`, `operator` y `auditor`.
- Regla de permisos aprobada: el rol define que puede hacer el usuario; la membresia define donde puede hacerlo.
- Contacto y conversacion usan una sede principal operativa; futuras reglas pueden ampliar relacion multisedes sin bloquear la base actual.
- Una empresa puede tener varios numeros/canales WhatsApp.
- Enrutamiento entrante aprobado: sede del canal, luego sede del contacto, luego bandeja sin sede dentro de la empresa.
- Implementado en base de autorizacion: seed de roles finales, gates tenant-aware, policies operativas por membresia activa y compatibilidad con roles legacy `admin`, `operator` y `auditor`.

### MT-01 Modelo base de empresas y sedes

Prioridad: P0

Objetivo: crear la estructura de empresas, sedes y membresias.

Tareas:

- Crear migraciones para `companies`, `branches` y `memberships`.
- Crear modelos, relaciones y seeders base.
- Crear empresa/sede por defecto para migrar datos existentes.
- Asociar el usuario admin actual a la empresa/sede por defecto.

Criterios de aceptacion:

- La aplicacion sigue entrando con el admin actual.
- Existe empresa y sede por defecto.
- Usuarios tienen membresia valida.

### MT-02 Migracion de datos existentes

Prioridad: P0

Objetivo: asignar todos los datos actuales a una empresa/sede por defecto sin perdida.

Tareas:

- Agregar `company_id` a tablas operativas.
- Agregar `branch_id` donde aplique.
- Poblar datos existentes con empresa/sede por defecto.
- Ajustar indices unicos globales para que incluyan `company_id`.
- Verificar contactos, conversaciones, mensajes, plantillas y auditoria.

Criterios de aceptacion:

- Migraciones corren en local y produccion.
- No se pierden conversaciones ni mensajes.
- Restricciones de duplicados funcionan por empresa.

### MT-03 Contexto de empresa activa

Prioridad: P0

Objetivo: que cada request opere dentro de una empresa y sede permitida.

Tareas:

- Crear servicio `TenantContext` o equivalente.
- Resolver empresa activa desde membresia del usuario.
- Permitir cambio de empresa/sede si el usuario tiene acceso.
- Persistir empresa/sede activa en sesion.
- Exponer selectores compactos en UI para usuarios multiempresa.

Criterios de aceptacion:

- Un usuario con una sola empresa entra directo a su contexto.
- Un usuario con varias empresas puede cambiar contexto.
- Las rutas operativas conocen empresa activa.

### MT-04 Policies y scopes de aislamiento

Prioridad: P0

Objetivo: impedir fugas de datos entre empresas/sedes.

Tareas:

- Ajustar policies de contactos, conversaciones, mensajes, usuarios, auditoria y settings.
- Agregar scopes por `company_id`.
- Revisar repositorios y queries con filtros obligatorios.
- Agregar pruebas de aislamiento entre dos empresas.
- Bloquear asignaciones a usuarios fuera de la empresa/sede.

Criterios de aceptacion:

- Un operador de empresa A no ve datos de empresa B.
- Un auditor de sede A no ve sede B si no tiene permiso.
- Las pruebas cubren intentos de acceso cruzado.

Estado 2026-06-15:

- Implementado como base.
- Se agrego scope `forTenantContext` y verificacion `belongsToActiveTenant` en modelos tenant.
- Repositorios de contactos, canales, conversaciones, mensajes, consentimientos, blacklist y auditoria filtran por empresa/sede activa cuando existe contexto autenticado.
- Dashboard, settings, usuarios y selectores operativos consultan solo datos del tenant activo.
- Policies de contactos, conversaciones, mensajes y auditoria combinan rol con pertenencia a empresa/sede.
- Pruebas cubren listados por empresa, bloqueo de detalle por ID ajeno, aislamiento por sede y conteos de dashboard.

### MT-05 Canales WhatsApp por empresa/sede

Prioridad: P0

Objetivo: que WhatsApp opere por empresa y no globalmente.

Tareas:

- Asociar `channels` a `company_id` y opcionalmente `branch_id`.
- Mover credenciales WhatsApp desde `.env` global a configuracion por canal o secreto por empresa.
- Resolver canal entrante por `phone_number_id`.
- Ajustar envio para usar credenciales del canal de la conversacion.
- Ajustar sincronizacion de plantillas por canal.

Criterios de aceptacion:

- Dos empresas pueden tener canales WhatsApp distintos.
- Un webhook entrante se asigna a la empresa correcta.
- Un envio saliente usa el token/numero correcto.

Estado 2026-06-16:

- Implementado como base.
- `WhatsAppChannelConfig` resuelve credenciales por canal con fallback a `config/services.php` para compatibilidad.
- El provider de WhatsApp usa el canal del mensaje para elegir `access_token`, `phone_number_id` y `api_base_url`.
- La verificacion de webhook acepta tokens configurados en canales activos y la firma HMAC valida contra secretos de canal.
- El procesamiento entrante resuelve canal por `metadata.phone_number_id` y escribe contactos, conversaciones, inbound, logs, opt-outs y blacklist con `company_id`/`branch_id` del canal.
- La sincronizacion de plantillas usa `business_account_id` y token del canal.
- Pruebas cubren inbound por `phone_number_id` entre empresas y envio saliente con credenciales del canal.

### MT-06 Contactos, importacion y fusion por empresa

Prioridad: P1

Objetivo: adaptar la gestion de contactos al nuevo alcance.

Tareas:

- Filtrar contactos por empresa/sede.
- Ajustar importacion para usar empresa/sede activa.
- Cambiar deteccion de duplicados a empresa/canal.
- Bloquear fusion entre empresas.
- Mostrar empresa/sede en detalle y auditoria.

Criterios de aceptacion:

- Un mismo telefono puede existir en empresas diferentes.
- El importador no mezcla contactos entre empresas.
- La fusion conserva aislamiento.

Estado 2026-06-16:

- Implementado como base.
- La importacion usa el contexto de empresa/sede activa y no bloquea un telefono que ya exista en otra empresa.
- La edicion de contactos valida duplicados activos dentro del canal WhatsApp del tenant antes de guardar.
- La fusion valida que origen y destino pertenezcan a la misma empresa y la ruta bloquea origenes fuera del tenant activo.
- Al mover canales de contacto durante una fusion, se evitan colisiones de telefono activo en el mismo canal/empresa.
- Pruebas cubren importacion con telefono repetido en otra empresa, bloqueo de fusion cross-company y bloqueo de edicion con telefono duplicado del canal activo.

### MT-07 Conversaciones por sede y reglas de asignacion

Prioridad: P1

Objetivo: que la bandeja diaria funcione por equipos y sedes.

Tareas:

- Filtrar inbox por empresa/sede activa.
- Agregar filtro de sede cuando el usuario tenga mas de una.
- Enrutar conversaciones entrantes a sede por canal, contacto o regla.
- Permitir reasignar sede con auditoria.
- Validar que los operadores solo se asignen conversaciones permitidas.

Criterios de aceptacion:

- El inbox muestra solo conversaciones del alcance del usuario.
- Una conversacion puede moverse de sede con trazabilidad.
- La experiencia tipo WhatsApp se mantiene compacta.

Estado 2026-06-16:

- Implementado como base.
- El repositorio de conversaciones soporta filtro por `branch_id` encima del scope de tenant activo.
- Usuarios con membresia de empresa completa pueden filtrar el inbox por sede activa.
- Usuarios con membresia de sede siguen viendo solo su sede por `forTenantContext`.
- La asignacion valida que el usuario asignado tenga membresia activa de la empresa y de la sede de la conversacion, o membresia de empresa completa.
- La accion "Asignar a mi" reutiliza la misma validacion de sede.
- Pruebas cubren filtro de inbox por sede y rechazo de asignacion a operador de otra sede.

### MT-08 Auditoria y reportes multiempresa

Prioridad: P1

Objetivo: que administracion y cumplimiento tengan trazabilidad por alcance.

Tareas:

- Agregar empresa/sede a auditoria.
- Filtrar auditoria por empresa/sede.
- Ajustar dashboard operativo.
- Ajustar exportaciones futuras para respetar alcance.

Criterios de aceptacion:

- Auditoria muestra empresa/sede.
- Reportes de una empresa no incluyen datos de otra.

Estado 2026-06-16:

- Implementado como base.
- Auditoria usa el scope de tenant activo y acepta filtro de sede solo cuando la membresia activa tiene alcance de empresa.
- El resumen por modulo de auditoria aplica el mismo filtro de sede.
- El filtro de usuarios de auditoria se limita a usuarios con membresia activa dentro del alcance.
- Dashboard aplica tenant activo a contactos, mensajes, inbound y blacklist.
- Usuarios con alcance de empresa pueden filtrar metricas por sede; usuarios de sede quedan limitados por su contexto.
- Pruebas cubren dashboard filtrado por sede y auditoria filtrada por sede.

### MT-09 Administracion UI

Prioridad: P1

Objetivo: administrar empresas, sedes, usuarios y permisos sin tocar base de datos.

Tareas:

- Crear CRUD de empresas para `super_admin`.
- Crear CRUD de sedes para `super_admin` y `company_admin`.
- Crear pantalla de membresias de usuario.
- Permitir asignar usuarios a sedes y roles por empresa.
- Mostrar selector de contexto solo cuando aplique.

Criterios de aceptacion:

- Se puede crear una empresa nueva desde panel.
- Se puede crear una sede y asociar operadores.
- Un usuario nuevo recibe acceso solo al alcance definido.

Estado 2026-06-16:

- Implementado como base para administracion global y administracion del tenant activo.
- `super_admin` puede crear y editar empresas desde el panel, incluyendo una sede inicial opcional.
- Se agrego pantalla `Empresa` en el panel para editar datos de la empresa activa, crear/editar sedes y crear/editar membresias.
- La administracion de sedes y membresias valida siempre `company_id` contra la empresa activa para evitar cambios cross-company.
- Al asignar membresias se sincronizan los roles globales del usuario desde sus membresias activas, manteniendo compatibilidad con los gates actuales.
- Los cambios de empresa, sede y membresia se registran en auditoria con modulo `tenancy`.
- `company_admin` no puede usar las rutas globales de empresas.

### MT-10 Pruebas, migracion productiva y despliegue

Prioridad: P0

Objetivo: subir el cambio sin perder datos ni dejar accesos cruzados.

Tareas:

- Crear pruebas automatizadas de aislamiento.
- Crear backup antes de migracion productiva.
- Probar migracion en una copia de produccion.
- Documentar rollback.
- Desplegar en ventana controlada.
- Validar login, inbox, webhook, envio, plantillas y auditoria despues del deploy.

Criterios de aceptacion:

- Pruebas completas pasan.
- Backup verificado antes del deploy.
- Produccion conserva datos actuales en empresa/sede por defecto.
- No hay errores 500 en rutas principales.

Estado 2026-06-16:

- Implementado como checklist operativo en `docs/multiempresa-checklist-produccion.md`.
- La suite automatizada cubre aislamiento entre empresas/sedes, webhooks por canal, credenciales por canal, filtros de auditoria/dashboard y administracion de sedes/membresias.
- El comando `noiachat:backup` genera `manifest.json` con metadatos de validacion productiva y tiene pruebas de copia de base, storage y manifiesto.
- El checklist exige backup antes de migrar, validacion en copia/staging, comandos de migracion productiva, verificacion posterior y rollback documentado.
- Ultima verificacion local: `php artisan test`.

## Orden recomendado

1. MT-00 Definicion funcional.
2. MT-01 Modelo base de empresas y sedes.
3. MT-02 Migracion de datos existentes.
4. MT-03 Contexto de empresa activa.
5. MT-04 Policies y scopes de aislamiento.
6. MT-05 Canales WhatsApp por empresa/sede.
7. MT-06 Contactos, importacion y fusion por empresa.
8. MT-07 Conversaciones por sede y reglas de asignacion.
9. MT-08 Auditoria y reportes multiempresa.
10. MT-09 Administracion UI.
11. MT-10 Pruebas, migracion productiva y despliegue.

## Recomendacion ejecutiva

Antes de seguir agregando muchas funciones operativas, conviene priorizar multiempresa/multisede si el producto se va a usar con mas de una empresa real. Es una base arquitectonica: mientras mas datos y funcionalidades existan sin `company_id`, mas costosa y riesgosa sera la migracion.

La primera implementacion debe crear una empresa y sede por defecto para que todo lo actual siga funcionando igual. Luego se activan las reglas de aislamiento, y finalmente se agregan pantallas de administracion multiempresa.

## Avance de implementacion

### 2026-06-15

- `MT-01` iniciado como base tecnica.
- Se agregaron las tablas `companies`, `branches` y `memberships`.
- Se agregaron modelos y relaciones para empresa, sede, membresia, usuario y rol.
- El seeder crea una empresa `default`, una sede `principal` y una membresia activa para el administrador inicial.
- Se agregaron pruebas iniciales en `tests/Feature/TenancyBaseTest.php`.
- En ese momento quedo como siguiente paso `MT-02 Migracion de datos existentes`, agregando `company_id` y `branch_id` a las tablas operativas con empresa/sede por defecto.

### 2026-06-15 - MT-02

- `MT-02` implementado como base compatible.
- Se agregaron `company_id` y `branch_id` a tablas operativas: canales, contactos, consentimientos, blacklist, conversaciones, plantillas, media, mensajes, eventos, adjuntos, entrantes, opt-outs, logs de proveedor y auditoria.
- La migracion asigna datos existentes a la empresa `default` y sede `principal`.
- Se agrego un trait de tenant por defecto para que nuevas entidades operativas nazcan con empresa/sede mientras se implementa el contexto activo.
- Se ampliaron `fillable` y relaciones base para aceptar `company_id`/`branch_id`.
- Se agregaron pruebas de columnas tenant y datos semilla con contexto.
- Siguiente paso recomendado: `MT-03 Contexto de empresa activa`, para reemplazar el default automatico por resolucion real desde la membresia del usuario.

### 2026-06-15 - MT-03

- `MT-03` implementado como base operativa.
- Se agrego `TenantContext` para conocer membresia, empresa y sede activa durante el request.
- Se agrego middleware de resolucion de contexto para rutas autenticadas.
- La seleccion activa se persiste en sesion con `tenant.membership_id`, `tenant.company_id` y `tenant.branch_id`.
- Se agrego una ruta para cambiar la membresia activa del usuario.
- El layout operativo muestra empresa/sede activa y permite cambiarla cuando hay mas de una membresia.
- Usuarios legacy con roles globales, pero sin membresia, reciben membresia default automaticamente para mantener compatibilidad.
- Se agregaron pruebas de resolucion de contexto, cambio de membresia y bloqueo de membresias de otros usuarios.
- Siguiente paso recomendado: `MT-04 Policies y scopes de aislamiento`, para que listados, detalle, reportes, webhooks y acciones respeten empresa/sede activa.

### 2026-06-15 - MT-04

- `MT-04` implementado como base de aislamiento.
- Se agrego scope `forTenantContext` para consultas operativas por empresa/sede activa.
- Se agrego verificacion `belongsToActiveTenant` para policies y acciones directas por ID.
- Las policies de contactos, conversaciones, mensajes y auditoria ahora validan rol y pertenencia al tenant activo.
- Repositorios y controladores principales filtran contactos, canales, conversaciones, mensajes, consentimientos, blacklist, auditoria, dashboard, settings y usuarios.
- La gestion de usuarios lista/edita usuarios del tenant activo y sincroniza membresias al crear o actualizar roles.
- Se agregaron pruebas de aislamiento entre empresas, bloqueo de detalle ajeno, aislamiento por sede y conteos del dashboard.
- Siguiente paso recomendado: `MT-05 Canales WhatsApp por empresa/sede`, para que tokens, `phone_number_id`, plantillas y webhooks se resuelvan por empresa.

### 2026-06-16 - MT-05

- `MT-05` implementado como base de integracion WhatsApp multiempresa.
- Los canales WhatsApp conservan empresa/sede y pueden guardar `access_token`, `phone_number_id`, `business_account_id`, `webhook_verify_token`, `app_secret` y `api_base_url`.
- Los jobs salientes registran logs de proveedor con tenant.
- El webhook entrante ya no cae a un canal default cuando Meta envia un `phone_number_id` desconocido.
- La resolucion de contactos entrantes mantiene el matching local/internacional, pero restringido a la empresa del canal.
- Siguiente paso recomendado: `MT-06 Contactos, importacion y fusion por empresa`, para cerrar duplicados y fusiones dentro de empresa/canal.

### 2026-06-16 - MT-06

- `MT-06` implementado como base de contactos por empresa/canal.
- El importador mantiene deduplicacion dentro del archivo y contra contactos del tenant activo, pero permite el mismo telefono en empresas diferentes.
- La actualizacion de contacto bloquea telefonos ya activos en el mismo canal antes de depender de restricciones de base de datos.
- La fusion de contactos valida empresa, bloquea origenes fuera del tenant activo y evita mover `contact_channels` que causarian duplicados por canal.
- Siguiente paso recomendado: `MT-07 Conversaciones por sede y reglas de asignacion`, para cerrar bandeja, asignacion y enrutamiento por sede.

### 2026-06-16 - MT-07

- `MT-07` implementado como base de conversaciones por sede.
- El inbox mantiene la experiencia tipo WhatsApp y agrega filtro compacto de sede solo cuando el usuario tiene alcance de empresa.
- La lista y el auto-refresh respetan el mismo filtro de sede.
- El selector de asignacion muestra usuarios elegibles para la sede de la conversacion activa.
- La asignacion manual y "Asignar a mi" bloquean usuarios sin membresia compatible.
- Siguiente paso recomendado: `MT-08 Auditoria y reportes por empresa/sede`, para cerrar trazabilidad y metricas segmentadas.

### 2026-06-16 - MT-08

- `MT-08` implementado como base de auditoria y reportes por empresa/sede.
- La vista de auditoria conserva sus filtros existentes y suma selector de sede para usuarios con alcance de empresa.
- El dashboard conserva sus tarjetas actuales y suma selector de sede para segmentar conteos.
- Los filtros de sede son saneados contra las sedes de la empresa activa antes de tocar las consultas.
- Siguiente paso recomendado: `MT-09 Administracion UI de empresas/sedes`, para gestionar empresas, sedes y membresias desde el panel.
