// Enhanced notes.js with inline SVG icons and permanent login support - Complete version

let notes = [];
let isLoading = false;

// SVG Icon definitions
const ICONS = {
  notepad: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M168,128a8,8,0,0,1-8,8H96a8,8,0,0,1,0-16h64A8,8,0,0,1,168,128Zm-8,24H96a8,8,0,0,0,0,16h64a8,8,0,0,0,0-16ZM216,40V200a32,32,0,0,1-32,32H72a32,32,0,0,1-32-32V40a8,8,0,0,1,8-8H72V24a8,8,0,0,1,16,0v8h32V24a8,8,0,0,1,16,0v8h32V24a8,8,0,0,1,16,0v8h24A8,8,0,0,1,216,40Zm-16,8H184v8a8,8,0,0,1-16,0V48H136v8a8,8,0,0,1-16,0V48H88v8a8,8,0,0,1-16,0V48H56V200a16,16,0,0,0,16,16H184a16,16,0,0,0,16-16Z"></path></svg>',
  
  pencil: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M229.66,58.34l-32-32a8,8,0,0,0-11.32,0l-96,96A8,8,0,0,0,88,128v32a8,8,0,0,0,8,8h32a8,8,0,0,0,5.66-2.34l96-96A8,8,0,0,0,229.66,58.34ZM124.69,152H104V131.31l64-64L188.69,88ZM200,76.69,179.31,56,192,43.31,212.69,64ZM224,128v80a16,16,0,0,1-16,16H48a16,16,0,0,1-16-16V48A16,16,0,0,1,48,32h80a8,8,0,0,1,0,16H48V208H208V128a8,8,0,0,1,16,0Z"></path></svg>',
  
  files: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M213.66,66.34l-40-40A8,8,0,0,0,168,24H88A16,16,0,0,0,72,40V56H56A16,16,0,0,0,40,72V216a16,16,0,0,0,16,16H168a16,16,0,0,0,16-16V200h16a16,16,0,0,0,16-16V72A8,8,0,0,0,213.66,66.34ZM168,216H56V72h76.69L168,107.31v84.53c0,.06,0,.11,0,.16s0,.1,0,.16V216Zm32-32H184V104a8,8,0,0,0-2.34-5.66l-40-40A8,8,0,0,0,136,56H88V40h76.69L200,75.31Zm-56-32a8,8,0,0,1-8,8H88a8,8,0,0,1,0-16h48A8,8,0,0,1,144,152Zm0,32a8,8,0,0,1-8,8H88a8,8,0,0,1,0-16h48A8,8,0,0,1,144,184Z"></path></svg>',
  
  'floppy-disk': '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M219.31,72,184,36.69A15.86,15.86,0,0,0,172.69,32H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V83.31A15.86,15.86,0,0,0,219.31,72ZM168,208H88V152h80Zm40,0H184V152a16,16,0,0,0-16-16H88a16,16,0,0,0-16,16v56H48V48H172.69L208,83.31ZM160,72a8,8,0,0,1-8,8H96a8,8,0,0,1,0-16h56A8,8,0,0,1,160,72Z"></path></svg>',
  
  'sign-in': '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M141.66,133.66l-40,40a8,8,0,0,1-11.32-11.32L116.69,136H24a8,8,0,0,1,0-16h92.69L90.34,93.66a8,8,0,0,1,11.32-11.32l40,40A8,8,0,0,1,141.66,133.66ZM200,32H136a8,8,0,0,0,0,16h56V208H136a8,8,0,0,0,0,16h64a8,8,0,0,0,8-8V40A8,8,0,0,0,200,32Z"></path></svg>',
  
  'user-plus': '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M256,136a8,8,0,0,1-8,8H232v16a8,8,0,0,1-16,0V144H200a8,8,0,0,1,0-16h16V112a8,8,0,0,1,16,0v16h16A8,8,0,0,1,256,136Zm-57.87,58.85a8,8,0,0,1-12.26,10.3C165.75,181.19,138.09,168,108,168s-57.75,13.19-77.87,37.15a8,8,0,0,1-12.25-10.3c14.94-17.78,33.52-30.41,54.17-37.17a68,68,0,1,1,71.9,0C164.6,164.44,183.18,177.07,198.13,194.85ZM108,152a52,52,0,1,0-52-52A52.06,52.06,0,0,0,108,152Z"></path></svg>',
  
  note: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M227.32,73.37,182.63,28.69a16,16,0,0,0-22.63,0L36.69,152A15.86,15.86,0,0,0,32,163.31V208a16,16,0,0,0,16,16H92.69A15.86,15.86,0,0,0,104,219.31l83.67-83.66,3.48,13.9-36.8,36.79a8,8,0,0,0,11.31,11.32l40-40a8,8,0,0,0,2.11-7.6l-6.9-27.61L227.32,96A16,16,0,0,0,227.32,73.37ZM48,179.31,76.69,208H48Zm48,25.38L51.31,160,136,75.31,180.69,120Zm96-96L147.32,64l24-24L216,84.69Z"></path></svg>',
  
  'trash-simple': '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M213.66,82.34l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40V216a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V88A8,8,0,0,0,213.66,82.34ZM160,51.31,188.69,80H160ZM200,216H56V40h88V88a8,8,0,0,0,8,8h48V216Zm-42.34-82.34L139.31,152l18.35,18.34a8,8,0,0,1-11.32,11.32L128,163.31l-18.34,18.35a8,8,0,0,1-11.32-11.32L116.69,152,98.34,133.66a8,8,0,0,1,11.32-11.32L128,140.69l18.34-18.35a8,8,0,0,1,11.32,11.32Z"></path></svg>',
  
  'magnifying-glass': '<svg width="24" height="24" viewBox="0 0 256 256" fill="currentColor"><path d="m229.66,218.34-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"></path></svg>',

  // Clock icon for session info
  clock: '<svg width="16" height="16" viewBox="0 0 256 256" fill="currentColor"><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm64-88a8,8,0,0,1-8,8H128a8,8,0,0,1-8-8V72a8,8,0,0,1,16,0v48h48A8,8,0,0,1,192,128Z"></path></svg>',

  // Sign out icon for logout all
  'sign-out': '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256"><path d="M112,216a8,8,0,0,1-8,8H48a16,16,0,0,1-16-16V48A16,16,0,0,1,48,32h56a8,8,0,0,1,0,16H48V208h56A8,8,0,0,1,112,216Zm109.66-93.66-40-40a8,8,0,0,0-11.32,11.32L188.69,112H104a8,8,0,0,0,0,16h84.69l-18.35,18.34a8,8,0,0,0,11.32,11.32l40-40A8,8,0,0,0,221.66,122.34Z"></path></svg>'
};

