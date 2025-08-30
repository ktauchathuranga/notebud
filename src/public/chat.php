<?php
// src/public/chat.php
// Server-side protect this page by checking JWT cookie and redirecting to login if invalid

require_once __DIR__ . '/../api/auth.php';
$payload = null;
try {
    $payload = require_auth_or_redirect();
} catch (Exception $e) {
    header('Location: /login');
    exit;
}

// Expose JWT and user info to JS
$exp = $payload['exp'] ?? null;
$isPermanent = $payload['permanent'] ?? false;
$sessionId = $payload['session_id'] ?? null;
$userId = $payload['user_id'] ?? null;

// Get the actual JWT token from cookie to pass to WebSocket
$jwtToken = $_COOKIE['token'] ?? null;
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
    <title>Chat - notebud</title>
    <link rel="stylesheet" href="css/style.css" />

    <!-- Expose session info and JWT token to JS -->
    <script>
        window.JWT_EXP = <?= $exp ? intval($exp) : 'null' ?>;
        window.IS_PERMANENT = <?= json_encode($isPermanent) ?>;
        window.SESSION_ID = <?= json_encode($sessionId) ?>;
        window.USER_ID = <?= json_encode($userId) ?>;
        window.JWT_TOKEN = <?= json_encode($jwtToken) ?>; // Expose JWT token for WebSocket auth
        console.log('Chat page loaded with user ID:', <?= json_encode($userId) ?>);
    </script>
</head>

