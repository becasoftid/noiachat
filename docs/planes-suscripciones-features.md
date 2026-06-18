# Plan de trabajo: planes, suscripciones y funcionalidades

Ultima actualizacion: 2026-06-18

Este documento define la linea de trabajo para convertir el onboarding actual de prueba basica en un modelo SaaS real por empresa: planes comerciales, suscripciones, trial, limites y funcionalidades habilitadas por plan.

## Objetivo

Implementar planes comerciales por empresa, con suscripcion activa, periodo de prueba, limites y funcionalidades habilitadas por plan, independientes de los roles de usuario.

## Principio base

Toda accion relevante debe validar dos condiciones:

```text
Permiso por rol/membresia
+
Funcionalidad incluida en el plan de la empresa
=
Acceso permitido
```

Los roles responden que puede hacer un usuario dentro de su empresa. El plan responde que capacidades contrato o tiene habilitadas esa empresa.

Un rol nunca debe desbloquear funcionalidades que el plan no incluye. La unica excepcion prevista es el `super_admin` interno para soporte, auditoria tecnica o recuperacion operativa.

## BILLING-001 | Modelo de planes y suscripciones | P1

Objetivo: crear la base comercial persistente del producto.

Estado actual: implementado como MVP el 2026-06-17 con migracion, modelos, seeder de planes y registro publico creando `company_subscriptions`.

Tablas propuestas:

- `plans`
  - `id`
  - `code`
  - `name`
  - `description`
  - `price_cents`
  - `currency`
  - `billing_period`: `trial`, `monthly`, `yearly`
  - `trial_days`
  - `max_users`
  - `max_branches`
  - `max_contacts`
  - `max_whatsapp_channels`
  - `metadata` JSON
  - `is_active`
  - timestamps

- `company_subscriptions`
  - `id`
  - `company_id`
  - `plan_id`
  - `status`: `trialing`, `active`, `past_due`, `expired`, `cancelled`
  - `trial_started_at`
  - `trial_ends_at`
  - `current_period_started_at`
  - `current_period_ends_at`
  - `cancelled_at`
  - `metadata` JSON
  - timestamps

Entregables:

- Migraciones.
- Modelos y relaciones.
- Seeder de planes iniciales: `basic_trial`, `basic`, `pro`, `enterprise`.
- Registro publico usando `plans` y `company_subscriptions`, no metadata suelta en `companies.settings`.
- Pruebas de creacion de trial al registrar empresa.

Criterios de aceptacion:

- Toda empresa nueva queda asociada a una suscripcion.
- El trial de una empresa se consulta desde `company_subscriptions`.
- Los limites base se consultan desde `plans`.
- El registro no depende de `.env` como fuente principal de negocio.

## BILLING-002 | Catalogo de funcionalidades | P1

Objetivo: definir que actividades o funciones puede habilitar cada plan.

Estado actual: implementado como MVP el 2026-06-17 con migracion, modelos, seeder de features y matriz inicial por plan.

Tablas propuestas:

- `features`
  - `id`
  - `code`
  - `name`
  - `description`
  - `module`
  - `is_active`
  - timestamps

- `plan_features`
  - `plan_id`
  - `feature_id`
  - `enabled`
  - `limits` JSON opcional
  - timestamps

Features iniciales sugeridas:

- `contacts.create`
- `contacts.import`
- `contacts.merge`
- `conversations.inbox`
- `conversations.assignment`
- `whatsapp.text`
- `whatsapp.media`
- `whatsapp.templates`
- `reports.dashboard`
- `reports.export`
- `audit.view`
- `audit.detail`
- `settings.whatsapp_channel`
- `users.manage`
- `branches.manage`
- `health.view`
- `api.access`

Entregables:

- Migraciones.
- Modelos.
- Seeder de features.
- Seeder de matriz plan-feature.
- Pruebas de que cada plan tiene las features correctas.

Criterios de aceptacion:

- Cada funcionalidad comercial relevante tiene un `feature.code` estable.
- Cada plan puede habilitar o deshabilitar features sin cambiar codigo.
- La matriz plan-feature puede probarse automaticamente.

## BILLING-003 | Servicio de evaluacion del plan | P1

Objetivo: centralizar la validacion de suscripcion, funcionalidades y limites.

Estado actual: implementado como MVP el 2026-06-17 con `SubscriptionFeatureService`, evaluacion de suscripcion operativa, features incluidas, limites y dias restantes de trial.

Servicio sugerido:

```php
SubscriptionFeatureService::allows($company, 'reports.export');
SubscriptionFeatureService::limit($company, 'contacts.max');
SubscriptionFeatureService::subscription($company);
```

