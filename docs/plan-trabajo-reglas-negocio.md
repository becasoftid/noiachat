# Plan de trabajo y reglas de negocio NoiaChat

Ultima actualizacion: 2026-06-18

Este documento convierte el estado actual del proyecto en un plan de desarrollo por etapas. Su objetivo es ordenar las tareas pendientes, definir reglas de negocio claras y dejar criterios de aceptacion para saber cuando una funcionalidad esta lista.

## Objetivo operativo

NoiaChat debe funcionar como una herramienta interna para gestionar atencion y mensajeria por WhatsApp con trazabilidad, consentimiento, cumplimiento y control por roles.

El sistema debe permitir:

- Operar con multiples empresas y sedes sin mezclar datos.
- Administrar contactos y sus canales.
- Registrar y respetar consentimientos.
- Bloquear contactos que no deben recibir mensajes.
- Enviar mensajes de texto, multimedia y plantillas.
- Recibir mensajes entrantes desde WhatsApp.
- Agrupar la comunicacion en conversaciones.
- Asignar conversaciones a operadores.
- Auditar acciones relevantes.
- Operar con trabajadores de cola y webhooks confiables.

## Reglas de negocio generales

### Usuarios y roles

- Todo acceso al panel debe requerir autenticacion.
- Solo usuarios activos pueden iniciar sesion.
- Un usuario puede tener uno o mas roles.
- Un usuario puede pertenecer a una o varias empresas, y su alcance debe definirse por empresa y sede.
- Los roles no deben dar acceso global por defecto cuando existan varias empresas.
- El registro publico debe crear una empresa y sede inicial, asignando al responsable un rol administrativo limitado a su propia empresa.
- Toda empresa creada desde registro publico debe iniciar en plan basico de prueba con fecha de inicio, fecha de vencimiento y limites documentados.
- El plan contratado por la empresa debe definir funcionalidades habilitadas y limites, independientemente de los roles de sus usuarios.
- Una accion operativa requiere permiso por rol/membresia y feature habilitada por plan.
- El rol `admin` puede administrar configuracion, usuarios, contactos, mensajes, conversaciones y auditoria.
- El rol `operator` puede gestionar contactos, conversaciones y mensajes, pero no configuracion critica.
- El rol `auditor` puede consultar informacion y auditoria, pero no enviar mensajes ni modificar datos operativos.
- Todo cambio sensible de usuario, rol, configuracion, contacto, consentimiento o blacklist debe quedar auditado.

### Empresas y sedes

- Todo dato operativo debe pertenecer a una empresa.
- Una empresa puede tener una o mas sedes.
- Una sede pertenece a una sola empresa.
- Toda consulta del panel debe respetar el alcance de empresa/sede del usuario autenticado.
- Un `super_admin` interno puede administrar todas las empresas si el negocio lo requiere.
- Un administrador de empresa solo debe administrar usuarios, sedes, canales, contactos, conversaciones y reportes de su empresa.
- Un operador solo debe ver conversaciones de sus sedes asignadas o las conversaciones que tenga permitidas por regla.
- Las reglas de asignacion, importacion, fusion, reportes y auditoria deben ejecutarse dentro del alcance de empresa/sede.
- El detalle tecnico y el plan de migracion viven en `docs/multiempresa-multisede.md`.

### Contactos

- Todo contacto debe tener nombre y telefono principal.
- El telefono debe normalizarse antes de guardarse o compararse.
- No debe existir mas de un contacto operativo con el mismo telefono normalizado para la misma empresa y canal sin una decision de fusion o deduplicacion.
- Un mismo telefono puede existir en empresas diferentes.
- Los contactos creados automaticamente desde WhatsApp deben marcarse con origen `whatsapp_webhook`.
- Un contacto puede estar activo, bloqueado, sin contacto o invalido.
- Un contacto eliminado no debe perder su historial si ya tiene mensajes, consentimientos o auditoria.

### Consentimiento y cumplimiento

