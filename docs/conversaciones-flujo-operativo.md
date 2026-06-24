# Flujo operativo de conversaciones y envio

Este documento define el flujo recomendado para iniciar y atender conversaciones en NoiaChat. El objetivo es que la operacion diaria se haga desde una experiencia tipo WhatsApp, evitando que los usuarios comerciales trabajen desde formularios separados por tipo de mensaje.

## Decision funcional

La vista principal para enviar y responder mensajes debe ser `/conversations`.

La vista `/contacts` es el punto recomendado para iniciar una conversacion cuando el operador ya sabe a que persona necesita escribir. La vista `/messages` se conserva como bitacora/listado de mensajes salientes y fallidos. La vista `/messages/create` queda como respaldo tecnico legado, pero la operacion comercial diaria debe terminar siempre en `/conversations`.

## Problema detectado

La pantalla `/messages/create` separa el envio en tres tarjetas independientes:

- Texto.
- Imagen.
- Documento.

Esa separacion obliga al usuario a elegir contacto y canal varias veces, no muestra historial, no muestra ventana de atencion WhatsApp y no expone de forma natural las plantillas aprobadas. Para un operador comercial, el flujo resulta menos parecido a WhatsApp y mas cercano a una consola tecnica.

## Flujo objetivo

1. El usuario entra a **Contactos**.
2. Ubica el contacto por nombre, telefono o email.
3. Usa el icono **Ver** para consultar el detalle del contacto.
4. Usa el icono **Enviar mensaje** para abrir la conversacion operativa.
5. Si el contacto tiene un canal principal activo o solo existe un canal activo en la empresa/sede, NoiaChat abre directamente la conversacion.
6. Si hay varios canales posibles sin canal principal, NoiaChat abre `/conversations?new=1&contact_id={id}` con el contacto preseleccionado para que el operador elija canal.
7. NoiaChat reutiliza una conversacion abierta, pendiente o resuelta si existe.
8. Si no existe, NoiaChat crea una conversacion nueva.
9. El usuario queda en `/conversations?conversation={id}`.
10. Desde el panel de conversacion puede:
   - Responder con texto libre si la ventana de 24h lo permite.
   - Enviar una plantilla aprobada si la ventana de 24h esta cerrada.
   - Enviar adjuntos cuando el flujo de compliance lo permita.

## Organizacion visual del panel

La vista `/conversations` queda organizada como una bandeja operativa tipo WhatsApp. Los ajustes visuales recientes tambien estan resumidos en `docs/ux-ajustes-operativos.md`.

- Columna izquierda de inbox con titulo, contador de no leidos, busqueda por nombre/telefono, filtros rapidos, accion **Nuevo chat**, filtros avanzados y exportacion.
- Lista de conversaciones con avatar, contacto, telefono, canal, vista previa, hora, contador de no leidos, responsable y estado de ventana.
- Conversacion activa resaltada para evitar dudas cuando hay varios contactos visibles.
- Cabecera compacta con contexto de responsable, estado y ventana 24h. La informacion repetida de contacto/canal se evita cuando ya esta visible en el panel de detalles.
- Historial con scroll independiente, divisores de fecha, burbujas entrantes/salientes, eventos del sistema, estados de entrega/lectura y errores tecnicos cuando existan.
- Compositor inferior inteligente que muestra solo el formulario permitido por el estado de la ventana.
- Panel lateral de detalles colapsable en escritorio con contacto, canal, responsable, ultima actividad, estado del canal, estado de ventana y acceso al perfil completo.
- En pantallas menores a escritorio amplio, los detalles se abren como drawer para evitar scroll horizontal.

### Ventana de atencion y composer

La ventana de 24h se muestra como una sola fuente de verdad calculada en backend.

Cuando la ventana esta abierta:

- Se muestra el mensaje **Ventana de atencion abierta** con hora limite cuando esta disponible.
- El operador puede responder con texto libre.
- El operador puede alternar a respuesta con adjunto para imagen o documento, respetando las validaciones existentes.

Cuando la ventana esta cerrada:

- Se muestra una franja ambar con **Ventana 24h cerrada** y la indicacion de usar plantilla aprobada.
- El campo de texto libre queda oculto/deshabilitado.
- Se muestra el composer de plantilla con selector, campos individuales para variables requeridas, vista previa y boton **Enviar plantilla**.
- Las variables se envian al backend usando el mismo campo `variables` existente, sin cambiar el contrato del endpoint.

## Reglas de negocio

- El inicio de chat respeta empresa y sede activa.
- Solo usuarios con permiso `messages.send` pueden iniciar conversaciones.
- El contacto debe pertenecer al tenant activo.
- El canal debe estar activo y pertenecer al tenant activo.
- Desde `/contacts`, el icono **Enviar mensaje** usa el canal principal del contacto cuando existe.
- Si no hay canal principal, usa el unico canal activo disponible.
- Si hay mas de un canal activo y no se puede inferir uno, el sistema lleva al operador a `/conversations?new=1&contact_id={id}` para completar el canal.
- Si existe una conversacion reutilizable para el mismo contacto y canal, se abre esa conversacion en lugar de crear duplicados.
- La conversacion reutilizable puede estar en estado `open`, `pending` o `resolved`.
- Las conversaciones cerradas no se reutilizan automaticamente.
- La ventana de atencion de WhatsApp sigue gobernando si se puede enviar texto libre o adjuntos.
- El envio por plantilla aprobada sigue disponible desde el panel de conversacion.
- Los filtros rapidos **Todos**, **Mios**, **No asignados** y **No leidos** se aplican sobre el mismo alcance de empresa/sede activo.
- El panel de detalles no crea datos nuevos; solo muestra informacion disponible del contacto, la conversacion y el canal.
- La UI no inventa etiquetas, notas ni nombres de variables que no existan en el modelo actual.