Reglas:

- Si la empresa no tiene suscripcion activa o en trial vigente, bloquear funciones operativas.
- Si el trial vencio, bloquear funciones operativas y permitir acceso a configuracion/facturacion.
- `super_admin` puede tener bypass interno.
- `company_admin` no puede saltarse limites del plan.
- Los roles siguen existiendo, pero no reemplazan al plan.

Entregables:

- Servicio de evaluacion de suscripcion/features.
- Metodos para validar feature y limites.
- Manejo consistente de estados `trialing`, `active`, `expired`, `cancelled`, `past_due`.
- Tests unitarios y feature.

Criterios de aceptacion:

- La aplicacion tiene un unico punto confiable para preguntar si una empresa tiene acceso a una feature.
- Los errores por plan son distinguibles de errores por rol.
- Un plan vencido no bloquea login, pero si acciones operativas.

## BILLING-004 | Middleware y policies por feature | P1

Objetivo: aplicar restricciones de plan en rutas y acciones clave.

Estado actual: implementado como MVP el 2026-06-17 con middleware `feature`, bypass de `super_admin` y proteccion inicial en usuarios, sedes/membresias y settings/plantillas.

Middleware sugerido:

```php
->middleware('feature:reports.export')
```

Validacion alternativa en controladores o policies:

```php
abort_unless($subscriptionFeatures->allows($company, 'contacts.import'), 403);
```

Primeras funciones a proteger:

- Importar contactos.
- Fusionar contactos.
- Exportar reportes.
- Enviar multimedia.
- Enviar plantillas.
- Administrar usuarios.
- Administrar sedes.
- Administrar canal WhatsApp.
- Ver health panel.

Entregables:

- Middleware `EnsureFeatureEnabled`.
- Uso en rutas/controladores clave.
- Mensaje claro cuando el plan no incluye una funcion.
- Tests por combinacion rol + plan.

Criterios de aceptacion:

- Un usuario con rol suficiente queda bloqueado si su plan no incluye la feature.
- Un usuario sin rol suficiente queda bloqueado aunque su plan incluya la feature.
- Las respuestas de error son comprensibles para usuario final.

## BILLING-005 | Limites por plan | P1

Objetivo: hacer cumplir limites numericos del plan.

Estado actual: implementado como MVP el 2026-06-18 con `PlanLimitService`, validacion antes de crear usuarios, sedes y contactos, y validacion antes de activar canales WhatsApp adicionales.

Limites iniciales:

- Usuarios por empresa.
- Sedes.
- Contactos.
- Canales WhatsApp.
- Mensajes mensuales, opcional en fase posterior.

Reglas:

- Validar antes de crear.
- No borrar ni bloquear datos existentes si se reduce plan.
- Mostrar error operativo: "Tu plan actual permite hasta X sedes".
- Permitir que `super_admin` haga correcciones administrativas si el negocio lo requiere.

Entregables:

- Servicio de limites: implementado.
- Validacion en creacion de usuarios, sedes, contactos y activacion de canales: implementada.
- Tests de limite alcanzado: implementados para usuarios, sedes, contactos y canales WhatsApp.
- Mensajes de error claros: implementados en formularios/controladores.

Criterios de aceptacion:

- No se puede superar el limite de usuarios, sedes, contactos o canales desde UI/controladores.
- Los limites se leen del plan activo de la empresa.
- La reduccion de plan no elimina datos existentes.

## BILLING-006 | Panel de plan y suscripcion | P2

Objetivo: hacer visible el estado comercial de la empresa.

Estado actual: implementado como MVP el 2026-06-18 con panel `/billing`, resumen de plan/estado/dias restantes, limites usados/disponibles, features incluidas y administracion manual de suscripciones para `super_admin`.

Para `company_admin`:

- Ver plan actual.
- Ver estado: prueba, activo, vencido.
- Ver dias restantes.
- Ver limites usados / disponibles.
- Ver funcionalidades incluidas.
- CTA "Solicitar upgrade" o "Cambiar plan" si aun no hay pasarela.

Para `super_admin`:

- Ver empresas y sus planes.
- Cambiar plan manualmente.
- Extender trial.
- Cancelar/reactivar suscripcion.
- Activar/desactivar features por plan.

Entregables:

- Vista de plan para empresa: implementada.
- Vista administrativa global para `super_admin`: implementada.
- Acciones manuales de cambio/extension: implementadas.
- Auditoria de cambios de plan: implementada.

Criterios de aceptacion:

