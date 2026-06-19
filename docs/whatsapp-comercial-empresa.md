# Plan comercial de configuracion WhatsApp por empresa

Ultima actualizacion: 2026-06-19

Este documento separa la configuracion tecnica interna de NoiaChat de la configuracion comercial que debe poder gestionar cada empresa para conectar su propio WhatsApp Cloud API de Meta.

## Objetivo

Permitir que una empresa configure y opere su canal WhatsApp desde el panel, sin exponer modulos tecnicos globales ni permitir acceso a datos o credenciales de otras empresas.

## Estado actual

La arquitectura base ya existe:

- Los canales WhatsApp se almacenan en `channels`.
- Cada canal tiene `company_id`, `branch_id`, `slug`, `is_active` y `settings`.
- `WhatsAppChannelConfig` resuelve credenciales por canal.
- Los webhooks entrantes resuelven el canal por `metadata.phone_number_id`.
- Los envios salientes usan el canal asociado al mensaje.
- La sincronizacion de plantillas usa `business_account_id` y `access_token` del canal.

La brecha actual es de producto/UI:

- La configuracion existe en `/settings`, pero esa ruta es de plataforma y requiere `platform.access`.
- El usuario comercial `company_admin` no tiene una pantalla propia para configurar WhatsApp.
- La experiencia no esta orientada a onboarding comercial ni validacion guiada.

## Alcance funcional

La configuracion WhatsApp comercial debe quedar dentro del contexto de la empresa activa, preferiblemente en:

- `Empresa > Canales WhatsApp`, o
- `Empresa > Integraciones > WhatsApp`.

El usuario solo debe ver y editar canales de su empresa y sede permitida.

## Actividades

| ID | Actividad | Prioridad | Estado | Objetivo | Criterio de aceptacion |
| --- | --- | --- | --- | --- | --- |
| WA-COM-001 | Pantalla comercial de canales WhatsApp | P1 | MVP | Crear una vista para que `company_admin` gestione canales WhatsApp de su empresa/sede sin entrar a `/settings`. | Desde el menu comercial se accede a la configuracion del canal propio y no aparecen modulos tecnicos globales. |
| WA-COM-002 | Permisos comerciales para integraciones | P1 | MVP | Separar permisos de plataforma y permisos de integracion empresarial. | `company_admin` puede configurar su canal; `operator` no; `super_admin` conserva soporte global. |
| WA-COM-003 | Crear/editar canal por empresa/sede | P1 | MVP | Permitir crear o actualizar canal WhatsApp asociado a la empresa y sede activa. | No se puede crear/editar un canal de otra empresa ni exceder el limite del plan. |
| WA-COM-004 | Formulario seguro de credenciales Meta | P1 | MVP | Capturar `phone_number_id`, `business_account_id`, `access_token`, `webhook_verify_token`, `app_secret` y metadata de rotacion. | Secretos se enmascaran, campos vacios conservan valores existentes y hay validaciones en espanol. |
| WA-COM-005 | Prueba de conexion con Meta | P1 | MVP | Validar credenciales antes o despues de guardar. | La pantalla muestra exito o error legible al consultar Meta con el token y WABA configurados. |
| WA-COM-006 | Sincronizacion comercial de plantillas | P1 | MVP | Permitir sincronizar plantillas desde la misma pantalla comercial. | Las plantillas se sincronizan solo para el canal de la empresa/sede activa. |
| WA-COM-007 | Estado operativo del canal | P2 | MVP | Mostrar resumen claro: activo, credenciales completas, ultima sincronizacion, expiracion de token y alertas. | El administrador entiende si el canal esta listo, incompleto o requiere accion. |
| WA-COM-008 | Guia en pantalla y documentacion operativa | P2 | MVP | Documentar datos que debe copiar desde Meta y pasos de verificacion. | Existe checklist operativo y copy de ayuda sin exponer secretos. |
| WA-COM-009 | Pruebas de aislamiento y permisos | P1 | MVP | Cubrir acceso por UI, rutas directas, edicion cruzada y asignacion de canales. | Tests prueban que empresa A no ve ni modifica canal de empresa B. |
| WA-COM-010 | Despliegue y validacion con numero real | P1 | MVP | Validar el flujo en staging/produccion con un numero WhatsApp real por empresa. | Envio, recepcion, webhook y plantillas funcionan con credenciales del canal comercial. |

