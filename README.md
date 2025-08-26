# notebud

A simple note-taking app designed for university labs with flexible session management. Quick login with both temporary and permanent session options, automatic cleanup, and seamless cloud or local database support.

## Why NoteBud?

Perfect for uni labs where you can't access OneDrive/Google Drive due to 2FA hassles. Choose between temporary sessions (4-hour auto-logout) or permanent sessions (logout only when you want). Notes auto-clear in 30 days to keep things tidy.

## Features

### Core Features
- **Flexible login** - Choose temporary (4h) or permanent sessions
- **Session management** - Permanent users can logout all temporary sessions
- **Simple authentication** - No 2FA required
- **Auto-delete** - Notes vanish after 30 days
- **Modern UI** - Dark theme, responsive design with inline SVG icons
- **Auto-save** - Saves after 30 seconds of inactivity
- **Edit notes** - Click to modify existing notes
- **Search functionality** - Quick search through your notes
- **Character counter** - Track note length with warnings

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
docker-compose up -d web
```

Access at `http://localhost:8080`

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
docker-compose up -d web
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
    docker-compose up -d web
else
    echo "Usage: ./switch.sh [local|cloud]"
fi
```

Usage: `./switch.sh local` or `./switch.sh cloud`

## Security

- JWT authentication with HttpOnly cookies
- Users only see their own notes
- Input validation and sanitization
- Flexible session timeout (4-hour temporary or permanent)
- Session management for permanent users
- Auto-deletion after 30 days
- HTTPS support for production

## Development

### Requirements
- Docker and Docker Compose
- PHP 8+ (if running without Docker)
- MongoDB (local) or Atlas account (cloud)

### Local Development
```bash
# Start local development
docker-compose up -d

# View logs
docker-compose logs -f web

# Stop services  
docker-compose down

# Rebuild containers
docker-compose build --no-cache
```

### Database Management
- **Local**: MongoDB runs in Docker container
- **Cloud**: Managed by MongoDB Atlas
- **TTL Index**: Automatically deletes notes after 30 days
- **Collections**: `users` and `notes`

### Project Structure
```
notebud
├── docker
│   ├── mongo
│   │   └── init-mongo.js
│   └── php
│       ├── 000-default.conf
│       └── Dockerfile
├── docker-compose.yml
├── init-mongo.js
├── README.md
├── src
│   ├── api
│   │   ├── auth.php
│   │   ├── db.php
│   │   ├── delete_note.php
│   │   ├── get_notes.php
│   │   ├── jwt.php
│   │   ├── login.php
│   │   ├── logout_all_temp.php
│   │   ├── logout.php
│   │   ├── register.php
│   │   ├── save_note.php
│   │   └── update_note.php
│   └── public
│       ├── css
│       │   └── style.css
│       ├── favicon
│       │   ├── android-chrome-192x192.png
│       │   ├── android-chrome-512x512.png
│       │   ├── apple-touch-icon.png
│       │   ├── favicon-16x16.png
│       │   ├── favicon-32x32.png
│       │   ├── favicon.ico
│       │   └── site.webmanifest
│       ├── index.php
│       ├── js
│       │   └── notes.js
│       ├── login.html
│       ├── notes.php
│       └── register.html
└── switch.sh
```

## Contributing

We welcome contributions to NoteBud! Please follow these guidelines:

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

### Development Guidelines

#### Code Style
- **PHP**: Follow PSR-12 coding standards
- **JavaScript**: Use modern ES6+ features, maintain consistent indentation
- **CSS**: Use BEM methodology for class naming
- **HTML**: Semantic markup, accessibility considerations

#### Commit Messages
Use conventional commit format:
```
type(scope): description

feat(auth): add permanent login session support
fix(notes): resolve auto-save timing issue
docs(readme): update installation instructions
```

Types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

#### Testing
- Test your changes with both local MongoDB and Atlas configurations
- Verify functionality in multiple browsers
- Test both temporary and permanent session flows
- Ensure mobile responsiveness

### Pull Request Process

1. **Update documentation** if needed
2. **Test thoroughly** with both database configurations
3. **Create detailed PR description** with:
   - What changes were made
   - Why the changes are necessary
   - How to test the changes
   - Screenshots for UI changes

4. **PR Title Format**:
   ```
   feat: Add session management for permanent users
   fix: Resolve note deletion confirmation dialog
   docs: Update contributing guidelines
   ```

### Issue Reporting

When reporting issues, please include:

- **Environment details** (OS, browser, Docker version)
- **Steps to reproduce** the issue
- **Expected vs actual behavior**
- **Screenshots** if applicable
- **Database configuration** (local/cloud)

### Feature Requests

For new features:
- Check existing issues to avoid duplicates
- Provide clear use case and rationale
- Consider implementation complexity
- Discuss breaking changes

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

3. **Access application**: `http://localhost:8080`

4. **Monitor logs**:
   ```bash
   docker-compose logs -f web
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

### Community

- Be respectful and constructive in discussions
- Help review other contributors' PRs
- Share knowledge and best practices
- Follow the code of conduct

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

If you encounter issues or have questions:
- Check existing [GitHub Issues](https://github.com/yourusername/notebud/issues)
- Create a new issue with detailed information
- Join discussions in existing issues

---

**Perfect for university students who need quick, flexible note-taking without authentication hassles.**
