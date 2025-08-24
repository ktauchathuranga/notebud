// Enhanced notes.js with inline SVG icons - Clean and maintainable version

let notes = [];
let isLoading = false;

// SVG Icon definitions
const ICONS = {
  notepad: '<svg width="24" height="24" viewBox="0 0 256 256" fill="currentColor"><path d="M192,76H176V56a16,16,0,0,0-16-16H96A16,16,0,0,0,80,56V76H64A16,16,0,0,0,48,92V208a16,16,0,0,0,16,16H192a16,16,0,0,0,16-16V92A16,16,0,0,0,192,76ZM96,56h64V76H96ZM192,208H64V92H80v12a8,8,0,0,0,16,0V92h64v12a8,8,0,0,0,16,0V92h16V208Z"></path></svg>',
  
  pencil: '<svg width="24" height="24" viewBox="0 0 256 256" fill="currentColor"><path d="M227.31,73.37,182.63,28.68a16,16,0,0,0-22.63,0L36.69,152A15.86,15.86,0,0,0,32,163.31V208a16,16,0,0,0,16,16H92.69A15.86,15.86,0,0,0,104,219.31L227.31,96A16,16,0,0,0,227.31,73.37ZM92.69,208H48V163.31l88-88L180.69,120ZM192,108.68,147.31,64l24-24L216,84.68Z"></path></svg>',
  
  files: '<svg width="24" height="24" viewBox="0 0 256 256" fill="currentColor"><path d="M213.66,82.34l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40V216a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V88A8,8,0,0,0,213.66,82.34ZM160,51.31,188.69,80H160ZM200,216H56V40h88V88a8,8,0,0,0,8,8h48V216Z"></path></svg>',
  
  'floppy-disk': '<svg width="24" height="24" viewBox="0 0 256 256" fill="currentColor"><path d="M219.31,80,176,36.69A15.86,15.86,0,0,0,164.69,32H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V91.31A15.86,15.86,0,0,0,219.31,80ZM164,48l20,20H164ZM208,208H48V48H148V80a8,8,0,0,0,8,8h52V208Z"></path></svg>',
  
  'sign-in': '<svg width="24" height="24" viewBox="0 0 256 256" fill="currentColor"><path d="M141.66,133.66l-40,40a8,8,0,0,1-11.32-11.32L116.69,136H24a8,8,0,0,1,0-16h92.69L90.34,93.66a8,8,0,0,1,11.32-11.32l40,40A8,8,0,0,1,141.66,133.66ZM192,32H136a8,8,0,0,0,0,16h56V208H136a8,8,0,0,0,0,16h56a16,16,0,0,0,16-16V48A16,16,0,0,0,192,32Z"></path></svg>',
  
  'user-plus': '<svg width="24" height="24" viewBox="0 0 256 256" fill="currentColor"><path d="M256,136a8,8,0,0,1-8,8H232v16a8,8,0,0,1-16,0V144H200a8,8,0,0,1,0-16h16V112a8,8,0,0,1,16,0v16h16A8,8,0,0,1,256,136Zm-57.87,58.85a8,8,0,0,1-12.26,10.3C165.75,181.19,138.09,168,108,168s-57.75,13.19-77.87,37.15a8,8,0,0,1-12.25-10.3c14.94-17.78,33.52-30.41,54.17-37.17a68,68,0,1,1,71.9,0C164.6,164.44,183.18,177.07,198.13,194.85ZM108,152a52,52,0,1,0-52-52A52.06,52.06,0,0,0,108,152Z"></path></svg>',
  
  note: '<svg width="24" height="24" viewBox="0 0 256 256" fill="currentColor"><path d="M230.63,89.37,166.63,25.37a8,8,0,0,0-11.31,0L32,148.69A15.86,15.86,0,0,0,27.31,160L16,208a16,16,0,0,0,20,20l48-11.31a15.86,15.86,0,0,0,11.32-4.69L218.63,89.37a8,8,0,0,0,0-11.31ZM48,192l8-32,24,24ZM80,176,56,152,168,40l24,24Z"></path></svg>',
  
  'trash-simple': '<svg width="24" height="24" viewBox="0 0 256 256" fill="currentColor"><path d="M216,48H176V40a24,24,0,0,0-24-24H104A24,24,0,0,0,80,40v8H40a8,8,0,0,0,0,16h8V208a16,16,0,0,0,16,16H192a16,16,0,0,0,16-16V64h8a8,8,0,0,0,0-16ZM96,40a8,8,0,0,1,8-8h48a8,8,0,0,1,8,8v8H96Zm96,168H64V64H192V208Z"></path></svg>',
  
  'magnifying-glass': '<svg width="24" height="24" viewBox="0 0 256 256" fill="currentColor"><path d="m229.66,218.34-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"></path></svg>',

  // Clock icon for session info
  clock: '<svg width="16" height="16" viewBox="0 0 256 256" fill="currentColor"><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm64-88a8,8,0,0,1-8,8H128a8,8,0,0,1-8-8V72a8,8,0,0,1,16,0v48h48A8,8,0,0,1,192,128Z"></path></svg>'
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

// Session management
const SessionManager = {
  init() {
    if (DOM.userInfo && window.JWT_EXP) {
      this.displaySessionInfo();
      this.scheduleWarning();
    }
    
    // Periodic refresh to check for session expiry
    setInterval(() => {
      if (!isLoading && document.visibilityState === 'visible') {
        NotesDisplay.load();
      }
    }, CONFIG.REFRESH_INTERVAL);
  },

  displaySessionInfo() {
    const sessionEnd = new Date(window.JWT_EXP * 1000);
    DOM.userInfo.className = CSS_CLASSES.userInfo;
    
    // Clear and rebuild with icon
    DOM.userInfo.innerHTML = '';
    const clockIcon = Utils.createIcon('clock');
    DOM.userInfo.appendChild(clockIcon);
    DOM.userInfo.appendChild(document.createTextNode(` Session expires: ${sessionEnd.toLocaleTimeString()}`));
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

// Auth form handlers
const AuthHandler = {
  init() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    if (loginForm) {
      loginForm.addEventListener('submit', this.handleLogin);
    }
    
    if (registerForm) {
      registerForm.addEventListener('submit', this.handleRegister);
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
      const response = await fetch('/api/login.php', {
        method: 'POST',
        body: new URLSearchParams(new FormData(form))
      });
      
      const data = await response.json();
      
      if (response.ok && data.success) {
        loginBtn.innerHTML = '<span>Success! Redirecting...</span>';
        setTimeout(() => {
          location.href = '/notes.php';
        }, 500);
      } else {
        MessageHandler.show(msg, data.error || 'Login failed', 'error');
        AuthHandler.resetButton(loginBtn, loginBtn.querySelector('span'), form);
      }
    } catch (error) {
      MessageHandler.show(msg, 'Network error. Please try again.', 'error');
      AuthHandler.resetButton(loginBtn, loginBtn.querySelector('span'), form);
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
        AuthHandler.resetButton(registerBtn, registerBtn.querySelector('span'), form);
      }
    } catch (error) {
      MessageHandler.show(msg, 'Network error. Please try again.', 'error');
      AuthHandler.resetButton(registerBtn, registerBtn.querySelector('span'), form);
    }
  },

  resetButton(button, originalContent, form) {
    button.disabled = false;
    // Restore original button content with icon
    if (button.id === 'loginBtn') {
      button.innerHTML = '';
      button.appendChild(Utils.createIcon('sign-in'));
      button.appendChild(document.createTextNode(' Login'));
    } else if (button.id === 'registerBtn') {
      button.innerHTML = '';
      button.appendChild(Utils.createIcon('user-plus'));
      button.appendChild(document.createTextNode(' Create Account'));
    }
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