- Un `company_admin` entiende que plan tiene y que limites consume.
- Un `super_admin` puede corregir o extender una suscripcion sin tocar base de datos.
- Todo cambio sensible de plan queda auditado.

## BILLING-007 | Vencimiento de trial y estados | P1

Objetivo: automatizar el ciclo de vida del trial.

Estado actual: implementado como MVP el 2026-06-18 con `SubscriptionLifecycleService`, comando `noiachat:subscriptions-check`, auditoria de expiracion, banner de trial vencido/proximo a vencer y bloqueo operativo por middleware.

Comando/job sugerido:

```bash
php artisan noiachat:subscriptions-check
```

Responsabilidades:

- Detectar trials vencidos.
- Cambiar estado a `expired`.
- Registrar auditoria.
- Opcionalmente enviar correo.

Comportamiento cuando vence:

- Permitir login.
- Permitir ver configuracion/facturacion.
- Bloquear acciones operativas criticas.
- Mostrar aviso claro en dashboard.

Entregables:

- Comando/job de verificacion: implementado como `php artisan noiachat:subscriptions-check`.
- Modo de simulacion: implementado con `--dry-run`.
- Tests de trial vencido: implementados en `tests/Feature/BillingPlansTest.php`.
- Banner o alerta de plan vencido: implementado en layout operativo.
- Documentacion operativa: registrada en matriz y changelog.

Criterios de aceptacion:

- Una suscripcion `trialing` vencida pasa a `expired`.
- La empresa vencida conserva sus datos.
- Los usuarios entienden que deben cambiar o renovar plan para continuar operando.

## BILLING-008 | Documentacion y checklist comercial | P2

Objetivo: dejar el modelo mantenible y operable.

Estado actual: implementado como MVP el 2026-06-18 en `docs/billing-checklist-comercial.md`, con matriz comercial de planes/features, estados de suscripcion, procedimientos y checklists de validacion.

Entregables:

- Matriz de planes y features: documentada.
- Documentacion de estados de suscripcion: documentada.
- Manual para extender trial/cambiar plan: documentado.
- Checklist de validacion comercial antes de produccion: documentado.

Criterios de aceptacion:

- El equipo puede explicar que incluye cada plan.
- El equipo puede operar cambios manuales de plan sin improvisar.
- La documentacion refleja los estados reales del sistema.

## Orden recomendado

La linea `BILLING-001` a `BILLING-008` queda implementada como MVP. El siguiente paso no es una tarea de codigo de billing, sino validacion comercial/productiva: tarifas reales, copy, cron de vencimiento, flujo de upgrade y pruebas en staging.

## Resultado esperado

NoiaChat queda listo para operar como SaaS multiempresa: cada empresa tiene un plan, una suscripcion, un trial, limites y funcionalidades habilitadas, independientemente del rol de sus usuarios.

## Fase profesional de billing

Esta fase eleva el MVP de billing hacia una operacion SaaS comercial mas completa. No reemplaza `BILLING-001` a `BILLING-008`; los toma como base.

| ID | Tarea | Prioridad | Estado |
| --- | --- | --- | --- |
| BILLING-PRO-001 | Tarifas reales y catalogo comercial | P1 | Implementado como MVP: catalogo comparativo en `/billing`, metadata comercial por plan y orden destacado. Pendiente definir tarifas reales en `price_cents`. |
| BILLING-PRO-002 | Flujo real de upgrade | P1 | Implementado como MVP: solicitudes persistidas, bandeja para `super_admin`, aprobacion/rechazo, cambio de plan y auditoria. |
| BILLING-PRO-003 | Pasarela de pagos | P1 | Pendiente: elegir proveedor e integrar cobros, renovaciones y pagos fallidos. |
| BILLING-PRO-004 | Facturacion y recibos | P2 | Pendiente: historial de pagos, datos fiscales y comprobantes. |
| BILLING-PRO-005 | Cron productivo y alertas de suscripcion | P1 | Pendiente: configurar cron productivo, alertas previas y monitoreo. |
| BILLING-PRO-006 | Notificaciones comerciales | P1 | Pendiente: emails o alertas por trial proximo a vencer, vencido, upgrade aprobado/rechazado y pago pendiente. |
| BILLING-PRO-007 | UI avanzada de billing | P2 | Pendiente: timeline, historial, comparacion mas completa y estados comerciales refinados. |

Siguiente paso recomendado: `BILLING-PRO-003` solo despues de decidir proveedor de pagos y tarifas reales. Si todavia no hay proveedor, avanzar con `BILLING-PRO-005` y `BILLING-PRO-006` para dejar alertas y comunicacion comercial.
