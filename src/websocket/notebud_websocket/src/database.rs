use anyhow::Result;
use mongodb::{
    Client, Collection, Database,
    bson::{DateTime as BsonDateTime, doc, oid::ObjectId},
};
use serde::{Deserialize, Serialize};
use std::str::FromStr;
use uuid::Uuid;

use crate::types::{ActiveChat, ChatMessage, ChatRequest, User};

#[derive(Debug, Serialize, Deserialize)]
struct DbUser {
    #[serde(rename = "_id")]
    pub id: ObjectId,
    pub username: String,
    #[serde(default)]
    pub email: String,
    #[serde(default)]
    pub online: bool,
    pub last_seen: Option<BsonDateTime>,
    pub created_at: Option<BsonDateTime>,
}

#[derive(Debug, Serialize, Deserialize)]
struct DbChatRequest {
    #[serde(rename = "_id")]
    pub id: ObjectId,
    pub from_user_id: String,
    pub to_user_id: String,
    pub from_username: String, // Store username directly for efficiency
    pub status: String,        // "pending", "accepted", "declined"
    pub created_at: BsonDateTime,
}

#[derive(Debug, Serialize, Deserialize)]
struct DbChat {
    #[serde(rename = "_id")]
    pub id: ObjectId,
    pub chat_id: String,
    pub participants: Vec<String>, // Array of user ObjectId strings
    pub created_at: BsonDateTime,
    pub last_message_at: Option<BsonDateTime>,
}

#[derive(Debug, Serialize, Deserialize)]
struct DbMessage {
    #[serde(rename = "_id")]
    pub id: ObjectId,
    pub chat_id: String,
    pub from_user_id: String,
    pub message: String,
    pub timestamp: BsonDateTime,
}

pub struct DatabaseManager {
    db: Database,
    users: Collection<DbUser>,
    chat_requests: Collection<DbChatRequest>,
    chats: Collection<DbChat>,
    messages: Collection<DbMessage>,
}

impl DatabaseManager {
    pub async fn new(connection_string: &str, db_name: &str) -> Result<Self> {
        let client = Client::with_uri_str(connection_string).await?;
        let db = client.database(db_name);

        let users = db.collection::<DbUser>("users");
        let chat_requests = db.collection::<DbChatRequest>("chat_requests");
        let chats = db.collection::<DbChat>("chats");
        let messages = db.collection::<DbMessage>("messages");

        Ok(Self {
            db,
            users,
            chat_requests,
            chats,
            messages,
        })
    }

    // Health check method
    pub async fn health_check(&self) -> Result<()> {
        // Simple ping to verify database connectivity and responsiveness
        self.db.run_command(doc! { "ping": 1 }, None).await?;
        Ok(())
    }

    // Find user by MongoDB ObjectId (which is what PHP uses as user identifier)
    pub async fn find_user_by_id(&self, user_id: &str) -> Result<Option<User>> {
        // Parse the string as ObjectId
        let object_id = ObjectId::from_str(user_id)
            .map_err(|e| anyhow::anyhow!("Invalid user ID format: {}", e))?;

        let filter = doc! { "_id": object_id };

        if let Some(db_user) = self.users.find_one(filter, None).await? {
            Ok(Some(User {
                user_id: user_id.to_string(), // Keep as string for consistency
                username: db_user.username,
                email: db_user.email,
                online: db_user.online,
                last_seen: db_user
                    .last_seen
                    .map(|dt| dt.try_to_rfc3339_string().unwrap_or_default()),
            }))
        } else {
            Ok(None)
        }
    }

    pub async fn find_user_by_username(&self, username: &str) -> Result<Option<User>> {
        let filter = doc! { "username": username };

        if let Some(db_user) = self.users.find_one(filter, None).await? {
            Ok(Some(User {
                user_id: db_user.id.to_hex(), // Convert ObjectId to hex string
                username: db_user.username,
                email: db_user.email,
                online: db_user.online,
                last_seen: db_user
                    .last_seen
                    .map(|dt| dt.try_to_rfc3339_string().unwrap_or_default()),
            }))
        } else {
            Ok(None)
        }
    }

