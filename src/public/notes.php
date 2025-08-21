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
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Scratchpad - Your Notes</title>
    <link rel="stylesheet" href="css/style.css" />
</head>

<body>
    <header class="topbar">
        <h1>Scratchpad</h1>
        <div>
            <span id="userInfo" style="color: var(--text-muted); margin-right: 1rem; font-size: 0.9rem;"></span>
            <button id="logoutBtn">Logout</button>
        </div>
    </header>

    <main class="container">
        <section class="editor">
            <h2>Write Note</h2>
            <input
                id="title"
                placeholder="Note title (optional)"
                spellcheck="false" />
            <textarea
                id="content"
                placeholder="Start typing your note here...

Perfect for:
• Quick reminders
• Code snippets  
• Lab instructions
• Temporary data

Your notes auto-delete after 30 days."
                spellcheck="false"></textarea>
            <button id="saveBtn">
                <span>💾 Save Note</span>
            </button>
            <div id="saveMsg" class="msg" style="display: none;"></div>
        </section>

        <section class="notes-list">
            <h2>Your Notes</h2>
            <div id="notesContainer">
                <div class="empty-state">
                    <div>No notes yet. Create your first note!</div>
                </div>
            </div>
        </section>
    </main>

    <script src="js/notes.js"></script>
    <script>
        // Display user info
        const userInfo = document.getElementById('userInfo');
        const sessionStart = new Date();
        const sessionEnd = new Date(sessionStart.getTime() + 4 * 60 * 60 * 1000); // 4 hours
        userInfo.textContent = `Session expires: ${sessionEnd.toLocaleTimeString()}`;

        // Logout functionality
        document.getElementById('logoutBtn').addEventListener('click', async () => {
            const btn = document.getElementById('logoutBtn');
            btn.disabled = true;
            btn.textContent = 'Logging out...';

            try {
                await fetch('/api/logout.php', {
                    method: 'POST'
                });
            } catch (error) {
                console.log('Logout request failed, but redirecting anyway');
            }

            location.href = '/login.html';
        });

        // Auto-save functionality is handled in notes.js

        // Session warning
        setTimeout(() => {
            if (confirm('Your session will expire in 30 minutes. Click OK to extend your session.')) {
                location.reload();
            }
        }, 3.5 * 60 * 60 * 1000); // 3.5 hours
    </script>
</body>

</html>
