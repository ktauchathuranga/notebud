import { CONFIG } from './utils.js';
import { DOM } from './dom.js';
import { showNotification } from './notifications.js';
import { formatDate, createElement, CSS_CLASSES } from './utils.js';
import { NotesDisplay } from './notes.js';

let noteRequests = [];

/**
 * Share Handler functionality
 */
export const ShareHandler = {
  init() {
    // Initialize main share modal
    if (DOM.shareModal) {
      DOM.shareForm.addEventListener('submit', this.handleShareNote.bind(this));
      document.getElementById('closeShareModal').addEventListener('click', this.closeShareModal.bind(this));
      DOM.shareModal.addEventListener('click', (e) => {
        if (e.target === DOM.shareModal) this.closeShareModal();
      });
    }

    // Initialize requests popup
    if (DOM.requestsBtn) {
        DOM.requestsBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            DOM.requestsPopup.classList.toggle('show');
        });

        // Close popup if clicking outside
        document.addEventListener('click', (e) => {
            if (!DOM.requestsPopup.contains(e.target) && !DOM.requestsBtn.contains(e.target)) {
                DOM.requestsPopup.classList.remove('show');
            }
        });
    }
    
    // Load note requests periodically
    this.loadShareRequests();
    setInterval(() => this.loadShareRequests(), CONFIG.SHARE_REFRESH_INTERVAL);
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

  async handleShareNote(e) {
    e.preventDefault();
    const noteId = DOM.shareNoteId.value;
    const username = DOM.shareUsername.value.trim();
    if (!username) {
      showNotification('Please enter a username', 'error');
      return;
    }
    
    const shareBtn = document.getElementById('shareBtn');
    shareBtn.disabled = true;
    shareBtn.textContent = 'Sharing...';
    
    try {
      const formData = new URLSearchParams({ note_id: noteId, username });
      const response = await fetch('/api/share_note', { method: 'POST', body: formData });
      const data = await response.json();
      
      if (response.ok && data.success) {
        showNotification(data.message || 'Note shared successfully!', 'success');
      } else {
        const errorMessage = data.error?.includes('already') ? 'This note is already shared with this user.' : data.error || 'Failed to share note';
        showNotification(errorMessage, 'error');
      }
    } catch (error) {
      showNotification('Network error. Please try again.', 'error');
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
      noteRequests = response.ok ? (data.requests || []) : [];
    } catch (error) {
      console.error('Error loading note share requests:', error);
      noteRequests = [];
    } finally {
        this.renderShareRequests();
    }
  },

  renderShareRequests() {
    const container = DOM.noteShareRequestsContainer;
    if (!container) return;
    container.innerHTML = '';
    
    noteRequests.forEach(request => {
      const requestEl = createElement('div', CSS_CLASSES.shareRequestItem);
      requestEl.innerHTML = `
        <p>📝 <strong>${request.from_username}</strong> wants to share a note</p>
        <small>Received: ${formatDate(request.created_at)}</small>
      `;
      
      const actions = createElement('div', CSS_CLASSES.noteControls);
      const acceptBtn = createElement('button', 'success', 'Accept');
      acceptBtn.addEventListener('click', () => this.acceptShare(request.id));
      
      const rejectBtn = createElement('button', CSS_CLASSES.buttonDanger, 'Reject');
      rejectBtn.addEventListener('click', () => this.rejectShare(request.id));
      
      actions.appendChild(acceptBtn);
      actions.appendChild(rejectBtn);
      requestEl.appendChild(actions);
      container.appendChild(requestEl);
    });

    this.updateRequestsIndicator();
  },

  updateRequestsIndicator() {
    const noteRequestCount = noteRequests.length;
    const fileRequestCount = DOM.fileShareRequestsContainer?.childElementCount || 0;
    const totalRequests = noteRequestCount + fileRequestCount;

    if (DOM.requestsIndicator) {
      DOM.requestsIndicator.style.display = totalRequests > 0 ? 'block' : 'none';
      DOM.requestsIndicator.textContent = totalRequests;
    }
    if (DOM.noRequestsMessage) {
        DOM.noRequestsMessage.style.display = totalRequests === 0 ? 'block' : 'none';
    }
  },

  async handleShareResponse(action, requestId) {
    try {
      const response = await fetch(`/api/${action}_share`, {
        method: 'POST',
        body: new URLSearchParams({ request_id: requestId })
      });
      const data = await response.json();
      
      if (response.ok && data.success) {
        showNotification(data.message, 'success');
        this.loadShareRequests();
        if (action === 'accept') NotesDisplay.load();
      } else {
        throw new Error(data.error || `Failed to ${action} share`);
      }
    } catch (error) {
      showNotification(error.message, 'error');
    }
  },

  acceptShare(requestId) {
    this.handleShareResponse('accept', requestId);
  },

  rejectShare(requestId) {
    this.handleShareResponse('reject', requestId);
  }
};
