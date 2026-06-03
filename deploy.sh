#!/bin/bash
set -e

HOST="160.30.5.131"
USER="root"
PORT="22"
IDENTITY="$HOME/.ssh/koperasihub"
REMOTE_PATH="/home/koperasihub.my/public_html"

echo "=== 1. Delete public/hot (jika ada) ==="
rm -f public/hot

echo "=== 2. Rsync ke VPS ==="
rsync -avzr --delete \
  -e "ssh -i $IDENTITY -p $PORT" \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='node_modules' \
  --exclude='storage/framework' \
  --exclude='storage/logs' \
  --exclude='storage/app/private' \
  --exclude='public/storage' \
  --exclude='public/hot' \
  ./ "$USER@$HOST:$REMOTE_PATH"

echo "=== 3. SSH: Fix ownership + rebuild ==="
ssh -i "$IDENTITY" -p "$PORT" "$USER@$HOST" << 'EOF'
  set -e
  cd /home/koperasihub.my/public_html

  echo "--- Fix ownership ---"
  chown koper9666:koper9666 .
  chmod 755 .
  chmod 664 database/database.sqlite
  chmod 777 database
  chmod -R 775 storage bootstrap/cache

  echo "--- Install PHP deps ---"
  composer install --no-dev --optimize-autoloader

  echo "--- Build frontend ---"
  npm ci && npm run build

  echo "--- Migration ---"
  php artisan migrate --force

  echo "--- Cache ---"
  php artisan optimize:clear
  php artisan config:cache
  php artisan route:cache

  echo "--- Storage link ---"
  php artisan storage:link --force

  echo "--- Final permissions ---"
  chown -R koper9666:koper9666 .
  chmod -R 775 storage bootstrap/cache

  echo "✓ Deploy selesai!"
EOF
