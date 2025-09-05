import { CHAT_CSS_CLASSES } from './config.js';
import { ChatUtils } from './utils.js';

/**
 * Chat UI rendering functionality
 */
export class ChatUI {
  constructor(state) {
    this.state = state;
  }

  /**
   * Render chat requests list
   */
  renderChatRequests() {
    const requestsList = document.getElementById('requestsList');
    if (!requestsList) return;
    
    if (this.state.chatRequestsCache.length === 0) {
      requestsList.innerHTML = '<div class="loading-placeholder">No pending requests</div>';
      return;
    }
    
    requestsList.innerHTML = '';
    
    this.state.chatRequestsCache.forEach(request => {
      const requestDiv = document.createElement('div');
      requestDiv.className = CHAT_CSS_CLASSES.requestItem;
      requestDiv.id = `request-${request.from_user_id}`;
      
      const fromDiv = document.createElement('div');
      fromDiv.className = 'request-from';
      fromDiv.innerHTML = `<strong>${ChatUtils.escapeHtml(request.from_username)}</strong>`;
      
      const timeDiv = document.createElement('div');
      timeDiv.className = 'request-time';
      timeDiv.textContent = ChatUtils.formatRelativeTime(new Date(request.created_at));
      
      const actionsDiv = document.createElement('div');
      actionsDiv.className = 'request-actions';
      actionsDiv.innerHTML = `
        <button class="accept-btn" onclick="window.chatApp.acceptChatRequestWithLoading('${request.from_user_id}', this)">Accept</button>
        <button class="decline-btn" onclick="window.chatApp.declineChatRequestWithLoading('${request.from_user_id}', this)">Decline</button>
      `;
      
      requestDiv.appendChild(fromDiv);
      requestDiv.appendChild(timeDiv);
      requestDiv.appendChild(actionsDiv);
      requestsList.appendChild(requestDiv);
    });
  }

  /**
   * Render active chats list
   */
  renderActiveChats() {
    const chatsList = document.getElementById('chatsList');
    if (!chatsList) return;
    
    if (this.state.activeChatsCache.length === 0) {
      chatsList.innerHTML = '<div class="loading-placeholder">No active chats</div>';
      return;
    }
    
    chatsList.innerHTML = '';
    const sortedChats = this.state.getSortedActiveChats();
    
    sortedChats.forEach(chat => {
      const chatDiv = document.createElement('div');
      chatDiv.className = CHAT_CSS_CLASSES.chatListItem;
      chatDiv.setAttribute('data-chat-id', chat.chat_id);
      chatDiv.onclick = () => window.chatApp.openChat(chat.chat_id, chat.with_user, chat.online);
      
      const avatar = document.createElement('div');
      avatar.className = `${CHAT_CSS_CLASSES.chatAvatar} ${chat.online ? CHAT_CSS_CLASSES.online : CHAT_CSS_CLASSES.offline}`;
      avatar.style.background = ChatUtils.getAvatarColor(chat.with_user);
      avatar.textContent = ChatUtils.getUserInitials(chat.with_user);
      
      const content = document.createElement('div');
      content.className = 'chat-list-content';
      
      const username = document.createElement('div');
      username.className = 'chat-username';
      username.textContent = chat.with_user;
      
      const time = document.createElement('div');
      time.className = 'chat-time';
      time.textContent = ChatUtils.formatRelativeTime(new Date(chat.last_message_at));
      
      content.appendChild(username);
      content.appendChild(time);
      
      chatDiv.appendChild(avatar);
      chatDiv.appendChild(content);
      chatsList.appendChild(chatDiv);
      
      // Mark as active if this is the current chat
      if (chat.chat_id === this.state.currentChatId) {
        chatDiv.classList.add(CHAT_CSS_CLASSES.active);
      }
    });
  }

