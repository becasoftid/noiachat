# Flujo operativo de conversaciones y envio

Este documento define el flujo recomendado para iniciar y atender conversaciones en NoiaChat. El objetivo es que la operacion diaria se haga desde una experiencia tipo WhatsApp, evitando que los usuarios comerciales trabajen desde formularios separados por tipo de mensaje.

## Decision funcional

La vista principal para enviar y responder mensajes debe ser `/conversations`.

La vista `/messages` se conserva como bitacora/listado de mensajes salientes y fallidos. La vista `/messages/create` queda como respaldo tecnico legado, pero el flujo comercial de **Nuevo envio** debe llevar al operador a iniciar o reutilizar un chat dentro de `/conversations`.

## Problema detectado

La pantalla `/messages/create` separa el envio en tres tarjetas independientes:

- Texto.
- Imagen.
- Documento.

Esa separacion obliga al usuario a elegir contacto y canal varias veces, no muestra historial, no muestra ventana de atencion WhatsApp y no expone de forma natural las plantillas aprobadas. Para un operador comercial, el flujo resulta menos parecido a WhatsApp y mas cercano a una consola tecnica.

## Flujo objetivo

1. El usuario entra a **Mensajes** o **Conversaciones**.
2. Si quiere iniciar una conversacion, usa **Nuevo chat** dentro del inbox de `/conversations`.
3. Selecciona contacto y canal.
4. NoiaChat reutiliza una conversacion abierta, pendiente o resuelta si existe.
5. Si no existe, NoiaChat crea una conversacion nueva.
6. El usuario queda en `/conversations?conversation={id}`.
7. Desde el panel de conversacion puede:
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
- Si existe una conversacion reutilizable para el mismo contacto y canal, se abre esa conversacion en lugar de crear duplicados.
- La conversacion reutilizable puede estar en estado `open`, `pending` o `resolved`.
- Las conversaciones cerradas no se reutilizan automaticamente.
- La ventana de atencion de WhatsApp sigue gobernando si se puede enviar texto libre o adjuntos.
- El envio por plantilla aprobada sigue disponible desde el panel de conversacion.

## Rutas

| Ruta | Uso |
| --- | --- |
| `GET /conversations` | Inbox operativo y panel de chat. |
| `POST /conversations/start` | Inicia o reutiliza una conversacion por contacto/canal. |
| `GET /conversations?conversation={id}` | Abre una conversacion dentro del inbox. |
| `GET /messages` | Bitacora/listado de mensajes salientes. |
| `GET /messages/create` | Respaldo tecnico legado para envio directo por tipo. |

## Criterios de aceptacion

- El inbox muestra una accion **Nuevo chat** para usuarios con permiso de envio.
- El formulario de nuevo chat permite seleccionar contacto y canal activo del tenant actual.
- Al enviar el formulario, se crea o reutiliza una conversacion y se redirige al panel del chat.
- El boton **Nuevo envio** de `/messages` abre `/conversations?new=1`.
- Un usuario sin permiso `messages.send` no puede iniciar conversaciones.
- No se crean conversaciones para contactos o canales fuera del tenant activo.
- Las pruebas automatizadas cubren creacion/reutilizacion del chat y el cambio del boton de nuevo envio.

## Evidencia esperada

- Pruebas en `tests/Feature/NoiaChatMvpTest.php`.
- Validacion manual:
  1. Ir a `/messages`.
  2. Clic en **Nuevo envio**.
  3. Confirmar que abre `/conversations?new=1`.
  4. Seleccionar contacto y canal.
  5. Confirmar que abre `/conversations?conversation={id}`.
  6. Enviar plantilla o texto segun ventana de 24h.

## Estado

MVP. El flujo principal queda unificado en conversaciones y la vista fue reorganizada para que la operacion diaria sea mas cercana a una bandeja tipo WhatsApp. Como mejora futura se puede reemplazar el selector de contacto por busqueda/autocomplete cuando haya alto volumen de contactos.
