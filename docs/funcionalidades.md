# Matriz de funcionalidades NoiaChat

Ultima actualizacion: 2026-06-08

Esta matriz controla el estado funcional del proyecto. Debe actualizarse cada vez que se implemente, cambie, valide o descarte una funcionalidad.

## Resumen ejecutivo

| Area | Estado general | Lectura rapida |
| --- | --- | --- |
| Autenticacion y acceso | MVP | Login funcional; falta administracion completa de usuarios y endurecimiento. |
| Contactos | MVP | CRUD y normalizacion funcionan; falta importacion, deduplicacion y fusion. |
| Consentimientos | MVP | Otorgar/revocar funciona; falta historial mas completo y reglas de expiracion si aplican. |
| WhatsApp Cloud API | Operativo | Entrantes, salientes y estados reales validados; falta firma de webhook y ventana 24h. |
| Conversaciones | MVP | Inbox funcional; falta no leidos, auto-refresh y gestion operativa avanzada. |
| Mensajeria saliente | MVP | Texto validado; multimedia y plantillas requieren endurecimiento. |
| Auditoria | MVP | Registro y filtros funcionan; falta detalle expandido/exportacion. |
| Reportes | Pendiente | Dashboard basico; faltan metricas operativas y exportaciones. |
| Despliegue | MVP | GitHub Actions funciona; falta worker permanente formal y secretos completos. |
| Seguridad | MVP | Roles y CSRF base; falta firma webhook, 2FA, gestion usuarios y politicas productivas. |

## Leyenda

- Estado: `Operativo`, `MVP`, `En progreso`, `Pendiente`, `Bloqueado`, `En revision`, `Descartado`.
- Prioridad: `P0`, `P1`, `P2`, `P3`.

## Funcionalidades por modulo