- No se debe permitir envio saliente si el contacto no tiene consentimiento vigente para el canal.
- No se debe permitir envio saliente si el contacto esta en blacklist para el canal.
- Revocar consentimiento debe impedir nuevos envios libres.
- Un opt-out recibido por webhook debe crear una solicitud de opt-out y agregar el contacto a blacklist.
- Palabras como `STOP` y `NO ENVIAR` deben tratarse como opt-out.
- Las reglas de cumplimiento deben ejecutarse antes de encolar cualquier mensaje.
- Los errores de cumplimiento deben mostrarse de forma clara al operador.

### WhatsApp y ventana de conversacion

- Los mensajes de texto libre y multimedia solo deben permitirse dentro de la ventana de atencion permitida por WhatsApp.
- Cuando la ventana este cerrada, el sistema debe orientar al operador a usar una plantilla aprobada.
- Las plantillas deben estar activas en NoiaChat y aprobadas/configuradas en Meta antes de usarse en produccion.
- Las plantillas y credenciales de Meta deben pertenecer a un canal de una empresa especifica.
- Los webhooks entrantes deben resolver empresa y canal por el `phone_number_id` recibido de Meta.
- Todo mensaje enviado debe crear registro local antes de llamar al proveedor.
- La respuesta del proveedor debe actualizar el mensaje con `provider_message_id` cuando exista.
- Los estados recibidos por webhook deben actualizar el mensaje correspondiente.

### Conversaciones

- Una conversacion pertenece a un contacto y un canal.
- En modo multiempresa, una conversacion tambien debe pertenecer a una empresa y, cuando aplique, a una sede.
- Si llega un mensaje entrante y no existe conversacion abierta para contacto/canal, el sistema debe crearla o recuperar la conversacion vigente.
- Una conversacion puede estar abierta, pendiente, resuelta o cerrada.
- Una conversacion puede asignarse a un operador.
- La asignacion solo puede hacerse a usuarios con acceso a la empresa/sede de la conversacion.
- Las respuestas desde una conversacion deben quedar asociadas a esa conversacion.
- La vista de conversacion debe mostrar mensajes entrantes y salientes en orden cronologico.
- Debe existir una senal visible de conversaciones con mensajes no leidos o sin atender.
- La operacion diaria de conversaciones debe priorizar una experiencia tipo chat en una sola vista: lista de conversaciones, conversacion activa, cabecera de contacto, errores visibles y composer disponible sin perder contexto.
- El menu principal debe permitir contraerse en escritorio para ganar espacio operativo y conservar acceso a las secciones mediante iconos reconocibles.

### Auditoria

- La auditoria debe registrar actor, accion, modulo, entidad afectada, valores previos/nuevos cuando aplique, IP y user agent si existen.
- En modo multiempresa, la auditoria debe registrar empresa y sede cuando exista contexto operativo.
- Un auditor debe poder filtrar por accion, modulo, usuario y fecha.
- Un auditor solo debe ver eventos de las empresas/sedes permitidas.
- La auditoria no debe exponer secretos o tokens completos.

### Configuracion y seguridad

- Los secretos de WhatsApp no deben mostrarse completos en pantalla.
- Las credenciales iniciales deben cambiarse antes de operar fuera de local.
- El webhook debe validar token de verificacion y firma cuando `WHATSAPP_APP_SECRET` este configurado.
- Los jobs fallidos deben poder revisarse y reintentarse con control.
- Los backups deben generarse de forma automatica y tener una ruta documentada de restauracion.

## Plan de desarrollo

### Fase 0 - Estabilizacion inmediata

Objetivo: dejar el MVP listo para pruebas operativas controladas.

