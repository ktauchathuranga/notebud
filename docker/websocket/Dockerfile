FROM php:8.2-cli

# Install dependencies and the MongoDB extension
RUN apt-get update && apt-get install -y --no-install-recommends \
    libssl-dev pkg-config git unzip ca-certificates \
  && pecl install mongodb \
  && docker-php-ext-enable mongodb \
  && rm -rf /var/lib/apt/lists/*

# Install sockets extension for WebSocket functionality
RUN docker-php-ext-install sockets

# Copy application files
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Expose WebSocket port
EXPOSE 8080

# Run the WebSocket server
CMD ["php", "/var/www/html/src/websocket/websocket_server.php"]
