import { WEBSOCKET_TYPES } from './chat/config.js';
import { showNotification } from './chat/notifications.js';
import { initializeWebSocket, sendWebSocketMessage, requestChatRequests, requestActiveChats, getIsConnected } from './chat/websocket.js';
import { formatRelativeTime, escapeHtml, getUserInitials, getAvatarColor } from './chat/utils.js';

// State variables (keep them simple like the original)
let currentChatId = null;
let currentUserId = null;
let currentUsername = null;
let lastMessageSender = null;

// State management for real-time updates
let chatRequestsCache = [];
let activeChatsCache = [];
let currentChatMessagesCache = [];

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
    initializeWebSocket(handleWebSocketMessage);
    setupEventListeners();
});

function handleWebSocketMessage(data) {
    console.log('=== WebSocket Message Received ===');
    console.log('Type:', data.type);
    console.log('Full data:', data);
    
    switch (data.type) {
        case WEBSOCKET_TYPES.AUTH_SUCCESS:
            currentUserId = data.user_id;
            currentUsername = data.username;
            console.log('WebSocket authentication successful. User:', currentUsername, 'ID:', currentUserId);
            
            showNotification(`Connected as ${currentUsername}`, 'success');
            
            // Request initial data
            requestChatRequests();
            requestActiveChats();
            break;
            
        case WEBSOCKET_TYPES.NEW_CHAT_REQUEST:
            console.log('New chat request received from:', data.from_username);
            showNotification(`New chat request from ${data.from_username}`);
            
            // Add the new request to cache and update UI
            const newRequest = {
                from_user_id: data.from_user_id,
                from_username: data.from_username,
                created_at: new Date().toISOString()
            };
            chatRequestsCache.unshift(newRequest);
            renderChatRequests();
            break;
            
        case WEBSOCKET_TYPES.CHAT_REQUEST_SENT:
            console.log('Chat request sent to:', data.to_username);
            showNotification(`Chat request sent to ${data.to_username}`);
            const usernameInput = document.getElementById('usernameInput');
            if (usernameInput) usernameInput.value = '';
            break;
            
        case WEBSOCKET_TYPES.CHAT_ACCEPTED:
            console.log('Chat accepted - chat_id:', data.chat_id, 'with_user:', data.with_user);
            showNotification(`Chat accepted by ${data.with_user}`);
            
            // Add new chat to cache
            const newChat = {
                chat_id: data.chat_id,
                with_user: data.with_user,
                with_user_id: data.with_user_id || 'unknown',
                online: true, // Assume online since they just accepted
                last_message_at: new Date().toISOString()
            };
            
            // Remove from requests if it exists
            chatRequestsCache = chatRequestsCache.filter(req => 
                req.from_username !== data.with_user && 
                req.to_username !== data.with_user
            );
            
            // Add to active chats if not already exists
            const existingChatIndex = activeChatsCache.findIndex(chat => chat.chat_id === data.chat_id);
            if (existingChatIndex === -1) {
                activeChatsCache.unshift(newChat);
            }
            
            // Update UI immediately
            renderChatRequests();
            renderActiveChats();
            break;
            
        case WEBSOCKET_TYPES.CHAT_DECLINED:
            console.log('Chat declined by:', data.by_user);
            showNotification(`Chat request declined by ${data.by_user}`);
            
            // Remove from requests cache
            chatRequestsCache = chatRequestsCache.filter(req => req.from_username !== data.by_user);
            renderChatRequests();
            break;

        case WEBSOCKET_TYPES.REQUEST_STATUS_CHANGED:
            // Refresh data from server
            requestChatRequests();
            requestActiveChats();
            break;
            
        case WEBSOCKET_TYPES.NEW_MESSAGE:
            console.log('New message received for chat:', data.chat_id);
            handleNewMessage(data);
            break;
            
        case WEBSOCKET_TYPES.CHAT_REQUESTS:
            console.log('Chat requests received:', data.requests.length, 'requests');
            chatRequestsCache = data.requests;
            renderChatRequests();
            break;
            
        case WEBSOCKET_TYPES.ACTIVE_CHATS:
            console.log('Active chats received:', data.chats.length, 'chats');
            activeChatsCache = data.chats;
            renderActiveChats();
            break;
            
        case WEBSOCKET_TYPES.CHAT_MESSAGES:
            console.log('Chat messages received for chat:', data.chat_id, 'Messages:', data.messages.length);
            if (data.chat_id === currentChatId) {
                currentChatMessagesCache = data.messages;
                renderChatMessages();
            }
            break;
            
        case WEBSOCKET_TYPES.ERROR:
            console.error('WebSocket server error:', data.message);
            showNotification(`${data.message}`, 'error');
            break;
            
        default:
            console.log('Unknown WebSocket message type:', data.type, 'Full data:', data);
    }
}