| ID | Tarea | Prioridad | Resultado esperado |
| --- | --- | --- | --- |
| P0-01 | Validar credenciales admin y documentarlas | P0 | Login local funcional con `admin@noiachat.local` y `Password`. |
| P0-02 | Ejecutar suite de pruebas actual | P0 | Resultado conocido antes de nuevos cambios. |
| P0-03 | Probar envio de texto real o simulado | P0 | Mensaje queda encolado, enviado o fallido con log claro. |
| P0-04 | Confirmar worker de cola local/productivo | P0 | Jobs de mensajes procesan sin intervencion manual. |
| P0-05 | Revisar errores visibles de formularios clave | P0 | Contactos, mensajes y conversaciones fallan con mensajes entendibles. |

Criterios de aceptacion:

- El administrador puede iniciar sesion.
- El panel carga sin errores.
- Contactos, mensajes y conversaciones son accesibles segun rol.
- Los jobs de cola se ejecutan.
- Los errores de proveedor quedan visibles en mensaje o logs.

### Fase 1 - Operacion diaria minima

Objetivo: hacer que un equipo pueda trabajar todos los dias con control basico.

| ID | Tarea | Prioridad | Resultado esperado |
| --- | --- | --- | --- |
| P1-01 | CRUD de usuarios | P1 | Implementado como MVP: admin crea, edita, activa/desactiva usuarios y asigna roles. |
| P1-02 | Accion "Asignar a mi" | P1 | Implementado: operador toma una conversacion con un clic. |
| P1-03 | Filtro "mis conversaciones" | P1 | Implementado: operador ve sus conversaciones asignadas. |
| P1-04 | Indicador de no leidos | P1 | Implementado: inbox muestra conversaciones pendientes de atencion. |
| P1-05 | Auto-refresh simple del inbox | P1 | Implementado: el listado se refresca por polling sin recargar toda la app. |
| P1-06 | Motivo visible de bloqueo/compliance | P1 | Implementado: operador ve por que no puede enviar en flash, detalle, listado y conversacion. |
| P1-17 | Vista operativa tipo WhatsApp | P1 | Implementado: inbox lateral y conversacion activa dentro de `/conversations?conversation=...`, con burbujas, fechas, errores y composer inferior. |
| P1-18 | Menu lateral colapsable | P1 | Implementado: menu expandible/contraible con iconos y preferencia local. |
| P1-19 | Indicador visual de lectura | P1 | Implementado: mensajes salientes muestran enviado, entregado o leido con checks y hora de lectura cuando Meta la reporta. |
| P1-20 | Sonido de mensaje entrante | P1 | Implementado: operador puede activar sonido local y el inbox reproduce alerta cuando aumentan los no leidos por auto-refresh. |

Criterios de aceptacion:

- Admin puede gestionar usuarios sin tocar base de datos.
- Operator no accede a configuracion sensible.
- Auditor no puede modificar ni enviar.
- Una conversacion entrante puede asignarse, responderse y marcarse como resuelta.
- Los no leidos se actualizan al abrir o atender la conversacion.
- Los estados de mensajes salientes son visibles sin abrir detalle tecnico.
- El operador puede activar/desactivar alertas sonoras locales para mensajes nuevos.

### Fase 1B - Base multiempresa y multisede

Objetivo: convertir el MVP operativo actual en una base segura para varias empresas y sedes.

Documento detallado: `docs/multiempresa-multisede.md`

