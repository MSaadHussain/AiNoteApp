<?php

namespace NoteNest\Models;

use NoteNest\Config\Database;
use PDO;

class ChatMessage
{
    public string $id;
    public string $noteId;
    public string $role; // 'user' | 'ai'
    public string $content;
    public ?string $createdAt = null;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? Database::generateUuid();
        $this->noteId = $data['note_id'] ?? $data['noteId'] ?? '';
        $this->role = $data['role'] ?? 'user';
        $this->content = $data['content'] ?? '';
        $this->createdAt = $data['created_at'] ?? null;
    }

    public static function forNote(string $noteId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE note_id = :note_id ORDER BY created_at ASC");
        $stmt->execute([':note_id' => $noteId]);
        $rows = $stmt->fetchAll();
        return array_map(fn($row) => new self($row), $rows);
    }

    public function save(): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO chat_messages (id, note_id, role, content) 
             VALUES (:id, :note_id, :role, :content)"
        );

        return $stmt->execute([
            ':id' => $this->id,
            ':note_id' => $this->noteId,
            ':role' => $this->role,
            ':content' => $this->content
        ]);
    }

    public static function clearForNote(string $noteId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM chat_messages WHERE note_id = :note_id");
        return $stmt->execute([':note_id' => $noteId]);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'noteId' => $this->noteId,
            'role' => $this->role,
            'content' => $this->content,
            'createdAt' => $this->createdAt
        ];
    }
}
