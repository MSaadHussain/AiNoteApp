import React, { useState, useEffect } from 'react';
import { Note, Reminder } from '../types';
import { ArrowLeft, BookOpen, BrainCircuit, Sparkles, StickyNote, FileText, Bell, PenTool, X } from 'lucide-react';
import { solveWithThinking } from '../services/geminiService';

interface PdfReaderProps {
  note: Note;
  onClose: () => void;
  onAddReminder: (reminder: Reminder) => void;
  onCreateNoteFromText: (text: string, subject: string) => void;
}

const PdfReader: React.FC<PdfReaderProps> = ({ note, onClose, onAddReminder, onCreateNoteFromText }) => {
  const [activeTab, setActiveTab] = useState<'pdf' | 'text'>('pdf');
  const [selectedText, setSelectedText] = useState('');
  const [contextMenu, setContextMenu] = useState<{ visible: boolean; x: number; y: number } | null>(null);
  const [aiPanelOpen, setAiPanelOpen] = useState(true);
  const [aiQuery, setAiQuery] = useState('');
  const [aiResponse, setAiResponse] = useState('');
  const [isAiThinking, setIsAiThinking] = useState(false);

  // Close context menu on click
  useEffect(() => {
      const handleClick = () => setContextMenu(null);
      document.addEventListener('click', handleClick);
      return () => document.removeEventListener('click', handleClick);
  }, []);

  const handleTextSelection = () => {
      const selection = window.getSelection();
      if (selection && selection.toString().trim().length > 0) {
          setSelectedText(selection.toString().trim());
      }
  };

  const handleContextMenu = (e: React.MouseEvent) => {
      e.preventDefault();
      if (selectedText) {
          setContextMenu({ visible: true, x: e.clientX, y: e.clientY });
      }
  };

  const handleAiExplain = async () => {
      setContextMenu(null);
      setAiPanelOpen(true);
      setAiQuery(`Explain this: "${selectedText}"`);
      setIsAiThinking(true);
      setAiResponse('');
      try {
          const res = await solveWithThinking(note.rawContent || "", `Explain this specific text: "${selectedText}"`);
          setAiResponse(res);
      } catch (err) {
          setAiResponse("Sorry, I couldn't explain that.");
      } finally {
          setIsAiThinking(false);
      }
  };

  const handleCreateReminder = () => {
      const reminder: Reminder = {
          id: crypto.randomUUID(),
          text: `Read: "${selectedText.substring(0, 30)}..."`,
          dueDate: new Date(Date.now() + 86400000).toLocaleString(), // Tomorrow
          type: 'note',
          targetId: note.id,
          targetName: note.title,
          completed: false
      };
      onAddReminder(reminder);
      setContextMenu(null);
      alert("Reminder set for tomorrow!");
  };

  const handleCreateNote = () => {
      onCreateNoteFromText(selectedText, note.subject);
      setContextMenu(null);
  };

  const handleCustomQuery = async () => {
      if(!aiQuery.trim()) return;
      setIsAiThinking(true);
      setAiResponse('');
      try {
          const res = await solveWithThinking(note.rawContent || "", aiQuery);
          setAiResponse(res);
      } catch (err) {
          setAiResponse("Error getting answer.");
      } finally {
          setIsAiThinking(false);
      }
  };

  return (
    <div className="flex h-full bg-stone-100 overflow-hidden relative">
        {/* Header */}
        <div className="absolute top-0 left-0 right-0 h-14 bg-white border-b border-stone-200 flex items-center justify-between px-4 z-20">
            <div className="flex items-center gap-3">
                <button onClick={onClose} className="p-2 hover:bg-stone-100 rounded-full text-stone-500">
                    <ArrowLeft className="w-5 h-5" />
                </button>
                <h2 className="font-bold text-stone-700 truncate max-w-md">{note.title}</h2>
            </div>
            <div className="flex bg-stone-100 p-1 rounded-lg">
                <button 
                    onClick={() => setActiveTab('pdf')}
                    className={`px-3 py-1.5 rounded-md text-sm font-bold flex items-center gap-2 transition-all ${activeTab === 'pdf' ? 'bg-white shadow-sm text-stone-800' : 'text-stone-500 hover:text-stone-700'}`}
                >
                    <BookOpen className="w-4 h-4" /> PDF View
                </button>
                <button 
                    onClick={() => setActiveTab('text')}
                    className={`px-3 py-1.5 rounded-md text-sm font-bold flex items-center gap-2 transition-all ${activeTab === 'text' ? 'bg-white shadow-sm text-stone-800' : 'text-stone-500 hover:text-stone-700'}`}
                >
                    <FileText className="w-4 h-4" /> Smart Reader
                </button>
            </div>
            <button 
                onClick={() => setAiPanelOpen(!aiPanelOpen)}
                className={`p-2 rounded-lg transition-colors ${aiPanelOpen ? 'bg-orange-100 text-orange-600' : 'hover:bg-stone-100 text-stone-500'}`}
            >
                <BrainCircuit className="w-5 h-5" />
            </button>
        </div>

        {/* Main Content (Split) */}
        <div className={`flex-1 flex pt-14 h-full transition-all ${aiPanelOpen ? 'mr-[400px]' : ''}`}>
            
            {/* Left Panel: Viewer */}
            <div className="flex-1 bg-stone-200 h-full overflow-hidden relative">
                {activeTab === 'pdf' ? (
                    note.pdfUrl ? (
                        <iframe src={note.pdfUrl} className="w-full h-full border-none" title="PDF Viewer"></iframe>
                    ) : (
                        <div className="flex items-center justify-center h-full text-stone-500">No PDF file attached.</div>
                    )
                ) : (
                    <div 
                        className="h-full overflow-y-auto p-8 bg-white max-w-3xl mx-auto shadow-sm"
                        onMouseUp={handleTextSelection}
                        onContextMenu={handleContextMenu}
                    >
                        <h1 className="text-3xl font-bold mb-6 text-stone-900">{note.title}</h1>
                        <div className="prose prose-lg prose-stone max-w-none font-serif leading-relaxed whitespace-pre-wrap">
                            {note.rawContent || "No text content extracted. Try re-uploading the PDF."}
                        </div>
                    </div>
                )}
            </div>
        </div>

        {/* Right Panel: AI (Fixed Width) */}
        <div className={`absolute top-14 bottom-0 right-0 w-[400px] bg-white border-l border-stone-200 transform transition-transform duration-300 z-10 flex flex-col ${aiPanelOpen ? 'translate-x-0' : 'translate-x-full'}`}>
            <div className="p-4 border-b border-stone-100 bg-orange-50/50 flex items-center gap-2">
                <Sparkles className="w-5 h-5 text-orange-500" />
                <h3 className="font-bold text-stone-700">AI Assistant</h3>
            </div>
            
            <div className="flex-1 overflow-y-auto p-4 space-y-4 bg-stone-50">
                {!aiResponse && !isAiThinking && (
                    <div className="text-center text-stone-400 mt-10 p-4">
                        <BrainCircuit className="w-12 h-12 mx-auto mb-2 opacity-50" />
                        <p>Select text in "Smart Reader" mode to ask for explanations, or type a question below.</p>
                    </div>
                )}
                
                {aiQuery && (
                    <div className="bg-white p-3 rounded-lg border border-stone-200 shadow-sm self-end ml-4">
                        <p className="text-stone-700 text-sm">{aiQuery}</p>
                    </div>
                )}

                {isAiThinking && (
                    <div className="flex items-center gap-2 text-orange-500 text-sm font-bold animate-pulse">
                        <Sparkles className="w-4 h-4" /> Thinking...
                    </div>
                )}

                {aiResponse && (
                    <div className="bg-white p-4 rounded-xl border-l-4 border-orange-400 shadow-sm">
                        <p className="text-stone-800 text-sm leading-relaxed whitespace-pre-wrap">{aiResponse}</p>
                    </div>
                )}
            </div>

            <div className="p-4 bg-white border-t border-stone-200">
                <div className="relative">
                    <input 
                        type="text" 
                        value={aiQuery}
                        onChange={(e) => setAiQuery(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && handleCustomQuery()}
                        placeholder="Ask about this document..."
                        className="w-full pl-4 pr-10 py-3 bg-stone-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-200"
                    />
                    <button onClick={handleCustomQuery} className="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                        <ArrowLeft className="w-4 h-4 rotate-180" />
                    </button>
                </div>
            </div>
        </div>

        {/* Context Menu */}
        {contextMenu?.visible && (
            <div 
                className="fixed bg-white rounded-lg shadow-xl border border-stone-200 py-1 z-50 animate-fade-in w-48"
                style={{ top: contextMenu.y, left: contextMenu.x }}
            >
                <button onClick={handleAiExplain} className="w-full text-left px-4 py-2 text-sm text-stone-700 hover:bg-orange-50 hover:text-orange-600 flex items-center gap-2">
                    <Sparkles className="w-4 h-4" /> Explain This
                </button>
                <button onClick={handleCreateReminder} className="w-full text-left px-4 py-2 text-sm text-stone-700 hover:bg-orange-50 hover:text-orange-600 flex items-center gap-2">
                    <Bell className="w-4 h-4" /> Create Reminder
                </button>
                <button onClick={handleCreateNote} className="w-full text-left px-4 py-2 text-sm text-stone-700 hover:bg-orange-50 hover:text-orange-600 flex items-center gap-2">
                    <StickyNote className="w-4 h-4" /> Save as Note
                </button>
            </div>
        )}
    </div>
  );
};

export default PdfReader;