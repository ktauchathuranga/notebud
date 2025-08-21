# Scratchpad

A simple note-taking app for university labs. Quick login, temporary storage, auto-delete after 30 days.

## Why Scratchpad?

Perfect for uni labs where you can't access OneDrive/Google Drive due to 2FA hassles. Just username/password login, 4-hour sessions, notes auto-clear in 30 days.

## Features

- **Simple login** - No 2FA required
- **4-hour sessions** - Perfect for max lecture duration  
- **Auto-delete** - Notes vanish after 30 days
- **Modern UI** - Dark theme, responsive design
- **Auto-save** - Saves after 30 seconds of inactivity
- **Edit notes** - Click to modify existing notes

## Quick Start

1. **Clone and start**
```bash
git clone <repo-url>
cd scratchpad
cp .env.example .env
docker-compose up -d
```

2. **Access at** `http://localhost:8080`

## Environment Setup

Edit `.env`:
```env
DB_NAME=scratchpad
DB_USER=admin  
DB_PASS=your_password
MONGO_INITDB_ROOT_USERNAME=admin
MONGO_INITDB_ROOT_PASSWORD=your_password
JWT_SECRET=your_long_random_secret
```

## File Structure

```
src/
├── public/          # Frontend (HTML, CSS, JS)
├── api/             # Backend PHP endpoints
└── ...
```

## Security

- JWT authentication with HttpOnly cookies
- Users only see their own notes
- Input validation and sanitization
- 4-hour session timeout

---

**Perfect for uni students who just want quick, temporary notes without authentication hassles.**
