<?php

// Front Controller for NoteNest AI

// 1. PSR-4 Autoloader (Self-contained, works with or without Composer vendor/autoload.php)
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require dirname(__DIR__) . '/vendor/autoload.php';
} else {
    spl_autoload_register(function ($class) {
        $prefix = 'NoteNest\\';
        $baseDir = dirname(__DIR__) . '/src/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    });
}

// 2. Load .env file if present
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

use NoteNest\Controllers\AppController;
use NoteNest\Controllers\ApiController;
use NoteNest\Controllers\AiController;
use NoteNest\Controllers\ExportController;
use NoteNest\Utils\Router;

$router = new Router();

// --- Web Page Views ---
$router->get('/', [AppController::class, 'dashboard']);
$router->get('/note/{id}', [AppController::class, 'noteView']);
$router->get('/notepad', [AppController::class, 'notepad']);
$router->get('/pdf/{id}', [AppController::class, 'pdfReader']);
$router->get('/recorder', [AppController::class, 'recorder']);
$router->get('/study', [AppController::class, 'studyMode']);

// --- REST API Endpoints ---
$router->get('/api/notes', [ApiController::class, 'getNotes']);
$router->get('/api/notes/{id}', [ApiController::class, 'getNote']);
$router->post('/api/notes', [ApiController::class, 'saveNote']);
$router->delete('/api/notes/{id}', [ApiController::class, 'deleteNote']);

$router->get('/api/registers', [ApiController::class, 'getRegisters']);
$router->post('/api/registers', [ApiController::class, 'createRegister']);
$router->delete('/api/registers/{id}', [ApiController::class, 'deleteRegister']);

$router->get('/api/reminders', [ApiController::class, 'getReminders']);
$router->post('/api/reminders', [ApiController::class, 'createReminder']);
$router->post('/api/reminders/{id}/toggle', [ApiController::class, 'toggleReminder']);
$router->delete('/api/reminders/{id}', [ApiController::class, 'deleteReminder']);

$router->get('/api/chat/{id}', [ApiController::class, 'getChatHistory']);
$router->post('/api/chat/{id}', [ApiController::class, 'saveChatMessage']);
$router->delete('/api/chat/{id}', [ApiController::class, 'clearChatHistory']);

// --- AI API Endpoints ---
$router->post('/api/ai/organize', [AiController::class, 'organize']);
$router->post('/api/ai/solve', [AiController::class, 'solve']);
$router->post('/api/ai/answer', [AiController::class, 'answer']);
$router->post('/api/ai/flashcards', [AiController::class, 'flashcards']);
$router->post('/api/ai/quiz', [AiController::class, 'quiz']);
$router->post('/api/ai/search', [AiController::class, 'semanticSearch']);
$router->post('/api/ai/image', [AiController::class, 'processImage']);
$router->post('/api/ai/pdf', [AiController::class, 'processPdf']);

// --- Export Endpoints ---
$router->get('/export/note/{id}/markdown', [ExportController::class, 'exportNoteMarkdown']);
$router->get('/export/note/{id}/pdf', [ExportController::class, 'exportNotePdf']);
$router->get('/export/register/{name}/markdown', [ExportController::class, 'exportRegisterMarkdown']);
$router->get('/export/register/{name}/pdf', [ExportController::class, 'exportRegisterPdf']);

// Dispatch Request
$router->dispatch();
