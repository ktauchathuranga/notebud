mod auth;
mod database;
mod handlers;
mod types;

use std::env;
use std::net::SocketAddr;
use std::sync::Arc;

use anyhow::Result;
use futures_util::{SinkExt, StreamExt};
use tokio::net::TcpListener;
use tokio::sync::Mutex;
use tokio_tungstenite::{accept_async, WebSocketStream, tungstenite::Message};

use crate::auth::JwtValidator;
use crate::database::DatabaseManager;
use crate::handlers::MessageHandler;
use crate::types::IncomingMessage;

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    // Initialize logging
    env_logger::init();

    // Load environment variables
    dotenv::dotenv().ok();

    // Get port from Render's PORT environment variable, fallback to 8080
    let port = env::var("PORT").unwrap_or_else(|_| "8080".to_string());
    let bind_address = format!("0.0.0.0:{}", port);

    let db_name = env::var("DB_NAME").unwrap_or_else(|_| "notebud".to_string());
    let jwt_secret = env::var("JWT_SECRET").expect("JWT_SECRET environment variable is required");

    // Build MongoDB connection string using the same logic as PHP
    let connection_string = build_mongodb_connection_string(&db_name);

    log::info!("Starting notebud server...");
    log::info!("Server will listen on {}", bind_address);
    log::info!("Connecting to MongoDB...");
    log::debug!(
        "MongoDB connection string: {}",
        mask_password(&connection_string)
    );

    // Initialize database connection
    let db = DatabaseManager::new(&connection_string, &db_name).await?;
    log::info!("Database connection established");

    // Initialize JWT validator
    let jwt_validator = JwtValidator::new(jwt_secret);

    // Initialize message handler
    let message_handler = Arc::new(Mutex::new(MessageHandler::new(db, jwt_validator)));

    // Start pure WebSocket server
    let listener = TcpListener::bind(&bind_address).await?;
    log::info!("Pure WebSocket server listening on {}", bind_address);

    while let Ok((stream, _)) = listener.accept().await {
        let handler = Arc::clone(&message_handler);
        tokio::spawn(async move {
            match accept_async(stream).await {
                Ok(ws_stream) => {
                    handle_websocket_stream(ws_stream, handler).await;
                }
                Err(e) => {
                    log::error!("WebSocket handshake failed: {}", e);
                }
            }
        });
    }

    Ok(())
}

async fn handle_websocket_stream(
    ws_stream: WebSocketStream<tokio::net::TcpStream>,
    message_handler: Arc<Mutex<MessageHandler>>,
) {
    log::info!("WebSocket connection established");

    let (mut ws_sender, mut ws_receiver) = ws_stream.split();
    let (tx, mut rx) = tokio::sync::mpsc::unbounded_channel::<Message>();

    // Register client
    let client_id = {
        let mut handler = message_handler.lock().await;
        handler.add_client(tx)
    };

    log::info!("WebSocket client {} registered", client_id);

    // Spawn task to forward messages to WebSocket
    let ws_sender_task = tokio::spawn(async move {
        while let Some(message) = rx.recv().await {
            if ws_sender.send(message).await.is_err() {
                break;
            }
        }
    });

    // Handle incoming messages
    while let Some(msg) = ws_receiver.next().await {
        match msg {
            Ok(Message::Text(text)) => {
                log::debug!("Received message from {}: {}", client_id, text);

                // Parse incoming message
                match serde_json::from_str::<IncomingMessage>(&text) {
                    Ok(parsed_msg) => {
                        let mut handler = message_handler.lock().await;
                        if let Err(e) = handler.handle_message(client_id, parsed_msg).await {
                            log::error!("Error handling message from {}: {}", client_id, e);
                        }
                    }
                    Err(e) => {
                        log::warn!("Failed to parse message from {}: {}", client_id, e);
                    }
                }
            }
            Ok(Message::Close(_)) => {
                log::info!("WebSocket client {} disconnected", client_id);
                break;
            }
            Ok(_) => {
                // Handle other message types if needed
            }
            Err(e) => {
                log::error!("WebSocket error for client {}: {}", client_id, e);
                break;
            }
        }
    }

    // Clean up
    ws_sender_task.abort();
    let mut handler = message_handler.lock().await;
    handler.remove_client(client_id).await;
    log::info!("WebSocket client {} cleaned up", client_id);
}

