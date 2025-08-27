FROM php:8.2-cli

# Install dependencies and the MongoDB extension
RUN apt-get update && apt-get install -y --no-install-recommends \
    libssl-dev pkg-config git unzip ca-certificates \
  && pecl install mongodb \
  && docker-php-ext-enable mongodb \
  && rm -rf /var/lib/apt/lists/*

# Install sockets extension for WebSocket functionality
RUN docker-php-ext-install sockets pcntl

# Copy application files
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose WebSocket port
EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
  CMD php -r "echo 'Health check OK';" || exit 1

# Run the WebSocket server with signal handling
CMD ["php", "/var/www/html/src/websocket/websocket_server.php"]