## Rutas

| Ruta | Uso |
| --- | --- |
| `GET /contacts` | Listado operativo para buscar contacto y abrir detalle o conversacion. |
| `GET /conversations` | Inbox operativo y panel de chat. |
| `POST /conversations/start` | Inicia o reutiliza una conversacion por contacto/canal. |
| `GET /conversations?conversation={id}` | Abre una conversacion dentro del inbox. |
| `GET /conversations?new=1&contact_id={id}` | Abre el formulario de nuevo chat con contacto preseleccionado cuando se debe escoger canal. |
| `GET /messages` | Bitacora/listado de mensajes salientes. |
| `GET /messages/create` | Respaldo tecnico legado para envio directo por tipo. |

## Criterios de aceptacion

- El inbox muestra una accion **Nuevo chat** para usuarios con permiso de envio.
- El listado de contactos muestra acciones por icono: **Ver** y **Enviar mensaje**.
- El icono **Enviar mensaje** abre directamente la conversacion cuando el canal es determinable.
- Si el canal no es determinable, el operador llega al formulario de nuevo chat con el contacto preseleccionado.
- El formulario de nuevo chat permite seleccionar contacto y canal activo del tenant actual.
- Al enviar el formulario, se crea o reutiliza una conversacion y se redirige al panel del chat.
- El boton **Nuevo envio** de `/messages` se mantiene como acceso secundario hacia `/conversations?new=1`.
- Un usuario sin permiso `messages.send` no puede iniciar conversaciones.
- No se crean conversaciones para contactos o canales fuera del tenant activo.
- Las pruebas automatizadas cubren creacion/reutilizacion del chat y el cambio del boton de nuevo envio.
- El redisenio de `/conversations` conserva los endpoints actuales de responder texto, responder adjunto, responder plantilla, asignacion y cambio de estado.
- La pantalla no muestra simultaneamente compositor de texto libre y compositor de plantilla.
- Los bloqueos por politica se muestran como tarjetas de sistema legibles dentro del historial.
- La vista de detalles es lateral en escritorio amplio y drawer en pantallas menores.

## Evidencia esperada

- Pruebas en `tests/Feature/NoiaChatMvpTest.php`.
- Build frontend con `npm run build`.
- Suite completa con `php artisan test`.
- Validacion manual:
  1. Ir a `/contacts`.
  2. Buscar un contacto.
  3. Clic en el icono **Ver** y confirmar que abre el detalle.
  4. Volver a `/contacts`.
  5. Clic en el icono **Enviar mensaje**.
  6. Confirmar que abre `/conversations?conversation={id}` cuando hay canal directo.
  7. Confirmar que abre `/conversations?new=1&contact_id={id}` cuando se debe elegir canal.
  8. Enviar plantilla o texto segun ventana de 24h.
  9. Confirmar que el inbox no presenta scroll horizontal en escritorio y tablet.
  10. Confirmar que una conversacion sin ventana 24h muestra solo composer de plantilla.
  11. Confirmar que una conversacion con ventana abierta muestra texto libre y adjunto.

## Implementacion UX/UI 2026-06-22

Se implemento el redisenio de `/conversations` sobre Blade, Tailwind CSS y Alpine.js sin incorporar frameworks adicionales ni modificar base de datos.

Archivos funcionales ajustados:

- `app/Modules/Conversations/Presentation/Controllers/ConversationController.php`.
- `app/Modules/Conversations/Infrastructure/Persistence/Repositories/EloquentConversationRepository.php`.
- `resources/views/noia/conversations/index.blade.php`.
- `resources/views/noia/conversations/show.blade.php`.
- `resources/views/noia/conversations/partials/list.blade.php`.
- `resources/views/noia/conversations/partials/panel.blade.php`.
- `resources/views/noia/conversations/partials/details.blade.php`.
- `resources/js/app.js`.

Componentes/partials relevantes:

- Inbox operativo.
- Lista de conversaciones.
- Panel de conversacion activa.
- Panel de detalles del contacto.
- Composer inteligente de texto/adjunto/plantilla.
- Composer Alpine para variables de plantilla y vista previa.

Validaciones ejecutadas:

- `php artisan test --filter=NoiaChatMvpTest`: exitoso.
- `npm run build`: exitoso.
- `php artisan test`: exitoso.
- `./vendor/bin/pint --test` global: pendiente por deuda historica de formato en archivos no relacionados; los archivos PHP modificados del modulo Conversaciones pasan Pint.

Limitaciones conscientes:

- Los nombres de variables de plantilla se muestran como `Variable 1`, `Variable 2`, etc. porque la sincronizacion actual conserva placeholders numericos de Meta (`{{1}}`, `{{2}}`) y no nombres semanticos.
- El envio de adjuntos dentro de plantillas no se habilito porque el endpoint actual de plantilla no soporta archivo sin cambiar contrato backend.
- El menu global conserva rutas reales existentes; no se agregaron enlaces falsos para secciones del mockup que aun no tienen ruta propia.

## Estado

Operativo. El flujo principal queda centrado en contactos para iniciar la conversacion y en conversaciones para atenderla. La vista fue reorganizada para que la operacion diaria sea mas cercana a una bandeja tipo WhatsApp, con inbox, conversacion activa, detalles laterales y composer segun ventana de atencion. Como mejora futura se puede reemplazar el selector de contacto por busqueda/autocomplete cuando haya alto volumen de contactos.
