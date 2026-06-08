# Gestion documental de funcionalidades NoiaChat

Este documento define como llevar el control documental de las funcionalidades de NoiaChat, su estado, prioridad, riesgos, criterios de aceptacion y evidencias.

La gestion documental se apoya en tres archivos:

- [Matriz de funcionalidades](funcionalidades.md): inventario vivo de modulos, funcionalidades, estado y siguientes acciones.
- [Manual de integracion WhatsApp](integracion-whatsapp.md): guia operativa para conectar, probar y diagnosticar WhatsApp Cloud API.
- [Plantilla de funcionalidad](plantilla-funcionalidad.md): formato para documentar nuevas funcionalidades, cambios o mejoras.

## Objetivo

Mantener una fuente unica de verdad sobre:

- Que funcionalidades existen.
- Cuales estan listas para operar.
- Cuales estan en MVP y requieren endurecimiento.
- Cuales estan pendientes, bloqueadas o en revision.
- Que pruebas o evidencias respaldan cada estado.
- Que tareas siguen para llevar el proyecto a produccion confiable.

## Estados permitidos

Usa estos estados de forma consistente:

| Estado | Significado |
| --- | --- |
| `Operativo` | Funciona en produccion o entorno real y fue validado con pruebas manuales o automatizadas. |
| `MVP` | Funciona para el flujo principal, pero requiere mejoras antes de considerarse robusto. |
| `En progreso` | Se esta implementando o ajustando. |
| `Pendiente` | Aun no se ha iniciado. |
| `Bloqueado` | No puede avanzar sin decision, credencial, proveedor, aprobacion o insumo externo. |
| `En revision` | Implementado, pendiente de QA, aprobacion o validacion operativa. |
| `Descartado` | Ya no se considera necesario o fue reemplazado. |

## Prioridades

| Prioridad | Uso |
| --- | --- |
| `P0` | Critico para operar sin romper flujos principales, seguridad o cumplimiento. |
| `P1` | Importante para eficiencia operativa, control y confiabilidad. |
| `P2` | Mejora deseable, reportes, optimizacion o experiencia avanzada. |
| `P3` | Idea futura o mejora no urgente. |

## Ciclo de control

1. Registrar la funcionalidad en `docs/funcionalidades.md`.
2. Definir estado inicial, prioridad y criterio de aceptacion.
3. Asociar evidencias: prueba automatizada, prueba manual, captura, log, commit o URL.
4. Actualizar el estado al cerrar una tarea.
5. Registrar cambios relevantes en `CHANGELOG.md`.
6. Si la funcionalidad requiere instrucciones operativas, crear o actualizar un manual en `docs/`.

## Reglas de mantenimiento

- No dejar funcionalidades nuevas sin estado.
- No marcar `Operativo` sin evidencia.
- No marcar `MVP` si el flujo principal no funciona.
- Toda integracion externa debe documentar credenciales necesarias, errores comunes y prueba de validacion.
- Todo cambio de seguridad, webhook, token, despliegue o cola debe registrarse en changelog.
- Las tareas P0 deben revisarse antes de iniciar mejoras P2/P3.

## Evidencias recomendadas

| Tipo | Ejemplo |
| --- | --- |
| Commit | `259b889 Registrar errores de proveedor WhatsApp como fallidos` |
| Prueba automatizada | `composer test`, `44 passed` |
| Prueba manual | Mensaje entrante y respuesta real por WhatsApp |
| Log | `provider_logs`, `message_events`, `audit_logs` |
| URL | `/conversations`, `/audit-logs`, `/contacts` |
| Documento | `docs/integracion-whatsapp.md` |

## Revision sugerida

- Revision rapida semanal: actualizar estados y riesgos.
- Revision de release: antes de publicar cambios en produccion.
- Revision post-incidente: despues de errores de token, webhook, colas o proveedor.

## Documentos relacionados

- [README](../README.md)
- [Changelog](../CHANGELOG.md)
- [Matriz de funcionalidades](funcionalidades.md)
- [Manual de integracion WhatsApp](integracion-whatsapp.md)
- [Plantilla de funcionalidad](plantilla-funcionalidad.md)
