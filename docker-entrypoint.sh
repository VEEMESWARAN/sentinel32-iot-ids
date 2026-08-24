#!/bin/bash

set -e

echo ""
echo "======================================"
echo " SENTINEL32 CONTAINER STARTING"
echo "======================================"
echo ""

# ============================================================
# 1. CHECK DATABASE_URL
# ============================================================

echo "[1/5] Checking DATABASE_URL..."

if [ -z "$DATABASE_URL" ]; then

    echo ""
    echo "ERROR: DATABASE_URL is not configured."
    echo "Add DATABASE_URL in Render Environment Variables."
    echo ""

    exit 1

fi

echo "DATABASE_URL detected."


# ============================================================
# 2. CHECK DATABASE SCHEMA
# ============================================================

echo ""
echo "[2/5] Checking PostgreSQL schema file..."

# IMPORTANT:
# GitHub folder is named "Database" with capital D.
# Linux/Render paths are case-sensitive.

SCHEMA_FILE="/var/www/html/Database/schema.sql"

if [ ! -f "$SCHEMA_FILE" ]; then

    echo ""
    echo "ERROR: PostgreSQL schema file not found."
    echo "Expected:"
    echo "$SCHEMA_FILE"
    echo ""
    echo "Make sure GitHub contains:"
    echo "Database/schema.sql"
    echo ""

    exit 1

fi

echo "Schema file found:"
echo "$SCHEMA_FILE"


# ============================================================
# 3. INITIALIZE POSTGRESQL
# ============================================================

echo ""
echo "[3/5] Initializing Sentinel32 PostgreSQL database..."
echo ""

# Check that psql actually exists

if ! command -v psql >/dev/null 2>&1; then

    echo "ERROR: psql command is unavailable."
    echo "Check that postgresql-client is installed in Dockerfile."

    exit 1

fi

# Execute schema.
#
# ON_ERROR_STOP ensures that deployment stops if the SQL
# contains an actual PostgreSQL error.

psql "$DATABASE_URL" \
    -v ON_ERROR_STOP=1 \
    -f "$SCHEMA_FILE"

echo ""
echo "PostgreSQL schema initialization completed."


# ============================================================
# 4. CONFIGURE APACHE FOR RENDER
# ============================================================

echo ""
echo "[4/5] Configuring Apache..."

PORT="${PORT:-10000}"

echo "Application port: $PORT"

# php:apache normally listens on port 80.
# Render expects the service to bind to $PORT.

sed -i \
    "s/^Listen 80$/Listen ${PORT}/" \
    /etc/apache2/ports.conf

sed -i \
    "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" \
    /etc/apache2/sites-available/000-default.conf

echo "Apache configured to listen on port $PORT."


# ============================================================
# 5. START APACHE
# ============================================================

echo ""
echo "[5/5] Starting Sentinel32 web server..."
echo ""

echo "======================================"
echo " SENTINEL32 READY"
echo "======================================"
echo ""

exec apache2-foreground
