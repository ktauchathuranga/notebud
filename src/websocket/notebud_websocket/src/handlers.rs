use anyhow::Result;
use serde_json;
use std::collections::HashMap;
use tokio::sync::mpsc::UnboundedSender;
use tokio_tungstenite::tungstenite::Message;

use crate::auth::JwtValidator;
use crate::database::DatabaseManager;
use crate::types::{ClientInfo, IncomingMessage, OutgoingMessage};

pub struct MessageHandler {
    db: DatabaseManager,
    jwt_validator: JwtValidator,
    clients: HashMap<usize, ClientInfo>,
    user_to_client: HashMap<String, usize>,
    next_client_id: usize,
}

impl MessageHandler {
    pub fn new(db: DatabaseManager, jwt_validator: JwtValidator) -> Self {
        Self {
            db,
            jwt_validator,
            clients: HashMap::new(),
            user_to_client: HashMap::new(),
            next_client_id: 0,
        }
    }

    pub fn add_client(&mut self, sender: UnboundedSender<Message>) -> usize {
        let client_id = self.next_client_id;
        self.next_client_id += 1;

        let client_info = ClientInfo {
            user_id: None,
            username: None,
            sender,
        };

        self.clients.insert(client_id, client_info);
        client_id
    }

    pub async fn remove_client(&mut self, client_id: usize) {
        if let Some(client) = self.clients.remove(&client_id) {
            if let Some(user_id) = &client.user_id {
                self.user_to_client.remove(user_id);

                if let Err(e) = self.db.update_user_online_status(user_id, false).await {
                    log::error!("Failed to update user offline status: {}", e);
                }
            }
        }
    }

    pub async fn handle_message(
        &mut self,
        client_id: usize,
        message: IncomingMessage,
    ) -> Result<()> {
        match message {
            IncomingMessage::Auth { token } => self.handle_auth(client_id, token).await,
            _ => {
                if !self.is_client_authenticated(client_id) {
                    log::error!("Client not authenticated");
                    self.send_error(client_id, "Client not authenticated")
                        .await?;
                    return Ok(());
                }

                match message {
                    IncomingMessage::SendChatRequest { to_username } => {
                        self.handle_send_chat_request(client_id, to_username).await
                    }
                    IncomingMessage::AcceptChatRequest { from_user_id } => {
                        self.handle_accept_chat_request(client_id, from_user_id)
                            .await
                    }
                    IncomingMessage::DeclineChatRequest { from_user_id } => {
                        self.handle_decline_chat_request(client_id, from_user_id)
                            .await
                    }
                    IncomingMessage::SendMessage {
                        chat_id,
                        message: msg,
                    } => self.handle_send_message(client_id, chat_id, msg).await,
                    IncomingMessage::GetChatRequests => {
                        self.handle_get_chat_requests(client_id).await
                    }
                    IncomingMessage::GetActiveChats => {
                        self.handle_get_active_chats(client_id).await
                    }
                    IncomingMessage::GetChatMessages { chat_id } => {
                        self.handle_get_chat_messages(client_id, chat_id).await
                    }
                    _ => Ok(()),
                }
            }
        }
    }

    async fn handle_auth(&mut self, client_id: usize, token: String) -> Result<()> {
        log::info!("Processing authentication for client {}", client_id);

        match self.jwt_validator.validate_token(&token) {
            Ok(claims) => {
                log::info!("JWT validation successful for user_id: {}", claims.user_id);

                match self.db.find_user_by_id(&claims.user_id).await {
                    Ok(Some(user)) => {
                        log::info!("User found in database: {}", user.username);

                        if let Some(client) = self.clients.get_mut(&client_id) {
                            client.user_id = Some(claims.user_id.clone());
                            client.username = Some(user.username.clone());
                        }

                        self.user_to_client
                            .insert(claims.user_id.clone(), client_id);

                        if let Err(e) = self
                            .db
                            .update_user_online_status(&claims.user_id, true)
                            .await
                        {
                            log::error!("Failed to update user online status: {}", e);
                        }

                        let response = OutgoingMessage::AuthSuccess {
                            user_id: claims.user_id,
                            username: user.username,
                        };

                        self.send_to_client(client_id, response).await?;
                        log::info!("Authentication successful for client {}", client_id);
                        Ok(())
                    }
                    Ok(None) => {
                        log::error!("User not found in database for user_id: {}", claims.user_id);
                        self.send_error(client_id, "User not found").await
                    }
                    Err(e) => {
                        log::error!("Database error during user lookup: {}", e);
                        self.send_error(client_id, "Database error").await
                    }
                }
            }
            Err(e) => {
                log::error!("JWT validation failed: {}", e);
                self.send_error(client_id, "Invalid token").await
            }
        }
    }

