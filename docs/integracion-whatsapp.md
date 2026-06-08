# Manual de integracion WhatsApp Cloud API para NoiaChat

Este manual documenta el proceso paso a paso para conectar NoiaChat con WhatsApp Cloud API de Meta, desde la configuracion inicial en Meta Developers hasta las pruebas de recepcion, envio, estados y diagnostico de errores.

> Importante: no pegues tokens de acceso en capturas, chats, commits ni documentos publicos. Si un token queda expuesto, revocalo en Meta y genera uno nuevo.

## 1. Requisitos previos

Antes de iniciar confirma que tienes:

- Acceso administrativo al repositorio y servidor donde corre NoiaChat.
- Acceso a Meta Developers.
- Una app creada en Meta Developers.
- Un numero de WhatsApp Business de prueba o produccion.
- Dominio publico con HTTPS para el webhook de NoiaChat.
- Aplicacion NoiaChat publicada y accesible.

En este proyecto se uso:

```text
URL publica: https://noiachat.saludlegal360.com
Webhook: https://noiachat.saludlegal360.com/webhooks/whatsapp
```

## 2. Crear o seleccionar la app en Meta Developers

1. Ingresa a `https://developers.facebook.com/apps/`.
2. Crea una app nueva o selecciona la app existente de NoiaChat.
3. En casos de uso, selecciona **Conecta con los clientes a traves de WhatsApp**.
4. Completa los pasos de registro de Meta Developers si Meta los solicita.
5. En el panel de la app, entra a la seccion de WhatsApp.

## 3. Obtener datos de WhatsApp Cloud API

En **WhatsApp > Configuracion de la API** identifica estos datos:

- Version de Graph API, por ejemplo `v25.0`.
- Identificador del numero de telefono.
- Identificador de la cuenta de WhatsApp Business.
- Token de acceso.

En el servidor esos datos se configuran como variables de entorno:

```env
WHATSAPP_API_BASE_URL=https://graph.facebook.com/v25.0
WHATSAPP_ACCESS_TOKEN=TOKEN_DE_META
WHATSAPP_PHONE_NUMBER_ID=ID_DEL_NUMERO
WHATSAPP_BUSINESS_ACCOUNT_ID=ID_DE_LA_CUENTA_WHATSAPP_BUSINESS
WHATSAPP_WEBHOOK_VERIFY_TOKEN=noiachat_webhook_2026
WHATSAPP_APP_SECRET=APP_SECRET_DE_META
```

El `WHATSAPP_WEBHOOK_VERIFY_TOKEN` no lo entrega Meta. Lo defines tu y debe coincidir exactamente entre NoiaChat y Meta.

## 4. Configurar variables en produccion

Entra al servidor:

```bash
cd /var/www/noiachat
nano .env
```

Configura o actualiza:

```env
WHATSAPP_API_BASE_URL=https://graph.facebook.com/v25.0
WHATSAPP_ACCESS_TOKEN=TOKEN_DE_META
WHATSAPP_PHONE_NUMBER_ID=ID_DEL_NUMERO
WHATSAPP_BUSINESS_ACCOUNT_ID=ID_DE_LA_CUENTA_WHATSAPP_BUSINESS
WHATSAPP_WEBHOOK_VERIFY_TOKEN=noiachat_webhook_2026
WHATSAPP_APP_SECRET=APP_SECRET_DE_META
```

Guarda el archivo y limpia/cachea configuracion:

```bash
php8.4 artisan optimize:clear
php8.4 artisan config:cache
php8.4 artisan queue:restart
```

`WHATSAPP_APP_SECRET` activa la validacion de `X-Hub-Signature-256` en los POST del webhook. Si esta variable esta vacia, NoiaChat no bloqueara el POST por firma para mantener compatibilidad en entornos locales.

## 5. Validar manualmente el webhook

Antes de guardar el webhook en Meta, prueba que NoiaChat responde el challenge.

Abre esta URL en el navegador, cambiando el token si usaste otro:

```text
https://noiachat.saludlegal360.com/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=noiachat_webhook_2026&hub.challenge=12345
```

Resultado esperado:

```text
12345
```

Si no responde `12345`, revisa:

- Que `WHATSAPP_WEBHOOK_VERIFY_TOKEN` coincida.
- Que el dominio tenga HTTPS valido.
- Que el deploy este actualizado.
- Que la configuracion de Laravel no este cacheada con valores viejos.

