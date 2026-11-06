# End-to-end install test

Runs the installer's real HTTP routes against a throwaway Laravel app (MariaDB), with release
details and the zip served by `dev/fake-release-server`.

```bash
# 1. Build a release zip from the AdminUI checkout you want to test
php dev/fake-release-server/build.php --source=../adminui-v4 --version=v4.0.0-test

# 2. Create the app + empty database (also the reset step: it deletes and recreates both)
dev/e2e/setup-app.sh [app-dir] [db-name]

# 3. Start the fake release server (:8099) and the app (:8100). Both run inside lerd's PHP container.
dev/e2e/servers.sh start

# 4. Walk the install routes in UI order; stops at the first failing step
podman exec lerd-php85-fpm sh $PWD/dev/e2e/drive-install.sh

dev/e2e/servers.sh stop
```

Notes
- `php` here is lerd's container-backed wrapper, so 127.0.0.1 only works between processes in that container.
- The app pins the installer path repo to version 4.0.0 (override with `INSTALLER_VERSION=...`) so it satisfies the `adminui/adminui-installer` constraint in AdminUI's composer.json.
- `setup-app.sh` copies `auth.json` (packagist.evomark.co.uk token) into the throwaway app; it is gitignored there.

## Migration audit

Runs every migration the app registers on an empty database, one at a time, and keeps going after failures.
Reports migrations that throw and foreign keys that point at a table created by a later migration (or never).
Needs the fake release server up (AdminUI fetches its licence from it) and the app already installed up to `publish-resources`.

```bash
podman exec lerd-php85-fpm sh -c 'cd /path/to/app && CACHE_STORE=array SESSION_DRIVER=array QUEUE_CONNECTION=null \
  php /path/to/adminui-installer/dev/e2e/audit-migrations.php /path/to/app' > audit.json
```

It wipes the app's database. The licence packages come from `FAKE_LICENCE_PACKAGES` in the fake server's environment
(lerd's `php` wrapper doesn't forward env vars, so change the default in `router.php` to include e.g. `Staff`).
