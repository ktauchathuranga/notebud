mod auth;
mod database;
mod handlers;
mod types;

use std::collections::HashMap;
use std::env;
use std::sync::Arc;

use futures_util::{SinkExt, StreamExt};
use serde_json;
use tokio::sync::{Mutex, mpsc};
use warp::Filter;
use warp::ws::{Message, WebSocket};

use crate::auth::JwtValidator;
use crate::database::DatabaseManager;
use crate::handlers::MessageHandler;
use crate::types::IncomingMessage;

type Clients = Arc<Mutex<HashMap<usize, mpsc::UnboundedSender<Message>>>>;

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    // Initialize logging
    env_logger::init();

    // Load environment variables
    dotenv::dotenv().ok();

    // Get port from Render's PORT environment variable, fallback to 8080
    let port = env::var("PORT")
        .unwrap_or_else(|_| "8080".to_string())
        .parse::<u16>()
        .unwrap_or(8080);

    let db_name = env::var("DB_NAME").unwrap_or_else(|_| "notebud".to_string());
    let jwt_secret = env::var("JWT_SECRET").expect("JWT_SECRET environment variable is required");

    // Build MongoDB connection string
    let connection_string = build_mongodb_connection_string(&db_name);

    log::info!("Starting notebud WebSocket server...");
    log::info!("Server will listen on 0.0.0.0:{}", port);
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

    // CORS headers for WebSocket
    let cors = warp::cors()
        .allow_any_origin()
        .allow_headers(vec!["content-type", "authorization", "x-requested-with"])
        .allow_methods(vec!["GET", "POST", "OPTIONS"]);

    // Health check endpoint
    let health = warp::path("health").and(warp::get()).map(|| {
        warp::reply::json(&serde_json::json!({
            "status": "ok",
            "service": "notebud-websocket"
        }))
    });

    // Root endpoint
    let root = warp::path::end().and(warp::get()).map(move || {
        let port_str = port.to_string();
        warp::reply::json(&serde_json::json!({
            "status": "ok",
            "service": "notebud-websocket",
            "port": port_str,
            "websocket": "Available on same URL with WebSocket protocol"
        }))
    });

    // WebSocket route
    let message_handler_filter = warp::any().map(move || Arc::clone(&message_handler));

    let websocket = warp::path::end()
        .and(warp::ws())
        .and(message_handler_filter)
        .map(|ws: warp::ws::Ws, message_handler| {
            ws.on_upgrade(move |socket| handle_websocket(socket, message_handler))
        });

    let routes = health
        .or(root)
        .or(websocket)
        .with(cors)
        .with(warp::log("notebud_websocket"));

    log::info!("WebSocket server listening on 0.0.0.0:{}", port);

    warp::serve(routes).run(([0, 0, 0, 0], port)).await;

    Ok(())
}

async fn handle_websocket(ws: WebSocket, message_handler: Arc<Mutex<MessageHandler>>) {
    log::info!("New WebSocket connection established");

    let (mut ws_tx, mut ws_rx) = ws.split();
    let (tx, mut rx) = mpsc::unbounded_channel();

    // Register client
    let client_id = {
        let mut handler = message_handler.lock().await;
        handler.add_client(tx)
    };

    log::info!("WebSocket client {} registered", client_id);

    // Spawn task to forward messages to WebSocket
    let ws_sender_task = tokio::spawn(async move {
        while let Some(message) = rx.recv().await {
            if ws_tx.send(message).await.is_err() {
                log::debug!("WebSocket send failed, client likely disconnected");
                break;
            }
        }
    });

    // Handle incoming messages
    while let Some(result) = ws_rx.next().await {
        match result {
            Ok(msg) => {
                if msg.is_text() {
                    if let Ok(text) = msg.to_str() {
                        log::debug!("Received message from {}: {}", client_id, text);

                        // Parse incoming message
                        match serde_json::from_str::<IncomingMessage>(text) {
                            Ok(parsed_msg) => {
                                let mut handler = message_handler.lock().await;
                                if let Err(e) = handler.handle_message(client_id, parsed_msg).await
                                {
                                    log::error!("Error handling message from {}: {}", client_id, e);
                                }
                            }
                            Err(e) => {
                                log::warn!("Failed to parse message from {}: {}", client_id, e);
                            }
                        }
                    }
                } else if msg.is_close() {
                    log::info!("WebSocket client {} disconnected", client_id);
                    break;
                }
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
