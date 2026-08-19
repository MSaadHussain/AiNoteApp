<?php

namespace NoteNest\Services;

class AiService
{
    private string $apiKey;
    private string $baseUrl;
    private string $defaultModel;
    private string $reasonerModel;

    public function __construct()
    {
        // Load environment variables if available
        $this->apiKey = $_ENV['DEEPSEEK_API_KEY'] 
            ?? $_ENV['OPENAI_API_KEY'] 
            ?? getenv('DEEPSEEK_API_KEY') 
            ?? getenv('OPENAI_API_KEY') 
            ?? getenv('API_KEY') 
            ?? '';

        $this->baseUrl = rtrim(
            $_ENV['AI_API_BASE_URL'] 
            ?? getenv('AI_API_BASE_URL') 
            ?? 'https://api.deepseek.com', 
            '/'
        );

        $this->defaultModel = $_ENV['AI_DEFAULT_MODEL'] 
            ?? getenv('AI_DEFAULT_MODEL') 
            ?? 'deepseek-chat';

        $this->reasonerModel = $_ENV['AI_REASONER_MODEL'] 
            ?? getenv('AI_REASONER_MODEL') 
            ?? 'deepseek-reasoner';
    }

    /**
     * Send HTTP POST to OpenAI-compatible Chat Completions API
     */
    private function callChatApi(array $messages, string $model, bool $jsonMode = false, float $temperature = 0.7, int $maxTokens = 8192): string
    {
        if (empty($this->apiKey)) {
            // Provide a graceful fallback simulation if no API key is configured
            return $this->getMockResponse($messages, $jsonMode);
        }

        $url = $this->baseUrl . '/chat/completions';
        
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];

