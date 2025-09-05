export const CHAT_CONFIG = {
  MAX_RECONNECT_ATTEMPTS: 5,
  RECONNECT_DELAY: 3000,
  MESSAGE_BUFFER_SIZE: 100,
  NOTIFICATION_DURATION: 5000
};

export const WEBSOCKET_TYPES = {
  AUTH: 'auth',
  AUTH_SUCCESS: 'auth_success',
  NEW_CHAT_REQUEST: 'new_chat_request',
  CHAT_REQUEST_SENT: 'chat_request_sent',
  CHAT_ACCEPTED: 'chat_accepted',
  CHAT_DECLINED: 'chat_declined',
  REQUEST_STATUS_CHANGED: 'request_status_changed',
  NEW_MESSAGE: 'new_message',
  CHAT_REQUESTS: 'chat_requests',
  ACTIVE_CHATS: 'active_chats',
  CHAT_MESSAGES: 'chat_messages',
  SEND_CHAT_REQUEST: 'send_chat_request',
  ACCEPT_CHAT_REQUEST: 'accept_chat_request',
  DECLINE_CHAT_REQUEST: 'decline_chat_request',
  SEND_MESSAGE: 'send_message',
  GET_CHAT_REQUESTS: 'get_chat_requests',
  GET_ACTIVE_CHATS: 'get_active_chats',
  GET_CHAT_MESSAGES: 'get_chat_messages',
  ERROR: 'error'
};

export const CONNECTION_STATUS = {
  CONNECTING: 'connecting',
  CONNECTED: 'connected',
  DISCONNECTED: 'disconnected'
};

// Add notification styles immediately
const notificationStyles = `
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--card, #fff);
    color: var(--text, #333);
    padding: 1rem 1.5rem;
    border-radius: 8px;
    z-index: 1000;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    border-left: 4px solid #3b82f6;
    min-width: 300px;
    max-width: 500px;
    font-weight: 500;
    word-wrap: break-word;
}

.notification.success {
    border-left-color: #10b981;
    background: #f0fdf4;
    color: #166534;
}

.notification.error {
    border-left-color: #ef4444;
    background: #fef2f2;
    color: #dc2626;
}

@media (max-width: 767px) {
    .notification {
        top: 10px;
        right: 10px;
        left: 10px;
        min-width: auto;
        max-width: none;
    }
}
`;

// Add styles immediately when module loads
if (!document.querySelector('#chat-notification-styles')) {
    const styleElement = document.createElement('style');
    styleElement.id = 'chat-notification-styles';
    styleElement.textContent = notificationStyles;
    document.head.appendChild(styleElement);
}
