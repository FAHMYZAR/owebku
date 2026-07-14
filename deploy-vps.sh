#!/usr/bin/env sh

set -eu

APP_DIR="/var/www/html/owebku"
COMPOSE_DIR="${COMPOSE_DIR:-$HOME/srv/icbear}"

echo "== Owebku VPS setup =="
echo "Compose dir : $COMPOSE_DIR"
echo "App dir     : $APP_DIR"

cd "$COMPOSE_DIR"

echo "== Require running containers =="
docker compose exec -T php true
docker compose exec -T nginx true

HOST_GID="$(id -g)"
case "$HOST_GID" in
  ''|*[!0-9]*) echo "Invalid host group ID" >&2; exit 1 ;;
esac

echo "== Ensure runtime directories =="
docker compose exec -T -u root php sh -eu -c "mkdir -p \
  '$APP_DIR/storage/workspaces' \
  '$APP_DIR/storage/quarantine' \
  '$APP_DIR/storage/logs' \
  '$APP_DIR/sites' \
  '$APP_DIR/public/uploads'"

echo "== Detect PHP-FPM runtime UID =="
PHP_UID="$(docker compose exec -T php sh -c "id -u www-data 2>/dev/null || id -u")"
case "$PHP_UID" in
  ''|*[!0-9]*) echo "Unable to determine PHP UID" >&2; exit 1 ;;
esac
echo "PHP UID     : $PHP_UID"
echo "Host GID    : $HOST_GID"

echo "== Refuse deployment if executable files exist in runtime content =="
RUNTIME_EXECUTABLES="$(docker compose exec -T php find \
  "$APP_DIR/storage" "$APP_DIR/sites" "$APP_DIR/public/uploads" \
  -type f \( -iname '*.php' -o -iname '*.phtml' -o -iname '*.phar' -o -iname '*.cgi' -o -iname '*.pl' -o -iname '*.py' -o -iname '*.rb' -o -iname '*.sh' -o -iname '*.so' \) \
  -print)"
if [ -n "$RUNTIME_EXECUTABLES" ]; then
  echo "Deployment refused: executable files found in runtime content:" >&2
  printf '%s\n' "$RUNTIME_EXECUTABLES" >&2
  exit 1
fi

echo "== Apply shared host/PHP ownership and least-privilege modes =="
docker compose exec -T -u root php sh -eu -c "
  chown -R '$PHP_UID:$HOST_GID' '$APP_DIR/storage' '$APP_DIR/sites' '$APP_DIR/public/uploads'
  find '$APP_DIR/storage' '$APP_DIR/public/uploads' -type d -exec chmod 2770 {} +
  find '$APP_DIR/storage' '$APP_DIR/public/uploads' -type f -exec chmod 0660 {} +
  find '$APP_DIR/sites' -type d -exec chmod 2775 {} +
  find '$APP_DIR/sites' -type f -exec chmod 0664 {} +
"

echo "== Verify runtime access =="
docker compose exec -T php sh -eu -c "
  test -w '$APP_DIR/storage/workspaces'
  test -w '$APP_DIR/storage/quarantine'
  test -w '$APP_DIR/storage/logs'
  test -w '$APP_DIR/sites'
  test -w '$APP_DIR/public/uploads'
"
docker compose exec -T nginx sh -eu -c "test -r '$APP_DIR/sites' && test -x '$APP_DIR/sites'"
echo "Runtime access checks: passed"

echo "== Test nginx config =="
docker compose exec -T nginx nginx -t

echo "== Run database migration =="
docker compose exec -T php php "$APP_DIR/migrate.php"

echo "== Restart app containers =="
docker compose restart php nginx

echo "== Done =="
echo "Open: https://owebku.site"

