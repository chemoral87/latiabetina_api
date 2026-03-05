#!/bin/bash

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

APP_DIR="/var/www/backend"
RELEASES_DIR="$APP_DIR/releases"
CURRENT_LINK="$APP_DIR/current"

error_exit() {
  echo -e "${RED}❌ Error: $1${NC}"
  exit 1
}

if [ -n "$1" ]; then
    ROLLBACK_TO="$RELEASES_DIR/$1"
    if [ ! -d "$ROLLBACK_TO" ]; then
        error_exit "Release $1 not found"
    fi
else
    # Automatically find previous release
    PREVIOUS_RELEASE=$(ls -1dt "$RELEASES_DIR"/*/ | sed -n '2p')
    if [ -z "$PREVIOUS_RELEASE" ]; then
        error_exit "No previous release found to rollback to"
    fi
    ROLLBACK_TO=${PREVIOUS_RELEASE%/}
fi

echo -e "${YELLOW}⏪ Rolling back to: $(basename "$ROLLBACK_TO")${NC}"

# Update current symlink
ln -sfn "$ROLLBACK_TO" "$CURRENT_LINK" || error_exit "Symlink update failed"

# Restart services
echo -e "${YELLOW}🔄 Restarting services...${NC}"
cd "$CURRENT_LINK"

# Restart PHP-FPM to clear OPcache
sudo systemctl reload php8.2-fpm || echo -e "${RED}⚠️ Could not reload php8.2-fpm${NC}"

# Restart queue workers
php artisan queue:restart || true

# Reload nginx
echo -e "${YELLOW}🔄 Reloading nginx...${NC}"
sudo nginx -t && sudo systemctl reload nginx || echo -e "${RED}⚠️ Could not reload nginx${NC}"

echo -e "${GREEN}✅ Rollback completed successfully!${NC}"