## Reglas de negocio

- La configuracion Meta no debe ser global para todas las empresas.
- Cada empresa puede tener uno o varios canales WhatsApp segun su plan.
- Un canal puede aplicar a toda la empresa o a una sede especifica.
- El `phone_number_id` debe identificar de forma unica el canal operativo.
- El token de acceso nunca debe mostrarse completo despues de guardarlo.
- Si el campo de token queda vacio al editar, se conserva el token existente.
- El webhook publico puede ser comun, pero la resolucion interna debe hacerse por `phone_number_id`.
- Las plantillas sincronizadas deben quedar asociadas al canal correspondiente.
- Las variables `.env` de WhatsApp quedan solo como fallback tecnico de compatibilidad, no como configuracion comercial principal.

## Roles esperados

| Rol | Puede configurar WhatsApp comercial | Observacion |
| --- | --- | --- |
| `super_admin` | Si | Soporte global y diagnostico tecnico. |
| `company_admin` | Si | Solo canales de su empresa y sedes permitidas. |
| `branch_manager` | Opcional | Solo si se decide permitir configuracion por sede. |
| `operator` | No | Opera conversaciones, no credenciales. |
| `auditor` | No | Puede revisar trazabilidad si su plan/rol lo permite. |

## Riesgos

- Exponer accidentalmente secretos Meta a usuarios sin permiso.
- Permitir que una empresa modifique el canal de otra empresa.
- Activar mas canales que los permitidos por el plan.
- Mezclar plantillas o webhooks entre empresas si el `phone_number_id` no esta bien validado.
- Confundir configuracion tecnica de plataforma con configuracion comercial del cliente.

## Evidencias requeridas para cerrar MVP

- Pruebas automatizadas de permisos y aislamiento.
- Prueba manual de guardar credenciales en empresa de prueba.
- Prueba de sincronizacion de plantillas desde Meta.
- Prueba de webhook entrante asociado a la empresa correcta.
- Prueba de envio saliente usando el canal configurado.
- Documentacion actualizada en `docs/integracion-whatsapp.md` o documento operativo comercial.

## Avances

### 2026-06-19 - WA-COM-002 base de permisos

- Se creo el permiso `whatsapp.integration.manage`.
- `company_admin` puede tener permiso comercial de integracion WhatsApp sin obtener `platform.access`.
- `operator` no puede gestionar integraciones WhatsApp.
- La configuracion tecnica `/settings` permanece restringida a `platform.access`.
- Prueba ejecutada: `php artisan test tests/Feature/Auth/RegistrationTest.php` con `13 passed`.

### 2026-06-19 - WA-COM-001 pantalla comercial base

- Se creo la ruta comercial `/integrations/whatsapp`.
- Se agrego el menu `WhatsApp` para usuarios con `whatsapp.integration.manage` y feature `settings.whatsapp_channel`.
- La pantalla lista canales WhatsApp de la empresa/sede activa, estado, sede, credenciales enmascaradas, conteo de mensajes/conversaciones y expiracion del token.
- Si no hay canales, muestra el estado vacio comercial sin exponer `/settings`.
- Las acciones de crear/editar canal y sincronizar plantillas quedan para `WA-COM-003`, `WA-COM-004` y `WA-COM-006`.
- Prueba ejecutada: `php artisan test tests/Feature/Auth/RegistrationTest.php` con `14 passed`.

### 2026-06-19 - WA-COM-003/004 crear, editar y guardar credenciales

- Se agregaron rutas comerciales para crear y actualizar canales:
  - `POST /integrations/whatsapp/channels`
  - `PATCH /integrations/whatsapp/channels/{channel}`
