# notebud

A simple note-taking and chat app designed for university labs with flexible session management. Quick login with both temporary and permanent session options, automatic cleanup, and seamless cloud or local database support.

![notebud-preview](https://github.com/user-attachments/assets/a4ec8595-59ff-49d6-b2e1-1ebbee244d02)


## Why notebud?

Perfect for uni labs where you can't access OneDrive/Google Drive due to 2FA hassles. Choose between temporary sessions (4-hour auto-logout) or permanent sessions (logout only when you want). Notes auto-clear in 30 days to keep things tidy. Connect with classmates through real-time chat.

> [!TIP]
> - **Visit us at**: [notebud.cc](https://notebud.cc)
> - Alternative Domain: [notebud-3x6z.onrender.com](https://notebud-3x6z.onrender.com/)

## ⚠️ Important User Responsibility

**PRIVACY & SECURITY WARNING**: notebud is designed for temporary academic use. Users are solely responsible for:

- **Never sharing private, sensitive, or confidential information** through notes or chat
- **Not using the platform for illegal activities** or inappropriate content
- **Understanding that data may be visible to system administrators** for maintenance purposes
- **Accepting that all content is automatically deleted after 30 days**
- **Using the service at your own risk** - we provide no guarantees of data security or privacy

By using notebud, you acknowledge these limitations and agree to use the platform responsibly and legally.

## Features

### Core Features
- **Flexible login** - Choose temporary (4h) or permanent sessions
- **Session management** - Permanent users can logout all temporary sessions
- **Simple authentication** - No 2FA required
- **Auto-delete** - Notes and chat data vanish after 30 days
- **Modern UI** - Dark theme, responsive design with inline SVG icons
- **Auto-save** - Notes save after 30 seconds of inactivity
- **Edit notes** - Click to modify existing notes
- **Search functionality** - Quick search through your notes
- **Character counter** - Track note length with warnings
- **Share notes** - You can share notes using usernames
- **20Mb storage** - You can store documets and zip files total size of 20Mb's
- **Share files** - You can share files as well with other users

### Chat Features
- **Real-time messaging** - WebSocket-powered instant communication
- **Username-based contacts** - Find and chat with other users by username
- **Chat requests** - Send and receive connection requests
- **Session status** - See when users are online/offline
- **Mobile responsive** - Full chat functionality on mobile devices
- **Professional interface** - Clean, modern chat experience

### Session Types
- **Temporary Sessions**: 4-hour duration, perfect for lecture sessions
- **Permanent Sessions**: Stay logged in until manual logout
- **Session Control**: Permanent users can logout all temporary sessions across devices

### Database Options
- **Local Docker MongoDB** - Full local development setup
- **MongoDB Atlas** - Cloud database with free tier support

## Quick Start

### Option 1: Local Development (Docker MongoDB)
```bash
git clone https://github.com/yourusername/notebud.git
cd notebud
cp .env.local .env
# Edit .env with your passwords
docker-compose up -d
```

### Option 2: Cloud Database (MongoDB Atlas)
```bash
git clone https://github.com/yourusername/notebud.git
cd notebud
cp .env.cloud .env
# Edit .env with your Atlas connection string
docker-compose up -d web websocket
```

Access the app at `http://localhost:8090` for local development or visit [https://notebud.cc](https://notebud.cc) for the live version.

## Architecture

notebud consists of three main services:

1. **Web Service** (PHP) - Handles authentication, notes, and serves the frontend
2. **WebSocket Service** (Rust) - Powers real-time chat functionality  
3. **MongoDB** - Stores users, notes, chat messages, and chat requests

### Service Ports
- **Web**: `8090` (HTTP interface)
- **WebSocket**: `8092` (Chat server)
- **MongoDB**: `27017` (Database)

## Database Configuration

### Local Docker MongoDB
Use `.env.local` configuration:
```env
DB_HOST=mongo
DB_PORT=27017
DB_NAME=notebud
DB_USER=admin
DB_PASS=your_secure_password
MONGO_INITDB_ROOT_USERNAME=admin
MONGO_INITDB_ROOT_PASSWORD=your_secure_password
MONGODB_URI=
JWT_SECRET=your_very_long_random_secret_key
```

### MongoDB Atlas Cloud
Use `.env.cloud` configuration:
```env
DB_HOST=cluster0.xxxxx.mongodb.net
DB_NAME=notebud
DB_USER=notebud_user
DB_PASS=your_atlas_password
MONGODB_URI=mongodb+srv://notebud_user:password@cluster0.xxxxx.mongodb.net/notebud?retryWrites=true&w=majority
JWT_SECRET=your_very_long_random_secret_key
```

## Setting Up MongoDB Atlas (Cloud)

1. **Create account** at [mongodb.com/atlas](https://mongodb.com/atlas)
2. **Create free M0 cluster**
3. **Add database user** with read/write permissions
4. **Allow network access** from anywhere (0.0.0.0/0)
5. **Get connection string** and update `.env.cloud`
6. **Copy to .env**: `cp .env.cloud .env`

## Switching Between Databases

### Manual Method
```bash
# Use local Docker MongoDB
cp .env.local .env
docker-compose up -d

# Use MongoDB Atlas
cp .env.cloud .env
docker-compose up -d web websocket
```

### Script Method
Create `switch.sh`:
```bash
#!/bin/bash
if [ "$1" = "local" ]; then
    cp .env.local .env
    echo "Switched to local Docker MongoDB"
    docker-compose up -d
elif [ "$1" = "cloud" ]; then
    cp .env.cloud .env  
    echo "Switched to MongoDB Atlas"
    docker-compose up -d web websocket
else
    echo "Usage: ./switch.sh [local|cloud]"
fi
```

Usage: `./switch.sh local` or `./switch.sh cloud`

## Chat System

### How Chat Works

1. **User Discovery**: Find other users by their username
2. **Connection Requests**: Send chat requests to initiate conversations
3. **Real-time Messaging**: Once accepted, chat in real-time via WebSocket
4. **Session Awareness**: See when contacts are online/offline
5. **Mobile Support**: Full functionality on mobile devices

### Chat Security Considerations

- All chat messages are stored temporarily and auto-deleted after 30 days
- Users can only see their own conversations
- WebSocket connections are authenticated via JWT tokens
- No end-to-end encryption - messages are visible to system administrators
- Chat history is limited to active sessions

### Chat Usage Guidelines

- Use professional, appropriate language
- Respect other users' privacy and time
- Remember that conversations are not permanently stored
- Report inappropriate behavior through proper channels
- Understand that chat logs may be reviewed for maintenance purposes

## Security

- JWT authentication with HttpOnly cookies
- Users only see their own notes and conversations
- Input validation and sanitization
- Flexible session timeout (4-hour temporary or permanent)
- Session management for permanent users
- Auto-deletion after 30 days for all data
- WebSocket authentication
- HTTPS support for production

## Development

### Requirements
- Docker and Docker Compose
- PHP 8+ (if running without Docker)
- Rust 1.70+ (for WebSocket service development)
- MongoDB (local) or Atlas account (cloud)

### Local Development
```bash
# Start all services
docker-compose up -d

# View logs for specific services
docker-compose logs -f web
docker-compose logs -f websocket
docker-compose logs -f mongo

# Stop services  
docker-compose down

# Rebuild containers
docker-compose build --no-cache
```

### Database Management
- **Local**: MongoDB runs in Docker container
- **Cloud**: Managed by MongoDB Atlas
- **TTL Index**: Automatically deletes notes and chat data after 30 days
- **Collections**: `users`, `notes`, `chat_requests`, `chats`, `chat_messages`

### WebSocket Development

The chat system uses a Rust-based WebSocket server for real-time communication:

```bash
# Access WebSocket container
docker-compose exec websocket bash

# View WebSocket logs
docker-compose logs -f websocket

# Rebuild WebSocket service
docker-compose build websocket
```

### Project Structure
```
notebud
├── docker
│   ├── mongo
│   │   └── init-mongo.js
│   ├── php
│   │   ├── 000-default.conf
│   │   ├── Dockerfile
│   │   └── supervisord.conf
│   └── websocket
│       └── Dockerfile
├── docker-compose.yml
├── init-mongo.js
├── README.md
├── src
│   ├── api
│   │   ├── auth.php
│   │   ├── db.php
│   │   ├── delete_note.php
│   │   ├── get_chat_requests.php
│   │   ├── get_notes.php
│   │   ├── get_online_users.php
│   │   ├── jwt.php
│   │   ├── login.php
│   │   ├── logout_all_temp.php
│   │   ├── logout.php
│   │   ├── register.php
│   │   ├── reset_password.php
│   │   ├── save_note.php
│   │   └── update_note.php
│   ├── public
│   │   ├── 404.html
│   │   ├── chat.php
│   │   ├── css
│   │   │   └── style.css
│   │   ├── favicon
│   │   ├── index.php
│   │   ├── js
│   │   │   ├── chat.js
│   │   │   └── notes.js
│   │   ├── login.html
│   │   ├── notes.php
│   │   ├── register.html
│   │   └── reset-password.html
│   └── websocket
│       └── notebud_websocket
│           ├── Cargo.lock
│           ├── Cargo.toml
│           └── src
│               ├── auth.rs
│               ├── database.rs
│               ├── handlers.rs
│               ├── main.rs
│               └── types.rs
└── switch.sh
```

## Contributing

We welcome contributions to notebud! Please follow these guidelines:

### Getting Started

1. **Fork the repository** on GitHub
2. **Clone your fork** locally:
   ```bash
   git clone https://github.com/yourusername/notebud.git
   cd notebud
   ```
3. **Create a feature branch**:
   ```bash
   git checkout -b feature/your-feature-name
   ```
### Environment File Handling

This repository contains `.env.local` and `.env.cloud` files as **templates only**.
- Do **not** commit real passwords or API keys.
- Treat these files as "locked" — changes should not be pushed.

To prevent accidental commits, run the following after cloning:

```bash
git update-index --skip-worktree .env.local
git update-index --skip-worktree .env.cloud
```

#### Commit Messages
Use conventional commit format:
```
type(scope): description

feat(auth): add permanent login session support
feat(chat): implement real-time messaging system
fix(notes): resolve auto-save timing issue
docs(readme): update installation instructions
```

Types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

#### Testing
- Test your changes with both local MongoDB and Atlas configurations
- Verify functionality in multiple browsers
- Test both temporary and permanent session flows
- Test chat functionality with multiple users
- Ensure mobile responsiveness for both notes and chat

### Pull Request Process

1. **Update documentation** if needed
2. **Test thoroughly** with both database configurations
3. **Test chat features** with multiple browser sessions
4. **Create detailed PR description** with:
   - What changes were made
   - Why the changes are necessary
   - How to test the changes
   - Screenshots for UI changes

4. **PR Title Format**:
   ```
   feat: Add session management for permanent users
   feat(chat): Add real-time messaging system
   fix: Resolve note deletion confirmation dialog
   docs: Update contributing guidelines
   ```

### Issue Reporting

When reporting issues, please include:

- **Environment details** (OS, browser, Docker version)
- **Service details** (which service: web, websocket, mongo)
- **Steps to reproduce** the issue
- **Expected vs actual behavior**
- **Screenshots** if applicable
- **Database configuration** (local/cloud)
- **Browser console errors** (if applicable)

### Feature Requests

For new features:
- Check existing issues to avoid duplicates
- Provide clear use case and rationale
- Consider implementation complexity
- Discuss breaking changes
- Specify if feature affects notes, chat, or both

### Development Setup

1. **Environment setup**:
   ```bash
   cp .env.local .env
   # Edit database credentials
   ```

2. **Start development environment**:
   ```bash
   docker-compose up -d
   ```

3. **Access application**: `http://localhost:8090`

4. **Monitor logs**:
   ```bash
   # All services
   docker-compose logs -f
   
   # Specific service
   docker-compose logs -f websocket
   ```

### Code Review Checklist

Before submitting:
- [ ] Code follows project style guidelines
- [ ] All tests pass
- [ ] Documentation updated
- [ ] No sensitive data in commits
- [ ] Changes work with both database options
- [ ] UI changes are responsive
- [ ] Session management works correctly
- [ ] Chat functionality tested with multiple users
- [ ] WebSocket connections handle disconnections gracefully

### Community

- Be respectful and constructive in discussions
- Help review other contributors' PRs
- Share knowledge and best practices
- Follow the code of conduct

## Privacy & Legal Disclaimers

### Data Handling
- All user data (notes, messages, accounts) is automatically deleted after 30 days
- System administrators may access data for maintenance and security purposes
- No data recovery is possible after deletion
- We do not guarantee data security, privacy, or availability

### User Responsibilities
- Users must not share sensitive, private, or confidential information
- Users must comply with all applicable laws and regulations
- Users must not engage in harassment, illegal activities, or inappropriate behavior
- Users acknowledge that conversations and notes are not private or secure

### Limitation of Liability
- notebud is provided "as-is" without warranties of any kind
- We are not liable for any data loss, privacy breaches, or damages
- Users assume all risks associated with using the platform
- The service may be discontinued or modified at any time

### Academic Use Only
This platform is designed specifically for temporary academic collaboration and note-taking in university lab environments. It is not suitable for:
- Sensitive or confidential communications
- Long-term data storage
- Business or commercial use
- Storage of personally identifiable information

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

If you encounter issues or have questions:
- Check existing [GitHub Issues](https://github.com/yourusername/notebud/issues)
- Create a new issue with detailed information
- Join discussions in existing issues
- Visit [https://notebud.cc](https://notebud.cc) for the live application

---

**Perfect for university students who need quick, flexible note-taking and communication without authentication hassles. Use responsibly and at your own risk.**
