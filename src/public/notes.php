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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/phosphor-icons@1.4.2/src/css/icons.min.css">
    <title>scratchpad - Your Notes</title>
    <link rel="stylesheet" href="css/style.css" />
    <!-- Expose the JWT exp claim to JS -->
    <script>
        window.JWT_EXP = <?= $exp ? intval($exp) : 'null' ?>;
    </script>
</head>

<body>
    <header class="topbar">
        <h1><i class="ph-notepad"></i>scratchpad</h1>
        <div>
            <span id="userInfo"></span>
            <button id="logoutBtn">Logout</button>
        </div>
    </header>

    <main class="container">
        <section class="editor">
            <h2><i class="ph-pencil"></i>Write Note</h2>
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
                <span><i class="ph-floppy-disk"></i> Save Note</span>
            </button>
            <div id="saveMsg" class="msg"></div>
        </section>

        <section class="notes-list">
            <h2><i class="ph-files"></i>Your Notes</h2>
            <div id="notesContainer">
                <div class="empty-state">
                    <div>No notes yet. Create your first note!</div>
                </div>
            </div>
        </section>
    </main>

    <script src="js/notes.js"></script>
</body>

</html>
