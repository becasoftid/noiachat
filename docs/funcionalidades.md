# Matriz de funcionalidades NoiaChat

Ultima actualizacion: 2026-06-15

Esta matriz controla el estado funcional del proyecto. Debe actualizarse cada vez que se implemente, cambie, valide o descarte una funcionalidad.

## Resumen ejecutivo

| Area | Estado general | Lectura rapida |
| --- | --- | --- |
| Autenticacion y acceso | MVP | Login funcional; gestion de usuarios disponible para admin; falta 2FA y endurecimiento productivo. |
| Contactos | MVP | CRUD y normalizacion funcionan; falta importacion, deduplicacion y fusion. |
| Consentimientos | MVP | Otorgar/revocar funciona; falta historial mas completo y reglas de expiracion si aplican. |
| WhatsApp Cloud API | Operativo | Entrantes, salientes, estados reales, firma de webhook y ventana 24h validados. |
| Conversaciones | Operativo | Inbox, no leidos, auto-refresh y vista tipo chat para atencion diaria. |
| Mensajeria saliente | MVP | Texto validado; multimedia y plantillas requieren endurecimiento. |
| Auditoria | MVP | Registro y filtros funcionan; falta detalle expandido/exportacion. |
| Reportes | Pendiente | Dashboard basico; faltan metricas operativas y exportaciones. |
| Despliegue | Operativo | GitHub Actions funciona con secretos, worker permanente y backups automaticos locales. |
| Seguridad | MVP | Roles, usuarios activos, CSRF base y firma webhook; falta 2FA y politicas productivas. |

## Leyenda

- Estado: `Operativo`, `MVP`, `En progreso`, `Pendiente`, `Bloqueado`, `En revision`, `Descartado`.
- Prioridad: `P0`, `P1`, `P2`, `P3`.

## Funcionalidades por modulo

