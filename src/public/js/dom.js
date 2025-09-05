// DOM element getters and utilities
export const DOM = {
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