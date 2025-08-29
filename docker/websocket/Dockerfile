FROM rust:1.88 as builder

WORKDIR /app
COPY src/websocket/notebud_websocket/Cargo.toml ./
# Copy Cargo.lock if it exists
COPY src/websocket/notebud_websocket/Cargo.lock* ./
COPY src/websocket/notebud_websocket/src/ src/

# Build the application
RUN cargo build --release

# Runtime stage
FROM debian:bookworm-slim

# Install required system dependencies
RUN apt-get update && \
    apt-get install -y ca-certificates && \
    rm -rf /var/lib/apt/lists/*

# Copy the binary from builder stage
COPY --from=builder /app/target/release/notebud_websocket /usr/local/bin/notebud_websocket

# Set environment variables
ENV RUST_LOG=info
ENV WEBSOCKET_BIND_ADDRESS=0.0.0.0:8080

# Expose the WebSocket port
EXPOSE 8080 8081

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
  CMD timeout 5s bash -c 'cat < /dev/null > /dev/tcp/localhost/8080' || exit 1

# Run the WebSocket server
CMD ["notebud_websocket"]
