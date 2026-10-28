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

echo "[7/7] Creating symlink..."
TARGET="$PORTAL_UPLOADS"
MNT_DIR="$DEPLOY_DIR/storage/app/private"
LINK="$MNT_DIR/portal-files"

if [[ "$TARGET" != /* ]] || ! sudo test -d "$TARGET"; then
  echo "Portal uploads directory not found or not absolute: $TARGET" >&2
  exit 1
fi

sudo mkdir -p "$MNT_DIR"

if sudo test -L "$LINK"; then
  if [ "$(sudo readlink "$LINK")" = "$TARGET" ]; then
    echo "Symlink already configured: $LINK"
  else
    echo "Updating symlink: $LINK"
    sudo ln -sfnT "$TARGET" "$LINK"
  fi
elif sudo test -e "$LINK"; then
  echo "Error: $LINK exists but is not a symlink." >&2
  exit 1
else
  sudo ln -s "$TARGET" "$LINK"
  echo "Created symlink: $LINK -> $TARGET"
fi

if ! sudo test -d "$LINK" || ! sudo test -r "$LINK" || ! sudo test -x "$LINK"; then
  echo "Portal storage is not accessible: $LINK" >&2
  exit 1
fi

echo "Portal storage link configured successfully."

cd "$DEPLOY_DIR"
sudo php artisan config:clear

echo "=== Deployment complete ==="
