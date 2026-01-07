<?php
// src/public/notes.php
require_once __DIR__ . '/../api/auth.php';
$payload = null;
try {
    $payload = require_auth_or_redirect();
} catch (Exception $e) {
    header('Location: /login');
    exit;
}
$exp = $payload['exp'] ?? null;
$isPermanent = $payload['permanent'] ?? false;
$sessionId = $payload['session_id'] ?? null;
$username = $payload['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Notebud :: My Notes</title>
    <link rel="stylesheet" href="css/style.css" />
    <script src="https://cdn.jsdelivr.net/npm/marked@12.0.0/marked.min.js"></script>
    <script>
        window.JWT_EXP = <?= $exp ? intval($exp) : 'null' ?>;
        window.IS_PERMANENT = <?= json_encode($isPermanent) ?>;
        window.SESSION_ID = <?= json_encode($sessionId) ?>;
        window.USERNAME = <?= json_encode($username) ?>;
    </script>
</head>
<body>

<div class="main-wrapper">
    <div class="topbar">
        <h1>notebud <sup>v1.0</sup></h1>
        <div class="session-controls">
            Logged in as: <b><?= htmlspecialchars($username) ?></b> | 
            <a href="/chat">Chat Room</a> | 
            <button id="logoutBtn" style="height: 20px; padding: 0 5px;">Log Off</button>
        </div>
    </div>

    <div class="container-new">
        
        <aside class="notes-list-panel">
            <div class="panel-header">
                :: SAVED NOTES
            </div>
            <div id="notesContainer">
                <div style="padding:10px; text-align:center;"><i>Loading...</i></div>
            </div>
        </aside>

        <section class="editor-panel">
            <div class="panel-header">
                :: EDITOR
            </div>
            <div style="padding: 10px;">
                <input type="text" id="title" placeholder="Note Title..." style="width: 98%;" />
                <br />
                <textarea id="content" placeholder="Type your text here..."></textarea>
                <div style="margin-top: 5px; text-align: right;">
                    <button id="saveBtn" class="primary">Save Note</button>
                </div>
            </div>
        </section>

        <aside class="files-panel">
            <div class="panel-header">
                :: FILES
            </div>
            <div style="padding: 10px;">
                <div class="file-upload" id="fileUploadArea">
                    <input type="file" id="fileInput" multiple style="display: none;">
                    <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()">
                        [ Click to Upload ]
                    </div>
                </div>
                <div id="storageUsage" style="font-size: 9px; margin-bottom: 5px;">Usage: Calculating...</div>
                <div id="filesContainer"></div>
            </div>
        </aside>
    </div>
    
    <div style="background: #e0e0e0; border-top: 1px solid #999; padding: 5px; font-size: 9px; text-align: center;">
        &copy; 2005 Notebud Inc. All rights reserved. <a href="#">Privacy Policy</a> - <a href="#">Terms of Service</a>
    </div>
</div>

<div id="noteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span>Reading Note</span>
            <div id="closeModal" class="modal-close">X</div>
        </div>
        <div class="modal-body">
            <h2 id="modalTitle" style="border-bottom: 1px dashed #999; padding-bottom: 5px;"></h2>
            <div id="modalContent" style="margin-top: 10px;"></div>
        </div>
        <div class="modal-footer" style="padding: 5px; background: #e0e0e0; text-align: right;">
            <button id="toggleMarkdown">View Source</button>
        </div>
    </div>
</div>

<script type="module" src="js/app.js"></script>
</body>
</html>