// src/public/js/chat.js
// Enhanced chat functionality with professional UI

let socket = null;
let currentChatId = null;
let currentUserId = null;
let currentUsername = null;
let isConnected = false;
let reconnectAttempts = 0;
let maxReconnectAttempts = 5;
let lastMessageSender = null;

function getWebSocketUrl() {
    // Allow server to override via window.WS_URL
    if (window.WS_URL) return window.WS_URL;

    // Local development: use ws://localhost:8091
    if (
        window.location.hostname === "localhost" ||
        window.location.hostname === "127.0.0.1"
    ) {
        return "ws://localhost:8091";
    }

    // Production (Render): use your Render websocket URL
    return "wss://notebud-websocket.onrender.com";
}

// Initialize chat
document.addEventListener('DOMContentLoaded', () => {
    console.log('=== CHAT PAGE DEBUG START ===');
    
    // Get user info from window variables set by PHP
    currentUserId = window.USER_ID;
    console.log('User ID from PHP:', currentUserId);
    
    if (!currentUserId) {
        console.error('No user ID found - this should not happen if PHP auth worked');
        showNotification('Authentication error. Please refresh the page.', 'error');
        return;
    }
    
    if (!window.JWT_TOKEN) {
        console.error('No JWT token found - this should not happen if PHP auth worked');
        showNotification('Authentication error. Please refresh the page.', 'error');
        return;
    }
    
    console.log('=== STARTING WEBSOCKET INITIALIZATION ===');
    initializeWebSocket();
    setupEventListeners();
});

function initializeWebSocket() {
    const token = window.JWT_TOKEN;
    console.log('Using JWT token from PHP for WebSocket auth');

    if (!token) {
        console.error('No JWT token available for WebSocket authentication');
        showNotification('Authentication error. Please refresh the page.', 'error');
        return;
    }

    updateConnectionStatus('connecting');
    const wsUrl = getWebSocketUrl();
    console.log('Attempting to connect to WebSocket server at ' + wsUrl);

    try {
        socket = new WebSocket(wsUrl);

        socket.onopen = function() {
            console.log('WebSocket connected successfully');
            reconnectAttempts = 0;
            updateConnectionStatus('connected');

            socket.send(JSON.stringify({
                type: 'auth',
                token: token
            }));
        };

        socket.onmessage = function(event) {
            console.log('WebSocket message received:', event.data);
            try {
                const data = JSON.parse(event.data);
                handleWebSocketMessage(data);
            } catch (e) {
                console.error('Error parsing WebSocket message:', e, 'Raw data:', event.data);
            }
        };

        socket.onclose = function(event) {
            console.log('WebSocket disconnected. Code:', event.code, 'Reason:', event.reason);
            isConnected = false;
            updateConnectionStatus('disconnected');

            if (reconnectAttempts < maxReconnectAttempts) {
                reconnectAttempts++;
                console.log(`Reconnection attempt ${reconnectAttempts}/${maxReconnectAttempts} in 3 seconds...`);
                setTimeout(initializeWebSocket, 3000);
            } else {
                console.error('Max reconnection attempts reached');
                showNotification('Unable to connect to chat server. Please refresh the page.', 'error');
            }
        };

        socket.onerror = function(error) {
            console.error('WebSocket error:', error);
            updateConnectionStatus('disconnected');
            showNotification('Connection error. Check if the chat server is running.', 'error');
        };
    } catch (error) {
        console.error('Failed to create WebSocket connection:', error);
        updateConnectionStatus('disconnected');
        showNotification('Failed to connect to chat server', 'error');
    }
}

