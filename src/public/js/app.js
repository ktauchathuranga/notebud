import { NotesDisplay, NoteEditor, CharacterCounter } from './notes.js';
import { NoteModal } from './modal.js';
import { SessionManager } from './session.js';
import { AuthHandler, FormValidation } from './auth.js';
import { LogoutHandler } from './logout.js';
import { ShareHandler } from './sharing.js';
import { FileManager } from './files.js';
import { KeyboardShortcuts } from './keyboard.js';
import { HeaderIcons } from './ui.js';
import { DOM } from './dom.js';

/**
 * Application initialization
 */
export const App = {
  /**
   * Initialize the application
   */
  init() {
    HeaderIcons.init();
    
    if (DOM.notesContainer) {
      this.initNotesPage();
    } else {
      this.initAuthPage();
    }
  },

  /**
   * Initialize notes page
   */
  initNotesPage() {
    NoteEditor.init();
    CharacterCounter.init();
    KeyboardShortcuts.init();
    SessionManager.init();
    LogoutHandler.init();
    NoteModal.init();
    ShareHandler.init();
    FileManager.init();
    
    NotesDisplay.load();
    DOM.contentInput.focus();
  },

  /**
   * Initialize auth pages
   */
  initAuthPage() {
    FormValidation.init();
    AuthHandler.init();
    
    const firstInput = document.querySelector('input');
    if (firstInput) firstInput.focus();
  }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  App.init();
});