| ID | Modulo | Funcionalidad | Estado | Prioridad | Evidencia actual | Siguiente accion |
| --- | --- | --- | --- | --- | --- | --- |
| AUTH-001 | Autenticacion | Login de administrador | Operativo | P0 | Login validado en produccion; tests de autenticacion | Cambiar contrasena inicial y configurar recuperacion por email real. |
| AUTH-002 | Autenticacion | Recuperacion de contrasena | MVP | P1 | Breeze y tests existentes | Configurar mailer real y probar flujo productivo. |
| AUTH-003 | Usuarios | Gestion de usuarios desde panel | Pendiente | P1 | Roles existen en seeders/policies | Crear CRUD de usuarios y asignacion de roles. |
| AUTH-004 | Usuarios | 2FA para administradores | Pendiente | P2 | No implementado | Evaluar Laravel Fortify u otro mecanismo. |
| CONTACT-001 | Contactos | Crear, editar y consultar contactos | MVP | P0 | CRUD funcional y auditoria visible | Mejorar validaciones y mensajes de error. |
| CONTACT-002 | Contactos | Normalizacion de telefonos | Operativo | P0 | Entrantes WhatsApp relacionan contacto aunque haya formato local/internacional | Agregar pruebas adicionales para formatos internacionales. |
| CONTACT-003 | Contactos | Creacion automatica desde webhook | Operativo | P0 | Conversacion real creada desde mensaje entrante | Mejorar nombre provisional y captura de perfil si Meta lo envia. |
| CONTACT-004 | Contactos | Importacion masiva CSV/Excel | Pendiente | P1 | No implementado | Crear importador con validacion, vista previa y reporte de errores. |
| CONTACT-005 | Contactos | Deduplicacion/fusion de contactos | Pendiente | P1 | No implementado | Crear detector por telefono normalizado y merge controlado. |
| CONSENT-001 | Consentimientos | Otorgar consentimiento por canal | Operativo | P0 | Respuesta WhatsApp desbloqueada despues de otorgar consentimiento | Mejorar evidencia visible en conversacion/contacto. |
| CONSENT-002 | Consentimientos | Revocar consentimiento | MVP | P0 | Caso de uso y rutas existentes | Probar flujo operativo completo y estados posteriores. |
| CONSENT-003 | Consentimientos | Lista de exclusion | MVP | P0 | Bloqueo por blacklist implementado | Agregar razon visible en conversacion y reportes. |
| CONSENT-004 | Consentimientos | Opt-out automatico por palabra clave | MVP | P0 | `STOP` y `NO ENVIAR` cubiertos por pruebas | Ampliar diccionario y confirmar texto de respuesta operativa. |
| WA-001 | WhatsApp | Verificacion de webhook | Operativo | P0 | Challenge manual y Meta verificado | Agregar monitoreo periodico del endpoint. |
| WA-002 | WhatsApp | Recepcion de mensajes entrantes | Operativo | P0 | Mensajes reales visibles en conversaciones | Agregar indicador de no leidos y auto-refresh. |
| WA-003 | WhatsApp | Envio de texto libre | Operativo | P0 | Mensaje real recibido en WhatsApp | Implementar regla de ventana de 24 horas. |
| WA-004 | WhatsApp | Estados enviado/entregado/leido | Operativo | P0 | Estados reales visibles en conversacion | Agregar panel de fallos y reintentos. |
| WA-005 | WhatsApp | Firma de webhook `X-Hub-Signature-256` | Pendiente | P0 | No implementado | Validar firma con app secret antes de aceptar POST. |
| WA-006 | WhatsApp | Manejo de errores de proveedor | Operativo | P0 | Commit `259b889`; errores se marcan como `failed` | Mostrar codigo/error de Meta en UI. |
| WA-007 | WhatsApp | Token permanente y rotacion | MVP | P0 | Token funcional configurado manualmente | Documentar fecha de expiracion, rotacion y responsable. |
| MSG-001 | Mensajeria | Cola de mensajes de texto | Operativo | P0 | Envio real por WhatsApp | Mantener worker permanente. |
| MSG-002 | Mensajeria | Envio de imagen/documento | MVP | P0 | Compliance corregido y cubierto por pruebas; jobs existen | Probar envio multimedia real con URL publica HTTPS. |
| MSG-003 | Mensajeria | Envio por plantilla | MVP | P0 | Flujo existe; falta sincronizacion/aprobacion Meta | Sincronizar plantillas con Meta y validar variables. |
| MSG-004 | Mensajeria | Reintento de mensajes fallidos | MVP | P1 | Ruta y prueba existen | Mostrar causa de fallo y permitir reintento con control. |
| MSG-005 | Mensajeria | Ventana 24h de WhatsApp | Pendiente | P0 | No implementado | Bloquear texto libre fuera de ventana y sugerir plantilla. |
| CONV-001 | Conversaciones | Listado de conversaciones | Operativo | P0 | Conversacion real visible | Agregar contador de no leidos. |
| CONV-002 | Conversaciones | Timeline entrante/saliente | Operativo | P0 | Mensajes reales visibles con estados | Mejorar UI tipo chat y agrupacion por fecha. |
| CONV-003 | Conversaciones | Asignacion a operador | MVP | P1 | Select y accion existen | Crear accion rapida "Asignar a mi" y filtros por equipo. |
| CONV-004 | Conversaciones | Estados abierta/pendiente/resuelta/cerrada | MVP | P1 | Select existe | Definir reglas operativas y automatizaciones. |
| CONV-005 | Conversaciones | Auto-refresh o tiempo real | Pendiente | P1 | No implementado | Usar polling, Echo/Reverb o Livewire segun alcance. |
| AUDIT-001 | Auditoria | Registro de acciones principales | MVP | P0 | `/audit-logs` muestra contactos y acciones | Agregar auditoria de mas eventos operativos. |
| AUDIT-002 | Auditoria | Filtros de auditoria | MVP | P1 | Modal de filtros implementado | Agregar exportacion CSV/Excel. |
| AUDIT-003 | Auditoria | Detalle de cambios old/new | Pendiente | P1 | Datos existen parcialmente | Crear vista detalle del log. |
| SETTINGS-001 | Configuracion | Gestion de canal WhatsApp | MVP | P1 | Vista settings existe | Ocultar secretos y mejorar validaciones. |
| SETTINGS-002 | Plantillas | CRUD/versionado de plantillas | MVP | P1 | Implementado en settings | Sincronizar con Meta y estado de aprobacion. |
| REPORT-001 | Dashboard | Contadores basicos | MVP | P2 | Dashboard con totales | Agregar tendencias, tasa respuesta y filtros avanzados. |
| REPORT-002 | Reportes | Exportacion de datos | Pendiente | P2 | No implementado | Exportar contactos, conversaciones, auditoria y mensajes. |
| DEPLOY-001 | Deploy | GitHub Actions a droplet | MVP | P0 | Workflow exitoso en `main` | Mover host a secretos y retirar debug. |
| DEPLOY-002 | Deploy | Worker permanente | Pendiente | P0 | Worker manual probado | Configurar Supervisor/systemd y documentar restart. |
| DEPLOY-003 | Deploy | Backups | Pendiente | P0 | No implementado | Programar backup DB y storage. |
| DEPLOY-004 | Deploy | Monitoreo | Pendiente | P1 | No implementado | Alertar jobs fallidos, disco, errores 500 y webhook. |
| DOC-001 | Documentacion | README | Operativo | P1 | README actualizado | Mantener con cada release. |
| DOC-002 | Documentacion | Changelog | Operativo | P1 | CHANGELOG actualizado | Crear version publica inicial. |
| DOC-003 | Documentacion | Manual WhatsApp | Operativo | P1 | `docs/integracion-whatsapp.md` | Actualizar si cambia Meta o flujo productivo. |
| DOC-004 | Documentacion | Gestion documental de funcionalidades | Operativo | P1 | `docs/gestion-documental.md`, `docs/funcionalidades.md` y `docs/plantilla-funcionalidad.md` | Revisar semanalmente y cerrar estados. |

## Backlog priorizado

### P0 - Critico

- Validar firma de webhooks Meta.
- Implementar regla de ventana 24h.
- Probar envio multimedia real con URL publica HTTPS.
- Configurar worker permanente.
- Configurar backups automaticos.
- Mostrar errores Meta en la interfaz.
- Mover datos sensibles del deploy a secretos.

### P1 - Importante

- CRUD de usuarios y roles.
- Importacion masiva de contactos.
- Deduplicacion y fusion de contactos.
- No leidos y auto-refresh en conversaciones.
- Sincronizacion de plantillas con Meta.
- Exportacion de auditoria.
- Documentacion de despliegue final.

### P2 - Mejora

- Dashboard avanzado.
- Reportes por operador/contacto.
- 2FA.
- Monitor de salud WhatsApp.
- Vista de conversacion mas cercana a chat.

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
| 2026-06-08 | Creacion de matriz inicial de funcionalidades y backlog priorizado. | Equipo NoiaChat |
