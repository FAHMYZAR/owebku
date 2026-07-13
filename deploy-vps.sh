#!/usr/bin/env sh

set -eu

APP_DIR="/var/www/html/owebku"
COMPOSE_DIR="${COMPOSE_DIR:-$HOME/srv/icbear}"

echo "== Owebku VPS setup =="
echo "Compose dir : $COMPOSE_DIR"
echo "App dir     : $APP_DIR"

cd "$COMPOSE_DIR"

echo "== Ensure runtime directories =="
docker compose exec php sh -c "mkdir -p \
  $APP_DIR/storage/workspaces \
  $APP_DIR/storage/quarantine \
  $APP_DIR/storage/logs \
  $APP_DIR/sites \
  $APP_DIR/public/uploads"

echo "== Fix runtime permissions =="
docker compose exec php sh -c "chmod -R 775 \
  $APP_DIR/storage \
  $APP_DIR/sites \
  $APP_DIR/public/uploads"

echo "== Run database migration =="
docker compose exec php php "$APP_DIR/migrate.php"

echo "== Test nginx config =="
docker compose exec nginx nginx -t

echo "== Restart app containers =="
docker compose restart php nginx

echo "== Done =="
echo "Open: https://owebku.site"