// Configuration
const CONFIG = {
  AUTO_SAVE_DELAY: 30000, // 30 seconds
  REFRESH_INTERVAL: 300000, // 5 minutes
  MAX_CONTENT_LENGTH: 10000,
  TRUNCATE_LENGTH: 200,
  SEARCH_THRESHOLD: 3, // Show search when more than 3 notes
  MESSAGE_HIDE_DELAY: 5000 // 5 seconds
};

// CSS Classes
const CSS_CLASSES = {
  note: 'note',
  noteControls: 'note-controls',
  emptyState: 'empty-state',
  errorState: 'error',
  successState: 'success',
  deleting: 'deleting',
  slideOut: 'slide-out',
  loading: 'loading',
  msgShow: 'show',
  charCounter: 'char-counter',
  charCounterWarning: 'warning',
  searchWrapper: 'search-wrapper',
  searchIcon: 'search-icon',
  userInfo: 'user-info',
  buttonDanger: 'danger',
  buttonWarning: 'warning'
};

// DOM Elements
const DOM = {
  get notesContainer() { return document.getElementById('notesContainer'); },
  get titleInput() { return document.getElementById('title'); },
  get contentInput() { return document.getElementById('content'); },
  get saveBtn() { return document.getElementById('saveBtn'); },
  get saveMsg() { return document.getElementById('saveMsg'); },
  get editor() { return document.querySelector('.editor'); },
  get notesList() { return document.querySelector('.notes-list'); },
  get userInfo() { return document.getElementById('userInfo'); }
};