    pub async fn update_user_online_status(&self, user_id: &str, online: bool) -> Result<()> {
        let object_id = ObjectId::from_str(user_id)
            .map_err(|e| anyhow::anyhow!("Invalid user ID format: {}", e))?;

        let filter = doc! { "_id": object_id };
        let now = BsonDateTime::now();

        let update = doc! {
            "$set": {
                "online": online,
                "last_seen": now
            }
        };

        self.users.update_one(filter, update, None).await?;
        Ok(())
    }

    pub async fn create_chat_request(&self, from_user_id: &str, to_user_id: &str) -> Result<()> {
        // Check if request already exists
        let existing_filter = doc! {
            "$or": [
                {
                    "from_user_id": from_user_id,
                    "to_user_id": to_user_id,
                    "status": "pending"
                },
                {
                    "from_user_id": to_user_id,
                    "to_user_id": from_user_id,
                    "status": "pending"
                }
            ]
        };

        if self
            .chat_requests
            .find_one(existing_filter, None)
            .await?
            .is_some()
        {
            return Err(anyhow::anyhow!("Chat request already exists"));
        }

        // Check if chat already exists
        let chat_filter = doc! {
            "participants": {
                "$all": [from_user_id, to_user_id]
            }
        };

        if self.chats.find_one(chat_filter, None).await?.is_some() {
            return Err(anyhow::anyhow!("Chat already exists"));
        }

        // Get sender username
        let from_user = self
            .find_user_by_id(from_user_id)
            .await?
            .ok_or_else(|| anyhow::anyhow!("Sender user not found"))?;

        let request = DbChatRequest {
            id: ObjectId::new(),
            from_user_id: from_user_id.to_string(),
            to_user_id: to_user_id.to_string(),
            from_username: from_user.username,
            status: "pending".to_string(),
            created_at: BsonDateTime::now(),
        };

        self.chat_requests.insert_one(request, None).await?;
        Ok(())
    }

    pub async fn get_chat_requests_for_user(&self, user_id: &str) -> Result<Vec<ChatRequest>> {
        let filter = doc! {
            "to_user_id": user_id,
            "status": "pending"
        };

        let mut cursor = self.chat_requests.find(filter, None).await?;
        let mut requests = Vec::new();

        while cursor.advance().await? {
            let db_request = cursor.deserialize_current()?;

            requests.push(ChatRequest {
                from_user_id: db_request.from_user_id,
                from_username: db_request.from_username,
                to_user_id: db_request.to_user_id,
                created_at: db_request
                    .created_at
                    .try_to_rfc3339_string()
                    .unwrap_or_default(),
            });
        }

        Ok(requests)
    }

    pub async fn accept_chat_request(
        &self,
        from_user_id: &str,
        to_user_id: &str,
    ) -> Result<String> {
        // Update request status
        let filter = doc! {
            "from_user_id": from_user_id,
            "to_user_id": to_user_id,
            "status": "pending"
        };
        let update = doc! { "$set": { "status": "accepted" } };

        let result = self.chat_requests.update_one(filter, update, None).await?;
        if result.matched_count == 0 {
            return Err(anyhow::anyhow!("Chat request not found"));
        }

        // Create new chat
        let chat_id = Uuid::new_v4().to_string();
        let chat = DbChat {
            id: ObjectId::new(),
            chat_id: chat_id.clone(),
            participants: vec![from_user_id.to_string(), to_user_id.to_string()],
            created_at: BsonDateTime::now(),
            last_message_at: Some(BsonDateTime::now()),
        };

        self.chats.insert_one(chat, None).await?;
        Ok(chat_id)
    }

