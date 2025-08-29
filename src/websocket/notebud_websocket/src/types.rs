use serde::{Deserialize, Serialize};
use tokio::sync::mpsc::UnboundedSender;
use warp::ws::Message;

#[derive(Debug, Clone)]
pub struct ClientInfo {
    pub user_id: Option<String>,
    pub username: Option<String>,
    pub sender: UnboundedSender<Message>,
}

#[derive(Debug, Deserialize)]
#[serde(tag = "type")]
pub enum IncomingMessage {
    #[serde(rename = "auth")]
    Auth { token: String },

    #[serde(rename = "send_chat_request")]
    SendChatRequest { to_username: String },

    #[serde(rename = "accept_chat_request")]
    AcceptChatRequest { from_user_id: String },

    #[serde(rename = "decline_chat_request")]
    DeclineChatRequest { from_user_id: String },

    #[serde(rename = "send_message")]
    SendMessage { chat_id: String, message: String },

    #[serde(rename = "get_chat_requests")]
    GetChatRequests,

    #[serde(rename = "get_active_chats")]
    GetActiveChats,

    #[serde(rename = "get_chat_messages")]
    GetChatMessages { chat_id: String },
}

#[derive(Debug, Serialize, Clone)]
#[serde(tag = "type")]
pub enum OutgoingMessage {
    #[serde(rename = "auth_success")]
    AuthSuccess { user_id: String, username: String },

    #[serde(rename = "error")]
    Error { message: String },

    #[serde(rename = "chat_requests")]
    ChatRequests { requests: Vec<ChatRequest> },

    #[serde(rename = "active_chats")]
    ActiveChats { chats: Vec<ActiveChat> },

    #[serde(rename = "chat_messages")]
    ChatMessages {
        chat_id: String,
        messages: Vec<ChatMessage>,
    },

    #[serde(rename = "new_chat_request")]
    NewChatRequest {
        from_user_id: String,
        from_username: String,
    },

    #[serde(rename = "chat_request_sent")]
    ChatRequestSent { to_username: String },

    #[serde(rename = "chat_accepted")]
    ChatAccepted {
        chat_id: String,
        with_user: String,
        with_user_id: String,
    },

    #[serde(rename = "chat_declined")]
    ChatDeclined { by_user: String },

    #[serde(rename = "new_message")]
    NewMessage {
        chat_id: String,
        from_user_id: String,
        from_username: String,
        message: String,
        timestamp: i64,
    },
}

#[derive(Debug, Serialize, Deserialize, Clone)]
pub struct ChatRequest {
    pub from_user_id: String,
    pub from_username: String,
    pub to_user_id: String,
    pub created_at: String,
}

#[derive(Debug, Serialize, Deserialize, Clone)]
pub struct ActiveChat {
    pub chat_id: String,
    pub with_user: String,
    pub with_user_id: String,
    pub online: bool,
    pub last_message_at: String,
}

#[derive(Debug, Serialize, Deserialize, Clone)]
pub struct ChatMessage {
    pub from_user_id: String,
    pub from_username: String,
    pub message: String,
    pub timestamp: String,
}

#[derive(Debug, Serialize, Deserialize)]
pub struct User {
    pub user_id: String,
    pub username: String,
    pub email: String,
    pub online: bool,
    pub last_seen: Option<String>,
}
