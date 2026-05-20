#!/bin/bash
set -e

# ─── Esperar a que PostgreSQL esté disponible ─────────────────────────────────
if [ -n "${DB_PG_HOST}" ]; then
    echo "[entrypoint] Esperando conexión a PostgreSQL: ${DB_PG_HOST}:${DB_PG_PORT:-5432}..."
    PG_READY=false
    for i in $(seq 1 30); do
        if nc -z "${DB_PG_HOST}" "${DB_PG_PORT:-5432}" 2>/dev/null; then
            echo "[entrypoint] PostgreSQL disponible."
            PG_READY=true
            break
        fi
        echo "[entrypoint] Intento $i/30 fallido, reintentando en 2s..."
        sleep 2
    done

    # ─── Crear la base de datos si no existe ──────────────────────────────
    if [ "$PG_READY" = true ] && [ -f /var/www/docker/create-db.php ]; then
        echo "[entrypoint] Verificando/creando base de datos..."
        php /var/www/docker/create-db.php
    fi

fi

# ─── Esperar a que MySQL/MariaDB esté disponible (opcional) ───────────────────
if [ "${DASHBOARD_DEMO}" != "true" ] && [ -n "${DB_SAE_HOST}" ]; then
    echo "[entrypoint] Esperando conexión a MySQL: ${DB_SAE_HOST}:${DB_SAE_PORT:-3306}..."
    for i in $(seq 1 30); do
        if nc -z "${DB_SAE_HOST}" "${DB_SAE_PORT:-3306}" 2>/dev/null; then
            echo "[entrypoint] MySQL disponible."
            break
        fi
        echo "[entrypoint] Intento $i/30 fallido, reintentando en 2s..."
        sleep 2
    done
fi

# ─── Optimizaciones Laravel ───────────────────────────────────────────────────
cd /var/www

if [ "${APP_ENV}" = "production" ]; then
    echo "[entrypoint] Optimizando para producción..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
else
    echo "[entrypoint] Limpiando caché para entorno de desarrollo..."
    php artisan config:clear  2>/dev/null || true
    php artisan route:clear   2>/dev/null || true
    php artisan view:clear    2>/dev/null || true

    # Hot-reload: deshabilitar caché de opcache para que los cambios
    # en archivos .php se reflejen de inmediato sin reiniciar php-fpm.
    if [ "${PHP_OPCACHE_DEV:-true}" = "true" ]; then
        echo "[entrypoint] Modo desarrollo: opcache con validate_timestamps=1, revalidate_freq=0"
        {
            echo 'opcache.validate_timestamps=1'
            echo 'opcache.revalidate_freq=0'
        } > /usr/local/etc/php/conf.d/opcache-dev.ini
    fi
fi

# ─── Permisos de almacenamiento ───────────────────────────────────────────────
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

# ─── Directorios temporales de nginx ─────────────────────────────────────────
# Crear /tmp/nginx_client_body (usado por client_body_temp_path en nginx.conf)
echo "[entrypoint] Preparando directorios tmp de nginx..."
mkdir -p /tmp/nginx_client_body 2>/dev/null || true
chmod 1777 /tmp/nginx_client_body 2>/dev/null || true
# Sincronizar la configuración nginx del proyecto al sitio activo
cp /var/www/nginx.conf /etc/nginx/http.d/default.conf 2>/dev/null || true
# Fallback: asegurar permisos del directorio tmp del sistema nginx
chown -R www-data:www-data /var/lib/nginx/tmp/ 2>/dev/null || true

# ─── Directorio temporal de OCR ──────────────────────────────────────────────
mkdir -p /var/www/storage/app/private/temp-ocr 2>/dev/null || true
chown -R www-data:www-data /var/www/storage/app/private/temp-ocr 2>/dev/null || true
chmod -R 775 /var/www/storage/app/private/temp-ocr 2>/dev/null || true

echo "[entrypoint] Iniciando servicios..."
# ─── Limpieza final de cachés (siempre al final, antes de ejecutar el CMD) ──
echo "[entrypoint] Ejecutando limpieza final de cachés..."
cd /var/www || true
# Ejecutar limpiezas de forma tolerante (no fallar el entrypoint si algún comando falla)
php artisan cache:clear --no-interaction 2>/dev/null || true
php artisan config:clear --no-interaction 2>/dev/null || true
php artisan route:clear --no-interaction 2>/dev/null || true
php artisan view:clear --no-interaction 2>/dev/null || true
php artisan optimize:clear --no-interaction 2>/dev/null || true

# ─── Permisos FINALES ─────────────────────────────────────────────────────────
# IMPORTANTE: este bloque debe estar SIEMPRE al final, después de todos los
# comandos "php artisan *" que se ejecutan como root. Esos comandos hacen
# boot de Laravel y pueden crear/actualizar el archivo de log del día
# (storage/logs/laravel-YYYY-MM-DD.log) con ownership root. Si no se corrige
# aquí, php-fpm (www-data) recibirá "Permission denied" al primer request.
echo "[entrypoint] Aplicando permisos finales sobre storage (post-artisan)..."
mkdir -p \
    /var/www/storage/logs \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/storage/framework/cache \
    /var/www/bootstrap/cache
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Finalmente arrancar el proceso principal
exec "$@"
