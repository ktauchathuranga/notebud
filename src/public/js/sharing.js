import { CONFIG } from './utils.js';
import { DOM } from './dom.js';
import { MessageHandler } from './notifications.js';
import { formatDate, createElement, CSS_CLASSES } from './utils.js';
import { NotesDisplay } from './notes.js';

/**
 * Share Handler functionality
 */
export const ShareHandler = {
  init() {
    if (DOM.shareModal) {
      DOM.shareForm.addEventListener('submit', this.handleShare.bind(this));
      document.getElementById('closeShareModal').addEventListener('click', this.closeShareModal.bind(this));
      DOM.shareModal.addEventListener('click', (e) => {
        if (e.target === DOM.shareModal) this.closeShareModal();
      });
    }
    
    this.loadShareRequests();
    
    setInterval(() => {
      this.loadShareRequests();
    }, CONFIG.SHARE_REFRESH_INTERVAL);
  },

  openShareModal(note) {
    DOM.shareNoteId.value = note.id;
    DOM.shareUsername.value = '';
    DOM.shareModal.classList.add('show');
    DOM.shareUsername.focus();
  },

  closeShareModal() {
    DOM.shareModal.classList.remove('show');
  },

  async handleShare(e) {
    e.preventDefault();
    
    const noteId = DOM.shareNoteId.value;
    const username = DOM.shareUsername.value.trim();
    
    if (!username) {
      MessageHandler.show(DOM.saveMsg, 'Please enter a username', 'error');
      return;
    }
    
    const shareBtn = document.getElementById('shareBtn');
    shareBtn.disabled = true;
    shareBtn.textContent = 'Sharing...';
    
    try {
      const formData = new URLSearchParams();
      formData.append('note_id', noteId);
      formData.append('username', username);
      
      const response = await fetch('/api/share_note', {
        method: 'POST',
        body: formData
      });
      
      const data = await response.json();
      
      if (response.ok && data.success) {
        MessageHandler.show(DOM.saveMsg, data.message || 'Note shared successfully!', 'success');
      } else {
        const errorMessage = data.error && data.error.includes('already') 
          ? 'This note is already shared with this user.'
          : data.error || 'Failed to share note';
        MessageHandler.show(DOM.saveMsg, errorMessage, 'error');
      }
    } catch (error) {
      MessageHandler.show(DOM.saveMsg, 'Network error. Please try again.', 'error');
    } finally {
      shareBtn.disabled = false;
      shareBtn.textContent = 'Share Note';
      this.closeShareModal();
    }
  },

  async loadShareRequests() {
    try {
      const response = await fetch('/api/get_share_requests');
      const data = await response.json();
      
      if (response.ok) {
        this.renderShareRequests(data.requests);
      } else {
        throw new Error(data.error || 'Failed to load share requests');
      }
    } catch (error) {
      console.error('Error loading share requests:', error);
    }
  },

  renderShareRequests(requests) {
    const container = DOM.shareRequestsContainer;
    
    if (requests.length === 0) {
      DOM.shareRequestsSection.style.display = 'none';
      return;
    }
    
    DOM.shareRequestsSection.style.display = 'block';
    container.innerHTML = '';
    
    requests.forEach(request => {
      const requestEl = createElement('div', CSS_CLASSES.shareRequestItem);
      
      const message = createElement('p', '', 
        `${request.from_username} wants to share a note with you`);
      
      const date = createElement('small', '', 
        `Received: ${formatDate(request.created_at)}`);
      
      const actions = createElement('div', CSS_CLASSES.noteControls);
      
      const acceptBtn = document.createElement('button');
      acceptBtn.className = 'success';
      acceptBtn.textContent = 'Accept';
      acceptBtn.addEventListener('click', () => {
        this.acceptShare(request.id);
      });
      
      const rejectBtn = document.createElement('button');
      rejectBtn.className = CSS_CLASSES.buttonDanger;
      rejectBtn.textContent = 'Reject';
      rejectBtn.addEventListener('click', () => {
        this.rejectShare(request.id);
      });
      
      actions.appendChild(acceptBtn);
      actions.appendChild(rejectBtn);
      
      requestEl.appendChild(message);
      requestEl.appendChild(date);
      requestEl.appendChild(actions);
      
      container.appendChild(requestEl);
    });
  },

  async acceptShare(requestId) {
    try {
      const formData = new URLSearchParams();
      formData.append('request_id', requestId);
      
      const response = await fetch('/api/accept_share', {
        method: 'POST',
        body: formData
      });
      
      const data = await response.json();
      
      if (response.ok && data.success) {
        MessageHandler.show(DOM.saveMsg, data.message, 'success');
        this.loadShareRequests();
        NotesDisplay.load();
      } else {
        throw new Error(data.error || 'Failed to accept share');
      }
    } catch (error) {
      MessageHandler.show(DOM.saveMsg, error.message, 'error');
    }
  },

  async rejectShare(requestId) {
    try {
      const formData = new URLSearchParams();
      formData.append('request_id', requestId);
      
      const response = await fetch('/api/reject_share', {
        method: 'POST',
        body: formData
      });
      
      const data = await response.json();
      
      if (response.ok && data.success) {
        MessageHandler.show(DOM.saveMsg, data.message, 'success');
        this.loadShareRequests();
      } else {
        throw new Error(data.error || 'Failed to reject share');
      }
    } catch (error) {
      MessageHandler.show(DOM.saveMsg, error.message, 'error');
    }
  }
};