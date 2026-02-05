export interface NoteSection {
  heading: string;
  content: string; // Markdown supported
  type: 'definition' | 'example' | 'theory' | 'formula';
}

export type NoteType = 'audio' | 'pdf' | 'text';

export interface Note {
  id: string;
  title: string;
  subject: string;
  date: string;
  type: NoteType;
  originalTranscript?: string; // For audio
  rawContent?: string; // For text/notepad/pdf-text
  pdfUrl?: string; // For PDF
  summary: string;
  sections: NoteSection[];
  tags: string[];
  audioUrl?: string; // Blob URL
}

export interface SubjectRegister {
  name: string;
  color: string;
  noteIds: string[];
}

export interface Flashcard {
  id: string;
  front: string;
  back: string;
}

export interface QuizQuestion {
  id: string;
  question: string;
  options: string[];
  correctAnswer: number; // Index of the correct option
  explanation: string;
}

export interface ChatMessage {
  role: 'user' | 'model';
  content: string;
  isThinking?: boolean;
}

export interface Reminder {
  id: string;
  text: string;
  dueDate: string;
  type: 'general' | 'subject' | 'note';
  targetId?: string;
  targetName?: string;
  completed: boolean;
}

export enum AppView {
  DASHBOARD = 'DASHBOARD',
  NOTE_VIEW = 'NOTE_VIEW',
  RECORDER = 'RECORDER',
  STUDY_MODE = 'STUDY_MODE',
  NOTEPAD = 'NOTEPAD',
  PDF_VIEW = 'PDF_VIEW', // New view for PDF interaction
}