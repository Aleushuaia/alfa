# SAE Kayen Dashboard

Panel de control para el Sistema de Actuaciones y Expedientes (SAE Kayen),
construido con **Laravel 11**, **AdminLTE 4**, **ApexCharts** y **Alpine.js**,
totalmente dockerizado.

---

## Requisitos

| Herramienta | Version minima |
|-------------|---------------|
| Docker      | 20.x          |
| Docker Compose | v2.x       |

No se necesita PHP, Node ni Composer instalados localmente.

---

## 1. Construir el contenedor

`ash
# Build imagen de produccion (multi-stage optimizado)
docker compose build

# Build forzando sin cache (para cambios en Dockerfile)
docker compose build --no-cache
`

---

## 2. Levantar el proyecto

`ash
# Paso 1: clonar/descomprimir el proyecto y entrar al directorio
cd dashboard

# Paso 2: copiar el .env de ejemplo
cp .env.example .env

# Paso 3: Editar .env con las credenciales de BD (opcional en modo demo)
# (ver seccion 3)

# Paso 4: Levantar
docker compose up -d

# Ver logs en tiempo real
docker compose logs -f app
`

El dashboard estara disponible en: **http://localhost:8080/dashboard**

---

## 3. Configurar el .env para la BD real

Editar el archivo .env con los datos de conexion a SAE Kayen:

`env
# Deshabilitar modo demo
DASHBOARD_DEMO=false

# Conexion MySQL/MariaDB
DB_SAE_CONNECTION=mysql
DB_SAE_HOST=192.168.1.X       # IP del servidor de BD
DB_SAE_PORT=3306
DB_SAE_DATABASE=sae_kayen
DB_SAE_USERNAME=sae_user
DB_SAE_PASSWORD=tu_password

# (opcional) Conexion PostgreSQL
DB_PG_HOST=192.168.1.Y
DB_PG_PORT=5432
DB_PG_DATABASE=sae_kayen_pg
DB_PG_USERNAME=sae_pg_user
DB_PG_PASSWORD=tu_password_pg
`

Luego reiniciar:

`ash
docker compose restart app
`

---

## 4. URL del dashboard

| Entorno | URL |
|---------|-----|
| Local   | http://localhost:8080/dashboard |
| Produccion | https://tu-dominio.com/dashboard |

---

## 5. Comandos utiles

`ash
# Limpiar caches dentro del contenedor
docker exec sae_dashboard php artisan cache:clear
docker exec sae_dashboard php artisan config:clear
docker exec sae_dashboard php artisan view:clear

# Ver logs del sistema
docker compose logs -f

# Reconstruir solo los assets frontend
docker run --rm -v \C:\laravel\dashboard:/app -w /app node:20-alpine npm run build

# Detener el proyecto
docker compose down

# Detener y eliminar volumenes
docker compose down -v
`

---

## Arquitectura

`
dashboard/
 app/
    Http/Controllers/DashboardController.php
    Providers/AppServiceProvider.php
    Repositories/DashboardRepository.php
 config/database.php          (conexiones sae_kayen, sae_kayen_pg)
 resources/
    css/app.css              (AdminLTE 4 + iconos)
    js/app.js                (Bootstrap, AdminLTE, Alpine, ApexCharts)
    views/
        layouts/adminlte.blade.php
        dashboard/
            index.blade.php
            partials/
                cards.blade.php
                chart_expedientes.blade.php
                chart_actuaciones.blade.php
                chart_escritos.blade.php
                tabla_notificaciones.blade.php
                actividad_reciente.blade.php
 routes/web.php
 Dockerfile
 docker-compose.yml
 nginx.conf
 supervisord.conf
 docker-entrypoint.sh
 .env.example
`

---

## Modo Demo

Con DASHBOARD_DEMO=true (default), el dashboard muestra datos ficticios
realistas sin necesidad de conexion a BD. Cada widget tiene su propio
generador de datos demo en DashboardRepository.