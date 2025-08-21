// Enhanced notes.js with modern UI interactions

let notes = [];
let isLoading = false;

// Utility functions
function formatDate(dateString) {
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
}

function truncateText(text, maxLength = 200) {
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength) + '...';
}

function showMessage(element, text, type = 'error') {
  element.style.display = 'block';
  element.style.color = type === 'success' ? '#10b981' : '#ef4444';
  element.textContent = text;
  
  // Auto-hide after 5 seconds
  setTimeout(() => {
    element.style.display = 'none';
  }, 5000);
}

// Fetch and display notes
async function fetchNotes() {
  if (isLoading) return;
  
  isLoading = true;
  const container = document.getElementById('notesContainer');
  
  try {
    const res = await fetch('/api/get_notes.php');
    
    if (!res.ok) {
      if (res.status === 401) {
        // Session expired
        location.href = '/login.html';
        return;
      }
      throw new Error('Failed to fetch notes');
    }
    
    notes = await res.json();
    displayNotes();
    
  } catch (error) {
    console.error('Error fetching notes:', error);
    container.innerHTML = `
      <div class="empty-state">
        <div style="color: var(--danger);">Failed to load notes. Please refresh the page.</div>
      </div>
    `;
  } finally {
    isLoading = false;
  }
}

function displayNotes() {
  const container = document.getElementById('notesContainer');
  
  if (notes.length === 0) {
    container.innerHTML = `
      <div class="empty-state">
        <div>No notes yet. Create your first note!</div>
      </div>
    `;
    return;
  }
  
  container.innerHTML = '';
  
  // Sort notes by creation date (newest first)
  const sortedNotes = [...notes].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  
  sortedNotes.forEach((note, index) => {
    const noteEl = document.createElement('div');
    noteEl.className = 'note';
    noteEl.style.animationDelay = `${index * 0.1}s`;
    
    const title = document.createElement('h3');
    title.textContent = note.title || 'Untitled Note';
    
    const meta = document.createElement('small');
    meta.textContent = formatDate(note.created_at);
    
    const content = document.createElement('p');
    content.textContent = truncateText(note.content || '');
    
    const controls = document.createElement('div');
    controls.className = 'note-controls';
    
    const editBtn = document.createElement('button');
    editBtn.textContent = '✏️ Edit';
    editBtn.style.background = 'linear-gradient(135deg, var(--warning), #d97706)';
    editBtn.addEventListener('click', () => editNote(note));
    
    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = '🗑️ Delete';
    deleteBtn.className = 'danger';
    deleteBtn.addEventListener('click', () => deleteNote(note.id, noteEl));
    
    controls.appendChild(editBtn);
    controls.appendChild(deleteBtn);
    
    noteEl.appendChild(title);
    noteEl.appendChild(meta);
    noteEl.appendChild(content);
    noteEl.appendChild(controls);
    
    container.appendChild(noteEl);
  });
}

// Edit note functionality
function editNote(note) {
  document.getElementById('title').value = note.title || '';
  document.getElementById('content').value = note.content || '';
  
  // Scroll to editor
  document.querySelector('.editor').scrollIntoView({ behavior: 'smooth' });
  
  // Focus on title field
  document.getElementById('title').focus();
  
  // Show edit indicator
  const saveBtn = document.getElementById('saveBtn');
  saveBtn.innerHTML = '<span>💾 Update Note</span>';
  saveBtn.dataset.editingId = note.id;
}

// Delete note with confirmation
async function deleteNote(noteId, noteElement) {
  if (!confirm('Are you sure you want to delete this note?')) {
    return;
  }
  
  // Animate out
  noteElement.style.opacity = '0.5';
  noteElement.style.pointerEvents = 'none';
  
  try {
    const res = await fetch('/api/delete_note.php', { 
      method: 'POST', 
      body: new URLSearchParams({ id: noteId }) 
    });
    
    if (res.ok) {
      // Remove from DOM with animation
      noteElement.style.transform = 'translateX(-100%)';
      noteElement.style.transition = 'all 0.3s ease';
      
      setTimeout(() => {
        fetchNotes(); // Refresh the list
      }, 300);
      
    } else {
      throw new Error('Delete failed');
    }
    
  } catch (error) {
    console.error('Error deleting note:', error);
    // Restore element if delete failed
    noteElement.style.opacity = '1';
    noteElement.style.pointerEvents = 'auto';
    alert('Failed to delete note. Please try again.');
  }
}