/// Build MongoDB connection string using the same priority logic as PHP db.php
fn build_mongodb_connection_string(db_name: &str) -> String {
    // First priority: Check for MONGODB_URI (for Atlas/cloud)
    if let Ok(mongodb_uri) = env::var("MONGODB_URI") {
        if !mongodb_uri.trim().is_empty() {
            log::info!("Using MONGODB_URI for cloud/Atlas connection");
            return mongodb_uri;
        }
    }

    // Second priority: Build from individual components (for local Docker)
    log::info!("Building connection string from individual components");

    let db_host = env::var("DB_HOST").unwrap_or_else(|_| "mongo".to_string());
    let db_port = env::var("DB_PORT").unwrap_or_else(|_| "27017".to_string());
    let db_user = env::var("DB_USER").unwrap_or_default();
    let db_pass = env::var("DB_PASS").unwrap_or_default();

    let mut uri = "mongodb://".to_string();

    // Add authentication if provided
    if !db_user.trim().is_empty() && !db_pass.trim().is_empty() {
        uri.push_str(&format!(
            "{}:{}@",
            urlencoding::encode(&db_user),
            urlencoding::encode(&db_pass)
        ));
    }

    // Add host, port, and database
    uri.push_str(&format!("{}:{}/{}", db_host, db_port, db_name));

    // Add auth source if we have credentials
    if !db_user.trim().is_empty() && !db_pass.trim().is_empty() {
        uri.push_str("?authSource=admin");
    }

    uri
}

/// Remove sensitive information from connection string for logging
fn mask_password(connection_string: &str) -> String {
    if let Some(at_pos) = connection_string.find('@') {
        if let Some(colon_pos) = connection_string[..at_pos].rfind(':') {
            if let Some(scheme_end) = connection_string.find("://") {
                let scheme_end = scheme_end + 3;
                if colon_pos > scheme_end {
                    let mut masked = connection_string.to_string();
                    masked.replace_range(colon_pos + 1..at_pos, "****");
                    return masked;
                }
            }
        }
    }
    connection_string.to_string()
}
```

**Solution 2: More Robust - Combined HTTP/WebSocket Server with Upgrade Handling**

This is the original provided code, which already handles both HTTP (for health checks and root) and WebSocket upgrades on the same port using Hyper. It supports Render's HTTP health checks fully. The rest of the modules (auth.rs, database.rs, handlers.rs, types.rs) remain unchanged. Dependencies like `sha1` and `base64` are assumed to be in `Cargo.toml` as needed.

```rust
mod auth;
mod database;
mod handlers;
mod types;

use std::convert::Infallible;
use std::env;
use std::net::SocketAddr;
use std::sync::Arc;

use futures_util::{SinkExt, StreamExt};
use hyper::service::{make_service_fn, service_fn};
use hyper::{Body, Method, Request, Response, Server, StatusCode};
use tokio::sync::Mutex;
use tokio_tungstenite::{WebSocketStream, tungstenite::Message};

use crate::auth::JwtValidator;
use crate::database::DatabaseManager;
use crate::handlers::MessageHandler;
use crate::types::IncomingMessage;

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    // Initialize logging
    env_logger::init();

    // Load environment variables
    dotenv::dotenv().ok();

    // Get port from Render's PORT environment variable, fallback to 8080
    let port = env::var("PORT").unwrap_or_else(|_| "8080".to_string());
    let http_port = port.parse::<u16>().unwrap_or(8080);

    let bind_address = format!("0.0.0.0:{}", http_port);

    let db_name = env::var("DB_NAME").unwrap_or_else(|_| "notebud".to_string());
    let jwt_secret = env::var("JWT_SECRET").expect("JWT_SECRET environment variable is required");

    // Build MongoDB connection string using the same logic as PHP
    let connection_string = build_mongodb_connection_string(&db_name);

    log::info!("Starting notebud server...");
    log::info!("Server will listen on {}", bind_address);
    log::info!("Connecting to MongoDB...");
    log::debug!(
        "MongoDB connection string: {}",
        mask_password(&connection_string)
    );

    // Initialize database connection
    let db = DatabaseManager::new(&connection_string, &db_name).await?;
    log::info!("Database connection established");

    // Initialize JWT validator
    let jwt_validator = JwtValidator::new(jwt_secret);

    // Initialize message handler
    let message_handler = Arc::new(Mutex::new(MessageHandler::new(db, jwt_validator)));

    // Start HTTP server that also handles WebSocket upgrades
    let addr: SocketAddr = bind_address.parse()?;
    let handler = Arc::clone(&message_handler);

    let make_svc = make_service_fn(move |_conn| {
        let handler = Arc::clone(&handler);
        async move {
            Ok::<_, Infallible>(service_fn(move |req| {
                let handler = Arc::clone(&handler);
                handle_request(req, handler)
            }))
        }
    });

    let server = Server::bind(&addr).serve(make_svc);
    log::info!(
        "Combined HTTP/WebSocket server listening on {}",
        bind_address
    );

    if let Err(e) = server.await {
        log::error!("Server error: {}", e);
    }

    Ok(())
}

