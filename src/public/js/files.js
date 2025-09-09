import { FilesAPI } from './api.js';
import { showNotification } from './notifications.js';
import { formatBytes, formatDate, escapeHtml, getFileIcon, createElement, CSS_CLASSES } from './utils.js';
import { ShareHandler } from './sharing.js'; // Import ShareHandler to update indicator

let filesData = [];
let storageUsage = 0;
let storageLimit = 20 * 1024 * 1024; // 20MB
let fileRequests = [];

/**
 * File management functionality
 */
export const FileManager = {
  init() {
    this.initializeFileUpload();
    this.initializeDragAndDrop();
    this.loadUserFiles();
    FileShareHandler.init();
  },

  initializeFileUpload() {
    const fileInput = document.getElementById('fileInput');
    const uploadZone = document.getElementById('uploadZone');

    if (!fileInput || !uploadZone) return;
    
    fileInput.addEventListener('change', this.handleFileUpload.bind(this));
    
    uploadZone.addEventListener('click', () => {
      fileInput.click();
    });
  },

  initializeDragAndDrop() {
    const uploadArea = document.getElementById('fileUploadArea');
    const uploadZone = document.getElementById('uploadZone');
    const dragOverlay = document.getElementById('dragOverlay');
    const fileInput = document.getElementById('fileInput');

    if (!uploadArea || !uploadZone || !dragOverlay || !fileInput) return;

    let dragCounter = 0;

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
      document.addEventListener(eventName, (e) => {
        e.preventDefault();
        e.stopPropagation();
      });
    });

    uploadArea.addEventListener('dragenter', (e) => {
      e.preventDefault();
      dragCounter++;
      uploadZone.classList.add('drag-over');
      dragOverlay.classList.add('show');
    });

    uploadArea.addEventListener('dragover', (e) => {
      e.preventDefault();
    });

    uploadArea.addEventListener('dragleave', (e) => {
      e.preventDefault();
      dragCounter--;
      
      if (dragCounter === 0) {
        uploadZone.classList.remove('drag-over');
        dragOverlay.classList.remove('show');
      }
    });

    uploadArea.addEventListener('drop', (e) => {
      e.preventDefault();
      dragCounter = 0;
      uploadZone.classList.remove('drag-over');
      dragOverlay.classList.remove('show');

      const files = Array.from(e.dataTransfer.files);
      if (files.length > 0) {
        const fakeEvent = {
          target: {
            files: files,
            value: ''
          }
        };
        this.handleFileUpload(fakeEvent);
      }
    });
  },

  validateFile(file) {
    const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB
    const MAX_ZIP_SIZE = 100 * 1024 * 1024; // 100MB for ZIP files
    
    const allowedTypes = [
      'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
      'text/plain', 'text/csv', 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
      'application/zip', 'application/x-zip-compressed', 'application/x-zip', 'application/x-rar-compressed', 'application/x-7z-compressed'
    ];

    const extension = file.name.toLowerCase().split('.').pop();
    const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'zip', 'rar', '7z'];
    
    if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(extension)) {
      return { valid: false, error: `File type not supported: ${file.type || extension}` };
    }

    const isArchive = ['zip', 'rar', '7z'].includes(extension);
    const sizeLimit = isArchive ? MAX_ZIP_SIZE : MAX_FILE_SIZE;
    
    if (file.size > sizeLimit) {
      return { valid: false, error: `File too large: ${formatBytes(file.size)}. Max: ${formatBytes(sizeLimit)}` };
    }

    return { valid: true };
  },

  getFileTypeDescription(file) {
    const extension = file.name.toLowerCase().split('.').pop();
    const descriptions = {
      'zip': 'Archive', 'rar': 'Archive', '7z': 'Archive', 'pdf': 'Document', 'doc': 'Document', 'docx': 'Document',
      'xls': 'Spreadsheet', 'xlsx': 'Spreadsheet', 'ppt': 'Presentation', 'pptx': 'Presentation', 'txt': 'Text file',
      'csv': 'CSV file', 'jpg': 'Image', 'jpeg': 'Image', 'png': 'Image', 'gif': 'Image', 'webp': 'Image', 'svg': 'Image'
    };
    return descriptions[extension] || 'File';
  },

  async handleFileUpload(event) {
    const files = Array.from(event.target.files || event.target);
    if (files.length === 0) return;
    
    const validFiles = files.filter(file => {
      const validation = this.validateFile(file);
      if (!validation.valid) {
        showNotification(`${file.name}: ${validation.error}`, 'error', 7000);
        return false;
      }
      return true;
    });
    
    if (validFiles.length === 0) {
      if (event.target.value !== undefined) event.target.value = '';
      return;
    }
    
    const progressDiv = document.querySelector('.upload-progress');
    if (progressDiv) progressDiv.style.display = 'block';
    
    for (let i = 0; i < validFiles.length; i++) {
      this.updateUploadProgress(i + 1, validFiles.length, validFiles[i].name);
      try {
        const result = await FilesAPI.uploadFile(validFiles[i]);
        if (!result.success) {
            showNotification(`Failed to upload "${validFiles[i].name}": ${result.error}`, 'error', 5000);
        }
      } catch (error) {
        showNotification(`Failed to upload "${validFiles[i].name}": Network error`, 'error', 5000);
      }
    }
    
    if (progressDiv) progressDiv.style.display = 'none';
    if (event.target.value !== undefined) event.target.value = '';
    
    await this.loadUserFiles();
    showNotification(`Upload process completed. ${validFiles.length} file(s) processed.`, 'success');
  },

  updateUploadProgress(current, total, fileName) {
    const progressDiv = document.querySelector('.upload-progress');
    if (!progressDiv) return;
    
    const progressFill = progressDiv.querySelector('.progress-fill');
    const progressText = progressDiv.querySelector('.progress-text');
    
    const percentage = Math.round((current / total) * 100);
    if (progressFill) progressFill.style.width = `${percentage}%`;
    if (progressText) progressText.textContent = `Uploading ${current} of ${total}: ${fileName}`;
  },

  async loadUserFiles() {
    try {
      const result = await FilesAPI.getFiles();
      if (result.success) {
        filesData = result.files || [];
        storageUsage = result.storage_usage || 0;
        storageLimit = result.storage_limit || (20 * 1024 * 1024);
        this.renderFiles();
        this.updateStorageDisplay();
      } else {
        showNotification(`Failed to load files: ${result.error}`, 'error');
      }
    } catch (error) {
      showNotification('Failed to load files: Network error', 'error');
    }
  },

  renderFiles() {
    const container = document.getElementById('filesContainer');
    if (!container) return;
    
    container.innerHTML = filesData.length === 0 
      ? '<div class="empty-state"><div>No files uploaded yet.</div></div>'
      : '';
      
    filesData.forEach(file => container.appendChild(this.createFileElement(file)));
  },

  createFileElement(file) {
      const fileDiv = createElement('div', 'file-item');
      fileDiv.innerHTML = `
        <div class="file-icon">${getFileIcon(file.mime_type)}</div>
        <div class="file-info">
          <div class="file-name">${escapeHtml(file.filename)}</div>
          <div class="file-meta">
            <span>${formatBytes(file.size)}</span>
            <span>${formatDate(file.uploaded_at)}</span>
          </div>
        </div>
      `;
      
      const fileActions = createElement('div', 'file-actions');
      
      const downloadBtn = createElement('button', 'download-btn', '⬇️ Download');
      downloadBtn.addEventListener('click', () => this.downloadFile(file.file_id, file.filename));
      
      const shareBtn = createElement('button', 'secondary', '📤 Share');
      shareBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        FileShareHandler.openFileShareModal(file.file_id, file.filename);
      });
      
      const deleteBtn = createElement('button', 'delete-file-btn', '🗑️ Delete');
      deleteBtn.addEventListener('click', () => this.deleteFile(file.file_id, file.filename));
      
      fileActions.appendChild(downloadBtn);
      fileActions.appendChild(shareBtn);
      fileActions.appendChild(deleteBtn);
      
      fileDiv.appendChild(fileActions);
      return fileDiv;
  },

  updateStorageDisplay() {
    const storageUsageDiv = document.getElementById('storageUsage');
    if (!storageUsageDiv) return;
    
    const percentage = Math.round((storageUsage / storageLimit) * 100);
    const storageBar = storageUsageDiv.querySelector('.storage-fill');
    const storageText = storageUsageDiv.querySelector('small');
    
    if (storageBar) {
      storageBar.style.width = `${percentage}%`;
      storageBar.className = 'storage-fill';
      if (percentage >= 90) storageBar.classList.add('full');
      else if (percentage >= 75) storageBar.classList.add('warning');
    }
    
    if (storageText) {
      storageText.textContent = `${formatBytes(storageUsage)} / ${formatBytes(storageLimit)} used (${percentage}%)`;
    }
  },

  async downloadFile(fileId, filename) {
    try {
      const blob = await FilesAPI.downloadFile(fileId);
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);
      showNotification(`Downloading "${filename}"...`, 'success');
    } catch (error) {
      showNotification(`Download failed: ${error.message}`, 'error');
    }
  },

  async deleteFile(fileId, filename) {
    if (!confirm(`Are you sure you want to delete "${filename}"? This action cannot be undone.`)) return;
    
    try {
      const result = await FilesAPI.deleteFile(fileId);
      if (result.success) {
        showNotification(`File "${filename}" deleted.`, 'success');
        this.loadUserFiles();
      } else {
        showNotification(`Failed to delete file: ${result.error}`, 'error');
      }
    } catch (error) {
      showNotification('Failed to delete file: Network error.', 'error');
    }
  }
};

