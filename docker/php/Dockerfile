FROM php:8.2-apache

# Install dependencies and the MongoDB extension
RUN apt-get update && apt-get install -y --no-install-recommends \
    libssl-dev pkg-config git unzip ca-certificates \
  && pecl install mongodb \
  && docker-php-ext-enable mongodb \
  && a2enmod rewrite \
  && rm -rf /var/lib/apt/lists/*

# Copy custom Apache vhost that sets DocumentRoot to /var/www/html/public
COPY docker/php/000-default.conf /etc/apache2/sites-available/000-default.conf

# Copy application files
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
