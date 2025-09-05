import { CHAT_CONFIG, WEBSOCKET_TYPES, CONNECTION_STATUS, CHAT_CSS_CLASSES } from './config.js';
import { ChatNotifications } from './notifications.js';

/**
 * WebSocket connection manager for chat functionality
 */
export class ChatWebSocket {
  constructor() {
    this.socket = null;
    this.isConnected = false;
    this.reconnectAttempts = 0;
    this.messageHandlers = new Map();
  }

  /**
   * Get WebSocket URL based on environment
   */
  getWebSocketUrl() {
    if (window.WS_URL) return window.WS_URL;

    if (
      window.location.hostname === "localhost" ||
      window.location.hostname === "127.0.0.1"
    ) {
      return "ws://localhost:8092/ws";
    }

    return "wss://notebud-websocket.onrender.com/ws";
  }

  /**
   * Initialize WebSocket connection
   */
  connect() {
    const token = window.JWT_TOKEN;
    
    if (!token) {
      console.error('No JWT token available for WebSocket authentication');
      ChatNotifications.show('Authentication error. Please refresh the page.', 'error');
      return;
    }

    this.updateConnectionStatus(CONNECTION_STATUS.CONNECTING);
    const wsUrl = this.getWebSocketUrl();
    console.log('Attempting to connect to WebSocket server at ' + wsUrl);

    try {
      this.socket = new WebSocket(wsUrl);
      this.setupEventListeners(token);
    } catch (error) {
      console.error('Failed to create WebSocket connection:', error);
      this.updateConnectionStatus(CONNECTION_STATUS.DISCONNECTED);
      ChatNotifications.show('Failed to connect to chat server', 'error');
    }
  }

  /**
   * Setup WebSocket event listeners
   */
  setupEventListeners(token) {
    this.socket.onopen = () => {
      console.log('WebSocket connected successfully');
      this.reconnectAttempts = 0;
      this.updateConnectionStatus(CONNECTION_STATUS.CONNECTED);

      this.send({
        type: WEBSOCKET_TYPES.AUTH,
        token: token
      });
    };

    this.socket.onmessage = (event) => {
      console.log('WebSocket message received:', event.data);
      try {
        const data = JSON.parse(event.data);
        this.handleMessage(data);
      } catch (e) {
        console.error('Error parsing WebSocket message:', e, 'Raw data:', event.data);
      }
    };

    this.socket.onclose = (event) => {
      console.log('WebSocket disconnected. Code:', event.code, 'Reason:', event.reason);
      this.isConnected = false;
      this.updateConnectionStatus(CONNECTION_STATUS.DISCONNECTED);

      if (this.reconnectAttempts < CHAT_CONFIG.MAX_RECONNECT_ATTEMPTS) {
        this.reconnectAttempts++;
        console.log(`Reconnection attempt ${this.reconnectAttempts}/${CHAT_CONFIG.MAX_RECONNECT_ATTEMPTS} in 3 seconds...`);
        setTimeout(() => this.connect(), CHAT_CONFIG.RECONNECT_DELAY);
      } else {
        console.error('Max reconnection attempts reached');
        ChatNotifications.show('Unable to connect to chat server. Please refresh the page.', 'error');
      }
    };

    this.socket.onerror = (error) => {
      console.error('WebSocket error:', error);
      this.updateConnectionStatus(CONNECTION_STATUS.DISCONNECTED);
      ChatNotifications.show('Connection error. Check if the chat server is running.', 'error');
    };
  }

  /**
   * Handle incoming WebSocket messages
   */
  handleMessage(data) {
    const handler = this.messageHandlers.get(data.type);
    if (handler) {
      handler(data);
    } else {
      console.log('Unknown WebSocket message type:', data.type, 'Full data:', data);
    }
  }

  /**
   * Register message handler
   */
  onMessage(type, handler) {
    this.messageHandlers.set(type, handler);
  }

  /**
   * Send message through WebSocket
   */
  send(data) {
    if (this.socket && this.socket.readyState === WebSocket.OPEN) {
      this.socket.send(JSON.stringify(data));
      return true;
    }
    return false;
  }

  /**
   * Update connection status
   */
  updateConnectionStatus(status) {
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
        this.isConnected = true;
        if (sendRequestBtn) sendRequestBtn.disabled = false;
        if (messageInput) messageInput.disabled = false;
        if (sendMessageBtn) sendMessageBtn.disabled = false;
        break;
      case CONNECTION_STATUS.CONNECTING:
        statusElement.innerHTML = 'Connecting to chat server...';
        this.isConnected = false;
        if (sendRequestBtn) sendRequestBtn.disabled = true;
        if (messageInput) messageInput.disabled = true;
        if (sendMessageBtn) sendMessageBtn.disabled = true;
        break;
      case CONNECTION_STATUS.DISCONNECTED:
        if (this.reconnectAttempts < CHAT_CONFIG.MAX_RECONNECT_ATTEMPTS) {
          statusElement.innerHTML = `Reconnecting... (${this.reconnectAttempts}/${CHAT_CONFIG.MAX_RECONNECT_ATTEMPTS})`;
        } else {
          statusElement.innerHTML = 'Disconnected from chat server';
        }
        this.isConnected = false;
        if (sendRequestBtn) sendRequestBtn.disabled = true;
        if (messageInput) messageInput.disabled = true;
        if (sendMessageBtn) sendMessageBtn.disabled = true;
        break;
    }
  }

  /**
   * Close WebSocket connection
   */
  disconnect() {
    if (this.socket) {
      this.socket.close();
      this.socket = null;
    }
    this.isConnected = false;
  }
}
