#!/bin/bash
# (Re)creates a throwaway Laravel app + database for running the installer end to end.
# Run from the host. Everything PHP runs through lerd's container-backed `php`.
#
#   dev/e2e/setup-app.sh [app-dir] [db-name]
#
# Needs: lerd, a running lerd MariaDB/MySQL container, and an auth.json for packagist.evomark.co.uk
# (copied from $AUTH_JSON, default: the adminui-v4 lerd site).
set -euo pipefail

INSTALLER="$(cd "$(dirname "$0")/../.." && pwd)"
APP="${1:-$HOME/Code/Lerd/adminui-install-test}"
DB="${2:-adminui_install_test}"
AUTH_JSON="${AUTH_JSON:-$HOME/Code/Lerd/adminui-v4/auth.json}"
DB_ENV="${DB_ENV:-$HOME/Code/Lerd/adminui-v4/.env}"   # where to read the DB container name/password from
COMPOSER="php $INSTALLER/lib/composer.phar"

DB_HOST=$(grep '^DB_HOST=' "$DB_ENV" | cut -d= -f2- | tr -d '"')
DB_PASS=$(grep '^DB_PASSWORD=' "$DB_ENV" | cut -d= -f2- | tr -d '"')

echo "== database $DB on $DB_HOST"
podman exec "$DB_HOST" mariadb -uroot -p"$DB_PASS" -e "DROP DATABASE IF EXISTS \`$DB\`; CREATE DATABASE \`$DB\`;"

echo "== fresh Laravel app in $APP"
rm -rf "$APP"
cd "$(dirname "$APP")"
$COMPOSER create-project laravel/laravel "$(basename "$APP")" --no-interaction --prefer-dist >/dev/null 2>&1
cd "$APP"
rm -f database/database.sqlite

# Same as a stock Laravel app, just pointed at MariaDB (cache/session/queue stay on the `database` driver)
sed -i -E "s/^DB_CONNECTION=.*/DB_CONNECTION=mariadb/; s/^# ?DB_HOST=.*/DB_HOST=$DB_HOST/; s/^# ?DB_PORT=.*/DB_PORT=3306/; s/^# ?DB_DATABASE=.*/DB_DATABASE=$DB/; s/^# ?DB_USERNAME=.*/DB_USERNAME=root/; s/^# ?DB_PASSWORD=.*/DB_PASSWORD=\"$DB_PASS\"/; s#^APP_URL=.*#APP_URL=http://127.0.0.1:8100#" .env
printf '\nADMINUI_INSTALLER_VERSION_ENDPOINT=http://127.0.0.1:8099/api/update\n' >> .env
cp "$AUTH_JSON" auth.json

# AdminUI validates its licence against aui_endpoint at runtime; send that to the fake server too.
mkdir -p config
cat > config/aui-setup.php <<'PHP'
<?php

return [
    'aui_endpoint' => 'http://127.0.0.1:8099/api/',
];
PHP

echo "== install the installer package (symlinked)"
$COMPOSER config minimum-stability dev
$COMPOSER config prefer-stable true
$COMPOSER config allow-plugins.wikimedia/composer-merge-plugin true
$COMPOSER config allow-plugins.pixelfear/composer-dist-plugin true
$COMPOSER config repositories.installer "{\"type\":\"path\",\"url\":\"$INSTALLER\",\"options\":{\"symlink\":true,\"versions\":{\"adminui/adminui-installer\":\"${INSTALLER_VERSION:-4.0.0}\"}}}" --json
$COMPOSER config repositories.evomark '{"type":"composer","url":"https://packagist.evomark.co.uk/evomark"}' --json
$COMPOSER require 'adminui/adminui-installer:*' --no-interaction >/dev/null 2>&1

# The installer's prepare-dependencies step enables cache.serializable_classes itself (EnableCacheSerializationAction).
# Set SERIALIZABLE_CLASSES=true to pre-enable it and bypass that step.
if [ "${SERIALIZABLE_CLASSES:-false}" = "true" ]; then
  sed -i "s/'serializable_classes' => false/'serializable_classes' => true/" config/cache.php
fi

echo "== baseline Laravel migrations"
php artisan migrate --force >/dev/null
echo "ready: $APP"
