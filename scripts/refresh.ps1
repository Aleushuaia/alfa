param(
    [string]$Service = 'app'
)

Write-Host "Refreshing Laravel caches (service: $Service)..."

$docker = Get-Command docker -ErrorAction SilentlyContinue
if ($docker) {
    try {
        $services = docker compose ps --services 2>$null
        if ($services -match "^$Service$") {
            Write-Host "Detected docker compose service '$Service'. Running inside container..."
            docker compose exec $Service php artisan optimize:clear
            docker compose exec $Service php artisan cache:clear
            docker compose exec $Service php artisan config:clear
            docker compose exec $Service php artisan route:clear
            docker compose exec $Service php artisan view:clear
            docker compose exec $Service composer dump-autoload -o
            exit 0
        }
    } catch { }
}

if (Get-Command php -ErrorAction SilentlyContinue) {
    Write-Host "Running artisan locally..."
    php artisan optimize:clear
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    composer dump-autoload -o
    exit 0
}

Write-Error "Neither docker compose service '$Service' detected nor local php available."
