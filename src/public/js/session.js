import { showNotification } from './notifications.js';

let sessionTimer;

/**
 * Session management functionality
 */
export const SessionManager = {
  /**
   * Initialize session management
   */
  init() {
    this.setupLogout();
    
    if (window.IS_PERMANENT) {
      this.setupPermanentSessionControls();
    } else {
      this.startSessionTimer();
    }
    
    this.displayUserInfo();
  },
  
  /**
   * Set up logout button
   */
  setupLogout() {
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', this.logout);
    }
  },
  
  /**
   * Set up controls specific to permanent sessions
   */
  setupPermanentSessionControls() {
    const logoutAllBtn = document.getElementById('logoutAllBtn');
    if (logoutAllBtn) {
      logoutAllBtn.style.display = 'inline-flex';
      logoutAllBtn.addEventListener('click', this.logoutAllTempSessions);
    }
  },

  /**
   * Start the session timer for temporary sessions
   */
  startSessionTimer() {
    if (window.JWT_EXP) {
      const expiresIn = (window.JWT_EXP * 1000) - Date.now();
      
      if (expiresIn > 0) {
        sessionTimer = setTimeout(() => {
          alert('Your session has expired. You will be logged out.');
          this.logout();
        }, expiresIn);
      } else {
        this.logout();
      }
    }
  },
  
  /**
   * Display user info
   */
  displayUserInfo() {
    const userInfoDiv = document.getElementById('userInfo');
    if (userInfoDiv && window.USERNAME) {
      userInfoDiv.textContent = `👤 ${window.USERNAME}`;
    }
  },
  
  /**
   * Logout the current user
   */
  async logout() {
    try {
      const response = await fetch('/api/logout', { method: 'POST' });
      const data = await response.json();
      
      if (data.success) {
        window.location.href = '/login';
      } else {
        console.error('Logout failed:', data.error);
        // Fallback logout
        document.cookie = "jwt=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        window.location.href = '/login';
      }
    } catch (error) {
      console.error('Error during logout:', error);
      // Fallback logout
      document.cookie = "jwt=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
      window.location.href = '/login';
    }
  },
  
  /**
   * Logout all temporary sessions for the current user
   */
  async logoutAllTempSessions() {
    if (!confirm('Are you sure you want to log out all other temporary sessions?')) {
      return;
    }

    const btn = document.getElementById('logoutAllBtn');
    if(btn) {
        btn.disabled = true;
        btn.textContent = 'Logging out...';
    }

    try {
      const response = await fetch('/api/logout_all_temp', {
        method: 'POST'
      });
      const result = await response.json();
      
      if (result.success) {
        showNotification(result.message || 'All temporary sessions have been logged out.', 'success');
      } else {
        showNotification(result.error || 'Failed to log out sessions.', 'error');
      }
    } catch (error) {
      console.error('Error logging out sessions:', error);
      showNotification('A network error occurred. Please try again.', 'error');
    } finally {
        if(btn) {
            btn.disabled = false;
            btn.textContent = 'Logout All Temp Sessions';
        }
    }
  }
};
