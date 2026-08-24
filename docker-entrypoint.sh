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

psql "$DATABASE_URL" -f /var/www/html/database/schema.sql

echo "Database initialization completed."

echo "Starting Apache..."

exec apache2-foreground
