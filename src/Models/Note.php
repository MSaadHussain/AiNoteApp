<?php

namespace NoteNest\Models;

use NoteNest\Config\Database;
use PDO;

class Note
{
    public string $id;
    public string $title;
    public string $subject;
    public string $date;
    public string $type; // 'audio' | 'pdf' | 'text'
    public ?string $originalTranscript = null;
    public ?string $rawContent = null;
    public ?string $pdfUrl = null;
    public ?string $audioUrl = null;
    public string $summary;
    public array $sections = [];
    public array $tags = [];
    public ?string $createdAt = null;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? Database::generateUuid();
        $this->title = $data['title'] ?? 'Untitled Note';
        $this->subject = $data['subject'] ?? 'General';
        $this->date = $data['date'] ?? date('n/j/Y');
        $this->type = $data['type'] ?? 'text';
        $this->originalTranscript = $data['original_transcript'] ?? $data['originalTranscript'] ?? null;
        $this->rawContent = $data['raw_content'] ?? $data['rawContent'] ?? null;
        $this->pdfUrl = $data['pdf_url'] ?? $data['pdfUrl'] ?? null;
        $this->audioUrl = $data['audio_url'] ?? $data['audioUrl'] ?? null;
        $this->summary = $data['summary'] ?? '';

        if (isset($data['sections'])) {
            $this->sections = is_string($data['sections']) ? (json_decode($data['sections'], true) ?: []) : (array)$data['sections'];
        }
        if (isset($data['tags'])) {
            $this->tags = is_string($data['tags']) ? (json_decode($data['tags'], true) ?: []) : (array)$data['tags'];
        }
        $this->createdAt = $data['created_at'] ?? null;
    }

    public static function all(?string $subject = null): array
    {
        $pdo = Database::getConnection();
        if ($subject && $subject !== '') {
            $stmt = $pdo->prepare("SELECT * FROM notes WHERE subject = :subject ORDER BY created_at DESC");
            $stmt->execute([':subject' => $subject]);
        } else {
            $stmt = $pdo->query("SELECT * FROM notes ORDER BY created_at DESC");
        }

        $rows = $stmt->fetchAll();
        return array_map(fn($row) => new self($row), $rows);
    }

    public static function find(string $id): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? new self($row) : null;
    }

    public function save(): bool
    {
        $pdo = Database::getConnection();
        $exists = self::find($this->id);

        if ($exists) {
            $stmt = $pdo->prepare(
                "UPDATE notes SET 
                    title = :title, 
                    subject = :subject, 
                    date = :date, 
                    type = :type, 
                    original_transcript = :original_transcript, 
                    raw_content = :raw_content, 
                    pdf_url = :pdf_url, 
                    audio_url = :audio_url, 
                    summary = :summary, 
                    sections = :sections, 
                    tags = :tags 
                 WHERE id = :id"
            );
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO notes 
                    (id, title, subject, date, type, original_transcript, raw_content, pdf_url, audio_url, summary, sections, tags) 
                 VALUES 
                    (:id, :title, :subject, :date, :type, :original_transcript, :raw_content, :pdf_url, :audio_url, :summary, :sections, :tags)"
            );
        }

        return $stmt->execute([
            ':id' => $this->id,
            ':title' => $this->title,
            ':subject' => $this->subject,
            ':date' => $this->date,
            ':type' => $this->type,
            ':original_transcript' => $this->originalTranscript,
            ':raw_content' => $this->rawContent,
            ':pdf_url' => $this->pdfUrl,
            ':audio_url' => $this->audioUrl,
            ':summary' => $this->summary,
            ':sections' => json_encode($this->sections),
            ':tags' => json_encode($this->tags),
        ]);
    }

    public static function delete(string $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM notes WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subject' => $this->subject,
            'date' => $this->date,
            'type' => $this->type,
            'originalTranscript' => $this->originalTranscript,
            'rawContent' => $this->rawContent,
            'pdfUrl' => $this->pdfUrl,
            'audioUrl' => $this->audioUrl,
            'summary' => $this->summary,
            'sections' => $this->sections,
            'tags' => $this->tags,
            'createdAt' => $this->createdAt,
        ];
    }
}
