<?php

namespace NoteNest\Controllers;

use NoteNest\Services\AiService;
use NoteNest\Services\PdfService;
use NoteNest\Utils\Router;

class AiController
{
    private AiService $aiService;

    public function __construct()
    {
        $this->aiService = new AiService();
    }

    public function organize(): void
    {
        $data = Router::getJsonInput();
        $transcript = $data['transcript'] ?? ($_POST['transcript'] ?? '');

        if (empty(trim($transcript))) {
            Router::json(['error' => 'Transcript is required'], 422);
        }

        try {
            $result = $this->aiService->organizeNote($transcript);
            Router::json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            Router::json(['error' => $e->getMessage()], 500);
        }
    }

    public function solve(): void
    {
        $data = Router::getJsonInput();
        $context = $data['context'] ?? '';
        $question = $data['question'] ?? '';

        if (empty(trim($question))) {
            Router::json(['error' => 'Question is required'], 422);
        }

        try {
            $response = $this->aiService->solveWithThinking($context, $question);
            Router::json(['success' => true, 'response' => $response]);
        } catch (\Exception $e) {
            Router::json(['error' => $e->getMessage()], 500);
        }
    }

    public function answer(): void
    {
        $data = Router::getJsonInput();
        $context = $data['context'] ?? '';
        $question = $data['question'] ?? '';

        if (empty(trim($question))) {
            Router::json(['error' => 'Question is required'], 422);
        }

        try {
            $response = $this->aiService->getQuickAnswer($context, $question);
            Router::json(['success' => true, 'answer' => $response]);
        } catch (\Exception $e) {
            Router::json(['error' => $e->getMessage()], 500);
        }
    }

    public function flashcards(): void
    {
        $data = Router::getJsonInput();
        $content = $data['content'] ?? ($_POST['content'] ?? '');

        if (empty(trim($content))) {
            Router::json(['error' => 'Content is required'], 422);
        }

        try {
            $cards = $this->aiService->generateFlashcards($content);
            Router::json(['success' => true, 'flashcards' => $cards]);
        } catch (\Exception $e) {
            Router::json(['error' => $e->getMessage()], 500);
        }
    }

    public function quiz(): void
    {
        $data = Router::getJsonInput();
        $content = $data['content'] ?? ($_POST['content'] ?? '');

        if (empty(trim($content))) {
            Router::json(['error' => 'Content is required'], 422);
        }

        try {
            $questions = $this->aiService->generateQuiz($content);
            Router::json(['success' => true, 'questions' => $questions]);
        } catch (\Exception $e) {
            Router::json(['error' => $e->getMessage()], 500);
        }
    }

    public function semanticSearch(): void
    {
        $data = Router::getJsonInput();
        $query = $data['query'] ?? ($_POST['query'] ?? '');
        $metadata = $data['metadata'] ?? ($_POST['metadata'] ?? []);

        try {
            $matchedIds = $this->aiService->performSemanticSearch($query, $metadata);
            Router::json(['success' => true, 'noteIds' => $matchedIds]);
        } catch (\Exception $e) {
            Router::json(['error' => $e->getMessage()], 500);
        }
    }

    public function processImage(): void
    {
        $extractedText = '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // If image is uploaded, we can describe its metadata and run structured organization
            $imageInfo = getimagesize($_FILES['image']['tmp_name']);
            $extractedText = "[Uploaded Image: " . basename($_FILES['image']['name']) . 
                " (" . ($imageInfo ? "{$imageInfo[0]}x{$imageInfo[1]}px" : "Image") . ")]";
        } else {
            $data = Router::getJsonInput();
            $extractedText = $data['text'] ?? ($_POST['text'] ?? '');
        }

        try {
            $result = $this->aiService->convertImageToNote($extractedText);
            Router::json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            Router::json(['error' => $e->getMessage()], 500);
        }
    }

    public function processPdf(): void
    {
        $rawText = '';
        $subject = $_POST['subject'] ?? 'General';

        if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['pdf']['tmp_name'];
            $rawText = PdfService::extractText($tmpPath);
            $pageCount = PdfService::getPageCount($tmpPath);
        } else {
            $data = Router::getJsonInput();
            $rawText = $data['text'] ?? '';
            $pageCount = 1;
        }

        try {
            $structured = $this->aiService->processPdfText($rawText);
            Router::json([
                'success' => true,
                'data' => [
                    'title' => $structured['title'] ?? 'PDF Document',
                    'subject' => $subject,
                    'summary' => $structured['summary'] ?? '',
                    'sections' => $structured['sections'] ?? [],
                    'tags' => $structured['tags'] ?? ['pdf'],
                    'rawContent' => $rawText,
                    'pageCount' => $pageCount
                ]
            ]);
        } catch (\Exception $e) {
            Router::json(['error' => $e->getMessage()], 500);
        }
    }
}
