mod auth;
mod database;
mod handlers;
mod types;

use std::convert::Infallible;
use std::env;
use std::net::SocketAddr;
use std::sync::Arc;

use chrono::{DateTime, Utc};
use futures_util::{SinkExt, StreamExt};
use hyper::service::{make_service_fn, service_fn};
use hyper::upgrade::Upgraded;
use hyper::{Body, Method, Request, Response, Server, StatusCode, header};
use serde_json::json;
use tokio::sync::Mutex;
use tokio_tungstenite::{accept_async, tungstenite::Message};

use crate::auth::JwtValidator;
use crate::database::DatabaseManager;
use crate::handlers::MessageHandler;
use crate::types::IncomingMessage;

// Track server start time for uptime calculation
lazy_static::lazy_static! {
    static ref SERVER_START_TIME: DateTime<Utc> = Utc::now();
}

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    // Initialize logging
    env_logger::init();

    // Load environment variables
    dotenv::dotenv().ok();

    // Get port from Render's PORT environment variable, fallback to 10000 (Render's default)
    let port = env::var("PORT").unwrap_or_else(|_| "10000".to_string());
    let bind_port = port.parse::<u16>().unwrap_or(10000);
    let bind_address = format!("0.0.0.0:{}", bind_port);

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

    // Create service
    let handler_for_service = Arc::clone(&message_handler);
    let make_svc = make_service_fn(move |_conn| {
        let handler = Arc::clone(&handler_for_service);
        async move {
            Ok::<_, Infallible>(service_fn(move |req| {
                let handler = Arc::clone(&handler);
                handle_request(req, handler)
            }))
        }
    });

    // Start the combined server
    let addr: SocketAddr = bind_address.parse()?;
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
    match (req.method(), req.uri().path()) {
        // WebSocket upgrade endpoint
        (&Method::GET, "/ws") | (&Method::GET, "/websocket") => {
            if hyper_tungstenite::is_upgrade_request(&req) {
                match hyper_tungstenite::upgrade(&mut req, None) {
                    Ok((response, websocket)) => {
                        tokio::spawn(handle_websocket(websocket, message_handler));
                        Ok(response)
                    }
                    Err(e) => {
                        log::error!("WebSocket upgrade failed: {}", e);
                        Ok(Response::builder()
                            .status(StatusCode::BAD_REQUEST)
                            .body(Body::from("WebSocket upgrade failed"))
                            .unwrap())
                    }
                }
            } else {
                Ok(Response::builder()
                    .status(StatusCode::BAD_REQUEST)
                    .body(Body::from("This endpoint requires WebSocket upgrade"))
                    .unwrap())
            }
        }

        // Health check endpoint
        (&Method::GET, "/health") => {
            log::debug!("Health check requested");

            // Get query parameter for detailed health check
            let detailed = req
                .uri()
                .query()
                .and_then(|q| {
                    q.split('&')
                        .find(|param| param.starts_with("detailed="))
                        .and_then(|param| param.split('=').nth(1))
                        .map(|v| v == "true")
                })
                .unwrap_or(false);

            if detailed {
                match detailed_health_check(Arc::clone(&message_handler)).await {
                    Ok(health_response) => {
                        let status_code = if health_response["status"] == "ok" {
                            StatusCode::OK
                        } else {
                            StatusCode::SERVICE_UNAVAILABLE
                        };

                        Ok(Response::builder()
                            .status(status_code)
                            .header("Content-Type", "application/json")
                            .body(Body::from(health_response.to_string()))
                            .unwrap())
                    }
                    Err(e) => {
                        log::error!("Health check failed: {}", e);
                        let error_response = json!({
                            "status": "error",
                            "service": "notebud-websocket",
                            "timestamp": Utc::now().to_rfc3339(),
                            "error": e.to_string()
                        });

                        Ok(Response::builder()
                            .status(StatusCode::SERVICE_UNAVAILABLE)
                            .header("Content-Type", "application/json")
                            .body(Body::from(error_response.to_string()))
                            .unwrap())
                    }
                }
            } else {
                // Basic health check
                let now = Utc::now();
                let uptime = now.signed_duration_since(*SERVER_START_TIME);

                let response = json!({
                    "status": "ok",
                    "service": "notebud-websocket",
                    "timestamp": now.to_rfc3339(),
                    "uptime_seconds": uptime.num_seconds(),
                    "version": env!("CARGO_PKG_VERSION")
                });

                Ok(Response::builder()
                    .status(StatusCode::OK)
                    .header("Content-Type", "application/json")
                    .body(Body::from(response.to_string()))
                    .unwrap())
            }
        }

        // Root endpoint with service info
        (&Method::GET, "/") => {
            let response_body = json!({
                "status": "ok",
                "service": "notebud-websocket",
                "version": env!("CARGO_PKG_VERSION"),
                "timestamp": Utc::now().to_rfc3339(),
                "endpoints": {
                    "/health": "Health check (add ?detailed=true for comprehensive check)",
                    "/ws": "WebSocket endpoint",
                    "/": "Service info"
                }
            });

            Ok(Response::builder()
                .status(StatusCode::OK)
                .header("Content-Type", "application/json")
                .body(Body::from(response_body.to_string()))
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

async fn detailed_health_check(
    message_handler: Arc<Mutex<MessageHandler>>,
) -> Result<serde_json::Value, Box<dyn std::error::Error + Send + Sync>> {
    let now = Utc::now();
    let uptime = now.signed_duration_since(*SERVER_START_TIME);
    let mut status = "ok";
    let mut checks = json!({});

    // Check database connectivity
    {
        let handler = message_handler.lock().await;
        match handler.health_check_database().await {
            Ok(_) => {
                checks["database"] = json!({
                    "status": "ok",
                    "message": "Connected and responsive"
                });
            }
            Err(e) => {
                status = "degraded";
                checks["database"] = json!({
                    "status": "error",
                    "message": format!("Database error: {}", e)
                });
            }
        }
    }

    // Check active connections
    {
        let handler = message_handler.lock().await;
        let active_connections = handler.get_active_connection_count();
        let authenticated_connections = handler.get_authenticated_connection_count();

        checks["websocket_connections"] = json!({
            "status": "ok",
            "total_connections": active_connections,
            "authenticated_connections": authenticated_connections
        });
    }

    // Memory usage (basic check)
    checks["system"] = json!({
        "status": "ok",
        "uptime_seconds": uptime.num_seconds(),
        "uptime_human": format_duration(uptime.num_seconds())
    });

    Ok(json!({
        "status": status,
        "service": "notebud-websocket",
        "timestamp": now.to_rfc3339(),
        "version": env!("CARGO_PKG_VERSION"),
        "uptime_seconds": uptime.num_seconds(),
        "checks": checks
    }))
}

fn format_duration(seconds: i64) -> String {
    let days = seconds / 86400;
    let hours = (seconds % 86400) / 3600;
    let mins = (seconds % 3600) / 60;
    let secs = seconds % 60;

    if days > 0 {
        format!("{}d {}h {}m {}s", days, hours, mins, secs)
    } else if hours > 0 {
        format!("{}h {}m {}s", hours, mins, secs)
    } else if mins > 0 {
        format!("{}m {}s", mins, secs)
    } else {
        format!("{}s", secs)
    }
}

async fn handle_websocket(
    websocket: hyper_tungstenite::HyperWebsocket,
    message_handler: Arc<Mutex<MessageHandler>>,
) {
    let ws_stream = match websocket.await {
        Ok(ws) => ws,
        Err(e) => {
            log::error!("WebSocket connection failed: {}", e);
            return;
        }
    };

    log::info!("New WebSocket connection established");

    let (mut ws_sender, mut ws_receiver) = ws_stream.split();
    let (tx, mut rx) = tokio::sync::mpsc::unbounded_channel::<Message>();

    // Register client
    let client_id = {
        let mut handler = message_handler.lock().await;
        handler.add_client(tx)
    };

    log::info!("Client {} registered", client_id);

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