    pub async fn decline_chat_request(&self, from_user_id: &str, to_user_id: &str) -> Result<()> {
        let filter = doc! {
            "from_user_id": from_user_id,
            "to_user_id": to_user_id,
            "status": "pending"
        };
        let update = doc! { "$set": { "status": "declined" } };

        let result = self.chat_requests.update_one(filter, update, None).await?;
        if result.matched_count == 0 {
            return Err(anyhow::anyhow!("Chat request not found"));
        }

        Ok(())
    }

    pub async fn get_active_chats_for_user(&self, user_id: &str) -> Result<Vec<ActiveChat>> {
        let filter = doc! { "participants": user_id };
        let mut cursor = self.chats.find(filter, None).await?;
        let mut chats = Vec::new();

        while cursor.advance().await? {
            let db_chat = cursor.deserialize_current()?;

            // Get the other participant
            let other_user_id = db_chat
                .participants
                .iter()
                .find(|&id| id != user_id)
                .unwrap_or(&"Unknown".to_string())
                .clone();

            if let Some(other_user) = self.find_user_by_id(&other_user_id).await? {
                chats.push(ActiveChat {
                    chat_id: db_chat.chat_id,
                    with_user: other_user.username,
                    with_user_id: other_user.user_id,
                    online: other_user.online,
                    last_message_at: db_chat
                        .last_message_at
                        .unwrap_or(db_chat.created_at)
                        .try_to_rfc3339_string()
                        .unwrap_or_default(),
                });
            }
        }

        // Sort by last message time
        chats.sort_by(|a, b| b.last_message_at.cmp(&a.last_message_at));
        Ok(chats)
    }

    pub async fn save_message(
        &self,
        chat_id: &str,
        from_user_id: &str,
        message: &str,
    ) -> Result<i64> {
        let timestamp = BsonDateTime::now();

        let db_message = DbMessage {
            id: ObjectId::new(),
            chat_id: chat_id.to_string(),
            from_user_id: from_user_id.to_string(),
            message: message.to_string(),
            timestamp,
        };

        self.messages.insert_one(db_message, None).await?;

        // Update chat last_message_at
        let chat_filter = doc! { "chat_id": chat_id };
        let chat_update = doc! { "$set": { "last_message_at": timestamp } };
        self.chats
            .update_one(chat_filter, chat_update, None)
            .await?;

        Ok(timestamp.timestamp_millis() / 1000)
    }

    pub async fn get_chat_messages(
        &self,
        chat_id: &str,
        user_id: &str,
    ) -> Result<Vec<ChatMessage>> {
        // Verify user is participant in chat
        let chat_filter = doc! {
            "chat_id": chat_id,
            "participants": user_id
        };

        if self.chats.find_one(chat_filter, None).await?.is_none() {
            return Err(anyhow::anyhow!("User not authorized to view this chat"));
        }

        let filter = doc! { "chat_id": chat_id };
        let options = mongodb::options::FindOptions::builder()
            .sort(doc! { "timestamp": 1 })
            .build();

        let mut cursor = self.messages.find(filter, options).await?;
        let mut messages = Vec::new();

        while cursor.advance().await? {
            let db_message = cursor.deserialize_current()?;

            if let Some(sender) = self.find_user_by_id(&db_message.from_user_id).await? {
                messages.push(ChatMessage {
                    from_user_id: db_message.from_user_id,
                    from_username: sender.username,
                    message: db_message.message,
                    timestamp: db_message
                        .timestamp
                        .try_to_rfc3339_string()
                        .unwrap_or_default(),
                });
            }
        }

        Ok(messages)
    }

    pub async fn get_chat_participants(&self, chat_id: &str) -> Result<Vec<String>> {
        let filter = doc! { "chat_id": chat_id };

        if let Some(chat) = self.chats.find_one(filter, None).await? {
            Ok(chat.participants)
        } else {
            Err(anyhow::anyhow!("Chat not found"))
        }
    }
}
