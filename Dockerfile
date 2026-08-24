# ============================================================
# SENTINEL32 IoT NETWORK IDS
# Production Docker Image for Render
# PHP 8.3 + Apache + PostgreSQL
# ============================================================

FROM php:8.3-apache

# ------------------------------------------------------------
# Install required system packages
# ------------------------------------------------------------
# libpq-dev          = PostgreSQL development libraries
# postgresql-client  = provides the psql command
# libcurl dev        = required for PHP cURL / Telegram API
# ------------------------------------------------------------

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpq-dev \
        postgresql-client \
        libcurl4-openssl-dev \
        ca-certificates \
    && docker-php-ext-install pdo_pgsql \
    && docker-php-ext-install curl \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# ------------------------------------------------------------
# Application directory
# ------------------------------------------------------------

WORKDIR /var/www/html

# ------------------------------------------------------------
# Copy complete Sentinel32 project
# ------------------------------------------------------------

COPY . /var/www/html

# ------------------------------------------------------------
# Permissions
# ------------------------------------------------------------

RUN chmod +x /var/www/html/docker-entrypoint.sh \
    && chown -R www-data:www-data /var/www/html

# ------------------------------------------------------------
# Render default application port
# ------------------------------------------------------------

ENV PORT=10000

EXPOSE 10000

# ------------------------------------------------------------
# Container startup
# ------------------------------------------------------------

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
