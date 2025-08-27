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
    <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon/favicon-16x16.png">
    <link rel="manifest" href="favicon/site.webmanifest">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-hover: #2563eb;
            --primary-light: #dbeafe;
            --success-color: #22c55e;
            --success-light: #dcfce7;
            --error-color: #ef4444;
            --error-light: #fef2f2;
            --warning-color: #f59e0b;
            --warning-light: #fef3c7;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --white: #ffffff;
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --border-radius-lg: 16px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--gray-900);
            line-height: 1.6;
        }

        .chat-container {
            display: grid;
            grid-template-columns: 380px 1fr;
            height: calc(100vh - 100px);
            max-width: 1400px;
            margin: 20px auto;
            background: var(--white);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-xl);
        }

        /* Sidebar Styles */
        .chat-sidebar {
            background: var(--white);
            border-right: 1px solid var(--gray-200);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .sidebar-header {
            padding: 24px;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
        }

        .sidebar-header h3 {
            margin: 0 0 16px 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-close-btn {
            display: none;
            background: var(--error-color);
            color: var(--white);
            border: none;
            padding: 8px 16px;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .sidebar-close-btn:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .sidebar-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        /* Connection Status */
        .connection-status {
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            text-align: center;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .connection-status.connected {
            background: var(--success-light);
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .connection-status.disconnected {
            background: var(--error-light);
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .connection-status.connecting {
            background: var(--warning-light);
            color: #d97706;
            border: 1px solid #fed7aa;
        }

        /* New Chat Form */
        .new-chat-form {
            margin-bottom: 24px;
            padding: 20px;
            background: var(--gray-50);
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-200);
        }

        .new-chat-form h4 {
            margin: 0 0 16px 0;
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-700);
        }

        .new-chat-form input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius-sm);
            font-size: 0.875rem;
            margin-bottom: 12px;
            transition: all 0.2s ease;
            background: var(--white);
            color: var(--gray-900);
        }

        .new-chat-form input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .new-chat-form button {
            width: 100%;
            padding: 12px 16px;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius-sm);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .new-chat-form button:hover:not(:disabled) {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .new-chat-form button:disabled {
            background: var(--gray-400);
            cursor: not-allowed;
            transform: none;
        }

        /* Chat Section Headers */
        .chat-section {
            margin-bottom: 24px;
        }

        .chat-section h4 {
            margin: 0 0 12px 0;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            padding: 0 4px;
        }

        /* Chat List Items */
        .chat-list-item {
            padding: 16px;
            margin-bottom: 8px;
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
        }

        .chat-list-item:hover {
            background: var(--primary-light);
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .chat-list-item.active {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
            box-shadow: var(--shadow-lg);
        }

        .chat-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 600;
            font-size: 1.1rem;
            flex-shrink: 0;
            position: relative;
            box-shadow: var(--shadow);
        }

        .chat-avatar::after {
            content: '';
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 3px solid var(--white);
        }

        .chat-avatar.online::after {
            background: var(--success-color);
        }

        .chat-avatar.offline::after {
            background: var(--gray-400);
        }

        .chat-list-content {
            flex: 1;
            min-width: 0;
        }

        .chat-username {
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 0.95rem;
            line-height: 1.2;
        }

        .chat-list-item.active .chat-username {
            color: var(--white);
        }

        .chat-time {
            font-size: 0.8rem;
            opacity: 0.7;
            color: var(--gray-500);
        }

        .chat-list-item.active .chat-time {
            color: rgba(255, 255, 255, 0.8);
        }

        /* Request Items */
        .request-item {
            padding: 20px;
            margin-bottom: 12px;
            background: #fefbff;
            border: 1px solid #e9d5ff;
            border-radius: var(--border-radius);
            position: relative;
        }

        .request-from {
            font-weight: 600;
            color: #7c3aed;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .request-time {
            font-size: 0.8rem;
            color: var(--gray-500);
            margin-bottom: 16px;
        }

        .request-actions {
            display: flex;
            gap: 8px;
        }

        .request-actions button {
            flex: 1;
            padding: 10px 16px;
            border: none;
            border-radius: var(--border-radius-sm);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .request-actions .accept-btn {
            background: var(--success-color);
            color: var(--white);
        }

        .request-actions .accept-btn:hover {
            background: #16a34a;
            transform: translateY(-1px);
        }

        .request-actions .decline-btn {
            background: var(--error-color);
            color: var(--white);
        }

        .request-actions .decline-btn:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        /* Main Chat Area - FIXED LAYOUT */
        .chat-main {
            display: flex;
            flex-direction: column;
            background: var(--white);
            position: relative;
            height: 100%;
            min-height: 0;
            /* Important: allows flex child to shrink */
        }

        .chat-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--white);
            flex-shrink: 0;
            /* Prevent header from shrinking */
            z-index: 10;
            box-shadow: var(--shadow-sm);
        }

        .chat-header-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .chat-header-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 600;
            font-size: 1.3rem;
            position: relative;
            box-shadow: var(--shadow-md);
        }

        .chat-header-avatar::after {
            content: '';
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 3px solid var(--white);
        }

        .chat-header-avatar.online::after {
            background: var(--success-color);
        }

        .chat-header-avatar.offline::after {
            background: var(--gray-400);
        }

        .chat-header-details h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1.2;
        }

        .chat-header-status {
            font-size: 0.875rem;
            color: var(--gray-500);
            margin-top: 4px;
            font-weight: 500;
        }

        .mobile-back-btn {
            display: none;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 10px 20px;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .mobile-back-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        /* Messages Area - FIXED TO TAKE AVAILABLE SPACE */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-height: 0;
            /* Important: allows scrolling */
        }

        .message-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
            max-width: 75%;
        }

        .message-group.own {
            align-self: flex-end;
            align-items: flex-end;
        }

        .message-group.other {
            align-self: flex-start;
            align-items: flex-start;
        }

        .message {
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 2px;
            max-width: 100%;
        }

        .message.own {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            color: var(--white);
            border-bottom-right-radius: 6px;
            box-shadow: var(--shadow);
        }

        .message.other {
            background: var(--white);
            color: var(--gray-900);
            border: 1px solid var(--gray-200);
            border-bottom-left-radius: 6px;
            box-shadow: var(--shadow-sm);
        }

        .message-info {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 8px;
            padding: 0 4px;
            font-weight: 500;
        }

        .message-group.own .message-info {
            text-align: right;
            color: var(--gray-500);
        }

        .message-group.other .message-info {
            text-align: left;
            color: var(--gray-500);
        }

        /* Chat Input - FIXED ALIGNMENT AND VISIBILITY */
        .chat-input {
            padding: 20px 24px;
            border-top: 1px solid var(--gray-200);
            background: var(--white);
            display: flex;
            gap: 12px;
            align-items: center;
            /* Changed from flex-end to center for better alignment */
            flex-shrink: 0;
            min-height: 88px;
            /* Consistent height */
            z-index: 5;
        }

        .chat-input-wrapper {
            flex: 1;
            position: relative;
        }

        .chat-input input {
            width: 100%;
            height: 48px;
            /* Fixed height for consistent alignment */
            padding: 12px 20px;
            border: 2px solid var(--gray-300);
            border-radius: 24px;
            font-size: 0.95rem;
            font-family: inherit;
            outline: none;
            transition: all 0.2s ease;
            background: var(--white);
            /* Ensure white background */
            color: var(--gray-900);
            /* Dark text for visibility */
            line-height: 1.4;
            resize: none;
        }

        .chat-input input::placeholder {
            color: var(--gray-500);
            /* Lighter placeholder text */
            opacity: 1;
        }

        .chat-input input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: var(--white);
        }

        .chat-input input:disabled {
            background: var(--gray-100);
            color: var(--gray-500);
            border-color: var(--gray-200);
            cursor: not-allowed;
        }

        .chat-input button {
            height: 48px;
            /* Match input height exactly */
            padding: 0 24px;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 24px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            font-family: inherit;
            transition: all 0.2s ease;
            white-space: nowrap;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 80px;
            /* Minimum width for button */
        }

        .chat-input button:hover:not(:disabled) {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .chat-input button:disabled {
            background: var(--gray-400);
            cursor: not-allowed;
            transform: none;
            box-shadow: var(--shadow-sm);
        }

        /* Empty State */
        .empty-chat {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .empty-chat-content {
            max-width: 400px;
            padding: 40px;
        }

        .empty-chat-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--white);
            box-shadow: var(--shadow-lg);
        }

        .empty-chat h3 {
            margin: 0 0 16px 0;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
        }

        .empty-chat p {
            margin: 0;
            font-size: 1rem;
            line-height: 1.6;
            color: var(--gray-600);
        }

        /* Notifications */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--white);
            color: var(--gray-900);
            padding: 16px 20px;
            border-radius: var(--border-radius);
            z-index: 1000;
            box-shadow: var(--shadow-xl);
            border-left: 4px solid var(--success-color);
            min-width: 320px;
            animation: slideIn 0.3s ease;
            font-weight: 500;
        }

        .notification.error {
            border-left-color: var(--error-color);
        }

        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .chat-container {
                grid-template-columns: 320px 1fr;
                margin: 16px;
                height: calc(100vh - 32px);
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 0;
            }

            .chat-container {
                grid-template-columns: 1fr;
                height: 100vh;
                margin: 0;
                border-radius: 0;
            }

            .chat-sidebar {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 20;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                box-shadow: var(--shadow-xl);
            }

            .chat-sidebar.show {
                transform: translateX(0);
            }

            .sidebar-close-btn {
                display: block;
            }

            .mobile-back-btn {
                display: block;
            }

            .chat-header {
                padding: 16px 20px;
            }

            .chat-messages {
                padding: 20px 16px;
            }

            .message-group {
                max-width: 85%;
            }

            .message {
                padding: 12px 16px;
                font-size: 0.9rem;
            }

            .chat-input {
                padding: 16px 20px;
                min-height: 80px;
            }

            .notification {
                top: 10px;
                right: 10px;
                left: 10px;
                min-width: auto;
            }

            #mobileSidebarBtn {
                display: block !important;
                background: var(--primary-color);
                color: var(--white);
                border: none;
                padding: 8px 16px;
                border-radius: var(--border-radius-sm);
                cursor: pointer;
                font-size: 0.875rem;
                font-weight: 600;
                transition: all 0.2s ease;
            }

            #mobileSidebarBtn:hover {
                background: var(--primary-hover);
                transform: translateY(-1px);
            }
        }

        @media (max-width: 480px) {
            .chat-input {
                padding: 12px 16px;
                min-height: 72px;
                gap: 8px;
            }

            .chat-input input {
                font-size: 16px;
                /* Prevents zoom on iOS */
                height: 44px;
                padding: 10px 16px;
            }

            .chat-input button {
                height: 44px;
                padding: 0 20px;
                min-width: 70px;
            }

            .message-group {
                max-width: 90%;
            }

            .message {
                padding: 10px 14px;
                font-size: 0.9rem;
            }

            .chat-header {
                padding: 12px 16px;
            }

            .chat-header-avatar {
                width: 48px;
                height: 48px;
                font-size: 1.1rem;
            }

            .request-actions {
                flex-direction: column;
                gap: 8px;
            }

            .sidebar-header {
                padding: 20px;
            }

            .sidebar-body {
                padding: 16px;
            }

            .new-chat-form {
                padding: 16px;
                margin-bottom: 20px;
            }
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Custom Scrollbar */
        .chat-messages::-webkit-scrollbar,
        .sidebar-body::-webkit-scrollbar {
            width: 6px;
        }

        .chat-messages::-webkit-scrollbar-track,
        .sidebar-body::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 3px;
        }

        .chat-messages::-webkit-scrollbar-thumb,
        .sidebar-body::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 3px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover,
        .sidebar-body::-webkit-scrollbar-thumb:hover {
            background: var(--gray-400);
        }

        /* Loading state */
        .loading-placeholder {
            color: var(--gray-400);
            font-size: 0.875rem;
            text-align: center;
            padding: 20px;
            font-style: italic;
        }

        /* Enhanced focus states */
        button:focus-visible,
        input:focus-visible {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        /* Ensure chat area is always visible when active */
        #chatArea {
            display: none;
            height: 100%;
            flex-direction: column;
        }

        #chatArea.active {
            display: flex !important;
        }
    </style>
</head>

<body>
    <header class="topbar">
        <h1>notebud</h1>
        <div class="session-controls">
            <button id="mobileSidebarBtn" style="display: none;">💬 Chats</button>
            <a href="/notes" style="color: var(--text); text-decoration: none;">← Back to Notes</a>
            <button id="logoutBtn">Logout</button>
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
