#!/bin/bash
# Script ni copy uploaded files & database ke demo-* directories
# untuk di-commit dan di-deploy via GitHub Actions

echo ">>> Copy database..."
cp database/database.sqlite demo-database/database.sqlite

echo ">>> Copy semua storage/public (branding, news, financing, media, posters, sections)..."
rsync -a storage/app/public/ demo-storage/public/ --exclude='.gitignore'

echo ">>> Copy storage/private..."
rsync -a storage/app/private/ demo-storage/private/ --exclude='.gitignore'

echo ">>> Selesai! Sekarang git add & commit."
echo ">>> git add demo-storage/ demo-database/"