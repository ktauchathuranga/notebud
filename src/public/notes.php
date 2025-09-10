<?php
// src/public/notes.php
// Server-side protect this page by checking JWT cookie and redirecting to login if invalid

require_once __DIR__ . '/../api/auth.php';
$payload = null;
try {
    $payload = require_auth_or_redirect();
} catch (Exception $e) {
    header('Location: /login');
    exit;
}

// Expose JWT info to JS
$exp = $payload['exp'] ?? null;
$isPermanent = $payload['permanent'] ?? false;
$sessionId = $payload['session_id'] ?? null;

// Use the username from the payload
$username = $payload['username'] ?? 'User';
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

    <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon/favicon-16x16.png">
    <link rel="manifest" href="favicon/site.webmanifest">
    <title>Notes - notebud</title>
    <link rel="stylesheet" href="css/style.css" />

    <!-- Expose session info to JS -->
    <script>
        window.JWT_EXP = <?= $exp ? intval($exp) : 'null' ?>;
        window.IS_PERMANENT = <?= json_encode($isPermanent) ?>;
        window.SESSION_ID = <?= json_encode($sessionId) ?>;
        window.USERNAME = <?= json_encode($username) ?>;
    </script>
</head>

<body>
    <header class="topbar">
        <h1>notebud</h1>
        <div class="session-controls">
            <div class="session-status <?= $isPermanent ? 'permanent' : '' ?>">
                <div class="indicator"></div>
                <span class="text"><?= $isPermanent ? 'Permanent Session' : 'Temporary Session (4h)' ?></span>
            </div>

            <!-- Requests Button and Popup -->
            <div class="requests-wrapper">
                <button id="requestsBtn" class="requests-btn">
                    Inbox
                    <span id="requestsIndicator" class="indicator" style="display: none;"></span>
                </button>
                <div id="requestsPopup" class="requests-popup">
                    <div class="popup-header">
                        <h3>Inbox</h3>
                    </div>
                    <div class="popup-body">
                        <div id="noteShareRequests"></div>
                        <div id="fileShareRequests"></div>
                        <div id="noRequestsMessage" class="empty-state" style="display: none;">
                            <div>No new requests.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="userInfo" class="user-info"></div>
            <a href="/chat" class="chat-link">💬 Chat</a>
            <?php if ($isPermanent): ?>
                <button id="logoutAllBtn" class="logout-all-btn" style="display:none;">Logout All Temp Sessions</button>
            <?php endif; ?>
            <button id="logoutBtn" class="logout-btn">Logout</button>
        </div>
    </header>

    <main class="container-new">
        <!-- Notes List Panel -->
        <aside class="notes-list-panel">
            <div class="panel-header">
                <h2>Your Notes</h2>
            </div>
            <div class="panel-body">
                <div id="notesContainer">
                    <div class="empty-state">
                        <div>Loading notes...</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Editor Panel -->
        <section class="editor-panel">
            <div class="panel-header">
                <h2>Write Note</h2>
            </div>
            <div class="panel-body">
                <input type="text" id="title" placeholder="Note title (optional)" />
                <textarea id="content" placeholder="Start writing your note..."></textarea>
                <button id="saveBtn">Save Note</button>
            </div>
        </section>

        <!-- Files Panel -->
        <aside class="files-panel">
            <div class="panel-header">
                <h2>📎 Files</h2>
            </div>
            <div class="panel-body">
                <!-- File Upload Area with Drag & Drop -->
                <div class="file-upload" id="fileUploadArea">
                    <input type="file" id="fileInput" multiple style="display: none;"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.jpg,.jpeg,.png,.gif,.webp,.svg,.zip,.rar,.7z">

                    <div class="upload-zone" id="uploadZone">
                        <div class="upload-icon">📎</div>
                        <div class="upload-text">
                            <strong>Drop files here</strong> or
                            <p>click to browse</p>
                        </div>
                        <div class="upload-hint">
                            PDF, Office docs, images, text files, and archives (ZIP, RAR, 7Z)
                        </div>
                    </div>

                    <div class="drag-overlay" id="dragOverlay">
                        <div class="drag-content">
                            <div class="drag-icon">📁</div>
                            <div class="drag-text">Drop files to upload</div>
                        </div>
                    </div>
                </div>

                <!-- Upload Progress -->
                <div class="upload-progress" id="uploadProgress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 0%;"></div>
                    </div>
                    <div class="progress-text">Uploading...</div>
                </div>

                <!-- Storage Usage -->
                <div id="storageUsage" class="storage-info">
                    <div class="storage-usage">
                        <div class="storage-bar">
                            <div class="storage-fill" style="width: 0%;"></div>
                        </div>
                        <small>Loading storage info...</small>
                    </div>
                </div>

                <!-- Files List -->
                <div id="filesContainer" class="files-list">
                    <div class="empty-state">
                        <div>Loading files...</div>
                    </div>
                </div>
            </div>
        </aside>
    </main>

    <!-- Note Modal -->
    <div id="noteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Note Title</h3>
                <button id="closeModal" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <pre id="modalContent">Note content...</pre>
            </div>
            <div class="modal-footer">
                <small id="modalDate">Created: ...</small>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div id="shareModal" class="modal share-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Share Note</h3>
                <button id="closeShareModal" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="shareForm" class="share-form">
                    <input type="hidden" id="shareNoteId" />
                    <div class="form-group">
                        <label for="shareUsername">Username:</label>
                        <input type="text" id="shareUsername" placeholder="Enter username to share with..." required />
                    </div>
                    <button type="submit" id="shareBtn">Share Note</button>
                </form>
            </div>
        </div>
    </div>

    <script type="module" src="js/app.js"></script>
</body>

</html>
