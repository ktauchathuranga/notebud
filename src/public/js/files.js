import { FilesAPI } from './api.js';
import { showNotification } from './notifications.js';
import { formatBytes, formatDate, escapeHtml, getFileIcon } from './utils.js';

let filesData = [];
let storageUsage = 0;
let storageLimit = 20 * 1024 * 1024; // 20MB

/**
 * File management functionality
 */
export const FileManager = {
  init() {
    this.initializeFileUpload();
    this.loadUserFiles();
    FileShareHandler.init();
  },

  initializeFileUpload() {
    const uploadBtn = document.getElementById('uploadBtn');
    const fileInput = document.getElementById('fileInput');
    
    if (uploadBtn && fileInput) {
      uploadBtn.addEventListener('click', () => {
        fileInput.click();
      });
      
      fileInput.addEventListener('change', this.handleFileUpload.bind(this));
    }
  },

  async handleFileUpload(event) {
    const files = Array.from(event.target.files);
    if (files.length === 0) return;
    
    const uploadBtn = document.getElementById('uploadBtn');
    const progressDiv = document.querySelector('.upload-progress');
    
    if (progressDiv) {
      progressDiv.style.display = 'block';
    }
    
    if (uploadBtn) {
      uploadBtn.disabled = true;
      uploadBtn.innerHTML = '<span>⏳ Uploading...</span>';
    }
    
    let successCount = 0;
    let errorCount = 0;
    let errors = [];
    
    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      this.updateUploadProgress(i + 1, files.length, file.name);
      
      try {
        const result = await FilesAPI.uploadFile(file);
        
        if (result.success) {
          successCount++;
          showNotification(`File "${file.name}" uploaded successfully!`, 'success', 3000);
        } else {
          errorCount++;
          errors.push(`${file.name}: ${result.error}`);
          showNotification(`Failed to upload "${file.name}": ${result.error}`, 'error', 5000);
        }
      } catch (error) {
        errorCount++;
        errors.push(`${file.name}: Network error`);
        showNotification(`Failed to upload "${file.name}": Network error`, 'error', 5000);
      }
    }
    
    if (progressDiv) {
      progressDiv.style.display = 'none';
    }
    
    if (uploadBtn) {
      uploadBtn.disabled = false;
      uploadBtn.innerHTML = '<span>📎 Upload Files</span>';
    }
    
    event.target.value = '';
    
    await this.loadUserFiles();
    
    if (successCount > 0 && errorCount === 0) {
      showNotification(`Successfully uploaded ${successCount} file(s)!`, 'success', 3000);
    } else if (successCount > 0 && errorCount > 0) {
      showNotification(`Uploaded ${successCount} file(s), failed ${errorCount}`, 'warning', 5000);
    } else if (errorCount > 0) {
      showNotification(`Failed to upload all ${errorCount} file(s)`, 'error', 5000);
    }
  },

  updateUploadProgress(current, total, fileName) {
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
      console.error('Network error loading files:', error);
      showNotification('Failed to load files: Network error', 'error');
    }
  },

  renderFiles() {
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
      
      const downloadBtn = document.createElement('button');
      downloadBtn.className = 'download-btn';
      downloadBtn.innerHTML = '⬇️ Download';
      downloadBtn.addEventListener('click', () => {
        this.downloadFile(file.file_id, file.filename);
      });
      
      const shareBtn = document.createElement('button');
      shareBtn.className = 'secondary';
      shareBtn.innerHTML = '📤 Share';
      shareBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        FileShareHandler.openFileShareModal(file.file_id, file.filename);
      });
      
      const deleteBtn = document.createElement('button');
      deleteBtn.className = 'delete-file-btn';
      deleteBtn.innerHTML = '🗑️ Delete';
      deleteBtn.addEventListener('click', () => {
        this.deleteFile(file.file_id, file.filename);
      });
      
      fileActions.appendChild(downloadBtn);
      fileActions.appendChild(shareBtn);
      fileActions.appendChild(deleteBtn);
      
      fileDiv.appendChild(fileIcon);
      fileDiv.appendChild(fileInfo);
      fileDiv.appendChild(fileActions);
      
      container.appendChild(fileDiv);
    });
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
      if (percentage >= 90) {
        storageBar.classList.add('full');
      } else if (percentage >= 75) {
        storageBar.classList.add('warning');
      }
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
      document.body.removeChild(a);
      
      window.URL.revokeObjectURL(url);
      
      showNotification(`File "${filename}" downloaded successfully!`, 'success');
    } catch (error) {
      showNotification(`Failed to download file: ${error.message}`, 'error');
    }
  },

  async deleteFile(fileId, filename) {
    if (!confirm(`Are you sure you want to delete "${filename}"? This action cannot be undone.`)) {
      return;
    }
    
    try {
      const result = await FilesAPI.deleteFile(fileId);
      
      if (result.success) {
        showNotification(`File "${filename}" deleted successfully!`, 'success');
        await this.loadUserFiles();
      } else {
        showNotification(`Failed to delete file: ${result.error}`, 'error');
      }
    } catch (error) {
      console.error('Delete error:', error);
      showNotification('Failed to delete file: Network error', 'error');
    }
  }
};

