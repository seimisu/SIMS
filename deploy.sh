#!/bin/bash

set -euo pipefail

PROJECT_DIR=~/SIMS
DEPLOY_DIR=/var/www/SIMS

if [ ! -d "$PROJECT_DIR" ]; then
  echo "Project directory not found: $PROJECT_DIR" >&2
  exit 1
fi

echo "=== SIMS Deployment ==="
cd "$PROJECT_DIR"

echo "[1/7] Pulling latest code..."
git pull origin revisions --ff-only

echo "[2/7] Installing npm dependencies..."
npm install

echo "[3/7] Installing composer dependencies..."
composer install

echo "[4/7] Building frontend..."
npm run build

echo "[5/7] Clearing application caches..."
php artisan optimize:clear

echo "[6/7] Copying files to NGINX directory..."
cd "$PROJECT_DIR"
sudo mkdir -p "$DEPLOY_DIR"
sudo cp -r * "$DEPLOY_DIR/"


PORTAL_UPLOADS="${PORTAL_UPLOADS_DIR:-/var/www/sei-scholars-portal-next/backend/uploads}"

echo "[7/7] Checking portal uploads storage..."

PORTAL_UPLOADS="/mnt/portalsimsfiles/uploads"

if ! sudo test -d "$PORTAL_UPLOADS"; then
    echo "Error: Portal uploads directory not found: $PORTAL_UPLOADS" >&2
    exit 1
fi

if ! sudo test -r "$PORTAL_UPLOADS" || ! sudo test -x "$PORTAL_UPLOADS"; then
    echo "Error: Portal uploads directory is not accessible: $PORTAL_UPLOADS" >&2
    exit 1
fi

echo "Portal uploads storage verified: $PORTAL_UPLOADS"

cd "$DEPLOY_DIR"
sudo php artisan config:clear

echo "=== Deployment complete ==="
