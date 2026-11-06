#!/bin/sh
# Drives the installer's HTTP routes in the same order as the install UI.
# Run inside the PHP container (so 127.0.0.1 reaches the app and the fake release server):
#   podman exec lerd-php85-fpm sh /path/to/dev/e2e/drive-install.sh [base-url]
BASE="${1:-http://127.0.0.1:8100}/install-adminui"
KEY="${ADMINUI_TEST_KEY:-e2e-test-licence}"

step() {
  name="$1"; shift
  out=$(curl -s --max-time 600 -w '\n%{http_code}' -X POST -H 'Accept: application/json' "$@" "$BASE/$name")
  code=$(printf '%s' "$out" | tail -n1)
  body=$(printf '%s' "$out" | sed '$d')
  if [ "$code" != "200" ]; then
    echo "FAIL  $name -> HTTP $code"; printf '%s\n' "$body" | head -c 1500; echo; exit 1
  fi
  echo "ok    $name"
}

step save-key -H 'Content-Type: application/json' -d "{\"licence_key\":\"$KEY\"}"
for s in release-details download-release unpack-release prepare-dependencies dependencies publish-resources run-migrations seed-database; do
  step "$s"
done
echo "Install flow completed"
