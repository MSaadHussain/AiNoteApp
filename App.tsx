import React, { useState, useEffect } from 'react';
import Sidebar from './components/Sidebar';
import Dashboard from './components/Dashboard';
import AudioRecorder from './components/AudioRecorder';
import NoteView from './components/NoteView';
import StudyMode from './components/StudyMode';
import Notepad from './components/Notepad';
import PdfReader from './components/PdfReader';
import { Note, SubjectRegister, AppView, Reminder } from './types';
import { performSemanticSearch } from './services/geminiService';
import { Menu, Bell, Backpack } from 'lucide-react';

// Pastel colors for Notebook covers
const COLORS = [
  'bg-rose-200 text-rose-800 border-rose-300', 
  'bg-sky-200 text-sky-800 border-sky-300', 
  'bg-emerald-200 text-emerald-800 border-emerald-300', 
  'bg-amber-200 text-amber-800 border-amber-300', 
  'bg-violet-200 text-violet-800 border-violet-300', 
  'bg-orange-200 text-orange-800 border-orange-300'
];

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

  const handleNoteCreated = (newNotes: Note | Note[]) => {
    const notesToAdd = Array.isArray(newNotes) ? newNotes : [newNotes];
    setNotes(prev => [...notesToAdd, ...prev]);

    if (notesToAdd.length > 1) {
        if (notesToAdd[0].subject) {
            setActiveSubject(notesToAdd[0].subject);
        }
        setView(AppView.DASHBOARD);
    } else {
        const note = notesToAdd[0];
        if (note.type === 'pdf') {
            setSelectedNote(note);
            setView(AppView.PDF_VIEW);
        } else {
            setSelectedNote(note);
            setView(AppView.NOTE_VIEW);
        }
    }
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
      
      {/* Mobile Header */}
      <div className="md:hidden h-16 bg-white border-b border-stone-200 flex items-center justify-between px-4 z-40 flex-shrink-0">
          <button onClick={() => setMobileMenuOpen(true)} className="p-2 text-stone-600">
              <Menu className="w-6 h-6" />
          </button>
          
          <div className="flex items-center gap-2">
            <div className="bg-orange-100 p-1.5 rounded-lg border border-orange-200">
              <Backpack className="text-orange-600 w-4 h-4" />
            </div>
            <h1 className="font-hand font-bold text-xl text-stone-800">ScholarAI</h1>
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
            />
            )}

            {view === AppView.RECORDER && (
            <AudioRecorder 
                onNoteCreated={handleNoteCreated} 
                onOpenNotepad={handleOpenNotepad}
                registers={registers}
                preSelectedSubject={activeSubject}
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