- El formulario comercial permite asociar el canal a toda la empresa o a una sede activa.
- Se guardan `phone_number_id`, `business_account_id`, `access_token`, `webhook_verify_token`, `app_secret`, URL base de Graph API y metadata de rotacion.
- Los secretos se muestran enmascarados y se conservan si el campo queda vacio al editar.
- La creacion respeta el limite de canales WhatsApp del plan activo.
- Se bloquea crear otro canal WhatsApp para el mismo alcance empresa/sede.
- Los cambios quedan auditados sin escribir secretos completos en el snapshot de auditoria.
- Prueba ejecutada: `php artisan test tests/Feature/Auth/RegistrationTest.php` con `17 passed`.

### 2026-06-19 - WA-COM-005/006 prueba de conexion y sync comercial

- Se agrego `POST /integrations/whatsapp/channels/{channel}/test` para validar credenciales contra Meta.
- La prueba valida `phone_number_id`, `business_account_id` y `access_token`; si falta algo muestra error legible.
- Si Meta responde correctamente, se guarda `last_connection_test` en `settings` con nombre verificado, telefono, WABA y fecha.
- Se agrego `POST /integrations/whatsapp/channels/{channel}/sync-templates` para sincronizar plantillas desde el canal comercial.
- La sincronizacion reutiliza `WhatsAppTemplateSyncService` y mantiene plantillas dentro de la empresa/sede del canal.
- La pantalla comercial muestra la ultima conexion validada y botones por canal.
- Prueba ejecutada: `php artisan test tests/Feature/Auth/RegistrationTest.php` con `20 passed`.

### 2026-06-19 - WA-COM-007/008 estado operativo y guia

- La pantalla comercial calcula estado por canal: `Listo para operar`, `Requiere revision` o `Configuracion incompleta`.
- El estado considera canal activo, credenciales requeridas, ultima prueba de conexion, expiracion de token y alertas de vencimiento.
- La vista muestra pendientes concretos por canal sin exponer secretos.
- Se agrego checklist visual con los datos que deben copiarse desde Meta y el orden de validacion.
- Prueba ejecutada: `php artisan test tests/Feature/Auth/RegistrationTest.php` con `21 passed`.

### 2026-06-19 - WA-COM-009/010 aislamiento y validacion real

- Se agregaron pruebas automatizadas para confirmar que una empresa no ve canales WhatsApp de otra.
- Se cubrieron intentos por URL directa para editar, probar conexion y sincronizar plantillas de un canal ajeno.
- Se cubrio bloqueo al intentar crear un canal usando una sede de otra empresa.
- Se creo el comando `noiachat:whatsapp-commercial-validate {channel_id} --sync-templates` para validar un canal real contra Meta desde staging/produccion.
- El comando prueba conexion, guarda `last_connection_test` y opcionalmente sincroniza plantillas.
- Prueba ejecutada: `php artisan test tests/Feature/Auth/RegistrationTest.php` con `25 passed`.

## Validacion real WA-COM-010

Ejecutar en staging o produccion con un canal que ya tenga credenciales reales:

```bash
php artisan noiachat:whatsapp-commercial-validate {channel_id} --sync-templates
```

Checklist de cierre real:

- Canal creado desde `/integrations/whatsapp`.
- `Phone Number ID`, `WABA ID` y token real guardados.
- App secret y webhook verify token configurados si aplica.
- Comando de validacion ejecutado sin errores.
- Plantillas reales sincronizadas.
- Webhook Meta apunta al dominio productivo.
- Mensaje entrante real crea/actualiza conversacion en la empresa correcta.
- Mensaje saliente real usa el canal configurado.

## Orden recomendado de desarrollo

1. `WA-COM-002` permisos comerciales.
2. `WA-COM-001` pantalla comercial.
3. `WA-COM-003` crear/editar canal.
4. `WA-COM-004` formulario seguro de credenciales.
5. `WA-COM-009` pruebas de aislamiento.
6. `WA-COM-006` sincronizacion de plantillas.
7. `WA-COM-005` prueba de conexion.
8. `WA-COM-007` estado operativo.
9. `WA-COM-008` guia y documentacion.
10. `WA-COM-010` validacion real.
