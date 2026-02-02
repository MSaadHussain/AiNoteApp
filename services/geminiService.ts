import { GoogleGenAI, Type, Modality } from "@google/genai";
import { NoteSection, Flashcard, QuizQuestion } from "../types";
import { PDFDocument } from 'pdf-lib';

// Helper to convert Blob to Base64
const blobToBase64 = (blob: Blob): Promise<string> => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onloadend = () => {
      const base64String = reader.result as string;
      // Remove data url prefix (e.g. "data:audio/wav;base64,")
      resolve(base64String.split(',')[1]);
    };
    reader.onerror = reject;
    reader.readAsDataURL(blob);
  });
};

// Initialize GenAI
// Note: In a real app, ensure API key is secure.
const ai = new GoogleGenAI({ apiKey: process.env.API_KEY || '' });

/**
 * Splits a PDF Blob into smaller chunks (blobs) containing a set number of pages.
 */
export const splitPdf = async (pdfBlob: Blob, pagesPerChunk: number = 5): Promise<Blob[]> => {
  try {
    const arrayBuffer = await pdfBlob.arrayBuffer();
    const pdfDoc = await PDFDocument.load(arrayBuffer);
    const totalPages = pdfDoc.getPageCount();
    const chunks: Blob[] = [];

    // If less than or equal to limit, return original (as array)
    if (totalPages <= pagesPerChunk) {
      return [pdfBlob];
    }

    for (let i = 0; i < totalPages; i += pagesPerChunk) {
      const newPdf = await PDFDocument.create();
      const pageIndices = [];
      
      // Calculate indices for this chunk
      for (let j = 0; j < pagesPerChunk && (i + j) < totalPages; j++) {
        pageIndices.push(i + j);
      }
      
      const copiedPages = await newPdf.copyPages(pdfDoc, pageIndices);
      copiedPages.forEach((page) => newPdf.addPage(page));
      
      const pdfBytes = await newPdf.save();
      chunks.push(new Blob([pdfBytes], { type: 'application/pdf' }));
    }
    return chunks;
  } catch (error) {
    console.error("Error splitting PDF:", error);
    // Fallback: return original if splitting fails
    return [pdfBlob];
  }
};

/**
 * Transcribes audio using Gemini Flash (Multimodal)
 */
export const transcribeAudio = async (audioBlob: Blob, mimeType: string = 'audio/wav'): Promise<string> => {
  try {
    const base64Audio = await blobToBase64(audioBlob);

    const response = await ai.models.generateContent({
      model: 'gemini-3-flash-preview',
      contents: {
        parts: [
          {
            inlineData: {
              mimeType: mimeType,
              data: base64Audio,
            },
          },
          {
            text: "Transcribe this lecture audio accurately. Capture the main speaker's words verbatim where possible, but remove excessive filler words like 'um' or 'ah'. return ONLY the transcript text.",
          },
        ],
      },
    });

    return response.text || "Transcription failed.";
  } catch (error) {
    console.error("Transcription error:", error);
    throw error;
  }
};

/**
 * Process uploaded PDF to extract structured notes
 */