| ID | Modulo | Funcionalidad | Estado | Prioridad | Evidencia actual | Siguiente accion |
| --- | --- | --- | --- | --- | --- | --- |
| AUTH-001 | Autenticacion | Login de administrador | Operativo | P0 | Login validado en produccion; tests de autenticacion | Cambiar contrasena inicial y configurar recuperacion por email real. |
| AUTH-002 | Autenticacion | Recuperacion de contrasena | MVP | P1 | Breeze y tests existentes | Configurar mailer real y probar flujo productivo. |
| AUTH-003 | Usuarios | Gestion de usuarios desde panel | MVP | P1 | CRUD admin, asignacion de roles, bloqueo de inactivos y `tests/Feature/UserManagementTest.php` | Validar flujo manual en panel y mejorar gestion de sesiones activas. |
| AUTH-004 | Usuarios | 2FA para administradores | Pendiente | P2 | No implementado | Evaluar Laravel Fortify u otro mecanismo. |
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
| WA-004 | WhatsApp | Estados enviado/entregado/leido | Operativo | P0 | Estados reales visibles en conversacion | Agregar panel de fallos consolidado. |
| WA-005 | WhatsApp | Firma de webhook `X-Hub-Signature-256` | Operativo | P0 | Validacion HMAC-SHA256 con `WHATSAPP_APP_SECRET` y pruebas automatizadas | Configurar app secret en produccion y verificar evento real. |
| WA-006 | WhatsApp | Manejo de errores de proveedor | Operativo | P0 | Errores se marcan como `failed` y se muestran en detalle/timeline con codigo y payload tecnico | Crear tablero operativo de fallos recientes. |
| WA-007 | WhatsApp | Token permanente y rotacion | MVP | P0 | Token funcional configurado manualmente | Documentar fecha de expiracion, rotacion y responsable. |
| MSG-001 | Mensajeria | Cola de mensajes de texto | Operativo | P0 | Envio real por WhatsApp | Mantener worker permanente. |
| MSG-002 | Mensajeria | Envio de imagen/documento | Operativo | P0 | Compliance cubierto; jobs usan URL publica HTTPS y fallan con motivo claro si el archivo no es accesible | Validar envio real en produccion con dominio final y certificado vigente. |
| MSG-003 | Mensajeria | Envio por plantilla | Operativo | P0 | Plantillas sincronizadas con Meta, estado visible, no aprobadas bloqueadas y variables exactas validadas antes de encolar | Probar envio real con plantilla aprobada en Meta. |
| MSG-004 | Mensajeria | Reintento de mensajes fallidos | MVP | P1 | Ruta y prueba existen | Mostrar causa de fallo y permitir reintento con control. |
| MSG-005 | Mensajeria | Ventana 24h de WhatsApp | Operativo | P0 | Texto libre/multimedia bloqueado fuera de ventana; motivos visibles; aviso preventivo; plantillas permitidas; pruebas automatizadas | Revisar copy operativo con usuarios finales. |
| CONV-001 | Conversaciones | Listado de conversaciones | Operativo | P0 | Inbox redisenado como panel de chats con filtros compactos, no leidos, auto-refresh y pruebas automatizadas | Validar con operadores en produccion y ajustar densidad si aumenta el volumen. |
| CONV-002 | Conversaciones | Timeline entrante/saliente | Operativo | P0 | Vista tipo chat integrada en `/conversations?conversation=...` con lista lateral, cabecera de contacto, burbujas, fechas, estados, errores y compositor inferior | Agregar scroll automatico al ultimo mensaje si el volumen lo exige. |
| CONV-003 | Conversaciones | Asignacion a operador | MVP | P1 | Select, accion "Asignar a mi" y pruebas en `NoiaChatMvpTest` | Crear filtros por equipo y reglas operativas de reasignacion. |
| CONV-004 | Conversaciones | Estados abierta/pendiente/resuelta/cerrada | MVP | P1 | Select existe | Definir reglas operativas y automatizaciones. |
| CONV-005 | Conversaciones | Auto-refresh o tiempo real | MVP | P1 | Polling simple del inbox con endpoint parcial y `NoiaChatMvpTest` | Evaluar tiempo real con Echo/Reverb si el volumen lo exige. |
| UI-001 | Interfaz | Menu lateral colapsable con iconos | Operativo | P1 | Layout principal permite contraer/expandir, recuerda preferencia local e identifica opciones por icono | Validar usabilidad con usuarios y reemplazar SVG inline por libreria de iconos si se adopta una. |
| AUDIT-001 | Auditoria | Registro de acciones principales | MVP | P0 | `/audit-logs` muestra contactos y acciones | Agregar auditoria de mas eventos operativos. |
| AUDIT-002 | Auditoria | Filtros de auditoria | MVP | P1 | Modal de filtros implementado | Agregar exportacion CSV/Excel. |
| AUDIT-003 | Auditoria | Detalle de cambios old/new | Pendiente | P1 | Datos existen parcialmente | Crear vista detalle del log. |
| SETTINGS-001 | Configuracion | Gestion de canal WhatsApp | MVP | P1 | Vista settings existe | Ocultar secretos y mejorar validaciones. |
| SETTINGS-002 | Plantillas | CRUD/versionado de plantillas | MVP | P1 | Implementado en settings | Sincronizar con Meta y estado de aprobacion. |
| REPORT-001 | Dashboard | Contadores basicos | MVP | P2 | Dashboard con totales | Agregar tendencias, tasa respuesta y filtros avanzados. |
| REPORT-002 | Reportes | Exportacion de datos | Pendiente | P2 | No implementado | Exportar contactos, conversaciones, auditoria y mensajes. |
| DEPLOY-001 | Deploy | GitHub Actions a droplet | Operativo | P0 | Workflow usa secretos para host, usuario, puerto y llave; debug retirado | Verificar nuevo run de Actions con secretos configurados. |
| DEPLOY-002 | Deploy | Worker permanente | Operativo | P0 | Configuracion Supervisor versionada, deploy reinicia workers y manual operativo creado | Verificar `supervisorctl status noiachat-worker:*` en produccion. |
| DEPLOY-003 | Deploy | Backups | Operativo | P0 | Comando `noiachat:backup`, cron versionado, deploy instala cron y manual de restauracion | Sincronizar backups a almacenamiento externo. |
| DEPLOY-004 | Deploy | Monitoreo | Pendiente | P1 | No implementado | Alertar jobs fallidos, disco, errores 500 y webhook. |
| DOC-001 | Documentacion | README | Operativo | P1 | README actualizado | Mantener con cada release. |
| DOC-002 | Documentacion | Changelog | Operativo | P1 | CHANGELOG actualizado | Crear version publica inicial. |
| DOC-003 | Documentacion | Manual WhatsApp | Operativo | P1 | `docs/integracion-whatsapp.md` | Actualizar si cambia Meta o flujo productivo. |
| DOC-004 | Documentacion | Gestion documental de funcionalidades | Operativo | P1 | `docs/gestion-documental.md`, `docs/funcionalidades.md` y `docs/plantilla-funcionalidad.md` | Revisar semanalmente y cerrar estados. |

## Backlog priorizado

### P0 - Critico

- Probar envio multimedia real con URL publica HTTPS.

### P1 - Importante

- CRUD de usuarios y roles.
- Importacion masiva de contactos.
- Deduplicacion y fusion de contactos.
- Vista tipo WhatsApp para conversaciones.
- Sincronizacion de plantillas con Meta.
- Exportacion de auditoria.
- Documentacion de despliegue final.

### P2 - Mejora

- Dashboard avanzado.
- Reportes por operador/contacto.
- 2FA.
- Monitor de salud WhatsApp.
- Panel de fallos recientes.

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
| 2026-06-15 | Redisenio operativo de conversaciones, carga del chat activo en `/conversations` y menu lateral colapsable con iconos. | Equipo NoiaChat |
| 2026-06-08 | Creacion de matriz inicial de funcionalidades y backlog priorizado. | Equipo NoiaChat |
