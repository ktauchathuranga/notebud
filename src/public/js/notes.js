async function fetchNotes() {
  const res = await fetch('/api/get_notes.php');
  if (!res.ok) {
    // If unauthorized, redirect to login
    if (res.status === 401) {
      location.href = '/login.html';
    }
    return;
  }
  const notes = await res.json();
  const container = document.getElementById('notesContainer');
  container.innerHTML = '';
  for (const n of notes) {
    const noteEl = document.createElement('div');
    noteEl.className = 'note';
    const title = document.createElement('h3');
    title.textContent = n.title || '(no title)';
    const meta = document.createElement('small');
    meta.textContent = n.created_at || '';
    const content = document.createElement('p');
    content.textContent = n.content || '';
    const btns = document.createElement('div');
    btns.className = 'note-controls';
    const del = document.createElement('button');
    del.textContent = 'Delete';
    del.style.background = '#ef4444';
    del.addEventListener('click', async () => {
      await fetch('/api/delete_note.php', { method: 'POST', body: new URLSearchParams({ id: n.id }) });
      fetchNotes();
    });
    btns.appendChild(del);
    noteEl.appendChild(title);
    noteEl.appendChild(meta);
    noteEl.appendChild(content);
    noteEl.appendChild(btns);
    container.appendChild(noteEl);
  }
}

document.getElementById('saveBtn').addEventListener('click', async () => {
  const title = document.getElementById('title').value;
  const content = document.getElementById('content').value;
  const saveMsg = document.getElementById('saveMsg');
  saveMsg.textContent = '';
  const res = await fetch('/api/save_note.php', { method: 'POST', body: new URLSearchParams({ title, content }) });
  const json = await res.json();
  if (res.ok && json.success) {
    document.getElementById('title').value = '';
    document.getElementById('content').value = '';
    saveMsg.style.color = 'green';
    saveMsg.textContent = 'Saved';
    fetchNotes();
  } else {
    saveMsg.style.color = '#b91c1c';
    saveMsg.textContent = json.error || 'Save failed';
  }
});

// initial load
fetchNotes();