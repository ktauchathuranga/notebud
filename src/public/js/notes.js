// Enhanced notes.js with inline SVG icons, permanent login support, and note sharing functionality

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
  
  'trash-simple': '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M213.66,82.34l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40V56H56A16,16,0,0,0,40,72V216a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V88A8,8,0,0,0,213.66,82.34ZM160,51.31,188.69,80H160ZM200,216H56V40h88V88a8,8,0,0,0,8,8h48V216Zm-42.34-82.34L139.31,152l18.35,18.34a8,8,0,0,1-11.32,11.32L128,163.31l-18.34,18.35a8,8,0,0,1-11.32-11.32L116.69,152,98.34,133.66a8,8,0,0,1,11.32-11.32L128,140.69l18.34-18.35a8,8,0,0,1,11.32,11.32Z"></path></svg>',
  
  'magnifying-glass': '<svg width="24" height="24" viewBox="0 0 256 256" fill="currentColor"><path d="m229.66,218.34-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"></path></svg>',

  // Clock icon for session info
  clock: '<svg width="16" height="16" viewBox="0 0 256 256" fill="currentColor"><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm64-88a8,8,0,0,1-8,8H128a8,8,0,0,1-8-8V72a8,8,0,0,1,16,0v48h48A8,8,0,0,1,192,128Z"></path></svg>',

  // Sign out icon for logout all
  'sign-out': '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256"><path d="M112,216a8,8,0,0,1-8,8H48a16,16,0,0,1-16-16V48A16,16,0,0,1,48,32h56a8,8,0,0,1,0,16H48V208h56A8,8,0,0,1,112,216Zm109.66-93.66-40-40a8,8,0,0,0-11.32,11.32L188.69,112H104a8,8,0,0,0,0,16h84.69l-18.35,18.34a8,8,0,0,0,11.32,11.32l40-40A8,8,0,0,0,221.66,122.34Z"></path></svg>',

  // Share icon for note sharing
  share: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M229.66,109.66l-48,48a8,8,0,0,1-11.32-11.32L204.69,112H165a88,88,0,0,0-85.23,66,8,8,0,0,1-15.5-4A104.11,104.11,0,0,1,165,96h39.69L170.34,61.66a8,8,0,0,1,11.32-11.32l48,48A8,8,0,0,1,229.66,109.66ZM192,208H40V80a8,8,0,0,0-16,0V208a16,16,0,0,0,16,16H192a16,16,0,0,0,16-16V160a8,8,0,0,0-16,0v48Z"></path></svg>'
};

