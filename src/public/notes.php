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
    <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon/favicon-16x16.png">
    <link rel="manifest" href="favicon/site.webmanifest">
    <link href="https://fonts.googleapis.com/css2?family=Della+Respira&display=swap" rel="stylesheet">
    <title>scratchpad - Your Notes</title>
    <link rel="stylesheet" href="css/style.css" />
    <!-- Expose session info to JS -->
    <script>
        window.JWT_EXP = <?= $exp ? intval($exp) : 'null' ?>;
        window.IS_PERMANENT = <?= json_encode($isPermanent) ?>;
        window.SESSION_ID = <?= json_encode($sessionId) ?>;
    </script>
    <style>
        .session-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .session-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        .session-status.permanent {
            color: var(--success);
        }
        
        .session-status .indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--warning);
        }
        
        .session-status.permanent .indicator {
            background: var(--success);
        }
        
        .logout-all-btn {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .logout-all-btn:hover {
            background: var(--danger);
            color: white;
        }
        
        .logout-all-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        @media (max-width: 768px) {
            .session-controls {
                flex-direction: column;
                align-items: flex-end;
                gap: 0.5rem;
            }
        }
    </style>
</head>

<body>
    <header class="topbar">
        <h1>scratchpad</h1>
        <div class="session-controls">
            <div id="sessionStatus" class="session-status">
                <span class="indicator"></span>
                <span class="text"></span>
            </div>
            <button id="logoutAllBtn" class="logout-all-btn" style="display: none;">
                Logout All Temp Sessions
            </button>
            <button id="logoutBtn">Logout</button>
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
