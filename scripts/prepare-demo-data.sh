#!/bin/bash
# Script ni copy uploaded files & database ke demo-* directories
# untuk di-commit dan di-deploy via GitHub Actions

echo ">>> Copy database..."
cp database/database.sqlite demo-database/database.sqlite

echo ">>> Copy storage/public (logos, favicons, photos)..."
cp storage/app/public/branding/logos/* demo-storage/public/branding/logos/ 2>/dev/null
cp storage/app/public/branding/favicons/* demo-storage/public/branding/favicons/ 2>/dev/null
cp storage/app/public/member-photos/* demo-storage/public/member-photos/ 2>/dev/null

echo ">>> Copy storage/private (documents)..."
cp storage/app/private/documents/* demo-storage/private/documents/ 2>/dev/null

echo ">>> Selesai! Sekarang git add & commit."
echo ">>> git add demo-storage/ demo-database/"
