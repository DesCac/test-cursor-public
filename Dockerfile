FROM php:8.3-fpm-bookworm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libpq-dev \
    nodejs \
    npm \
    && docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV COMPOSER_HOME=/var/www/.composer \
    NPM_CONFIG_CACHE=/var/www/.npm-cache

RUN mkdir -p "$COMPOSER_HOME" "$NPM_CONFIG_CACHE"

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies required for the baked image (actual dev install runs via make)
RUN composer install --no-interaction --optimize-autoloader --no-dev || true

# Ensure caches belong to the runtime user
RUN chown -R www-data:www-data /var/www

# Set permissions
RUN chmod -R 755 /var/www/html

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
