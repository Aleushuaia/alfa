#!/usr/bin/env bash
set -euo pipefail

SERVICE="${1:-app}"

echo "Refreshing Laravel caches (service: $SERVICE)..."

if command -v docker >/dev/null 2>&1 && docker compose ps --services 2>/dev/null | grep -q "^${SERVICE}$"; then
  echo "Detected docker compose service '${SERVICE}'. Running inside container..."
  docker compose exec -T ${SERVICE} php artisan optimize:clear
  docker compose exec -T ${SERVICE} php artisan cache:clear
  docker compose exec -T ${SERVICE} php artisan config:clear
  docker compose exec -T ${SERVICE} php artisan route:clear
  docker compose exec -T ${SERVICE} php artisan view:clear
  docker compose exec -T ${SERVICE} composer dump-autoload -o || true
else
  if command -v php >/dev/null 2>&1; then
    echo "Running artisan locally..."
    php artisan optimize:clear
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    composer dump-autoload -o || true
  else
    echo "Neither docker compose service '${SERVICE}' running nor local php found. Aborting." >&2
    exit 1
  fi
fi

echo "Done."