/**
 * File sharing functionality
 */
export const FileShareHandler = {
  init() {
    this.loadFileShareRequests();
    setInterval(() => this.loadFileShareRequests(), 30000);
  },

  openFileShareModal(fileId, filename) {
    const existingModal = document.querySelector('.file-share-modal');
    if (existingModal) existingModal.remove();
    
    const modal = createElement('div', 'modal file-share-modal show');
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
              <input type="text" id="shareFileUsername" placeholder="Enter username..." required />
            </div>
            <button type="button" id="shareFileBtn">Share File</button>
          </div>
        </div>
      </div>
    `;
    
    document.body.appendChild(modal);
    
    const usernameInput = modal.querySelector('#shareFileUsername');
    usernameInput.focus();
    
    const shareBtn = modal.querySelector('#shareFileBtn');
    shareBtn.addEventListener('click', async () => {
      if (!usernameInput.value.trim()) {
        showNotification('Please enter a username', 'error');
        return;
      }
      shareBtn.disabled = true;
      shareBtn.textContent = 'Sharing...';
      
      try {
        const response = await fetch('/api/share_file', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ file_id: fileId, username: usernameInput.value.trim() })
        });
        const result = await response.json();
        
        if (result.success) {
          showNotification(result.message || 'File shared successfully!', 'success');
          modal.remove();
        } else {
          showNotification(result.error || 'Failed to share file', 'error');
        }
      } catch (error) {
        showNotification('Network error. Please try again.', 'error');
      } finally {
        shareBtn.disabled = false;
        shareBtn.textContent = 'Share File';
      }
    });
    
    modal.querySelector('.modal-close').addEventListener('click', () => modal.remove());
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.remove(); });
    usernameInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') shareBtn.click(); });
  },

  async loadFileShareRequests() {
    try {
      const response = await fetch('/api/get_file_share_requests');
      const data = await response.json();
      fileRequests = data.success ? (data.requests || []) : [];
    } catch (error) {
      console.error('Error loading file share requests:', error);
      fileRequests = [];
    } finally {
        this.renderFileShareRequests();
    }
  },

  renderFileShareRequests() {
    const container = document.getElementById('fileShareRequests');
    if (!container) return;
    container.innerHTML = '';

    fileRequests.forEach(request => {
      const requestEl = createElement('div', CSS_CLASSES.shareRequestItem);
      requestEl.innerHTML = `
        <p>📎 <strong>${escapeHtml(request.from_username)}</strong> wants to share a file:</p>
        <p><strong>${escapeHtml(request.filename)}</strong> (${request.file_size_formatted})</p>
        <small>Received: ${request.created_at_formatted}</small>
      `;
      
      const actions = createElement('div', CSS_CLASSES.noteControls);
      const acceptBtn = createElement('button', 'success', 'Accept');
      acceptBtn.addEventListener('click', () => this.acceptFileShare(request.id));
      
      const rejectBtn = createElement('button', CSS_CLASSES.buttonDanger, 'Reject');
      rejectBtn.addEventListener('click', () => this.rejectFileShare(request.id));
      
      actions.appendChild(acceptBtn);
      actions.appendChild(rejectBtn);
      requestEl.appendChild(actions);
      container.appendChild(requestEl);
    });

    ShareHandler.updateRequestsIndicator();
  },

  async handleShareAction(action, requestId) {
      try {
          const response = await fetch(`/api/${action}_file_share`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ request_id: requestId })
          });
          const result = await response.json();
          showNotification(result.message || 'Action completed.', result.success ? 'success' : 'error');
          if(result.success) {
              this.loadFileShareRequests();
              if (action === 'accept') FileManager.loadUserFiles();
          }
      } catch (error) {
          showNotification('Network error. Please try again.', 'error');
      }
  },

  acceptFileShare(requestId) {
      this.handleShareAction('accept', requestId);
  },

  rejectFileShare(requestId) {
      this.handleShareAction('reject', requestId);
  }
};
