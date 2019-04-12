#!/usr/bin/env sh

set -e

## Clear container on start by default
if [ -d var/cache ]; then
    echo "Deleting var/cache to make sure environment variables are picked up"
    rm -rf var/cache
fi

# docker-entrypoint-initdb.d, as provided by most official images allows for direct usage and extended images to
# extend behaviour without modifying this file.
for f in /docker-entrypoint-initdb.d/*; do
    case "$f" in
        *.sh)     logger "$0: running $f"; . "$f" ;;
        "/docker-entrypoint-initdb.d/*") ;;
        *)        logger "$0: ignoring $f" ;;
    esac
done

exec "$@"
