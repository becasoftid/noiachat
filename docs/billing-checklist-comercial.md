# Billing: matriz comercial y checklist operativo

Ultima actualizacion: 2026-06-19

Este documento deja operable el modelo de planes y suscripciones de NoiaChat. Su objetivo es que soporte, comercial y administracion puedan explicar planes, cambiar suscripciones y validar una salida productiva sin tocar base de datos.

## Estado actual

- El registro publico crea una empresa, sede inicial, usuario `company_admin` y suscripcion `trialing` al plan `basic_trial`.
- El flujo publico de alta, la vista `/register`, los mensajes de validacion en espanol y el checklist de onboarding estan documentados en `docs/onboarding-registro-trial.md`.
- Los planes, features y limites viven en base de datos: `plans`, `features`, `plan_features` y `company_subscriptions`.
- El panel `/billing` muestra plan actual, estado, dias restantes, limites usados/disponibles y features incluidas.
- El panel `/billing` muestra un catalogo comercial comparativo con metadata de audiencia, etiqueta comercial, precio/nota comercial y limites por plan.
- Las solicitudes de upgrade/cambio de plan se registran en `subscription_change_requests` y quedan auditadas.
- Un `super_admin` puede aprobar o rechazar solicitudes de cambio desde `/billing`; al aprobar se activa el plan solicitado.
- Un `super_admin` puede cambiar plan, estado y fechas desde `/billing`; el cambio queda en `audit_logs`.
- El comando `php artisan noiachat:subscriptions-check` vence trials expirados y audita la expiracion.

## Matriz de planes

| Plan | Uso recomendado | Periodo | Trial | Usuarios | Sedes | Contactos | Canales WhatsApp |
| --- | --- | --- | --- | ---: | ---: | ---: | ---: |
| `basic_trial` | Prueba inicial despues del registro publico | trial | 14 dias | 3 | 1 | 100 | 1 |
| `basic` | Operacion inicial de una empresa pequena | monthly | No | 3 | 1 | 500 | 1 |
| `pro` | Operacion con importacion, multimedia, plantillas y reportes | monthly | No | 15 | 5 | 5000 | 5 |
| `enterprise` | Operacion con limites personalizados | monthly | No | Ilimitado | Ilimitado | Ilimitado | Ilimitado |

Notas:

- Los precios se leen desde `price_cents`; mientras el equipo comercial define tarifas reales, el catalogo muestra `metadata.price_note`.
- `enterprise` usa limites `null`, interpretados como ilimitados.
- Mensajes mensuales no tienen limite aplicado todavia; queda como expansion futura si el negocio lo requiere.
- La metadata comercial vive en `plans.metadata`: `display_order`, `audience`, `commercial_label`, `price_note` y `highlight`.

## Matriz de funcionalidades por plan

| Feature | Modulo | Trial | Basic | Pro | Enterprise |
| --- | --- | --- | --- | --- | --- |
| `contacts.create` | Contactos | Si | Si | Si | Si |
| `contacts.import` | Contactos | No | No | Si | Si |
| `contacts.merge` | Contactos | No | No | Si | Si |
| `conversations.inbox` | Conversaciones | Si | Si | Si | Si |
| `conversations.assignment` | Conversaciones | Si | Si | Si | Si |
| `whatsapp.text` | WhatsApp | Si | Si | Si | Si |
| `whatsapp.media` | WhatsApp | No | No | Si | Si |
| `whatsapp.templates` | WhatsApp | Si | Si | Si | Si |
| `reports.dashboard` | Reportes | Si | Si | Si | Si |
| `reports.export` | Reportes | No | No | Si | Si |
| `audit.view` | Auditoria | No | No | Si | Si |
| `audit.detail` | Auditoria | No | No | Si | Si |
| `settings.whatsapp_channel` | Configuracion | Si | Si | Si | Si |
| `users.manage` | Usuarios | Si | Si | Si | Si |
| `branches.manage` | Empresa/sedes | Si | Si | Si | Si |
| `health.view` | Deploy/salud | No | No | No | Si |
| `api.access` | API | No | No | No | Si |

## Estados de suscripcion

| Estado | Significado | Acceso esperado | Accion comercial |
| --- | --- | --- | --- |
| `trialing` | Empresa en periodo de prueba vigente | Login, dashboard, billing y funciones incluidas por plan | Acompanamiento y conversion antes del vencimiento |
| `active` | Empresa activa en plan pago/manual | Operacion normal segun features y limites | Seguimiento comercial normal |
| `past_due` | Pago pendiente o situacion administrativa | Operacion permitida temporalmente | Contactar responsable y resolver pago |
| `expired` | Trial vencido o suscripcion expirada | Login, dashboard y billing; acciones operativas bloqueadas | Renovar, cambiar a plan activo o extender trial |
| `cancelled` | Suscripcion cancelada | Acciones operativas bloqueadas | Reactivar solo con aprobacion comercial |

Reglas importantes:

- Un rol no desbloquea una feature que el plan no incluye.
- `super_admin` puede hacer bypass operativo para soporte y recuperacion.
- Reducir un plan no borra datos existentes; bloquea nuevas creaciones al superar limites.
- Un trial vencido no impide iniciar sesion, para permitir ver avisos y solicitar renovacion.

## Procedimiento: extender trial

1. Ingresar como `super_admin`.
2. Abrir `/billing`.
3. Ubicar la empresa en "Administracion global de suscripciones".
4. Seleccionar estado `trialing`.
5. Ajustar `Fin prueba` y `Fin periodo` a la nueva fecha aprobada.
6. Guardar.
7. Confirmar que aparece el mensaje "Suscripcion actualizada."
8. Revisar `/audit-logs` y filtrar modulo `billing` para confirmar trazabilidad.

