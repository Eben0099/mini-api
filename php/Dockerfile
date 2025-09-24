FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    zlib1g-dev g++ git libicu-dev libzip-dev zip unzip curl \
    && docker-php-ext-install intl opcache pdo pdo_mysql zip \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules for rewrite, SSL, cache
RUN a2enmod rewrite ssl socache_shmcb

WORKDIR /var/www

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Symfony CLI globally
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony
