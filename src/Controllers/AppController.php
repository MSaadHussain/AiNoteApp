<?php

namespace NoteNest\Controllers;

use NoteNest\Models\Note;
use NoteNest\Models\Register;
use NoteNest\Models\Reminder;

class AppController
{
    private function render(string $view, array $data = []): void
    {
        // Extract variables for view template
        extract($data);

        // Common layout variables
        $registers = Register::all();
        $reminders = Reminder::all();
        $activeSubject = $data['activeSubject'] ?? ($_GET['subject'] ?? null);

        // Capture view content
        ob_start();
        $viewFile = dirname(__DIR__, 2) . "/views/pages/{$view}.php";
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "View [{$view}] not found.";
        }
        $content = ob_get_clean();

        // Render main layout
        require dirname(__DIR__, 2) . "/views/layouts/main.php";
    }

    public function dashboard(): void
    {
        $subject = $_GET['subject'] ?? null;
        $search = $_GET['q'] ?? null;

        $notes = Note::all($subject);

        if ($search) {
            $searchLower = strtolower(trim($search));
            $notes = array_filter($notes, function ($note) use ($searchLower) {
                return str_contains(strtolower($note->title), $searchLower)
                    || str_contains(strtolower($note->summary), $searchLower)
                    || str_contains(strtolower($note->subject), $searchLower);
            });
        }

        $this->render('dashboard', [
            'notes' => array_values($notes),
            'activeSubject' => $subject,
            'searchQuery' => $search,
            'pageTitle' => $subject ? "{$subject} - Notebook" : "Study Desk",
            'currentView' => 'DASHBOARD'
        ]);
    }

    public function noteView(string $id): void
    {
        $note = Note::find($id);
        if (!$note) {
            header('Location: /');
            exit;
        }

        $this->render('note_view', [
            'note' => $note,
            'pageTitle' => $note->title,
            'currentView' => 'NOTE_VIEW'
        ]);
    }

    public function notepad(): void
    {
        $id = $_GET['id'] ?? null;
        $subject = $_GET['subject'] ?? 'General';
        $note = $id ? Note::find($id) : null;

        $this->render('notepad', [
            'note' => $note,
            'subject' => $note ? $note->subject : $subject,
            'pageTitle' => $note ? "Edit: " . $note->title : "New Note",
            'currentView' => 'NOTEPAD'
        ]);
    }

    public function pdfReader(string $id): void
    {
        $note = Note::find($id);
        if (!$note) {
            header('Location: /');
            exit;
        }

        $this->render('pdf_reader', [
            'note' => $note,
            'pageTitle' => $note->title,
            'currentView' => 'PDF_VIEW'
        ]);
    }

    public function recorder(): void
    {
        $subject = $_GET['subject'] ?? '';

        $this->render('recorder', [
            'preSelectedSubject' => $subject,
            'pageTitle' => "Record Lecture",
            'currentView' => 'RECORDER'
        ]);
    }

    public function studyMode(): void
    {
        $subject = $_GET['subject'] ?? null;
        $notes = Note::all($subject);

        $this->render('study_mode', [
            'notes' => $notes,
            'pageTitle' => "Study Center",
            'currentView' => 'STUDY_MODE'
        ]);
    }
}
