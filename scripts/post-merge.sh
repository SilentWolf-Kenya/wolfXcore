#!/bin/bash
set -e

echo "=== wolfXcore post-merge setup ==="

# Install/update PHP dependencies if composer.json changed
if [ -f "composer.json" ]; then
    echo "Running composer install..."
    php /usr/local/bin/composer install --no-interaction --no-progress 2>/dev/null \
        || php composer.phar install --no-interaction --no-progress 2>/dev/null \
        || echo "Composer not available in this environment, skipping."
fi

# Clear Laravel caches so new views/routes/config take effect
echo "Clearing Laravel caches..."
php artisan view:clear --no-interaction 2>/dev/null || true
php artisan config:clear --no-interaction 2>/dev/null || true
php artisan route:clear --no-interaction 2>/dev/null || true

echo "=== Post-merge complete ==="
