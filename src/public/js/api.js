/**
 * Notes API wrapper
 */
export const NotesAPI = {
  /**
   * Fetch all notes
   * @returns {Promise<Array>} Array of notes
   */
  async fetch() {
    const response = await fetch('/api/get_notes');
    
    if (!response.ok) {
      if (response.status === 401) {
        location.href = '/login';
        return;
      }
      throw new Error('Failed to fetch notes');
    }
    
    return await response.json();
  },

  /**
   * Save a new note
   * @param {string} title - Note title
   * @param {string} content - Note content
   * @returns {Promise<Object>} API response
   */
  async save(title, content) {
    const body = new URLSearchParams({ title, content });
    const response = await fetch('/api/save_note', { 
      method: 'POST', 
      body 
    });
    return await response.json();
  },

  /**
   * Update an existing note
   * @param {string} id - Note ID
   * @param {string} title - Note title
   * @param {string} content - Note content
   * @returns {Promise<Object>} API response
   */
  async update(id, title, content) {
    const body = new URLSearchParams({ id, title, content });
    const response = await fetch('/api/update_note', { 
      method: 'POST', 
      body 
    });
    return await response.json();
  },

  /**
   * Delete a note
   * @param {string} id - Note ID
   * @returns {Promise<boolean>} Success status
   */
  async delete(id) {
    const response = await fetch('/api/delete_note', { 
      method: 'POST', 
      body: new URLSearchParams({ id }) 
    });
    return response.ok;
  }
};

/**
 * Files API wrapper
 */
export const FilesAPI = {
  /**
   * Get user files
   * @returns {Promise<Object>} Files data
   */
  async getFiles() {
    const response = await fetch('/api/get_files');
    const responseText = await response.text();
    
    // Clean the response by removing PHP error messages
    let cleanedResponse = responseText;
    const jsonStart = cleanedResponse.indexOf('{');
    if (jsonStart > 0) {
      cleanedResponse = cleanedResponse.substring(jsonStart);
    }
    
    return JSON.parse(cleanedResponse);
  },

  /**
   * Upload a file
   * @param {File} file - File to upload
   * @returns {Promise<Object>} API response
   */
  async uploadFile(file) {
    const formData = new FormData();
    formData.append('file', file);
    
    const response = await fetch('/api/upload_file', {
      method: 'POST',
      body: formData
    });
    
    const responseText = await response.text();
    let cleanedResponse = responseText;
    const jsonStart = cleanedResponse.indexOf('{');
    if (jsonStart > 0) {
      cleanedResponse = cleanedResponse.substring(jsonStart);
    }
    
    return JSON.parse(cleanedResponse);
  },

  /**
   * Download a file
   * @param {string} fileId - File ID
   * @returns {Promise<Blob>} File blob
   */
  async downloadFile(fileId) {
    const response = await fetch(`/api/download_file?file_id=${encodeURIComponent(fileId)}`);
    if (!response.ok) {
      const result = await response.json();
      throw new Error(result.error);
    }
    return await response.blob();
  },

  /**
   * Delete a file
   * @param {string} fileId - File ID
   * @returns {Promise<Object>} API response
   */
  async deleteFile(fileId) {
    const response = await fetch('/api/delete_file', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        file_id: fileId
      })
    });
    return await response.json();
  }
};