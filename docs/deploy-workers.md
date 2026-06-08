# Worker permanente de NoiaChat

NoiaChat usa colas para procesar webhooks y envios de WhatsApp. En produccion el worker debe quedar administrado por Supervisor para que siga activo despues de cerrar la terminal, reiniciar PHP o desplegar cambios.

## Archivo incluido

El repositorio incluye la configuracion base en:

```text
deploy/supervisor/noiachat-worker.conf
```

La configuracion ejecuta:

```bash
php8.4 /var/www/noiachat/artisan queue:work --sleep=3 --tries=3 --timeout=120 --max-time=3600
```

Usa dos procesos, usuario `www-data`, reinicio automatico y escribe logs en:

```text
/var/www/noiachat/storage/logs/worker.log
```

## Instalacion inicial en el servidor

Desde el droplet:

```bash
apt update
apt install -y supervisor
cd /var/www/noiachat
cp deploy/supervisor/noiachat-worker.conf /etc/supervisor/conf.d/noiachat-worker.conf
supervisorctl reread
supervisorctl update
supervisorctl start noiachat-worker:*
```

## Verificacion

```bash
supervisorctl status noiachat-worker:*
tail -f storage/logs/worker.log
php8.4 artisan queue:failed
```

Resultado esperado:

```text
noiachat-worker:noiachat-worker_00 RUNNING
noiachat-worker:noiachat-worker_01 RUNNING
```

## Operacion despues de deploy

El workflow de GitHub Actions copia la configuracion de Supervisor y reinicia los procesos si `supervisorctl` existe en el servidor.

Si haces despliegue manual:

```bash
cd /var/www/noiachat
php8.4 artisan queue:restart
supervisorctl restart noiachat-worker:*
```

## Diagnostico

Si los mensajes quedan en cola:

```bash
php8.4 artisan tinker
DB::table('jobs')->count();
DB::table('failed_jobs')->count();
```

Si hay trabajos fallidos:

```bash
php8.4 artisan queue:failed
php8.4 artisan queue:retry all
```

Si Supervisor no esta activo:

```bash
systemctl status supervisor
systemctl enable supervisor
systemctl start supervisor
supervisorctl status
```
