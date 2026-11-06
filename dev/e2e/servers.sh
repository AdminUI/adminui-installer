#!/bin/bash
# start|stop the fake release server (:8099) and the throwaway app (:8100).
# Both run inside lerd's PHP container, so the app can reach the release server on 127.0.0.1.
INSTALLER="$(cd "$(dirname "$0")/../.." && pwd)"
APP="${APP:-$HOME/Code/Lerd/adminui-install-test}"
PIDS="${TMPDIR:-/tmp}/adminui-e2e-pids"

stop() {
  if [ -f "$PIDS" ]; then
    while read -r pid; do kill "$pid" 2>/dev/null; done < "$PIDS"
    rm -f "$PIDS"
  fi
  # lerd's wrapper leaves the real php child behind; match on the ports we own
  # anchored to the start of the command line so this never matches the calling shell
  for p in $(ps -eo pid=,args= | awk '$0 ~ /^ *[0-9]+ ([^ ]*\/)?(lerd )?php (-S 127\.0\.0\.1:(8099|8100)|artisan serve)/ {print $1}'); do kill "$p" 2>/dev/null; done
}

case "$1" in
  start)
    stop
    ( cd "$INSTALLER" && setsid nohup php -S 127.0.0.1:8099 dev/fake-release-server/router.php >/dev/null 2>&1 </dev/null & echo $! >> "$PIDS" )
    ( cd "$APP" && setsid nohup php artisan serve --host=127.0.0.1 --port=8100 --no-reload >"$APP/storage/logs/serve.log" 2>&1 </dev/null & echo $! >> "$PIDS" )
    sleep 4; echo "servers started" ;;
  stop) stop; echo "servers stopped" ;;
  *) echo "usage: $0 start|stop"; exit 1 ;;
esac
