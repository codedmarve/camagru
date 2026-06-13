# Camagru

A photo-editing web app: capture an image from your webcam (or upload one), superimpose a
transparent overlay, and share it to a public gallery with likes and comments. Built with
plain PHP (no framework), MySQL, and Docker.

## Requirements

- Docker and Docker Compose

## Setup & run

Credentials are read from a `.env` file, which is **git-ignored** and therefore not part of the
repository. Create it from the provided template before the first run:

```bash
cp .env.example .env
docker compose up --build
```

The default values in `.env.example` are self-consistent (they configure both the MySQL
container and the app), so the copy above is enough to get a fully working stack.

Once the containers are up:

- **App:** http://localhost:8080
- **Mailhog (dev inbox):** http://localhost:8025

All emails (account verification, password reset, comment notifications) are captured by
Mailhog in development — open the Mailhog UI to click the verification/reset links.

## Common commands

```bash
# Start (rebuild images)
docker compose up --build

# Stop
docker compose down

# Reset the database (wipes all data, re-seeds schema)
docker compose down && docker volume rm camagru_db_data && docker compose up --build

# Tail the web server logs
docker compose logs -f web
```

## Configuration (`.env`)

| Variable | Purpose |
|---|---|
| `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS` | App database connection |
| `DB_ROOT_PASS` | MySQL root password (container init) |
| `MAIL_HOST` / `MAIL_PORT` | SMTP server (dev: `mailhog` / `1025`) |
| `MAIL_FROM` | Envelope/from address |
| `MAIL_TLS` | `on` to enable STARTTLS (real SMTP) |
| `MAIL_AUTH` | `on` to enable SMTP authentication |
| `MAIL_USER` / `MAIL_PASS` | SMTP credentials (when `MAIL_AUTH=on`) |

### Sending real email instead of Mailhog

The `msmtp` configuration is generated from these variables at container startup, so switching
providers needs only an `.env` edit and a restart — no image rebuild. For example, Gmail:

```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_TLS=on
MAIL_AUTH=on
MAIL_USER=you@gmail.com
MAIL_PASS=your_16_char_app_password
MAIL_FROM=you@gmail.com
```

```bash
docker compose up -d   # recreates the web container with the new settings
```

Gmail requires an **App Password** (2-factor auth enabled), and `MAIL_FROM` should match
`MAIL_USER`. Set the values back to `mailhog` afterwards to return to the dev inbox.

## Project layout

```
config/        Database connection (PDO)
overlays/      Transparent PNG overlays (incl. "none")
public/        Web root + single front-controller (index.php router)
scripts/       Helper scripts (overlay generation)
src/
  Controllers/ Request handlers
  Models/      Database access (prepared statements)
  Views/       Server-rendered templates
  Helpers/     CSRF helper
uploads/       Composited images written at runtime (git-ignored)
docker/        Container entrypoint (generates msmtp config)
```

## Security

- Passwords hashed with `password_hash` / `password_verify`
- SQL injection prevented via PDO prepared statements (emulation off)
- Output escaped with `htmlspecialchars` (XSS)
- CSRF tokens on every form and AJAX request
- File uploads validated by real MIME type and size
- Credentials kept in a git-ignored `.env`
- Sessions expire after inactivity (idle) and a hard maximum (absolute)

## Tech stack

PHP 8.2 (Apache) · MySQL 8.0 · Mailhog · vanilla JS + compiled Tailwind CSS · Docker Compose
