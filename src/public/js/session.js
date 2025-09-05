import { CONFIG, CSS_CLASSES, createIcon } from './utils.js';
import { DOM } from './dom.js';
import { MessageHandler } from './notifications.js';
import { NotesDisplay } from './notes.js';

let isLoading = false;

/**
 * Session management with permanent login support
 */
export const SessionManager = {
  init() {
    this.displaySessionStatus();
    
    if (DOM.userInfo && window.JWT_EXP) {
      this.displaySessionInfo();
      if (!window.IS_PERMANENT) {
        this.scheduleWarning();
      }
    }
    
    this.initLogoutAllButton();
    
    if (!window.IS_PERMANENT) {
      setInterval(() => {
        if (!isLoading && document.visibilityState === 'visible') {
          NotesDisplay.load();
        }
      }, CONFIG.REFRESH_INTERVAL);
    }
  },

  displaySessionStatus() {
    const statusElement = document.getElementById('sessionStatus');
    if (!statusElement) return;
    
    const textElement = statusElement.querySelector('.text');
    
    if (window.IS_PERMANENT) {
      statusElement.classList.add('permanent');
      textElement.textContent = 'Permanent Session';
    } else {
      statusElement.classList.remove('permanent');
      textElement.textContent = 'Temporary Session (4h)';
    }
  },

  displaySessionInfo() {
    if (!window.IS_PERMANENT && window.JWT_EXP) {
      const sessionEnd = new Date(window.JWT_EXP * 1000);
      DOM.userInfo.className = CSS_CLASSES.userInfo;
      
      DOM.userInfo.innerHTML = '';
      const clockIcon = createIcon('clock');
      DOM.userInfo.appendChild(clockIcon);
      DOM.userInfo.appendChild(document.createTextNode(` Expires: ${sessionEnd.toLocaleTimeString()}`));
    } else if (window.IS_PERMANENT) {
      DOM.userInfo.className = CSS_CLASSES.userInfo;
      DOM.userInfo.innerHTML = '';
      const clockIcon = createIcon('clock');
      DOM.userInfo.appendChild(clockIcon);
      DOM.userInfo.appendChild(document.createTextNode(' No expiration'));
    }
  },

  initLogoutAllButton() {
    const logoutAllBtn = document.getElementById('logoutAllBtn');
    if (!logoutAllBtn) return;
    
    if (window.IS_PERMANENT) {
      logoutAllBtn.style.display = 'inline-block';
      logoutAllBtn.addEventListener('click', this.logoutAllTempSessions);
    }
  },

  async logoutAllTempSessions() {
    const btn = document.getElementById('logoutAllBtn');
    if (!confirm('This will logout all your temporary sessions on other devices. Continue?')) {
      return;
    }
    
    btn.disabled = true;
    btn.textContent = 'Logging out...';
    
    try {
      const response = await fetch('/api/logout_all_temp', { 
        method: 'POST' 
      });
      const data = await response.json();
      
      if (response.ok && data.success) {
        btn.textContent = 'Success!';
        setTimeout(() => {
          btn.textContent = 'Logout All Temp Sessions';
          btn.disabled = false;
        }, 2000);
        
        MessageHandler.show(document.getElementById('saveMsg'), 
          data.message || 'All temporary sessions logged out successfully!', 
          'success');
      } else {
        throw new Error(data.error || 'Failed to logout sessions');
      }
    } catch (error) {
      console.error('Error logging out sessions:', error);
      btn.textContent = 'Failed';
      setTimeout(() => {
        btn.textContent = 'Logout All Temp Sessions';
        btn.disabled = false;
      }, 2000);
      
      MessageHandler.show(document.getElementById('saveMsg'), 
        'Failed to logout sessions. Please try again.', 
        'error');
    }
  },

  scheduleWarning() {
    const now = Date.now();
    const timeUntilExpiry = window.JWT_EXP * 1000 - now;
    const warningTime = timeUntilExpiry - (30 * 60 * 1000);

    if (warningTime > 0) {
      setTimeout(() => {
        if (confirm('Your session will expire in 30 minutes. Click OK to extend your session.')) {
          location.reload();
        }
      }, warningTime);
    }
  }
};