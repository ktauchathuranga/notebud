/**
 * Logout functionality
 */
export const LogoutHandler = {
  init() {
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', this.logout);
    }
  },

  async logout() {
    const btn = document.getElementById('logoutBtn');
    btn.disabled = true;
    btn.textContent = 'Logging out...';

    try {
      await fetch('/api/logout', { method: 'POST' });
    } catch (error) {
      console.log('Logout request failed, but redirecting anyway');
    }

    location.href = '/login';
  }
};