# Ajustes UX operativos

Ultima actualizacion: 2026-06-24

Este documento resume los ajustes recientes de experiencia de usuario en las vistas operativas de NoiaChat. Complementa la matriz de funcionalidades y deja trazabilidad de decisiones visuales, archivos impactados y validaciones ejecutadas.

## Objetivo

Reducir friccion en pantallas de uso diario, aprovechar mejor el ancho disponible y mantener la navegacion estable mientras el usuario trabaja con vistas largas.

## Conversaciones

Rutas impactadas:

- `/conversations`
- `/conversations?conversation={id}`

Cambios aplicados:

- Layout especial `conversation-workspace` para usar mejor el ancho disponible.
- Inbox, chat activo, panel de detalles y composer dentro de una experiencia tipo WhatsApp.
- Panel de detalles colapsable en desktop y drawer en pantallas menores.
- Cabecera del chat mas limpia, evitando repetir nombre, telefono y canal cuando ya aparecen en detalles.
- Chip contextual de contacto/canal cuando el panel de detalles esta oculto.
- Burbujas de mensaje con ancho natural y maximo controlado para evitar franjas demasiado largas.
- Composer inferior compacto, con texto/adjunto cuando la ventana 24h esta abierta y plantillas cuando esta cerrada.
- Notificaciones de estado/error reemplazadas por SweetAlert2.

Archivos principales:

- `resources/views/components/layouts/conversation-workspace.blade.php`
- `resources/views/noia/conversations/index.blade.php`
- `resources/views/noia/conversations/show.blade.php`
- `resources/views/noia/conversations/partials/list.blade.php`
- `resources/views/noia/conversations/partials/panel.blade.php`
- `resources/views/noia/conversations/partials/details.blade.php`
- `resources/js/app.js`

## Detalle de contacto

Ruta impactada:

- `/contacts/{id}`

Cambios aplicados:

- Ficha principal compacta con avatar, estado, telefono, email y fecha de creacion.
- Metricas resumidas de consentimientos, bloqueos y mensajes.
- Columna izquierda sticky en desktop para conservar contexto mientras se revisan secciones largas.
- Tarjetas operativas separadas para fusion, consentimientos, lista de exclusion y mensajes.
- Estados vacios visibles para lista de exclusion y mensajes.
- Correccion de estiramiento visual: la tarjeta del contacto ya no toma la altura de la columna derecha.

Archivo principal:

- `resources/views/noia/contacts/show.blade.php`

## Layout principal

Rutas impactadas:

- Vistas que usan `x-layouts.noia`, excepto el workspace especial de conversaciones.

Cambios aplicados:

- Sidebar fijo en desktop.
- Header superior sticky.
- Scroll limitado al area principal de contenido.
- Comportamiento movil conservado en flujo normal.

Archivo principal:

- `resources/views/components/layouts/noia.blade.php`

## Integracion WhatsApp

Ruta impactada:

- `/integrations/whatsapp`

Cambios aplicados:

- Resumen superior con canales activos, canales en revision, mensajes y conversaciones.
- Tarjetas de canal enfocadas en estado, pendientes, credenciales clave, metricas y acciones.
- Checklist Meta lateral y compacto.
- Flujo recomendado en tarjeta lateral.
- Formulario de crear canal movido a modal.
- Formulario de editar configuracion movido a modal por canal.
- Modales con cierre por boton, clic fuera o tecla `Escape`, y scroll interno para formularios largos.

Archivo principal:

- `resources/views/noia/tenancy/whatsapp/index.blade.php`

## Validaciones

Validaciones ejecutadas durante estos ajustes:

- `npm run build`
- `php artisan test --filter=NoiaChatMvpTest`

Resultado esperado:

- Build frontend exitoso.
- `NoiaChatMvpTest` exitoso con 48 tests y 149 assertions.

## Pendientes recomendados

- Hacer revision visual en navegador con datos reales despues del despliegue.
- Capturar evidencia por breakpoint de `/conversations`, `/contacts/{id}` y `/integrations/whatsapp`.
- Ajustar copy o densidad visual con feedback de operadores reales.
