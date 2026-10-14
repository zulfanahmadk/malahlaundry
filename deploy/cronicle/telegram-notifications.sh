#!/bin/bash
set -euo pipefail

# Cronicle runs as the cronicle account. Sudo permits only this root-owned wrapper.
exec /usr/bin/sudo -n -u www-data /usr/local/libexec/malahlaundry/telegram-notifications