Comandos utiles:

```bash
cd /var/www/noiachat
git log -1 --oneline
php8.4 artisan optimize:clear
php8.4 artisan config:cache
```

## 6. Registrar webhook en Meta

En Meta Developers:

1. Ve a **WhatsApp > Configuracion**.
2. Busca la seccion **Webhook**.
3. En **URL de devolucion de llamada** coloca:

```text
https://noiachat.saludlegal360.com/webhooks/whatsapp
```

4. En **Token de verificacion** coloca:

```text
noiachat_webhook_2026
```

5. Clic en **Verificar y guardar**.

Si Meta valida correctamente, el webhook queda activo.

## 7. Suscribir campos del webhook

En la misma seccion de webhook, suscribe como minimo:

```text
messages
```

Este campo permite recibir:

- Mensajes entrantes.
- Cambios de estado de mensajes salientes.
- Eventos de lectura, entrega y envio.

Para probar desde Meta:

1. Busca el campo `messages`.
2. Clic en **Probar**.
3. En el modal, clic en **Enviar al servidor v25.0**.

## 8. Configurar destinatarios permitidos en modo prueba

Si usas el numero de prueba de WhatsApp Cloud API, Meta solo permite enviar mensajes a telefonos autorizados.

En **WhatsApp > Configuracion de la API**:

1. Busca la seccion **Para** o **Selecciona un numero de telefono destinatario**.
2. Agrega el numero del destinatario con codigo de pais.
3. Confirma el codigo que envia Meta.

Ejemplo para Colombia:

```text
+57 302 801 8618
```

Si el destinatario no esta autorizado, Meta puede responder:

```text
(#131030) Recipient phone number not in allowed list
```

## 9. Crear token con permisos correctos

Para enviar mensajes el token debe tener permisos adecuados. Si Meta responde:

```text
(#131005) Access denied
```

significa que el token no tiene permisos validos, expiro o no tiene acceso al activo correcto.

Permisos requeridos:

```text
whatsapp_business_messaging
whatsapp_business_management
```

Para un token permanente:

1. Ve a **Business Settings**.
2. Entra a **Usuarios > Usuarios del sistema**.
3. Crea o selecciona un usuario del sistema.
4. Asignale activos:
   - App NoiaChat.
   - Cuenta de WhatsApp Business.
   - Numero de WhatsApp.
5. Genera un token con permisos:
   - `whatsapp_business_messaging`
   - `whatsapp_business_management`
6. Copia el token y actualiza `WHATSAPP_ACCESS_TOKEN` en `.env`.
7. Ejecuta:

```bash
php8.4 artisan optimize:clear
php8.4 artisan config:cache
php8.4 artisan queue:restart
```

## 10. Probar recepcion de mensajes

Desde WhatsApp envia un mensaje al numero conectado a Meta.

En NoiaChat debe ocurrir lo siguiente:

1. El webhook recibe el evento.
2. Se crea o relaciona el contacto por telefono.
3. Se crea o actualiza una conversacion.
4. El mensaje aparece en **Conversaciones**.

Puedes validar en base de datos:

```bash
php8.4 artisan tinker
```

```php
DB::table('inbound_messages')->latest()->first();
DB::table('conversations')->latest('last_message_at')->first();
```

El mensaje entrante debe tener:

```text
contact_id: valor no nulo
conversation_id: valor no nulo
```

## 11. Otorgar consentimiento antes de responder

NoiaChat bloquea mensajes salientes si el contacto no tiene consentimiento activo.

Si intentas responder sin consentimiento, el mensaje puede quedar como:

```text
blocked_by_policy
blocked_no_consent
```

Para otorgarlo desde la interfaz:

1. Ve a **Contactos**.
2. Abre el contacto.
3. En **Consentimientos**, selecciona canal **WhatsApp**.
4. Selecciona origen, por ejemplo **WhatsApp**.
5. Clic en **Otorgar**.

Luego vuelve a la conversacion y envia una nueva respuesta.

## 12. Probar envio de respuestas

En NoiaChat:

1. Ve a **Conversaciones**.
2. Abre la conversacion.
3. Escribe en **Respuesta de texto**.
4. Clic en **Enviar respuesta**.

