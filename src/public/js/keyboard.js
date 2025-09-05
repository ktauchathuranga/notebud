import { NoteEditor } from './notes.js';
import { DOM } from './dom.js';

/**
 * Keyboard shortcuts
 */
export const KeyboardShortcuts = {
  init() {
    document.addEventListener('keydown', (e) => {
      // Ctrl/Cmd + S to save
      if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        NoteEditor.save();
      }
      
      // Ctrl/Cmd + N for new note
      if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        NoteEditor.reset();
        DOM.contentInput.focus();
      }
    });
  }
};