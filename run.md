# Running NoteNest AI (PHP Edition)

Verified working on **Windows 11 + PHP 8.5.1** on 2026-08-20.
The app is self-contained: no `composer install`, no build step, no external database.

---

## 1. Prerequisites

| Requirement | Needed for | Verified with |
| :--- | :--- | :--- |
| PHP **8.1+** (CLI) | Runtime + built-in web server | `php -v` |
| `pdo_sqlite` + `sqlite3` | Note/register/reminder persistence | `php -m` |
| `curl` | DeepSeek / OpenAI HTTP calls | `php -m` |
| `mbstring`, `openssl` | Text handling + TLS | `php -m` |

Check everything at once:

```bash
php -v && php -m | grep -iE "pdo_sqlite|sqlite3|curl|mbstring|openssl"
```

Expected output includes `curl`, `mbstring`, `openssl`, `PDO`, `pdo_sqlite`, `sqlite3`.

> **Composer is optional.** `public/index.php` registers its own PSR-4 autoloader for the
> `NoteNest\` namespace and only uses `vendor/autoload.php` if it happens to exist.
> `composer.json` declares no third-party packages, so `composer install` is not required.

> **Node is optional too.** The compiled stylesheet (`public/assets/css/app.css`) and the
> vendored icon bundle (`public/assets/vendor/`) are committed, so a PHP-only deployment
> needs no Node toolchain. You only need Node to *rebuild* those assets - see section 4.

---

## 2. Configure the environment

A working `.env` is already committed. If you are starting from a clean checkout:

```bash
cp .env.example .env
```

Relevant keys:

```ini
DEEPSEEK_API_KEY=          # leave blank to run in mock mode
OPENAI_API_KEY=            # alternative provider
AI_API_BASE_URL=https://api.deepseek.com
AI_DEFAULT_MODEL=deepseek-chat
AI_REASONER_MODEL=deepseek-reasoner

APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=storage/database.sqlite
```

**AI keys are currently empty**, so every `/api/ai/*` endpoint returns a canned mock response
instead of calling a provider. The whole UI is usable offline in this state — add a
`DEEPSEEK_API_KEY` (or `OPENAI_API_KEY`) only when you want real model output.

---

## 3. Database

Nothing to run. `src/Config/Database.php` opens `storage/database.sqlite` over PDO and
auto-migrates + seeds on first boot. `storage/database.sqlite` already exists in the repo
with seed data (3 registers, 1 note, 1 reminder).

To start from a blank database, delete the file and restart the server — it is recreated:

```bash
rm storage/database.sqlite
```

---

## 4. Front-end assets (only when changing styles)

Styling is a real Tailwind build, not a CDN script. The output is committed, so this step is
**not** needed to run the app - only to rebuild after editing `resources/css/input.css`,
`tailwind.config.js`, or any Tailwind class in `views/`.

```bash
npm install
```

```bash
npm run build
```

| Script | What it does |
| :--- | :--- |
| `npm run build` | Vendors the icon bundle, then compiles + minifies the stylesheet |
| `npm run build:css` | Compiles `resources/css/input.css` → `public/assets/css/app.css` (purged, minified) |
| `npm run watch:css` | Same, rebuilding on every save |
| `npm run build:icons` | Copies the pinned Lucide UMD bundle into `public/assets/vendor/` |
| `npm run dev` | `build:icons` + `watch:css` - use this while working on UI |

**Why there is a build at all.** The previous version pulled Tailwind and Lucide from CDNs at
runtime. That meant an unstyled flash on every page load, a hard dependency on two third-party
hosts, no version pinning, and the full un-purged framework shipped to the browser. The build
produces one purged ~60 KB stylesheet served from the app's own origin, and the app works
offline.

**Gotcha:** Tailwind only emits classes it can *see* in the source files listed under `content`
in `tailwind.config.js`. Class names assembled at runtime (`'bg-' + tone`) or stored in the
database will silently compile to nothing. Subject colours come from the database, so they are
mapped to literal class names in `views/partials/helpers.php` - follow that pattern rather than
interpolating class strings.

---

## 5. Start the server

From the project root (`D:\DevServer\AiNoteApp`):

```bash
php -S localhost:8000 -t public
```

Equivalent shortcut defined in `composer.json`:

```bash
composer start
```

`-t public` is required — it makes `public/` the document root so the front controller
`public/index.php` handles routing and `src/`, `views/`, `.env`, and `storage/` stay
outside the web root.

Then open:

```
http://localhost:8000
```

Stop the server with `Ctrl+C`.

---

## 6. Verify it works

The dashboard should render the "Study Desk" with the seeded notebooks:

```bash
curl -s http://localhost:8000 | grep -o "<title>.*</title>"
```

→ `<title>Study Desk - NoteNest</title>`

Smoke-test every page and API route:

```bash
for p in / /notepad /recorder /study /api/notes /api/registers /api/reminders; do curl -s -o /dev/null -w "%{http_code}  $p\n" "http://localhost:8000$p"; done
```

All seven returned **200** on the verified run.

Confirm the database is actually being read:

```bash
curl -s http://localhost:8000/api/notes
```

→ JSON array containing the seeded note `Getting Started with NoteNest`.

Confirm the AI layer responds (mock mode, no key needed):

```bash
curl -s -X POST http://localhost:8000/api/ai/answer -H "Content-Type: application/json" -d "{\"question\":\"What is 2+2?\"}"
```

→ `{"success":true,"answer":"This is a detailed analysis based on your note context. ..."}`

---

## 7. Routes at a glance

**Pages** — `/` (dashboard), `/note/{id}`, `/notepad`, `/pdf/{id}`, `/recorder`, `/study`

**REST API** — `/api/notes`, `/api/registers`, `/api/reminders`, `/api/chat/{id}`

**AI** — `/api/ai/organize`, `/solve`, `/answer`, `/flashcards`, `/quiz`, `/search`, `/image`, `/pdf`

**Export** — `/export/note/{id}/{markdown|pdf}`, `/export/register/{name}/{markdown|pdf}`

Full endpoint table lives in [README.md](README.md).

---

## 8. Notes and gotchas

- **Use `localhost`, not `127.0.0.1`.** `APP_URL` is set to `http://localhost:8000`; mixing
  hosts splits the PHP session cookie.
- **`/favicon.ico` 404s** in the server log. Harmless — no favicon ships with the project.
- **Speech recording (`/recorder`) needs the Web Speech API**, which is Chrome/Edge-only and
  requires microphone permission. It will not work in Firefox.
- **The built-in PHP server is single-threaded.** A long-running AI request blocks other
  requests. Fine for development; use Apache/nginx + PHP-FPM for anything else.
- **A `.claude/launch.json`** was added so the dev server can be started from the Claude Code
  browser preview under the name `notenest`.
- **Do not commit real API keys.** `.env` is tracked in this repo — keep it blank and use a
  local override if you add a key. It is now listed in `.gitignore`, but git keeps tracking
  files it already knows about; run `git rm --cached .env` if you want it untracked.
- **`app.js` is loaded blocking, in `<head>`, on purpose.** Views contain inline `<script>`
  blocks that call `window.NoteNest`, and those execute during parsing - before any deferred
  script has run. Adding `defer` or moving it to the end of `<body>` would make `NoteNest`
  undefined for every one of them. The icon bundle stays deferred; nothing needs it at parse time.
- **All asset URLs go through `NoteNest\Utils\Asset::url()`**, which appends the file's
  modification time (`app.js?v=1787174900`). Without it, returning users keep running the
  JavaScript their browser cached before the last deploy. Add new CSS/JS via that helper, never
  as a bare path.
- **Dialogs close themselves if the layout hides them.** `#mobile-drawer` is `md:hidden` and
  `#mobile-reminders` is `xl:hidden`, so widening the window past that breakpoint would
  otherwise leave the page scroll-locked with a focus trap armed on an invisible element.
  `Dialog.closeIfHiddenByLayout()` runs on resize, orientation change, and breakpoint crossings.

---

## 9. One-liner recap

```bash
php -S localhost:8000 -t public
```
