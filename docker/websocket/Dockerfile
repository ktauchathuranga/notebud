FROM rust:1.88 as builder

WORKDIR /app

# Copy Cargo files first to cache dependencies
COPY src/websocket/notebud_websocket/Cargo.toml ./
COPY src/websocket/notebud_websocket/Cargo.lock* ./

# Create dummy main.rs to build dependencies
RUN mkdir src && echo "fn main() {}" > src/main.rs

# Build dependencies (this layer will be cached)
RUN cargo build --release
RUN rm src/main.rs

# Copy actual source code
COPY src/websocket/notebud_websocket/src/ src/

# Build the application
RUN cargo build --release

# Runtime stage
FROM debian:bookworm-slim

# Install required system dependencies
RUN apt-get update && \
    apt-get install -y \
        ca-certificates \
        libssl3 \
        libssl-dev \
        pkg-config && \
    rm -rf /var/lib/apt/lists/*

# Create a non-root user
RUN groupadd -r notebud && useradd -r -g notebud notebud

# Copy the binary from builder stage
COPY --from=builder /app/target/release/notebud_websocket /usr/local/bin/notebud_websocket

# Set proper permissions
RUN chmod +x /usr/local/bin/notebud_websocket

# Switch to non-root user
USER notebud

# Set environment variables
ENV RUST_LOG=info
ENV RUST_BACKTRACE=1

# Expose the port (Render will set PORT env var)
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
  CMD echo "Health check placeholder"

# Run the WebSocket server
CMD ["notebud_websocket"]