    fn is_client_authenticated(&self, client_id: usize) -> bool {
        self.clients
            .get(&client_id)
            .and_then(|client| client.user_id.as_ref())
            .is_some()
    }

    async fn send_error(&mut self, client_id: usize, message: &str) -> Result<()> {
        let error_msg = OutgoingMessage::Error {
            message: message.to_string(),
        };
        self.send_to_client(client_id, error_msg).await
    }

    pub async fn send_to_client(
        &mut self,
        client_id: usize,
        message: OutgoingMessage,
    ) -> Result<()> {
        if let Some(client) = self.clients.get(&client_id) {
            let json = serde_json::to_string(&message)?;
            let ws_message = Message::Text(json);

            if client.sender.send(ws_message).is_err() {
                log::error!("Failed to send message to client {}", client_id);
            }
        }
        Ok(())
    }

    async fn handle_send_chat_request(
        &mut self,
        client_id: usize,
        to_username: String,
    ) -> Result<()> {
        let from_user_id = self.get_client_user_id(client_id)?;

        match self.db.find_user_by_username(&to_username).await {
            Ok(Some(to_user)) => {
                if to_user.user_id == from_user_id {
                    return self
                        .send_error(client_id, "Cannot send request to yourself")
                        .await;
                }

                match self
                    .db
                    .create_chat_request(&from_user_id, &to_user.user_id)
                    .await
                {
                    Ok(()) => {
                        let from_username = self.get_client_username(client_id)?;

                        // Send confirmation to sender
                        let response = OutgoingMessage::ChatRequestSent {
                            to_username: to_username.clone(),
                        };
                        self.send_to_client(client_id, response).await?;

                        // Notify recipient if online
                        if let Some(recipient_client_id) = self.user_to_client.get(&to_user.user_id)
                        {
                            let notification = OutgoingMessage::NewChatRequest {
                                from_user_id: from_user_id,
                                from_username: from_username,
                            };
                            self.send_to_client(*recipient_client_id, notification)
                                .await?;
                        }

                        Ok(())
                    }
                    Err(e) => self.send_error(client_id, &e.to_string()).await,
                }
            }
            Ok(None) => self.send_error(client_id, "User not found").await,
            Err(e) => {
                log::error!("Database error finding user: {}", e);
                self.send_error(client_id, "Database error").await
            }
        }
    }

    async fn handle_accept_chat_request(
        &mut self,
        client_id: usize,
        from_user_id: String,
    ) -> Result<()> {
        let to_user_id = self.get_client_user_id(client_id)?;

        match self
            .db
            .accept_chat_request(&from_user_id, &to_user_id)
            .await
        {
            Ok(chat_id) => {
                let to_username = self.get_client_username(client_id)?;

                // Get sender info
                if let Ok(Some(from_user)) = self.db.find_user_by_id(&from_user_id).await {
                    // Notify sender if online
                    if let Some(sender_client_id) = self.user_to_client.get(&from_user_id) {
                        let notification = OutgoingMessage::ChatAccepted {
                            chat_id: chat_id.clone(),
                            with_user: to_username.clone(),
                            with_user_id: to_user_id.clone(),
                        };
                        self.send_to_client(*sender_client_id, notification).await?;
                    }

                    // Notify recipient (current user)
                    let notification = OutgoingMessage::ChatAccepted {
                        chat_id,
                        with_user: from_user.username,
                        with_user_id: from_user_id,
                    };
                    self.send_to_client(client_id, notification).await?;
                }

                Ok(())
            }
            Err(e) => {
                log::error!("Error accepting chat request: {}", e);
                self.send_error(client_id, "Failed to accept chat request")
                    .await
            }
        }
    }

