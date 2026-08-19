<?php

namespace NoteNest\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    /**
     * Get or initialize SQLite PDO connection
     */
    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $storageDir = dirname(__DIR__, 2) . '/storage';
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0777, true);
            }

            $dbPath = $storageDir . '/database.sqlite';
            $isNew = !file_exists($dbPath);

            try {
                self::$pdo = new PDO("sqlite:" . $dbPath);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                // Enable foreign keys and WAL mode for better concurrency
                self::$pdo->exec("PRAGMA foreign_keys = ON;");
                self::$pdo->exec("PRAGMA journal_mode = WAL;");

                self::initializeSchema();

                if ($isNew) {
                    self::seedDefaults();
                }
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }

        return self::$pdo;
    }

    /**
     * Create tables if they do not exist
     */
    private static function initializeSchema(): void
    {
        $schema = <<<SQL
        CREATE TABLE IF NOT EXISTS registers (
            id TEXT PRIMARY KEY,
            name TEXT UNIQUE NOT NULL,
            color TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS notes (
            id TEXT PRIMARY KEY,
            title TEXT NOT NULL,
            subject TEXT NOT NULL,
            date TEXT NOT NULL,
            type TEXT NOT NULL, -- 'audio' | 'pdf' | 'text'
            original_transcript TEXT,
            raw_content TEXT,
            pdf_url TEXT,
            audio_url TEXT,
            summary TEXT,
            sections TEXT, -- JSON string
            tags TEXT,     -- JSON string
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS reminders (
            id TEXT PRIMARY KEY,
            text TEXT NOT NULL,
            due_date TEXT NOT NULL,
            type TEXT NOT NULL, -- 'general' | 'subject' | 'note'
            target_id TEXT,
            target_name TEXT,
            completed INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS chat_messages (
            id TEXT PRIMARY KEY,
            note_id TEXT NOT NULL,
            role TEXT NOT NULL, -- 'user' | 'ai'
            content TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
SQL;

        self::$pdo->exec($schema);
    }

    /**
     * Seed initial sample data for new installation
     */
    private static function seedDefaults(): void
    {
        $colors = [
            'bg-rose-200 text-rose-800 border-rose-300',
            'bg-sky-200 text-sky-800 border-sky-300',
            'bg-emerald-200 text-emerald-800 border-emerald-300',
            'bg-amber-200 text-amber-800 border-amber-300',
            'bg-violet-200 text-violet-800 border-violet-300',
            'bg-orange-200 text-orange-800 border-orange-300'
        ];

        $defaultSubjects = [
            'Computer Science' => $colors[1],
            'Artificial Intelligence' => $colors[4],
            'Mathematics' => $colors[3],
        ];

        $stmt = self::$pdo->prepare("INSERT OR IGNORE INTO registers (id, name, color) VALUES (:id, :name, :color)");
        foreach ($defaultSubjects as $name => $color) {
            $stmt->execute([
                ':id' => self::generateUuid(),
                ':name' => $name,
                ':color' => $color,
            ]);
        }

        // Add a starter welcome note
        $noteStmt = self::$pdo->prepare(
            "INSERT INTO notes (id, title, subject, date, type, summary, sections, tags, raw_content) 
             VALUES (:id, :title, :subject, :date, :type, :summary, :sections, :tags, :raw_content)"
        );

        $sampleSections = [
            [
                'heading' => 'Welcome to NoteNest AI PHP',
                'content' => 'NoteNest AI is your intelligent student productivity companion. You can record lectures, upload PDFs, draft handwritten-style notes, and generate instant AI study flashcards and quizzes.',
                'type' => 'definition'
            ],
            [
                'heading' => 'Key Features',
                'content' => '1. Live Voice Recording & Transcript Organization\n2. Smart PDF Reader with Deep Context AI\n3. Interactive Study Center with 3D Flip Flashcards\n4. Desk Sticky Note Reminders',
                'type' => 'theory'
            ]
        ];

        $noteStmt->execute([
            ':id' => self::generateUuid(),
            ':title' => 'Getting Started with NoteNest',
            ':subject' => 'Artificial Intelligence',
            ':date' => date('n/j/Y'),
            ':type' => 'text',
            ':summary' => 'Introduction to NoteNest AI note-taking workflow and study productivity features.',
            ':sections' => json_encode($sampleSections),
            ':tags' => json_encode(['welcome', 'overview', 'ai']),
            ':raw_content' => 'Welcome to NoteNest AI! Start by exploring your notebooks, recording a lecture, or creating a new note.'
        ]);

        // Add a sample reminder
        $remStmt = self::$pdo->prepare(
            "INSERT INTO reminders (id, text, due_date, type, target_id, target_name, completed) 
             VALUES (:id, :text, :due_date, :type, :target_id, :target_name, :completed)"
        );
        $tomorrow = date('M j, Y, g:i A', strtotime('+1 day 9:00 AM'));
        $remStmt->execute([
            ':id' => self::generateUuid(),
            ':text' => 'Explore NoteNest AI Study Center',
            ':due_date' => $tomorrow,
            ':type' => 'general',
            ':target_id' => null,
            ':target_name' => null,
            ':completed' => 0
        ]);
    }

    /**
     * Generate a UUID v4
     */
    public static function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
