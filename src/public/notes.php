<?php
// src/public/notes.php
// Server-side protect this page by checking JWT cookie and redirecting to login if invalid
require_once __DIR__ . '/../api/auth.php';
$payload = null;
try {
    $payload = require_auth_or_redirect();
} catch (Exception $e) {
    header('Location: /login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Your Notes</title>
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>
  <header class="topbar">
    <h1>Your Notes</h1>
    <div>
      <button id="logoutBtn">Logout</button>
    </div>
  </header>

  <main class="container">
    <section class="editor">
      <input id="title" placeholder="Title" />
      <textarea id="content" placeholder="Write your note here..."></textarea>
      <button id="saveBtn">Save Note</button>
      <div id="saveMsg" class="msg"></div>
    </section>

    <section class="notes-list">
      <h2>Saved Notes</h2>
      <div id="notesContainer"></div>
    </section>
  </main>

  <script src="js/notes.js"></script>
  <script>
    document.getElementById('logoutBtn').addEventListener('click', async () => {
      await fetch('/api/logout.php', { method: 'POST' });
      location.href = '/login.html';
    });
  </script>
</body>
</html>