    async fn handle_decline_chat_request(
        &mut self,
        client_id: usize,
        from_user_id: String,
    ) -> Result<()> {
        let to_user_id = self.get_client_user_id(client_id)?;

        match self
            .db
            .decline_chat_request(&from_user_id, &to_user_id)
            .await
        {
            Ok(()) => {
                let to_username = self.get_client_username(client_id)?;

                // Notify sender if online
                if let Some(sender_client_id) = self.user_to_client.get(&from_user_id) {
                    let notification = OutgoingMessage::ChatDeclined {
                        by_user: to_username,
                    };
                    self.send_to_client(*sender_client_id, notification).await?;
                }

                Ok(())
            }
            Err(e) => {
                log::error!("Error declining chat request: {}", e);
                self.send_error(client_id, "Failed to decline chat request")
                    .await
            }
        }
    }

    async fn handle_send_message(
        &mut self,
        client_id: usize,
        chat_id: String,
        message: String,
    ) -> Result<()> {
        let from_user_id = self.get_client_user_id(client_id)?;
        let from_username = self.get_client_username(client_id)?;

        if message.trim().is_empty() {
            return self.send_error(client_id, "Message cannot be empty").await;
        }

        // Verify user is participant and get other participants
        match self.db.get_chat_participants(&chat_id).await {
            Ok(participants) => {
                if !participants.contains(&from_user_id) {
                    return self
                        .send_error(client_id, "Not authorized to send messages to this chat")
                        .await;
                }

                // Save message
                match self
                    .db
                    .save_message(&chat_id, &from_user_id, &message)
                    .await
                {
                    Ok(timestamp) => {
                        let new_message = OutgoingMessage::NewMessage {
                            chat_id: chat_id.clone(),
                            from_user_id: from_user_id.clone(),
                            from_username: from_username,
                            message,
                            timestamp,
                        };

                        // Send to all participants who are online
                        for participant_id in participants {
                            if let Some(participant_client_id) =
                                self.user_to_client.get(&participant_id)
                            {
                                self.send_to_client(*participant_client_id, new_message.clone())
                                    .await?;
                            }
                        }

                        Ok(())
                    }
                    Err(e) => {
                        log::error!("Error saving message: {}", e);
                        self.send_error(client_id, "Failed to send message").await
                    }
                }
            }
            Err(e) => {
                log::error!("Error getting chat participants: {}", e);
                self.send_error(client_id, "Chat not found").await
            }
        }
    }

    async fn handle_get_chat_requests(&mut self, client_id: usize) -> Result<()> {
        let user_id = self.get_client_user_id(client_id)?;

        match self.db.get_chat_requests_for_user(&user_id).await {
            Ok(requests) => {
                let response = OutgoingMessage::ChatRequests { requests };
                self.send_to_client(client_id, response).await
            }
            Err(e) => {
                log::error!("Error getting chat requests: {}", e);
                self.send_error(client_id, "Failed to get chat requests")
                    .await
            }
        }
    }

    async fn handle_get_active_chats(&mut self, client_id: usize) -> Result<()> {
        let user_id = self.get_client_user_id(client_id)?;

        match self.db.get_active_chats_for_user(&user_id).await {
            Ok(chats) => {
                let response = OutgoingMessage::ActiveChats { chats };
                self.send_to_client(client_id, response).await
            }
            Err(e) => {
                log::error!("Error getting active chats: {}", e);
                self.send_error(client_id, "Failed to get active chats")
                    .await
            }
        }
    }

    async fn handle_get_chat_messages(&mut self, client_id: usize, chat_id: String) -> Result<()> {
        let user_id = self.get_client_user_id(client_id)?;

        match self.db.get_chat_messages(&chat_id, &user_id).await {
            Ok(messages) => {
                let response = OutgoingMessage::ChatMessages { chat_id, messages };
                self.send_to_client(client_id, response).await
            }
            Err(e) => {
                log::error!("Error getting chat messages: {}", e);
                self.send_error(client_id, "Failed to get chat messages")
                    .await
            }
        }
    }

    fn get_client_user_id(&self, client_id: usize) -> Result<String> {
        self.clients
            .get(&client_id)
            .and_then(|client| client.user_id.as_ref())
            .map(|id| id.clone())
            .ok_or_else(|| anyhow::anyhow!("Client not authenticated"))
    }

    fn get_client_username(&self, client_id: usize) -> Result<String> {
        self.clients
            .get(&client_id)
            .and_then(|client| client.username.as_ref())
            .map(|username| username.clone())
            .ok_or_else(|| anyhow::anyhow!("Client not authenticated"))
    }
}

