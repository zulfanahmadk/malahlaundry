#!/bin/bash
set -euo pipefail

# Cronicle's Shell Script plugin runs this as root on malahsite.
# Run Laravel on the application account and serialize manual/scheduled executions.
exec /usr/bin/sudo -n -u www-data /usr/bin/flock -n -E 0 \
    /var/www/malahlaundry/storage/framework/telegram-cronicle.lock \
    /bin/bash -c '
        set -euo pipefail
        cd /var/www/malahlaundry
        /usr/bin/php artisan telegram:dispatch --no-interaction
        /usr/bin/php artisan queue:work telegram --queue=telegram \
            --stop-when-empty --max-time=35 --max-jobs=100 \
            --sleep=1 --tries=1 --timeout=30 --no-interaction
    '
