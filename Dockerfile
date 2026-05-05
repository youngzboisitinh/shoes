FROM php:8.1-apache

# Set working directory
WORKDIR /var/www/html

# Install system packages and PHP extensions required for MySQL
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev zip curl \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && docker-php-ext-enable pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module and set UTF-8 charset
RUN a2enmod rewrite \
    && echo 'AddDefaultCharset UTF-8' >> /etc/apache2/apache2.conf

# Copy application files
COPY . /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

# Simple healthcheck
HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
  CMD curl -f http://localhost/ || exit 1

CMD ["apache2-foreground"]