async fn handle_request(
    mut req: Request<Body>,
    message_handler: Arc<Mutex<MessageHandler>>,
) -> Result<Response<Body>, Infallible> {
    // Check if this is a WebSocket upgrade request
    if is_websocket_upgrade(&req) {
        log::info!("WebSocket upgrade request received");

        match hyper::upgrade::on(&mut req).await {
            Ok(upgraded) => {
                // Spawn WebSocket handler
                tokio::spawn(async move {
                    // Convert upgraded connection to WebSocket
                    let ws_stream = WebSocketStream::from_raw_socket(
                        upgraded,
                        tokio_tungstenite::tungstenite::protocol::Role::Server,
                        None,
                    )
                    .await;

                    handle_websocket_stream(ws_stream, message_handler).await;
                });

                // Return WebSocket upgrade response
                let response = Response::builder()
                    .status(StatusCode::SWITCHING_PROTOCOLS)
                    .header("upgrade", "websocket")
                    .header("connection", "Upgrade")
                    .header("sec-websocket-accept", compute_websocket_accept(&req))
                    .body(Body::empty())
                    .unwrap();

                Ok(response)
            }
            Err(e) => {
                log::error!("Failed to upgrade connection: {}", e);
                Ok(Response::builder()
                    .status(StatusCode::BAD_REQUEST)
                    .body(Body::from("Failed to upgrade to WebSocket"))
                    .unwrap())
            }
        }
    } else {
        // Handle regular HTTP requests
        handle_http_request(req).await
    }
}

fn is_websocket_upgrade(req: &Request<Body>) -> bool {
    req.headers()
        .get("upgrade")
        .and_then(|h| h.to_str().ok())
        .map(|h| h.to_lowercase() == "websocket")
        .unwrap_or(false)
        && req
            .headers()
            .get("connection")
            .and_then(|h| h.to_str().ok())
            .map(|h| h.to_lowercase().contains("upgrade"))
            .unwrap_or(false)
        && req.headers().get("sec-websocket-key").is_some()
        && req.method() == Method::GET
}

fn compute_websocket_accept(req: &Request<Body>) -> String {
    use sha1::{Digest, Sha1};

    const WEBSOCKET_MAGIC: &str = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";

    if let Some(key) = req.headers().get("sec-websocket-key") {
        if let Ok(key_str) = key.to_str() {
            let mut hasher = Sha1::new();
            hasher.update(key_str.as_bytes());
            hasher.update(WEBSOCKET_MAGIC.as_bytes());
            let hash = hasher.finalize();
            return base64::encode(hash);
        }
    }

    String::new()
}

