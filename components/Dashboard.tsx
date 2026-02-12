import React, { useState, useEffect, useRef } from 'react';
import { Note, SubjectRegister, Reminder } from '../types';
import { Clock, Book, ArrowRight, PenTool, Download, FileText, File, Plus, CheckCircle, Trash2, Calendar, Bell, Image, Upload, MoreVertical, X } from 'lucide-react';
import { exportRegisterAsMarkdown, exportRegisterAsPDF } from '../services/exportService';

interface DashboardProps {
  notes: Note[];
  onOpenNote: (note: Note) => void;
  onNewNote: () => void;
  onNewNoteInRegister: (subject: string) => void;
  onNoteCreated: (note: Note) => void;
  registers: SubjectRegister[];
  activeSubject: string | null;
  reminders: Reminder[];
  onAddReminder: (reminder: Reminder) => void;
  onToggleReminder: (id: string) => void;
  onDeleteReminder: (id: string) => void;
  onReminderClick: (reminder: Reminder) => void;
  showMobileReminders: boolean;
  onCloseMobileReminders: () => void;
  onProcessImage: (blob: Blob, subject?: string) => void;
  onProcessPdf: (blob: Blob, subject?: string) => void;
}

interface ContextMenuState {
    visible: boolean;
    x: number;
    y: number;
    type: 'general' | 'subject' | 'note';
    targetId?: string;
    targetName?: string;
}

