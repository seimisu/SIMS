#!/bin/bash

set -euo pipefail

PROJECT_DIR=~/sei-scholars-portal-next

if [ ! -d "$PROJECT_DIR" ]; then
  echo "Project directory not found: $PROJECT_DIR" >&2
  exit 1
fi

if ! command -v supervisorctl >/dev/null 2>&1; then
  echo "Supervisor is not installed or not available in PATH." >&2
  exit 1
fi

echo "=== Scholars Portal Deployment ==="
cd "$PROJECT_DIR"

echo "[1/9] Pulling latest code..."
git pull origin staging --ff-only

echo "[2/9] Installing frontend dependencies..."
cd "$PROJECT_DIR/frontend"
npm install

echo "[3/9] Building frontend..."
npm run build

echo "[4/9] Installing backend dependencies..."
cd "$PROJECT_DIR/backend"
npm install

# echo "[5/9] Deploying database migrations..."
# npx prisma migrate deploy --schema=prisma/portal.prisma

echo "[5/9] Generating Prisma clients and building backend..."
sudo rm -r src/generated/
npx prisma generate --schema=prisma/portal.prisma
npx prisma generate --schema=prisma/sims.prisma
npm run build
sudo mkdir -p ./dist/generated/portal
sudo mkdir -p ./dist/generated/sims
sudo cp -r src/generated/sims/*  ./dist/generated/sims
sudo cp -r src/generated/portal/*  ./dist/generated/portal

# SIMS public storage directory
SIMS_PUBLIC="/var/www/SIMS/storage/app/public"
SIMS_PRIVATE="/var/www/SIMS/storage/app/private/sensitive"

echo "[6/9] Creating symlink..."

# Create the directory if it doesn't exist
MNT_DIR="./mnt/sims-files"
mkdir -p "$MNT_DIR"

# Create symlinks only if they don't already exist
for DIR in documents video-thumbnails; do
    LINK="$MNT_DIR/$DIR"

    if [ "$DIR" = "documents" ]; then
        TARGET="$SIMS_PRIVATE/$DIR"
    else
        TARGET="$SIMS_PUBLIC/$DIR"
    fi

    if ! sudo test -d "$TARGET"; then
        echo "Error: SIMS directory not found: $TARGET"
        exit 1
    fi

    if [ -L "$LINK" ]; then
        if [ "$(readlink "$LINK")" = "$TARGET" ]; then
            echo "Symlink already configured: $LINK"
        else
            echo "Updating symlink: $LINK"
            sudo ln -sfn "$TARGET" "$LINK"
        fi
    elif [ -e "$LINK" ]; then
        echo "Error: $LINK exists but is not a symlink."
        exit 1
    else
        sudo ln -s "$TARGET" "$LINK"
        echo "Created symlink: $LINK -> $TARGET"
    fi
done

echo "SIMS storage links configured successfully."


echo "[7/9] Adding supervisor configuration..."
cd $PROJECT_DIR
sudo cp deployment/supervisor/sei-scholars-portal.conf \
    /etc/supervisor/conf.d/sei-scholars-portal.conf


echo "[8/9] Copying files to NGINX directory..."
cd "$PROJECT_DIR/frontend/dist"
sudo cp -r * /var/www/sei-scholars-portal-next/frontend/dist/
cd "$PROJECT_DIR/backend/"
sudo cp -r * /var/www/sei-scholars-portal-next/backend/
cd "$PROJECT_DIR/backend/"
sudo chown -R www-data:www-data /var/www/sei-scholars-portal-next/

echo "[9/9] Restarting Portal services via Supervisor..."
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart sei-scholars-api:*
sudo supervisorctl restart sei-scholars-worker:*

echo "=== Deployment complete ==="


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
git pull origin main --ff-only

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
