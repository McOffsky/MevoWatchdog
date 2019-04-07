#!/usr/bin/env sh

set -e

# See:
# - Doc: https://docs.docker.com/engine/reference/builder/#entrypoint
# - Example: https://github.com/docker-library/mariadb/blob/master/10.1/docker-entrypoint.sh
#
# Example use:
# ./docker-entrypoint.sh php-fpm


## Clear container on start by default
if [ "$NO_FORCE_SF_CONTAINER_REFRESH" != "" ]; then
    echo "NO_FORCE_SF_CONTAINER_REFRESH set, skipping Symfony container clearing on startup."
elif [ -d var/cache ]; then
    echo "Symfony 3.x structure detected, container is not cleared on startup, use 3.2+ env variables support and warmup container during build."
elif [ -d ezpublish/cache ]; then
    echo "Deleting ezpublish/cache/*/*/*ProjectContainer.php to make sure environment variables are picked up"
    rm -f ezpublish/cache/*/*/*ProjectContainer.php
elif [ -d app/cache ]; then
    echo "Deleting app/cache/*/*/*ProjectContainer.php to make sure environment variables are picked up"
    rm -f app/cache/*/*/*ProjectContainer.php
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