// Utility functions
const Utils = {
  formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 0) {
      return `Today at ${date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}`;
    } else if (diffDays === 1) {
      return `Yesterday at ${date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}`;
    } else if (diffDays < 7) {
      return `${diffDays} days ago`;
    } else {
      return date.toLocaleDateString();
    }
  },

  truncateText(text, maxLength = CONFIG.TRUNCATE_LENGTH) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
  },

  createElement(tag, className, content) {
    const element = document.createElement(tag);
    if (className) element.className = className;
    if (content) element.textContent = content;
    return element;
  },

  // Updated to create SVG icons instead of font icons
  createIcon(iconName) {
    const wrapper = document.createElement('span');
    wrapper.className = 'icon';
    wrapper.innerHTML = ICONS[iconName] || ICONS.note;
    return wrapper;
  }
};

// Message handling
const MessageHandler = {
  show(element, text, type = 'error') {
    element.textContent = text;
    element.className = `msg ${type} ${CSS_CLASSES.msgShow}`;
    
    setTimeout(() => {
      element.classList.remove(CSS_CLASSES.msgShow);
    }, CONFIG.MESSAGE_HIDE_DELAY);
  },

  hide(element) {
    element.classList.remove(CSS_CLASSES.msgShow);
  }
};

// Notes API
const NotesAPI = {
  async fetch() {
    if (isLoading) return;
    
    isLoading = true;
    
    try {
      const response = await fetch('/api/get_notes.php');
      
      if (!response.ok) {
        if (response.status === 401) {
          location.href = '/login.html';
          return;
        }
        throw new Error('Failed to fetch notes');
      }
      
      return await response.json();
    } catch (error) {
      console.error('Error fetching notes:', error);
      throw error;
    } finally {
      isLoading = false;
    }
  },

  async save(title, content) {
    const body = new URLSearchParams({ title, content });
    const response = await fetch('/api/save_note.php', { 
      method: 'POST', 
      body 
    });
    return await response.json();
  },

  async update(id, title, content) {
    const body = new URLSearchParams({ id, title, content });
    const response = await fetch('/api/update_note.php', { 
      method: 'POST', 
      body 
    });
    return await response.json();
  },

  async delete(id) {
    const response = await fetch('/api/delete_note.php', { 
      method: 'POST', 
      body: new URLSearchParams({ id }) 
    });
    return response.ok;
  }
};