/**
 * File sharing functionality
 */
export const FileShareHandler = {
  init() {
    this.loadFileShareRequests();
    setInterval(() => {
      this.loadFileShareRequests();
    }, 30000);
  },

  openFileShareModal(fileId, filename) {
    const existingModal = document.querySelector('.file-share-modal');
    if (existingModal) {
      existingModal.remove();
    }
    
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
    
    const usernameInput = modal.querySelector('#shareFileUsername');
    usernameInput.focus();
    
    const shareBtn = modal.querySelector('#shareFileBtn');
    shareBtn.addEventListener('click', async () => {
      const username = usernameInput.value.trim();
      
      if (!username) {
        showNotification('Please enter a username', 'error');
        return;
      }
      
      shareBtn.disabled = true;
      shareBtn.textContent = 'Sharing...';
      
      try {
        const response = await fetch('/api/share_file', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            file_id: fileId,
            username: username
          })
        });
        
        const result = await response.json();
        
        if (result.success) {
          showNotification(result.message || 'File shared successfully!', 'success');
          modal.remove();
        } else {
          showNotification(result.error || 'Failed to share file', 'error');
          shareBtn.disabled = false;
          shareBtn.textContent = 'Share File';
        }
      } catch (error) {
        console.error('Error sharing file:', error);
        showNotification('Network error. Please try again.', 'error');
        shareBtn.disabled = false;
        shareBtn.textContent = 'Share File';
      }
    });
    
    modal.querySelector('.modal-close').addEventListener('click', () => {
      modal.remove();
    });
    
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        modal.remove();
      }
    });
    
    usernameInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        shareBtn.click();
      }
    });
  },

  async loadFileShareRequests() {
    try {
      const response = await fetch('/api/get_file_share_requests');
      const data = await response.json();
      
      if (data.success) {
        this.renderFileShareRequests(data.requests);
      }
    } catch (error) {
      console.error('Error loading file share requests:', error);
    }
  },

  renderFileShareRequests(requests) {
    let container = document.getElementById('fileShareRequestsContainer');
    let section = document.getElementById('fileShareRequestsSection');
    
    if (!section) {
      section = document.createElement('div');
      section.id = 'fileShareRequestsSection';
      section.className = 'share-requests';
      section.style.display = 'none';
      section.innerHTML = `
        <h3>📁 File Share Requests</h3>
        <div id="fileShareRequestsContainer"></div>
      `;
      
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
      const response = await fetch('/api/accept_file_share', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ request_id: requestId })
      });
      
      const result = await response.json();
      
      if (result.success) {
        showNotification(result.message, 'success');
        this.loadFileShareRequests();
        FileManager.loadUserFiles();
      } else {
        showNotification(result.error, 'error');
      }
    } catch (error) {
      console.error('Error accepting file share:', error);
      showNotification('Network error. Please try again.', 'error');
    }
  },

  async rejectFileShare(requestId) {
    try {
      const response = await fetch('/api/reject_file_share', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ request_id: requestId })
      });
      
      const result = await response.json();
      
      if (result.success) {
        showNotification(result.message, 'success');
        this.loadFileShareRequests();
      } else {
        showNotification(result.error, 'error');
      }
    } catch (error) {
      console.error('Error rejecting file share:', error);
      showNotification('Network error. Please try again.', 'error');
    }
  }
};