| ID | Tarea | Prioridad | Resultado esperado |
| --- | --- | --- | --- |
| MT-00 | Definicion funcional multiempresa | P0 | Aprobado: roles finales, alcances, reglas de sede y enrutamiento documentados en `docs/multiempresa-multisede.md`. |
| MT-01 | Modelo base de empresas y sedes | P0 | Implementado como base: tablas `companies`, `branches`, `memberships`, relaciones, seeder y pruebas iniciales. |
| MT-02 | Migracion de datos existentes | P0 | Implementado como base: tablas operativas tienen `company_id`/`branch_id`, backfill a default/principal y pruebas de esquema/semillas. |
| MT-03 | Contexto de empresa activa | P0 | Implementado como base: middleware resuelve membresia activa, persiste seleccion en sesion y expone selector compacto. |
| MT-04 | Policies y scopes de aislamiento | P0 | Implementado como base: scopes por tenant activo, policies con empresa/sede, listados filtrados y pruebas de acceso cruzado. |
| MT-05 | Canales WhatsApp por empresa/sede | P0 | Implementado como base: webhooks resuelven canal por `phone_number_id`; envios, firmas y sync de plantillas usan credenciales del canal de empresa/sede. |
| MT-06 | Contactos, importacion y fusion por empresa | P1 | Implementado como base: importacion y edicion respetan duplicados por empresa/canal; fusion valida tenant y bloquea mezcla entre empresas. |
| MT-07 | Conversaciones por sede y reglas de asignacion | P1 | Implementado como base: inbox filtra por sede cuando el usuario tiene alcance de empresa y asignacion valida membresia de la sede. |
| MT-08 | Auditoria y reportes multiempresa | P1 | Implementado como base: auditoria y dashboard respetan tenant activo y filtran por sede cuando la membresia tiene alcance de empresa. |
| MT-09 | Administracion UI de empresas/sedes | P1 | Implementado como base: empresa activa, sedes y membresias se administran desde el panel con validacion por tenant. |
| MT-10 | Pruebas, migracion productiva y despliegue | P0 | Implementado como base: checklist productivo, backup verificable, pruebas de aislamiento y rollback documentado. |

Criterios de aceptacion:

- La instalacion actual sigue funcionando con una empresa/sede por defecto.
- Un usuario de una empresa no ve datos de otra.
- Los webhooks y envios WhatsApp usan el canal correcto por empresa.
- Contactos, conversaciones, mensajes, plantillas, auditoria y reportes quedan segmentados.
- Existen pruebas automatizadas de aislamiento entre al menos dos empresas.

### Fase 1C - Planes, suscripciones y funcionalidades por plan

Objetivo: convertir el trial actual en un modelo SaaS real por empresa, con planes, suscripciones, limites y features independientes de los roles.

Documento detallado: `docs/planes-suscripciones-features.md`

| ID | Tarea | Prioridad | Resultado esperado |
| --- | --- | --- | --- |
| BILLING-001 | Modelo de planes y suscripciones | P1 | Implementado como MVP: tablas `plans` y `company_subscriptions`, seeders y registro usando suscripcion real. |
| BILLING-002 | Catalogo de funcionalidades | P1 | Implementado como MVP: tablas `features` y `plan_features` con matriz por plan. |
| BILLING-003 | Servicio de evaluacion del plan | P1 | Implementado como MVP: servicio central valida suscripcion operativa, features, limites y dias restantes de trial. |
| BILLING-004 | Middleware y policies por feature | P1 | Implementado como MVP: middleware `feature` protege rutas administrativas iniciales y distingue bloqueo por plan. |
| BILLING-005 | Limites por plan | P1 | Implementado como MVP: usuarios, sedes, contactos y activacion de canales WhatsApp se validan contra el plan activo. |
| BILLING-007 | Vencimiento de trial y estados | P1 | Implementado como MVP: comando `noiachat:subscriptions-check`, auditoria, banner y bloqueo operativo por trial vencido. |
| BILLING-006 | Panel de plan y suscripcion | P2 | Implementado como MVP: `/billing` muestra plan, limites, features y administracion manual para `super_admin`. |
| BILLING-008 | Documentacion y checklist comercial | P2 | Implementado como MVP: matriz de planes/features, estados, procedimientos y checklist comercial documentados. |

Criterios de aceptacion:

- Toda empresa tiene una suscripcion.
- El rol del usuario no desbloquea features no incluidas en el plan.
- Los limites se aplican por empresa y plan.
- El trial vencido conserva datos, pero bloquea acciones operativas.
- El equipo puede administrar planes y suscripciones sin tocar base de datos.

### Fase 2 - Contactos y consentimiento robustos

Objetivo: reducir errores operativos con bases de contactos reales.

