import { WEBSOCKET_TYPES } from './config.js';
import { ChatNotifications } from './notifications.js';
import { ChatUtils } from './utils.js';

/**
 * WebSocket message handlers
 */
export class ChatMessageHandlers {
  constructor(state, ui, websocket) {
    this.state = state;
    this.ui = ui;
    this.websocket = websocket;
  }

  /**
   * Setup all message handlers
   */
  setupHandlers() {
    this.websocket.onMessage(WEBSOCKET_TYPES.AUTH_SUCCESS, this.handleAuthSuccess.bind(this));
    this.websocket.onMessage(WEBSOCKET_TYPES.NEW_CHAT_REQUEST, this.handleNewChatRequest.bind(this));
    this.websocket.onMessage(WEBSOCKET_TYPES.CHAT_REQUEST_SENT, this.handleChatRequestSent.bind(this));
    this.websocket.onMessage(WEBSOCKET_TYPES.CHAT_ACCEPTED, this.handleChatAccepted.bind(this));
    this.websocket.onMessage(WEBSOCKET_TYPES.CHAT_DECLINED, this.handleChatDeclined.bind(this));
    this.websocket.onMessage(WEBSOCKET_TYPES.REQUEST_STATUS_CHANGED, this.handleRequestStatusChanged.bind(this));
    this.websocket.onMessage(WEBSOCKET_TYPES.NEW_MESSAGE, this.handleNewMessage.bind(this));
    this.websocket.onMessage(WEBSOCKET_TYPES.CHAT_REQUESTS, this.handleChatRequests.bind(this));
    this.websocket.onMessage(WEBSOCKET_TYPES.ACTIVE_CHATS, this.handleActiveChats.bind(this));
    this.websocket.onMessage(WEBSOCKET_TYPES.CHAT_MESSAGES, this.handleChatMessages.bind(this));
    this.websocket.onMessage(WEBSOCKET_TYPES.ERROR, this.handleError.bind(this));
  }

  handleAuthSuccess(data) {
    this.state.setCurrentUser(data.user_id, data.username);
    console.log('WebSocket authentication successful. User:', data.username, 'ID:', data.user_id);
    
    ChatNotifications.show(`Connected as ${data.username}`, 'success');
    
    // Request initial data
    this.websocket.send({ type: WEBSOCKET_TYPES.GET_CHAT_REQUESTS });
    this.websocket.send({ type: WEBSOCKET_TYPES.GET_ACTIVE_CHATS });
  }

  handleNewChatRequest(data) {
    console.log('New chat request received from:', data.from_username);
    ChatNotifications.show(`New chat request from ${data.from_username}`);
    
    const newRequest = {
      from_user_id: data.from_user_id,
      from_username: data.from_username,
      created_at: new Date().toISOString()
    };
    
    this.state.addChatRequest(newRequest);
    this.ui.renderChatRequests();
  }

  handleChatRequestSent(data) {
    console.log('Chat request sent to:', data.to_username);
    ChatNotifications.show(`Chat request sent to ${data.to_username}`);
    
    const usernameInput = document.getElementById('usernameInput');
    if (usernameInput) usernameInput.value = '';
  }

  handleChatAccepted(data) {
    console.log('Chat accepted - chat_id:', data.chat_id, 'with_user:', data.with_user);
    ChatNotifications.show(`Chat accepted by ${data.with_user}`);
    
    const newChat = {
      chat_id: data.chat_id,
      with_user: data.with_user,
      with_user_id: data.with_user_id || 'unknown',
      online: true,
      last_message_at: new Date().toISOString()
    };
    
    // Remove from requests and add to active chats
    this.state.removeChatRequest(data.with_user);
    this.state.addOrUpdateActiveChat(newChat);
    
    // Update UI
    this.ui.renderChatRequests();
    this.ui.renderActiveChats();
  }

  handleChatDeclined(data) {
    console.log('Chat declined by:', data.by_user);
    ChatNotifications.show(`Chat request declined by ${data.by_user}`);
    
    this.state.removeChatRequest(data.by_user);
    this.ui.renderChatRequests();
  }

  handleRequestStatusChanged(data) {
    // Refresh data from server
    this.websocket.send({ type: WEBSOCKET_TYPES.GET_CHAT_REQUESTS });
    this.websocket.send({ type: WEBSOCKET_TYPES.GET_ACTIVE_CHATS });
  }

  handleNewMessage(data) {
    console.log('New message received for chat:', data.chat_id);
    
    const isForCurrentChat = this.state.isMessageForCurrentChat(data.chat_id);
    const isFromCurrentUser = this.state.isMessageFromCurrentUser(data.from_user_id);
    
    // Update current chat if it's the active chat
    if (isForCurrentChat) {
      const newMessage = {
        from_user_id: data.from_user_id,
        from_username: data.from_username,
        message: data.message,
        timestamp: data.timestamp ? new Date(data.timestamp * 1000).toISOString() : new Date().toISOString()
      };
      
      this.state.addMessageToCurrentChat(newMessage);
      this.ui.appendMessageToUI(newMessage);
    }
    
    // Update chat list
    const timestamp = new Date().toISOString();
    this.state.updateChatLastMessage(data.chat_id, timestamp);
    this.ui.renderActiveChats();
    
    // Show notification if not current chat and not from current user
    if (!isForCurrentChat && !isFromCurrentUser) {
      ChatNotifications.show(`New message from ${data.from_username}`);
      this.ui.addNotificationIndicator(data.chat_id);
    }
  }

  handleChatRequests(data) {
    console.log('Chat requests received:', data.requests.length, 'requests');
    this.state.setChatRequests(data.requests);
    this.ui.renderChatRequests();
  }

  handleActiveChats(data) {
    console.log('Active chats received:', data.chats.length, 'chats');
    this.state.setActiveChats(data.chats);
    this.ui.renderActiveChats();
  }

  handleChatMessages(data) {
    console.log('Chat messages received for chat:', data.chat_id, 'Messages:', data.messages.length);
    if (data.chat_id === this.state.currentChatId) {
      this.state.setCurrentChatMessages(data.messages);
      this.ui.renderChatMessages();
    }
  }

  handleError(data) {
    console.error('WebSocket server error:', data.message);
    ChatNotifications.show(`${data.message}`, 'error');
  }
}