async fn handle_websocket_stream(
    ws_stream: WebSocketStream<hyper::upgrade::Upgraded>,
    message_handler: Arc<Mutex<MessageHandler>>,
) {
    log::info!("WebSocket connection established");

    let (mut ws_sender, mut ws_receiver) = ws_stream.split();
    let (tx, mut rx) = tokio::sync::mpsc::unbounded_channel::<Message>();

    // Register client
    let client_id = {
        let mut handler = message_handler.lock().await;
        handler.add_client(tx)
    };

    log::info!("WebSocket client {} registered", client_id);

    // Spawn task to forward messages to WebSocket
    let ws_sender_task = tokio::spawn(async move {
        while let Some(message) = rx.recv().await {
            if ws_sender.send(message).await.is_err() {
                break;
            }
        }
    });

    // Handle incoming messages
    while let Some(msg) = ws_receiver.next().await {
        match msg {
            Ok(Message::Text(text)) => {
                log::debug!("Received message from {}: {}", client_id, text);

                // Parse incoming message
                match serde_json::from_str::<IncomingMessage>(&text) {
                    Ok(parsed_msg) => {
                        let mut handler = message_handler.lock().await;
                        if let Err(e) = handler.handle_message(client_id, parsed_msg).await {
                            log::error!("Error handling message from {}: {}", client_id, e);
                        }
                    }
                    Err(e) => {
                        log::warn!("Failed to parse message from {}: {}", client_id, e);
                    }
                }
            }
            Ok(Message::Close(_)) => {
                log::info!("WebSocket client {} disconnected", client_id);
                break;
            }
            Ok(_) => {
                // Handle other message types if needed
            }
            Err(e) => {
                log::error!("WebSocket error for client {}: {}", client_id, e);
                break;
            }
        }
    }

    // Clean up
    ws_sender_task.abort();
    let mut handler = message_handler.lock().await;
    handler.remove_client(client_id).await;
    log::info!("WebSocket client {} cleaned up", client_id);
}

async fn handle_http_request(req: Request<Body>) -> Result<Response<Body>, Infallible> {
    match (req.method(), req.uri().path()) {
        // Health check endpoint
        (&Method::GET, "/health") => {
            log::debug!("Health check requested");
            Ok(Response::builder()
                .status(StatusCode::OK)
                .header("Content-Type", "application/json")
                .body(Body::from(
                    r#"{"status":"ok","service":"notebud-websocket"}"#,
                ))
                .unwrap())
        }

        // Root endpoint with service info
        (&Method::GET, "/") => {
            let port = env::var("PORT").unwrap_or_else(|_| "8080".to_string());

            let response_body = format!(
                r#"{{"status":"ok","service":"notebud-websocket","port":"{}","websocket":"Available on same URL with WebSocket protocol"}}"#,
                port
            );

            Ok(Response::builder()
                .status(StatusCode::OK)
                .header("Content-Type", "application/json")
                .body(Body::from(response_body))
                .unwrap())
        }

        // 404 for all other paths
        _ => Ok(Response::builder()
            .status(StatusCode::NOT_FOUND)
            .header("Content-Type", "application/json")
            .body(Body::from(r#"{"error":"Not Found"}"#))
            .unwrap()),
    }
}

/// Build MongoDB connection string using the same priority logic as PHP db.php
fn build_mongodb_connection_string(db_name: &str) -> String {
    // First priority: Check for MONGODB_URI (for Atlas/cloud)
    if let Ok(mongodb_uri) = env::var("MONGODB_URI") {
        if !mongodb_uri.trim().is_empty() {
            log::info!("Using MONGODB_URI for cloud/Atlas connection");
            return mongodb_uri;
        }
    }

    // Second priority: Build from individual components (for local Docker)
    log::info!("Building connection string from individual components");

    let db_host = env::var("DB_HOST").unwrap_or_else(|_| "mongo".to_string());
    let db_port = env::var("DB_PORT").unwrap_or_else(|_| "27017".to_string());
    let db_user = env::var("DB_USER").unwrap_or_default();
    let db_pass = env::var("DB_PASS").unwrap_or_default();

    let mut uri = "mongodb://".to_string();

    // Add authentication if provided
    if !db_user.trim().is_empty() && !db_pass.trim().is_empty() {
        uri.push_str(&format!(
            "{}:{}@",
            urlencoding::encode(&db_user),
            urlencoding::encode(&db_pass)
        ));
    }

    // Add host, port, and database
    uri.push_str(&format!("{}:{}/{}", db_host, db_port, db_name));

    // Add auth source if we have credentials
    if !db_user.trim().is_empty() && !db_pass.trim().is_empty() {
        uri.push_str("?authSource=admin");
    }

    uri
}

/// Remove sensitive information from connection string for logging
fn mask_password(connection_string: &str) -> String {
    if let Some(at_pos) = connection_string.find('@') {
        if let Some(colon_pos) = connection_string[..at_pos].rfind(':') {
            if let Some(scheme_end) = connection_string.find("://") {
                let scheme_end = scheme_end + 3;
                if colon_pos > scheme_end {
                    let mut masked = connection_string.to_string();
                    masked.replace_range(colon_pos + 1..at_pos, "****");
                    return masked;
                }
            }
        }
    }
    connection_string.to_string()
}
