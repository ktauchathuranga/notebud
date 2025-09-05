import { CONFIG, CSS_CLASSES } from './utils.js';

/**
 * Message handling utility
 */
export const MessageHandler = {
  /**
   * Show a message
   * @param {HTMLElement} element - Element to show message in
   * @param {string} text - Message text
   * @param {string} type - Message type (error, success, warning)
   */
  show(element, text, type = 'error') {
    element.textContent = text;
    element.className = `msg ${type} ${CSS_CLASSES.msgShow}`;
    
    setTimeout(() => {
      element.classList.remove(CSS_CLASSES.msgShow);
    }, CONFIG.MESSAGE_HIDE_DELAY);
  },

  /**
   * Hide a message
   * @param {HTMLElement} element - Element to hide message from
   */
  hide(element) {
    element.classList.remove(CSS_CLASSES.msgShow);
  }
};

/**
 * Show a global notification message
 * @param {string} message - Notification message
 * @param {string} type - Notification type (info, success, error, warning)
 * @param {number} duration - Duration in milliseconds
 */
export function showNotification(message, type = 'info', duration = 5000) {
  // Remove existing messages
  const existingMessages = document.querySelectorAll('.notification');
  existingMessages.forEach(msg => msg.remove());
  
  // Create notification
  const notification = document.createElement('div');
  notification.className = `notification ${type}`;
  notification.textContent = message;
  
  // Add to page
  document.body.appendChild(notification);
  
  // Auto-remove after duration
  setTimeout(() => {
    if (notification.parentNode) {
      notification.remove();
    }
  }, duration);
}

// Add notification styles
const notificationStyles = `
.notification {
  position: fixed;
  top: 20px;
  right: 20px;
  background: var(--card);
  color: var(--text);
  padding: 1rem 1.5rem;
  border-radius: var(--radius);
  z-index: 1000;
  box-shadow: var(--shadow-lg);
  border-left: 4px solid var(--primary);
  min-width: 300px;
  max-width: 500px;
  animation: slideIn 0.3s ease;
  font-weight: 500;
  word-wrap: break-word;
}

.notification.success {
  border-left-color: var(--success);
  background: var(--success-light);
  color: var(--success);
}

.notification.error {
  border-left-color: var(--danger);
  background: var(--danger-light);
  color: var(--danger);
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

// Add styles if they don't exist
if (!document.querySelector('#notification-styles')) {
  const styleElement = document.createElement('style');
  styleElement.id = 'notification-styles';
  styleElement.textContent = notificationStyles;
  document.head.appendChild(styleElement);
}