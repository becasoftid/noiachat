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

- La lista lateral muestra contacto, vista previa, hora, contador de no leidos, canal y usuario asignado.
- El chat activo se resalta para evitar dudas cuando hay varios contactos visibles.
- La cabecera del panel muestra contacto, telefono, canal, responsable y estado operativo.
- El historial mantiene burbujas entrantes/salientes, errores de Meta y estados de entrega/lectura.
- El compositor inferior separa la respuesta rapida de texto, los adjuntos y las plantillas aprobadas.
- Cuando la ventana de 24h esta cerrada, texto y adjuntos quedan deshabilitados visualmente y la accion de plantilla queda destacada.

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

## Evidencia esperada

- Pruebas en `tests/Feature/NoiaChatMvpTest.php`.
- Validacion manual:
  1. Ir a `/contacts`.
  2. Buscar un contacto.
  3. Clic en el icono **Ver** y confirmar que abre el detalle.
  4. Volver a `/contacts`.
  5. Clic en el icono **Enviar mensaje**.
  6. Confirmar que abre `/conversations?conversation={id}` cuando hay canal directo.
  7. Confirmar que abre `/conversations?new=1&contact_id={id}` cuando se debe elegir canal.
  8. Enviar plantilla o texto segun ventana de 24h.

## Estado

MVP. El flujo principal queda centrado en contactos para iniciar la conversacion y en conversaciones para atenderla. La vista fue reorganizada para que la operacion diaria sea mas cercana a una bandeja tipo WhatsApp. Como mejora futura se puede reemplazar el selector de contacto por busqueda/autocomplete cuando haya alto volumen de contactos.
