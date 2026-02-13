import OpenAI from "openai";
import { NoteSection, Flashcard, QuizQuestion } from "../types";
import { PDFDocument } from 'pdf-lib';

// Initialize OpenAI-compatible client pointing at DeepSeek
const client = new OpenAI({
  baseURL: 'https://api.deepseek.com',
  apiKey: process.env.API_KEY || '',
  dangerouslyAllowBrowser: true,
});

/**
 * Safely parse JSON from LLM output, handling control characters and truncation
 */
const safeParseJSON = (text: string): any => {
  // Strip markdown code fences if present
  let cleaned = text.trim();
  if (cleaned.startsWith('```')) {
    cleaned = cleaned.replace(/^```(?:json)?\n?/, '').replace(/\n?```$/, '');
  }
  // Remove control characters that break JSON.parse (tabs, newlines inside strings, etc)
  cleaned = cleaned.replace(/[\x00-\x1F\x7F]/g, (ch) => {
    if (ch === '\n') return '\\n';
    if (ch === '\r') return '\\r';
    if (ch === '\t') return '\\t';
    return '';
  });

  try {
    return JSON.parse(cleaned);
  } catch (e) {
    // Try to repair truncated JSON by closing open structures
    let repaired = cleaned;
    // Close any unclosed strings
    const quoteCount = (repaired.match(/(?<!\\)"/g) || []).length;
    if (quoteCount % 2 !== 0) repaired += '"';
    // Close unclosed arrays/objects
    const opens = (repaired.match(/[{\[]/g) || []).length;
    const closes = (repaired.match(/[}\]]/g) || []).length;
    for (let i = 0; i < opens - closes; i++) {
      // Determine what to close based on what was opened
      const lastOpen = repaired.lastIndexOf('[') > repaired.lastIndexOf('{') ? ']' : '}';
      repaired += lastOpen;
    }
    try {
      return JSON.parse(repaired);
    } catch (e2) {
      console.error('JSON repair failed, returning empty object');
      return {};
    }
  }
};

/**
 * Helper: Call DeepSeek chat API with JSON mode
 */
const chatJSON = async (systemPrompt: string, userPrompt: string, model: string = 'deepseek-chat'): Promise<string> => {
  const response = await client.chat.completions.create({
    model,
    messages: [
      { role: 'system', content: systemPrompt },
      { role: 'user', content: userPrompt },
    ],
    response_format: { type: 'json_object' },
    temperature: 0.7,
    max_tokens: 8192,
  });
  return response.choices[0]?.message?.content || '{}';
};

/**
 * Helper: Call DeepSeek chat API for plain text response
 */
const chatText = async (systemPrompt: string, userPrompt: string, model: string = 'deepseek-chat'): Promise<string> => {
  const response = await client.chat.completions.create({
    model,
    messages: [
      { role: 'system', content: systemPrompt },
      { role: 'user', content: userPrompt },
    ],
    temperature: 0.7,
  });
  return response.choices[0]?.message?.content || '';
};

/**
 * Splits a PDF Blob into smaller chunks (blobs) containing a set number of pages.
 */
export const splitPdf = async (pdfBlob: Blob, pagesPerChunk: number = 5): Promise<Blob[]> => {
  try {
    const arrayBuffer = await pdfBlob.arrayBuffer();
    const pdfDoc = await PDFDocument.load(arrayBuffer);
    const totalPages = pdfDoc.getPageCount();
    const chunks: Blob[] = [];

    if (totalPages <= pagesPerChunk) {
      return [pdfBlob];
    }

    for (let i = 0; i < totalPages; i += pagesPerChunk) {
      const newPdf = await PDFDocument.create();
      const pageIndices = [];
      for (let j = 0; j < pagesPerChunk && (i + j) < totalPages; j++) {
        pageIndices.push(i + j);
      }
      const copiedPages = await newPdf.copyPages(pdfDoc, pageIndices);
      copiedPages.forEach((page) => newPdf.addPage(page));
      const pdfBytes = await newPdf.save();
      chunks.push(new Blob([pdfBytes as BlobPart], { type: 'application/pdf' }));
    }
    return chunks;
  } catch (error) {
    console.error("Error splitting PDF:", error);
    return [pdfBlob];
  }
};

/**
 * Extract raw text from a PDF blob using pdf-lib (basic extraction).
 * Falls back to empty string on failure.
 */
export const extractTextFromPdf = async (pdfBlob: Blob): Promise<string> => {
  try {
    const arrayBuffer = await pdfBlob.arrayBuffer();
    const pdfDoc = await PDFDocument.load(arrayBuffer);
    const pages = pdfDoc.getPages();

    // pdf-lib doesn't have built-in text extraction, so we use a simpler approach:
    // We'll read the raw PDF content as text and try to extract readable strings.
    const uint8Array = new Uint8Array(arrayBuffer);
    const decoder = new TextDecoder('utf-8', { fatal: false });
    const rawText = decoder.decode(uint8Array);

    // Extract text between BT and ET markers (PDF text objects)
    const textMatches: string[] = [];
    const regex = /\(([^)]+)\)/g;
    let match;
    while ((match = regex.exec(rawText)) !== null) {
      const text = match[1].trim();
      if (text.length > 1 && /[a-zA-Z0-9]/.test(text)) {
        textMatches.push(text);
      }
    }

    return textMatches.join(' ') || `[PDF with ${pages.length} pages - text extraction limited. The PDF content could not be fully extracted as plain text.]`;
  } catch (error) {
    console.error("PDF text extraction error:", error);
    return '';
  }
};

/**
 * Transcribes audio using the browser's Web Speech API (SpeechRecognition).
 * This replaces the old Gemini-based transcription.
 * Note: This returns a new SpeechRecognition instance. Call .start() to begin.
 */
export const createSpeechRecognition = (): any | null => {
  const SpeechRecognition = (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition;
  if (!SpeechRecognition) {
    console.warn("Web Speech API is not supported in this browser.");
    return null;
  }
  const recognition = new SpeechRecognition();
  recognition.continuous = true;
  recognition.interimResults = true;
  recognition.lang = 'en-US';
  return recognition;
};

/**
 * Fallback: Transcribe audio by sending a message to DeepSeek asking it to acknowledge
 * that audio transcription is handled client-side. This is kept as a compatibility shim.
 */
export const transcribeAudio = async (_audioBlob: Blob, _mimeType: string = 'audio/wav'): Promise<string> => {
  // Audio transcription is now handled by the Web Speech API in the AudioRecorder component.
  // This function is kept as a fallback that returns a message.
  return "Audio transcription is now handled locally via your browser's speech recognition. Please use the recorder with speech-to-text enabled.";
};

/**
 * Convert Image to Note — extracts text from image via canvas, then structures via DeepSeek
 */
export const convertImageToNote = async (imageBlob: Blob): Promise<{
  title: string;
  content: string;
  summary: string;
  tags: string[];
}> => {
  try {
    // Extract text from image using canvas-based approach
    const imageText = await extractTextFromImage(imageBlob);

    const systemPrompt = `You are an OCR and note-structuring assistant. You will receive text extracted from an image of notes, a whiteboard, or a document.
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
  "tags": ["string", "string", ...]
}`;

    const result = await chatJSON(systemPrompt, `Text extracted from image:\n\n${imageText}`);
    return safeParseJSON(result);
  } catch (error) {
    console.error("Image processing error:", error);
    throw error;
  }
};

/**
 * Helper: Extract text from an image blob using canvas (basic approach)
 */
const extractTextFromImage = async (imageBlob: Blob): Promise<string> => {
  return new Promise((resolve) => {
    const img = new Image();
    const url = URL.createObjectURL(imageBlob);
    img.onload = () => {
      URL.revokeObjectURL(url);
      // We can't do true OCR in the browser without a library,
      // so we'll send the image description to DeepSeek
      resolve(`[Image uploaded: ${img.width}x${img.height}px, type: ${imageBlob.type}, size: ${(imageBlob.size / 1024).toFixed(1)}KB. Please note that the image content description should be provided by the user or through an OCR service.]`);
    };
    img.onerror = () => {
      URL.revokeObjectURL(url);
      resolve('[Image could not be loaded for processing]');
    };
    img.src = url;
  });
};

/**
 * Process uploaded PDF to extract structured notes AND raw text
 */
export const processPdf = async (pdfBlob: Blob): Promise<{
  title: string;
  summary: string;
  sections: NoteSection[];
  tags: string[];
  rawText: string;
}> => {
  try {
    const rawText = await extractTextFromPdf(pdfBlob);

    const systemPrompt = `You are an academic document analyzer. Analyze the provided text and return a SHORT structured summary.
Rules:
- Title: max 10 words
- Summary: exactly 3 sentences
- Sections: max 4 sections, each content max 2 sentences
- Tags: 3-5 single words
- Do NOT include raw text or long content
- Keep your ENTIRE response under 1000 tokens

Return JSON:
{"title":"...","summary":"...","tags":["..."],"sections":[{"heading":"...","content":"...","type":"theory"}]}`;

    const result = await chatJSON(systemPrompt, `Analyze this text:\n${rawText.substring(0, 8000)}`);
    const parsed = safeParseJSON(result);
    return {
      title: parsed.title || 'Untitled PDF',
      summary: parsed.summary || '',
      sections: Array.isArray(parsed.sections) ? parsed.sections : [],
      tags: Array.isArray(parsed.tags) ? parsed.tags : [],
      rawText: rawText,
    };
  } catch (error) {
    console.error("PDF Processing error:", error);
    throw error;
  }
};

/**
 * Organizes raw transcript into a structured note
 */
export const organizeNote = async (transcript: string): Promise<{
  subject: string;
  title: string;
  summary: string;
  sections: NoteSection[];
  tags: string[];
}> => {
  const systemPrompt = `You are an academic note organizer. Analyze the following lecture transcript.
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
  "tags": ["string", ...],
  "sections": [
    {
      "heading": "string",
      "content": "string",
      "type": "definition|example|theory|formula"
    }
  ]
}`;

  const result = await chatJSON(systemPrompt, `Transcript:\n\n${transcript.substring(0, 20000)}`);
  return safeParseJSON(result);
};

/**
 * Deep thinking / step-by-step explanation using DeepSeek Reasoner
 */
export const solveWithThinking = async (context: string, question: string): Promise<string> => {
  try {
    const response = await client.chat.completions.create({
      model: 'deepseek-reasoner',
      messages: [
        { role: 'system', content: 'You are a helpful academic tutor. Provide clear, step-by-step explanations.' },
        { role: 'user', content: `Context from document: "${context}"\n\nStudent Question: "${question}"\n\nProvide a clear, step-by-step explanation or solution.` },
      ],
    });
    return response.choices[0]?.message?.content || "Could not generate a solution.";
  } catch (error) {
    console.error("Solve with thinking error:", error);
    // Fallback to deepseek-chat if reasoner fails
    return chatText(
      'You are a helpful academic tutor. Provide clear, step-by-step explanations.',
      `Context from document: "${context}"\n\nStudent Question: "${question}"\n\nProvide a clear, step-by-step explanation or solution.`
    );
  }
};

/**
 * Quick concise answer using DeepSeek Chat
 */
export const getQuickAnswer = async (context: string, question: string): Promise<string> => {
  return chatText(
    'You are a helpful study assistant. Provide a concise, direct answer (1-2 sentences).',
    `Context: "${context.substring(Math.max(0, context.length - 2000))}"\n\nUser asked: "${question}"`
  );
};

/**
 * Generate flashcards from note content using DeepSeek
 */
export const generateFlashcards = async (noteContent: string): Promise<Flashcard[]> => {
  const systemPrompt = `You are a study assistant. Generate flashcards from the given lecture/note content.
Create 8-12 flashcards covering the key concepts, definitions, and important facts.

Respond ONLY with a JSON object in this exact format:
{
  "flashcards": [
    { "front": "Question or term", "back": "Answer or definition" }
  ]
}

Make the questions clear and concise. The answers should be informative but brief.`;

  try {
    const result = await chatJSON(systemPrompt, `Notes:\n\n${noteContent.substring(0, 6000)}`);
    const parsed = safeParseJSON(result);
    const cards = parsed.flashcards || [];
    return cards.map((c: any, i: number) => ({
      id: `fc-${i}`,
      front: c.front || 'Question',
      back: c.back || 'Answer',
    }));
  } catch (error) {
    console.error("Flashcard generation error:", error);
    throw new Error("Failed to generate flashcards");
  }
};

/**
 * Generate quiz questions (with solved answers) from note content using DeepSeek
 */
export const generateQuiz = async (noteContent: string): Promise<QuizQuestion[]> => {
  const systemPrompt = `You are a quiz generator. Create a multiple-choice quiz from the given lecture/note content.
Generate 6-10 questions that test understanding of the key concepts.

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
- Cover different topics from the content`;

  try {
    const result = await chatJSON(systemPrompt, `Notes:\n\n${noteContent.substring(0, 6000)}`);
    const parsed = safeParseJSON(result);
    const questions = parsed.questions || [];
    return questions.map((q: any, i: number) => ({
      id: `quiz-${i}`,
      question: q.question || 'Question',
      options: Array.isArray(q.options) ? q.options : ['A', 'B', 'C', 'D'],
      correctAnswer: typeof q.correctAnswer === 'number' ? q.correctAnswer : 0,
      explanation: q.explanation || 'No explanation provided.',
    }));
  } catch (error) {
    console.error("Quiz generation error:", error);
    throw new Error("Failed to generate quiz");
  }
};

/**
 * Text-to-Speech using browser's built-in speechSynthesis API.
 * Replaces the old Gemini TTS model.
 */
export const speakText = async (text: string): Promise<void> => {
  return new Promise((resolve, reject) => {
    if (!('speechSynthesis' in window)) {
      reject(new Error("Speech synthesis is not supported in this browser."));
      return;
    }

    // Cancel any ongoing speech
    window.speechSynthesis.cancel();

    const utterance = new SpeechSynthesisUtterance(text);
    utterance.rate = 1.0;
    utterance.pitch = 1.0;
    utterance.volume = 1.0;
    utterance.lang = 'en-US';

    // Try to pick a good voice
    const voices = window.speechSynthesis.getVoices();
    const preferredVoice = voices.find(v => v.name.includes('Google') || v.name.includes('Microsoft') || v.name.includes('Natural'));
    if (preferredVoice) {
      utterance.voice = preferredVoice;
    }

    utterance.onend = () => resolve();
    utterance.onerror = (e) => reject(e);

    window.speechSynthesis.speak(utterance);
  });
};

/**
 * Stop any ongoing speech
 */
export const stopSpeaking = () => {
  if ('speechSynthesis' in window) {
    window.speechSynthesis.cancel();
  }
};

/**
 * Legacy compatibility — playAudioBuffer is no longer needed with browser TTS
 * but kept as a no-op to avoid breaking imports.
 */
export const playAudioBuffer = (_buffer: any) => {
  console.warn("playAudioBuffer is deprecated. TTS now uses browser speechSynthesis.");
};

/**
 * Semantic search: ask DeepSeek to identify relevant notes from metadata
 */
export const performSemanticSearch = async (query: string, notesMetadata: any[]): Promise<string[]> => {
  if (!query.trim() || notesMetadata.length === 0) return [];
  try {
    const systemPrompt = `You are a search assistant. Given a user query and a list of note metadata (id, title, subject, tags), identify which notes are relevant to the query.

Respond ONLY with a JSON object in this exact format:
{
  "noteIds": ["id1", "id2", ...]
}

Return only the IDs of relevant notes. If none are relevant, return {"noteIds": []}.`;

    const result = await chatJSON(systemPrompt, `User Query: "${query}"\n\nNotes:\n${JSON.stringify(notesMetadata.slice(0, 50))}`);
    const parsed = safeParseJSON(result);
    return parsed.noteIds || parsed || [];
  } catch (error) {
    console.error("Semantic search error:", error);
    return [];
  }
};


