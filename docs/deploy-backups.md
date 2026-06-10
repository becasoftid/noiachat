# Backups automaticos de NoiaChat

NoiaChat incluye un comando Artisan para generar backups locales de base de datos y archivos publicos de `storage`.

## Comando disponible

```bash
php8.4 artisan noiachat:backup
```

Por defecto crea un directorio con timestamp en:

```text
storage/app/backups/noiachat_YYYYMMDD_HHMMSS
```

Cada backup incluye:

- `manifest.json` con datos basicos del entorno.
- Copia de base de datos SQLite o dump SQL para MySQL/PostgreSQL.
- ZIP de `storage/app/public`.

## Opciones

```bash
php8.4 artisan noiachat:backup --only=database
php8.4 artisan noiachat:backup --only=storage
php8.4 artisan noiachat:backup --retention=14
php8.4 artisan noiachat:backup --path=/ruta/externa/noiachat-backups
```

Valores permitidos para `--only`:

- `all`
- `database`
- `storage`

## Requisitos del servidor

Para comprimir archivos:

```bash
php8.4 -m | grep zip
```

Para MySQL/MariaDB:

```bash
apt install -y mysql-client
```

Para PostgreSQL:

```bash
apt install -y postgresql-client
```

## Cron incluido

El repositorio incluye:

```text
deploy/cron/noiachat-backup
```

Ese cron ejecuta diariamente a las 02:15:

```bash
cd /var/www/noiachat && php8.4 artisan noiachat:backup --retention=14
```

El workflow de GitHub Actions copia este archivo a:

```text
/etc/cron.d/noiachat-backup
```

## Instalacion manual

```bash
cd /var/www/noiachat
cp deploy/cron/noiachat-backup /etc/cron.d/noiachat-backup
chmod 644 /etc/cron.d/noiachat-backup
systemctl reload cron || systemctl restart cron
```

## Verificacion

Ejecutar backup manual:

```bash
cd /var/www/noiachat
php8.4 artisan noiachat:backup
```

Ver backups generados:

```bash
ls -lah storage/app/backups
find storage/app/backups -maxdepth 2 -type f
tail -f storage/logs/backup.log
```

## Restauracion

Antes de restaurar, activar mantenimiento:

```bash
php8.4 artisan down
```

### SQLite

```bash
cp storage/app/backups/noiachat_YYYYMMDD_HHMMSS/database.sqlite database/database.sqlite
```

### MySQL/MariaDB

```bash
mysql -u USUARIO -p BASE_DE_DATOS < storage/app/backups/noiachat_YYYYMMDD_HHMMSS/database_YYYYMMDD_HHMMSS.sql
```

### Storage publico

```bash
unzip storage/app/backups/noiachat_YYYYMMDD_HHMMSS/storage_public_YYYYMMDD_HHMMSS.zip -d storage/app/public
php8.4 artisan storage:link
```

Finalizar:

```bash
php8.4 artisan optimize:clear
php8.4 artisan up
```

## Recomendacion operativa

El backup local protege contra errores logicos y despliegues fallidos, pero no reemplaza un backup externo. Para produccion sostenida, sincroniza `storage/app/backups` hacia un destino fuera del droplet como S3, Spaces, rsync o snapshots del proveedor.
