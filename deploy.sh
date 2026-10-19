#!/bin/bash

set -euo pipefail

PROJECT_DIR=~/SIMS

if [ ! -d "$PROJECT_DIR" ]; then
  echo "Project directory not found: $PROJECT_DIR" >&2
  exit 1
fi

echo "=== SIMS Deployment ==="
cd "$PROJECT_DIR"

echo "[1/6] Pulling latest code..."
git pull origin main --ff-only

echo "[2/6] Installing npm dependencies..."
npm install

echo "[3/6] Installing composer dependencies..."
composer install

echo "[4/6] Building.."
npm run build

echo "[5/6] Optimising build"
php artisan optimize:clear

echo "[6/6] Copying files to NGINX"
cd $PROJECT_DIR
sudo cp -r * /var/www/SIMS


echo "=== Deployment complete ==="