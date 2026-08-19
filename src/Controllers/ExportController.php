<?php

namespace NoteNest\Controllers;

use NoteNest\Models\Note;
use NoteNest\Services\ExportService;

class ExportController
{
    public function exportNoteMarkdown(string $id): void
    {
        $note = Note::find($id);
        if (!$note) {
            http_response_code(404);
            echo "Note not found";
            exit;
        }

        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $note->title) . '.md';
        $content = ExportService::formatNoteMarkdown($note);

        header('Content-Type: text/markdown; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }

    public function exportNotePdf(string $id): void
    {
        $note = Note::find($id);
        if (!$note) {
            http_response_code(404);
            echo "Note not found";
            exit;
        }

        $htmlContent = "<h1>" . htmlspecialchars($note->title) . "</h1>";
        $htmlContent .= "<div class=\"meta\"><strong>Subject:</strong> " . htmlspecialchars($note->subject) . " | <strong>Date:</strong> " . htmlspecialchars($note->date) . "</div>";
        $htmlContent .= "<div class=\"summary-box\"><strong>Summary:</strong> " . nl2br(htmlspecialchars($note->summary)) . "</div>";

        if (!empty($note->sections)) {
            foreach ($note->sections as $sec) {
                $heading = htmlspecialchars($sec['heading'] ?? '');
                $type = htmlspecialchars($sec['type'] ?? 'theory');
                $text = nl2br(htmlspecialchars($sec['content'] ?? ''));
                $htmlContent .= "<h2>{$heading} <span class=\"type-badge\">{$type}</span></h2>";
                $htmlContent .= "<p>{$text}</p>";
            }
        } elseif (!empty($note->rawContent)) {
            $htmlContent .= "<div>" . nl2br(htmlspecialchars($note->rawContent)) . "</div>";
        }

        if (!empty($note->tags)) {
            $htmlContent .= "<hr><div><strong>Tags: </strong>";
            foreach ($note->tags as $t) {
                $htmlContent .= "<span class=\"tag\">#" . htmlspecialchars($t) . "</span>";
            }
            $htmlContent .= "</div>";
        }

        echo ExportService::generatePrintableHtml($note->title, $htmlContent);
        exit;
    }

    public function exportRegisterMarkdown(string $encodedSubject): void
    {
        $subject = urldecode($encodedSubject);
        $notes = Note::all($subject);

        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $subject) . '_Notebook.md';
        $content = ExportService::formatRegisterMarkdown($subject, $notes);

        header('Content-Type: text/markdown; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }

    public function exportRegisterPdf(string $encodedSubject): void
    {
        $subject = urldecode($encodedSubject);
        $notes = Note::all($subject);

        $htmlContent = "<div style=\"text-align: center; margin-top: 100px; margin-bottom: 120px;\">";
        $htmlContent .= "<h1 style=\"font-size: 36px; border: none;\">" . htmlspecialchars($subject) . " Notebook</h1>";
        $htmlContent .= "<p style=\"font-size: 18px; color: #ea580c; font-weight: bold;\">NoteNest AI</p>";
        $htmlContent .= "<p style=\"color: #64748b;\">Compiled on " . date('F j, Y') . " • " . count($notes) . " Notes</p>";
        $htmlContent .= "</div>";
        $htmlContent .= "<div style=\"page-break-after: always;\"></div>";

        foreach ($notes as $idx => $note) {
            $htmlContent .= "<h1>" . htmlspecialchars($note->title) . "</h1>";
            $htmlContent .= "<div class=\"meta\"><strong>Date:</strong> " . htmlspecialchars($note->date) . "</div>";
            $htmlContent .= "<div class=\"summary-box\"><strong>Summary:</strong> " . nl2br(htmlspecialchars($note->summary)) . "</div>";

            if (!empty($note->sections)) {
                foreach ($note->sections as $sec) {
                    $heading = htmlspecialchars($sec['heading'] ?? '');
                    $type = htmlspecialchars($sec['type'] ?? 'theory');
                    $text = nl2br(htmlspecialchars($sec['content'] ?? ''));
                    $htmlContent .= "<h2>{$heading} <span class=\"type-badge\">{$type}</span></h2>";
                    $htmlContent .= "<p>{$text}</p>";
                }
            } elseif (!empty($note->rawContent)) {
                $htmlContent .= "<div>" . nl2br(htmlspecialchars($note->rawContent)) . "</div>";
            }

            if ($idx < count($notes) - 1) {
                $htmlContent .= "<div style=\"page-break-after: always;\"></div>";
            }
        }

        echo ExportService::generatePrintableHtml($subject . " Notebook", $htmlContent);
        exit;
    }
}