## Procedimiento: cambiar plan

1. Confirmar solicitud comercial o soporte aprobado.
2. Ingresar como `super_admin`.
3. Abrir `/billing`.
4. Ubicar la empresa.
5. Seleccionar el plan nuevo: `basic`, `pro` o `enterprise`.
6. Definir estado:
   - `active` para plan vigente.
   - `past_due` si se permite operacion temporal con pago pendiente.
   - `expired` si debe bloquearse operacion.
   - `cancelled` si la empresa cancelo.
7. Ajustar `Fin periodo` si aplica.
8. Guardar.
9. Validar que los limites y features cambien en el resumen del panel.
10. Revisar auditoria del cambio.

## Procedimiento: solicitud de upgrade

1. Ingresar como `company_admin`.
2. Abrir `/billing`.
3. Revisar el catalogo comercial.
4. Seleccionar el plan objetivo en "Accion comercial".
5. Escribir el motivo de la solicitud.
6. Enviar la solicitud.
7. El sistema crea un registro `pending` en `subscription_change_requests`.
8. Un `super_admin` revisa la solicitud en `/billing`.
9. Si aprueba, el sistema cambia la suscripcion al plan solicitado con estado `active`.
10. Si rechaza, la solicitud queda cerrada sin cambiar la suscripcion.
11. En ambos casos debe existir auditoria en `audit_logs` con modulo `billing`.

## Procedimiento: trial vencido

1. Ejecutar simulacion:

```bash
php artisan noiachat:subscriptions-check --dry-run
```

2. Si la lista es correcta, ejecutar:

```bash
php artisan noiachat:subscriptions-check
```

3. Confirmar que las empresas vencidas quedan en estado `expired`.
4. Validar que el usuario pueda entrar al dashboard y ver el aviso de vencimiento.
5. Validar que una accion operativa protegida por plan quede bloqueada.
6. Contactar a la empresa para renovar, extender trial o cambiar plan.

## Checklist comercial antes de produccion

- [ ] Tarifas reales definidas para `basic`, `pro` y `enterprise`.
- [ ] Moneda confirmada en `COP` o ajustada segun operacion comercial.
- [ ] Copy comercial revisado para trial, vencimiento y upgrade.
- [ ] Responsable interno definido para aprobar extensiones de trial.
- [ ] Responsable interno definido para cambiar planes manualmente.
- [ ] Correo real configurado para solicitudes de upgrade o flujo alterno definido.
- [ ] Bandeja de solicitudes de upgrade validada con `company_admin` y `super_admin`.
- [ ] Politica interna definida para aprobar/rechazar solicitudes.
- [ ] `php artisan noiachat:subscriptions-check --dry-run` probado en staging.
- [ ] Cron productivo definido para `php artisan noiachat:subscriptions-check`.
- [ ] Auditoria de cambio de plan validada desde `/audit-logs`.
- [ ] Empresa de prueba registrada desde `/register` y revisada en `/billing`.
- [ ] Limites de usuarios, sedes, contactos y canales WhatsApp validados en staging.
- [ ] Plan vencido validado: login permitido, acciones operativas bloqueadas.
- [ ] Procedimiento de rollback comercial definido: volver a plan anterior o reactivar suscripcion.

## Checklist para alta de cliente

- [ ] Cliente registrado desde `/register` o creado por administracion.
- [ ] Empresa y sede inicial verificadas.
- [ ] Responsable con rol `company_admin` confirmado.
- [ ] Canal WhatsApp configurado o plan de configuracion acordado.
- [ ] Plan inicial revisado en `/billing`.
- [ ] Fecha de fin de trial comunicada al cliente.
- [ ] Limites del plan explicados al responsable.
- [ ] Acciones de upgrade explicadas al responsable.

## Checklist para cambio manual de plan

- [ ] Solicitud aprobada por comercial/soporte.
- [ ] Empresa correcta seleccionada en `/billing`.
- [ ] Plan anterior anotado.
- [ ] Plan nuevo seleccionado.
- [ ] Estado correcto seleccionado.
- [ ] Fechas revisadas.
- [ ] Cambio guardado.
- [ ] Auditoria revisada.
- [ ] Cliente informado.

## Checklist para solicitud de upgrade

- [ ] Empresa reviso catalogo en `/billing`.
- [ ] Solicitud creada con plan objetivo y motivo.
- [ ] Comercial/soporte valido cupos, precio y condiciones.
- [ ] `super_admin` aprobo o rechazo desde `/billing`.
- [ ] Auditoria revisada.
- [ ] Cliente informado del resultado.

## Riesgos y controles

| Riesgo | Control |
| --- | --- |
| Cambiar el plan de la empresa equivocada | Confirmar nombre, slug y responsable antes de guardar |
| Extender trial sin aprobacion | Exigir aprobacion comercial interna y revisar auditoria |
| Aprobar upgrade sin validacion comercial | Usar notas internas y politica de aprobacion antes de aprobar |
| Cliente supera limites despues de downgrade | No se borran datos; se bloquean nuevas creaciones hasta liberar cupo o subir plan |
| Trial vencido bloquea operacion critica | `super_admin` puede reactivar/ extender desde `/billing` |
| Cron no ejecuta vencimiento | Revisar dry-run periodico y monitor productivo |

## Evidencia de implementacion

- Panel: `/billing`
- Comando: `php artisan noiachat:subscriptions-check`
- Solicitudes: `subscription_change_requests`
- Pruebas: `tests/Feature/BillingPlansTest.php`
- Documentos relacionados:
  - `docs/planes-suscripciones-features.md`
  - `docs/funcionalidades.md`
  - `docs/plan-trabajo-reglas-negocio.md`
