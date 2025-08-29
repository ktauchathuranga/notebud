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
use tokio::net::{TcpListener, TcpStream};
use tokio::sync::Mutex;
use tokio_tungstenite::{accept_async, tungstenite::Message};

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
    let ws_port = http_port + 1; // Use next port for WebSocket

    let http_bind_address = format!("0.0.0.0:{}", http_port);
    let ws_bind_address = format!("0.0.0.0:{}", ws_port);

    let db_name = env::var("DB_NAME").unwrap_or_else(|_| "notebud".to_string());
    let jwt_secret = env::var("JWT_SECRET").expect("JWT_SECRET environment variable is required");

    // Build MongoDB connection string using the same logic as PHP
    let connection_string = build_mongodb_connection_string(&db_name);

    log::info!("Starting notebud server...");
    log::info!("HTTP server will listen on {}", http_bind_address);
    log::info!("WebSocket server will listen on {}", ws_bind_address);
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

    // Start HTTP server for health checks
    let http_addr: SocketAddr = http_bind_address.parse()?;
    let make_svc = make_service_fn(move |_conn| async move {
        Ok::<_, Infallible>(service_fn(handle_http_request))
    });

    let http_server = Server::bind(&http_addr).serve(make_svc);
    log::info!("HTTP server listening on {}", http_bind_address);

    // Start WebSocket server
    let ws_handler = Arc::clone(&message_handler);
    let ws_server = async move {
        let listener = TcpListener::bind(&ws_bind_address).await?;
        log::info!("WebSocket server listening on {}", ws_bind_address);

        while let Ok((stream, addr)) = listener.accept().await {
            let handler = Arc::clone(&ws_handler);
            tokio::spawn(handle_connection(stream, addr, handler));
        }

        Ok::<(), Box<dyn std::error::Error + Send + Sync>>(())
    };

    // Run both servers concurrently
    tokio::select! {
        result = http_server => {
            if let Err(e) = result {
                log::error!("HTTP server error: {}", e);
            }
        }
        result = ws_server => {
            if let Err(e) = result {
                log::error!("WebSocket server error: {}", e);
            }
        }
    }

    Ok(())
}

async fn handle_http_request(req: Request<Body>) -> Result<Response<Body>, Infallible> {
    match (req.method(), req.uri().path()) {
        // Health check endpoint
        (&Method::GET, "/health") => {
            log::debug!("Health check requested");
            Ok(Response::builder()
                .status(StatusCode::OK)
                .header("Content-Type", "application/json")
                .body(Body::from(r#"{"status":"ok","service":"notebud-websocket","timestamp":"2025-08-29T15:36:20Z"}"#))
                .unwrap())
        }

        // Root endpoint with service info
        (&Method::GET, "/") => {
            let ws_port = env::var("PORT")
                .unwrap_or_else(|_| "8080".to_string())
                .parse::<u16>()
                .unwrap_or(8080)
                + 1;

            let response_body = format!(
                r#"{{"status":"ok","service":"notebud-websocket","websocket_port":{},"endpoints":{{"/health":"Health check","/":"Service info"}}}}"#,
                ws_port
            );

            Ok(Response::builder()
                .status(StatusCode::OK)
                .header("Content-Type", "application/json")
                .body(Body::from(response_body))
                .unwrap())
        }

        // 404 for all other paths
        _ => {
            log::debug!("404 - Path not found: {}", req.uri().path());
            Ok(Response::builder()
                .status(StatusCode::NOT_FOUND)
                .header("Content-Type", "application/json")
                .body(Body::from(
                    r#"{"error":"Not Found","message":"Endpoint not found"}"#,
                ))
                .unwrap())
        }
    }
}

async fn handle_connection(
    stream: TcpStream,
    addr: SocketAddr,
    message_handler: Arc<Mutex<MessageHandler>>,
) {
    log::info!("New connection from: {}", addr);

    let ws_stream = match accept_async(stream).await {
        Ok(ws) => ws,
        Err(e) => {
            log::error!("WebSocket handshake failed for {}: {}", addr, e);
            return;
        }
    };

    let (mut ws_sender, mut ws_receiver) = ws_stream.split();
    let (tx, mut rx) = tokio::sync::mpsc::unbounded_channel::<Message>();

    // Register client
    let client_id = {
        let mut handler = message_handler.lock().await;
        handler.add_client(tx)
    };

    log::info!("Client {} registered from {}", client_id, addr);

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
                log::info!("Client {} disconnected", client_id);
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
    log::info!("Client {} cleaned up", client_id);
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
