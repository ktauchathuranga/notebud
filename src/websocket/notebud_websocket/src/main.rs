mod auth;
mod database;
mod handlers;
mod types;

use std::env;
use std::net::SocketAddr;
use std::sync::Arc;

use futures_util::{SinkExt, StreamExt};
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

    // Get configuration from environment
    let bind_address =
        env::var("WEBSOCKET_BIND_ADDRESS").unwrap_or_else(|_| "0.0.0.0:8080".to_string());

    let db_name = env::var("DB_NAME").unwrap_or_else(|_| "notebud".to_string());

    let jwt_secret = env::var("JWT_SECRET").expect("JWT_SECRET environment variable is required");

    // Build MongoDB connection string
    let connection_string = build_connection_string_from_parts(&db_name);

    log::info!("Starting notebud WebSocket server on {}", bind_address);
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

    // Start server
    let listener = TcpListener::bind(&bind_address).await?;
    log::info!("WebSocket server listening on {}", bind_address);

    while let Ok((stream, addr)) = listener.accept().await {
        let handler = Arc::clone(&message_handler);
        tokio::spawn(handle_connection(stream, addr, handler));
    }

    Ok(())
}

fn build_connection_string_from_parts(db_name: &str) -> String {
    let db_host = env::var("DB_HOST").unwrap_or_else(|_| "mongo".to_string());
    let db_port = env::var("DB_PORT").unwrap_or_else(|_| "27017".to_string());

    // Try DB_USER/DB_PASS first
    let db_user = env::var("DB_USER").unwrap_or_default();
    let db_pass = env::var("DB_PASS").unwrap_or_default();

    if !db_user.trim().is_empty() && !db_pass.trim().is_empty() {
        return format!(
            "mongodb://{}:{}@{}:{}/{}?authSource=admin",
            db_user, db_pass, db_host, db_port, db_name
        );
    }

    // Fallback to root credentials
    let root_user = env::var("MONGO_INITDB_ROOT_USERNAME").unwrap_or_default();
    let root_pass = env::var("MONGO_INITDB_ROOT_PASSWORD").unwrap_or_default();

    if !root_user.trim().is_empty() && !root_pass.trim().is_empty() {
        format!(
            "mongodb://{}:{}@{}:{}/{}?authSource=admin",
            root_user, root_pass, db_host, db_port, db_name
        )
    } else {
        // No authentication
        format!("mongodb://{}:{}/{}", db_host, db_port, db_name)
    }
}

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