        if ($jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => 90,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            error_log("AI API Error: HTTP $httpCode - " . ($curlError ?: $response));
            if ($model === $this->reasonerModel) {
                // Fallback to default model if reasoner fails
                return $this->callChatApi($messages, $this->defaultModel, $jsonMode, $temperature, $maxTokens);
            }
            throw new \Exception("AI API Request Failed: HTTP $httpCode");
        }

        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? ($jsonMode ? '{}' : '');
    }

    /**
     * Safely parse JSON from LLM output with truncation repair
     */
    public function safeParseJson(string $text): array
    {
        $cleaned = trim($text);
        if (str_starts_with($cleaned, '```')) {
            $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $cleaned);
            $cleaned = preg_replace('/\s*```$/', '', $cleaned);
        }

        $cleaned = trim($cleaned);
        $decoded = json_decode($cleaned, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Try repairing truncated brackets/quotes
        $repaired = $cleaned;
        $quoteCount = substr_count($repaired, '"') - substr_count($repaired, '\"');
        if ($quoteCount % 2 !== 0) {
            $repaired .= '"';
        }

        $opensCurly = substr_count($repaired, '{');
        $closesCurly = substr_count($repaired, '}');
        for ($i = 0; $i < ($opensCurly - $closesCurly); $i++) {
            $repaired .= '}';
        }

        $opensSquare = substr_count($repaired, '[');
        $closesSquare = substr_count($repaired, ']');
        for ($i = 0; $i < ($opensSquare - $closesSquare); $i++) {
            $repaired .= ']';
        }

        $decoded = json_decode($repaired, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        error_log("JSON parse and repair failed on: " . substr($text, 0, 200));
        return [];
    }

    /**
     * Organizes raw transcript into structured note with sections
     */
    public function organizeNote(string $transcript): array
    {
        $systemPrompt = <<<PROMPT
You are an academic note organizer. Analyze the following lecture transcript.
Your task:
1. Identify the academic Subject.
2. Create a concise Title.
3. Write a short 3-sentence Summary.
4. Break the content into logical sections.
5. Extract 3-5 keywords as tags.

Respond ONLY with a JSON object in this exact format:
{
  "subject": "string",
  "title": "string",
  "summary": "string",
  "tags": ["string"],
  "sections": [
    {
      "heading": "string",
      "content": "string",
      "type": "definition" -- one of: "definition", "example", "theory", "formula"
    }
  ]
}
PROMPT;

        $userPrompt = "Transcript:\n\n" . substr($transcript, 0, 20000);
        $raw = $this->callChatApi([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ], $this->defaultModel, true);

        $parsed = $this->safeParseJson($raw);
        return [
            'subject' => $parsed['subject'] ?? 'General',
            'title' => $parsed['title'] ?? 'Lecture Notes',
            'summary' => $parsed['summary'] ?? substr($transcript, 0, 200) . '...',
            'tags' => is_array($parsed['tags'] ?? null) ? $parsed['tags'] : ['lecture', 'notes'],
            'sections' => is_array($parsed['sections'] ?? null) ? $parsed['sections'] : [
                ['heading' => 'Lecture Content', 'content' => $transcript, 'type' => 'theory']
            ]
        ];
    }

    /**
     * Deep thinking / step-by-step explanation using DeepSeek Reasoner
     */
    public function solveWithThinking(string $context, string $question): string
    {
        $systemPrompt = "You are a helpful academic tutor. Provide clear, step-by-step explanations.";
        $userPrompt = "Context from document: \"$context\"\n\nStudent Question: \"$question\"\n\nProvide a clear, step-by-step explanation or solution.";

        return $this->callChatApi([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ], $this->reasonerModel, false);
    }

    /**
     * Quick concise answer using Chat model
     */
    public function getQuickAnswer(string $context, string $question): string
    {
        $shortContext = substr($context, max(0, strlen($context) - 3000));
        $systemPrompt = "You are a helpful study assistant. Provide a concise, direct answer (1-3 sentences).";
        $userPrompt = "Context: \"$shortContext\"\n\nUser asked: \"$question\"";

        return $this->callChatApi([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ], $this->defaultModel, false);
    }

    /**
     * Generate 8-12 flashcards from note content
     */
    public function generateFlashcards(string $noteContent): array
    {
        $systemPrompt = <<<PROMPT
You are a study assistant. Generate flashcards from the given lecture/note content.
Create 8-12 flashcards covering the key concepts, definitions, and important facts.

Respond ONLY with a JSON object in this exact format:
{
  "flashcards": [
    { "front": "Question or term", "back": "Answer or definition" }
  ]
}

Make the questions clear and concise. The answers should be informative but brief.
PROMPT;

        $userPrompt = "Notes:\n\n" . substr($noteContent, 0, 8000);
        $raw = $this->callChatApi([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ], $this->defaultModel, true);

        $parsed = $this->safeParseJson($raw);
        $cards = $parsed['flashcards'] ?? [];

        if (empty($cards)) {
            $cards = [
                ['front' => 'Key Concept', 'back' => 'Primary principle discussed in this material.'],
                ['front' => 'Core Application', 'back' => 'How this concept is used in practical scenarios.']
            ];
        }

        return array_map(function ($c, $i) {
            return [
                'id' => 'fc-' . ($i + 1),
                'front' => $c['front'] ?? 'Question',
                'back' => $c['back'] ?? 'Answer'
            ];
        }, $cards, array_keys($cards));
    }

    /**
     * Generate 6-10 quiz questions from note content
     */
    public function generateQuiz(string $noteContent): array
    {
        $systemPrompt = <<<PROMPT
You are a quiz generator. Create a multiple-choice quiz from the given lecture/note content.
Generate 6-10 questions that test understanding of key concepts.

Respond ONLY with a JSON object in this exact format:
{
  "questions": [
    {
      "question": "What is...?",
      "options": ["Option A", "Option B", "Option C", "Option D"],
      "correctAnswer": 0,
      "explanation": "Brief explanation of why this is correct"
    }
  ]
}

Rules:
- Each question must have exactly 4 options
- correctAnswer is the 0-based index of the correct option (0, 1, 2, or 3)
- Provide a clear, educational explanation for each answer
- Make questions progressively harder
PROMPT;

        $userPrompt = "Notes:\n\n" . substr($noteContent, 0, 8000);
        $raw = $this->callChatApi([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ], $this->defaultModel, true);

        $parsed = $this->safeParseJson($raw);
        $questions = $parsed['questions'] ?? [];

        if (empty($questions)) {
            $questions = [
                [
                    'question' => 'What is the main topic covered in these notes?',
                    'options' => ['Core Concept Definition', 'Historical Context', 'Experimental Data', 'Theoretical Framework'],
                    'correctAnswer' => 0,
                    'explanation' => 'The notes primarily introduce and define the fundamental concept.'
                ]
            ];
        }

        return array_map(function ($q, $i) {
            return [
                'id' => 'quiz-' . ($i + 1),
                'question' => $q['question'] ?? 'Question',
                'options' => is_array($q['options'] ?? null) ? $q['options'] : ['A', 'B', 'C', 'D'],
                'correctAnswer' => is_numeric($q['correctAnswer'] ?? null) ? (int)$q['correctAnswer'] : 0,
                'explanation' => $q['explanation'] ?? 'Correct concept explanation.'
            ];
        }, $questions, array_keys($questions));
    }

    /**
     * Semantic search over note metadata
     */
    public function performSemanticSearch(string $query, array $notesMetadata): array
    {
        if (empty(trim($query)) || empty($notesMetadata)) {
            return [];
        }

        $systemPrompt = <<<PROMPT
You are a search assistant. Given a user query and a list of note metadata (id, title, subject, tags), identify which notes are relevant to the query.

Respond ONLY with a JSON object in this exact format:
{
  "noteIds": ["id1", "id2"]
}

Return only the IDs of relevant notes. If none are relevant, return {"noteIds": []}.
PROMPT;

        $userPrompt = "User Query: \"$query\"\n\nNotes:\n" . json_encode(array_slice($notesMetadata, 0, 50));
        $raw = $this->callChatApi([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ], $this->defaultModel, true);

        $parsed = $this->safeParseJson($raw);
        return is_array($parsed['noteIds'] ?? null) ? $parsed['noteIds'] : [];
    }

    /**
     * Process image text to structured note
     */
    public function convertImageToNote(string $extractedText): array
    {
        $systemPrompt = <<<PROMPT
You are an OCR and note-structuring assistant. You will receive text extracted from an image of notes, a whiteboard, or a document.
Your task:
1. Clean up and organize the text.
2. Create a relevant Title.
3. Write a short Summary.
4. Extract 3-5 tags.

Respond ONLY with a JSON object in this exact format:
{
  "title": "string",
  "content": "string (the full cleaned-up text)",
  "summary": "string",
  "tags": ["string"]
}
PROMPT;

        $raw = $this->callChatApi([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "Text extracted from image:\n\n" . $extractedText]
        ], $this->defaultModel, true);

        $parsed = $this->safeParseJson($raw);
        return [
            'title' => $parsed['title'] ?? 'Image Notes',
            'content' => $parsed['content'] ?? $extractedText,
            'summary' => $parsed['summary'] ?? 'Notes captured from uploaded image.',
            'tags' => is_array($parsed['tags'] ?? null) ? $parsed['tags'] : ['image', 'ocr']
        ];
    }

    /**
     * Process PDF document text to structured summary
     */
    public function processPdfText(string $rawText): array
    {
        $systemPrompt = <<<PROMPT
You are an academic document analyzer. Analyze the provided text and return a SHORT structured summary.
Rules:
- Title: max 10 words
- Summary: exactly 3 sentences
- Sections: max 4 sections, each content max 2 sentences
- Tags: 3-5 single words
- Keep your ENTIRE response under 1000 tokens

Return JSON:
{"title":"...","summary":"...","tags":["..."],"sections":[{"heading":"...","content":"...","type":"theory"}]}
PROMPT;

        $raw = $this->callChatApi([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "Analyze this text:\n" . substr($rawText, 0, 8000)]
        ], $this->defaultModel, true);

        $parsed = $this->safeParseJson($raw);
        return [
            'title' => $parsed['title'] ?? 'PDF Document',
            'summary' => $parsed['summary'] ?? 'Extracted PDF document summary.',
            'sections' => is_array($parsed['sections'] ?? null) ? $parsed['sections'] : [],
            'tags' => is_array($parsed['tags'] ?? null) ? $parsed['tags'] : ['pdf', 'document'],
            'rawText' => $rawText
        ];
    }

    /**
     * Mock response generator if no API key is supplied
     */
    private function getMockResponse(array $messages, bool $jsonMode): string
    {
        $lastMessage = end($messages)['content'] ?? '';

        if ($jsonMode) {
            if (str_contains($lastMessage, 'flashcards')) {
                return json_encode([
                    'flashcards' => [
                        ['front' => 'What is the main topic?', 'back' => 'The fundamental principles discussed in the lecture.'],
                        ['front' => 'Key Definition', 'back' => 'A structured explanation of the core concept.'],
                        ['front' => 'Important Formula/Rule', 'back' => 'Primary equation or standard rule of operation.']
                    ]
                ]);
            }
            if (str_contains($lastMessage, 'quiz')) {
                return json_encode([
                    'questions' => [
                        [
                            'question' => 'What is the primary concept covered in this material?',
                            'options' => ['Fundamental Theory', 'Secondary Application', 'Historical Background', 'Advanced Nuances'],
                            'correctAnswer' => 0,
                            'explanation' => 'The material highlights the fundamental theoretical principles.'
                        ],
                        [
                            'question' => 'How should key principles be applied?',
                            'options' => ['Systematically following established rules', 'Randomly', 'Only in theory', 'Without validation'],
                            'correctAnswer' => 0,
                            'explanation' => 'Systematic application ensures consistent and verified results.'
                        ]
                    ]
                ]);
            }
            if (str_contains($lastMessage, 'noteIds')) {
                return json_encode(['noteIds' => []]);
            }
            return json_encode([
                'title' => 'Structured Study Note',
                'subject' => 'General',
                'summary' => 'Comprehensive summary generated from the provided study content.',
                'tags' => ['study', 'ai', 'notes'],
                'sections' => [
                    ['heading' => 'Core Overview', 'content' => 'Key takeaways extracted from the text.', 'type' => 'theory'],
                    ['heading' => 'Application', 'content' => 'How to put these concepts into practice.', 'type' => 'example']
                ]
            ]);
        }

        return "This is a detailed analysis based on your note context. The key takeaway is to review the definitions, verify the core theorems, and test your understanding using flashcards.";
    }
}
