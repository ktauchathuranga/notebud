import { NotesAPI } from './api.js';
import { DOM } from './dom.js';
import { MessageHandler, showNotification } from './notifications.js';
import { formatDate, truncateText, createElement, createIcon, CSS_CLASSES, CONFIG } from './utils.js';
import { NoteModal } from './modal.js';
import { ShareHandler } from './sharing.js';

let notes = [];
let isLoading = false;

/**
 * Notes display and management
 */
export const NotesDisplay = {
  /**
   * Load and display notes
   */
  async load() {
    try {
      notes = await NotesAPI.fetch();
      this.render();
    } catch (error) {
      this.showError('Failed to load notes. Please refresh the page.');
    }
  },

  /**
   * Render notes list
   */
  render() {
    const container = DOM.notesContainer;
    
    if (notes.length === 0) {
      this.showEmpty();
      return;
    }
    
    container.innerHTML = '';
    
    // Sort notes by creation date (newest first)
    const sortedNotes = [...notes].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    
    sortedNotes.forEach((note, index) => {
      const noteEl = this.createNoteElement(note, index);
      container.appendChild(noteEl);
    });

    // Add search functionality if needed
    if (notes.length > CONFIG.SEARCH_THRESHOLD) {
      SearchHandler.add();
    }
  },

  /**
   * Create a note element
   * @param {Object} note - Note data
   * @param {number} index - Note index for animation
   * @returns {HTMLElement} Note element
   */
  createNoteElement(note, index) {
    const noteEl = createElement('div', CSS_CLASSES.note);
    noteEl.style.animationDelay = `${index * 0.1}s`;
    
    // Add shared note indicator
    if (note.is_shared) {
      noteEl.classList.add(CSS_CLASSES.sharedNote);
      const sharedBy = createElement('div', CSS_CLASSES.sharedBy, `Shared by: ${note.shared_by}`);
      noteEl.appendChild(sharedBy);
    }
    
    // Add click event to show full note in modal
    noteEl.addEventListener('click', () => {
      NoteModal.show(note);
    });
    
    // Title
    const title = createElement('h3', '', note.title || 'Untitled Note');
    
    // Meta info
    const meta = createElement('small', '', formatDate(note.created_at));
    
    // Content
    const content = createElement('p', '', truncateText(note.content || ''));
    
    // Controls
    const controls = this.createNoteControls(note, noteEl);
    
    noteEl.appendChild(title);
    noteEl.appendChild(meta);
    noteEl.appendChild(content);
    noteEl.appendChild(controls);
    
    return noteEl;
  },

  /**
   * Create note control buttons
   * @param {Object} note - Note data
   * @param {HTMLElement} noteElement - Note DOM element
   * @returns {HTMLElement} Controls element
   */
  createNoteControls(note, noteElement) {
    const controls = createElement('div', CSS_CLASSES.noteControls);
    
    // Edit button
    const editBtn = document.createElement('button');
    editBtn.className = CSS_CLASSES.buttonWarning;
    editBtn.appendChild(createIcon('note'));
    editBtn.appendChild(document.createTextNode(' Edit'));
    editBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      NoteEditor.edit(note);
    });
    
    // Share button
    const shareBtn = document.createElement('button');
    shareBtn.className = 'secondary';
    shareBtn.appendChild(createIcon('share'));
    shareBtn.appendChild(document.createTextNode(' Share'));
    shareBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      ShareHandler.openShareModal(note);
    });
    
    // Delete button
    const deleteBtn = document.createElement('button');
    deleteBtn.className = CSS_CLASSES.buttonDanger;
    deleteBtn.appendChild(createIcon('trash-simple'));
    deleteBtn.appendChild(document.createTextNode(' Delete'));
    deleteBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      this.delete(note.id, noteElement);
    });
    
    controls.appendChild(editBtn);
    controls.appendChild(shareBtn);
    controls.appendChild(deleteBtn);
    
    return controls;
  },

  /**
   * Show empty state
   */
  showEmpty() {
    DOM.notesContainer.innerHTML = `
      <div class="${CSS_CLASSES.emptyState}">
        <div>No notes yet. Create your first note!</div>
      </div>
    `;
  },

  /**
   * Show error state
   * @param {string} message - Error message
   */
  showError(message) {
    DOM.notesContainer.innerHTML = `
      <div class="${CSS_CLASSES.emptyState} ${CSS_CLASSES.errorState}">
        <div>${message}</div>
      </div>
    `;
  },

  /**
   * Delete a note
   * @param {string} noteId - Note ID
   * @param {HTMLElement} noteElement - Note DOM element
   */
  async delete(noteId, noteElement) {
    if (!confirm('Are you sure you want to delete this note?')) {
      return;
    }
    
    noteElement.classList.add(CSS_CLASSES.deleting);
    
    try {
      const success = await NotesAPI.delete(noteId);
      
      if (success) {
        noteElement.classList.add(CSS_CLASSES.slideOut);
        setTimeout(() => {
          this.load();
        }, 300);
      } else {
        throw new Error('Delete failed');
      }
    } catch (error) {
      console.error('Error deleting note:', error);
      noteElement.classList.remove(CSS_CLASSES.deleting);
      alert('Failed to delete note. Please try again.');
    }
  }
};

/**
 * Note editor functionality
 */
