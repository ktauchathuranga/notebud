```markdown
# Notes App (PHP + MongoDB) — Dockerized

This project is a minimal notes application using plain HTML/CSS/JS for frontend, PHP for backend, and MongoDB for storage. It's fully dockerized: one container for the web (PHP + Apache) and one container for MongoDB. JWTs are used for authentication and are stored in an HttpOnly cookie that expires in 4 hours. Notes are automatically deleted 30 days after creation using a MongoDB TTL index.

What you get:
- User registration & login (username + password, password hashing)
- JWT stored in an HttpOnly cookie (4-hour expiration)
- Notes UI: create, list, delete notes (multiple notes per user)
- Automatic deletion of notes after 30 days (MongoDB TTL index)
- Docker Compose to run the web app and MongoDB together

Ports:
- Web app: http://localhost:8080
- MongoDB: 27017 (exposed for convenience; you can remove if not needed)

Quick start
1. Copy `.env.example` to `.env` and set secure values:
   cp .env.example .env
   Edit .env to set MONGO credentials and JWT_SECRET

2. Initialize (optional): The mongo container will run `docker-entrypoint-initdb.d/init-mongo.js` to create the TTL index and database automatically.

3. Build & run:
   docker-compose up --build

4. Open http://localhost:8080 in your browser.

Notes:
- For development the cookie is not set as Secure. For production, set COOKIE_SECURE=true in `.env` and serve over HTTPS.
- The MongoDB TTL index expires documents automatically after 30 days (2592000 seconds).
- No external PHP libraries are required; we use the mongodb PHP extension (pecl) and PHP's built-in functions.

Security and production considerations
- Use HTTPS in production and set COOKIE_SECURE=true.
- Consider adding CSRF protection for state-modifying endpoints.
- Add rate limiting and account lockout for failed login attempts.
- Use a non-root MongoDB user in production (set env vars accordingly).
- Backup your MongoDB data volume regularly.

If you want, I can:
- Add HTTPS support (nginx reverse proxy with Let's Encrypt).
- Add CSRF tokens for forms.
- Add password reset via email.
- Harden container permissions and provide a production-ready deployment.