// Notes display
const NotesDisplay = {
  async load() {
    try {
      notes = await NotesAPI.fetch();
      this.render();
    } catch (error) {
      this.showError('Failed to load notes. Please refresh the page.');
    }
  },

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

  createNoteElement(note, index) {
    const noteEl = Utils.createElement('div', CSS_CLASSES.note);
    noteEl.style.animationDelay = `${index * 0.1}s`;
    
    // Title
    const title = Utils.createElement('h3', '', note.title || 'Untitled Note');
    
    // Meta info
    const meta = Utils.createElement('small', '', Utils.formatDate(note.created_at));
    
    // Content
    const content = Utils.createElement('p', '', Utils.truncateText(note.content || ''));
    
    // Controls
    const controls = this.createNoteControls(note, noteEl);
    
    noteEl.appendChild(title);
    noteEl.appendChild(meta);
    noteEl.appendChild(content);
    noteEl.appendChild(controls);
    
    return noteEl;
  },

  createNoteControls(note, noteElement) {
    const controls = Utils.createElement('div', CSS_CLASSES.noteControls);
    
    // Edit button
    const editBtn = document.createElement('button');
    editBtn.className = CSS_CLASSES.buttonWarning;
    editBtn.appendChild(Utils.createIcon('note'));
    editBtn.appendChild(document.createTextNode(' Edit'));
    editBtn.addEventListener('click', () => NoteEditor.edit(note));
    
    // Delete button
    const deleteBtn = document.createElement('button');
    deleteBtn.className = CSS_CLASSES.buttonDanger;
    deleteBtn.appendChild(Utils.createIcon('trash-simple'));
    deleteBtn.appendChild(document.createTextNode(' Delete'));
    deleteBtn.addEventListener('click', () => this.delete(note.id, noteElement));
    
    controls.appendChild(editBtn);
    controls.appendChild(deleteBtn);
    
    return controls;
  },

  showEmpty() {
    DOM.notesContainer.innerHTML = `
      <div class="${CSS_CLASSES.emptyState}">
        <div>No notes yet. Create your first note!</div>
      </div>
    `;
  },

  showError(message) {
    DOM.notesContainer.innerHTML = `
      <div class="${CSS_CLASSES.emptyState} ${CSS_CLASSES.errorState}">
        <div>${message}</div>
      </div>
    `;
  },

  async delete(noteId, noteElement) {
    if (!confirm('Are you sure you want to delete this note?')) {
      return;
    }
    
    // Add deleting class for visual feedback
    noteElement.classList.add(CSS_CLASSES.deleting);
    
    try {
      const success = await NotesAPI.delete(noteId);
      
      if (success) {
        noteElement.classList.add(CSS_CLASSES.slideOut);
        setTimeout(() => {
          this.load(); // Refresh the list
        }, 300);
      } else {
        throw new Error('Delete failed');
      }
    } catch (error) {
      console.error('Error deleting note:', error);
      // Restore element if delete failed
      noteElement.classList.remove(CSS_CLASSES.deleting);
      alert('Failed to delete note. Please try again.');
    }
  }
};

