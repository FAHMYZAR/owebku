#!/usr/bin/env sh

set -eu

APP_DIR="/var/www/html/owebku"
COMPOSE_DIR="${COMPOSE_DIR:-$HOME/srv/icbear}"

echo "== Owebku VPS setup =="
echo "Compose dir : $COMPOSE_DIR"
echo "App dir     : $APP_DIR"

cd "$COMPOSE_DIR"

echo "== Ensure runtime directories =="
docker compose exec -u root php sh -c "mkdir -p \
  $APP_DIR/storage/workspaces \
  $APP_DIR/storage/quarantine \
  $APP_DIR/storage/logs \
  $APP_DIR/sites \
  $APP_DIR/public/uploads"

echo "== Detect PHP-FPM runtime user =="
PHP_USER="$(docker compose exec php sh -c "ps -o user= -C php-fpm 2>/dev/null | tail -n 1 | tr -d '[:space:]' || true")"
if [ -z "$PHP_USER" ]; then
  PHP_USER="www-data"
fi
echo "PHP user    : $PHP_USER"

echo "== Fix runtime ownership and permissions =="
docker compose exec -u root php sh -c "chown -R $PHP_USER:$PHP_USER \
  $APP_DIR/storage \
  $APP_DIR/sites \
  $APP_DIR/public/uploads && \
  chmod -R ug+rwX,o-rwx $APP_DIR/storage && \
  chmod -R ug+rwX,o+rX $APP_DIR/sites $APP_DIR/public/uploads"

echo "== Force published sites readable by nginx =="
docker compose exec -u root nginx sh -c "chmod -R a+rX $APP_DIR/sites"

echo "== Verify workspace and published-site access =="
docker compose exec php sh -c "test -w $APP_DIR/storage/workspaces && echo 'storage/workspaces writable: yes'"
docker compose exec nginx sh -c "test -r $APP_DIR/sites && test -x $APP_DIR/sites && echo 'sites readable by nginx: yes'"

echo "== Run database migration =="
docker compose exec php php "$APP_DIR/migrate.php"

echo "== Test nginx config =="
docker compose exec nginx nginx -t

echo "== Restart app containers =="
docker compose restart php nginx

echo "== Done =="
echo "Open: https://owebku.site"