function handleNewMessage(messageData) {
    const isForCurrentChat = messageData.chat_id === currentChatId;
    const isFromCurrentUser = messageData.from_user_id === currentUserId;
    
    // Update the message in the current chat if it's the active chat
    if (isForCurrentChat) {
        // Add to messages cache and render
        const newMessage = {
            from_user_id: messageData.from_user_id,
            from_username: messageData.from_username,
            message: messageData.message,
            timestamp: messageData.timestamp ? new Date(messageData.timestamp * 1000).toISOString() : new Date().toISOString()
        };
        
        currentChatMessagesCache.push(newMessage);
        appendMessageToUI(newMessage);
    }
    
    // Update the last message time in active chats cache
    const chatIndex = activeChatsCache.findIndex(chat => chat.chat_id === messageData.chat_id);
    if (chatIndex !== -1) {
        activeChatsCache[chatIndex].last_message_at = new Date().toISOString();
        
        // Move this chat to the top of the list
        const [updatedChat] = activeChatsCache.splice(chatIndex, 1);
        activeChatsCache.unshift(updatedChat);
        
        renderActiveChats();
    }
    
    // Show notification if not current chat and not from current user
    if (!isForCurrentChat && !isFromCurrentUser) {
        showNotification(`New message from ${messageData.from_username}`);
        
        // Add visual indicator to chat list item
        setTimeout(() => {
            const chatItem = document.querySelector(`[data-chat-id="${messageData.chat_id}"]`);
            if (chatItem && !chatItem.classList.contains('active')) {
                chatItem.style.background = 'linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%)';
                chatItem.style.borderColor = '#f59e0b';
            }
        }, 100);
    }
}

