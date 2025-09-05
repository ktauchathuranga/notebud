import { ChatApplication } from './chat/app.js';
import { ChatUtils } from './chat/utils.js';

// Initialize chat application
let chatApp;

document.addEventListener('DOMContentLoaded', () => {
  chatApp = new ChatApplication();
  chatApp.init();
  
  // Make chatApp globally available for onclick handlers
  window.chatApp = chatApp;
  
  // Make utils globally available for compatibility
  window.getUserInitials = ChatUtils.getUserInitials;
  window.getAvatarColor = ChatUtils.getAvatarColor;
});

// Global functions for backward compatibility
window.sendChatRequest = () => chatApp?.sendChatRequest();
window.sendMessage = () => chatApp?.sendMessage();
window.openChat = (chatId, withUser, isOnline) => chatApp?.openChat(chatId, withUser, isOnline);
window.acceptChatRequest = (fromUserId) => chatApp?.acceptChatRequest(fromUserId);
window.declineChatRequest = (fromUserId) => chatApp?.declineChatRequest(fromUserId);