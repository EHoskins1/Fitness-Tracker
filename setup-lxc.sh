#!/bin/bash
# =============================================================================
# FITNESS TRACKER - LXC CONTAINER SETUP SCRIPT
# =============================================================================
# Run this on a fresh Debian 12 or Ubuntu 22.04 LXC container
# 
# Usage:
#   1. Create LXC container in Proxmox (Debian 12, 1 CPU, 512MB RAM, 8GB disk)
#   2. Copy this script and fitness-tracker folder to the container
#   3. Run: chmod +x setup-lxc.sh && sudo ./setup-lxc.sh
# =============================================================================

set -e  # Exit on error

# Configuration - CHANGE THESE!
DB_ROOT_PASS="ChangeThisRootPassword123!"
DB_USER="fitness_user"
DB_PASS="ChangeThisUserPassword456!"
DB_NAME="fitness_tracker"
APP_DIR="/var/www/fitness-tracker"
SERVER_NAME="_"  # Use "_" for any hostname, or set your domain

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  FITNESS TRACKER SETUP SCRIPT${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Please run as root (sudo ./setup-lxc.sh)${NC}"
    exit 1
fi

# =============================================================================
# STEP 1: Update System
# =============================================================================
echo -e "${YELLOW}[1/8] Updating system packages...${NC}"
apt update && apt upgrade -y

# =============================================================================
# STEP 2: Install Required Packages
# =============================================================================
echo -e "${YELLOW}[2/8] Installing Apache, MariaDB, PHP...${NC}"
apt install -y \
    apache2 \
    mariadb-server \
    php \
    php-mysql \
    php-mbstring \
    php-json \
    php-curl \
    php-xml \
    unzip \
    curl

# =============================================================================
# STEP 3: Configure MariaDB
# =============================================================================
echo -e "${YELLOW}[3/8] Configuring MariaDB...${NC}"

# Start MariaDB
systemctl start mariadb
systemctl enable mariadb

# Secure MariaDB installation
mysql -u root <<EOF
-- Set root password
ALTER USER 'root'@'localhost' IDENTIFIED BY '${DB_ROOT_PASS}';

-- Remove anonymous users
DELETE FROM mysql.user WHERE User='';

-- Disallow root login remotely
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');

-- Remove test database
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';

-- Create fitness tracker database and user
CREATE DATABASE IF NOT EXISTS ${DB_NAME};
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF

echo -e "${GREEN}MariaDB configured successfully${NC}"

# =============================================================================
# STEP 4: Copy Application Files
# =============================================================================
echo -e "${YELLOW}[4/8] Setting up application files...${NC}"

# Check if fitness-tracker folder exists in current directory
if [ -d "./fitness-tracker" ]; then
    cp -r ./fitness-tracker ${APP_DIR}
    echo -e "${GREEN}Copied fitness-tracker to ${APP_DIR}${NC}"
elif [ -d "/root/fitness-tracker" ]; then
    cp -r /root/fitness-tracker ${APP_DIR}
    echo -e "${GREEN}Copied from /root/fitness-tracker to ${APP_DIR}${NC}"
else
    echo -e "${RED}ERROR: fitness-tracker folder not found!${NC}"
    echo -e "${YELLOW}Please copy the fitness-tracker folder to the current directory or /root/${NC}"
    echo -e "${YELLOW}Then run this script again.${NC}"
    exit 1
fi

# Create logs directory if it doesn't exist
mkdir -p ${APP_DIR}/logs
mkdir -p ${APP_DIR}/backups

# =============================================================================
# STEP 5: Import Database Schema
# =============================================================================
echo -e "${YELLOW}[5/8] Importing database schema...${NC}"

mysql -u ${DB_USER} -p${DB_PASS} ${DB_NAME} < ${APP_DIR}/database/schema.sql
echo -e "${GREEN}Database schema imported${NC}"

# =============================================================================
# STEP 6: Update Application Configuration
# =============================================================================
echo -e "${YELLOW}[6/8] Updating application configuration...${NC}"

# Update database.php with actual credentials
sed -i "s/define('DB_PASS', '.*');/define('DB_PASS', '${DB_PASS}');/" ${APP_DIR}/config/database.php
sed -i "s/define('DB_USER', '.*');/define('DB_USER', '${DB_USER}');/" ${APP_DIR}/config/database.php
sed -i "s/define('DB_NAME', '.*');/define('DB_NAME', '${DB_NAME}');/" ${APP_DIR}/config/database.php

# Set to production mode
sed -i "s/define('APP_ENV', 'development');/define('APP_ENV', 'production');/" ${APP_DIR}/config/config.php

echo -e "${GREEN}Configuration updated${NC}"

# =============================================================================
# STEP 7: Configure Apache
# =============================================================================
echo -e "${YELLOW}[7/8] Configuring Apache...${NC}"

# Enable required modules
a2enmod rewrite
a2enmod headers

# Create virtual host configuration
cat > /etc/apache2/sites-available/fitness-tracker.conf <<EOF
<VirtualHost *:80>
    ServerName ${SERVER_NAME}
    DocumentRoot ${APP_DIR}/public
    
    <Directory ${APP_DIR}/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security headers
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    
    # Logging
    ErrorLog \${APACHE_LOG_DIR}/fitness-tracker-error.log
    CustomLog \${APACHE_LOG_DIR}/fitness-tracker-access.log combined
</VirtualHost>
EOF

# Create .htaccess for URL rewriting
cat > ${APP_DIR}/public/.htaccess <<'EOF'
RewriteEngine On

# Redirect to HTTPS (uncomment when you have SSL)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Handle Authorization Header
RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

# Handle Front Controller
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
EOF

# Disable default site, enable fitness tracker
a2dissite 000-default.conf
a2ensite fitness-tracker.conf

# =============================================================================
# STEP 8: Set Permissions
# =============================================================================
echo -e "${YELLOW}[8/8] Setting file permissions...${NC}"

chown -R www-data:www-data ${APP_DIR}
chmod -R 755 ${APP_DIR}
chmod -R 775 ${APP_DIR}/logs
chmod -R 775 ${APP_DIR}/backups

# Restart Apache
systemctl restart apache2
systemctl enable apache2

# =============================================================================
# COMPLETE!
# =============================================================================
echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  SETUP COMPLETE!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Get container IP
CONTAINER_IP=$(hostname -I | awk '{print $1}')

echo -e "Access your Fitness Tracker at:"
echo -e "  ${GREEN}http://${CONTAINER_IP}/${NC}"
echo ""
echo -e "Database Credentials (save these!):"
echo -e "  Database: ${DB_NAME}"
echo -e "  User:     ${DB_USER}"
echo -e "  Password: ${DB_PASS}"
echo ""
echo -e "${YELLOW}IMPORTANT: Change the passwords in this script before running!${NC}"
echo ""
echo -e "Next steps:"
echo -e "  1. Open http://${CONTAINER_IP}/ in your browser"
echo -e "  2. Register your first user account"
echo -e "  3. (Optional) Set up SSL with Let's Encrypt"
echo ""
echo -e "${GREEN}Enjoy your Fitness Tracker!${NC}"
