# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Collaboration Workflow

**Important**: The user wants to learn while building. Follow this approach:

1. **Explain before implementing** - Describe what we're about to do and why
2. **Write code directly to files** - Don't show full code in chat; write to file and give a summary
3. **User reviews in IDE** - They will check the code in their editor
4. **Answer questions** - User will ask questions; explain concepts thoroughly
5. **No silent implementations** - Always explain what was created and why

## Current Progress

### ALL MANDATORY FEATURES COMPLETE (Feb 5, 2026)

#### Infrastructure
- [x] Docker environment (PHP 8.2/Apache, MySQL 8.0, Mailhog)
- [x] Project structure (MVC pattern: src/Controllers, src/Models, src/Views)
- [x] Database schema (users, images, comments, likes tables + pending_email, email_change_token)
- [x] Single entry point router (public/index.php with .htaccess rewrite)
- [x] Database connection helper (config/database.php with getenv())
- [x] Mailhog email configuration (msmtp in Dockerfile)
- [x] Credentials in .env file (git ignored)

#### User Authentication
- [x] Registration with validation
- [x] Email verification (required before login)
- [x] Login with email OR username
- [x] Logout
- [x] Password reset via email (secure: doesn't reveal if email exists)
- [x] Profile editing (username, email, notifications)
- [x] Email change requires verification

#### Editor Page
- [x] Webcam mode with mirrored display (natural selfie view)
- [x] Upload mode with drag-and-drop support
- [x] Pan/zoom for uploaded images
- [x] Overlay selection (PNG with alpha channel)
- [x] "None (No Overlay)" option
- [x] Server-side image composition with GD library
- [x] User's thumbnails sidebar

#### Gallery Page
- [x] Public gallery showing ALL users' images
- [x] 5 images per page with pagination
- [x] Like toggle (AJAX)
- [x] Comments with real-time add/delete (AJAX)
- [x] Delete own comments only
- [x] Email notification on comments (configurable per user)

#### Security (All Mandatory)
- [x] Hashed passwords (password_hash/password_verify)
- [x] SQL injection prevention (PDO prepared statements)
- [x] XSS prevention (htmlspecialchars on all output)
- [x] CSRF protection (tokens on all forms + AJAX)
- [x] File upload validation (MIME type + size)
- [x] Credentials in .env (git ignored)

### Next Session: Testing & Bonus
- [ ] **Final testing** - Walk through all user flows
- [ ] **Browser console check** - Zero errors (except webcam HTTPS warning)
- [ ] (Bonus) Infinite scroll pagination
- [ ] (Bonus) Social media sharing
- [ ] (Bonus) Animated GIF rendering

### Testing Checklist for Next Session
```
[ ] Register new user → check Mailhog → click verify link → login
[ ] Webcam capture with overlay → appears in sidebar
[ ] Upload image with overlay → appears in sidebar
[ ] Delete photo from sidebar
[ ] Gallery: like/unlike photo
[ ] Gallery: add comment → check email notification in Mailhog
[ ] Gallery: delete own comment
[ ] Profile: change username
[ ] Profile: change email → verify via Mailhog
[ ] Profile: change password
[ ] Profile: toggle notifications
[ ] Forgot password → reset via Mailhog link
[ ] Logout
[ ] Check browser console for errors
```

## Tech Stack (Chosen)

- **Backend**: PHP 8.2
- **Frontend**: HTML, CSS (Tailwind), vanilla JavaScript
- **Database**: MySQL 8.0
- **Email**: Mailhog (dev), SMTP (production)
- **Containerization**: Docker with docker-compose

## Project Overview

Camagru is a 42 school web application for photo editing with webcam capture and social features. Users capture images via webcam, superimpose predefined overlay images (with alpha channels), and share them publicly with likes and comments.

## Tech Stack Constraints

**Critical**: All server-side code must use only functions with equivalents in PHP standard library. No external frameworks without PHP equivalent.

- **Server**: Any language (PHP standard library equivalents only)
- **Client**: HTML, CSS, JavaScript (browser native APIs only, no JS frameworks)
- **CSS Frameworks**: Allowed if no forbidden JavaScript
- **Database**: SQL required
- **Deployment**: Docker/docker-compose mandatory

## Build & Run

```bash
docker-compose up --build
```

Single command deployment is required.

## Architecture Requirements

### Pages
- **Gallery** (public): Paginated image feed (min 5/page), likes/comments for authenticated users
- **Editor** (authenticated): Webcam preview + overlay selection + capture button + user's thumbnails sidebar
- **User**: Registration, login, profile editing, password reset via email

### Key Technical Requirements
- Image superposition MUST happen server-side (not client)
- Overlay images must have alpha channel (PNG with transparency)
- Email notifications for comments (SMTP, default ON, configurable)
- getUserMedia() for webcam access
- Image upload alternative for users without webcam
- Browser support: Firefox >= 41, Chrome >= 46

## Security Requirements (Mandatory)

- Hashed passwords (never plain text)
- SQL injection prevention (parameterized queries)
- XSS prevention (escape all output)
- CSRF protection
- File upload validation
- Credentials in `.env` file (git ignored)

## Console Output Policy

Zero errors/warnings in browser console and server logs. Only getUserMedia() errors are tolerated (HTTPS limitation).

## Bonus Features (only if mandatory is perfect)

- AJAX for server exchanges
- Live preview on webcam feed
- Infinite scroll pagination
- Social media sharing
- Animated GIF rendering

## Development Commands

```bash
# Start all containers
docker-compose up --build

# Stop containers
docker-compose down

# Reset database (removes all data)
docker-compose down && docker volume rm camagru_db_data && docker-compose up --build

# Check database tables
docker-compose exec db mysql -u camagru_user -psecure_password camagru -e "SHOW TABLES;"

# View PHP logs
docker-compose logs web

# Access MySQL shell
docker-compose exec db mysql -u camagru_user -psecure_password camagru
```

## URLs (Development)

- **App**: http://localhost:8080
- **Mailhog UI**: http://localhost:8025
