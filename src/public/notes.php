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
// --- Add: expose JWT expiration timestamp to JS ---
$exp = $payload['exp'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Della+Respira&display=swap" rel="stylesheet">
    <title>scratchpad - Your Notes</title>
    <link rel="stylesheet" href="css/style.css" />
    <!-- Expose the JWT exp claim to JS -->
    <script>
        window.JWT_EXP = <?= $exp ? intval($exp) : 'null' ?>;
    </script>
</head>

<body>
    <header class="topbar">
        <h1>scratchpad</h1>
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
        // Display user info with real session expiration
        const userInfo = document.getElementById('userInfo');
        if (window.JWT_EXP) {
            const sessionEnd = new Date(window.JWT_EXP * 1000); // JWT exp is in seconds
            userInfo.textContent = `Session expires: ${sessionEnd.toLocaleTimeString()}, ${sessionEnd.toLocaleDateString()}`;
        } else {
            userInfo.textContent = "Session expiration unknown";
        }

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

        // Session warning based on real JWT expiration
        if (window.JWT_EXP) {
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

        // Auto-save functionality is handled in notes.js
    </script>
</body>

</html>
