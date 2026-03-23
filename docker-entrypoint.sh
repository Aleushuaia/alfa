#!/bin/bash
set -e

# ─── Esperar a que la BD esté disponible (opcional) ───────────────────────────
# Solo si DB_SAE_HOST está definido y no estamos en modo demo
if [ "${DASHBOARD_DEMO}" != "true" ] && [ -n "${DB_SAE_HOST}" ]; then
    echo "[entrypoint] Esperando conexión a la BD: ${DB_SAE_HOST}:${DB_SAE_PORT:-3306}..."
    for i in $(seq 1 30); do
        if nc -z "${DB_SAE_HOST}" "${DB_SAE_PORT:-3306}" 2>/dev/null; then
            echo "[entrypoint] BD disponible."
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

# ─── Directorios temporales de nginx (ahora en /tmp para evitar permisos) ───
echo "[entrypoint] Creando directorios tmp de nginx en /tmp..."
mkdir -p /tmp/nginx_client_body 2>/dev/null || true
chmod 1777 /tmp/nginx_client_body 2>/dev/null || true

# ─── Directorio temporal de OCR ──────────────────────────────────────────────
mkdir -p /var/www/storage/app/private/temp-ocr 2>/dev/null || true
chown -R www-data:www-data /var/www/storage/app/private/temp-ocr 2>/dev/null || true
chmod -R 775 /var/www/storage/app/private/temp-ocr 2>/dev/null || true

echo "[entrypoint] Iniciando servicios..."
exec "$@"
