import { CHAT_CONFIG, WEBSOCKET_TYPES, CONNECTION_STATUS } from './config.js';
import { showNotification } from './notifications.js';
import { getWebSocketUrl } from './utils.js';

let socket = null;
let isConnected = false;
let reconnectAttempts = 0;

export function getSocket() {
    return socket;
}

export function getIsConnected() {
    return isConnected;
}

export function initializeWebSocket(messageHandler) {
    const token = window.JWT_TOKEN;
    console.log('Using JWT token from PHP for WebSocket auth');

    if (!token) {
        console.error('No JWT token available for WebSocket authentication');
        showNotification('Authentication error. Please refresh the page.', 'error');
        return;
    }

    updateConnectionStatus(CONNECTION_STATUS.CONNECTING);
    const wsUrl = getWebSocketUrl();
    console.log('Attempting to connect to WebSocket server at ' + wsUrl);

    try {
        socket = new WebSocket(wsUrl);

        socket.onopen = function() {
            console.log('WebSocket connected successfully');
            reconnectAttempts = 0;
            updateConnectionStatus(CONNECTION_STATUS.CONNECTED);

            socket.send(JSON.stringify({
                type: WEBSOCKET_TYPES.AUTH,
                token: token
            }));
        };

        socket.onmessage = function(event) {
            console.log('WebSocket message received:', event.data);
            try {
                const data = JSON.parse(event.data);
                messageHandler(data);
            } catch (e) {
                console.error('Error parsing WebSocket message:', e, 'Raw data:', event.data);
            }
        };

        socket.onclose = function(event) {
            console.log('WebSocket disconnected. Code:', event.code, 'Reason:', event.reason);
            isConnected = false;
            updateConnectionStatus(CONNECTION_STATUS.DISCONNECTED);

            if (reconnectAttempts < CHAT_CONFIG.MAX_RECONNECT_ATTEMPTS) {
                reconnectAttempts++;
                console.log(`Reconnection attempt ${reconnectAttempts}/${CHAT_CONFIG.MAX_RECONNECT_ATTEMPTS} in 3 seconds...`);
                setTimeout(() => initializeWebSocket(messageHandler), CHAT_CONFIG.RECONNECT_DELAY);
            } else {
                console.error('Max reconnection attempts reached');
                showNotification('Unable to connect to chat server. Please refresh the page.', 'error');
            }
        };

        socket.onerror = function(error) {
            console.error('WebSocket error:', error);
            updateConnectionStatus(CONNECTION_STATUS.DISCONNECTED);
            showNotification('Connection error. Check if the chat server is running.', 'error');
        };
    } catch (error) {
        console.error('Failed to create WebSocket connection:', error);
        updateConnectionStatus(CONNECTION_STATUS.DISCONNECTED);
        showNotification('Failed to connect to chat server', 'error');
    }
}

export function updateConnectionStatus(status) {
    console.log('Connection status update:', status);
    const statusElement = document.getElementById('connectionStatus');
    const sendRequestBtn = document.getElementById('sendRequestBtn');
    const messageInput = document.getElementById('messageInput');
    const sendMessageBtn = document.getElementById('sendMessageBtn');
    
    if (!statusElement) return;
    
    statusElement.className = `connection-status ${status}`;
    
    switch (status) {
        case CONNECTION_STATUS.CONNECTED:
            statusElement.innerHTML = 'Connected to chat server';
            isConnected = true;
            if (sendRequestBtn) sendRequestBtn.disabled = false;
            if (messageInput) messageInput.disabled = false;
            if (sendMessageBtn) sendMessageBtn.disabled = false;
            break;
        case CONNECTION_STATUS.CONNECTING:
            statusElement.innerHTML = 'Connecting to chat server...';
            isConnected = false;
            if (sendRequestBtn) sendRequestBtn.disabled = true;
            if (messageInput) messageInput.disabled = true;
            if (sendMessageBtn) sendMessageBtn.disabled = true;
            break;
        case CONNECTION_STATUS.DISCONNECTED:
            if (reconnectAttempts < CHAT_CONFIG.MAX_RECONNECT_ATTEMPTS) {
                statusElement.innerHTML = `Reconnecting... (${reconnectAttempts}/${CHAT_CONFIG.MAX_RECONNECT_ATTEMPTS})`;
            } else {
                statusElement.innerHTML = 'Disconnected from chat server';
            }
            isConnected = false;
            if (sendRequestBtn) sendRequestBtn.disabled = true;
            if (messageInput) messageInput.disabled = true;
            if (sendMessageBtn) sendMessageBtn.disabled = true;
            break;
    }
}

export function sendWebSocketMessage(message) {
    if (socket && socket.readyState === WebSocket.OPEN) {
        socket.send(JSON.stringify(message));
        return true;
    }
    return false;
}

export function requestChatRequests() {
    sendWebSocketMessage({ type: WEBSOCKET_TYPES.GET_CHAT_REQUESTS });
}

export function requestActiveChats() {
    sendWebSocketMessage({ type: WEBSOCKET_TYPES.GET_ACTIVE_CHATS });
}