function updateConnectionStatus(status) {
    console.log('Connection status update:', status);
    const statusElement = document.getElementById('connectionStatus');
    const sendRequestBtn = document.getElementById('sendRequestBtn');
    const messageInput = document.getElementById('messageInput');
    const sendMessageBtn = document.getElementById('sendMessageBtn');
    
    if (!statusElement) return;
    
    statusElement.className = `connection-status ${status}`;
    
    switch (status) {
        case 'connected':
            statusElement.innerHTML = '🟢 Connected to chat server';
            isConnected = true;
            if (sendRequestBtn) sendRequestBtn.disabled = false;
            if (messageInput) messageInput.disabled = false;
            if (sendMessageBtn) sendMessageBtn.disabled = false;
            break;
        case 'connecting':
            statusElement.innerHTML = '🔄 Connecting to chat server...';
            isConnected = false;
            if (sendRequestBtn) sendRequestBtn.disabled = true;
            if (messageInput) messageInput.disabled = true;
            if (sendMessageBtn) sendMessageBtn.disabled = true;
            break;
        case 'disconnected':
            if (reconnectAttempts < maxReconnectAttempts) {
                statusElement.innerHTML = `🔄 Reconnecting... (${reconnectAttempts}/${maxReconnectAttempts})`;
            } else {
                statusElement.innerHTML = '🔴 Disconnected from chat server';
            }
            isConnected = false;
            if (sendRequestBtn) sendRequestBtn.disabled = true;
            if (messageInput) messageInput.disabled = true;
            if (sendMessageBtn) sendMessageBtn.disabled = true;
            break;
    }
}

function handleWebSocketMessage(data) {
    console.log('Handling WebSocket message type:', data.type, 'Data:', data);
    
    switch (data.type) {
        case 'auth_success':
            currentUserId = data.user_id;
            currentUsername = data.username;
            console.log('WebSocket authentication successful. User:', currentUsername, 'ID:', currentUserId);
            
            showNotification(`✅ Connected as ${currentUsername}`, 'success');
            
            socket.send(JSON.stringify({ type: 'get_chat_requests' }));
            socket.send(JSON.stringify({ type: 'get_active_chats' }));
            break;
            
        case 'new_chat_request':
            console.log('New chat request received from:', data.from_username);
            showNotification(`💬 New chat request from ${data.from_username}`);
            loadChatRequests();
            break;
            
        case 'chat_request_sent':
            console.log('Chat request sent to:', data.to_username);
            showNotification(`✅ Chat request sent to ${data.to_username}`);
            const usernameInput = document.getElementById('usernameInput');
            if (usernameInput) usernameInput.value = '';
            break;
            
        case 'chat_accepted':
            console.log('Chat accepted by:', data.with_user);
            showNotification(`🎉 Chat accepted by ${data.with_user}`);
            loadActiveChats();
            break;
            
        case 'chat_declined':
            console.log('Chat declined by:', data.by_user);
            showNotification(`❌ Chat request declined by ${data.by_user}`);
            break;
            
        case 'new_message':
            console.log('New message received for chat:', data.chat_id);
            
            // ALWAYS display the message if it's for the current chat
            if (data.chat_id === currentChatId) {
                console.log('Displaying message in current chat:', data.message);
                displayMessage(data);
            }
            
            // Update active chats list to show latest message time
            loadActiveChats();
            
            // Show notification if not current chat and not from current user
            if (data.chat_id !== currentChatId && data.from_user_id !== currentUserId) {
                showNotification(`💬 New message from ${data.from_username}`);
                
                // Add visual indicator to chat list item
                const chatItem = document.querySelector(`[data-chat-id="${data.chat_id}"]`);
                if (chatItem && !chatItem.classList.contains('active')) {
                    chatItem.style.background = 'linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%)';
                    chatItem.style.borderColor = '#f59e0b';
                }
            }
            break;
            
        case 'chat_requests':
            console.log('Chat requests received:', data.requests.length, 'requests');
            displayChatRequests(data.requests);
            break;
            
        case 'active_chats':
            console.log('Active chats received:', data.chats.length, 'chats');
            displayActiveChats(data.chats);
            break;
            
        case 'chat_messages':
            console.log('Chat messages received for chat:', data.chat_id, 'Messages:', data.messages.length);
            if (data.chat_id === currentChatId) {
                displayChatMessages(data.messages);
            }
            break;
            
        case 'error':
            console.error('WebSocket server error:', data.message);
            showNotification(`❌ ${data.message}`, 'error');
            break;
            
        default:
            console.log('Unknown WebSocket message type:', data.type, 'Full data:', data);
    }
}

