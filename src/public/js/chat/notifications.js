import { CHAT_CONFIG } from './config.js';

/**
 * Chat notification system
 */
export const ChatNotifications = {
  /**
   * Show notification message
   */
  show(message, type = 'success') {
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
    }, CHAT_CONFIG.NOTIFICATION_DURATION);
  }
};