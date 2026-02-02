import React, { useState, useRef, useEffect } from 'react';
import { Note } from '../types';
import { getQuickAnswer } from '../services/geminiService';
import { Save, Bot, Loader2, ArrowLeft, Cloud } from 'lucide-react';

interface NotepadProps {
  initialNote?: Note | null;
  subject?: string;
  onSave: (note: Note) => void;
  onCancel: () => void;
}

const AUTOSAVE_KEY = 'scholarai_notepad_draft';

const Notepad: React.FC<NotepadProps> = ({ initialNote, subject, onSave, onCancel }) => {
  const [title, setTitle] = useState(initialNote?.title || 'Untitled Note');
  const [content, setContent] = useState(initialNote?.rawContent || '');
  const [isAiThinking, setIsAiThinking] = useState(false);
  const [lastSaved, setLastSaved] = useState<Date | null>(null);
  const textareaRef = useRef<HTMLTextAreaElement>(null);

  // Load Auto-save Draft on Mount
  useEffect(() => {
    const saved = localStorage.getItem(AUTOSAVE_KEY);
    if (saved) {
      try {
        const parsed = JSON.parse(saved);
        const currentId = initialNote?.id || 'new';

        // Restore if the draft matches the current context (New Note or Same ID)
        if (parsed.noteId === currentId) {
          setTitle(parsed.title);
          setContent(parsed.content);
          setLastSaved(new Date(parsed.timestamp));
        }
      } catch (e) {
        console.error("Failed to restore draft", e);
      }
    }
  }, [initialNote?.id]);

  // Auto-save Interval
  useEffect(() => {
    const interval = setInterval(() => {
      const noteId = initialNote?.id || 'new';
      
      // Avoid saving empty new notes
      if (noteId === 'new' && !title.trim() && !content.trim()) return;

      const draft = {
        noteId,
        title,
        content,
        timestamp: Date.now()
      };
      
      localStorage.setItem(AUTOSAVE_KEY, JSON.stringify(draft));
      setLastSaved(new Date());
    }, 30000); // Save every 30 seconds

    return () => clearInterval(interval);
  }, [title, content, initialNote?.id]);

  // Restore cursor position after updates if needed
  useEffect(() => {
     if (textareaRef.current && isAiThinking === false) {
         // This effect runs when thinking stops (answer arrived)
         // We could auto-scroll to bottom or manage cursor here
         textareaRef.current.scrollTop = textareaRef.current.scrollHeight;
     }
  }, [isAiThinking]);

  const handleKeyDown = async (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    // Check for '?' key
    if (e.key === '?') {
        e.preventDefault(); // We'll insert manually to handle state sync

        const textarea = e.currentTarget;
        const cursorPosition = textarea.selectionStart;
        const selectionEnd = textarea.selectionEnd;

        // 1. Insert the '?' into the content
        const newContent = content.substring(0, cursorPosition) + "?" + content.substring(selectionEnd);
        setContent(newContent);

        // 2. Adjust cursor position immediately (requires setTimeout for React render cycle)
        setTimeout(() => {
            if (textareaRef.current) {
                textareaRef.current.selectionStart = cursorPosition + 1;
                textareaRef.current.selectionEnd = cursorPosition + 1;
            }
        }, 0);

        // 3. Extract the question text
        const textBeforeCursor = content.substring(0, cursorPosition);
        
        // Find the start of the current sentence (look for previous punctuation or newline)
        const lastSentenceEnd = Math.max(
            textBeforeCursor.lastIndexOf('.'),
            textBeforeCursor.lastIndexOf('!'),
            textBeforeCursor.lastIndexOf('?'),
            textBeforeCursor.lastIndexOf('\n')
        );

        // Extract clean question text
        const questionText = textBeforeCursor.substring(lastSentenceEnd + 1).trim();

        // 4. Trigger AI if we have a valid question
        if (questionText.length > 2) {
            setIsAiThinking(true);
            try {
                // Pass context including the newly added '?'
                const answer = await getQuickAnswer(newContent, questionText + "?");
                
                if (answer) {
                    setIsAiThinking(false);
                    setContent(prev => {
                        // Insert answer after the '?'
                        // We use the insertion point based on where '?' was added
                        const insertionPoint = cursorPosition + 1;
                        const before = prev.substring(0, insertionPoint);
                        const after = prev.substring(insertionPoint);
                        return before + "\n[AI]: " + answer + "\n\n" + after;
                    });
                } else {
                    setIsAiThinking(false);
                }
            } catch (err) {
                console.error(err);
                setIsAiThinking(false);
            }
        }
    }
  };

  const handleSave = () => {
    const newNote: Note = {
      id: initialNote?.id || crypto.randomUUID(),
      title,
      subject: subject || initialNote?.subject || 'General',
      date: initialNote?.date || new Date().toLocaleDateString(),
      type: 'text',
      rawContent: content,
      summary: content.substring(0, 100) + "...", 
      sections: [], 
      tags: [],
    };
    
    // Clear draft on successful save
    localStorage.removeItem(AUTOSAVE_KEY);
    onSave(newNote);
  };

  return (
    <div className="flex flex-col h-full bg-desk relative">
      
      {/* Header (Toolbar) */}
      <div className="absolute top-0 left-0 right-0 h-16 bg-white/80 backdrop-blur-md border-b border-stone-200 z-10 flex items-center justify-between px-6 shadow-sm">
        <div className="flex items-center gap-4 w-full">
            <button onClick={onCancel} className="p-2 hover:bg-stone-100 rounded-full text-stone-500 transition-colors">
                <ArrowLeft className="w-5 h-5" />
            </button>
            <input 
                type="text" 
                value={title} 
                onChange={(e) => setTitle(e.target.value)}
                className="text-2xl font-hand font-bold bg-transparent border-none focus:outline-none text-stone-800 placeholder-stone-400 w-full"
                placeholder="Note Title..."
            />
        </div>
        <div className="flex items-center gap-3">
             {lastSaved && (
                <div className="hidden md:flex items-center gap-1.5 text-xs text-stone-400 font-medium mr-2 animate-fade-in">
                    <Cloud className="w-3 h-3" />
                    <span>Saved {lastSaved.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                </div>
             )}
             {isAiThinking && (
                 <div className="flex items-center gap-2 text-orange-600 text-sm animate-pulse px-3 py-1 bg-orange-50 rounded-full border border-orange-200">
                     <Bot className="w-4 h-4" />
                     <span className="font-hand font-bold">Thinking...</span>
                 </div>
             )}
             <button 
                onClick={handleSave}
                className="flex items-center gap-2 px-6 py-2 bg-stone-800 hover:bg-stone-900 text-white rounded-lg font-hand text-lg transition-colors shadow-lg shadow-stone-300"
             >
                <Save className="w-4 h-4" />
                Save
             </button>
        </div>
      </div>
      
      {/* Writing Area */}
      <div className="flex-1 overflow-hidden pt-20 pb-8 px-4 md:px-0">
        <div className="max-w-3xl mx-auto bg-paper shadow-2xl h-full relative lined-paper rounded-sm overflow-hidden flex flex-col">
            
            {/* Top hole punches visual */}
            <div className="absolute top-4 left-0 right-0 flex justify-center gap-20 pointer-events-none opacity-20">
                <div className="w-4 h-4 rounded-full bg-stone-900"></div>
                <div className="w-4 h-4 rounded-full bg-stone-900"></div>
            </div>

            <textarea
                ref={textareaRef}
                value={content}
                onChange={(e) => setContent(e.target.value)}
                onKeyDown={handleKeyDown}
                placeholder="Start writing... (Tip: Just type '?' to get an instant AI answer)"
                className="w-full h-full p-12 pt-16 text-xl text-ink leading-[2rem] resize-none focus:outline-none bg-transparent font-hand"
                spellCheck={false}
            />
        </div>
      </div>
    </div>
  );
};

export default Notepad;
