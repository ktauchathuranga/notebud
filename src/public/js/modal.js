import { DOM } from './dom.js';
import { formatDate } from './utils.js';

/**
 * Note modal functionality
 */
export const NoteModal = {
  /**
   * Initialize modal
   */
  init() {
    DOM.closeModal.addEventListener('click', () => this.hide());
    
    DOM.noteModal.addEventListener('click', (e) => {
      if (e.target === DOM.noteModal) {
        this.hide();
      }
    });
    
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && DOM.noteModal.classList.contains('show')) {
        this.hide();
      }
    });
  },

  /**
   * Show note in modal
   * @param {Object} note - Note data
   */
  show(note) {
    DOM.modalTitle.textContent = note.title || 'Untitled Note';
    DOM.modalContent.textContent = note.content || '';
    DOM.modalDate.textContent = `Created: ${formatDate(note.created_at)}`;
    
    DOM.noteModal.classList.add('show');
    document.body.style.overflow = 'hidden';
  },

  /**
   * Hide modal
   */
  hide() {
    DOM.noteModal.classList.remove('show');
    document.body.style.overflow = '';
  }
};