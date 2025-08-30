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
// --- Add: expose JWT expiration timestamp and session info to JS ---
$exp = $payload['exp'] ?? null;
$isPermanent = $payload['permanent'] ?? false;
$sessionId = $payload['session_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="A simple note-taking and chat app designed for university labs with flexible session management. Quick login without 2FA hassles, auto-cleanup, and real-time messaging.">
    <meta name="keywords" content="notebud, note-taking, university labs, student notes, real-time chat, temporary sessions, academic collaboration, lab notes, student messaging">
    <meta name="author" content="Ashen Chathuranga">

    <!-- Facebook/Open Graph Meta Tags -->
    <meta property="og:url" content="https://notebud.cc/">
    <meta property="og:type" content="website">
    <meta property="og:title" content="notebud - Simple Note-Taking & Chat for University Labs">
    <meta property="og:description" content="Perfect for uni labs where you can't access OneDrive/Google Drive due to 2FA hassles. Choose between temporary or permanent sessions, auto-save notes, and chat with classmates in real-time.">
    <meta property="og:image" content="https://notebud.cc/images/notebud-preview.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="notebud - University Lab Note-Taking App">
    <meta property="og:site_name" content="notebud">

    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta property="twitter:domain" content="notebud.cc">
    <meta property="twitter:url" content="https://notebud.cc/">
    <meta name="twitter:title" content="notebud - Simple Note-Taking & Chat for University Labs">
    <meta name="twitter:description" content="Perfect for uni labs where you can't access OneDrive/Google Drive due to 2FA hassles. Choose between temporary or permanent sessions, auto-save notes, and chat with classmates in real-time.">
    <meta name="twitter:image" content="https://notebud.cc/images/notebud-preview.jpg">

    <!-- Additional SEO and App-specific Meta Tags -->
    <meta name="robots" content="index, follow">
    <meta name="language" content="English">
    <meta name="revisit-after" content="7 days">
    <meta name="application-name" content="notebud">
    <meta name="theme-color" content="#1a1a1a">

    <!-- Academic and Educational Context -->
    <meta name="category" content="Education, Productivity, University Tools">
    <meta name="coverage" content="Worldwide">
    <meta name="distribution" content="Global">
    <meta name="rating" content="General">
    <meta name="target" content="University Students, Academic Researchers, Lab Users">

    <!-- Privacy and Security Disclaimers (for search engines) -->
    <meta name="disclaimer" content="Designed for temporary academic use only. Auto-deletes data after 30 days. Use responsibly.">

    <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon/favicon-16x16.png">
    <link rel="manifest" href="favicon/site.webmanifest">
    <title>notebud - Your Notes</title>
    <link rel="stylesheet" href="css/style.css" />
    <!-- Expose session info to JS -->
    <script>
        window.JWT_EXP = <?= $exp ? intval($exp) : 'null' ?>;
        window.IS_PERMANENT = <?= json_encode($isPermanent) ?>;
        window.SESSION_ID = <?= json_encode($sessionId) ?>;
    </script>
</head>

<body>
    <header class="topbar">
        <h1>notebud</h1>
        <div class="session-controls">
            <div id="sessionStatus" class="session-status">
                <span class="indicator"></span>
                <span class="text"></span>
            </div>
            <a href="/chat" class="chat-link">💬 Chat</a>
            <button id="logoutAllBtn" class="logout-all-btn" style="display: none;">
                Logout All Temp Sessions
            </button>
            <button id="logoutBtn" class="logout-btn">Logout</button>
            <span id="userInfo"></span>
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
                maxlength="10000"
                placeholder="Start typing your note here...

Perfect for:
• Quick reminders
• Code snippets  
• Lab instructions
• Temporary data

Your notes auto-delete after 30 days."
                spellcheck="false"></textarea>
            <button id="saveBtn">
                <span>Save Note</span>
            </button>
            <div id="saveMsg" class="msg"></div>
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
</body>

</html>