function setupEventListeners() {
    console.log('Setting up event listeners...');
    
    const messageInput = document.getElementById('messageInput');
    if (messageInput) {
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.target.disabled) {
                sendMessage();
            }
        });
    }
    
    const usernameInput = document.getElementById('usernameInput');
    if (usernameInput) {
        usernameInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && isConnected) {
                sendChatRequest();
            }
        });
    }
    
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', logout);
    }
    
    console.log('Event listeners setup complete');
}

function sendChatRequest() {
    if (!isConnected) {
        showNotification('❌ Not connected to chat server', 'error');
        return;
    }
    
    const usernameInput = document.getElementById('usernameInput');
    const username = usernameInput ? usernameInput.value.trim() : '';
    
    if (!username) {
        showNotification('❌ Please enter a username', 'error');
        usernameInput?.focus();
        return;
    }
    
    if (username === currentUsername) {
        showNotification('❌ You cannot send a chat request to yourself', 'error');
        return;
    }
    
    console.log('Sending chat request to:', username);
    
    // Add loading state
    const sendBtn = document.getElementById('sendRequestBtn');
    const originalText = sendBtn?.textContent;
    if (sendBtn) {
        sendBtn.disabled = true;
        sendBtn.textContent = 'Sending...';
    }
    
    socket.send(JSON.stringify({
        type: 'send_chat_request',
        to_username: username
    }));
    
    // Reset button after a delay
    setTimeout(() => {
        if (sendBtn) {
            sendBtn.disabled = false;
            sendBtn.textContent = originalText;
        }
    }, 2000);
}

function acceptChatRequest(fromUserId) {
    if (!isConnected) {
        showNotification('❌ Not connected to chat server', 'error');
        return;
    }
    
    console.log('Accepting chat request from:', fromUserId);
    socket.send(JSON.stringify({
        type: 'accept_chat_request',
        from_user_id: fromUserId
    }));
}

function declineChatRequest(fromUserId) {
    if (!isConnected) {
        showNotification('❌ Not connected to chat server', 'error');
        return;
    }
    
    console.log('Declining chat request from:', fromUserId);
    socket.send(JSON.stringify({
        type: 'decline_chat_request',
        from_user_id: fromUserId
    }));
}

function openChat(chatId, withUser, isOnline) {
    currentChatId = chatId;
    lastMessageSender = null; // Reset message grouping
    console.log('Opening chat:', chatId, 'with:', withUser);
    
    // Update UI
    const noChatSelected = document.getElementById('noChatSelected');
    const chatArea = document.getElementById('chatArea');
    const chatWithUser = document.getElementById('chatWithUser');
    
    if (noChatSelected) noChatSelected.style.display = 'none';
    if (chatArea) {
        chatArea.style.display = 'flex';
        chatArea.classList.add('active');
    }
    if (chatWithUser) chatWithUser.textContent = withUser;
    
    // Clear messages and show loading
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        chatMessages.innerHTML = '<div class="loading-placeholder">Loading messages...</div>';
    }
    
    // Mark active chat in sidebar and remove notification indicators
    document.querySelectorAll('.chat-list-item').forEach(item => {
        item.classList.remove('active');
        item.style.background = '';
        item.style.borderColor = '';
    });
    
    const chatElement = document.querySelector(`[data-chat-id="${chatId}"]`);
    if (chatElement) {
        chatElement.classList.add('active');
    }
    
    // Enable message input
    const messageInput = document.getElementById('messageInput');
    const sendMessageBtn = document.getElementById('sendMessageBtn');
    if (messageInput && isConnected) {
        messageInput.disabled = false;
        messageInput.focus();
    }
    if (sendMessageBtn && isConnected) {
        sendMessageBtn.disabled = false;
    }
    
    // Load messages
    if (isConnected) {
        socket.send(JSON.stringify({
            type: 'get_chat_messages',
            chat_id: chatId
        }));
    }
}