Si el worker no esta corriendo de forma permanente, procesa la cola manualmente:

```bash
cd /var/www/noiachat
php8.4 artisan queue:work --once --verbose
```

Para un entorno productivo se recomienda usar Supervisor o systemd para mantener el worker activo.

## 13. Validar estados de envio y lectura

Despues de enviar, revisa en NoiaChat:

- `En cola`
- `Enviando`
- `Enviado`
- `Entregado`
- `Leido`
- `Fallido`
- `Bloqueado por politica`

Tambien puedes verificar desde Tinker:

```php
DB::table('messages')->latest()->first();
DB::table('message_events')->latest()->first();
DB::table('provider_logs')->latest()->first();
```

Si WhatsApp devuelve estados, el webhook `messages` los procesa y actualiza el mensaje saliente.

## 14. Worker de colas

Los webhooks y envios usan colas. Comandos utiles:

Procesar un trabajo una sola vez:

```bash
php8.4 artisan queue:work --once --verbose
```

Procesar continuamente:

```bash
php8.4 artisan queue:work --tries=3
```

Reiniciar workers despues de deploy o cambio de `.env`:

```bash
php8.4 artisan queue:restart
```

Consultar trabajos:

```php
DB::table('jobs')->count();
DB::table('failed_jobs')->count();
```

## 15. Diagnostico de errores comunes

### Meta no valida el webhook

Sintoma:

```text
No se pudo validar la URL de devolucion de llamada o el token de verificacion.
```

Revisar:

- URL publica con HTTPS.
- Token de verificacion igual en Meta y `.env`.
- Respuesta manual del challenge.
- Cache de configuracion Laravel.

Comando:

```bash
php8.4 artisan optimize:clear
php8.4 artisan config:cache
```

### El mensaje entra a inbound_messages pero no aparece en Conversaciones

Revisar:

```php
DB::table('inbound_messages')->latest()->first();
DB::table('conversations')->latest('last_message_at')->first();
```

Si `contact_id` o `conversation_id` son `null`, revisa que el servidor tenga el ultimo deploy y reinicia colas:

```bash
git log -1 --oneline
php8.4 artisan queue:restart
php8.4 artisan optimize:clear
php8.4 artisan config:cache
```

### Mensaje bloqueado por politica

Sintoma:

```text
blocked_by_policy
blocked_no_consent
```

Solucion:

- Otorgar consentimiento WhatsApp al contacto.
- Enviar una nueva respuesta.

### Recipient phone number not in allowed list

Sintoma:

```text
(#131030) Recipient phone number not in allowed list
```

Solucion:

- Agregar el numero destinatario en la lista permitida del numero de prueba.
- Confirmar el codigo enviado por Meta.

### Access denied

Sintoma:

```text
(#131005) Access denied
```

Solucion:

- Regenerar token.
- Confirmar permisos `whatsapp_business_messaging` y `whatsapp_business_management`.
- Confirmar que el token tenga acceso a la app, WABA y numero.
- Actualizar `.env` y cachear configuracion.

### El mensaje queda en cola

Revisar si hay worker activo:

```bash
php8.4 artisan queue:work --once --verbose
```

Si usas Supervisor:

```bash
supervisorctl status
supervisorctl restart all
```

## 16. Comandos de despliegue despues de cambios

Despues de subir cambios al repositorio y completar el deploy:

```bash
cd /var/www/noiachat
git log -1 --oneline
php8.4 artisan optimize:clear
php8.4 artisan config:cache
php8.4 artisan queue:restart
```

Si hay cambios de base de datos:

```bash
php8.4 artisan migrate --force
```

## 17. Checklist final de integracion

- App NoiaChat creada en Meta Developers.
- Caso de uso WhatsApp agregado.
- Variables `.env` configuradas.
- Webhook responde challenge.
- Webhook guardado en Meta.
- Campo `messages` suscrito.
- Numero destinatario autorizado en modo prueba.
- Token con permisos correctos.
- Mensaje entrante aparece en NoiaChat.
- Contacto queda relacionado.
- Conversacion queda creada.
- Consentimiento WhatsApp otorgado.
- Respuesta desde NoiaChat llega a WhatsApp.
- Estados enviado, leido o fallido se actualizan.
- Worker de cola activo o ejecutado manualmente.
- Tokens sensibles protegidos.
