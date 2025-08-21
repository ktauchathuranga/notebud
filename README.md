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
- **Flexible database** - Works with local Docker MongoDB or cloud Atlas

## Quick Start

### Option 1: Local Development (Docker MongoDB)
```bash
git clone <repo-url>
cd scratchpad
cp .env.local .env
# Edit .env with your passwords
docker-compose up -d
```

### Option 2: Cloud Database (MongoDB Atlas)
```bash
git clone <repo-url>
cd scratchpad
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
DB_NAME=scratchpad
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
DB_NAME=scratchpad
DB_USER=scratchpad_user
DB_PASS=your_atlas_password
MONGODB_URI=mongodb+srv://scratchpad_user:password@cluster0.xxxxx.mongodb.net/scratchpad?retryWrites=true&w=majority
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
- 4-hour session timeout
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
```

### Database Management
- **Local**: MongoDB runs in Docker container
- **Cloud**: Managed by MongoDB Atlas
- **TTL Index**: Automatically deletes notes after 30 days
- **Collections**: `users` and `notes`

---

**Perfect for uni students who just want quick, temporary notes without authentication hassles.**
