# ============================================================
#  Imagen �nica  PHP 8.3 + Nginx + Supervisor
# ============================================================
FROM php:8.4-fpm-alpine

#  Paquetes del sistema 
RUN apk add --no-cache \
        nginx \
        supervisor \
        bash \
        curl \
        git \
        unzip \
        zip \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        libxml2-dev \
        icu-dev \
        oniguruma-dev \
        libpq-dev \
        curl-dev \
        poppler-utils \
        ghostscript \
        tesseract-ocr \
        tesseract-ocr-data-spa

#  Extensiones PHP 
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
        pdo pdo_mysql pdo_pgsql \
        mbstring xml curl zip gd intl bcmath opcache

#  Opcache producci�n 
RUN { \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.revalidate_freq=60'; \
        echo 'opcache.fast_shutdown=1'; \
        echo 'opcache.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

#  Composer 
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copiar manifiestos para aprovechar cache de capas
COPY composer.json composer.lock* ./
# Deshabilitar verificacion SSL de git/Composer (entorno con proxy corporativo
# que inyecta certificado auto-firmado). Solo aplica durante el build.
RUN git config --global http.sslVerify false \
 && composer config --global disable-tls true \
 && composer config --global secure-http false \
 && composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress

# Codigo fuente y assets ya compilados (public/build)
COPY . .

# Autoloader optimizado (artisan ya existe en este punto)
RUN composer dump-autoload --optimize --no-interaction

# Directorios de runtime y permisos
# Nota: g+s (setgid bit) en storage hace que los archivos creados por root
# hereden el grupo www-data, actuando como segunda capa de proteccion.
RUN mkdir -p \
        /var/log/supervisor \
        /run/nginx \
        /var/www/storage/framework/sessions \
        /var/www/storage/framework/views \
        /var/www/storage/framework/cache \
        /var/www/storage/logs \
        /var/www/storage/app/private \
        /var/www/bootstrap/cache \
 && chown -R www-data:www-data /var/www \
 && chmod -R 775 /var/www/storage /var/www/bootstrap/cache \
 && chmod -R g+s /var/www/storage /var/www/bootstrap/cache

# Nginx user = www-data (igual que php-fpm)
RUN sed -i 's/user nginx;/user www-data;/g' /etc/nginx/nginx.conf 2>/dev/null || true

# Configuraciones
COPY nginx.conf           /etc/nginx/http.d/default.conf
COPY supervisord.conf     /etc/supervisor/conf.d/supervisord.conf
COPY php-upload.ini       /usr/local/etc/php/conf.d/upload.ini
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8080
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
