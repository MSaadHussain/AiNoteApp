import React, { useState, useEffect, useRef } from 'react';
import { Note, Reminder } from '../types';
import { ArrowLeft, BookOpen, BrainCircuit, Sparkles, StickyNote, FileText, Bell, PenTool, X, Search, Send, ArrowRight, ListFilter } from 'lucide-react';
import { getQuickAnswer, solveWithThinking } from '../services/geminiService';

interface PdfReaderProps {
    note: Note;
    onClose: () => void;
    onAddReminder: (reminder: Reminder) => void;
    onCreateNoteFromText: (text: string, subject: string) => void;
}

interface ChatMsg {
    id: string;
    role: 'user' | 'ai';
    content: string;
    isThinking?: boolean;
}

const PdfReader: React.FC<PdfReaderProps> = ({ note, onClose, onAddReminder, onCreateNoteFromText }) => {
    const [activeTab, setActiveTab] = useState<'pdf' | 'text'>('text');
    const [selectedText, setSelectedText] = useState('');
    const [contextMenu, setContextMenu] = useState<{ visible: boolean; x: number; y: number } | null>(null);
    const [aiPanelOpen, setAiPanelOpen] = useState(true);

    // Search state
    const [searchQuery, setSearchQuery] = useState('');
    const [searchHighlight, setSearchHighlight] = useState('');

    // Chat state  
    const [chatInput, setChatInput] = useState('');
    const [chatMessages, setChatMessages] = useState<ChatMsg[]>([]);
    const [isSummarizing, setIsSummarizing] = useState(false);
    const chatEndRef = useRef<HTMLDivElement>(null);

    // Close context menu on click
    useEffect(() => {
        const handleClick = () => setContextMenu(null);
        document.addEventListener('click', handleClick);
        return () => document.removeEventListener('click', handleClick);
    }, []);

    useEffect(() => {
        chatEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [chatMessages]);

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

    const handleAiExplain = () => {
        setContextMenu(null);
        setAiPanelOpen(true);
        sendChatMessage(`Explain this: "${selectedText.substring(0, 500)}"`);
    };

    const handleCreateReminder = () => {
        const reminder: Reminder = {
            id: crypto.randomUUID(),
            text: `Read: "${selectedText.substring(0, 30)}..."`,
            dueDate: new Date(Date.now() + 86400000).toLocaleString(),
            type: 'note',
            targetId: note.id,
            targetName: note.title,
            completed: false
        };
        onAddReminder(reminder);
        setContextMenu(null);
    };

    const handleCreateNote = () => {
        onCreateNoteFromText(selectedText, note.subject);
        setContextMenu(null);
    };

    const handleAskAboutSelection = () => {
        setContextMenu(null);
        setAiPanelOpen(true);
        setChatInput(`About this text: "${selectedText.substring(0, 200)}..." — `);
    };

    const sendChatMessage = async (message: string) => {
        if (!message.trim()) return;

        const userMsg: ChatMsg = { id: crypto.randomUUID(), role: 'user', content: message };
        const thinkingMsg: ChatMsg = { id: crypto.randomUUID(), role: 'ai', content: '', isThinking: true };

        setChatMessages(prev => [...prev, userMsg, thinkingMsg]);
        setChatInput('');

        try {
            // Use document content as context
            const docContext = note.rawContent?.substring(0, 4000) || '';
            const response = await getQuickAnswer(docContext, message);

            setChatMessages(prev => prev.map(m =>
                m.id === thinkingMsg.id
                    ? { ...m, content: response || "I couldn't find an answer.", isThinking: false }
                    : m
            ));
        } catch (err) {
            setChatMessages(prev => prev.map(m =>
                m.id === thinkingMsg.id
                    ? { ...m, content: "Sorry, there was an error getting a response.", isThinking: false }
                    : m
            ));
        }
    };

    const handleDeepThink = async (question: string) => {
        const userMsg: ChatMsg = { id: crypto.randomUUID(), role: 'user', content: `🧠 Deep Think: ${question}` };
        const thinkingMsg: ChatMsg = { id: crypto.randomUUID(), role: 'ai', content: '', isThinking: true };

        setChatMessages(prev => [...prev, userMsg, thinkingMsg]);

        try {
            const docContext = note.rawContent?.substring(0, 4000) || '';
            const response = await solveWithThinking(docContext, question);

            setChatMessages(prev => prev.map(m =>
                m.id === thinkingMsg.id
                    ? { ...m, content: response || "Could not generate a solution.", isThinking: false }
                    : m
            ));
        } catch (err) {
            setChatMessages(prev => prev.map(m =>
                m.id === thinkingMsg.id
                    ? { ...m, content: "Error during deep thinking.", isThinking: false }
                    : m
            ));
        }
    };

    const handleSearch = (query: string) => {
        setSearchQuery(query);
        setSearchHighlight(query.trim().toLowerCase());
    };

    const handleSummarize = async () => {
        if (isSummarizing || !note.rawContent) return;
        setIsSummarizing(true);
        setAiPanelOpen(true);

        const userMsg: ChatMsg = { id: crypto.randomUUID(), role: 'user', content: '📝 Summarize this document' };
        const thinkingMsg: ChatMsg = { id: crypto.randomUUID(), role: 'ai', content: '', isThinking: true };
        setChatMessages(prev => [...prev, userMsg, thinkingMsg]);

        try {
            const docText = note.rawContent.substring(0, 6000);
            const response = await getQuickAnswer(
                docText,
                'Provide a comprehensive summary of this document. Include the main topics, key points, and important conclusions. Format with clear sections.'
            );
            setChatMessages(prev => prev.map(m =>
                m.id === thinkingMsg.id
                    ? { ...m, content: response || 'Could not generate summary.', isThinking: false }
                    : m
            ));
        } catch (err) {
            setChatMessages(prev => prev.map(m =>
                m.id === thinkingMsg.id
                    ? { ...m, content: 'Error generating summary.', isThinking: false }
                    : m
            ));
        } finally {
            setIsSummarizing(false);
        }
    };

    // Render text with search highlights
    const renderHighlightedText = (text: string) => {
        if (!searchHighlight) return text;

        const parts = text.split(new RegExp(`(${searchHighlight.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi'));
        return parts.map((part, i) =>
            part.toLowerCase() === searchHighlight
                ? <mark key={i} className="bg-yellow-300 text-yellow-900 px-0.5 rounded">{part}</mark>
                : part
        );
    };

    const matchCount = searchHighlight && note.rawContent
        ? (note.rawContent.toLowerCase().match(new RegExp(searchHighlight.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi')) || []).length
        : 0;

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

            {/* Main Content */}
            <div className={`flex-1 flex pt-14 h-full transition-all ${aiPanelOpen ? 'mr-[400px]' : ''}`}>
                <div className="flex-1 bg-stone-200 h-full overflow-hidden relative flex flex-col">

                    {/* Search Bar (only in Smart Reader mode) */}
                    {activeTab === 'text' && (
                        <div className="bg-white border-b border-stone-200 px-4 py-2 flex items-center gap-3">
                            <Search className="w-4 h-4 text-stone-400" />
                            <input
                                type="text"
                                value={searchQuery}
                                onChange={(e) => handleSearch(e.target.value)}
                                placeholder="Search in document..."
                                className="flex-1 text-sm bg-transparent focus:outline-none text-stone-700 placeholder-stone-400"
                            />
                            {searchHighlight && (
                                <span className="text-xs text-stone-500 bg-stone-100 px-2 py-1 rounded-full">
                                    {matchCount} {matchCount === 1 ? 'match' : 'matches'}
                                </span>
                            )}
                            {searchQuery && (
                                <button onClick={() => handleSearch('')} className="text-stone-400 hover:text-stone-600">
                                    <X className="w-4 h-4" />
                                </button>
                            )}
                            <div className="w-px h-5 bg-stone-200"></div>
                            <button
                                onClick={handleSummarize}
                                disabled={isSummarizing}
                                className="flex items-center gap-1.5 px-3 py-1.5 bg-orange-50 hover:bg-orange-100 text-orange-600 rounded-lg text-xs font-bold transition-colors disabled:opacity-50 whitespace-nowrap border border-orange-200"
                            >
                                <ListFilter className="w-3.5 h-3.5" />
                                {isSummarizing ? 'Summarizing...' : 'Summarize'}
                            </button>
                        </div>
                    )}

                    {activeTab === 'pdf' ? (
                        note.pdfUrl ? (
                            <iframe src={note.pdfUrl} className="w-full flex-1 border-none" title="PDF Viewer"></iframe>
                        ) : (
                            <div className="flex items-center justify-center flex-1 text-stone-500">No PDF file attached.</div>
                        )
                    ) : (
                        <div
                            className="flex-1 overflow-y-auto p-8 bg-white max-w-3xl mx-auto shadow-sm w-full"
                            onMouseUp={handleTextSelection}
                            onContextMenu={handleContextMenu}
                        >
                            <h1 className="text-3xl font-bold mb-2 text-stone-900">{note.title}</h1>
                            <p className="text-sm text-stone-400 mb-6 border-b border-stone-100 pb-4">
                                {note.subject} • {note.date} • Select text to ask AI or right-click for options
                            </p>
                            <div className="prose prose-lg prose-stone max-w-none font-serif leading-relaxed whitespace-pre-wrap select-text">
                                {note.rawContent
                                    ? renderHighlightedText(note.rawContent)
                                    : "No text content extracted. Try re-uploading the PDF."}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* AI Chat Panel */}
            <div className={`absolute top-14 bottom-0 right-0 w-[400px] bg-white border-l border-stone-200 transform transition-transform duration-300 z-10 flex flex-col ${aiPanelOpen ? 'translate-x-0' : 'translate-x-full'}`}>
                <div className="p-4 border-b border-stone-100 bg-orange-50/50 flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Sparkles className="w-5 h-5 text-orange-500" />
                        <h3 className="font-bold text-stone-700">AI Chat</h3>
                    </div>
                    {chatMessages.length > 0 && (
                        <button
                            onClick={() => setChatMessages([])}
                            className="text-xs text-stone-400 hover:text-stone-600 px-2 py-1 rounded hover:bg-stone-100"
                        >
                            Clear
                        </button>
                    )}
                </div>

                {/* Chat Messages */}
                <div className="flex-1 overflow-y-auto p-4 space-y-3 bg-stone-50">
                    {chatMessages.length === 0 && (
                        <div className="text-center text-stone-400 mt-10 p-4">
                            <BrainCircuit className="w-12 h-12 mx-auto mb-3 opacity-50" />
                            <p className="text-sm font-medium mb-1">Ask me anything about this document!</p>
                            <p className="text-xs text-stone-400">Select text in Smart Reader and right-click to explain, or type a question below.</p>
                        </div>
                    )}

                    {chatMessages.map(msg => (
                        <div key={msg.id} className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                            <div className={`max-w-[85%] rounded-2xl px-4 py-2.5 text-sm ${msg.role === 'user'
                                ? 'bg-orange-500 text-white rounded-tr-sm'
                                : 'bg-white border border-stone-200 text-stone-700 rounded-tl-sm shadow-sm'
                                }`}>
                                {msg.isThinking ? (
                                    <div className="flex items-center gap-2 text-orange-500">
                                        <Sparkles className="w-3 h-3 animate-spin" />
                                        <span className="text-xs font-medium">Thinking...</span>
                                    </div>
                                ) : (
                                    <p className="whitespace-pre-wrap leading-relaxed">{msg.content}</p>
                                )}
                            </div>
                        </div>
                    ))}
                    <div ref={chatEndRef} />
                </div>

                {/* Selected Text Indicator */}
                {selectedText && (
                    <div className="px-4 py-2 bg-yellow-50 border-t border-yellow-100 flex items-center justify-between">
                        <p className="text-xs text-yellow-700 truncate flex-1">
                            <span className="font-bold">Selected:</span> "{selectedText.substring(0, 60)}..."
                        </p>
                        <button
                            onClick={() => sendChatMessage(`Explain: "${selectedText.substring(0, 500)}"`)}
                            className="text-xs bg-yellow-200 hover:bg-yellow-300 text-yellow-800 px-2 py-1 rounded font-bold ml-2 whitespace-nowrap"
                        >
                            Ask AI
                        </button>
                    </div>
                )}

                {/* Chat Input */}
                <div className="p-3 bg-white border-t border-stone-200">
                    <div className="relative flex gap-2">
                        <input
                            type="text"
                            value={chatInput}
                            onChange={(e) => setChatInput(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && sendChatMessage(chatInput)}
                            placeholder="Ask about this document..."
                            className="flex-1 pl-4 pr-4 py-2.5 bg-stone-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-200"
                        />
                        <button
                            onClick={() => sendChatMessage(chatInput)}
                            disabled={!chatInput.trim()}
                            className="p-2.5 bg-orange-500 text-white rounded-xl hover:bg-orange-600 disabled:opacity-40 transition-colors"
                        >
                            <Send className="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            {/* Context Menu */}
            {contextMenu?.visible && (
                <div
                    className="fixed bg-white rounded-xl shadow-2xl border border-stone-200 py-2 z-50 animate-fade-in w-52"
                    style={{ top: contextMenu.y, left: contextMenu.x }}
                >
                    <button onClick={handleAiExplain} className="w-full text-left px-4 py-2.5 text-sm text-stone-700 hover:bg-orange-50 hover:text-orange-600 flex items-center gap-3">
                        <Sparkles className="w-4 h-4" /> Explain This
                    </button>
                    <button onClick={handleAskAboutSelection} className="w-full text-left px-4 py-2.5 text-sm text-stone-700 hover:bg-orange-50 hover:text-orange-600 flex items-center gap-3">
                        <BrainCircuit className="w-4 h-4" /> Ask About This
                    </button>
                    <div className="border-t border-stone-100 my-1"></div>
                    <button onClick={handleCreateReminder} className="w-full text-left px-4 py-2.5 text-sm text-stone-700 hover:bg-orange-50 hover:text-orange-600 flex items-center gap-3">
                        <Bell className="w-4 h-4" /> Create Reminder
                    </button>
                    <button onClick={handleCreateNote} className="w-full text-left px-4 py-2.5 text-sm text-stone-700 hover:bg-orange-50 hover:text-orange-600 flex items-center gap-3">
                        <StickyNote className="w-4 h-4" /> Save as Note
                    </button>
                </div>
            )}
        </div>
    );
};

export default PdfReader;