export const NoteEditor = {
  autoSaveTimeout: null,

  /**
   * Initialize the editor
   */
  init() {
    DOM.saveBtn.addEventListener('click', () => this.save());
    DOM.contentInput.addEventListener('input', () => {
      CharacterCounter.update();
      AutoResize.adjust();
      this.scheduleAutoSave();
    });
    DOM.titleInput.addEventListener('input', () => this.scheduleAutoSave());
  },

  /**
   * Edit an existing note
   * @param {Object} note - Note data
   */
  edit(note) {
    DOM.titleInput.value = note.title || '';
    DOM.contentInput.value = note.content || '';
    
    // Scroll to editor
    DOM.editor.scrollIntoView({ behavior: 'smooth' });
    
    // Focus on title field
    DOM.titleInput.focus();
    
    // Show edit indicator
    DOM.saveBtn.innerHTML = '';
    DOM.saveBtn.appendChild(createIcon('floppy-disk'));
    DOM.saveBtn.appendChild(document.createTextNode(' Update Note'));
    DOM.saveBtn.dataset.editingId = note.id;
  },

  /**
   * Save note (create or update)
   */
  async save() {
    const title = DOM.titleInput.value.trim();
    const content = DOM.contentInput.value.trim();
    
    if (!content) {
      MessageHandler.show(DOM.saveMsg, 'Please enter some content for your note.', 'error');
      DOM.contentInput.focus();
      return;
    }
    
    // Show loading state
    DOM.saveBtn.disabled = true;
    const isEditing = DOM.saveBtn.dataset.editingId;
    DOM.saveBtn.textContent = isEditing ? 'Updating...' : 'Saving...';
    
    try {
      let result;
      if (isEditing) {
        result = await NotesAPI.update(DOM.saveBtn.dataset.editingId, title, content);
      } else {
        result = await NotesAPI.save(title, content);
      }
      
      if (result.success) {
        this.reset();
        MessageHandler.show(DOM.saveMsg, 
          isEditing ? 'Note updated successfully!' : 'Note saved successfully!', 
          'success');
        
        NotesDisplay.load();
        
        setTimeout(() => DOM.contentInput.focus(), 100);
      } else {
        throw new Error(result.error || 'Save failed');
      }
    } catch (error) {
      console.error('Error saving note:', error);
      MessageHandler.show(DOM.saveMsg, error.message || 'Failed to save note. Please try again.', 'error');
    } finally {
      DOM.saveBtn.disabled = false;
      if (!DOM.saveBtn.innerHTML.includes('Save Note')) {
        this.resetSaveButton();
      }
    }
  },

  /**
   * Reset the editor
   */
  reset() {
    DOM.titleInput.value = '';
    DOM.contentInput.value = '';
    this.resetSaveButton();
    delete DOM.saveBtn.dataset.editingId;
    CharacterCounter.update();
  },

  /**
   * Reset save button to default state
   */
  resetSaveButton() {
    DOM.saveBtn.innerHTML = '';
    DOM.saveBtn.appendChild(createIcon('floppy-disk'));
    DOM.saveBtn.appendChild(document.createTextNode(' Save Note'));
  },

  /**
   * Schedule auto-save
   */
  scheduleAutoSave() {
    clearTimeout(this.autoSaveTimeout);
    this.autoSaveTimeout = setTimeout(() => {
      const content = DOM.contentInput.value.trim();
      if (content) {
        this.save();
      }
    }, CONFIG.AUTO_SAVE_DELAY);
  }
};

/**
 * Character counter functionality
 */
export const CharacterCounter = {
  element: null,

  /**
   * Initialize character counter
   */
  init() {
    this.element = createElement('div', CSS_CLASSES.charCounter);
    DOM.contentInput.parentNode.insertBefore(this.element, DOM.contentInput.nextSibling);
    this.update();
  },

  /**
   * Update character count
   */
  update() {
    const count = DOM.contentInput.value.length;
    this.element.textContent = `${count.toLocaleString()} characters`;
    
    if (count > CONFIG.MAX_CONTENT_LENGTH * 0.9) {
      this.element.classList.add(CSS_CLASSES.charCounterWarning);
    } else {
      this.element.classList.remove(CSS_CLASSES.charCounterWarning);
    }
  }
};

/**
 * Auto-resize functionality for textarea
 */
export const AutoResize = {
  /**
   * Adjust textarea height
   */
  adjust() {
    DOM.contentInput.style.height = 'auto';
    DOM.contentInput.style.height = Math.min(DOM.contentInput.scrollHeight, 500) + 'px';
  }
};

/**
 * Search functionality
 */
export const SearchHandler = {
  isAdded: false,

  /**
   * Add search functionality
   */
  add() {
    if (this.isAdded) return;
    
    const searchWrapper = createElement('div', CSS_CLASSES.searchWrapper);
    
    const searchIcon = createIcon('magnifying-glass');
    searchIcon.classList.add(CSS_CLASSES.searchIcon);
    
    const searchInput = document.createElement('input');
    searchInput.placeholder = 'Search notes...';
    searchInput.addEventListener('input', (e) => this.filter(e.target.value));
    
    searchWrapper.appendChild(searchIcon);
    searchWrapper.appendChild(searchInput);
    
    const h2 = DOM.notesList.querySelector('h2');
    DOM.notesList.insertBefore(searchWrapper, h2.nextSibling);
    
    this.isAdded = true;
  },

  /**
   * Filter notes based on search query
   * @param {string} query - Search query
   */
  filter(query) {
    const searchQuery = query.toLowerCase();
    const noteElements = document.querySelectorAll(`.${CSS_CLASSES.note}`);
    
    noteElements.forEach(noteEl => {
      const title = noteEl.querySelector('h3').textContent.toLowerCase();
      const content = noteEl.querySelector('p').textContent.toLowerCase();
      
      if (title.includes(searchQuery) || content.includes(searchQuery)) {
        noteEl.style.display = 'block';
      } else {
        noteEl.style.display = 'none';
      }
    });
  }
};