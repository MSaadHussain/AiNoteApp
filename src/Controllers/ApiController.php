<?php

namespace NoteNest\Controllers;

use NoteNest\Models\Note;
use NoteNest\Models\Register;
use NoteNest\Models\Reminder;
use NoteNest\Models\ChatMessage;
use NoteNest\Utils\Router;

class ApiController
{
    // ================= NOTES =================

    public function getNotes(): void
    {
        $subject = $_GET['subject'] ?? null;
        $notes = Note::all($subject);
        Router::json(array_map(fn($n) => $n->toArray(), $notes));
    }

    public function getNote(string $id): void
    {
        $note = Note::find($id);
        if (!$note) {
            Router::json(['error' => 'Note not found'], 404);
        }
        Router::json($note->toArray());
    }

    public function saveNote(): void
    {
        $data = Router::getJsonInput();
        if (empty($data)) {
            $data = $_POST;
        }

        if (empty($data['title'])) {
            Router::json(['error' => 'Title is required'], 422);
        }

        $note = new Note($data);
        $saved = $note->save();

        if ($saved) {
            Router::json(['success' => true, 'note' => $note->toArray()], 200);
        } else {
            Router::json(['error' => 'Failed to save note'], 500);
        }
    }

    public function deleteNote(string $id): void
    {
        $deleted = Note::delete($id);
        ChatMessage::clearForNote($id);
        Router::json(['success' => $deleted]);
    }

    // ================= REGISTERS =================

    public function getRegisters(): void
    {
        $registers = Register::all();
        Router::json(array_map(fn($r) => $r->toArray(), $registers));
    }

    public function createRegister(): void
    {
        $data = Router::getJsonInput();
        $name = trim($data['name'] ?? ($_POST['name'] ?? ''));

        if (empty($name)) {
            Router::json(['error' => 'Register name is required'], 422);
        }

        $register = Register::create($name, $data['color'] ?? null);
        Router::json(['success' => true, 'register' => $register->toArray()]);
    }

    public function deleteRegister(string $id): void
    {
        $deleted = Register::delete($id);
        Router::json(['success' => $deleted]);
    }

    // ================= REMINDERS =================

    public function getReminders(): void
    {
        $reminders = Reminder::all();
        Router::json(array_map(fn($r) => $r->toArray(), $reminders));
    }

    public function createReminder(): void
    {
        $data = Router::getJsonInput();
        if (empty($data)) {
            $data = $_POST;
        }

        if (empty($data['text'])) {
            Router::json(['error' => 'Reminder text is required'], 422);
        }

        $reminder = new Reminder($data);
        $reminder->save();
        Router::json(['success' => true, 'reminder' => $reminder->toArray()]);
    }

    public function toggleReminder(string $id): void
    {
        $reminder = Reminder::toggle($id);
        if ($reminder) {
            Router::json(['success' => true, 'reminder' => $reminder->toArray()]);
        } else {
            Router::json(['error' => 'Reminder not found'], 404);
        }
    }

    public function deleteReminder(string $id): void
    {
        $deleted = Reminder::delete($id);
        Router::json(['success' => $deleted]);
    }

    // ================= CHAT =================

    public function getChatHistory(string $noteId): void
    {
        $messages = ChatMessage::forNote($noteId);
        Router::json(array_map(fn($m) => $m->toArray(), $messages));
    }

    public function saveChatMessage(string $noteId): void
    {
        $data = Router::getJsonInput();
        $msg = new ChatMessage([
            'note_id' => $noteId,
            'role' => $data['role'] ?? 'user',
            'content' => $data['content'] ?? ''
        ]);
        $msg->save();
        Router::json(['success' => true, 'message' => $msg->toArray()]);
    }

    public function clearChatHistory(string $noteId): void
    {
        ChatMessage::clearForNote($noteId);
        Router::json(['success' => true]);
    }
}
