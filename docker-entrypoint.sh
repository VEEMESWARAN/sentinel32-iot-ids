#!/bin/bash
set -e

echo "======================================"
echo " Sentinel32 Container Starting"
echo "======================================"

echo "Checking DATABASE_URL..."

if [ -z "$DATABASE_URL" ]; then
    echo "ERROR: DATABASE_URL is not configured."
    exit 1
fi

echo "DATABASE_URL detected."

echo "Initializing Sentinel32 PostgreSQL schema..."

psql "$DATABASE_URL" \
    -v ON_ERROR_STOP=1 \
    -f /var/www/html/database/schema.sql

echo "Database initialization completed."

# Render requires the application to listen on $PORT
PORT="${PORT:-10000}"

sed -i "s/Listen 80/Listen ${PORT}/" \
    /etc/apache2/ports.conf

sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" \
    /etc/apache2/sites-available/000-default.conf

echo "Apache configured on port ${PORT}"

echo "======================================"
echo " Sentinel32 Ready"
echo "======================================"

exec apache2-foreground
