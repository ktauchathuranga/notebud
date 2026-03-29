<p align="center">
    <img alt="Notebud Logo Light Mode" src="art/notebud-light.svg#gh-light-mode-only" height="80"/>
    <img alt="Notebud Logo Dark Mode" src="art/notebud-dark.svg#gh-dark-mode-only" height="80"/>
</p>

<p align="center">
<a href="https://github.com/ktauchathuranga/notebud/actions"><img src="https://github.com/ktauchathuranga/notebud/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://notebud.cc"><img src="https://img.shields.io/website?url=https%3A%2F%2Fnotebud.cc" alt="Live Demo"></a>
<a href="https://github.com/ktauchathuranga/notebud/releases"><img src="https://img.shields.io/github/v/release/ktauchathuranga/notebud" alt="Latest Release"></a>
<a href="https://github.com/ktauchathuranga/notebud/blob/laravel/LICENSE"><img src="https://img.shields.io/github/license/ktauchathuranga/notebud" alt="License"></a>
</p>


# Notebud

Notebud is a Laravel + Livewire app for writing notes, uploading files, and sharing content with classmates by username. It is designed for fast, secure, and fair academic collaboration, **no email or phone required**.

University labs typically don't allow personal devices, no phones, no laptops. When students need to save their work, the only option is cloud services like Google Drive, Mega, or Mediafire. But those require email addresses and OTP codes that students can't access without their phones. Even worse, logging into personal accounts on shared lab computers is risky, it's easy to forget to sign out.

**Notebud solves this.** It's a web app where students can save notes and files using just a username and password, no email, no OTP, no personal cloud accounts. Students can also share notes and files with classmates instantly by typing their username.

> [!TIP]
> Live: [notebud.cc](https://notebud.cc)<br>
> Alternative: [notebud-qo9d.onrender.com](https://notebud-qo9d.onrender.com/)<br>
> Support Email: [contact@notebud.cc](mailto:contact@notebud.cc)


### Academic Use Only – Disclaimer

**Notebud is intended strictly for fair academic use.**

- Do **not** use this platform to facilitate cheating, plagiarism, or any form of academic dishonesty.
- Do **not** use Notebud for any illegal activities or to violate your institution's code of conduct.
- The developer is **not responsible** for any misuse, academic consequences, or legal issues arising from the use of this software.

By using Notebud, you agree to use it ethically and at your own risk.

---

## Features

- **No email or phone required** — sign up with just a username and password
- **Safe on shared computers** — no personal accounts to forget to sign out of
- **Share by username** — send notes or files to classmates without email addresses
- Username/password authentication with Cloudflare Turnstile on login and registration
- Recovery-code based account recovery flow (`/recover-account`)
- Mandatory recovery-code handoff after registration
- Markdown note creation, editing, search, and sharing
- File upload/download and username-based sharing
- In-app notifications for share activity
- Per-user storage quotas (default 20 MB + grace)
- Admin user management with quota overrides
- Super-fast Redis object caching and invalidation
- Automated Docker CI/CD deployments via GitHub Actions
- SEO basics: dynamic meta tags, canonical URLs, `sitemap.xml`, `robots.txt`

## Stack

- Laravel 13
- Livewire 4 + Flux UI
- Tailwind CSS 4
- Laravel Fortify
- Pest + Pint
- Docker (single production-style container: PHP-FPM + Nginx + Supervisor)

## Storage and Upload Limits

- Default per-user quota: `20 MB` (`DEFAULT_STORAGE_QUOTA_BYTES=20971520`)
- Grace allowance: `1 MB` (`STORAGE_QUOTA_GRACE_BYTES=1048576`)
- Per-file upload validation limit: `10 MB` (`max:10240` in `FileUpload`)
- Docker PHP limits currently set to `10M` (`upload_max_filesize`, `post_max_size`)

If you want larger single-file uploads, increase both Laravel validation and Docker PHP ini limits.

## Local Development (non-Docker)

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
```

### Run

```bash
composer dev
```

This starts app server, queue worker, logs, and Vite at `http://localhost:8000`.

## Docker (Production-Style Testing)

`docker-compose` is not used in this project anymore.

### 1. Build image

```bash
docker build -t notebud-test .
```

### 2. Run container with env file

```bash
docker rm -f notebud-local || true
docker run --name notebud-local -p 8080:80 --env-file /path/to/prod.local.env notebud-test
```

### 3. Open app

- `http://localhost:8080`

### Important local-prod note

For local Docker prod testing, set:

```env
APP_URL=http://localhost:8080
```

Do not use your live domain as `APP_URL` in local Docker, otherwise assets can resolve to remote URLs and trigger CORS/MIME errors.

## Environment Notes

- Local default DB: SQLite
- Production DB: TiDB (Aiven SSL supported)
- Upload disk switch:
  - local: `UPLOADS_DISK=uploads`
  - production: `UPLOADS_DISK=r2`
- Avatars:
  - local default: `public`
  - production default: `r2`

## Database Seeding

Seed imported legacy data:

```bash
php artisan db:seed --class="Database\\Seeders\\OldDatabaseSeeder"
```

Inside Docker:

```bash
docker exec -it notebud-local php artisan db:seed --class="Database\\Seeders\\OldDatabaseSeeder"
```

Fresh DB + old seed inside Docker:

```bash
docker exec -it notebud-local php artisan migrate:fresh --seed --seeder="Database\\Seeders\\OldDatabaseSeeder"
```

## Useful Commands

| Command | Description |
|---|---|
| `composer dev` | Run local dev stack |
| `composer test` | Pint check + tests |
| `composer lint` | Auto-fix style |
| `composer lint:check` | Style check only |
| `php artisan test` | Run tests |

## Deployment

The repository is Docker-first for production deployment. Provide environment variables from `.env.production` in your platform (for example Render, VPS, or any container host).

A continuous deployment (CD) pipeline (`.github/workflows/deploy.yml`) is included, which automatically builds the Docker image remotely, caches composer/npm dependencies, pushes to Docker Hub, and triggers a live deployment webhook whenever code is merged into the `main` branch.

## License

[MIT](LICENSE)