// Save/Update note functionality
document.getElementById('saveBtn').addEventListener('click', async () => {
  const titleInput = document.getElementById('title');
  const contentInput = document.getElementById('content');
  const saveBtn = document.getElementById('saveBtn');
  const saveMsg = document.getElementById('saveMsg');
  
  const title = titleInput.value.trim();
  const content = contentInput.value.trim();
  
  if (!content) {
    showMessage(saveMsg, 'Please enter some content for your note.');
    contentInput.focus();
    return;
  }
  
  // Show loading state
  saveBtn.disabled = true;
  const isEditing = saveBtn.dataset.editingId;
  saveBtn.innerHTML = isEditing ? '<span>Updating...</span>' : '<span>Saving...</span>';
  
  try {
    const endpoint = isEditing ? '/api/update_note.php' : '/api/save_note.php';
    const body = new URLSearchParams({ title, content });
    if (isEditing) {
      body.append('id', saveBtn.dataset.editingId);
    }
    
    const res = await fetch(endpoint, { 
      method: 'POST', 
      body 
    });
    
    const data = await res.json();
    
    if (res.ok && data.success) {
      // Clear form
      titleInput.value = '';
      contentInput.value = '';
      
      // Reset save button
      saveBtn.innerHTML = '<span>💾 Save Note</span>';
      delete saveBtn.dataset.editingId;
      
      // Show success message
      showMessage(saveMsg, isEditing ? 'Note updated successfully!' : 'Note saved successfully!', 'success');
      
      // Refresh notes list
      fetchNotes();
      
      // Focus back to content for next note
      setTimeout(() => {
        contentInput.focus();
      }, 100);
      
    } else {
      throw new Error(data.error || 'Save failed');
    }
    
  } catch (error) {
    console.error('Error saving note:', error);
    showMessage(saveMsg, error.message || 'Failed to save note. Please try again.');
  } finally {
    saveBtn.disabled = false;
    if (!saveBtn.innerHTML.includes('Save Note')) {
      saveBtn.innerHTML = '<span>💾 Save Note</span>';
    }
  }
});

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
  // Ctrl/Cmd + S to save
  if ((e.ctrlKey || e.metaKey) && e.key === 's') {
    e.preventDefault();
    document.getElementById('saveBtn').click();
  }
  
  // Ctrl/Cmd + N for new note
  if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
    e.preventDefault();
    document.getElementById('title').value = '';
    document.getElementById('content').value = '';
    document.getElementById('content').focus();
    
    // Reset save button if editing
    const saveBtn = document.getElementById('saveBtn');
    saveBtn.innerHTML = '<span>💾 Save Note</span>';
    delete saveBtn.dataset.editingId;
  }
});

// Auto-save functionality
let autoSaveTimeout;

function scheduleAutoSave() {
  clearTimeout(autoSaveTimeout);
  autoSaveTimeout = setTimeout(() => {
    const content = document.getElementById('content').value.trim();
    if (content) {
      // Only auto-save if there's actual content
      document.getElementById('saveBtn').click();
    }
  }, 30000); // Auto-save after 30 seconds of inactivity
}

// Character counter for textarea
const contentTextarea = document.getElementById('content');
const titleInput = document.getElementById('title');

function updateCharacterCount() {
  const count = contentTextarea.value.length;
  const maxLength = 10000; // Reasonable limit
  
  // Add character count if it doesn't exist
  let counter = document.getElementById('charCounter');
  if (!counter) {
    counter = document.createElement('div');
    counter.id = 'charCounter';
    counter.style.cssText = `
      font-size: 0.8rem;
      color: var(--text-muted);
      text-align: right;
      margin-top: 0.5rem;
    `;
    contentTextarea.parentNode.insertBefore(counter, contentTextarea.nextSibling);
  }
  
  counter.textContent = `${count.toLocaleString()} characters`;
  
  if (count > maxLength * 0.9) {
    counter.style.color = 'var(--warning)';
  } else {
    counter.style.color = 'var(--text-muted)';
  }
}

contentTextarea.addEventListener('input', () => {
  updateCharacterCount();
  autoResize();
  scheduleAutoSave();
});

titleInput.addEventListener('input', scheduleAutoSave);

// Search functionality (if you want to add it later)
function addSearchFunctionality() {
  const searchInput = document.createElement('input');
  searchInput.placeholder = '🔍 Search notes...';
  searchInput.style.marginBottom = '1rem';
  
  searchInput.addEventListener('input', (e) => {
    const query = e.target.value.toLowerCase();
    const noteElements = document.querySelectorAll('.note');
    
    noteElements.forEach(noteEl => {
      const title = noteEl.querySelector('h3').textContent.toLowerCase();
      const content = noteEl.querySelector('p').textContent.toLowerCase();
      
      if (title.includes(query) || content.includes(query)) {
        noteEl.style.display = 'block';
      } else {
        noteEl.style.display = 'none';
      }
    });
  });
  
  const notesList = document.querySelector('.notes-list');
  const h2 = notesList.querySelector('h2');
  notesList.insertBefore(searchInput, h2.nextSibling);
}

// Auto-resize textarea
function autoResize() {
  contentTextarea.style.height = 'auto';
  contentTextarea.style.height = Math.min(contentTextarea.scrollHeight, 500) + 'px';
}

// Initialize the app
document.addEventListener('DOMContentLoaded', () => {
  fetchNotes();
  updateCharacterCount();
  
  // Focus on content textarea
  document.getElementById('content').focus();
  
  // Add search if there are notes
  setTimeout(() => {
    if (notes.length > 3) {
      addSearchFunctionality();
    }
  }, 1000);
});

// Periodic refresh to check for session expiry
setInterval(() => {
  if (!isLoading && document.visibilityState === 'visible') {
    fetchNotes();
  }
}, 300000); // Every 5 minutes
