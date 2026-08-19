<?php

namespace NoteNest\Models;

use NoteNest\Config\Database;
use PDO;

class Register
{
    public string $id;
    public string $name;
    public string $color;
    public array $noteIds = [];

    public const COLORS = [
        'bg-rose-200 text-rose-800 border-rose-300',
        'bg-sky-200 text-sky-800 border-sky-300',
        'bg-emerald-200 text-emerald-800 border-emerald-300',
        'bg-amber-200 text-amber-800 border-amber-300',
        'bg-violet-200 text-violet-800 border-violet-300',
        'bg-orange-200 text-orange-800 border-orange-300'
    ];

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? Database::generateUuid();
        $this->name = $data['name'] ?? 'Untitled Subject';
        $this->color = $data['color'] ?? self::COLORS[0];
        $this->noteIds = $data['noteIds'] ?? [];
    }

    public static function all(): array
    {
        $pdo = Database::getConnection();
        
        // 1. Get all explicitly registered subjects
        $stmt = $pdo->query("SELECT * FROM registers ORDER BY name ASC");
        $registersData = $stmt->fetchAll();

        // 2. Also check if any notes have subjects not in registers table
        $noteSubjectsStmt = $pdo->query("SELECT DISTINCT subject FROM notes");
        $noteSubjects = $noteSubjectsStmt->fetchAll(PDO::FETCH_COLUMN);

        $existingNames = array_column($registersData, 'name');
        $colorIdx = count($registersData);

        foreach ($noteSubjects as $subjectName) {
            if (!in_array($subjectName, $existingNames)) {
                $color = self::COLORS[$colorIdx % count(self::COLORS)];
                self::create($subjectName, $color);
                $registersData[] = [
                    'id' => Database::generateUuid(),
                    'name' => $subjectName,
                    'color' => $color
                ];
                $existingNames[] = $subjectName;
                $colorIdx++;
            }
        }

        // 3. Attach note IDs to each register
        $notesStmt = $pdo->query("SELECT id, subject FROM notes");
        $notes = $notesStmt->fetchAll();

        $result = [];
        foreach ($registersData as $r) {
            $reg = new self($r);
            $reg->noteIds = array_values(array_map(
                fn($n) => $n['id'],
                array_filter($notes, fn($n) => $n['subject'] === $reg->name)
            ));
            $result[] = $reg;
        }

        return $result;
    }

    public static function findByName(string $name): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM registers WHERE name = :name LIMIT 1");
        $stmt->execute([':name' => $name]);
        $row = $stmt->fetch();
        if ($row) {
            $reg = new self($row);
            $notesStmt = $pdo->prepare("SELECT id FROM notes WHERE subject = :subject");
            $notesStmt->execute([':subject' => $name]);
            $reg->noteIds = $notesStmt->fetchAll(PDO::FETCH_COLUMN);
            return $reg;
        }
        return null;
    }

    public static function create(string $name, ?string $color = null): self
    {
        $pdo = Database::getConnection();
        $existing = self::findByName($name);
        if ($existing) {
            return $existing;
        }

        if ($color === null) {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM registers");
            $count = (int)$countStmt->fetchColumn();
            $color = self::COLORS[$count % count(self::COLORS)];
        }

        $id = Database::generateUuid();
        $stmt = $pdo->prepare("INSERT INTO registers (id, name, color) VALUES (:id, :name, :color)");
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':color' => $color
        ]);

        return new self([
            'id' => $id,
            'name' => $name,
            'color' => $color,
            'noteIds' => []
        ]);
    }

    public static function delete(string $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM registers WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'noteIds' => $this->noteIds
        ];
    }
}
