import { ChatState } from './state.js';
import { ChatWebSocket } from './websocket.js';
import { ChatUI } from './ui.js';
import { ChatMessageHandlers } from './handlers.js';
import { ChatNotifications } from './notifications.js';
import { WEBSOCKET_TYPES } from './config.js';

/**
 * Main Chat Application
 */
export class ChatApplication {
  constructor() {
    this.state = new ChatState();
    this.websocket = new ChatWebSocket();
    this.ui = new ChatUI(this.state);
    this.handlers = new ChatMessageHandlers(this.state, this.ui, this.websocket);
  }

  /**
   * Initialize chat application
   */
  init() {
    try {
      console.log('=== CHAT PAGE DEBUG START ===');
      
      // Initialize state
      this.state.init();
      
      // Setup WebSocket message handlers
      this.handlers.setupHandlers();
      
      console.log('=== STARTING WEBSOCKET INITIALIZATION ===');
      
      // Initialize WebSocket connection
      this.websocket.connect();
      
      // Setup DOM event listeners
      this.setupEventListeners();
      
    } catch (error) {
      console.error('Failed to initialize chat application:', error);
      ChatNotifications.show(error.message, 'error');
    }
  }

  /**
   * Setup DOM event listeners
   */
  setupEventListeners() {
    console.log('Setting up event listeners...');
    
    const messageInput = document.getElementById('messageInput');
    if (messageInput) {
      messageInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.target.disabled) {
          this.sendMessage();
        }
      });
    }
    
    const usernameInput = document.getElementById('usernameInput');
    if (usernameInput) {
      usernameInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && this.websocket.isConnected) {
          this.sendChatRequest();
        }
      });
    }
    
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', this.logout);
    }
    
    console.log('Event listeners setup complete');
  }

  /**
   * Send chat request
   */
  sendChatRequest() {
    if (!this.websocket.isConnected) {
      ChatNotifications.show('Not connected to chat server', 'error');
      return;
    }
    
    const usernameInput = document.getElementById('usernameInput');
    const username = usernameInput ? usernameInput.value.trim() : '';
    
    if (!username) {
      ChatNotifications.show('Please enter a username', 'error');
      usernameInput?.focus();
      return;
    }
    
    if (username === this.state.currentUsername) {
      ChatNotifications.show('You cannot send a chat request to yourself', 'error');
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
    
    this.websocket.send({
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

  /**
   * Accept chat request
   */
  acceptChatRequest(fromUserId) {
    if (!this.websocket.isConnected) {
      ChatNotifications.show('Not connected to chat server', 'error');
      return;
    }
    
    console.log('Accepting chat request from:', fromUserId);
    this.websocket.send({
      type: WEBSOCKET_TYPES.ACCEPT_CHAT_REQUEST,
      from_user_id: fromUserId
    });
  }

  /**
   * Decline chat request
   */
  declineChatRequest(fromUserId) {
    if (!this.websocket.isConnected) {
      ChatNotifications.show('Not connected to chat server', 'error');
      return;
    }
    
    console.log('Declining chat request from:', fromUserId);
    this.websocket.send({
      type: WEBSOCKET_TYPES.DECLINE_CHAT_REQUEST,
      from_user_id: fromUserId
    });
  }

  /**
   * Accept chat request with loading UI
   */
  acceptChatRequestWithLoading(fromUserId, buttonElement) {
    const actionsDiv = buttonElement.parentNode;
    actionsDiv.innerHTML = '<div style="text-align: center; color: #22c55e;">Accepting...</div>';
    this.acceptChatRequest(fromUserId);
  }

  /**
   * Decline chat request with loading UI
   */
  declineChatRequestWithLoading(fromUserId, buttonElement) {
    const actionsDiv = buttonElement.parentNode;
    actionsDiv.innerHTML = '<div style="text-align: center; color: #ef4444;">Declining...</div>';
    this.declineChatRequest(fromUserId);
  }

  /**
   * Open chat
   */
  openChat(chatId, withUser, isOnline) {
    console.log('Opening chat:', chatId, 'with:', withUser);
    
    this.state.setCurrentChat(chatId);
    this.ui.updateChatUI(chatId, withUser, isOnline);
    
    // Load messages
    if (this.websocket.isConnected) {
      this.websocket.send({
        type: WEBSOCKET_TYPES.GET_CHAT_MESSAGES,
        chat_id: chatId
      });
    }
    
    // Call mobile UI function if available
    if (window.originalOpenChat) {
      window.originalOpenChat(chatId, withUser, isOnline);
    }
  }

  /**
   * Send message
   */
  sendMessage() {
    if (!this.websocket.isConnected) {
      ChatNotifications.show('Not connected to chat server', 'error');
      return;
    }
    
    const messageInput = document.getElementById('messageInput');
    const message = messageInput ? messageInput.value.trim() : '';
    
    if (!message || !this.state.currentChatId) return;
    
    console.log('Sending message to chat:', this.state.currentChatId, 'Message:', message);
    
    // Add loading state
    const sendBtn = document.getElementById('sendMessageBtn');
    const originalText = sendBtn?.textContent;
    if (sendBtn) {
      sendBtn.disabled = true;
      sendBtn.textContent = 'Sending...';
    }
    
    this.websocket.send({
      type: WEBSOCKET_TYPES.SEND_MESSAGE,
      chat_id: this.state.currentChatId,
      message: message
    });
    
    // Clear input immediately for better UX
    messageInput.value = '';
    
    // Reset button
    setTimeout(() => {
      if (sendBtn && this.websocket.isConnected) {
        sendBtn.disabled = false;
        sendBtn.textContent = originalText;
      }
    }, 500);
  }

  /**
   * Logout
   */
  async logout() {
    console.log('Logout requested');
    try {
      await fetch('/api/logout', { method: 'POST' });
    } catch (error) {
      console.log('Logout request failed, but redirecting anyway');
    }
    window.location.href = '/login';
  }
}