# 📝 NoteNest AI (PHP Edition)

> An intelligent student productivity and note-taking platform translated and built in modern **PHP 8.x** with MVC architecture, SQLite persistence, and DeepSeek / OpenAI AI integrations.

![NoteNest Banner](https://d112y698adiu2z.cloudfront.net/photos/production/software_thumbnail_photos/004/242/672/datas/medium.jpg)

---

## 🚀 Overview

**NoteNest AI (PHP Edition)** is a full translation and enhancement of the original React/TypeScript application into modern, clean, self-contained **PHP 8.x**.

It combines:
- **🎙️ Live Lecture Recording & Transcription**: Real-time speech-to-text with automated AI structuring into academic subjects, summaries, and categorized sections.
- **📚 Smart Digital Notebooks & Registers**: Pastel digital notebook covers with realistic binding, 3D hover effects, and loose paper lined-sheet views.
- **📄 Smart PDF Reader & AI Tutor**: Text extraction, split-screen interactive AI chat, text selection right-click tools, and document keyword highlighting.
- **🧠 Interactive Study Center**: AI-generated 3D flip flashcards and automated multiple-choice quizzes with instant scoring and explanations.
- **✍️ Blank Paper Notepad**: Handwritten-style lined paper editor with instant `?` inline AI answering and autosave.
- **🔔 Sticky Note Reminders**: Desk-affixed sticky notes linked to subjects or specific lecture notes.
- **💾 Full SQLite Database Persistence**: Robust PDO storage ensuring all notes, notebooks, reminders, and chat logs are safely stored across devices and sessions.
- **📥 Export Options**: One-click export of individual notes or entire subject notebooks to PDF and Markdown.

---

## 🛠️ Architecture & Tech Stack

- **Language**: PHP 8.1+ / 8.2+
- **Architecture**: MVC / Service-Repository Pattern with Front Controller (`public/index.php`)
- **Database**: SQLite with PDO (Zero configuration, automatic schema migration and seed data)
- **AI Backend**: DeepSeek API (`deepseek-chat`, `deepseek-reasoner`) / OpenAI API via native cURL with JSON mode and fallback repair
- **Frontend**: Custom Tailwind CSS, Lucide Icons, Patrick Hand & Nunito Typography, Web Speech API, and Vanilla JS / Alpine-ready interactivity

---

## 📁 Project Structure

```
notenest-php/
├── composer.json               # PHP PSR-4 configuration
├── .env.example                # Configuration template
├── .env                        # Environment variables
├── public/
│   ├── index.php               # Front controller & routing
│   └── assets/
│       ├── css/app.css         # Paper theme, animations, 3D card flips
│       └── js/
│           ├── app.js          # Core client helper, search, modals, TTS
│           ├── speech.js       # Web Speech API speech-to-text engine
│           └── study.js        # 3D Flashcards & interactive quiz engine
├── src/
│   ├── Config/
│   │   └── Database.php        # SQLite PDO connection & auto-migration
│   ├── Controllers/
│   │   ├── AppController.php   # Page renderers (Dashboard, NoteView, Notepad, etc.)
│   │   ├── ApiController.php   # REST API for Notes, Registers, Reminders, Chat
│   │   ├── AiController.php    # AI endpoints (organize, solve, quiz, flashcards)
│   │   └── ExportController.php# PDF & Markdown download handlers
│   ├── Models/
│   │   ├── Note.php            # Note entity & database queries
│   │   ├── Register.php        # Subject register entity & queries
│   │   ├── Reminder.php        # Reminder entity & queries
│   │   └── ChatMessage.php     # AI Chat history persistence
│   ├── Services/
│   │   ├── AiService.php       # DeepSeek / OpenAI client & prompt engine
│   │   ├── PdfService.php      # PDF text extraction & stream parsing
│   │   └── ExportService.php   # Markdown & PDF export generators
│   └── Utils/
│       └── Router.php          # Lightweight HTTP router with JSON support
├── views/
│   ├── layouts/
│   │   └── main.php            # Master layout (Header, Sidebar, Toast notifications)
│   ├── pages/
│   │   ├── dashboard.php       # Study desk, notebook stacks, loose papers
│   │   ├── note_view.php       # Note viewer, Study Buddy AI sidebar, TTS
│   │   ├── notepad.php         # Blank paper editor with '?' auto AI answer
│   │   ├── pdf_reader.php      # PDF viewer & Smart Reader with split AI chat
│   │   ├── recorder.php        # Speech recording & live AI transcription
│   │   └── study_mode.php      # 3D Flashcards & Quiz center
│   └── partials/
│       └── sidebar.php         # Notebook shelf & search bar
└── storage/
    └── database.sqlite         # SQLite database file
```

---

## ⚡ Quick Start & Setup

### 1. Prerequisites
- **PHP 8.1 or higher** (`php -v`)
- SQLite3 extension enabled in your `php.ini` (`pdo_sqlite`)

### 2. Clone / Open Directory
```bash
cd notenest-php
```

### 3. Configure Environment
Copy `.env.example` to `.env` and add your DeepSeek or OpenAI API key:
```bash
cp .env.example .env
```
Edit `.env`:
```ini
DEEPSEEK_API_KEY=your_deepseek_api_key_here
AI_API_BASE_URL=https://api.deepseek.com
AI_DEFAULT_MODEL=deepseek-chat
AI_REASONER_MODEL=deepseek-reasoner
```

*(Note: NoteNest includes graceful mock fallbacks if no API key is provided, allowing full offline testing!)*

### 4. Run the Application
Start PHP's built-in web server:
```bash
php -S localhost:8000 -t public
```

Open your browser and navigate to:
```
http://localhost:8000
```

---

## 📡 REST API Reference

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/notes` | List notes (optional `?subject=Name`) |
| `GET` | `/api/notes/{id}` | Get single note by ID |
| `POST` | `/api/notes` | Create or update a note |
| `DELETE` | `/api/notes/{id}` | Delete a note |
| `GET` | `/api/registers` | List all subject registers |
| `POST` | `/api/registers` | Create a new subject register |
| `DELETE` | `/api/registers/{id}` | Delete a subject register |
| `GET` | `/api/reminders` | List all reminders |
| `POST` | `/api/reminders` | Create a new reminder |
| `POST` | `/api/reminders/{id}/toggle` | Toggle reminder completed state |
| `DELETE` | `/api/reminders/{id}` | Delete a reminder |
| `POST` | `/api/ai/organize` | Organize transcript into structured note |
| `POST` | `/api/ai/solve` | Step-by-step reasoning on document context |
| `POST` | `/api/ai/answer` | Quick direct question answering |
| `POST` | `/api/ai/flashcards` | Generate study flashcards from note |
| `POST` | `/api/ai/quiz` | Generate multiple-choice quiz from note |
| `POST` | `/api/ai/search` | AI Semantic Search across note titles & tags |
| `GET` | `/export/note/{id}/pdf` | Printable / PDF view of note |
| `GET` | `/export/note/{id}/markdown` | Download note as Markdown (.md) |
| `GET` | `/export/register/{name}/pdf` | Printable / PDF view of notebook |
| `GET` | `/export/register/{name}/markdown`| Download full notebook as Markdown (.md) |

---

## 👥 Contributors

- **Sikandar Khan**
- **Saad Hussain**