// Configuration
const CONFIG = {
  AUTO_SAVE_DELAY: 30000, // 30 seconds
  REFRESH_INTERVAL: 300000, // 5 minutes
  MAX_CONTENT_LENGTH: 10000,
  TRUNCATE_LENGTH: 200,
  SEARCH_THRESHOLD: 3, // Show search when more than 3 notes
  MESSAGE_HIDE_DELAY: 5000, // 5 seconds
  SHARE_REFRESH_INTERVAL: 30000 // 30 seconds
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
  buttonWarning: 'warning',
  shareModal: 'share-modal',
  shareForm: 'share-form',
  shareRequests: 'share-requests',
  shareRequestItem: 'share-request-item',
  sharedNote: 'shared-note',
  sharedBy: 'shared-by'
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
  get userInfo() { return document.getElementById('userInfo'); },
  get noteModal() { return document.getElementById('noteModal'); },
  get modalTitle() { return document.getElementById('modalTitle'); },
  get modalContent() { return document.getElementById('modalContent'); },
  get modalDate() { return document.getElementById('modalDate'); },
  get closeModal() { return document.getElementById('closeModal'); },
  get shareModal() { return document.getElementById('shareModal'); },
  get shareUsername() { return document.getElementById('shareUsername'); },
  get shareNoteId() { return document.getElementById('shareNoteId'); },
  get shareForm() { return document.getElementById('shareForm'); },
  get shareRequestsContainer() { return document.getElementById('shareRequestsContainer'); },
  get shareRequestsSection() { return document.getElementById('shareRequestsSection'); }
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
      const response = await fetch('/api/get_notes');
      
      if (!response.ok) {
        if (response.status === 401) {
          location.href = '/login';
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
    const response = await fetch('/api/save_note', { 
      method: 'POST', 
      body 
    });
    return await response.json();
  },

  async update(id, title, content) {
    const body = new URLSearchParams({ id, title, content });
    const response = await fetch('/api/update_note', { 
      method: 'POST', 
      body 
    });
    return await response.json();
  },

  async delete(id) {
    const response = await fetch('/api/delete_note', { 
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
    
    // Add shared note indicator
    if (note.is_shared) {
      noteEl.classList.add(CSS_CLASSES.sharedNote);
      const sharedBy = Utils.createElement('div', CSS_CLASSES.sharedBy, `Shared by: ${note.shared_by}`);
      noteEl.appendChild(sharedBy);
    }
    
    // Add click event to show full note in modal
    noteEl.addEventListener('click', () => {
      NoteModal.show(note);
    });
    
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
    editBtn.addEventListener('click', (e) => {
      e.stopPropagation(); // Prevent triggering the note click event
      NoteEditor.edit(note);
    });
    
    // Share button
    const shareBtn = document.createElement('button');
    shareBtn.className = 'secondary';
    shareBtn.appendChild(Utils.createIcon('share'));
    shareBtn.appendChild(document.createTextNode(' Share'));
    shareBtn.addEventListener('click', (e) => {
      e.stopPropagation(); // Prevent triggering the note click event
      ShareHandler.openShareModal(note);
    });
    
    // Delete button
    const deleteBtn = document.createElement('button');
    deleteBtn.className = CSS_CLASSES.buttonDanger;
    deleteBtn.appendChild(Utils.createIcon('trash-simple'));
    deleteBtn.appendChild(document.createTextNode(' Delete'));
    deleteBtn.addEventListener('click', (e) => {
      e.stopPropagation(); // Prevent triggering the note click event
      this.delete(note.id, noteElement);
    });
    
    controls.appendChild(editBtn);
    controls.appendChild(shareBtn);
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

// Note modal functionality
const NoteModal = {
  init() {
    // Close modal when clicking the close button
    DOM.closeModal.addEventListener('click', () => this.hide());
    
    // Close modal when clicking outside the content
    DOM.noteModal.addEventListener('click', (e) => {
      if (e.target === DOM.noteModal) {
        this.hide();
      }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && DOM.noteModal.classList.contains('show')) {
        this.hide();
      }
    });
  },

  show(note) {
    DOM.modalTitle.textContent = note.title || 'Untitled Note';
    DOM.modalContent.textContent = note.content || '';
    DOM.modalDate.textContent = `Created: ${Utils.formatDate(note.created_at)}`;
    
    DOM.noteModal.classList.add('show');
    document.body.style.overflow = 'hidden'; // Prevent scrolling
  },

  hide() {
    DOM.noteModal.classList.remove('show');
    document.body.style.overflow = ''; // Re-enable scrolling
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

// Share Handler functionality
const ShareHandler = {
init() {
  // Initialize share modal
  if (DOM.shareModal) {
    DOM.shareForm.addEventListener('submit', this.handleShare.bind(this)); // Bind this to ShareHandler
    document.getElementById('closeShareModal').addEventListener('click', this.closeShareModal.bind(this)); // Bind for consistency
    DOM.shareModal.addEventListener('click', (e) => {
      if (e.target === DOM.shareModal) this.closeShareModal();
    });
  }
  
  // Load share requests
  this.loadShareRequests();
  
  // Set up periodic refresh of share requests
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
        // Handle specific error for duplicate share requests
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
      this.closeShareModal(); // Always close the modal
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
      const requestEl = Utils.createElement('div', CSS_CLASSES.shareRequestItem);
      
      const message = Utils.createElement('p', '', 
        `${request.from_username} wants to share a note with you`);
      
      const date = Utils.createElement('small', '', 
        `Received: ${Utils.formatDate(request.created_at)}`);
      
      const actions = Utils.createElement('div', CSS_CLASSES.noteControls);
      
      // Accept button
      const acceptBtn = document.createElement('button');
      acceptBtn.className = 'success';
      acceptBtn.textContent = 'Accept';
      acceptBtn.addEventListener('click', () => {
        this.acceptShare(request.id);
      });
      
      // Reject button
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
        NotesDisplay.load(); // Refresh notes list
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
      const response = await fetch('/api/logout_all_temp', { 
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
      await fetch('/api/logout', { method: 'POST' });
    } catch (error) {
      console.log('Logout request failed, but redirecting anyway');
    }

    location.href = '/login';
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
      
      const response = await fetch('/api/login', {
        method: 'POST',
        body: new URLSearchParams(formData)
      });
      
      const data = await response.json();
      
      if (response.ok && data.success) {
        loginBtn.innerHTML = '<span>Success! Redirecting...</span>';
        setTimeout(() => {
          location.href = '/notes';
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
      const response = await fetch('/api/register', {
        method: 'POST',
        body: new URLSearchParams(new FormData(form))
      });
      
      const data = await response.json();
      
      if (response.ok && data.success) {
        MessageHandler.show(msg, 'Account created successfully! You can now sign in.', 'success');
        form.reset();
        registerBtn.innerHTML = '<span>Account Created ✓</span>';
        
        setTimeout(() => {
          location.href = '/login';
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
      headerTitle.appendChild(document.createTextNode('notebud'));
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
    NoteModal.init(); // Initialize the note modal
    ShareHandler.init(); // Initialize share functionality
    FileShareHandler.init();
    
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


// File management variables
let filesData = [];
let storageUsage = 0;
let storageLimit = 20 * 1024 * 1024; // 20MB

// Initialize file functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Existing initialization code...
    
    // Initialize file functionality
    initializeFileManagement();
});

function initializeFileManagement() {
    const uploadBtn = document.getElementById('uploadBtn');
    const fileInput = document.getElementById('fileInput');
    
    if (uploadBtn && fileInput) {
        uploadBtn.addEventListener('click', () => {
            fileInput.click();
        });
        
        fileInput.addEventListener('change', handleFileUpload);
    }
    
    // Load user files
    loadUserFiles();
}

// Update the handleFileUpload function with better error handling

async function handleFileUpload(event) {
    const files = Array.from(event.target.files);
    if (files.length === 0) return;
    
    console.log('Starting file upload for files:', files.map(f => ({name: f.name, size: f.size, type: f.type})));
    
    const uploadBtn = document.getElementById('uploadBtn');
    const progressDiv = document.querySelector('.upload-progress');
    
    // Show progress
    if (progressDiv) {
        progressDiv.style.display = 'block';
    }
    
    // Disable upload button
    if (uploadBtn) {
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<span>⏳ Uploading...</span>';
    }
    
    let successCount = 0;
    let errorCount = 0;
    let errors = [];
    
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        console.log(`Uploading file ${i + 1}/${files.length}: ${file.name}`);
        
        // Update progress
        updateUploadProgress(i + 1, files.length, file.name);
        
        const formData = new FormData();
        formData.append('file', file);
        
        try {
            console.log('Sending upload request for:', file.name);
            const response = await fetch('/api/upload_file', {
                method: 'POST',
                body: formData
            });
            
            console.log('Upload response status:', response.status);
            console.log('Upload response headers:', response.headers);
            
            const responseText = await response.text();
            console.log('Upload raw response:', responseText);
            
            // Clean the response by removing PHP error messages
            let cleanedResponse = responseText;
            const jsonStart = cleanedResponse.indexOf('{');
            if (jsonStart > 0) {
                cleanedResponse = cleanedResponse.substring(jsonStart);
            }
            
            let result;
            try {
                result = JSON.parse(cleanedResponse);
                console.log('Parsed upload result:', result);
            } catch (parseError) {
                console.error('Failed to parse upload response:', parseError);
                console.error('Response was:', responseText);
                errorCount++;
                errors.push(`${file.name}: Invalid server response`);
                continue;
            }
            
            if (result.success) {
                successCount++;
                console.log(`Successfully uploaded: ${file.name}`);
                showMessage(`File "${file.name}" uploaded successfully!`, 'success', 3000);
            } else {
                errorCount++;
                errors.push(`${file.name}: ${result.error}`);
                console.error(`Failed to upload ${file.name}:`, result.error);
                showMessage(`Failed to upload "${file.name}": ${result.error}`, 'error', 5000);
            }
        } catch (error) {
            errorCount++;
            errors.push(`${file.name}: Network error`);
            console.error(`Network error uploading ${file.name}:`, error);
            showMessage(`Failed to upload "${file.name}": Network error`, 'error', 5000);
        }
    }
    
    // Hide progress
    if (progressDiv) {
        progressDiv.style.display = 'none';
    }
    
    // Reset upload button
    if (uploadBtn) {
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<span>📎 Upload Files</span>';
    }
    
    // Clear file input
    event.target.value = '';
    
    // Reload files list
    await loadUserFiles();
    
    // Show summary message
    console.log(`Upload complete. Success: ${successCount}, Errors: ${errorCount}`);
    if (errors.length > 0) {
        console.log('Upload errors:', errors);
    }
    
    if (successCount > 0 && errorCount === 0) {
        showMessage(`Successfully uploaded ${successCount} file(s)!`, 'success', 3000);
    } else if (successCount > 0 && errorCount > 0) {
        showMessage(`Uploaded ${successCount} file(s), failed ${errorCount}`, 'warning', 5000);
    } else if (errorCount > 0) {
        showMessage(`Failed to upload all ${errorCount} file(s)`, 'error', 5000);
    }
}

function updateUploadProgress(current, total, fileName) {
    const progressDiv = document.querySelector('.upload-progress');
    if (!progressDiv) return;
    
    const progressFill = progressDiv.querySelector('.progress-fill');
    const progressText = progressDiv.querySelector('.progress-text');
    
    const percentage = Math.round((current / total) * 100);
    
    if (progressFill) {
        progressFill.style.width = `${percentage}%`;
    }
    
    if (progressText) {
        progressText.textContent = `Uploading ${current} of ${total}: ${fileName}`;
    }
}
async function loadUserFiles() {
    try {
        console.log('Loading user files...');
        const response = await fetch('/api/get_files');
        
        console.log('Response status:', response.status);
        
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        // Clean the response by removing PHP error messages
        let cleanedResponse = responseText;
        
        // Remove PHP warnings/errors that appear before the JSON
        const jsonStart = cleanedResponse.indexOf('{');
        if (jsonStart > 0) {
            cleanedResponse = cleanedResponse.substring(jsonStart);
            console.log('Cleaned response:', cleanedResponse);
        }
        
        // Try to parse as JSON
        let result;
        try {
            result = JSON.parse(cleanedResponse);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response was not valid JSON:', cleanedResponse);
            showMessage('Server returned invalid response', 'error');
            return;
        }
        
        if (result.success) {
            filesData = result.files || [];
            storageUsage = result.storage_usage || 0;
            storageLimit = result.storage_limit || (20 * 1024 * 1024);
            
            renderFiles();
            updateStorageDisplay();
        } else {
            console.error('API returned error:', result.error);
            showMessage(`Failed to load files: ${result.error}`, 'error');
        }
    } catch (error) {
        console.error('Network error loading files:', error);
        showMessage('Failed to load files: Network error', 'error');
    }
}

function formatBytes(bytes) {
    if (bytes === 0 || !bytes || !isFinite(bytes)) return '0 B';
    
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    if (i < 0 || i >= sizes.length) return '0 B';
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function renderFiles() {
    const container = document.getElementById('filesContainer');
    if (!container) return;
    
    if (filesData.length === 0) {
        container.innerHTML = '<div class="empty-state"><div>No files uploaded yet.</div></div>';
        return;
    }
    
    container.innerHTML = '';
    
    filesData.forEach(file => {
        const fileDiv = document.createElement('div');
        fileDiv.className = 'file-item';
        
        // Create elements properly
        const fileIcon = document.createElement('div');
        fileIcon.className = 'file-icon';
        fileIcon.textContent = getFileIcon(file.mime_type);
        
        const fileInfo = document.createElement('div');
        fileInfo.className = 'file-info';
        fileInfo.innerHTML = `
            <div class="file-name">${escapeHtml(file.filename)}</div>
            <div class="file-meta">
                <span>${formatBytes(file.size)}</span>
                <span>${formatDate(file.uploaded_at)}</span>
            </div>
        `;
        
        const fileActions = document.createElement('div');
        fileActions.className = 'file-actions';
        
        // Download button
        const downloadBtn = document.createElement('button');
        downloadBtn.className = 'download-btn';
        downloadBtn.innerHTML = '⬇️ Download';
        downloadBtn.addEventListener('click', () => {
            downloadFile(file.file_id, file.filename);
        });
        
        // Share button
        const shareBtn = document.createElement('button');
        shareBtn.className = 'secondary';
        shareBtn.innerHTML = '📤 Share';
        shareBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            FileShareHandler.openFileShareModal(file.file_id, file.filename);
        });
        
        // Delete button
        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'delete-file-btn';
        deleteBtn.innerHTML = '🗑️ Delete';
        deleteBtn.addEventListener('click', () => {
            deleteFile(file.file_id, file.filename);
        });
        
        fileActions.appendChild(downloadBtn);
        fileActions.appendChild(shareBtn);
        fileActions.appendChild(deleteBtn);
        
        fileDiv.appendChild(fileIcon);
        fileDiv.appendChild(fileInfo);
        fileDiv.appendChild(fileActions);
        
        container.appendChild(fileDiv);
    });
}

function updateStorageDisplay() {
    const storageUsageDiv = document.getElementById('storageUsage');
    if (!storageUsageDiv) return;
    
    const percentage = Math.round((storageUsage / storageLimit) * 100);
    const storageBar = storageUsageDiv.querySelector('.storage-fill');
    const storageText = storageUsageDiv.querySelector('small');
    
    if (storageBar) {
        storageBar.style.width = `${percentage}%`;
        
        // Update color based on usage
        storageBar.className = 'storage-fill';
        if (percentage >= 90) {
            storageBar.classList.add('full');
        } else if (percentage >= 75) {
            storageBar.classList.add('warning');
        }
    }
    
    if (storageText) {
        storageText.textContent = `${formatBytes(storageUsage)} / ${formatBytes(storageLimit)} used (${percentage}%)`;
    }
}

async function downloadFile(fileId, filename) {
    try {
        const response = await fetch(`/api/download_file?file_id=${encodeURIComponent(fileId)}`);
        
        if (response.ok) {
            // Create a blob from the response
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            
            // Create a temporary link and click it to download
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            
            // Clean up the URL object
            window.URL.revokeObjectURL(url);
            
            showMessage(`File "${filename}" downloaded successfully!`, 'success');
        } else {
            const result = await response.json();
            showMessage(`Failed to download file: ${result.error}`, 'error');
        }
    } catch (error) {
        console.error('Download error:', error);
        showMessage('Failed to download file: Network error', 'error');
    }
}

async function deleteFile(fileId, filename) {
    if (!confirm(`Are you sure you want to delete "${filename}"? This action cannot be undone.`)) {
        return;
    }
    
    try {
        const response = await fetch('/api/delete_file', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                file_id: fileId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage(`File "${filename}" deleted successfully!`, 'success');
            await loadUserFiles(); // Reload files list
        } else {
            showMessage(`Failed to delete file: ${result.error}`, 'error');
        }
    } catch (error) {
        console.error('Delete error:', error);
        showMessage('Failed to delete file: Network error', 'error');
    }
}

function getFileIcon(mimeType) {
    const icons = {
        'application/pdf': '📄',
        'application/msword': '📝',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document': '📝',
        'application/vnd.ms-excel': '📊',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': '📊',
        'application/vnd.ms-powerpoint': '📽️',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation': '📽️',
        'text/plain': '📄',
        'text/csv': '📊',
        'image/jpeg': '🖼️',
        'image/png': '🖼️',
        'image/gif': '🖼️',
        'image/webp': '🖼️'
    };
    
    return icons[mimeType] || '📎';
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInMs = now - date;
    const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24));
    
    if (diffInDays === 0) {
        return 'Today';
    } else if (diffInDays === 1) {
        return 'Yesterday';
    } else if (diffInDays < 7) {
        return `${diffInDays} days ago`;
    } else {
        return date.toLocaleDateString();
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Add to existing showMessage function or create if it doesn't exist
function showMessage(message, type = 'info', duration = 5000) {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.notification');
    existingMessages.forEach(msg => msg.remove());
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto-remove after duration
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, duration);
}

// Update the existing CSS notification styles or add them
const notificationStyles = `
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--card);
    color: var(--text);
    padding: 1rem 1.5rem;
    border-radius: var(--radius);
    z-index: 1000;
    box-shadow: var(--shadow-lg);
    border-left: 4px solid var(--primary);
    min-width: 300px;
    max-width: 500px;
    animation: slideIn 0.3s ease;
    font-weight: 500;
    word-wrap: break-word;
}

.notification.success {
    border-left-color: var(--success);
    background: var(--success-light);
    color: var(--success);
}

.notification.error {
    border-left-color: var(--danger);
    background: var(--danger-light);
    color: var(--danger);
}

@media (max-width: 767px) {
    .notification {
        top: 10px;
        right: 10px;
        left: 10px;
        min-width: auto;
        max-width: none;
    }
}
`;

// Add styles if they don't exist
if (!document.querySelector('#notification-styles')) {
    const styleElement = document.createElement('style');
    styleElement.id = 'notification-styles';
    styleElement.textContent = notificationStyles;
    document.head.appendChild(styleElement);
}

// Add this to your notes.js file
const FileShareHandler = {
    init() {
        this.loadFileShareRequests();
        
        // Set up periodic refresh
        setInterval(() => {
            this.loadFileShareRequests();
        }, 30000); // 30 seconds
    },

    openFileShareModal(fileId, filename) {
        console.log('Opening share modal for file:', filename, 'ID:', fileId);
        
        // Remove existing modal if any
        const existingModal = document.querySelector('.file-share-modal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Create modal
        const modal = document.createElement('div');
        modal.className = 'modal file-share-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Share File: ${escapeHtml(filename)}</h3>
                    <button class="modal-close" type="button">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="share-form">
                        <div class="form-group">
                            <label>Username:</label>
                            <input type="text" id="shareFileUsername" placeholder="Enter username to share with..." required />
                        </div>
                        <button type="button" id="shareFileBtn">Share File</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        modal.classList.add('show');
        
        // Focus on username input
        const usernameInput = modal.querySelector('#shareFileUsername');
        usernameInput.focus();
        
        // Handle share button click
        const shareBtn = modal.querySelector('#shareFileBtn');
        shareBtn.addEventListener('click', async () => {
            const username = usernameInput.value.trim();
            
            if (!username) {
                showMessage('Please enter a username', 'error');
                return;
            }
            
            shareBtn.disabled = true;
            shareBtn.textContent = 'Sharing...';
            
            try {
                console.log('Sharing file:', fileId, 'with user:', username);
                
                const response = await fetch('/api/share_file.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        file_id: fileId,
                        username: username
                    })
                });
                
                console.log('Share response status:', response.status);
                
                const result = await response.json();
                console.log('Share response data:', result);
                
                if (result.success) {
                    showMessage(result.message || 'File shared successfully!', 'success');
                    modal.remove();
                } else {
                    showMessage(result.error || 'Failed to share file', 'error');
                    shareBtn.disabled = false;
                    shareBtn.textContent = 'Share File';
                }
            } catch (error) {
                console.error('Error sharing file:', error);
                showMessage('Network error. Please try again.', 'error');
                shareBtn.disabled = false;
                shareBtn.textContent = 'Share File';
            }
        });
        
        // Handle close
        modal.querySelector('.modal-close').addEventListener('click', () => {
            modal.remove();
        });
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
        
        // Handle Enter key
        usernameInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                shareBtn.click();
            }
        });
    },

    async loadFileShareRequests() {
        try {
            const response = await fetch('/api/get_file_share_requests.php');
            const data = await response.json();
            
            if (data.success) {
                this.renderFileShareRequests(data.requests);
            } else {
                console.error('Failed to load file share requests:', data.error);
            }
        } catch (error) {
            console.error('Error loading file share requests:', error);
        }
    },

    renderFileShareRequests(requests) {
        let container = document.getElementById('fileShareRequestsContainer');
        let section = document.getElementById('fileShareRequestsSection');
        
        // Create section if it doesn't exist
        if (!section) {
            section = document.createElement('div');
            section.id = 'fileShareRequestsSection';
            section.className = 'share-requests';
            section.style.display = 'none';
            section.innerHTML = `
                <h3>📁 File Share Requests</h3>
                <div id="fileShareRequestsContainer"></div>
            `;
            
            // Add to files section or create separate section
            const filesSection = document.querySelector('.files-section');
            if (filesSection) {
                filesSection.parentNode.insertBefore(section, filesSection.nextSibling);
            } else {
                document.querySelector('.right-panel').appendChild(section);
            }
            
            container = document.getElementById('fileShareRequestsContainer');
        }
        
        if (requests.length === 0) {
            section.style.display = 'none';
            return;
        }
        
        section.style.display = 'block';
        container.innerHTML = '';
        
        requests.forEach(request => {
            const requestEl = document.createElement('div');
            requestEl.className = 'share-request-item';
            requestEl.innerHTML = `
                <p><strong>${escapeHtml(request.from_username)}</strong> wants to share a file with you</p>
                <p>📎 <strong>${escapeHtml(request.filename)}</strong> (${request.file_size_formatted})</p>
                <small>Received: ${request.created_at_formatted}</small>
                <div class="note-controls">
                    <button class="success" data-request-id="${request.id}" data-action="accept">Accept</button>
                    <button class="danger" data-request-id="${request.id}" data-action="reject">Reject</button>
                </div>
            `;
            
            // Add event listeners for buttons
            const acceptBtn = requestEl.querySelector('[data-action="accept"]');
            const rejectBtn = requestEl.querySelector('[data-action="reject"]');
            
            acceptBtn.addEventListener('click', () => {
                this.acceptFileShare(request.id);
            });
            
            rejectBtn.addEventListener('click', () => {
                this.rejectFileShare(request.id);
            });
            
            container.appendChild(requestEl);
        });
    },

    async acceptFileShare(requestId) {
        try {
            const response = await fetch('/api/accept_file_share.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ request_id: requestId })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showMessage(result.message, 'success');
                this.loadFileShareRequests();
                loadUserFiles(); // Refresh files list
            } else {
                showMessage(result.error, 'error');
            }
        } catch (error) {
            console.error('Error accepting file share:', error);
            showMessage('Network error. Please try again.', 'error');
        }
    },

    async rejectFileShare(requestId) {
        try {
            const response = await fetch('/api/reject_file_share.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ request_id: requestId })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showMessage(result.message, 'success');
                this.loadFileShareRequests();
            } else {
                showMessage(result.error, 'error');
            }
        } catch (error) {
            console.error('Error rejecting file share:', error);
            showMessage('Network error. Please try again.', 'error');
        }
    }
};

// Update your existing renderFiles function to include share button
function renderFiles() {
    const container = document.getElementById('filesContainer');
    if (!container) return;
    
    if (filesData.length === 0) {
        container.innerHTML = '<div class="empty-state"><div>No files uploaded yet.</div></div>';
        return;
    }
    
    container.innerHTML = '';
    
    filesData.forEach(file => {
        const fileDiv = document.createElement('div');
        fileDiv.className = 'file-item';
        fileDiv.innerHTML = `
            <div class="file-icon">${getFileIcon(file.mime_type)}</div>
            <div class="file-info">
                <div class="file-name">${escapeHtml(file.filename)}</div>
                <div class="file-meta">
                    <span>${formatBytes(file.size)}</span>
                    <span>${formatDate(file.uploaded_at)}</span>
                </div>
            </div>
            <div class="file-actions">
                <button class="download-btn" onclick="downloadFile('${file.file_id}', '${escapeHtml(file.filename)}')">
                    ⬇️ Download
                </button>
                <button class="secondary" onclick="FileShareHandler.openFileShareModal('${file.file_id}', '${escapeHtml(file.filename)}')">
                    📤 Share
                </button>
                <button class="delete-file-btn" onclick="deleteFile('${file.file_id}', '${escapeHtml(file.filename)}')">
                    🗑️ Delete
                </button>
            </div>
        `;
        
        container.appendChild(fileDiv);
    });
}
