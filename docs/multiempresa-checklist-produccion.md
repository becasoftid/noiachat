# Checklist productivo multiempresa

Este checklist cierra `MT-10` para desplegar la base multiempresa/multisede con backup verificable, pruebas de aislamiento y ruta de rollback.

## 1. Preparacion

- Confirmar ventana de mantenimiento y responsable de aprobacion.
- Confirmar rama/tag exacto a desplegar.
- Confirmar acceso al servidor, base de datos, logs, Supervisor y panel de Meta.
- Confirmar que `APP_ENV=production`, `APP_DEBUG=false` y `.env` no contiene credenciales de prueba.
- Confirmar que los canales WhatsApp productivos tienen `phone_number_id`, `business_account_id`, token y secreto correctos por empresa/sede cuando aplique.

## 2. Backup obligatorio

Ejecutar backup antes de tocar migraciones:

```bash
cd /var/www/noiachat
php8.4 artisan noiachat:backup --only=all --retention=30
```

Verificar el resultado:

```bash
ls -lah storage/app/backups
find storage/app/backups -maxdepth 2 -type f | tail -20
cat storage/app/backups/noiachat_YYYYMMDD_HHMMSS/manifest.json
```

Debe existir:

- `manifest.json` con `environment`, `mode`, `database_connection` y `generated_at`.
- Copia/dump de base de datos.
- ZIP de `storage/app/public`.

Para produccion sostenida, copiar el backup fuera del servidor antes del deploy:

```bash
rsync -av storage/app/backups/noiachat_YYYYMMDD_HHMMSS usuario@backup-host:/backups/noiachat/
```

## 3. Validacion en copia

Antes de migrar produccion, restaurar el backup en una copia o staging y ejecutar:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php8.4 artisan migrate --force
php8.4 artisan db:seed --class=TenancySeeder --force
php8.4 artisan test
```

Validar datos tenant en la copia:

```bash
php8.4 artisan noiachat:tenant-validate --backup-path=storage/app/backups
```

El comando debe terminar con `Validacion multiempresa completada.` y `Errores: 0`.

Si se quiere validar solo la estructura y datos tenant sin exigir backup, por ejemplo en una copia efimera:

```bash
php8.4 artisan noiachat:tenant-validate --skip-backup
```

El comando revisa:

- Tablas `companies`, `branches` y `memberships`.
- Roles finales multiempresa.
- Empresas, sedes y membresias activas.
- Columnas `company_id` y `branch_id` en tablas operativas.
- Filas operativas sin `company_id`.
- Membresias apuntando a sedes de otra empresa.
- Backup mas reciente con `manifest.json`, copia/dump de base de datos y ZIP de storage cuando aplique.

## 4. Pruebas de aislamiento

Ejecutar la suite completa:

```bash
php8.4 artisan test
```

Como minimo, deben pasar los casos de:

- Contexto activo por membresia.
- Cambio de empresa/sede bloqueando membresias ajenas.
- Listados y detalle sin fuga entre empresas.
- Aislamiento por sede para contactos, conversaciones, dashboard y auditoria.
- Webhook entrante resuelto por `metadata.phone_number_id`.
- Envio saliente usando credenciales del canal de la conversacion.
- Administracion de sedes/membresias bloqueando cambios cross-company.

## 5. Deploy productivo

Activar mantenimiento:

```bash
php8.4 artisan down
```

Actualizar codigo y dependencias:

```bash
git fetch origin
git checkout main
git pull --ff-only origin main
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Migrar y optimizar:

```bash
php8.4 artisan migrate --force
php8.4 artisan db:seed --class=TenancySeeder --force
php8.4 artisan optimize:clear
php8.4 artisan config:cache
php8.4 artisan route:cache
php8.4 artisan view:cache
```

Reiniciar colas:

```bash
php8.4 artisan queue:restart
supervisorctl restart noiachat-worker:*
```

Reactivar:

```bash
php8.4 artisan up
```

## 6. Validacion posterior

Validar en panel:

- Login de administrador.
- Selector de empresa/sede visible cuando el usuario tenga mas de una membresia.
- Contactos, conversaciones, auditoria y dashboard filtrados por empresa/sede.
- Pantalla `Empresa` permite ver sedes y membresias del tenant activo.
- Usuario de una sede no ve datos de otra sede.

Validar WhatsApp:

- Webhook de verificacion responde a Meta.
- Mensaje entrante crea/actualiza contacto y conversacion en la empresa/sede del canal.
- Mensaje saliente usa el `phone_number_id` del canal correcto.
- Plantillas sincronizan desde el canal correcto.

Validar operacion:

```bash
php8.4 artisan queue:failed
supervisorctl status noiachat-worker:*
tail -n 100 storage/logs/laravel.log
tail -n 100 storage/logs/worker.log
```

## 7. Rollback

Si hay errores criticos:

```bash
php8.4 artisan down
```

Restaurar codigo al tag/commit anterior:

```bash
git fetch origin
git checkout TAG_O_COMMIT_ANTERIOR
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Restaurar base de datos desde el backup verificado segun el motor:

```bash
# SQLite
cp storage/app/backups/noiachat_YYYYMMDD_HHMMSS/database.sqlite database/database.sqlite

# MySQL/MariaDB
mysql -u USUARIO -p BASE_DE_DATOS < storage/app/backups/noiachat_YYYYMMDD_HHMMSS/database_YYYYMMDD_HHMMSS.sql
```

Restaurar storage publico si aplica:

```bash
rm -rf storage/app/public
mkdir -p storage/app/public
unzip storage/app/backups/noiachat_YYYYMMDD_HHMMSS/storage_public_YYYYMMDD_HHMMSS.zip -d storage/app/public
php8.4 artisan storage:link
```

Limpiar caches, reiniciar colas y levantar:

```bash
php8.4 artisan optimize:clear
php8.4 artisan queue:restart
supervisorctl restart noiachat-worker:*
php8.4 artisan up
```

## 8. Evidencia de cierre

Registrar:

- Ruta del backup usado.
- Commit/tag desplegado.
- Resultado de `php8.4 artisan test`.
- Resultado de `php8.4 artisan noiachat:tenant-validate`.
- Capturas o notas de validacion de login, inbox, webhook, envio, plantillas, auditoria y dashboard.
- Hora de inicio/fin de mantenimiento.
- Incidentes y acciones correctivas.
