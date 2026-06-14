# Plan de trabajo y reglas de negocio NoiaChat

Ultima actualizacion: 2026-06-14

Este documento convierte el estado actual del proyecto en un plan de desarrollo por etapas. Su objetivo es ordenar las tareas pendientes, definir reglas de negocio claras y dejar criterios de aceptacion para saber cuando una funcionalidad esta lista.

## Objetivo operativo

NoiaChat debe funcionar como una herramienta interna para gestionar atencion y mensajeria por WhatsApp con trazabilidad, consentimiento, cumplimiento y control por roles.

El sistema debe permitir:

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
- El rol `admin` puede administrar configuracion, usuarios, contactos, mensajes, conversaciones y auditoria.
- El rol `operator` puede gestionar contactos, conversaciones y mensajes, pero no configuracion critica.
- El rol `auditor` puede consultar informacion y auditoria, pero no enviar mensajes ni modificar datos operativos.
- Todo cambio sensible de usuario, rol, configuracion, contacto, consentimiento o blacklist debe quedar auditado.

### Contactos

- Todo contacto debe tener nombre y telefono principal.
- El telefono debe normalizarse antes de guardarse o compararse.
- No debe existir mas de un contacto operativo con el mismo telefono normalizado para el mismo canal sin una decision de fusion o deduplicacion.
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
- Todo mensaje enviado debe crear registro local antes de llamar al proveedor.
- La respuesta del proveedor debe actualizar el mensaje con `provider_message_id` cuando exista.
- Los estados recibidos por webhook deben actualizar el mensaje correspondiente.

### Conversaciones

- Una conversacion pertenece a un contacto y un canal.
- Si llega un mensaje entrante y no existe conversacion abierta para contacto/canal, el sistema debe crearla o recuperar la conversacion vigente.
- Una conversacion puede estar abierta, pendiente, resuelta o cerrada.
- Una conversacion puede asignarse a un operador.
- Las respuestas desde una conversacion deben quedar asociadas a esa conversacion.
- La vista de conversacion debe mostrar mensajes entrantes y salientes en orden cronologico.
- Debe existir una senal visible de conversaciones con mensajes no leidos o sin atender.

### Auditoria

- La auditoria debe registrar actor, accion, modulo, entidad afectada, valores previos/nuevos cuando aplique, IP y user agent si existen.
- Un auditor debe poder filtrar por accion, modulo, usuario y fecha.
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

Criterios de aceptacion:

- Admin puede gestionar usuarios sin tocar base de datos.
- Operator no accede a configuracion sensible.
- Auditor no puede modificar ni enviar.
- Una conversacion entrante puede asignarse, responderse y marcarse como resuelta.
- Los no leidos se actualizan al abrir o atender la conversacion.

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
| P1-16 | Panel de fallos recientes | P1 | Admin ve errores de proveedor, jobs fallidos y mensajes reintentables. |

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
| P2-01 | Detalle de auditoria old/new | P1 | Auditor ve cambios antes/despues. |
| P2-02 | Exportacion de auditoria | P1 | CSV/Excel filtrado por fechas, usuario y accion. |
| P2-03 | Exportacion de contactos | P2 | Base exportable para revision operativa. |
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
| P2-06 | 2FA para administradores | P2 | Admin requiere segundo factor. |
| P2-07 | Ocultar secretos en settings | P1 | Tokens se muestran enmascarados. |
| P2-08 | Monitor de salud | P1 | Estado de cola, webhook, jobs fallidos y disco visible o alertable. |
| P2-09 | Backups externos | P1 | Copia fuera del servidor local. |
| P2-10 | Manual de restauracion validado | P1 | Restauracion probada en entorno limpio. |

Criterios de aceptacion:

- Un secreto guardado no se expone completo en vistas.
- Hay evidencia de backup y restauracion.
- Fallos de cola/webhook generan alerta o indicador.
- Los administradores tienen mecanismo adicional de proteccion.

## Orden recomendado de implementacion

1. Ejecutar pruebas y validar base actual.
2. CRUD de usuarios y roles.
3. Mejoras de conversaciones: asignar a mi, mis conversaciones, no leidos.
4. Importacion de contactos.
5. Duplicados y fusion.
6. Plantillas Meta y validacion de variables.
7. Multimedia real con URL publica HTTPS.
8. Panel de fallos y reintentos mejorado.
9. Exportaciones y auditoria detallada.
10. Dashboard operativo.
11. Seguridad avanzada y monitoreo.

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

La siguiente tarea de desarrollo recomendada es `P1-16 Panel de fallos recientes`, porque los envios ya registran motivos claros y ahora falta una vista operativa para revisar errores y reintentos.

Al iniciar esa tarea, debe documentarse como funcionalidad con:

- ID: `P1-16`
- Modulo: `WhatsApp` / `Operaciones`
- Prioridad: `P1`
- Estado inicial: `En progreso`
- Documento base: este plan y `docs/funcionalidades.md`
