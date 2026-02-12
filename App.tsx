import React, { useState, useEffect } from 'react';
import Sidebar from './components/Sidebar';
import Dashboard from './components/Dashboard';
import AudioRecorder from './components/AudioRecorder';
import NoteView from './components/NoteView';
import StudyMode from './components/StudyMode';
import Notepad from './components/Notepad';
import PdfReader from './components/PdfReader';
import { Note, SubjectRegister, AppView, Reminder } from './types';
import { performSemanticSearch, transcribeAudio, organizeNote, processPdf, splitPdf, convertImageToNote } from './services/geminiService';
import { Menu, Bell, Backpack, Loader2, CheckCircle, XCircle, ChevronDown, ChevronUp } from 'lucide-react';

// Pastel colors for Notebook covers
const COLORS = [
  'bg-rose-200 text-rose-800 border-rose-300', 
  'bg-sky-200 text-sky-800 border-sky-300', 
  'bg-emerald-200 text-emerald-800 border-emerald-300', 
  'bg-amber-200 text-amber-800 border-amber-300', 
  'bg-violet-200 text-violet-800 border-violet-300', 
  'bg-orange-200 text-orange-800 border-orange-300'
];

interface BackgroundTask {
    status: 'processing' | 'organizing' | 'success' | 'error';
    message: string;
    details?: string;
}

