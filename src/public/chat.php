<?php
// src/public/chat.php
require_once __DIR__ . '/../api/auth.php';
$payload = null;
try {
    $payload = require_auth_or_redirect();
} catch (Exception $e) {
    header('Location: /login');
    exit;
}
$username = $payload['username'] ?? 'User';
$jwtToken = $_COOKIE['token'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Notebud Chat Room</title>
    <link rel="stylesheet" href="css/style.css" />
    <script>
        window.JWT_TOKEN = <?= json_encode($jwtToken) ?>;
        window.USERNAME = <?= json_encode($username) ?>;
    </script>
</head>
<body>

<div class="main-wrapper">
    <div class="topbar">
        <h1>notebud <sup>Chat</sup></h1>
        <div class="session-controls">
            <a href="/notes">&laquo; Back to Notes</a> | 
            <button id="logoutBtn" style="height: 20px;">Exit</button>
        </div>
    </div>

    <div class="chat-container">
        <div class="chat-sidebar" id="chatSidebar">
            <div style="background: #ccc; padding: 5px; font-weight: bold; border-bottom: 1px solid #999;">
                Online Users
            </div>
            <div class="sidebar-body" style="padding: 5px;">
                <div class="new-chat-form">
                    <input type="text" id="usernameInput" placeholder="Username..." style="width: 130px;">
                    <button onclick="sendChatRequest()" id="sendRequestBtn">Add</button>
                </div>
                <hr style="border:0; border-bottom: 1px dashed #999;">
                <div id="activeChats">
                    </div>
            </div>
        </div>

        <div class="chat-main">
            <div id="chatArea">
                <div class="chat-header" style="background: #e0e0e0; padding: 5px; border-bottom: 1px solid #999;">
                    Talking to: <span id="chatWithUser" style="font-weight: bold;">Select a user...</span>
                </div>

                <div class="chat-messages" id="chatMessages">
                    <div style="color: #666; font-style: italic; padding: 20px;">Welcome to the chat room...</div>
                </div>

                <div class="chat-input">
                    <table width="100%">
                        <tr>
                            <td width="85%">
                                <input type="text" id="messageInput" style="width: 98%;" placeholder="Type message..." disabled />
                            </td>
                            <td>
                                <button onclick="sendMessage()" id="sendMessageBtn" disabled style="width: 100%;">SEND</button>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="module" src="js/chat.js"></script>
</body>
</html>