| ID | Tarea | Prioridad | Resultado esperado |
| --- | --- | --- | --- |
| P1-07 | Importacion CSV/Excel de contactos | P1 | Implementado como MVP: admin/operator sube CSV/XLSX, se validan filas y se importan contactos validos. |
| P1-08 | Reporte de errores de importacion | P1 | Implementado: filas invalidas muestran causa, no rompen el lote y se descargan como CSV. |
| P1-09 | Deteccion de duplicados | P1 | Implementado: telefonos repetidos se detectan dentro del archivo y contra contactos existentes. |
| P1-10 | Fusion controlada de contactos | P1 | Implementado: admin une historiales, mueve relaciones y archiva el origen con auditoria. |
| P1-11 | Historial visible de consentimiento | P1 | Implementado: contacto muestra altas, revocaciones, canal, fuente, fechas, usuarios y notas. |

Criterios de aceptacion:

- Un archivo con filas validas e invalidas se procesa parcialmente con reporte.
- No se crean duplicados silenciosos por telefono normalizado.
- Las fusiones preservan mensajes, conversaciones, consentimientos y auditoria.
- El operador puede explicar el estado de consentimiento de un contacto desde la pantalla.

### Fase 3 - WhatsApp productivo y plantillas

Objetivo: cerrar la brecha entre MVP y operacion real con Meta.

| ID | Tarea | Prioridad | Resultado esperado |
| --- | --- | --- | --- |
| P1-12 | Sincronizacion de plantillas Meta | P1 | Hecho: NoiaChat conoce nombre, idioma, estado, categoria, componentes y variables de plantillas de Meta. |
| P1-13 | Validacion de variables de plantilla | P1 | Hecho: el formulario y el caso de uso exigen la cantidad exacta de variables. |
| P1-14 | Aviso de ventana 24h cerrada | P1 | Hecho: operador ve recomendacion de plantilla y texto/adjuntos quedan deshabilitados antes de fallar. |
| P1-15 | Prueba multimedia con URL publica HTTPS | P0 | Hecho: imagen/documento usan URL publica HTTPS y fallan con motivo claro si no esta disponible. |
| P1-16 | Panel de fallos recientes | P1 | Implementado: admin ve errores de proveedor, jobs fallidos y mensajes reintentables desde `/failures`. |

Criterios de aceptacion:

- Una plantilla aprobada puede enviarse con variables correctas.
- Una plantilla incompleta no se encola.
- Texto libre se bloquea fuera de ventana y sugiere plantilla.
- Multimedia funciona con archivos accesibles por WhatsApp.
- Los fallos muestran codigo, descripcion y accion sugerida.

### Fase 4 - Auditoria, reportes y control

Objetivo: dar visibilidad a administracion y cumplimiento.

| ID | Tarea | Prioridad | Resultado esperado |
| --- | --- | --- | --- |
| P2-01 | Detalle de auditoria old/new | P1 | Implementado: auditor ve cambios antes/despues desde el detalle de auditoria. |
| P2-02 | Exportacion de auditoria | P1 | Implementado como CSV filtrado por fechas, usuario, accion, modulo y sede. |
| P2-03 | Exportacion de contactos | P2 | Implementado como CSV filtrado por busqueda y alcance de empresa/sede. |
| P2-04 | Dashboard operativo | P2 | Totales, conversaciones abiertas, mensajes fallidos, tiempos de respuesta. |
| P2-05 | Reportes por operador | P2 | Actividad y resolucion por usuario. |

Criterios de aceptacion:

- Los reportes respetan permisos.
- Las exportaciones usan filtros aplicados.
- No se exportan secretos.
- Dashboard ayuda a tomar decisiones diarias, no solo muestra contadores.

### Fase 5 - Seguridad, produccion y mantenimiento

Objetivo: operar con menor riesgo.

