import { createIcon } from './utils.js';

/**
 * Initialize header icons on page load
 */
export const HeaderIcons = {
  init() {
    // Update header title with inline SVG
    const headerTitle = document.querySelector('.topbar h1');
    if (headerTitle) {
      headerTitle.innerHTML = '';
      headerTitle.appendChild(createIcon('notepad'));
      headerTitle.appendChild(document.createTextNode('notebud'));
    }

    // Update section headers with inline SVGs
    const editorHeader = document.querySelector('.editor h2');
    if (editorHeader) {
      editorHeader.innerHTML = '';
      editorHeader.appendChild(createIcon('pencil'));
      editorHeader.appendChild(document.createTextNode('Write Note'));
    }

    const notesHeader = document.querySelector('.notes-list h2');
    if (notesHeader) {
      notesHeader.innerHTML = '';
      notesHeader.appendChild(createIcon('files'));
      notesHeader.appendChild(document.createTextNode('Your Notes'));
    }

    // Update logout all button with inline SVG if it exists
    const logoutAllBtn = document.getElementById('logoutAllBtn');
    if (logoutAllBtn && window.IS_PERMANENT) {
      logoutAllBtn.innerHTML = '';
      logoutAllBtn.appendChild(createIcon('sign-out'));
      logoutAllBtn.appendChild(document.createTextNode(' Logout All Temp Sessions'));
    }

    // Update save button with inline SVG
    const saveBtn = document.getElementById('saveBtn');
    if (saveBtn) {
      saveBtn.innerHTML = '';
      saveBtn.appendChild(createIcon('floppy-disk'));
      saveBtn.appendChild(document.createTextNode(' Save Note'));
    }

    // Update auth form buttons with inline SVGs
    const loginBtn = document.getElementById('loginBtn');
    if (loginBtn) {
      loginBtn.innerHTML = '';
      loginBtn.appendChild(createIcon('sign-in'));
      loginBtn.appendChild(document.createTextNode(' Login'));
    }

    const registerBtn = document.getElementById('registerBtn');
    if (registerBtn) {
      registerBtn.innerHTML = '';
      registerBtn.appendChild(createIcon('user-plus'));
      registerBtn.appendChild(document.createTextNode(' Create Account'));
    }
  }
};