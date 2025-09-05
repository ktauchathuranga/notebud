import { CHAT_CONFIG } from './config.js';

/**
 * Chat application state management
 */
export class ChatState {
  constructor() {
    this.currentChatId = null;
    this.currentUserId = null;
    this.currentUsername = null;
    this.lastMessageSender = null;
    
    // Cache for real-time updates
    this.chatRequestsCache = [];
    this.activeChatsCache = [];
    this.currentChatMessagesCache = [];
  }

  /**
   * Initialize state from window variables
   */
  init() {
    this.currentUserId = window.USER_ID;
    
    if (!this.currentUserId) {
      console.error('No user ID found - this should not happen if PHP auth worked');
      throw new Error('Authentication error. No user ID found.');
    }
    
    if (!window.JWT_TOKEN) {
      console.error('No JWT token found - this should not happen if PHP auth worked');
      throw new Error('Authentication error. No JWT token found.');
    }
    
    console.log('Chat state initialized with user ID:', this.currentUserId);
  }

  /**
   * Set current user info from WebSocket auth response
   */
  setCurrentUser(userId, username) {
    this.currentUserId = userId;
    this.currentUsername = username;
    console.log('Current user set:', { userId, username });
  }

  /**
   * Set current active chat
   */
  setCurrentChat(chatId) {
    this.currentChatId = chatId;
    this.lastMessageSender = null;
    this.currentChatMessagesCache = [];
  }

  /**
   * Add chat request to cache
   */
  addChatRequest(request) {
    this.chatRequestsCache.unshift(request);
  }

  /**
   * Remove chat request from cache
   */
  removeChatRequest(fromUsername) {
    this.chatRequestsCache = this.chatRequestsCache.filter(req => 
      req.from_username !== fromUsername && req.to_username !== fromUsername
    );
  }

  /**
   * Set chat requests cache
   */
  setChatRequests(requests) {
    this.chatRequestsCache = requests;
  }

  /**
   * Add or update active chat
   */
  addOrUpdateActiveChat(chat) {
    const existingIndex = this.activeChatsCache.findIndex(c => c.chat_id === chat.chat_id);
    
    if (existingIndex === -1) {
      this.activeChatsCache.unshift(chat);
    } else {
      this.activeChatsCache[existingIndex] = { ...this.activeChatsCache[existingIndex], ...chat };
    }
  }

  /**
   * Set active chats cache
   */
  setActiveChats(chats) {
    this.activeChatsCache = chats;
  }

  /**
   * Update chat's last message time and move to top
   */
  updateChatLastMessage(chatId, timestamp) {
    const chatIndex = this.activeChatsCache.findIndex(chat => chat.chat_id === chatId);
    if (chatIndex !== -1) {
      this.activeChatsCache[chatIndex].last_message_at = timestamp;
      
      // Move to top
      const [updatedChat] = this.activeChatsCache.splice(chatIndex, 1);
      this.activeChatsCache.unshift(updatedChat);
    }
  }

  /**
   * Add message to current chat cache
   */
  addMessageToCurrentChat(message) {
    if (this.currentChatMessagesCache.length > CHAT_CONFIG.MESSAGE_BUFFER_SIZE) {
      this.currentChatMessagesCache.shift();
    }
    this.currentChatMessagesCache.push(message);
  }

  /**
   * Set current chat messages
   */
  setCurrentChatMessages(messages) {
    this.currentChatMessagesCache = messages;
  }

  /**
   * Get sorted active chats
   */
  getSortedActiveChats() {
    return [...this.activeChatsCache].sort((a, b) => 
      new Date(b.last_message_at) - new Date(a.last_message_at)
    );
  }

  /**
   * Check if message is for current chat
   */
  isMessageForCurrentChat(chatId) {
    return chatId === this.currentChatId;
  }

  /**
   * Check if message is from current user
   */
  isMessageFromCurrentUser(fromUserId) {
    return fromUserId === this.currentUserId;
  }
}
