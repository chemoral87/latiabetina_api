#!/bin/bash

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

APP_DIR="/var/www/backend"
RELEASES_DIR="$APP_DIR/releases"
SHARED_DIR="$APP_DIR/shared"
CURRENT_LINK="$APP_DIR/current"
REPO_URL="https://github.com/chemoral87/latiabetina_api.git"
RELEASE_NAME="${1:-$(date +%Y%m%d%H%M%S)}"
RELEASE_PATH="$RELEASES_DIR/$RELEASE_NAME"
KEEP_RELEASES=5

error_exit() {
  echo -e "${RED}❌ Error: $1${NC}"
  exit 1
}

echo -e "${YELLOW}🚀 Starting deployment: $RELEASE_NAME${NC}"

# Ensure directories exist
mkdir -p "$RELEASES_DIR"
mkdir -p "$SHARED_DIR"
mkdir -p "$SHARED_DIR/storage"
mkdir -p "$SHARED_DIR/storage/app"
mkdir -p "$SHARED_DIR/storage/framework"
mkdir -p "$SHARED_DIR/storage/framework/cache"
mkdir -p "$SHARED_DIR/storage/framework/sessions"
mkdir -p "$SHARED_DIR/storage/framework/views"
mkdir -p "$SHARED_DIR/storage/logs"

# Clone repo
echo -e "${YELLOW}📦 Cloning repository...${NC}"
git clone --depth 1 "$REPO_URL" "$RELEASE_PATH" || error_exit "Git clone failed"

cd "$RELEASE_PATH" || error_exit "Cannot enter release directory"

# Checkout specific commit if provided
if [ -n "$1" ]; then
  git fetch --depth 1 origin "$1" || true
  git checkout "$1" 2>/dev/null || true
fi

# Link shared files
echo -e "${YELLOW}🔗 Linking shared files...${NC}"

# .env
if [ -f "$APP_DIR/.env" ]; then
  ln -sfn "$APP_DIR/.env" "$RELEASE_PATH/.env" || error_exit "Failed to link .env"
else
  echo -e "${RED}⚠️ .env not found at $APP_DIR/.env${NC}"
fi

# storage
rm -rf "$RELEASE_PATH/storage"
ln -sfn "$SHARED_DIR/storage" "$RELEASE_PATH/storage" || error_exit "Failed to link storage"

# Install dependencies
echo -e "${YELLOW}📦 Installing composer dependencies...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction || error_exit "Composer install failed"

# Database migrations
echo -e "${YELLOW}🗄️ Running migrations...${NC}"
php artisan migrate --force || error_exit "Migrations failed"

# Optimize Laravel
echo -e "${YELLOW}⚡ Optimizing Laravel...${NC}"
php artisan optimize || error_exit "Optimization failed"
php artisan view:cache || error_exit "View cache failed"

# Update current symlink
echo -e "${YELLOW}🔗 Updating current symlink...${NC}"
ln -sfn "$RELEASE_PATH" "$CURRENT_LINK" || error_exit "Symlink update failed"

# Restarting services
echo -e "${YELLOW}🔄 Restarting services...${NC}"

# Restart PHP-FPM to clear OPcache
sudo systemctl reload php8.2-fpm || echo -e "${RED}⚠️ Could not reload php8.2-fpm${NC}"

# Restart queue workers
php artisan queue:restart || true

# Restart Reverb via Supervisor or specific service
# sudo supervisorctl restart reverb || echo -e "${RED}⚠️ Could not restart Reverb service${NC}"

# Reload nginx
echo -e "${YELLOW}🔄 Reloading nginx...${NC}"
sudo nginx -t && sudo systemctl reload nginx || echo -e "${RED}⚠️ Could not reload nginx${NC}"

# Keep only last $KEEP_RELEASES
echo -e "${YELLOW}🧹 Cleaning old releases (keeping last $KEEP_RELEASES)...${NC}"
cd "$RELEASES_DIR"
ls -1dt */ | tail -n +$((KEEP_RELEASES + 1)) | xargs -r rm -rf

echo -e "${GREEN}✅ Deployment $RELEASE_NAME completed successfully!${NC}"
