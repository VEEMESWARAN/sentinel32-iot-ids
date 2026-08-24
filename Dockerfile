FROM php:8.3-apache

# ============================================================
# SENTINEL32 - Render Production Docker Image
# ============================================================

# Install PostgreSQL libraries, PostgreSQL CLI and CURL
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpq-dev \
        postgresql-client \
        libcurl4-openssl-dev \
    && docker-php-ext-install pdo_pgsql \
    && docker-php-ext-install curl \
    && a2enmod headers rewrite \
    && rm -rf /var/lib/apt/lists/*

# Application directory
WORKDIR /var/www/html

# Copy project
COPY . /var/www/html

# Make startup script executable
RUN chmod +x /var/www/html/docker-entrypoint.sh \
    && chown -R www-data:www-data /var/www/html

# Render uses PORT
ENV PORT=10000

EXPOSE 10000

# Configure Apache for Render's PORT and initialize database
ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