function sendMessage() {
    if (!isConnected) {
        showNotification('❌ Not connected to chat server', 'error');
        return;
    }
    
    const messageInput = document.getElementById('messageInput');
    const message = messageInput ? messageInput.value.trim() : '';
    
    if (!message || !currentChatId) return;
    
    console.log('Sending message to chat:', currentChatId, 'Message:', message);
    
    // Add loading state
    const sendBtn = document.getElementById('sendMessageBtn');
    const originalText = sendBtn?.textContent;
    if (sendBtn) {
        sendBtn.disabled = true;
        sendBtn.textContent = 'Sending...';
    }
    
    socket.send(JSON.stringify({
        type: 'send_message',
        chat_id: currentChatId,
        message: message
    }));
    
    // Clear input immediately for better UX
    messageInput.value = '';
    
    // Reset button
    setTimeout(() => {
        if (sendBtn && isConnected) {
            sendBtn.disabled = false;
            sendBtn.textContent = originalText;
        }
    }, 500);
}

// FIXED: Simplified message display function
function displayMessage(messageData) {
    console.log('Displaying message:', messageData);
    
    const messagesContainer = document.getElementById('chatMessages');
    if (!messagesContainer) {
        console.error('Messages container not found');
        return;
    }
    
    // Remove loading placeholder if it exists
    const loadingPlaceholder = messagesContainer.querySelector('.loading-placeholder');
    if (loadingPlaceholder) {
        loadingPlaceholder.remove();
    }
    
    const isOwn = messageData.from_user_id === currentUserId;
    console.log('Message is from current user:', isOwn, 'Current user ID:', currentUserId, 'Message from:', messageData.from_user_id);
    
    // Create message element
    const messageDiv = document.createElement('div');
    messageDiv.className = `message-group ${isOwn ? 'own' : 'other'}`;
    
    const messageContent = document.createElement('div');
    messageContent.className = `message ${isOwn ? 'own' : 'other'}`;
    messageContent.textContent = messageData.message;
    
    const messageInfo = document.createElement('div');
    messageInfo.className = 'message-info';
    const timestamp = messageData.timestamp 
        ? new Date(messageData.timestamp * 1000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        : new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    messageInfo.textContent = `${isOwn ? 'You' : messageData.from_username} • ${timestamp}`;
    
    messageDiv.appendChild(messageContent);
    messageDiv.appendChild(messageInfo);
    messagesContainer.appendChild(messageDiv);
    
    // Force scroll to bottom
    console.log('Scrolling to bottom');
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    console.log('Message displayed successfully');
}

function displayChatMessages(messages) {
    console.log('Displaying chat messages:', messages.length);
    
    const messagesContainer = document.getElementById('chatMessages');
    if (!messagesContainer) return;
    
    messagesContainer.innerHTML = '';
    lastMessageSender = null;
    
    if (messages.length === 0) {
        messagesContainer.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--gray-500); font-style: italic;">
                No messages yet. Start the conversation! 👋
            </div>
        `;
        return;
    }
    
    messages.forEach(message => {
        const isOwn = message.from_user_id === currentUserId;
        
        // Create message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `message-group ${isOwn ? 'own' : 'other'}`;
        
        const messageContent = document.createElement('div');
        messageContent.className = `message ${isOwn ? 'own' : 'other'}`;
        messageContent.textContent = message.message;
        
        const messageInfo = document.createElement('div');
        messageInfo.className = 'message-info';
        const timestamp = new Date(message.timestamp).toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });
        messageInfo.textContent = `${isOwn ? 'You' : message.from_username} • ${timestamp}`;
        
        messageDiv.appendChild(messageContent);
        messageDiv.appendChild(messageInfo);
        messagesContainer.appendChild(messageDiv);
    });
    
    // Scroll to bottom
    setTimeout(() => {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }, 100);
}

function displayChatRequests(requests) {
    const requestsList = document.getElementById('requestsList');
    if (!requestsList) return;
    
    if (requests.length === 0) {
        requestsList.innerHTML = '<div class="loading-placeholder">No pending requests</div>';
        return;
    }
    
    requestsList.innerHTML = '';
    
    requests.forEach(request => {
        const requestDiv = document.createElement('div');
        requestDiv.className = 'request-item';
        
        const fromDiv = document.createElement('div');
        fromDiv.className = 'request-from';
        fromDiv.innerHTML = `👤 <strong>${escapeHtml(request.from_username)}</strong>`;
        
        const timeDiv = document.createElement('div');
        timeDiv.className = 'request-time';
        timeDiv.textContent = formatRelativeTime(new Date(request.created_at));
        
        const actionsDiv = document.createElement('div');
        actionsDiv.className = 'request-actions';
        actionsDiv.innerHTML = `
            <button class="accept-btn" onclick="acceptChatRequest('${request.from_user_id}')">✅ Accept</button>
            <button class="decline-btn" onclick="declineChatRequest('${request.from_user_id}')">❌ Decline</button>
        `;
        
        requestDiv.appendChild(fromDiv);
        requestDiv.appendChild(timeDiv);
        requestDiv.appendChild(actionsDiv);
        requestsList.appendChild(requestDiv);
    });
}

function displayActiveChats(chats) {
    const chatsList = document.getElementById('chatsList');
    if (!chatsList) return;
    
    if (chats.length === 0) {
        chatsList.innerHTML = '<div class="loading-placeholder">No active chats</div>';
        return;
    }
    
    chatsList.innerHTML = '';
    
    // Sort chats by last message time (most recent first)
    chats.sort((a, b) => new Date(b.last_message_at) - new Date(a.last_message_at));
    
    chats.forEach(chat => {
        const chatDiv = document.createElement('div');
        chatDiv.className = 'chat-list-item';
        chatDiv.setAttribute('data-chat-id', chat.chat_id);
        chatDiv.onclick = () => openChat(chat.chat_id, chat.with_user, chat.online);
        
        const avatar = document.createElement('div');
        avatar.className = `chat-avatar ${chat.online ? 'online' : 'offline'}`;
        avatar.style.background = window.getAvatarColor ? window.getAvatarColor(chat.with_user) : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
        avatar.textContent = window.getUserInitials ? window.getUserInitials(chat.with_user) : chat.with_user.charAt(0).toUpperCase();
        
        const content = document.createElement('div');
        content.className = 'chat-list-content';
        
        const username = document.createElement('div');
        username.className = 'chat-username';
        username.textContent = chat.with_user;
        
        const time = document.createElement('div');
        time.className = 'chat-time';
        time.textContent = formatRelativeTime(new Date(chat.last_message_at));
        
        content.appendChild(username);
        content.appendChild(time);
        
        chatDiv.appendChild(avatar);
        chatDiv.appendChild(content);
        chatsList.appendChild(chatDiv);
        
        // Mark as active if this is the current chat
        if (chat.chat_id === currentChatId) {
            chatDiv.classList.add('active');
        }
    });
}

function loadChatRequests() {
    if (socket && socket.readyState === WebSocket.OPEN) {
        socket.send(JSON.stringify({ type: 'get_chat_requests' }));
    }
}

function loadActiveChats() {
    if (socket && socket.readyState === WebSocket.OPEN) {
        socket.send(JSON.stringify({ type: 'get_active_chats' }));
    }
}

function showNotification(message, type = 'success') {
    console.log('Notification:', message, 'Type:', type);
    
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

function formatRelativeTime(date) {
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) {
        return 'Just now';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `${minutes}m ago`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours}h ago`;
    } else if (diffInSeconds < 604800) {
        const days = Math.floor(diffInSeconds / 86400);
        return `${days}d ago`;
    } else {
        return date.toLocaleDateString();
    }
}

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function logout() {
    console.log('Logout requested');
    try {
        await fetch('/api/logout', { method: 'POST' });
    } catch (error) {
        console.log('Logout request failed, but redirecting anyway');
    }
    window.location.href = '/login';
}
