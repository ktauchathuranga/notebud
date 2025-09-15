import { DOM } from './dom.js';
import { formatDate } from './utils.js';

/**
 * Note modal functionality with markdown rendering, toggle, fullscreen, and auto-hide footer
 */
export const NoteModal = {
  currentNote: null,
  isMarkdownView: true,
  isFullscreen: false,

  /**
   * Initialize modal
   */
  init() {
    DOM.closeModal.addEventListener('click', () => this.hide());
    
    // Initialize close button for share modal if it exists
    const closeShareModal = document.getElementById('closeShareModal');
    if (closeShareModal) {
      closeShareModal.addEventListener('click', () => this.hideShareModal());
    }
    
    // Add toggle functionality
    const toggleBtn = document.getElementById('toggleMarkdown');
    if (toggleBtn) {
      toggleBtn.addEventListener('click', () => this.toggleView());
    }

    // Add fullscreen functionality
    const fullscreenBtn = document.getElementById('fullscreenBtn');
    if (fullscreenBtn) {
      fullscreenBtn.addEventListener('click', () => this.toggleFullscreen());
    }
    
    // Close modal when clicking outside
    DOM.noteModal.addEventListener('click', (e) => {
      if (e.target === DOM.noteModal) {
        this.hide();
      }
    });

    // Close share modal when clicking outside
    const shareModal = DOM.shareModal;
    if (shareModal) {
      shareModal.addEventListener('click', (e) => {
        if (e.target === shareModal) {
          this.hideShareModal();
        }
      });
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        if (DOM.noteModal.classList.contains('show')) {
          this.hide();
        } else if (shareModal && shareModal.classList.contains('show')) {
          this.hideShareModal();
        }
      }
      
      // Toggle view with Ctrl/Cmd + M when modal is open
      if ((e.ctrlKey || e.metaKey) && e.key === 'm' && DOM.noteModal.classList.contains('show')) {
        e.preventDefault();
        this.toggleView();
      }

      // Toggle fullscreen with F11 when modal is open
      if (e.key === 'F11' && DOM.noteModal.classList.contains('show')) {
        e.preventDefault();
        this.toggleFullscreen();
      }
      
      // Show footer temporarily with 'h' key in fullscreen
      if (e.key === 'h' && DOM.noteModal.classList.contains('show') && this.isFullscreen) {
        e.preventDefault();
        this.showFooterTemporarily();
      }
    });
  },

  /**
   * Show note in modal with markdown rendering support
   * @param {Object} note - Note data
   */
  show(note) {
    this.currentNote = note;
    this.isMarkdownView = true;
    this.isFullscreen = false;
    
    DOM.modalTitle.textContent = note.title || 'Untitled Note';
    DOM.modalDate.textContent = `Created: ${formatDate(note.created_at)}`;
    
    // Update toggle button text
    const toggleBtn = document.getElementById('toggleMarkdown');
    if (toggleBtn) {
      toggleBtn.textContent = 'Raw';
      toggleBtn.title = 'Switch to raw markdown view (Ctrl+M)';
    }

    // Update fullscreen button
    const fullscreenBtn = document.getElementById('fullscreenBtn');
    if (fullscreenBtn) {
      this.updateFullscreenButton();
    }
    
    this.renderContent();
    
    DOM.noteModal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Focus the modal for keyboard navigation
    DOM.noteModal.focus();
  },

  /**
   * Toggle fullscreen mode with auto-hide footer
   */
  toggleFullscreen() {
    this.isFullscreen = !this.isFullscreen;
    
    if (this.isFullscreen) {
      DOM.noteModal.classList.add('fullscreen');
      
      // Add a subtle hint about the hidden footer
      this.showFooterHint();
      
    } else {
      DOM.noteModal.classList.remove('fullscreen');
      this.hideFooterHint();
    }
    
    this.updateFullscreenButton();
  },

  /**
   * Update fullscreen button appearance
   */
  updateFullscreenButton() {
    const fullscreenBtn = document.getElementById('fullscreenBtn');
    const fullscreenIcon = fullscreenBtn?.querySelector('.fullscreen-icon');
    const fullscreenText = fullscreenBtn?.querySelector('.fullscreen-text');
    
    if (fullscreenBtn && fullscreenIcon && fullscreenText) {
      if (this.isFullscreen) {
        fullscreenIcon.textContent = '⛷'; // Exit fullscreen icon
        fullscreenText.textContent = 'Exit Fullscreen';
        fullscreenBtn.title = 'Exit fullscreen (F11)';
      } else {
        fullscreenIcon.textContent = '⛶'; // Fullscreen icon
        fullscreenText.textContent = 'Fullscreen';
        fullscreenBtn.title = 'Toggle fullscreen (F11)';
      }
    }
  },

  /**
   * Show a temporary hint about the hidden footer
   */
  showFooterHint() {
    // Remove any existing hint first
    this.hideFooterHint();
    
    // Create a temporary hint
    const hint = document.createElement('div');
    hint.className = 'footer-hint';
    hint.innerHTML = 'Footer auto-hidden • Hover bottom to show controls • Press H to show temporarily';
    hint.style.cssText = `
      position: fixed;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: var(--primary, #007bff);
      color: white;
      padding: 0.6rem 1.2rem;
      border-radius: var(--radius, 6px);
      font-size: 0.85rem;
      font-weight: 500;
      z-index: 1003;
      opacity: 0;
      transition: opacity 0.3s ease;
      pointer-events: none;
      max-width: 90vw;
      text-align: center;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    `;
    
    document.body.appendChild(hint);
    
    // Animate in
    setTimeout(() => {
      hint.style.opacity = '0.95';
    }, 100);
    
    // Auto remove after 4 seconds
    setTimeout(() => {
      if (hint && hint.parentNode) {
        hint.style.opacity = '0';
        setTimeout(() => {
          if (hint && hint.parentNode) {
            hint.parentNode.removeChild(hint);
          }
        }, 300);
      }
    }, 4000);
  },

  /**
   * Hide footer hint
   */
  hideFooterHint() {
    const hints = document.querySelectorAll('.footer-hint');
    hints.forEach(hint => {
      hint.style.opacity = '0';
      setTimeout(() => {
        if (hint && hint.parentNode) {
          hint.parentNode.removeChild(hint);
        }
      }, 300);
    });
  },

  /**
   * Show footer temporarily (for 4 seconds)
   */
  showFooterTemporarily() {
    if (!this.isFullscreen) return;
    
    const footer = DOM.noteModal.querySelector('.modal-footer');
    if (footer) {
      // Force show footer
      footer.style.opacity = '0.95';
      footer.style.transform = 'translateY(0)';
      footer.style.pointerEvents = 'all';
      footer.style.transition = 'all 0.3s ease';
      
      // Hide after 4 seconds if still in fullscreen
      setTimeout(() => {
        if (this.isFullscreen && footer) {
          footer.style.opacity = '0';
          footer.style.transform = 'translateY(100%)';
          footer.style.pointerEvents = 'none';
        }
      }, 4000);
    }
  },

  /**
   * Toggle between markdown rendered view and raw text view
   */
  toggleView() {
    if (!this.currentNote) return;
    
    this.isMarkdownView = !this.isMarkdownView;
    this.renderContent();
    
    const toggleBtn = document.getElementById('toggleMarkdown');
    if (toggleBtn) {
      if (this.isMarkdownView) {
        toggleBtn.textContent = 'Raw';
        toggleBtn.title = 'Switch to raw markdown view (Ctrl+M)';
      } else {
        toggleBtn.textContent = 'Rendered';
        toggleBtn.title = 'Switch to rendered markdown view (Ctrl+M)';
      }
    }
    
    // Add a subtle animation
    const modalContent = document.getElementById('modalContent');
    if (modalContent) {
      modalContent.style.opacity = '0.5';
      setTimeout(() => {
        modalContent.style.opacity = '1';
      }, 100);
    }
  },

  /**
   * Render the note content based on current view mode
   */
  renderContent() {
    if (!this.currentNote) return;
    
    const content = this.currentNote.content || '';
    const modalContent = document.getElementById('modalContent');
    if (!modalContent) return;
    
    if (this.isMarkdownView && typeof marked !== 'undefined' && content.trim()) {
      try {
        // Configure marked for better rendering
        if (marked.setOptions) {
          marked.setOptions({
            breaks: true,          // Convert \n to <br>
            gfm: true,            // GitHub Flavored Markdown
            headerIds: false,     // Don't add IDs to headers
            mangle: false,        // Don't mangle autolinks
            sanitize: false,      // We trust our own content
            smartLists: true,     // Use smarter list behavior
            smartypants: false,   // Don't use smart quotes
            xhtml: false          // Don't use XHTML syntax
          });
        }
        
        // Parse markdown to HTML
        const htmlContent = typeof marked.parse === 'function' 
          ? marked.parse(content) 
          : marked(content);
          
        modalContent.innerHTML = htmlContent;
        modalContent.className = 'modal-content-display modal-content-markdown';
        
        // Handle task list checkboxes (make them interactive if needed)
        this.handleTaskLists();
        
      } catch (error) {
        console.error('Error parsing markdown:', error);
        // Fallback to raw text on error
        this.showRawContent(content);
      }
    } else {
      this.showRawContent(content);
    }
  },

  /**
   * Show raw content (fallback or when raw view is selected)
   * @param {string} content - The raw content to display
   */
  showRawContent(content) {
    const modalContent = document.getElementById('modalContent');
    if (modalContent) {
      modalContent.textContent = content;
      modalContent.className = 'modal-content-display modal-content-raw';
    }
  },

  /**
   * Handle interactive task lists (optional feature)
   */
  handleTaskLists() {
    const modalContent = document.getElementById('modalContent');
    if (!modalContent) return;
    
    const checkboxes = modalContent.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
      // Make checkboxes read-only in the modal (optional)
      checkbox.disabled = true;
      
      // Add some styling to disabled checkboxes
      checkbox.style.cursor = 'not-allowed';
      checkbox.style.opacity = '0.7';
      
      // Or make them interactive (uncomment if you want editable task lists)
      /*
      checkbox.disabled = false;
      checkbox.addEventListener('change', (e) => {
        // You could implement task list editing here
        console.log('Task list item toggled:', e.target.checked);
        // Update the note content and save to backend
        this.updateTaskList(e.target);
      });
      */
    });
  },

  /**
   * Update task list in the note content (optional feature)
   * @param {HTMLInputElement} checkbox - The checkbox that was toggled
   */
  updateTaskList(checkbox) {
    // This is a placeholder for task list editing functionality
    // You would implement this to update the note content and save it
    console.log('Task list update would be implemented here');
  },

  /**
   * Hide the note modal
   */
  hide() {
    DOM.noteModal.classList.remove('show', 'fullscreen');
    document.body.style.overflow = '';
    this.currentNote = null;
    this.isMarkdownView = true;
    this.isFullscreen = false;
    
    // Clean up any footer hints
    this.hideFooterHint();
    
    // Reset content opacity
    const modalContent = document.getElementById('modalContent');
    if (modalContent) {
      modalContent.style.opacity = '';
    }
  },

  /**
   * Show share modal
   * @param {string} noteId - ID of the note to share
   */
  showShareModal(noteId) {
    if (DOM.shareNoteId) {
      DOM.shareNoteId.value = noteId;
    }
    
    if (DOM.shareUsername) {
      DOM.shareUsername.value = '';
      DOM.shareUsername.focus();
    }
    
    if (DOM.shareModal) {
      DOM.shareModal.classList.add('show');
      document.body.style.overflow = 'hidden';
    }
  },

  /**
   * Hide share modal
   */
  hideShareModal() {
    if (DOM.shareModal) {
      DOM.shareModal.classList.remove('show');
      document.body.style.overflow = '';
    }
    
    // Reset form
    if (DOM.shareForm) {
      DOM.shareForm.reset();
    }
  },

  /**
   * Check if markdown library is available
   * @returns {boolean} True if marked library is loaded
   */
  isMarkdownSupported() {
    return typeof marked !== 'undefined';
  },

  /**
   * Get current view mode
   * @returns {string} Current view mode ('markdown' or 'raw')
   */
  getCurrentViewMode() {
    return this.isMarkdownView ? 'markdown' : 'raw';
  },

  /**
   * Set view mode programmatically
   * @param {string} mode - 'markdown' or 'raw'
   */
  setViewMode(mode) {
    const newMode = mode === 'markdown';
    if (newMode !== this.isMarkdownView) {
      this.toggleView();
    }
  },

  /**
   * Check if modal is in fullscreen mode
   * @returns {boolean} True if in fullscreen mode
   */
  isInFullscreen() {
    return this.isFullscreen;
  },

  /**
   * Get current note
   * @returns {Object|null} Current note object or null
   */
  getCurrentNote() {
    return this.currentNote;
  },

  /**
   * Force refresh content rendering
   */
  refreshContent() {
    if (this.currentNote) {
      this.renderContent();
    }
  },

  /**
   * Set focus to modal content for better keyboard navigation
   */
  focusContent() {
    const modalContent = document.getElementById('modalContent');
    if (modalContent) {
      modalContent.focus();
    }
  },

  /**
   * Check if modal is currently visible
   * @returns {boolean} True if modal is visible
   */
  isVisible() {
    return DOM.noteModal && DOM.noteModal.classList.contains('show');
  }
};

// Export for use in other modules
export default NoteModal;