const Dashboard: React.FC<DashboardProps> = ({ 
    notes, 
    onOpenNote, 
    onNewNote,
    onNewNoteInRegister,
    onNoteCreated,
    registers, 
    activeSubject,
    reminders,
    onAddReminder,
    onToggleReminder,
    onDeleteReminder,
    onReminderClick,
    showMobileReminders,
    onCloseMobileReminders,
    onProcessImage,
    onProcessPdf
}) => {
  const [exportMenuOpen, setExportMenuOpen] = useState<string | null>(null);
  
  // Context Menu State
  const [contextMenu, setContextMenu] = useState<ContextMenuState>({ visible: false, x: 0, y: 0, type: 'general' });
  const dashboardRef = useRef<HTMLDivElement>(null);
  const imageInputRef = useRef<HTMLInputElement>(null);
  const pdfInputRef = useRef<HTMLInputElement>(null);

  // Reminder Modal State
  const [isReminderModalOpen, setIsReminderModalOpen] = useState(false);
  const [reminderText, setReminderText] = useState('');
  const [reminderDate, setReminderDate] = useState('');

  const recentNotes = [...notes].sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());

  // Filter registers if a subject is active
  const displayRegisters = activeSubject 
    ? registers.filter(r => r.name === activeSubject) 
    : registers;

  const handleExport = (e: React.MouseEvent, type: 'pdf' | 'md', register: SubjectRegister) => {
      e.stopPropagation();
      const subjectNotes = notes.filter(n => n.subject === register.name);
      if (type === 'pdf') {
          exportRegisterAsPDF(register.name, subjectNotes);
      } else {
          exportRegisterAsMarkdown(register.name, subjectNotes);
      }
      setExportMenuOpen(null);
  };

  // --- File Upload Handlers ---
  const handleImageUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
      if (e.target.files && e.target.files[0] && contextMenu.targetName) {
          onProcessImage(e.target.files[0], contextMenu.targetName);
          e.target.value = ''; // Reset input
      }
  };

  const handlePdfUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
      if (e.target.files && e.target.files[0] && contextMenu.targetName) {
          onProcessPdf(e.target.files[0], contextMenu.targetName);
          e.target.value = ''; // Reset input
      }
  };


  // --- Context Menu Handlers ---
  
  useEffect(() => {
    const handleClick = () => setContextMenu({ ...contextMenu, visible: false });
    document.addEventListener('click', handleClick);
    return () => document.removeEventListener('click', handleClick);
  }, [contextMenu]);

  const handleContextMenu = (e: React.MouseEvent, type: 'general' | 'subject' | 'note', targetId?: string, targetName?: string) => {
      e.preventDefault();
      e.stopPropagation();
      
      // Basic responsive positioning logic
      let x = e.clientX;
      let y = e.clientY;

      // Adjust if off-screen (simple check)
      if (window.innerWidth - x < 250) x = window.innerWidth - 260;
      
      setContextMenu({
          visible: true,
          x,
          y,
          type,
          targetId,
          targetName
      });
  };

  const openReminderModal = () => {
      setReminderText(contextMenu.type === 'general' ? '' : `Review ${contextMenu.targetName}`);
      const tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);
      tomorrow.setHours(9, 0, 0, 0);
      setReminderDate(tomorrow.toISOString().slice(0, 16));
      
      setIsReminderModalOpen(true);
      setContextMenu({ ...contextMenu, visible: false });
  };

  const handleSaveReminder = () => {
      if (!reminderText) return;
      const newReminder: Reminder = {
          id: crypto.randomUUID(),
          text: reminderText,
          dueDate: new Date(reminderDate).toLocaleString(), 
          type: contextMenu.type,
          targetId: contextMenu.targetId,
          targetName: contextMenu.targetName,
          completed: false
      };
      onAddReminder(newReminder);
      setIsReminderModalOpen(false);
  };

  // Reminder Panel Content (Shared between desktop sticky and mobile drawer)
  const ReminderList = () => (
      <div className="flex-1 overflow-y-auto pr-2 space-y-3">
          {reminders.length === 0 ? (
              <p className="font-hand text-stone-500 text-lg text-center mt-10">Nothing to do yet!</p>
          ) : (
              reminders.sort((a,b) => a.completed === b.completed ? 0 : a.completed ? 1 : -1).map(reminder => (
                  <div 
                      key={reminder.id} 
                      className={`group flex items-start gap-2 p-2 rounded hover:bg-yellow-300/50 transition-colors cursor-pointer ${reminder.completed ? 'opacity-50' : ''}`}
                      onClick={() => onReminderClick(reminder)}
                  >
                      <button 
                          onClick={(e) => { e.stopPropagation(); onToggleReminder(reminder.id); }}
                          className={`mt-1 w-4 h-4 rounded border border-stone-500 flex items-center justify-center ${reminder.completed ? 'bg-stone-600 border-stone-600' : 'bg-white'}`}
                      >
                          {reminder.completed && <CheckCircle className="w-3 h-3 text-white" />}
                      </button>
                      
                      <div className="flex-1">
                          <p className={`font-hand text-lg leading-tight text-stone-800 ${reminder.completed ? 'line-through' : ''}`}>
                              {reminder.text}
                          </p>
                          <div className="flex items-center gap-2 mt-1">
                              <span className="text-[10px] uppercase font-bold text-stone-500 bg-white/50 px-1 rounded">
                                  {reminder.type === 'general' ? 'General' : reminder.targetName}
                              </span>
                              <span className="text-xs font-hand text-stone-500">
                                  {new Date(reminder.dueDate).toLocaleDateString([], {month:'short', day:'numeric'})}
                              </span>
                          </div>
                      </div>

                      <button 
                          onClick={(e) => { e.stopPropagation(); onDeleteReminder(reminder.id); }}
                          className="text-stone-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"
                      >
                          <Trash2 className="w-4 h-4" />
                      </button>
                  </div>
              ))
          )}
      </div>
  );

  return (
    <div 
        ref={dashboardRef}
        className="h-full overflow-y-auto bg-desk relative pb-20 md:pb-0" 
        onClick={() => setExportMenuOpen(null)}
        onContextMenu={(e) => handleContextMenu(e, 'general')}
    >
      {/* Hidden Inputs */}
      <input type="file" accept="image/*" ref={imageInputRef} className="hidden" onChange={handleImageUpload} />
      <input type="file" accept="application/pdf" ref={pdfInputRef} className="hidden" onChange={handlePdfUpload} />
      
      <div className="p-6 md:p-8 max-w-6xl mx-auto pr-0 md:pr-80">
        
        {/* Welcome Header */}
        <header className="mb-6 md:mb-10 flex flex-col md:flex-row md:justify-between md:items-end gap-4">
          <div>
            <h1 className="text-3xl md:text-4xl font-hand font-bold text-stone-800 mb-2">
                {activeSubject ? activeSubject : "Study Desk"}
            </h1>
            <p className="text-stone-500 text-sm md:text-base">
                {activeSubject 
                    ? `You have ${notes.length} notes in this register.` 
                    : "Tap the dots for options!"}
            </p>
          </div>
          <div className="hidden md:block text-sm font-hand text-stone-400 rotate-2">
             {new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })}
          </div>
        </header>

        {/* Registers (Notebooks) Stack */}
        {!activeSubject && (
            <section className="mb-12">
                <h2 className="text-lg font-bold text-stone-400 uppercase tracking-widest text-xs mb-6">My Notebooks</h2>
                
                {registers.length === 0 ? (
                    <div className="text-center py-12 bg-white/50 border-2 border-dashed border-stone-200 rounded-2xl">
                        <p className="text-stone-400 mb-4 font-hand text-xl">Your desk is empty.</p>
                        <button onClick={onNewNote} className="text-orange-600 font-bold hover:underline">Start a new notebook</button>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {displayRegisters.map(reg => (
                            <div 
                                key={reg.name} 
                                className="relative group perspective-1000"
                                onContextMenu={(e) => handleContextMenu(e, 'subject', reg.name, reg.name)}
                            >
                                {/* Notebook Cover */}
                                <div 
                                    className={`relative h-56 md:h-64 rounded-r-2xl rounded-l-md shadow-notebook transition-all duration-300 transform group-hover:-translate-y-2 group-hover:shadow-xl ${reg.color} border-l-8 border-black/10 cursor-pointer`}
                                    onClick={() => onOpenNote({ ...notes[0], subject: reg.name, id: '', title: '', date: '', type: 'text', summary: '', sections: [], tags: [] } as any)}
                                >
                                    {/* Mobile Context Trigger */}
                                    <div className="absolute top-2 right-2 md:hidden z-20">
                                         <button 
                                            onClick={(e) => handleContextMenu(e, 'subject', reg.name, reg.name)}
                                            className="p-2 bg-white/50 rounded-full text-stone-600 hover:bg-white"
                                         >
                                             <MoreVertical className="w-4 h-4" />
                                         </button>
                                    </div>

                                    {/* Notebook Binding Effect */}
                                    <div className="absolute left-0 top-0 bottom-0 w-4 bg-gradient-to-r from-black/20 to-transparent pointer-events-none"></div>
                                    <div className="absolute left-2 top-0 bottom-0 w-[1px] bg-white/20 pointer-events-none"></div>

                                    {/* Label */}
                                    <div className="absolute top-10 left-0 right-0 p-6 pointer-events-none">
                                        <div className="bg-white/90 backdrop-blur-sm p-4 shadow-sm transform -rotate-1 rounded-sm">
                                            <h3 className="font-hand font-bold text-2xl text-stone-800 leading-none text-center">
                                                {reg.name}
                                            </h3>
                                        </div>
                                    </div>

                                    {/* Pages Effect at bottom */}
                                    <div className="absolute bottom-0 left-4 right-0 h-3 bg-white rounded-br-2xl border-t border-stone-200 pointer-events-none"></div>
                                    <div className="absolute bottom-1 left-4 right-1 h-3 bg-stone-100 rounded-br-2xl border-t border-stone-200 pointer-events-none"></div>

                                    {/* Stats */}
                                    <div className="absolute bottom-6 left-6 pointer-events-none">
                                        <span className="bg-black/10 text-black/60 px-2 py-1 rounded text-xs font-bold">
                                            {reg.noteIds.length} Notes
                                        </span>
                                    </div>
                                    
                                    {/* Export Button (Desktop) */}
                                    <div className="absolute top-4 right-4 z-10 hidden md:block">
                                        <button 
                                            onClick={(e) => { e.stopPropagation(); setExportMenuOpen(exportMenuOpen === reg.name ? null : reg.name); }}
                                            className="p-1.5 bg-white/50 hover:bg-white rounded-full text-stone-600 hover:text-orange-600 transition-all opacity-0 group-hover:opacity-100 shadow-sm"
                                            title="Export Notebook"
                                        >
                                            <Download className="w-4 h-4" />
                                        </button>
                                        
                                        {exportMenuOpen === reg.name && (
                                            <div className="absolute right-0 top-8 w-40 bg-white rounded-xl shadow-xl border border-stone-100 py-1 overflow-hidden z-20 animate-fade-in">
                                                <button 
                                                    onClick={(e) => handleExport(e, 'pdf', reg)}
                                                    className="w-full flex items-center gap-2 px-3 py-2 text-xs font-bold text-stone-600 hover:bg-orange-50 hover:text-orange-600 text-left"
                                                >
                                                    <FileText className="w-3 h-3" /> PDF
                                                </button>
                                                <button 
                                                    onClick={(e) => handleExport(e, 'md', reg)}
                                                    className="w-full flex items-center gap-2 px-3 py-2 text-xs font-bold text-stone-600 hover:bg-orange-50 hover:text-orange-600 text-left"
                                                >
                                                    <File className="w-3 h-3" /> Markdown
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                        
                        {/* New Notebook Placeholder */}
                        <div onClick={onNewNote} className="h-56 md:h-64 rounded-2xl border-4 border-dashed border-stone-200 flex flex-col items-center justify-center cursor-pointer hover:border-orange-300 hover:bg-orange-50/50 transition-all group">
                            <div className="bg-white p-4 rounded-full shadow-sm mb-3 group-hover:scale-110 transition-transform">
                                <Book className="w-6 h-6 text-stone-400 group-hover:text-orange-500" />
                            </div>
                            <span className="font-hand text-xl text-stone-400 group-hover:text-orange-600">New Subject</span>
                        </div>
                    </div>
                )}
            </section>
        )}

        {/* Loose Papers (Recent Notes) */}
        <section>
          <h2 className="text-lg font-bold text-stone-400 uppercase tracking-widest text-xs mb-6 flex items-center gap-2">
            <Clock className="w-4 h-4" />
            Recent Papers
          </h2>

          {recentNotes.length === 0 ? (
             <p className="text-stone-400 italic ml-2">No loose papers found.</p>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {recentNotes.map((note, idx) => (
                    <div 
                        key={note.id} 
                        onClick={() => onOpenNote(note)}
                        onContextMenu={(e) => handleContextMenu(e, 'note', note.id, note.title)}
                        className="relative group cursor-pointer"
                        style={{ transform: `rotate(${idx % 2 === 0 ? '1deg' : '-1deg'})` }}
                    >
                        {/* Mobile Context Trigger */}
                         <div className="absolute top-2 right-2 md:hidden z-20">
                             <button 
                                onClick={(e) => handleContextMenu(e, 'note', note.id, note.title)}
                                className="p-2 bg-stone-100/80 rounded-full text-stone-600 hover:bg-white"
                             >
                                 <MoreVertical className="w-4 h-4" />
                             </button>
                         </div>

                        {/* Paper Shadow/Stack Effect */}
                        <div className="absolute inset-0 bg-stone-200 rounded-sm transform translate-x-1 translate-y-2"></div>
                        
                        {/* The Paper */}
                        <div className="relative bg-paper p-6 rounded-sm shadow-sm border border-stone-200 h-52 flex flex-col transition-transform group-hover:-translate-y-1">
                            {/* Holes (Binder paper style) */}
                            <div className="absolute top-0 bottom-0 left-4 flex flex-col justify-evenly">
                                <div className="w-3 h-3 bg-desk rounded-full shadow-inner"></div>
                                <div className="w-3 h-3 bg-desk rounded-full shadow-inner"></div>
                                <div className="w-3 h-3 bg-desk rounded-full shadow-inner"></div>
                            </div>

                            <div className="pl-8 h-full flex flex-col">
                                <div className="flex justify-between items-start mb-2">
                                    <span className={`text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded ${note.type === 'audio' ? 'bg-blue-100 text-blue-700' : note.type === 'pdf' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'}`}>
                                        {note.type}
                                    </span>
                                    <span className="font-hand text-stone-400 text-sm">{note.date}</span>
                                </div>
                                
                                <h3 className="font-hand font-bold text-2xl text-stone-800 mb-2 leading-tight group-hover:text-orange-700 transition-colors line-clamp-2">
                                    {note.title}
                                </h3>
                                
                                <div className="relative flex-1 overflow-hidden">
                                     <p className="text-sm text-stone-500 font-sans leading-relaxed line-clamp-3">
                                        {note.summary}
                                     </p>
                                     <div className="absolute bottom-0 left-0 right-0 h-8 bg-gradient-to-t from-paper to-transparent"></div>
                                </div>

                                <div className="mt-2 pt-2 border-t border-stone-100 flex justify-between items-center">
                                    <span className="text-xs text-stone-400 font-bold uppercase tracking-wide">{note.subject}</span>
                                    <ArrowRight className="w-4 h-4 text-stone-300 group-hover:text-orange-500" />
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
          )}
        </section>
      </div>

      {/* --- Reminder Panel (Sticky Note Style) - DESKTOP FIXED --- */}
      <div className="hidden md:flex absolute right-8 top-8 bottom-8 w-72 flex-col pointer-events-none">
          <div className="bg-yellow-200 shadow-xl transform -rotate-1 p-6 rounded-sm pointer-events-auto flex flex-col max-h-full border border-yellow-300 relative">
              <div className="absolute -top-4 left-1/2 -translate-x-1/2 w-16 h-8 bg-white/40 rotate-1 shadow-sm backdrop-blur-sm border-l border-r border-white/50"></div>
              <h3 className="font-hand font-bold text-2xl text-stone-800 mb-4 flex items-center gap-2 border-b-2 border-stone-800/10 pb-2">
                  <Bell className="w-5 h-5" /> 
                  Reminders
              </h3>
              <ReminderList />
          </div>
      </div>

      {/* --- Reminder Panel - MOBILE DRAWER (Slide from Right) --- */}
      {showMobileReminders && (
          <div className="fixed inset-0 z-50 md:hidden flex justify-end">
              <div className="absolute inset-0 bg-black/30 backdrop-blur-sm" onClick={onCloseMobileReminders}></div>
              <div className="w-80 bg-yellow-100 h-full shadow-2xl relative flex flex-col animate-slide-left border-l-4 border-yellow-300">
                  <button onClick={onCloseMobileReminders} className="absolute top-4 right-4 p-2 text-stone-500 hover:text-stone-800">
                      <X className="w-5 h-5" />
                  </button>
                  <div className="p-6 pt-10 flex-col flex h-full">
                      <h3 className="font-hand font-bold text-3xl text-stone-800 mb-6 flex items-center gap-2">
                         <Bell className="w-6 h-6" /> Reminders
                      </h3>
                      <div className="bg-yellow-200/50 -mx-6 px-6 py-2 flex-1 overflow-y-auto">
                        <ReminderList />
                      </div>
                      <p className="text-center text-xs text-stone-500 mt-4">Tap on a reminder to view</p>
                  </div>
              </div>
          </div>
      )}

      {/* --- Context Menu --- */}
      {contextMenu.visible && (
          <>
            <div className="fixed inset-0 z-40 md:hidden" onClick={() => setContextMenu({ ...contextMenu, visible: false })}></div>
            <div 
                className="fixed bg-white rounded-lg shadow-xl border border-stone-200 py-2 z-50 animate-fade-in w-56"
                style={{ top: contextMenu.y, left: contextMenu.x }}
            >
                <div className="px-4 py-2 border-b border-stone-100 mb-1 flex justify-between items-center">
                    <span className="text-xs font-bold text-stone-400 uppercase tracking-wider">
                        {contextMenu.type === 'subject' ? 'Subject' : contextMenu.type === 'note' ? 'Note' : 'Desk'}
                    </span>
                    {/* Close button for mobile comfort */}
                    <button className="md:hidden" onClick={() => setContextMenu({...contextMenu, visible: false})}>
                        <X className="w-3 h-3 text-stone-400" />
                    </button>
                </div>
                
                {contextMenu.type === 'subject' && (
                    <>
                        <button 
                            onClick={() => { onNewNoteInRegister(contextMenu.targetName!); setContextMenu({ ...contextMenu, visible: false }); }}
                            className="w-full text-left px-4 py-2 text-sm text-stone-700 hover:bg-orange-50 hover:text-orange-600 flex items-center gap-2"
                        >
                            <PenTool className="w-4 h-4" /> Create Note
                        </button>
                        <button 
                            onClick={() => { imageInputRef.current?.click(); setContextMenu({ ...contextMenu, visible: false }); }}
                            className="w-full text-left px-4 py-2 text-sm text-stone-700 hover:bg-orange-50 hover:text-orange-600 flex items-center gap-2"
                        >
                            <Image className="w-4 h-4" /> Upload Image
                        </button>
                        <button 
                            onClick={() => { pdfInputRef.current?.click(); setContextMenu({ ...contextMenu, visible: false }); }}
                            className="w-full text-left px-4 py-2 text-sm text-stone-700 hover:bg-orange-50 hover:text-orange-600 flex items-center gap-2"
                        >
                            <FileText className="w-4 h-4" /> Upload PDF
                        </button>
                        <div className="h-px bg-stone-100 my-1"></div>
                    </>
                )}

                <button 
                    onClick={openReminderModal}
                    className="w-full text-left px-4 py-2 text-sm text-stone-700 hover:bg-orange-50 hover:text-orange-600 flex items-center gap-2"
                >
                    <Bell className="w-4 h-4" />
                    {contextMenu.type === 'general' 
                        ? 'Create General Reminder' 
                        : `Remind me`}
                </button>
            </div>
          </>
      )}

      {/* --- Create Reminder Modal --- */}
      {isReminderModalOpen && (
          <div className="fixed inset-0 bg-black/20 backdrop-blur-sm z-[60] flex items-center justify-center p-4">
              <div className="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md animate-slide-up border border-stone-200">
                  <h3 className="font-hand font-bold text-2xl text-stone-800 mb-6 flex items-center gap-2">
                      <Clock className="text-orange-500" /> Set Reminder
                  </h3>
                  
                  <div className="space-y-4">
                      <div>
                          <label className="block text-xs font-bold uppercase text-stone-400 mb-1">Reminder</label>
                          <input 
                              type="text" 
                              value={reminderText}
                              onChange={(e) => setReminderText(e.target.value)}
                              className="w-full border-b-2 border-stone-200 focus:border-orange-400 outline-none py-2 font-hand text-xl bg-transparent"
                              placeholder="What do you need to remember?"
                              autoFocus
                          />
                      </div>
                      
                      {contextMenu.type !== 'general' && (
                          <div className="flex items-center gap-2 text-sm text-stone-500 bg-stone-50 p-2 rounded-lg">
                              <span className="font-bold">Linked to:</span>
                              <span className="bg-white border border-stone-200 px-2 py-0.5 rounded text-xs uppercase tracking-wide">
                                  {contextMenu.type}
                              </span>
                              <span className="truncate flex-1 font-medium">{contextMenu.targetName}</span>
                          </div>
                      )}

                      <div>
                           <label className="block text-xs font-bold uppercase text-stone-400 mb-1">Due Date</label>
                           <input 
                              type="datetime-local" 
                              value={reminderDate}
                              onChange={(e) => setReminderDate(e.target.value)}
                              className="w-full p-3 rounded-xl bg-stone-50 border border-stone-200 text-sm font-sans"
                           />
                      </div>
                  </div>

                  <div className="flex gap-3 mt-8">
                      <button 
                          onClick={() => setIsReminderModalOpen(false)}
                          className="flex-1 py-3 text-stone-500 hover:bg-stone-50 rounded-xl font-bold transition-colors"
                      >
                          Cancel
                      </button>
                      <button 
                          onClick={handleSaveReminder}
                          disabled={!reminderText}
                          className="flex-1 py-3 bg-stone-800 text-white hover:bg-stone-900 rounded-xl font-bold shadow-lg shadow-stone-200 disabled:opacity-50 transition-all"
                      >
                          Save
                      </button>
                  </div>
              </div>
          </div>
      )}
    </div>
  );
};

export default Dashboard;