// Note editor
const NoteEditor = {
  autoSaveTimeout: null,

  init() {
    DOM.saveBtn.addEventListener('click', () => this.save());
    DOM.contentInput.addEventListener('input', () => {
      CharacterCounter.update();
      AutoResize.adjust();
      this.scheduleAutoSave();
    });
    DOM.titleInput.addEventListener('input', () => this.scheduleAutoSave());
  },

  edit(note) {
    DOM.titleInput.value = note.title || '';
    DOM.contentInput.value = note.content || '';
    
    // Scroll to editor
    DOM.editor.scrollIntoView({ behavior: 'smooth' });
    
    // Focus on title field
    DOM.titleInput.focus();
    
    // Show edit indicator - updated with SVG icon
    DOM.saveBtn.innerHTML = '';
    DOM.saveBtn.appendChild(Utils.createIcon('floppy-disk'));
    DOM.saveBtn.appendChild(document.createTextNode(' Update Note'));
    DOM.saveBtn.dataset.editingId = note.id;
  },

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
        
        NotesDisplay.load(); // Refresh notes list
        
        // Focus back to content for next note
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

  reset() {
    DOM.titleInput.value = '';
    DOM.contentInput.value = '';
    this.resetSaveButton();
    delete DOM.saveBtn.dataset.editingId;
    CharacterCounter.update();
  },

  resetSaveButton() {
    DOM.saveBtn.innerHTML = '';
    DOM.saveBtn.appendChild(Utils.createIcon('floppy-disk'));
    DOM.saveBtn.appendChild(document.createTextNode(' Save Note'));
  },

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

// Character counter
const CharacterCounter = {
  element: null,

  init() {
    this.element = Utils.createElement('div', CSS_CLASSES.charCounter);
    DOM.contentInput.parentNode.insertBefore(this.element, DOM.contentInput.nextSibling);
    this.update();
  },

  update() {
    const count = DOM.contentInput.value.length;
    this.element.textContent = `${count.toLocaleString()} characters`;
    
    // Add warning class if approaching limit
    if (count > CONFIG.MAX_CONTENT_LENGTH * 0.9) {
      this.element.classList.add(CSS_CLASSES.charCounterWarning);
    } else {
      this.element.classList.remove(CSS_CLASSES.charCounterWarning);
    }
  }
};

// Auto-resize functionality
const AutoResize = {
  adjust() {
    DOM.contentInput.style.height = 'auto';
    DOM.contentInput.style.height = Math.min(DOM.contentInput.scrollHeight, 500) + 'px';
  }
};

// Search functionality
const SearchHandler = {
  isAdded: false,

  add() {
    if (this.isAdded) return;
    
    const searchWrapper = Utils.createElement('div', CSS_CLASSES.searchWrapper);
    
    const searchIcon = Utils.createIcon('magnifying-glass');
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

// Keyboard shortcuts
const KeyboardShortcuts = {
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

// Session management with permanent login support
const SessionManager = {
  init() {
    this.displaySessionStatus();
    
    if (DOM.userInfo && window.JWT_EXP) {
      this.displaySessionInfo();
      if (!window.IS_PERMANENT) {
        this.scheduleWarning();
      }
    }
    
    // Show logout all button for permanent sessions
    this.initLogoutAllButton();
    
    // Periodic refresh to check for session expiry (only for temporary sessions)
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
      
      // Clear and rebuild with icon
      DOM.userInfo.innerHTML = '';
      const clockIcon = Utils.createIcon('clock');
      DOM.userInfo.appendChild(clockIcon);
      DOM.userInfo.appendChild(document.createTextNode(` Expires: ${sessionEnd.toLocaleTimeString()}`));
    } else if (window.IS_PERMANENT) {
      DOM.userInfo.className = CSS_CLASSES.userInfo;
      DOM.userInfo.innerHTML = '';
      const clockIcon = Utils.createIcon('clock');
      DOM.userInfo.appendChild(clockIcon);
      DOM.userInfo.appendChild(document.createTextNode(' No expiration'));
    }
  },

  initLogoutAllButton() {
    const logoutAllBtn = document.getElementById('logoutAllBtn');
    if (!logoutAllBtn) return;
    
    // Only show for permanent sessions
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
      const response = await fetch('/api/logout_all_temp.php', { 
        method: 'POST' 
      });
      const data = await response.json();
      
      if (response.ok && data.success) {
        btn.textContent = 'Success!';
        setTimeout(() => {
          btn.textContent = 'Logout All Temp Sessions';
          btn.disabled = false;
        }, 2000);
        
        // Show success message
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
    const warningTime = timeUntilExpiry - (30 * 60 * 1000); // 30 min before expiry

    if (warningTime > 0) {
      setTimeout(() => {
        if (confirm('Your session will expire in 30 minutes. Click OK to extend your session.')) {
          location.reload();
        }
      }, warningTime);
    }
  }
};

// Logout functionality
const LogoutHandler = {
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
      await fetch('/api/logout.php', { method: 'POST' });
    } catch (error) {
      console.log('Logout request failed, but redirecting anyway');
    }

    location.href = '/login.html';
  }
};

// Form validation for auth pages
const FormValidation = {
  init() {
    const usernameInput = document.querySelector('input[name="username"]');
    const passwordInput = document.querySelector('input[name="password"]');
    
    if (usernameInput) {
      usernameInput.addEventListener('input', () => {
        this.validateUsername(usernameInput);
      });
    }
    
    if (passwordInput) {
      passwordInput.addEventListener('input', () => {
        this.validatePassword(passwordInput);
      });
    }
  },

  validateUsername(input) {
    if (input.value.length > 0 && input.value.length < 3) {
      input.classList.add(CSS_CLASSES.errorState);
    } else {
      input.classList.remove(CSS_CLASSES.errorState);
    }
  },

  validatePassword(input) {
    if (input.value.length > 0 && input.value.length < 6) {
      input.classList.add(CSS_CLASSES.errorState);
    } else {
      input.classList.remove(CSS_CLASSES.errorState);
    }
  }
};

// Enhanced Auth Handler with permanent login support
const AuthHandler = {
  init() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    if (loginForm) {
      loginForm.addEventListener('submit', this.handleLogin);
      
      // Handle permanent login checkbox
      const permanentCheckbox = document.getElementById('permanentLogin');
      if (permanentCheckbox) {
        permanentCheckbox.addEventListener('change', this.updateSessionInfo);
        
        // Initialize session info display
        this.updateSessionInfo();
      }
    }
    
    if (registerForm) {
      registerForm.addEventListener('submit', this.handleRegister);
    }
  },

  updateSessionInfo() {
    const sessionInfo = document.getElementById('sessionInfo');
    const tempInfo = sessionInfo?.querySelector('.temporary');
    const permInfo = sessionInfo?.querySelector('.permanent');
    const checkbox = document.getElementById('permanentLogin');
    
    if (!sessionInfo) return;
    
    if (checkbox?.checked) {
      sessionInfo.style.display = 'block';
      sessionInfo.classList.add('permanent');
      if (tempInfo) tempInfo.style.display = 'none';
      if (permInfo) permInfo.style.display = 'block';
    } else {
      sessionInfo.style.display = 'block';
      sessionInfo.classList.remove('permanent');
      if (tempInfo) tempInfo.style.display = 'block';
      if (permInfo) permInfo.style.display = 'none';
    }
  },

  async handleLogin(e) {
    e.preventDefault();
    
    const form = e.target;
    const loginBtn = document.getElementById('loginBtn');
    const msg = document.getElementById('msg');
    
    // Show loading state
    loginBtn.disabled = true;
    loginBtn.innerHTML = '<span>Signing in...</span>';
    form.classList.add(CSS_CLASSES.loading);
    MessageHandler.hide(msg);
    
    try {
      const formData = new FormData(form);
      
      const response = await fetch('/api/login.php', {
        method: 'POST',
        body: new URLSearchParams(formData)
      });
      
      const data = await response.json();
      
      if (response.ok && data.success) {
        loginBtn.innerHTML = '<span>Success! Redirecting...</span>';
        setTimeout(() => {
          location.href = '/notes.php';
        }, 500);
      } else {
        MessageHandler.show(msg, data.error || 'Login failed', 'error');
        AuthHandler.resetLoginButton(loginBtn, form);
      }
    } catch (error) {
      MessageHandler.show(msg, 'Network error. Please try again.', 'error');
      AuthHandler.resetLoginButton(loginBtn, form);
    }
  },

  async handleRegister(e) {
    e.preventDefault();
    
    const form = e.target;
    const registerBtn = document.getElementById('registerBtn');
    const msg = document.getElementById('msg');
    
    // Show loading state
    registerBtn.disabled = true;
    registerBtn.innerHTML = '<span>Creating account...</span>';
    form.classList.add(CSS_CLASSES.loading);
    MessageHandler.hide(msg);
    
    try {
      const response = await fetch('/api/register.php', {
        method: 'POST',
        body: new URLSearchParams(new FormData(form))
      });
      
      const data = await response.json();
      
      if (response.ok && data.success) {
        MessageHandler.show(msg, 'Account created successfully! You can now sign in.', 'success');
        form.reset();
        registerBtn.innerHTML = '<span>Account Created ✓</span>';
        
        setTimeout(() => {
          location.href = '/login.html';
        }, 2000);
      } else {
        MessageHandler.show(msg, data.error || 'Registration failed', 'error');
        AuthHandler.resetRegisterButton(registerBtn, form);
      }
    } catch (error) {
      MessageHandler.show(msg, 'Network error. Please try again.', 'error');
      AuthHandler.resetRegisterButton(registerBtn, form);
    }
  },

  resetLoginButton(button, form) {
    button.disabled = false;
    button.innerHTML = '';
    button.appendChild(Utils.createIcon('sign-in'));
    button.appendChild(document.createTextNode(' Login'));
    form.classList.remove(CSS_CLASSES.loading);
  },

  resetRegisterButton(button, form) {
    button.disabled = false;
    button.innerHTML = '';
    button.appendChild(Utils.createIcon('user-plus'));
    button.appendChild(document.createTextNode(' Create Account'));
    form.classList.remove(CSS_CLASSES.loading);
  }
};

// Initialize header icons on page load
const HeaderIcons = {
  init() {
    // Update header title with inline SVG
    const headerTitle = document.querySelector('.topbar h1');
    if (headerTitle) {
      headerTitle.innerHTML = '';
      headerTitle.appendChild(Utils.createIcon('notepad'));
      headerTitle.appendChild(document.createTextNode('scratchpad'));
    }

    // Update section headers with inline SVGs
    const editorHeader = document.querySelector('.editor h2');
    if (editorHeader) {
      editorHeader.innerHTML = '';
      editorHeader.appendChild(Utils.createIcon('pencil'));
      editorHeader.appendChild(document.createTextNode('Write Note'));
    }

    const notesHeader = document.querySelector('.notes-list h2');
    if (notesHeader) {
      notesHeader.innerHTML = '';
      notesHeader.appendChild(Utils.createIcon('files'));
      notesHeader.appendChild(document.createTextNode('Your Notes'));
    }

    // Update logout all button with inline SVG if it exists
    const logoutAllBtn = document.getElementById('logoutAllBtn');
    if (logoutAllBtn && window.IS_PERMANENT) {
      logoutAllBtn.innerHTML = '';
      logoutAllBtn.appendChild(Utils.createIcon('sign-out'));
      logoutAllBtn.appendChild(document.createTextNode(' Logout All Temp Sessions'));
    }
  }
};

// Application initialization
const App = {
  init() {
    // Initialize header icons first
    HeaderIcons.init();
    
    // Initialize based on current page
    if (DOM.notesContainer) {
      // Notes page
      this.initNotesPage();
    } else {
      // Auth pages
      this.initAuthPage();
    }
  },

  initNotesPage() {
    // Update save button with inline SVG
    const saveBtn = DOM.saveBtn;
    if (saveBtn) {
      saveBtn.innerHTML = '';
      saveBtn.appendChild(Utils.createIcon('floppy-disk'));
      saveBtn.appendChild(document.createTextNode(' Save Note'));
    }

    NoteEditor.init();
    CharacterCounter.init();
    KeyboardShortcuts.init();
    SessionManager.init();
    LogoutHandler.init();
    
    // Load notes and focus on content
    NotesDisplay.load();
    DOM.contentInput.focus();
  },

  initAuthPage() {
    // Update auth form buttons with inline SVGs
    const loginBtn = document.getElementById('loginBtn');
    if (loginBtn) {
      loginBtn.innerHTML = '';
      loginBtn.appendChild(Utils.createIcon('sign-in'));
      loginBtn.appendChild(document.createTextNode(' Login'));
    }

    const registerBtn = document.getElementById('registerBtn');
    if (registerBtn) {
      registerBtn.innerHTML = '';
      registerBtn.appendChild(Utils.createIcon('user-plus'));
      registerBtn.appendChild(document.createTextNode(' Create Account'));
    }

    FormValidation.init();
    AuthHandler.init();
    
    // Auto-focus first input
    const firstInput = document.querySelector('input');
    if (firstInput) firstInput.focus();
  }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  App.init();
});
