# notebud

A note-taking and file-sharing app built for university labs. Simple username-based login, no 2FA hassles. Create notes with Markdown support, upload files, and share both with classmates by username.

![notebud-preview](https://github.com/user-attachments/assets/a4ec8595-59ff-49d6-b2e1-1ebbee244d02)

> [!TIP]
> **Live at**: [notebud.cc](https://notebud.cc) | [notebud-3x6z.onrender.com](https://notebud-3x6z.onrender.com/)

## Features

- **Username-based auth** -- register and login with just a username and password
- **Notes** -- create, edit, delete, and search notes with Markdown rendering
- **File uploads** -- upload files up to 10MB, download and manage them
- **Sharing** -- share notes and files with other users by username
- **Notifications** -- in-app notifications when someone shares content with you
- **Settings** -- update profile, change password, manage two-factor authentication, delete account
- **Dark/light mode** -- appearance toggle
- **Responsive** -- works on desktop and mobile

## Tech Stack

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: Livewire 4, Flux UI 2.9, Tailwind CSS 4
- **Auth**: Laravel Fortify (username-based)
- **Testing**: Pest 4
- **Linting**: Laravel Pint
- **CI**: GitHub Actions (lint + tests on PHP 8.4/8.5)

### Production Infrastructure

- **Database**: MySQL (Aiven) with SSL
- **File storage**: Cloudflare R2 (S3-compatible)
- **Hosting**: Render.com via Docker
- **Dev defaults**: SQLite + local disk storage

## Local Development

### Requirements

- PHP 8.2+
- Composer
- Node.js 20+
- SQLite

### Setup

```bash
git clone https://github.com/ktauchathuranga/notebud.git
cd notebud
git checkout laravel

composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm run build
```

### Running

```bash
composer dev
```

This starts the dev server, queue worker, log watcher, and Vite in parallel. Access the app at `http://localhost:8000`.

### Commands

| Command | Description |
|---|---|
| `composer dev` | Start all dev services |
| `composer test` | Run linter + tests |
| `composer lint` | Auto-fix code style with Pint |
| `composer lint:check` | Check code style without fixing |
| `php artisan test` | Run tests only |

## Docker

The app ships with a production-ready Docker setup using PHP-FPM + Nginx + Supervisor.

### Build and Run

```bash
docker-compose up -d
```

This starts three services:

| Service | Purpose |
|---|---|
| `notebud-app` | Web server (port 8000) |
| `notebud-queue` | Queue worker |
| `notebud-migrate` | Runs migrations on startup |

### Dockerfile

Multi-stage build:
1. **Node stage** -- installs npm deps and runs `vite build`
2. **PHP stage** -- PHP 8.4-fpm-alpine with extensions (pdo_mysql, gd, zip, intl, opcache), Nginx, and Supervisor

## Configuration

### Environment Switching

The app uses environment variables to switch between local and production infrastructure. No code changes needed.

**Database**: Set `DB_CONNECTION` to `sqlite` (local) or `mysql` (production).

**File storage**: Set `UPLOADS_DISK` to `uploads` (local disk, default) or `r2` (Cloudflare R2).

### Production Environment

Copy `.env.production` and fill in the values:

```env
DB_CONNECTION=mysql
DB_HOST=your-mysql-host
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password
MYSQL_ATTR_SSL_CA=/var/www/html/docker/ca.pem

UPLOADS_DISK=r2
CLOUDFLARE_R2_ACCESS_KEY_ID=your_key
CLOUDFLARE_R2_SECRET_ACCESS_KEY=your_secret
CLOUDFLARE_R2_BUCKET=your_bucket
CLOUDFLARE_R2_ENDPOINT=https://your-account-id.r2.cloudflarestorage.com
```

### Render Deployment

1. Connect your GitHub repo to Render as a Web Service
2. Set the build context to the repo root
3. Add environment variables from `.env.production` in the Render dashboard
4. Render will build the Docker image and deploy automatically

## Project Structure

```
app/
  Http/Controllers/       -- FileDownloadController
  Livewire/
    Actions/              -- Logout
    Files/                -- FileIndex, FileUpload
    Notes/                -- NoteIndex, NoteCreate, NoteEdit, NoteShow
    Settings/             -- Profile, Password, Appearance, TwoFactor, DeleteUserForm
    Shares/               -- IncomingShares, ShareModal
    NotificationBell.php
  Models/                 -- User, Note, File, Share, Notification
config/
  filesystems.php         -- Local uploads + R2 disk config
database/
  migrations/             -- Users, cache, jobs, two-factor columns
docker/
  ca.pem                  -- SSL CA certificate for production DB
  entrypoint.sh           -- Container entrypoint
  nginx.conf              -- Nginx config
  supervisord.conf        -- Supervisor config (PHP-FPM + Nginx)
resources/views/
  livewire/               -- All Livewire component views
  layouts/                -- App and auth layouts
  components/             -- Shared UI components
routes/
  web.php                 -- Main routes (notes, files, shares)
  settings.php            -- Settings routes (profile, password, appearance)
tests/
  Feature/                -- Auth, notes, files, shares, settings, dashboard tests
```

## Testing

Tests use SQLite in-memory and run with Pest:

```bash
php artisan test
```

CI runs on push/PR to `main`, `master`, and `develop` branches via GitHub Actions. It tests against PHP 8.4 and 8.5.

## Contributing

1. Fork the repo and create a feature branch
2. Make your changes
3. Run `composer test` to verify lint + tests pass
4. Submit a pull request

Do not commit real credentials. The `.env.example` and `.env.production` files contain only placeholders.

## License

[MIT](LICENSE)
