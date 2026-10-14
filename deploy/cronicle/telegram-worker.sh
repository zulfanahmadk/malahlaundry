#!/bin/bash
set -euo pipefail

# Install as root:root 0755; execute as www-data through the scoped sudoers rule.
if [[ "$(/usr/bin/id -un)" != 'www-data' || "$#" -ne 0 ]]; then
    printf '%s\n' 'Run this wrapper as www-data without arguments.' >&2
    exit 1
fi

exec /usr/bin/flock -n -E 0 \
    /var/www/malahlaundry/storage/framework/telegram-cronicle.lock \
    /bin/bash -c '
        set -euo pipefail
        cd /var/www/malahlaundry
        /usr/bin/php artisan telegram:dispatch --no-interaction
        /usr/bin/php artisan queue:work telegram --queue=telegram \
            --stop-when-empty --max-time=35 --max-jobs=100 \
            --sleep=1 --tries=1 --timeout=30 --no-interaction
    '