export const processPdf = async (pdfBlob: Blob): Promise<{
  title: string;
  summary: string;
  sections: NoteSection[];
  tags: string[];
}> => {
  try {
    const base64Pdf = await blobToBase64(pdfBlob);

    // Gemini 2.5 Flash handles PDFs well
    const response = await ai.models.generateContent({
      model: 'gemini-3-flash-preview',
      contents: {
        parts: [
          {
            inlineData: {
              mimeType: 'application/pdf',
              data: base64Pdf,
            },
          },
          {
            text: `Analyze this PDF document. 
            1. Create a concise Title.
            2. Write a short 3-sentence Summary.
            3. Break the content into logical sections. For each section, determine its type (definition, example, theory, formula).
            4. Extract 3-5 keywords as tags.`,
          },
        ],
      },
      config: {
        responseMimeType: "application/json",
        responseSchema: {
          type: Type.OBJECT,
          properties: {
            title: { type: Type.STRING },
            summary: { type: Type.STRING },
            tags: { type: Type.ARRAY, items: { type: Type.STRING } },
            sections: {
              type: Type.ARRAY,
              items: {
                type: Type.OBJECT,
                properties: {
                  heading: { type: Type.STRING },
                  content: { type: Type.STRING },
                  type: { type: Type.STRING, enum: ['definition', 'example', 'theory', 'formula'] }
                }
              }
            }
          }
        }
      }
    });

    const jsonText = response.text;
    if (!jsonText) throw new Error("Failed to process PDF");
    return JSON.parse(jsonText);
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
  const response = await ai.models.generateContent({
    model: 'gemini-3-pro-preview', // Using Pro for better structuring logic
    contents: `Analyze the following lecture transcript. 
    1. Identify the academic Subject (e.g., Physics, History, CS).
    2. Create a concise Title.
    3. Write a short 3-sentence Summary.
    4. Break the content into logical sections. For each section, determine its type (definition, example, theory, formula).
    5. Extract 3-5 keywords as tags.
    
    Transcript: ${transcript.substring(0, 20000)}`, // Limit context if very long
    config: {
      responseMimeType: "application/json",
      responseSchema: {
        type: Type.OBJECT,
        properties: {
          subject: { type: Type.STRING },
          title: { type: Type.STRING },
          summary: { type: Type.STRING },
          tags: { type: Type.ARRAY, items: { type: Type.STRING } },
          sections: {
            type: Type.ARRAY,
            items: {
              type: Type.OBJECT,
              properties: {
                heading: { type: Type.STRING },
                content: { type: Type.STRING },
                type: { type: Type.STRING, enum: ['definition', 'example', 'theory', 'formula'] }
              }
            }
          }
        }
      }
    }
  });

  const jsonText = response.text;
  if (!jsonText) throw new Error("Failed to organize notes");
  return JSON.parse(jsonText);
};

/**
 * Solves a specific question or explains a concept using the Thinking Model
 */
export const solveWithThinking = async (context: string, question: string): Promise<string> => {
  const response = await ai.models.generateContent({
    model: 'gemini-3-pro-preview',
    contents: `Context from lecture notes: "${context}"\n\nStudent Question: "${question}"\n\nProvide a clear, step-by-step explanation or solution suitable for a student.`,
    config: {
      thinkingConfig: {
        thinkingBudget: 32768, // Max budget for deep reasoning
      },
    },
  });

  return response.text || "Could not generate a solution.";
};

/**
 * Quick AI Answer for Notepad
 */
export const getQuickAnswer = async (context: string, question: string): Promise<string> => {
  const response = await ai.models.generateContent({
    model: 'gemini-3-flash-preview',
    contents: `You are a helpful study assistant. The user is writing notes. 
    Context of notes so far: "${context.substring(Math.max(0, context.length - 1000))}"
    
    User asked: "${question}"
    
    Provide a concise, direct answer (1-2 sentences) to complete their thought or answer the question. Do not start with "Here is the answer". Just give the answer.`,
  });
  return response.text || "";
};

/**
 * Generates flashcards from note content
 */
export const generateFlashcards = async (noteContent: string): Promise<Flashcard[]> => {
  const response = await ai.models.generateContent({
    model: 'gemini-3-flash-preview',
    contents: `Create 5 high-quality flashcards based on these notes. Return JSON. Notes: ${noteContent.substring(0, 10000)}`,
    config: {
      responseMimeType: "application/json",
      responseSchema: {
        type: Type.ARRAY,
        items: {
          type: Type.OBJECT,
          properties: {
            id: { type: Type.STRING },
            front: { type: Type.STRING },
            back: { type: Type.STRING }
          }
        }
      }
    }
  });
  
  const text = response.text;
  if(!text) return [];
  const cards = JSON.parse(text);
  // Add unique IDs if AI didn't generate good ones
  return cards.map((c: any, i: number) => ({ ...c, id: c.id || `card-${Date.now()}-${i}` }));
};

/**
 * Generates a Quiz from note content
 */
export const generateQuiz = async (noteContent: string): Promise<QuizQuestion[]> => {
    const response = await ai.models.generateContent({
      model: 'gemini-3-flash-preview',
      contents: `Create 5 multiple-choice questions (MCQs) to test a student's understanding of these notes. 
      Provide 4 options for each question. 
      Indicate the correct answer index (0-3). 
      Provide a short explanation for the correct answer.
      
      Notes: ${noteContent.substring(0, 10000)}`,
      config: {
        responseMimeType: "application/json",
        responseSchema: {
          type: Type.ARRAY,
          items: {
            type: Type.OBJECT,
            properties: {
              id: { type: Type.STRING },
              question: { type: Type.STRING },
              options: { type: Type.ARRAY, items: { type: Type.STRING } },
              correctAnswer: { type: Type.INTEGER },
              explanation: { type: Type.STRING }
            }
          }
        }
      }
    });
    
    const text = response.text;
    if(!text) return [];
    const questions = JSON.parse(text);
    return questions.map((q: any, i: number) => ({ ...q, id: q.id || `quiz-${Date.now()}-${i}` }));
  };

/**
 * Text-to-Speech using Gemini 2.5 Flash TTS
 */
export const speakText = async (text: string): Promise<AudioBuffer> => {
  const response = await ai.models.generateContent({
    model: 'gemini-2.5-flash-preview-tts',
    contents: [{ parts: [{ text: text }] }],
    config: {
      responseModalities: [Modality.AUDIO],
      speechConfig: {
        voiceConfig: {
          prebuiltVoiceConfig: { voiceName: 'Puck' }, // 'Puck' is usually a clear voice
        },
      },
    },
  });

  const base64Audio = response.candidates?.[0]?.content?.parts?.[0]?.inlineData?.data;
  
  if (!base64Audio) {
    throw new Error("No audio generated");
  }

  // Decode audio
  const binaryString = atob(base64Audio);
  const len = binaryString.length;
  const bytes = new Uint8Array(len);
  for (let i = 0; i < len; i++) {
    bytes[i] = binaryString.charCodeAt(i);
  }

  const audioContext = new (window.AudioContext || (window as any).webkitAudioContext)();
  return await audioContext.decodeAudioData(bytes.buffer);
};

export const playAudioBuffer = (buffer: AudioBuffer) => {
    const audioContext = new (window.AudioContext || (window as any).webkitAudioContext)();
    const source = audioContext.createBufferSource();
    source.buffer = buffer;
    source.connect(audioContext.destination);
    source.start(0);
}

/**
 * Performs a semantic search across note metadata using AI.
 * Returns a list of Note IDs that match the query context.
 */
export const performSemanticSearch = async (
    query: string, 
    notesMetadata: { id: string; title: string; summary: string; tags: string[] }[]
): Promise<string[]> => {
    if (!query.trim() || notesMetadata.length === 0) return [];

    try {
        const response = await ai.models.generateContent({
            model: 'gemini-3-flash-preview',
            contents: `You are a semantic search engine for a student notebook.
            
            User Query: "${query}"

            Below is a list of notes (JSON). Identify the notes that are semantically relevant to the user's query, even if they don't contain exact keywords.
            For example, if query is "how things move", match notes about "Physics" or "Newton's Laws".
            
            Notes Data:
            ${JSON.stringify(notesMetadata.slice(0, 50))} 
            
            Return ONLY a JSON array of strings containing the 'id' of the relevant notes. If none are relevant, return an empty array.`,
            config: {
                responseMimeType: "application/json",
                responseSchema: {
                    type: Type.ARRAY,
                    items: { type: Type.STRING }
                }
            }
        });

        const text = response.text;
        if (!text) return [];
        return JSON.parse(text);
    } catch (error) {
        console.error("Semantic search failed:", error);
        return [];
    }
};
