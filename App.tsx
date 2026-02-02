import React, { useState, useEffect } from 'react';
import Sidebar from './components/Sidebar';
import Dashboard from './components/Dashboard';
import AudioRecorder from './components/AudioRecorder';
import NoteView from './components/NoteView';
import StudyMode from './components/StudyMode';
import Notepad from './components/Notepad';
import { Note, SubjectRegister, AppView } from './types';
import { performSemanticSearch } from './services/geminiService';

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
  
  const [activeSubject, setActiveSubject] = useState<string | null>(null);
  const [selectedNote, setSelectedNote] = useState<Note | null>(null);
  
  // Search State
  const [searchQuery, setSearchQuery] = useState('');
  const [isAiSearching, setIsAiSearching] = useState(false);
  const [aiFilteredIds, setAiFilteredIds] = useState<string[] | null>(null);
  
  const [notepadInitialNote, setNotepadInitialNote] = useState<Note | null>(null);
  const [notepadSubject, setNotepadSubject] = useState<string | undefined>(undefined);

  useEffect(() => {
    const savedNotes = localStorage.getItem('scholarai_notes');
    const savedRegisters = localStorage.getItem('scholarai_custom_registers');
    
    if (savedRegisters) {
        setCustomRegisterNames(JSON.parse(savedRegisters));
    }

    if (savedNotes) {
      const parsedNotes = JSON.parse(savedNotes);
      setNotes(parsedNotes);
    }
  }, []);

  useEffect(() => {
    localStorage.setItem('scholarai_notes', JSON.stringify(notes));
  }, [notes]);

  useEffect(() => {
    localStorage.setItem('scholarai_custom_registers', JSON.stringify(customRegisterNames));
  }, [customRegisterNames]);

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
    
    // Add new notes to the top of the list
    setNotes(prev => [...notesToAdd, ...prev]);

    if (notesToAdd.length > 1) {
        // If multiple notes were created (e.g. PDF split), go to dashboard to see them all
        if (notesToAdd[0].subject) {
            setActiveSubject(notesToAdd[0].subject);
        }
        setView(AppView.DASHBOARD);
    } else {
        // If single note, open it immediately
        setSelectedNote(notesToAdd[0]);
        setView(AppView.NOTE_VIEW);
    }
  };

  const handleViewChange = (newView: AppView) => {
    setView(newView);
    if (newView !== AppView.NOTE_VIEW && newView !== AppView.NOTEPAD) {
      setSelectedNote(null);
    }
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
  };

  const handleSearchChange = (query: string) => {
      setSearchQuery(query);
      
      // When user types, revert to local filtering immediately to avoid stale AI results
      setAiFilteredIds(null);

      if(query) {
          setView(AppView.DASHBOARD);
          setActiveSubject(null); 
      }
  };

  const handleSmartSearch = async () => {
      if (!searchQuery.trim()) return;

      setIsAiSearching(true);
      setAiFilteredIds(null); // Clear previous
      setView(AppView.DASHBOARD); // Ensure we are on dashboard to see results
      setActiveSubject(null);

      // Prepare minimized metadata for the AI to analyze (avoid hitting token limits with full content)
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

  const getDisplayNotes = () => {
      let filtered = notes;

      // Priority 1: AI Search Results
      if (aiFilteredIds !== null) {
          return filtered.filter(n => aiFilteredIds.includes(n.id));
      }

      // Priority 2: Standard Text Filter
      if (searchQuery) {
          const lowerQ = searchQuery.toLowerCase();
          filtered = filtered.filter(n => 
            n.title.toLowerCase().includes(lowerQ) || 
            n.summary.toLowerCase().includes(lowerQ) ||
            n.subject.toLowerCase().includes(lowerQ) ||
            n.tags.some(t => t.toLowerCase().includes(lowerQ))
          );
      } else if (activeSubject) {
          // Priority 3: Subject Filter
          filtered = filtered.filter(n => n.subject === activeSubject);
      }

      return filtered;
  };

  const displayNotes = getDisplayNotes();

  return (
    <div className="flex h-screen bg-stone-100 font-sans text-ink">
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
      />
      
      {/* Main "Desk" Area */}
      <main className="flex-1 overflow-hidden relative p-4 md:p-6 shadow-inner bg-stone-100">
        <div className="h-full rounded-3xl bg-desk border border-stone-200/50 shadow-sm overflow-hidden relative">
            {view === AppView.DASHBOARD && (
            <Dashboard 
                notes={displayNotes} 
                onOpenNote={handleOpenNote}
                onNewNote={() => setView(AppView.RECORDER)}
                registers={registers}
                activeSubject={activeSubject}
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
