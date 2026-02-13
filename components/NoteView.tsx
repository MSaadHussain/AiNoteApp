import React, { useState } from 'react';
import { Note, NoteSection } from '../types';
import { BrainCircuit, Play, Volume2, Sparkles, X, MessageSquare, ArrowLeft, MoreVertical, ArrowRight, Download, FileText, File } from 'lucide-react';
import { solveWithThinking, speakText, stopSpeaking } from '../services/geminiService';
import { exportNoteAsMarkdown, exportNoteAsPDF } from '../services/exportService';

interface NoteViewProps {
    note: Note;
    onClose: () => void;
}

const NoteView: React.FC<NoteViewProps> = ({ note, onClose }) => {
    const [activeSection, setActiveSection] = useState<number | null>(null);
    const [solverOpen, setSolverOpen] = useState(false);
    const [solverQuery, setSolverQuery] = useState('');
    const [solverResponse, setSolverResponse] = useState('');
    const [isThinking, setIsThinking] = useState(false);
    const [isSpeaking, setIsSpeaking] = useState(false);
    const [showMenu, setShowMenu] = useState(false);

    const handleSolve = async () => {
        if (!solverQuery.trim()) return;
        setIsThinking(true);
        setSolverResponse('');

        try {
            const context = activeSection !== null
                ? note.sections[activeSection].content
                : note.summary + "\n" + note.sections.map(s => s.content).join('\n');

            const response = await solveWithThinking(context, solverQuery);
            setSolverResponse(response);
        } catch (error) {
            setSolverResponse("Sorry, I encountered an error while thinking.");
        } finally {
            setIsThinking(false);
        }
    };

    const handleSpeak = async (text: string) => {
        if (isSpeaking) {
            stopSpeaking();
            setIsSpeaking(false);
            return;
        }
        setIsSpeaking(true);
        try {
            await speakText(text);
        } catch (e) {
            console.error("TTS Error", e);
        } finally {
            setIsSpeaking(false);
        }
    }

    return (
        <div className="flex h-full relative bg-desk">

            {/* Top Bar (Desk tools) */}
            <div className="absolute top-0 left-0 right-0 h-16 bg-white/80 backdrop-blur-md border-b border-stone-200 z-10 flex items-center justify-between px-6 shadow-sm">
                <div className="flex items-center gap-4">
                    <button onClick={onClose} className="p-2 hover:bg-stone-100 rounded-full text-stone-500 transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </button>
                    <div>
                        <h2 className="font-hand font-bold text-xl text-stone-800 leading-none">{note.title}</h2>
                        <span className="text-xs text-stone-400 font-bold uppercase tracking-wider">{note.subject} • {note.date}</span>
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    <button
                        onClick={() => setSolverOpen(true)}
                        className="bg-yellow-300 hover:bg-yellow-400 text-stone-900 px-4 py-2 rounded-lg flex items-center gap-2 text-sm font-hand font-bold transform -rotate-1 shadow-sm transition-transform hover:rotate-0"
                    >
                        <BrainCircuit className="w-4 h-4" />
                        Study Buddy
                    </button>

                    <div className="relative">
                        <button
                            onClick={() => setShowMenu(!showMenu)}
                            className="p-2 hover:bg-stone-100 rounded-full text-stone-400"
                        >
                            <MoreVertical className="w-5 h-5" />
                        </button>

                        {showMenu && (
                            <>
                                <div className="fixed inset-0 z-10" onClick={() => setShowMenu(false)}></div>
                                <div className="absolute right-0 top-12 w-48 bg-white rounded-xl shadow-xl border border-stone-100 z-20 py-2 animate-fade-in overflow-hidden">
                                    <p className="px-4 py-2 text-xs font-bold text-stone-400 uppercase tracking-wider border-b border-stone-100 mb-1">Export Note</p>
                                    <button
                                        onClick={() => { exportNoteAsPDF(note); setShowMenu(false); }}
                                        className="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-stone-600 hover:bg-orange-50 hover:text-orange-700 transition-colors"
                                    >
                                        <FileText className="w-4 h-4" />
                                        Export as PDF
                                    </button>
                                    <button
                                        onClick={() => { exportNoteAsMarkdown(note); setShowMenu(false); }}
                                        className="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-stone-600 hover:bg-orange-50 hover:text-orange-700 transition-colors"
                                    >
                                        <File className="w-4 h-4" />
                                        Export as Markdown
                                    </button>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </div>

            {/* Main Content Area (The Paper) */}
            <div className="flex-1 overflow-y-auto pt-20 pb-20 px-4 md:px-0">
                <div className="max-w-3xl mx-auto bg-paper shadow-2xl min-h-[90vh] relative lined-paper mb-10">

                    {/* Top of Paper Content */}
                    <div className="pl-16 pr-8 pt-12 pb-8">
                        <h1 className="text-4xl font-hand font-bold text-ink mb-6">{note.title}</h1>

                        {/* Summary Box (Looks like a sticky note or highlighted section) */}
                        <div className="relative mb-8 group">
                            <div className="absolute -inset-1 bg-yellow-100/50 rounded-lg transform -rotate-1 group-hover:rotate-0 transition-transform"></div>
                            <div className="relative border-l-4 border-yellow-400 pl-4 py-2">
                                <div className="flex justify-between items-start">
                                    <h3 className="font-hand font-bold text-lg text-stone-500 uppercase tracking-widest mb-1">Summary</h3>
                                    <button onClick={() => handleSpeak(note.summary)} disabled={isSpeaking} className="text-stone-300 hover:text-stone-600">
                                        <Volume2 className="w-4 h-4" />
                                    </button>
                                </div>
                                <p className="text-stone-700 leading-8 font-serif italic text-lg">{note.summary}</p>
                            </div>
                        </div>

                        {/* Sections */}
                        <div className="space-y-8">
                            {note.sections.map((section, idx) => (
                                <div
                                    key={idx}
                                    className="group relative"
                                    onMouseEnter={() => setActiveSection(idx)}
                                >
                                    {/* Interaction Hint in Margin */}
                                    <div className="absolute -left-12 top-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button
                                            onClick={() => {
                                                setSolverQuery(`Explain this: "${section.heading}"`);
                                                setSolverOpen(true);
                                            }}
                                            className="bg-stone-100 p-1.5 rounded-full text-stone-400 hover:text-orange-500 hover:bg-orange-50 shadow-sm border border-stone-200"
                                        >
                                            <Sparkles className="w-4 h-4" />
                                        </button>
                                    </div>

                                    <h2 className="text-2xl font-hand font-bold text-blue-800 mt-6 mb-2 flex items-baseline gap-2">
                                        {section.heading}
                                        <span className="text-[10px] font-sans text-stone-400 uppercase border border-stone-200 px-1 rounded bg-white">
                                            {section.type}
                                        </span>
                                    </h2>

                                    <div className="text-lg text-stone-800 leading-8">
                                        {/* We can do some simple formatting here if content has markdown */}
                                        {section.content.split('\n').map((para, i) => (
                                            <p key={i} className="mb-4">{para}</p>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Keywords */}
                        <div className="mt-12 pt-8 border-t-2 border-dashed border-stone-200/50">
                            <div className="flex flex-wrap gap-3">
                                {note.tags.map(tag => (
                                    <span key={tag} className="px-3 py-1 font-hand text-xl bg-stone-100 text-stone-500 rounded-full border border-stone-200 transform hover:-rotate-2 transition-transform cursor-default">
                                        #{tag}
                                    </span>
                                ))}
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {/* AI Buddy Panel (Slide from right) */}
            {solverOpen && (
                <div className="w-[400px] bg-white shadow-[-10px_0_30px_rgba(0,0,0,0.1)] flex flex-col absolute right-0 top-16 bottom-0 z-20 border-l border-stone-100">

                    <div className="p-4 bg-yellow-50 border-b border-yellow-100 flex justify-between items-center">
                        <div className="flex items-center gap-2">
                            <div className="bg-yellow-400 p-1.5 rounded-full">
                                <BrainCircuit className="w-5 h-5 text-yellow-900" />
                            </div>
                            <div>
                                <h3 className="font-hand font-bold text-xl text-stone-800 leading-none">Study Buddy</h3>
                                <p className="text-xs text-stone-500">Always here to help.</p>
                            </div>
                        </div>
                        <button onClick={() => setSolverOpen(false)} className="hover:bg-yellow-100 p-1 rounded-full text-stone-500">
                            <X className="w-5 h-5" />
                        </button>
                    </div>

                    <div className="flex-1 overflow-y-auto p-4 bg-desk space-y-4">
                        {/* Chat Bubbles */}

                        {/* Context Bubble */}
                        {activeSection !== null && !solverResponse && !solverQuery && (
                            <div className="sticky-note p-4 text-stone-800 rotate-1 max-w-[90%] mx-auto">
                                <p className="font-hand text-lg leading-tight mb-2">I see you're reading about:</p>
                                <p className="font-bold border-b border-stone-800/20 inline-block">{note.sections[activeSection].heading}</p>
                                <p className="font-hand text-lg mt-2 text-stone-600">Need a simpler explanation?</p>
                            </div>
                        )}

                        {!solverResponse && !isThinking && !solverQuery && activeSection === null && (
                            <div className="flex flex-col items-center justify-center mt-10 opacity-60">
                                <MessageSquare className="w-12 h-12 text-stone-300 mb-2" />
                                <p className="font-hand text-xl text-stone-400 text-center">Ask me anything about your notes!</p>
                            </div>
                        )}

                        {/* User Query */}
                        {solverQuery && (
                            <div className="self-end bg-white border border-stone-200 p-3 rounded-2xl rounded-tr-sm shadow-sm max-w-[85%] ml-auto mb-2">
                                <p className="text-sm text-stone-700">{solverQuery}</p>
                            </div>
                        )}

                        {/* AI Thinking */}
                        {isThinking && (
                            <div className="flex items-center gap-2 text-stone-400 p-2">
                                <Sparkles className="w-4 h-4 animate-spin text-orange-400" />
                                <span className="font-hand text-lg">Thinking...</span>
                            </div>
                        )}

                        {/* AI Response */}
                        {solverResponse && (
                            <div className="bg-white border-l-4 border-orange-400 p-4 rounded-r-xl shadow-sm animate-slide-up">
                                <div className="flex items-center gap-2 mb-2">
                                    <span className="text-xs font-bold uppercase text-orange-500 tracking-wider">Answer</span>
                                </div>
                                <div className="prose prose-sm prose-stone font-sans">
                                    {solverResponse}
                                </div>
                                <div className="mt-3 flex gap-2">
                                    <button
                                        onClick={() => handleSpeak(solverResponse)}
                                        className="p-1.5 bg-stone-100 rounded-full hover:bg-stone-200 text-stone-500"
                                        title="Read aloud"
                                    >
                                        <Volume2 className="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="p-4 bg-white border-t border-stone-100">
                        <div className="relative shadow-sm rounded-xl overflow-hidden border border-stone-300 focus-within:border-orange-400 focus-within:ring-2 focus-within:ring-orange-100 transition-all">
                            <textarea
                                value={solverQuery}
                                onChange={(e) => setSolverQuery(e.target.value)}
                                placeholder="Ask a question..."
                                className="w-full pl-3 pr-10 py-3 bg-white text-sm focus:outline-none resize-none h-20"
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter' && !e.shiftKey) {
                                        e.preventDefault();
                                        handleSolve();
                                    }
                                }}
                            />
                            <button
                                onClick={handleSolve}
                                disabled={!solverQuery.trim() || isThinking}
                                className="absolute right-2 bottom-2 p-1.5 bg-orange-500 text-white rounded-lg hover:bg-orange-600 disabled:opacity-50 transition-colors"
                            >
                                <ArrowRight className="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default NoteView;
