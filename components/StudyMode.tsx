import React, { useState } from 'react';
import { Note, Flashcard, QuizQuestion } from '../types';
import { generateFlashcards, generateQuiz } from '../services/geminiService';
import { Zap, BookOpen, BrainCircuit, ChevronRight, RotateCw, CheckCircle, XCircle, Loader2 } from 'lucide-react';

interface StudyModeProps {
  notes: Note[];
  onExit: () => void;
}

type Mode = 'SELECT' | 'FLASHCARDS' | 'QUIZ';

const StudyMode: React.FC<StudyModeProps> = ({ notes, onExit }) => {
  const [mode, setMode] = useState<Mode>('SELECT');
  const [selectedNote, setSelectedNote] = useState<Note | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [flashcards, setFlashcards] = useState<Flashcard[]>([]);
  const [quizQuestions, setQuizQuestions] = useState<QuizQuestion[]>([]);
  
  // Flashcard State
  const [currentCardIndex, setCurrentCardIndex] = useState(0);
  const [isFlipped, setIsFlipped] = useState(false);

  // Quiz State
  const [answers, setAnswers] = useState<{[key: string]: number}>({});
  const [quizSubmitted, setQuizSubmitted] = useState(false);

  const handleStartFlashcards = async () => {
    if (!selectedNote) return;
    setIsLoading(true);
    try {
      const cards = await generateFlashcards(selectedNote.summary + " " + selectedNote.sections.map(s => s.content).join(" "));
      setFlashcards(cards);
      setMode('FLASHCARDS');
      setCurrentCardIndex(0);
      setIsFlipped(false);
    } catch (e) {
      console.error(e);
      alert("Failed to generate flashcards.");
    } finally {
      setIsLoading(false);
    }
  };

  const handleStartQuiz = async () => {
    if (!selectedNote) return;
    setIsLoading(true);
    try {
      const qs = await generateQuiz(selectedNote.summary + " " + selectedNote.sections.map(s => s.content).join(" "));
      setQuizQuestions(qs);
      setMode('QUIZ');
      setAnswers({});
      setQuizSubmitted(false);
    } catch (e) {
      console.error(e);
      alert("Failed to generate quiz.");
    } finally {
      setIsLoading(false);
    }
  };

  const calculateQuizScore = () => {
    let score = 0;
    quizQuestions.forEach(q => {
      if (answers[q.id] === q.correctAnswer) score++;
    });
    return score;
  };

  if (mode === 'SELECT') {
    return (
      <div className="p-8 h-full overflow-y-auto">
        <button onClick={onExit} className="mb-6 text-sm text-slate-500 hover:text-slate-800 flex items-center gap-1">
           &larr; Back to Dashboard
        </button>
        <div className="max-w-4xl mx-auto text-center mb-12">
            <h2 className="text-3xl font-bold text-slate-900 mb-3 flex items-center justify-center gap-3">
                <Zap className="text-yellow-500 fill-current w-8 h-8" /> 
                Study Center
            </h2>
            <p className="text-slate-500">Select a lecture to generate AI-powered study materials.</p>
        </div>

        {notes.length === 0 && (
             <div className="text-center text-slate-400 mt-20">No notes available to study. Record a lecture first!</div>
        )}

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
            {notes.map(note => (
                <div 
                    key={note.id}
                    onClick={() => setSelectedNote(note)}
                    className={`cursor-pointer p-6 rounded-2xl border-2 transition-all ${selectedNote?.id === note.id ? 'border-indigo-500 bg-indigo-50 shadow-md' : 'border-slate-200 bg-white hover:border-indigo-300'}`}
                >
                    <span className="text-[10px] font-bold uppercase tracking-wider bg-white border border-slate-200 text-slate-600 px-2 py-1 rounded mb-3 inline-block">
                        {note.subject}
                    </span>
                    <h3 className="font-bold text-slate-800 mb-2 truncate">{note.title}</h3>
                    <p className="text-xs text-slate-500">{note.date}</p>
                </div>
            ))}
        </div>

        {selectedNote && (
            <div className="fixed bottom-0 left-64 right-0 p-6 bg-white border-t border-slate-200 flex justify-center gap-4 animate-slide-up shadow-2xl z-10">
                <button 
                    onClick={handleStartFlashcards}
                    disabled={isLoading}
                    className="flex items-center gap-2 px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-lg shadow-lg shadow-indigo-200 transition-all disabled:opacity-50"
                >
                    {isLoading ? <Loader2 className="animate-spin" /> : <BookOpen className="w-5 h-5" />}
                    Generate Flashcards
                </button>
                <button 
                    onClick={handleStartQuiz}
                    disabled={isLoading}
                    className="flex items-center gap-2 px-8 py-4 bg-white border-2 border-slate-200 hover:border-indigo-500 text-slate-700 hover:text-indigo-600 rounded-xl font-bold text-lg transition-all disabled:opacity-50"
                >
                    {isLoading ? <Loader2 className="animate-spin" /> : <BrainCircuit className="w-5 h-5" />}
                    Take Quiz
                </button>
            </div>
        )}
      </div>
    );
  }

  if (mode === 'FLASHCARDS') {
    const card = flashcards[currentCardIndex];
    return (
        <div className="flex flex-col h-full bg-slate-100">
             <header className="p-6 flex justify-between items-center bg-white shadow-sm z-10">
                <h3 className="font-bold text-lg text-slate-700">{selectedNote?.title} - Flashcards</h3>
                <button onClick={() => setMode('SELECT')} className="text-sm font-medium text-slate-500 hover:text-slate-800">Close</button>
             </header>
             <div className="flex-1 flex flex-col items-center justify-center p-8">
                <div 
                    className="perspective-1000 w-full max-w-2xl h-96 cursor-pointer group"
                    onClick={() => setIsFlipped(!isFlipped)}
                >
                    <div className={`relative w-full h-full transition-transform duration-500 transform-style-3d ${isFlipped ? 'rotate-y-180' : ''}`}>
                        {/* Front */}
                        <div className="absolute inset-0 backface-hidden bg-white rounded-3xl shadow-xl flex flex-col items-center justify-center p-12 text-center border border-slate-200">
                             <span className="text-xs uppercase font-bold text-indigo-500 mb-4 tracking-widest">Question</span>
                             <p className="text-2xl font-medium text-slate-800">{card.front}</p>
                             <p className="absolute bottom-6 text-xs text-slate-400">Click to flip</p>
                        </div>
                        {/* Back */}
                        <div className="absolute inset-0 backface-hidden bg-indigo-600 text-white rounded-3xl shadow-xl flex flex-col items-center justify-center p-12 text-center rotate-y-180">
                             <span className="text-xs uppercase font-bold text-indigo-200 mb-4 tracking-widest">Answer</span>
                             <p className="text-xl leading-relaxed">{card.back}</p>
                        </div>
                    </div>
                </div>

                <div className="flex items-center gap-6 mt-8">
                    <button 
                        onClick={() => { setCurrentCardIndex(Math.max(0, currentCardIndex - 1)); setIsFlipped(false); }}
                        disabled={currentCardIndex === 0}
                        className="p-3 rounded-full bg-white shadow-md hover:bg-slate-50 disabled:opacity-50 text-slate-600"
                    >
                        &larr; Prev
                    </button>
                    <span className="font-bold text-slate-500">{currentCardIndex + 1} / {flashcards.length}</span>
                    <button 
                        onClick={() => { setCurrentCardIndex(Math.min(flashcards.length - 1, currentCardIndex + 1)); setIsFlipped(false); }}
                        disabled={currentCardIndex === flashcards.length - 1}
                        className="p-3 rounded-full bg-white shadow-md hover:bg-slate-50 disabled:opacity-50 text-slate-600"
                    >
                        Next &rarr;
                    </button>
                </div>
             </div>
        </div>
    );
  }

  if (mode === 'QUIZ') {
    return (
        <div className="flex flex-col h-full bg-slate-50">
             <header className="p-6 flex justify-between items-center bg-white shadow-sm z-10">
                <h3 className="font-bold text-lg text-slate-700">{selectedNote?.title} - Quiz</h3>
                <button onClick={() => setMode('SELECT')} className="text-sm font-medium text-slate-500 hover:text-slate-800">Exit Quiz</button>
             </header>
             <div className="flex-1 overflow-y-auto p-8 max-w-3xl mx-auto w-full">
                {quizSubmitted && (
                    <div className="bg-indigo-600 text-white p-6 rounded-2xl mb-8 flex items-center justify-between shadow-lg">
                        <div>
                            <h4 className="text-2xl font-bold mb-1">Score: {calculateQuizScore()} / {quizQuestions.length}</h4>
                            <p className="text-indigo-200">Great job practicing!</p>
                        </div>
                        <button onClick={() => setMode('SELECT')} className="bg-white text-indigo-600 px-4 py-2 rounded-lg font-bold text-sm">Done</button>
                    </div>
                )}

                <div className="space-y-6 pb-20">
                    {quizQuestions.map((q, idx) => {
                        const isCorrect = answers[q.id] === q.correctAnswer;
                        const isWrong = answers[q.id] !== undefined && answers[q.id] !== q.correctAnswer;
                        
                        return (
                            <div key={q.id} className={`bg-white p-6 rounded-2xl border-2 transition-all ${quizSubmitted ? (isCorrect ? 'border-green-200 bg-green-50/30' : 'border-red-200 bg-red-50/30') : 'border-slate-100 shadow-sm'}`}>
                                <h4 className="font-bold text-lg text-slate-800 mb-4 flex gap-3">
                                    <span className="text-indigo-500">Q{idx+1}.</span>
                                    {q.question}
                                </h4>
                                <div className="space-y-3">
                                    {q.options.map((opt, optIdx) => (
                                        <button
                                            key={optIdx}
                                            disabled={quizSubmitted}
                                            onClick={() => setAnswers(prev => ({...prev, [q.id]: optIdx}))}
                                            className={`w-full text-left p-4 rounded-xl border transition-all flex justify-between items-center ${
                                                quizSubmitted
                                                    ? (optIdx === q.correctAnswer 
                                                        ? 'bg-green-100 border-green-500 text-green-800 font-medium' 
                                                        : (answers[q.id] === optIdx ? 'bg-red-100 border-red-500 text-red-800' : 'bg-white border-slate-100 opacity-60'))
                                                    : (answers[q.id] === optIdx 
                                                        ? 'bg-indigo-50 border-indigo-500 text-indigo-800 font-medium' 
                                                        : 'bg-white border-slate-200 hover:border-indigo-300 hover:bg-slate-50')
                                            }`}
                                        >
                                            <span>{opt}</span>
                                            {quizSubmitted && optIdx === q.correctAnswer && <CheckCircle className="w-5 h-5 text-green-600" />}
                                            {quizSubmitted && answers[q.id] === optIdx && optIdx !== q.correctAnswer && <XCircle className="w-5 h-5 text-red-500" />}
                                        </button>
                                    ))}
                                </div>
                                {quizSubmitted && (
                                    <div className="mt-4 p-4 bg-white/50 rounded-lg text-sm text-slate-600 border border-slate-200">
                                        <span className="font-bold">Explanation:</span> {q.explanation}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>
             </div>

             {!quizSubmitted && (
                 <div className="fixed bottom-0 left-64 right-0 p-6 bg-white border-t border-slate-200 flex justify-center z-20">
                     <button 
                        onClick={() => setQuizSubmitted(true)}
                        disabled={Object.keys(answers).length < quizQuestions.length}
                        className="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 disabled:opacity-50 transition-all"
                     >
                        Submit Quiz
                     </button>
                 </div>
             )}
        </div>
    );
  }

  return null;
};

export default StudyMode;
