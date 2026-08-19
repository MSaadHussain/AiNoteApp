<?php

namespace NoteNest\Models;

use NoteNest\Config\Database;
use PDO;

class Reminder
{
    public string $id;
    public string $text;
    public string $dueDate;
    public string $type; // 'general' | 'subject' | 'note'
    public ?string $targetId = null;
    public ?string $targetName = null;
    public bool $completed = false;
    public ?string $createdAt = null;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? Database::generateUuid();
        $this->text = $data['text'] ?? '';
        $this->dueDate = $data['due_date'] ?? $data['dueDate'] ?? date('M j, Y, g:i A', strtotime('+1 day 9:00 AM'));
        $this->type = $data['type'] ?? 'general';
        $this->targetId = $data['target_id'] ?? $data['targetId'] ?? null;
        $this->targetName = $data['target_name'] ?? $data['targetName'] ?? null;
        $this->completed = (bool)($data['completed'] ?? false);
        $this->createdAt = $data['created_at'] ?? null;
    }

    public static function all(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM reminders ORDER BY completed ASC, created_at DESC");
        $rows = $stmt->fetchAll();
        return array_map(fn($row) => new self($row), $rows);
    }

    public static function find(string $id): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM reminders WHERE id = :id LIMIT 1");
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
                "UPDATE reminders SET 
                    text = :text, 
                    due_date = :due_date, 
                    type = :type, 
                    target_id = :target_id, 
                    target_name = :target_name, 
                    completed = :completed 
                 WHERE id = :id"
            );
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO reminders 
                    (id, text, due_date, type, target_id, target_name, completed) 
                 VALUES 
                    (:id, :text, :due_date, :type, :target_id, :target_name, :completed)"
            );
        }

        return $stmt->execute([
            ':id' => $this->id,
            ':text' => $this->text,
            ':due_date' => $this->dueDate,
            ':type' => $this->type,
            ':target_id' => $this->targetId,
            ':target_name' => $this->targetName,
            ':completed' => $this->completed ? 1 : 0
        ]);
    }

    public static function toggle(string $id): ?self
    {
        $reminder = self::find($id);
        if ($reminder) {
            $reminder->completed = !$reminder->completed;
            $reminder->save();
            return $reminder;
        }
        return null;
    }

    public static function delete(string $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM reminders WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'dueDate' => $this->dueDate,
            'type' => $this->type,
            'targetId' => $this->targetId,
            'targetName' => $this->targetName,
            'completed' => $this->completed,
            'createdAt' => $this->createdAt
        ];
    }
}