<body class="chat-page">
    <header class="topbar">
        <h1>notebud</h1>
        <div class="session-controls">
            <button id="mobileSidebarBtn" style="display: none;">💬 Chats</button>
            <a href="/notes" style="color: var(--text); text-decoration: none;">← Back to Notes</a>
            <button id="logoutBtn" class="logout-btn">Logout</button>
        </div>
    </header>

    <main class="chat-container" id="chatContainer">
        <div class="chat-sidebar" id="chatSidebar">
            <div class="sidebar-header">
                <h3>
                    Messages
                    <button class="sidebar-close-btn" id="sidebarCloseBtn">✕</button>
                </h3>

                <div id="connectionStatus" class="connection-status connecting">
                    🔄 Connecting to chat server...
                </div>
            </div>

            <div class="sidebar-body">
                <div class="new-chat-form">
                    <h4>Start New Chat</h4>
                    <input type="text" id="usernameInput" placeholder="Enter username..." />
                    <button onclick="sendChatRequest()" id="sendRequestBtn" disabled>Send Request</button>
                </div>

                <div id="chatRequests" class="chat-section">
                    <h4>Pending Requests</h4>
                    <div id="requestsList">
                        <div class="loading-placeholder">Loading requests...</div>
                    </div>
                </div>

                <div id="activeChats" class="chat-section">
                    <h4>Active Chats</h4>
                    <div id="chatsList">
                        <div class="loading-placeholder">Loading chats...</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="chat-main">
            <div id="noChatSelected" class="empty-chat">
                <div class="empty-chat-content">
                    <div class="empty-chat-icon">💬</div>
                    <h3>Welcome to notebud Chat</h3>
                    <p>Select a conversation to start messaging or create a new chat to connect with your colleagues.</p>
                </div>
            </div>

            <div id="chatArea">
                <div class="chat-header">
                    <div class="chat-header-info">
                        <div class="chat-header-avatar" id="chatHeaderAvatar">
                            <span id="chatHeaderInitial"></span>
                        </div>
                        <div class="chat-header-details">
                            <h3 id="chatWithUser"></h3>
                            <div class="chat-header-status" id="chatHeaderStatus"></div>
                        </div>
                    </div>
                    <button class="mobile-back-btn" id="mobileBackBtn">← Back</button>
                </div>

                <div class="chat-messages" id="chatMessages">
                </div>

                <div class="chat-input">
                    <div class="chat-input-wrapper">
                        <input type="text" id="messageInput" placeholder="Type a message..." disabled />
                    </div>
                    <button onclick="sendMessage()" id="sendMessageBtn" disabled>Send</button>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Mobile sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileSidebarBtn = document.getElementById('mobileSidebarBtn');
            const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
            const mobileBackBtn = document.getElementById('mobileBackBtn');
            const chatSidebar = document.getElementById('chatSidebar');
            const chatContainer = document.getElementById('chatContainer');

            function showMobileControls() {
                const isMobile = window.innerWidth <= 768;
                if (!isMobile) {
                    chatSidebar.classList.remove('show');
                    chatContainer.classList.remove('chat-open');
                }
            }

            function openSidebar() {
                chatSidebar.classList.add('show');
                chatContainer.classList.remove('chat-open');
            }

            function closeSidebar() {
                chatSidebar.classList.remove('show');
            }

            function openChat() {
                if (window.innerWidth <= 768) {
                    chatContainer.classList.add('chat-open');
                    chatSidebar.classList.remove('show');
                }
            }

            function backToChats() {
                if (window.innerWidth <= 768) {
                    chatContainer.classList.remove('chat-open');
                    chatSidebar.classList.add('show');
                    document.getElementById('noChatSelected').style.display = 'flex';
                    const chatArea = document.getElementById('chatArea');
                    chatArea.style.display = 'none';
                    chatArea.classList.remove('active');
                }
            }

            // Event listeners
            mobileSidebarBtn.addEventListener('click', openSidebar);
            sidebarCloseBtn.addEventListener('click', closeSidebar);
            mobileBackBtn.addEventListener('click', backToChats);

            // Handle window resize
            window.addEventListener('resize', showMobileControls);
            showMobileControls();

            // Override the openChat function globally
            window.originalOpenChat = window.openChat;
            window.openChat = function(chatId, withUser, isOnline) {
                console.log('Opening chat UI for:', withUser);

                // Call original function if it exists
                if (window.originalOpenChat) {
                    window.originalOpenChat(chatId, withUser, isOnline);
                }

                // Show chat area and hide empty state
                const noChatSelected = document.getElementById('noChatSelected');
                const chatArea = document.getElementById('chatArea');

                if (noChatSelected) noChatSelected.style.display = 'none';
                if (chatArea) {
                    chatArea.style.display = 'flex';
                    chatArea.classList.add('active');
                }

                // Update header avatar and status
                const chatHeaderAvatar = document.getElementById('chatHeaderAvatar');
                const chatHeaderInitial = document.getElementById('chatHeaderInitial');
                const chatHeaderStatus = document.getElementById('chatHeaderStatus');

                if (chatHeaderAvatar && chatHeaderInitial && chatHeaderStatus) {
                    chatHeaderInitial.textContent = withUser.charAt(0).toUpperCase();
                    chatHeaderAvatar.className = `chat-header-avatar ${isOnline ? 'online' : 'offline'}`;
                    chatHeaderAvatar.style.background = window.getAvatarColor ? window.getAvatarColor(withUser) : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                    chatHeaderStatus.textContent = isOnline ? '🟢 Online' : '⚫ Last seen recently';
                }

                openChat();
            };

            // Helper function to get user initials
            window.getUserInitials = function(username) {
                return username.split(' ').map(name => name.charAt(0)).join('').toUpperCase().slice(0, 2);
            };

            // Helper function to generate avatar color
            window.getAvatarColor = function(username) {
                const colors = [
                    'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                    'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                    'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                    'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                    'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
                    'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)',
                    'linear-gradient(135deg, #ff8a80 0%, #ea6100 100%)'
                ];
                let hash = 0;
                for (let i = 0; i < username.length; i++) {
                    hash = username.charCodeAt(i) + ((hash << 5) - hash);
                }
                return colors[Math.abs(hash) % colors.length];
            };
        });
    </script>

    <script src="js/chat.js"></script>
</body>

</html>
