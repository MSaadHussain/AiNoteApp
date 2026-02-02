import React, { useState } from 'react';
import { Note, SubjectRegister } from '../types';
import { Clock, Book, ArrowRight, PenTool, Download, FileText, File } from 'lucide-react';
import { exportRegisterAsMarkdown, exportRegisterAsPDF } from '../services/exportService';

interface DashboardProps {
  notes: Note[];
  onOpenNote: (note: Note) => void;
  onNewNote: () => void;
  registers: SubjectRegister[];
  activeSubject: string | null;
}

const Dashboard: React.FC<DashboardProps> = ({ notes, onOpenNote, onNewNote, registers, activeSubject }) => {
  const [exportMenuOpen, setExportMenuOpen] = useState<string | null>(null);
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

  return (
    <div className="h-full overflow-y-auto bg-desk" onClick={() => setExportMenuOpen(null)}>
      <div className="p-8 max-w-6xl mx-auto">
        
        {/* Welcome Header */}
        <header className="mb-10 flex justify-between items-end">
          <div>
            <h1 className="text-4xl font-hand font-bold text-stone-800 mb-2">
                {activeSubject ? activeSubject : "Study Desk"}
            </h1>
            <p className="text-stone-500">
                {activeSubject 
                    ? `You have ${notes.length} notes in this register.` 
                    : "Good luck with your studies today!"}
            </p>
          </div>
          <div className="text-sm font-hand text-stone-400 rotate-2">
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
                    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        {displayRegisters.map(reg => (
                            <div key={reg.name} className="relative group perspective-1000">
                                {/* Notebook Cover */}
                                <div 
                                    className={`relative h-64 rounded-r-2xl rounded-l-md shadow-notebook transition-all duration-300 transform group-hover:-translate-y-2 group-hover:shadow-xl ${reg.color} border-l-8 border-black/10 cursor-pointer`}
                                    onClick={() => onOpenNote({ ...notes[0], subject: reg.name, id: '', title: '', date: '', type: 'text', summary: '', sections: [], tags: [] } as any)} // Hack to trigger subject view via parent logic if implemented, or just use click handler
                                >
                                    
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
                                    
                                    {/* Export Button */}
                                    <div className="absolute top-4 right-4 z-10">
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
                        <div onClick={onNewNote} className="h-64 rounded-2xl border-4 border-dashed border-stone-200 flex flex-col items-center justify-center cursor-pointer hover:border-orange-300 hover:bg-orange-50/50 transition-all group">
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
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {recentNotes.map((note, idx) => (
                    <div 
                        key={note.id} 
                        onClick={() => onOpenNote(note)}
                        className="relative group cursor-pointer"
                        style={{ transform: `rotate(${idx % 2 === 0 ? '1deg' : '-1deg'})` }}
                    >
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
    </div>
  );
};

export default Dashboard;
