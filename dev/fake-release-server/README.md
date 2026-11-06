# Fake release server

Lets you run the installer against an AdminUI build that isn't published yet.
The installer fetches release details from `adminui-installer.version_endpoint`, then downloads
`url` from that response with the licence key as a bearer token. This serves both from your machine.

## Use

```bash
# 1. Build a zip from a local checkout (excludes node_modules, vendor, .git, tests)
php dev/fake-release-server/build.php --source=/path/to/adminui --version=v4.0.0

# 2. Serve it
php -S 127.0.0.1:8099 dev/fake-release-server/router.php

# 3. In the Laravel app being tested, .env:
ADMINUI_INSTALLER_VERSION_ENDPOINT=http://127.0.0.1:8099/api/update
ADMINUI_LICENCE_KEY=anything
```

Then `php artisan config:clear` and run the install/update flow. Rebuild after changing AdminUI;
the checksum is recalculated each time.

Use a throwaway Laravel app: the update unpacks into `packages/adminui` and runs composer, migrations and seeders.
If `packages/adminui/.git` exists it unpacks to `packages/adminui-test` instead.