  /**
   * Render chat messages
   */
  renderChatMessages() {
    const messagesContainer = document.getElementById('chatMessages');
    if (!messagesContainer) return;
    
    messagesContainer.innerHTML = '';
    this.state.lastMessageSender = null;
    
    if (this.state.currentChatMessagesCache.length === 0) {
      messagesContainer.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--gray-500); font-style: italic;">
          No messages yet. Start the conversation!
        </div>
      `;
      return;
    }
    
    this.state.currentChatMessagesCache.forEach(message => {
      this.appendMessageToUI(message);
    });
    
    // Scroll to bottom
    setTimeout(() => {
      messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }, 100);
  }

  /**
   * Append single message to UI
   */
  appendMessageToUI(messageData) {
    const messagesContainer = document.getElementById('chatMessages');
    if (!messagesContainer) return;
    
    // Remove loading placeholder if it exists
    const loadingPlaceholder = messagesContainer.querySelector('.loading-placeholder');
    if (loadingPlaceholder) {
      loadingPlaceholder.remove();
    }
    
    const isOwn = messageData.from_user_id === this.state.currentUserId;
    
    // Create message element
    const messageDiv = document.createElement('div');
    messageDiv.className = `${CHAT_CSS_CLASSES.messageGroup} ${isOwn ? CHAT_CSS_CLASSES.own : CHAT_CSS_CLASSES.other}`;
    
    const messageContent = document.createElement('div');
    messageContent.className = `${CHAT_CSS_CLASSES.message} ${isOwn ? CHAT_CSS_CLASSES.own : CHAT_CSS_CLASSES.other}`;
    messageContent.textContent = messageData.message;
    
    const messageInfo = document.createElement('div');
    messageInfo.className = CHAT_CSS_CLASSES.messageInfo;
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

  /**
   * Update chat UI when opening a chat
   */
  updateChatUI(chatId, withUser, isOnline) {
    // Update UI elements
    const noChatSelected = document.getElementById('noChatSelected');
    const chatArea = document.getElementById('chatArea');
    const chatWithUser = document.getElementById('chatWithUser');
    const chatHeaderAvatar = document.getElementById('chatHeaderAvatar');
    const chatHeaderInitial = document.getElementById('chatHeaderInitial');
    const chatHeaderStatus = document.getElementById('chatHeaderStatus');
    
    if (noChatSelected) noChatSelected.style.display = 'none';
    if (chatArea) {
      chatArea.style.display = 'flex';
      chatArea.classList.add(CHAT_CSS_CLASSES.active);
    }
    if (chatWithUser) chatWithUser.textContent = withUser;
    
    // Update header avatar and status
    if (chatHeaderAvatar && chatHeaderInitial && chatHeaderStatus) {
      chatHeaderInitial.textContent = withUser.charAt(0).toUpperCase();
      chatHeaderAvatar.className = `chat-header-avatar ${isOnline ? CHAT_CSS_CLASSES.online : CHAT_CSS_CLASSES.offline}`;
      chatHeaderAvatar.style.background = ChatUtils.getAvatarColor(withUser);
      chatHeaderStatus.textContent = isOnline ? '🟢 Online' : '⚫ Last seen recently';
    }
    
    // Clear messages and show loading
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
      chatMessages.innerHTML = '<div class="loading-placeholder">Loading messages...</div>';
    }
    
    // Mark active chat in sidebar and remove notification indicators
    document.querySelectorAll(`.${CHAT_CSS_CLASSES.chatListItem}`).forEach(item => {
      item.classList.remove(CHAT_CSS_CLASSES.active);
      item.style.background = '';
      item.style.borderColor = '';
    });
    
    const chatElement = document.querySelector(`[data-chat-id="${chatId}"]`);
    if (chatElement) {
      chatElement.classList.add(CHAT_CSS_CLASSES.active);
      chatElement.style.background = '';
      chatElement.style.borderColor = '';
    }
    
    // Enable message input
    const messageInput = document.getElementById('messageInput');
    const sendMessageBtn = document.getElementById('sendMessageBtn');
    if (messageInput) {
      messageInput.disabled = false;
      messageInput.focus();
    }
    if (sendMessageBtn) {
      sendMessageBtn.disabled = false;
    }
  }

  /**
   * Add notification indicator to chat item
   */
  addNotificationIndicator(chatId) {
    setTimeout(() => {
      const chatItem = document.querySelector(`[data-chat-id="${chatId}"]`);
      if (chatItem && !chatItem.classList.contains(CHAT_CSS_CLASSES.active)) {
        chatItem.style.background = 'linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%)';
        chatItem.style.borderColor = '#f59e0b';
      }
    }, 100);
  }
}