const App: React.FC = () => {
  const [view, setView] = useState<AppView>(AppView.DASHBOARD);
  const [notes, setNotes] = useState<Note[]>([]);
  const [registers, setRegisters] = useState<SubjectRegister[]>([]);
  const [customRegisterNames, setCustomRegisterNames] = useState<string[]>([]);
  const [reminders, setReminders] = useState<Reminder[]>([]);
  
  const [activeSubject, setActiveSubject] = useState<string | null>(null);
  const [selectedNote, setSelectedNote] = useState<Note | null>(null);
  
  // Search State
  const [searchQuery, setSearchQuery] = useState('');
  const [isAiSearching, setIsAiSearching] = useState(false);
  const [aiFilteredIds, setAiFilteredIds] = useState<string[] | null>(null);
  
  const [notepadInitialNote, setNotepadInitialNote] = useState<Note | null>(null);
  const [notepadSubject, setNotepadSubject] = useState<string | undefined>(undefined);

  // Mobile State
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [mobileRemindersOpen, setMobileRemindersOpen] = useState(false);

  // Background Processing State
  const [bgTask, setBgTask] = useState<BackgroundTask | null>(null);
  const [isBgTaskExpanded, setIsBgTaskExpanded] = useState(false);

  useEffect(() => {
    const savedNotes = localStorage.getItem('scholarai_notes');
    const savedRegisters = localStorage.getItem('scholarai_custom_registers');
    const savedReminders = localStorage.getItem('scholarai_reminders');
    
    if (savedRegisters) {
        setCustomRegisterNames(JSON.parse(savedRegisters));
    }

    if (savedNotes) {
      const parsedNotes = JSON.parse(savedNotes);
      setNotes(parsedNotes);
    }

    if (savedReminders) {
        setReminders(JSON.parse(savedReminders));
    }
  }, []);

  useEffect(() => {
    localStorage.setItem('scholarai_notes', JSON.stringify(notes));
  }, [notes]);

  useEffect(() => {
    localStorage.setItem('scholarai_custom_registers', JSON.stringify(customRegisterNames));
  }, [customRegisterNames]);

  useEffect(() => {
    localStorage.setItem('scholarai_reminders', JSON.stringify(reminders));
  }, [reminders]);

  useEffect(() => {
      const subjectsFromNotes = new Set(notes.map(n => n.subject));
      const allSubjects = new Set([...customRegisterNames, ...Array.from(subjectsFromNotes)]);
      
      const newRegisters: SubjectRegister[] = Array.from(allSubjects).map((sub, idx) => ({
        name: sub,
        color: COLORS[idx % COLORS.length],
        noteIds: notes.filter(n => n.subject === sub).map(n => n.id)
      }));
      setRegisters(newRegisters);
  }, [notes, customRegisterNames]);

  // --- Background Processing Handlers ---

  const handleProcessAudio = async (blob: Blob, subject?: string) => {
      setView(AppView.DASHBOARD); // Go back to desk immediately
      setBgTask({ status: 'processing', message: 'Transcribing Audio...', details: 'Listening to your recording...' });
      
      try {
          const transcript = await transcribeAudio(blob, blob.type);
          
          setBgTask({ status: 'organizing', message: 'Organizing Notes...', details: 'Structuring content with AI...' });
          const organizedData = await organizeNote(transcript);
          
          const finalSubject = (subject || organizedData.subject) || 'General';
          const newNote: Note = {
            id: crypto.randomUUID(),
            title: organizedData.title,
            subject: finalSubject,
            date: new Date().toLocaleDateString(),
            type: 'audio',
            originalTranscript: transcript,
            summary: organizedData.summary,
            sections: organizedData.sections,
            tags: organizedData.tags,
            audioUrl: URL.createObjectURL(blob)
          };

          handleNoteCreated(newNote);
          setBgTask({ status: 'success', message: 'Audio Note Created!', details: `Saved "${newNote.title}"` });
          setTimeout(() => setBgTask(null), 4000);
      } catch (error) {
          console.error(error);
          setBgTask({ status: 'error', message: 'Processing Failed', details: 'Could not process audio.' });
          setTimeout(() => setBgTask(null), 5000);
      }
  };

  const handleProcessPdf = async (blob: Blob, subject?: string) => {
      setView(AppView.DASHBOARD);
      setBgTask({ status: 'processing', message: 'Analyzing PDF...', details: 'Scanning document pages...' });

      try {
          // 1. Split PDF
          const pdfChunks = await splitPdf(blob, 5);
          const createdNotes: Note[] = [];

          // 2. Process Chunks
          for (let i = 0; i < pdfChunks.length; i++) {
             setBgTask({ 
                 status: 'organizing', 
                 message: 'Reading PDF...', 
                 details: `Processing part ${i + 1} of ${pdfChunks.length}...` 
             });

             const chunk = pdfChunks[i];
             const pdfData = await processPdf(chunk);

             let noteTitle = pdfData.title;
             if (pdfChunks.length > 1) {
                 noteTitle = `${noteTitle} (Part ${i + 1})`;
             }

             const finalSubject = subject || 'General';
             
             const newNote: Note = {
                id: crypto.randomUUID(),
                title: noteTitle,
                subject: finalSubject,
                date: new Date().toLocaleDateString(),
                type: 'pdf',
                rawContent: pdfData.rawText,
                summary: pdfData.summary,
                sections: pdfData.sections,
                tags: pdfData.tags,
                pdfUrl: URL.createObjectURL(chunk)
            };
            createdNotes.push(newNote);
          }
          
          handleNoteCreated(createdNotes.reverse());
          setBgTask({ status: 'success', message: 'PDF Processed!', details: `Created ${createdNotes.length} notes.` });
          setTimeout(() => setBgTask(null), 4000);

      } catch (error) {
          console.error(error);
          setBgTask({ status: 'error', message: 'Processing Failed', details: 'Could not read PDF.' });
          setTimeout(() => setBgTask(null), 5000);
      }
  };

  const handleProcessImage = async (blob: Blob, subject?: string) => {
      // Don't change view if already on dashboard, just show indicator
      setBgTask({ status: 'processing', message: 'Reading Image...', details: 'Extracting text from image...' });

      try {
          const data = await convertImageToNote(blob);
          const newNote: Note = {
              id: crypto.randomUUID(),
              title: data.title,
              subject: subject || 'General',
              date: new Date().toLocaleDateString(),
              type: 'text',
              rawContent: data.content,
              summary: data.summary,
              sections: [],
              tags: data.tags
          };
          
          handleNoteCreated(newNote);
          setBgTask({ status: 'success', message: 'Image Note Created!', details: 'Image converted to text successfully.' });
          setTimeout(() => setBgTask(null), 4000);
      } catch (error) {
          console.error(error);
          setBgTask({ status: 'error', message: 'Failed', details: 'Could not read image.' });
          setTimeout(() => setBgTask(null), 5000);
      }
  };


  const handleNoteCreated = (newNotes: Note | Note[]) => {
    const notesToAdd = Array.isArray(newNotes) ? newNotes : [newNotes];
    setNotes(prev => [...notesToAdd, ...prev]);
  };

  const handleViewChange = (newView: AppView) => {
    setView(newView);
    if (newView !== AppView.NOTE_VIEW && newView !== AppView.NOTEPAD && newView !== AppView.PDF_VIEW) {
      setSelectedNote(null);
    }
    // Close mobile menu on view change
    setMobileMenuOpen(false);
  };

  const handleSubjectSelect = (subject: string) => {
    if (subject === '') {
        setActiveSubject(null);
        setView(AppView.DASHBOARD);
    } else {
        setActiveSubject(subject);
        setView(AppView.DASHBOARD);
        setSearchQuery(''); 
        setAiFilteredIds(null);
    }
    setMobileMenuOpen(false);
  };

  const handleCreateRegister = (name: string) => {
      if (!customRegisterNames.includes(name)) {
          setCustomRegisterNames([...customRegisterNames, name]);
      }
  };

  const handleOpenNotepad = (subject?: string) => {
      setNotepadSubject(subject || activeSubject || undefined);
      setNotepadInitialNote(null);
      setView(AppView.NOTEPAD);
      setMobileMenuOpen(false);
  };

  const handleSearchChange = (query: string) => {
      setSearchQuery(query);
      setAiFilteredIds(null);
      if(query) {
          setView(AppView.DASHBOARD);
          setActiveSubject(null); 
      }
  };

  const handleSmartSearch = async () => {
      if (!searchQuery.trim()) return;
      setIsAiSearching(true);
      setAiFilteredIds(null);
      setView(AppView.DASHBOARD);
      setActiveSubject(null);
      setMobileMenuOpen(false);

      const metadata = notes.map(n => ({
          id: n.id,
          title: n.title,
          summary: n.summary,
          tags: n.tags
      }));

      const relevantIds = await performSemanticSearch(searchQuery, metadata);
      setAiFilteredIds(relevantIds);
      setIsAiSearching(false);
  };

  const handleOpenNote = (note: Note) => {
      setSelectedNote(note);
      if (note.type === 'text') {
          setNotepadInitialNote(note);
          setNotepadSubject(note.subject);
          setView(AppView.NOTEPAD);
      } else if (note.type === 'pdf') {
          setView(AppView.PDF_VIEW);
      } else {
          setView(AppView.NOTE_VIEW);
      }
  };
  
  const handleSaveNotepad = (updatedNote: Note) => {
      const exists = notes.find(n => n.id === updatedNote.id);
      let updatedNotes;
      if (exists) {
          updatedNotes = notes.map(n => n.id === updatedNote.id ? updatedNote : n);
      } else {
          updatedNotes = [updatedNote, ...notes];
      }
      setNotes(updatedNotes);
      setSelectedNote(updatedNote);
      setView(AppView.DASHBOARD);
  };

  // Reminder Handlers
  const handleAddReminder = (reminder: Reminder) => {
      setReminders(prev => [...prev, reminder]);
  };

  const handleToggleReminder = (id: string) => {
      setReminders(prev => prev.map(r => r.id === id ? { ...r, completed: !r.completed } : r));
  };

  const handleDeleteReminder = (id: string) => {
      setReminders(prev => prev.filter(r => r.id !== id));
  };

  const handleReminderClick = (reminder: Reminder) => {
      setMobileRemindersOpen(false); // Close panel on mobile if open
      if (reminder.type === 'subject' && reminder.targetId) {
          handleSubjectSelect(reminder.targetId);
      } else if (reminder.type === 'note' && reminder.targetId) {
          const note = notes.find(n => n.id === reminder.targetId);
          if (note) {
              handleOpenNote(note);
          } else {
              setView(AppView.DASHBOARD);
          }
      } else {
          setView(AppView.DASHBOARD);
      }
  };

  const handleCreateNoteFromText = (text: string, subject: string) => {
      const newNote: Note = {
          id: crypto.randomUUID(),
          title: "Excerpt from PDF",
          subject: subject,
          date: new Date().toLocaleDateString(),
          type: 'text',
          rawContent: text,
          summary: text.substring(0, 100) + "...",
          sections: [],
          tags: ['excerpt']
      };
      setNotepadInitialNote(newNote);
      setNotepadSubject(subject);
      setView(AppView.NOTEPAD);
  };

  const getDisplayNotes = () => {
      let filtered = notes;
      if (aiFilteredIds !== null) {
          return filtered.filter(n => aiFilteredIds.includes(n.id));
      }
      if (searchQuery) {
          const lowerQ = searchQuery.toLowerCase();
          filtered = filtered.filter(n => 
            n.title.toLowerCase().includes(lowerQ) || 
            n.summary.toLowerCase().includes(lowerQ) ||
            n.subject.toLowerCase().includes(lowerQ) ||
            n.tags.some(t => t.toLowerCase().includes(lowerQ))
          );
      } else if (activeSubject) {
          filtered = filtered.filter(n => n.subject === activeSubject);
      }
      return filtered;
  };

  const displayNotes = getDisplayNotes();

  return (
    <div className="flex flex-col md:flex-row h-screen bg-stone-100 font-sans text-ink overflow-hidden">
      
      {/* Persistent Background Task Indicator (Top Right) */}
      {bgTask && (
        <div 
            className={`fixed top-4 right-4 z-[100] transition-all duration-300 ${isBgTaskExpanded ? 'w-80' : 'w-auto'}`}
        >
            <div 
                className="bg-white border border-stone-200 shadow-xl rounded-xl overflow-hidden cursor-pointer hover:shadow-2xl transition-shadow"
                onClick={() => setIsBgTaskExpanded(!isBgTaskExpanded)}
            >
                {/* Collapsed State */}
                {!isBgTaskExpanded && (
                    <div className="p-3 flex items-center gap-3 pr-4 animate-fade-in">
                        {bgTask.status === 'processing' || bgTask.status === 'organizing' ? (
                            <div className="relative">
                                <Loader2 className="w-5 h-5 text-orange-500 animate-spin" />
                            </div>
                        ) : bgTask.status === 'success' ? (
                            <CheckCircle className="w-5 h-5 text-green-500" />
                        ) : (
                            <XCircle className="w-5 h-5 text-red-500" />
                        )}
                        <span className="font-bold text-sm text-stone-700 whitespace-nowrap">
                            {bgTask.message}
                        </span>
                    </div>
                )}

                {/* Expanded State */}
                {isBgTaskExpanded && (
                    <div className="p-4 bg-white animate-fade-in">
                        <div className="flex justify-between items-start mb-2">
                             <div className="flex items-center gap-2">
                                {bgTask.status === 'processing' || bgTask.status === 'organizing' ? (
                                    <Loader2 className="w-4 h-4 text-orange-500 animate-spin" />
                                ) : <CheckCircle className="w-4 h-4 text-green-500" />}
                                <h4 className="font-bold text-stone-800 text-sm">{bgTask.message}</h4>
                             </div>
                             <button className="text-stone-400 hover:text-stone-600">
                                 <ChevronUp className="w-4 h-4" />
                             </button>
                        </div>
                        <p className="text-xs text-stone-500 leading-relaxed">
                            {bgTask.details}
                        </p>
                        {bgTask.status === 'processing' || bgTask.status === 'organizing' ? (
                            <div className="w-full bg-stone-100 h-1.5 rounded-full mt-3 overflow-hidden">
                                <div className="h-full bg-orange-400 animate-pulse w-2/3 rounded-full"></div>
                            </div>
                        ) : null}
                    </div>
                )}
            </div>
        </div>
      )}

      {/* Mobile Header */}
      <div className="md:hidden h-16 bg-white border-b border-stone-200 flex items-center justify-between px-4 z-40 flex-shrink-0">
          <button onClick={() => setMobileMenuOpen(true)} className="p-2 text-stone-600">
              <Menu className="w-6 h-6" />
          </button>
          
          <div className="flex items-center gap-2">
            <div className="bg-orange-100 p-1.5 rounded-lg border border-orange-200">
              <Backpack className="text-orange-600 w-4 h-4" />
            </div>
            <h1 className="font-hand font-bold text-xl text-stone-800">NotebookAI</h1>
          </div>

          <button onClick={() => setMobileRemindersOpen(true)} className="p-2 text-stone-600 relative">
              <Bell className="w-6 h-6" />
              {reminders.filter(r => !r.completed).length > 0 && (
                  <span className="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full border border-white"></span>
              )}
          </button>
      </div>

      <Sidebar 
        registers={registers} 
        currentView={view} 
        onChangeView={handleViewChange}
        onSelectSubject={handleSubjectSelect}
        selectedSubject={activeSubject}
        onSearch={handleSearchChange}
        onSmartSearch={handleSmartSearch}
        isSearching={isAiSearching}
        searchQuery={searchQuery}
        onCreateRegister={handleCreateRegister}
        isMobileOpen={mobileMenuOpen}
        onMobileClose={() => setMobileMenuOpen(false)}
      />
      
      {/* Main "Desk" Area */}
      <main className="flex-1 overflow-hidden relative p-0 md:p-6 shadow-inner bg-stone-100">
        <div className="h-full md:rounded-3xl bg-desk border-t md:border border-stone-200/50 shadow-sm overflow-hidden relative">
            {view === AppView.DASHBOARD && (
            <Dashboard 
                notes={displayNotes} 
                onOpenNote={handleOpenNote}
                onNewNote={() => setView(AppView.RECORDER)}
                onNewNoteInRegister={handleOpenNotepad}
                onNoteCreated={handleNoteCreated}
                registers={registers}
                activeSubject={activeSubject}
                reminders={reminders}
                onAddReminder={handleAddReminder}
                onToggleReminder={handleToggleReminder}
                onDeleteReminder={handleDeleteReminder}
                onReminderClick={handleReminderClick}
                showMobileReminders={mobileRemindersOpen}
                onCloseMobileReminders={() => setMobileRemindersOpen(false)}
                onProcessImage={handleProcessImage}
                onProcessPdf={handleProcessPdf}
            />
            )}

            {view === AppView.RECORDER && (
            <AudioRecorder 
                onNoteCreated={handleNoteCreated} 
                onOpenNotepad={handleOpenNotepad}
                registers={registers}
                preSelectedSubject={activeSubject}
                onProcessAudio={handleProcessAudio}
                onProcessPdf={handleProcessPdf}
            />
            )}

            {view === AppView.NOTEPAD && (
                <Notepad 
                    initialNote={notepadInitialNote}
                    subject={notepadSubject}
                    onSave={handleSaveNotepad}
                    onCancel={() => setView(AppView.DASHBOARD)}
                />
            )}

            {view === AppView.NOTE_VIEW && selectedNote && (
            <NoteView 
                note={selectedNote} 
                onClose={() => setView(AppView.DASHBOARD)}
            />
            )}

            {view === AppView.PDF_VIEW && selectedNote && (
                <PdfReader 
                    note={selectedNote}
                    onClose={() => setView(AppView.DASHBOARD)}
                    onAddReminder={handleAddReminder}
                    onCreateNoteFromText={handleCreateNoteFromText}
                />
            )}

            {view === AppView.STUDY_MODE && (
            <StudyMode 
                notes={notes}
                onExit={() => setView(AppView.DASHBOARD)}
            />
            )}
        </div>
      </main>
    </div>
  );
};

export default App;