function appendMessageToUI(messageData) {
    const messagesContainer = document.getElementById('chatMessages');
    if (!messagesContainer) return;
    
    // Remove loading placeholder if it exists
    const loadingPlaceholder = messagesContainer.querySelector('.loading-placeholder');
    if (loadingPlaceholder) {
        loadingPlaceholder.remove();
    }
    
    const isOwn = messageData.from_user_id === currentUserId;
    
    // Create message element
    const messageDiv = document.createElement('div');
    messageDiv.className = `message-group ${isOwn ? 'own' : 'other'}`;
    
    const messageContent = document.createElement('div');
    messageContent.className = `message ${isOwn ? 'own' : 'other'}`;
    messageContent.textContent = messageData.message;
    
    const messageInfo = document.createElement('div');
    messageInfo.className = 'message-info';
    const timestamp = messageData.timestamp 
        ? new Date(messageData.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        : new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    messageInfo.textContent = `${isOwn ? 'You' : messageData.from_username} • ${timestamp}`;
    
    messageDiv.appendChild(messageContent);
    messageDiv.appendChild(messageInfo);
    messagesContainer.appendChild(messageDiv);
    
    // Smooth scroll to bottom
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function renderChatRequests() {
    const requestsList = document.getElementById('requestsList');
    if (!requestsList) return;
    
    if (chatRequestsCache.length === 0) {
        requestsList.innerHTML = '<div class="loading-placeholder">No pending requests</div>';
        return;
    }
    
    requestsList.innerHTML = '';
    
    chatRequestsCache.forEach(request => {
        const requestDiv = document.createElement('div');
        requestDiv.className = 'request-item';
        requestDiv.id = `request-${request.from_user_id}`;
        
        const fromDiv = document.createElement('div');
        fromDiv.className = 'request-from';
        fromDiv.innerHTML = `<strong>${escapeHtml(request.from_username)}</strong>`;
        
        const timeDiv = document.createElement('div');
        timeDiv.className = 'request-time';
        timeDiv.textContent = formatRelativeTime(new Date(request.created_at));
        
        const actionsDiv = document.createElement('div');
        actionsDiv.className = 'request-actions';
        actionsDiv.innerHTML = `
            <button class="accept-btn" onclick="acceptChatRequestWithLoading('${request.from_user_id}', this)">Accept</button>
            <button class="decline-btn" onclick="declineChatRequestWithLoading('${request.from_user_id}', this)">Decline</button>
        `;
        
        requestDiv.appendChild(fromDiv);
        requestDiv.appendChild(timeDiv);
        requestDiv.appendChild(actionsDiv);
        requestsList.appendChild(requestDiv);
    });
}

function renderActiveChats() {
    const chatsList = document.getElementById('chatsList');
    if (!chatsList) return;
    
    if (activeChatsCache.length === 0) {
        chatsList.innerHTML = '<div class="loading-placeholder">No active chats</div>';
        return;
    }
    
    chatsList.innerHTML = '';
    
    // Sort chats by last message time (most recent first)
    const sortedChats = [...activeChatsCache].sort((a, b) => 
        new Date(b.last_message_at) - new Date(a.last_message_at)
    );
    
    sortedChats.forEach(chat => {
        const chatDiv = document.createElement('div');
        chatDiv.className = 'chat-list-item';
        chatDiv.setAttribute('data-chat-id', chat.chat_id);
        chatDiv.onclick = () => openChat(chat.chat_id, chat.with_user, chat.online);
        
        const avatar = document.createElement('div');
        avatar.className = `chat-avatar ${chat.online ? 'online' : 'offline'}`;
        avatar.style.background = getAvatarColor(chat.with_user);
        avatar.textContent = getUserInitials(chat.with_user);
        
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

function renderChatMessages() {
    const messagesContainer = document.getElementById('chatMessages');
    if (!messagesContainer) return;
    
    messagesContainer.innerHTML = '';
    lastMessageSender = null;
    
    if (currentChatMessagesCache.length === 0) {
        messagesContainer.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--gray-500); font-style: italic;">
                No messages yet. Start the conversation!
            </div>
        `;
        return;
    }
    
    currentChatMessagesCache.forEach(message => {
        appendMessageToUI(message);
    });
    
    // Scroll to bottom
    setTimeout(() => {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }, 100);
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
            if (e.key === 'Enter' && getIsConnected()) {
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
    if (!getIsConnected()) {
        showNotification('Not connected to chat server', 'error');
        return;
    }
    
    const usernameInput = document.getElementById('usernameInput');
    const username = usernameInput ? usernameInput.value.trim() : '';
    
    if (!username) {
        showNotification('Please enter a username', 'error');
        usernameInput?.focus();
        return;
    }
    
    if (username === currentUsername) {
        showNotification('You cannot send a chat request to yourself', 'error');
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
    
    sendWebSocketMessage({
        type: WEBSOCKET_TYPES.SEND_CHAT_REQUEST,
        to_username: username
    });
    
    // Reset button after a delay
    setTimeout(() => {
        if (sendBtn) {
            sendBtn.disabled = false;
            sendBtn.textContent = originalText;
        }
    }, 2000);
}

function acceptChatRequest(fromUserId) {
    if (!getIsConnected()) {
        showNotification('Not connected to chat server', 'error');
        return;
    }
    
    console.log('Accepting chat request from:', fromUserId);
    sendWebSocketMessage({
        type: WEBSOCKET_TYPES.ACCEPT_CHAT_REQUEST,
        from_user_id: fromUserId
    });
}

function declineChatRequest(fromUserId) {
    if (!getIsConnected()) {
        showNotification('Not connected to chat server', 'error');
        return;
    }
    
    console.log('Declining chat request from:', fromUserId);
    sendWebSocketMessage({
        type: WEBSOCKET_TYPES.DECLINE_CHAT_REQUEST,
        from_user_id: fromUserId
    });
}

function acceptChatRequestWithLoading(fromUserId, buttonElement) {
    const actionsDiv = buttonElement.parentNode;
    actionsDiv.innerHTML = '<div style="text-align: center; color: #22c55e;">Accepting...</div>';
    acceptChatRequest(fromUserId);
}

function declineChatRequestWithLoading(fromUserId, buttonElement) {
    const actionsDiv = buttonElement.parentNode;
    actionsDiv.innerHTML = '<div style="text-align: center; color: #ef4444;">Declining...</div>';
    declineChatRequest(fromUserId);
}

function openChat(chatId, withUser, isOnline) {
    currentChatId = chatId;
    lastMessageSender = null;
    currentChatMessagesCache = []; // Clear messages cache
    
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
        chatElement.style.background = '';
        chatElement.style.borderColor = '';
    }
    
    // Enable message input
    const messageInput = document.getElementById('messageInput');
    const sendMessageBtn = document.getElementById('sendMessageBtn');
    if (messageInput && getIsConnected()) {
        messageInput.disabled = false;
        messageInput.focus();
    }
    if (sendMessageBtn && getIsConnected()) {
        sendMessageBtn.disabled = false;
    }
    
    // Load messages
    if (getIsConnected()) {
        sendWebSocketMessage({
            type: WEBSOCKET_TYPES.GET_CHAT_MESSAGES,
            chat_id: chatId
        });
    }
}

function sendMessage() {
    if (!getIsConnected()) {
        showNotification('Not connected to chat server', 'error');
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
    
    sendWebSocketMessage({
        type: WEBSOCKET_TYPES.SEND_MESSAGE,
        chat_id: currentChatId,
        message: message
    });
    
    // Clear input immediately for better UX
    messageInput.value = '';
    
    // Reset button
    setTimeout(() => {
        if (sendBtn && getIsConnected()) {
            sendBtn.disabled = false;
            sendBtn.textContent = originalText;
        }
    }, 500);
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

// Global functions for compatibility and onclick handlers
window.sendChatRequest = sendChatRequest;
window.sendMessage = sendMessage;
window.openChat = openChat;
window.acceptChatRequest = acceptChatRequest;
window.declineChatRequest = declineChatRequest;
window.acceptChatRequestWithLoading = acceptChatRequestWithLoading;
window.declineChatRequestWithLoading = declineChatRequestWithLoading;

// Make utility functions available globally for compatibility
window.getUserInitials = getUserInitials;
window.getAvatarColor = getAvatarColor;

// Deprecated functions - keeping for compatibility
function loadChatRequests() {
    requestChatRequests();
}

function loadActiveChats() {
    requestActiveChats();
}

function displayMessage(messageData) {
    appendMessageToUI(messageData);
}

function displayChatMessages(messages) {
    currentChatMessagesCache = messages;
    renderChatMessages();
}

function displayChatRequests(requests) {
    chatRequestsCache = requests;
    renderChatRequests();
}

function displayActiveChats(chats) {
    activeChatsCache = chats;
    renderActiveChats();
}