| ID | Tarea | Prioridad | Resultado esperado |
| --- | --- | --- | --- |
| P2-06 | 2FA para administradores | P2 | Implementado como MVP configurable: roles administrativos requieren desafio OTP por email cuando `NOIACHAT_2FA_ENABLED=true`; queda apagado temporalmente hasta configurar correo/SMTP real. |
| P2-07 | Ocultar secretos en settings | P1 | Tokens se muestran enmascarados. |
| P2-08 | Monitor de salud | P1 | Implementado como MVP: `/health` y `noiachat:health-check` muestran cola, webhook, jobs fallidos, disco, backups y errores recientes. |
| P2-09 | Backups externos | P1 | Copia fuera del servidor local. |
| P2-10 | Manual de restauracion validado | P1 | Restauracion probada en entorno limpio. |
| P2-11 | Vencimiento de trial y upgrade | P1 | Implementado como MVP para vencimiento/bloqueo; pendiente conversion comercial a plan pago. |

Criterios de aceptacion:

- Un secreto guardado no se expone completo en vistas.
- Hay evidencia de backup y restauracion.
- Fallos de cola/webhook generan alerta o indicador.
- Los administradores tienen mecanismo adicional de proteccion cuando el correo/SMTP real esta configurado y `NOIACHAT_2FA_ENABLED=true`.

## Orden recomendado de implementacion

1. Ejecutar pruebas y validar base actual.
2. CRUD de usuarios y roles.
3. Mejoras de conversaciones: asignar a mi, mis conversaciones, no leidos.
4. Base multiempresa/multisede si el producto se usara con mas de una empresa real.
5. Importacion de contactos.
6. Duplicados y fusion.
7. Plantillas Meta y validacion de variables.
8. Multimedia real con URL publica HTTPS.
9. Vista operativa tipo WhatsApp y menu colapsable.
10. Panel de fallos y reintentos mejorado.
11. Exportaciones y auditoria detallada.
12. Dashboard operativo.
13. Seguridad avanzada y monitoreo.

## Definition of Done

Una tarea se considera terminada cuando:

- La regla de negocio principal esta implementada.
- Existen validaciones de entrada.
- Los permisos por rol fueron revisados.
- El flujo feliz y al menos un error importante estan cubiertos.
- Hay prueba automatizada cuando la logica afecta negocio, permisos, mensajeria, webhooks o datos.
- La vista muestra errores comprensibles.
- La auditoria registra el cambio si aplica.
- La documentacion se actualiza si cambia operacion, configuracion o flujo.
- `composer test` o la prueba especifica relevante pasa.

## Proxima tarea sugerida

La siguiente tarea recomendada depende del alcance comercial inmediato:

- Si se va a operar con mas de una empresa real, la prioridad debe ser ejecutar `MT-10` en staging/copia productiva usando `docs/multiempresa-checklist-produccion.md` y `php8.4 artisan noiachat:tenant-validate`, porque la base multiempresa ya esta implementada como MVP y necesita validacion con datos reales antes de produccion.
- Si la operacion seguira con una sola empresa por ahora, la siguiente tarea recomendada es configurar correo/SMTP real, activar `NOIACHAT_2FA_ENABLED=true`, validar entrega del 2FA y documentar soporte operativo ante perdida de acceso administrativo.
- Si se habilitara registro publico comercial, la siguiente tarea recomendada es validar tarifas, copy comercial, cron de vencimiento y flujo de upgrade en staging antes de produccion publica.
- Si la prioridad es operacion productiva, la alternativa recomendada es conectar `noiachat:health-check` a cron/alertas externas y ejecutar `MT-10` en staging/copia productiva.

Al iniciar esa tarea, debe documentarse como funcionalidad con:

- ID: `MT-10-VALIDACION`, `DEPLOY-ALERTAS`, `AUTH-004-SMTP` u `ONBOARD-002`, segun decision operativa.
- Modulo: `Usuarios` / `Multiempresa` / `Deploy`
- Prioridad: `P0` para validacion productiva multiempresa, `P1` para alertas externas o `P2` para validacion SMTP de 2FA.
- Estado inicial: `En progreso`
- Documento base: este plan y `docs/funcionalidades.md`
