import React, { useState, useRef, useEffect } from 'react';
import { Mic, Square, Upload, Loader2, FileText, PenTool, Save, X, Sparkles } from 'lucide-react';
import { Note, SubjectRegister } from '../types';
import { getQuickAnswer } from '../services/geminiService';

interface AudioRecorderProps {
  onNoteCreated: (note: Note | Note[]) => void;
  onOpenNotepad: (subject?: string) => void;
  registers: SubjectRegister[];
  preSelectedSubject?: string | null;
  onProcessAudio: (blob: Blob, subject?: string) => void;
  onProcessPdf: (blob: Blob, subject?: string) => void;
}

const AudioRecorder: React.FC<AudioRecorderProps> = ({ onNoteCreated, onOpenNotepad, registers, preSelectedSubject, onProcessPdf }) => {
  const [mode, setMode] = useState<'SELECT' | 'RECORD' | 'REVIEW'>('SELECT');
  const [selectedSubject, setSelectedSubject] = useState(preSelectedSubject || '');

  // Recording State
  const [isRecording, setIsRecording] = useState(false);
  const [recordingTime, setRecordingTime] = useState(0);

  // Transcription State
  const [liveTranscript, setLiveTranscript] = useState('');
  const [interimText, setInterimText] = useState('');
  const [isSummarizing, setIsSummarizing] = useState(false);
  const [summary, setSummary] = useState('');

  // PDF State
  const pdfInputRef = useRef<HTMLInputElement>(null);

  const recognitionRef = useRef<any>(null);
  const timerRef = useRef<number | null>(null);
  const transcriptRef = useRef('');

  useEffect(() => {
    return () => {
      if (timerRef.current) clearInterval(timerRef.current);
      if (recognitionRef.current) {
        try { recognitionRef.current.stop(); } catch { }
      }
    };
  }, []);

  const formatTime = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  const startRecording = async () => {
    // Check for Web Speech API support
    const SpeechRecognition = (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition;
    if (!SpeechRecognition) {
      alert('Speech recognition is not supported in this browser. Please use Chrome or Edge.');
      return;
    }

    const recognition = new SpeechRecognition();
    recognition.continuous = true;
    recognition.interimResults = true;
    recognition.lang = 'en-US';

    recognition.onresult = (event: any) => {
      let interim = '';
      let final = '';
      for (let i = event.resultIndex; i < event.results.length; i++) {
        const transcript = event.results[i][0].transcript;
        if (event.results[i].isFinal) {
          final += transcript + ' ';
        } else {
          interim = transcript;
        }
      }
      if (final) {
        transcriptRef.current += final;
        setLiveTranscript(transcriptRef.current);
      }
      setInterimText(interim);
    };

    recognition.onerror = (event: any) => {
      console.error('Speech recognition error:', event.error);
      if (event.error === 'no-speech') {
        // Restart silently on no-speech
        try { recognition.start(); } catch { }
      }
    };

    recognition.onend = () => {
      // Auto-restart if still recording (browser stops after silence)
      if (isRecordingRef.current) {
        try { recognition.start(); } catch { }
      }
    };

    recognitionRef.current = recognition;
    recognition.start();

    setIsRecording(true);
    isRecordingRef.current = true;
    setMode('RECORD');
    setLiveTranscript('');
    setInterimText('');
    transcriptRef.current = '';
    setSummary('');

    timerRef.current = window.setInterval(() => {
      setRecordingTime(prev => prev + 1);
    }, 1000);
  };

  const isRecordingRef = useRef(false);

  const stopRecording = () => {
    setIsRecording(false);
    isRecordingRef.current = false;
    if (timerRef.current) clearInterval(timerRef.current);
    if (recognitionRef.current) {
      try { recognitionRef.current.stop(); } catch { }
    }
    setInterimText('');
    setMode('REVIEW');
  };

  const handleSummarize = async () => {
    const text = transcriptRef.current.trim();
    if (!text || isSummarizing) return;
    setIsSummarizing(true);
    try {
      const result = await getQuickAnswer(
        text.substring(0, 6000),
        'Summarize this lecture transcript. Provide a clear, organized summary with key points, main topics covered, and important takeaways. Use bullet points where helpful.'
      );
      setSummary(result || 'Could not generate summary.');
    } catch {
      setSummary('Error generating summary.');
    } finally {
      setIsSummarizing(false);
    }
  };

  const handleSaveNote = () => {
    const text = transcriptRef.current.trim();
    if (!text) return;

    const subject = selectedSubject || 'General';
    const title = text.substring(0, 60).replace(/\s+/g, ' ').trim() + (text.length > 60 ? '...' : '');

    const newNote: Note = {
      id: crypto.randomUUID(),
      title: `Lecture: ${title}`,
      subject,
      date: new Date().toLocaleDateString(),
      type: 'audio',
      originalTranscript: text,
      summary: summary || text.substring(0, 200) + '...',
      sections: [{
        heading: 'Lecture Transcript',
        content: text,
        type: 'theory' as const,
      }],
      tags: ['lecture', 'transcription'],
      rawContent: text + (summary ? '\n\n--- AI Summary ---\n\n' + summary : ''),
    };

    onNoteCreated(newNote);
    // Reset
    setMode('SELECT');
    setLiveTranscript('');
    setInterimText('');
    transcriptRef.current = '';
    setRecordingTime(0);
    setSummary('');
  };

  const handlePdfUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      onProcessPdf(e.target.files[0], selectedSubject);
    }
  };

  // ===== SELECT MODE =====
  if (mode === 'SELECT') {
    return (
      <div className="flex flex-col items-center justify-center h-full p-8 max-w-4xl mx-auto animate-fade-in">
        <div className="text-center mb-10">
          <h2 className="text-4xl font-hand font-bold text-stone-800 mb-2">New Entry</h2>
          <p className="text-stone-500 font-sans">What are we learning today?</p>
        </div>

        <div className="w-full max-w-md mb-8">
          <label className="block text-xs font-bold uppercase text-stone-400 mb-2 tracking-wider">Select Register</label>
          <div className="relative">
            <select
              value={selectedSubject}
              onChange={(e) => setSelectedSubject(e.target.value)}
              className="block w-full pl-4 pr-10 py-3 text-base border-stone-200 focus:outline-none focus:ring-orange-200 focus:border-orange-300 sm:text-sm rounded-xl border bg-white shadow-sm font-hand text-lg"
              required
            >
              <option value="" disabled>— Select a Subject —</option>
              {registers.map(reg => (
                <option key={reg.name} value={reg.name}>{reg.name}</option>
              ))}
            </select>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 w-full">
          <button onClick={startRecording} disabled={!selectedSubject} className={`flex flex-col items-center p-8 bg-white border-2 border-stone-100 rounded-3xl transition-all group active:scale-95 ${!selectedSubject ? 'opacity-40 cursor-not-allowed' : 'hover:border-orange-300 hover:shadow-floating'}`}>
            <div className="p-5 bg-orange-50 text-orange-500 rounded-full mb-4 group-hover:bg-orange-100 transition-colors shadow-sm">
              <Mic className="w-8 h-8" />
            </div>
            <h3 className="font-hand font-bold text-xl text-stone-800">Record Lecture</h3>
            <p className="text-xs text-stone-400 mt-1">Live speech-to-text</p>
          </button>

          <div className="relative">
            <input type="file" accept="application/pdf" className="hidden" ref={pdfInputRef} onChange={handlePdfUpload} />
            <button onClick={() => pdfInputRef.current?.click()} disabled={!selectedSubject} className={`w-full h-full flex flex-col items-center p-8 bg-white border-2 border-stone-100 rounded-3xl transition-all group active:scale-95 ${!selectedSubject ? 'opacity-40 cursor-not-allowed' : 'hover:border-blue-300 hover:shadow-floating'}`}>
              <div className="p-5 bg-blue-50 text-blue-500 rounded-full mb-4 group-hover:bg-blue-100 transition-colors shadow-sm">
                <Upload className="w-8 h-8" />
              </div>
              <h3 className="font-hand font-bold text-xl text-stone-800">Upload PDF</h3>
            </button>
          </div>

          <button onClick={() => onOpenNotepad(selectedSubject)} disabled={!selectedSubject} className={`flex flex-col items-center p-8 bg-white border-2 border-stone-100 rounded-3xl transition-all group active:scale-95 ${!selectedSubject ? 'opacity-40 cursor-not-allowed' : 'hover:border-emerald-300 hover:shadow-floating'}`}>
            <div className="p-5 bg-emerald-50 text-emerald-500 rounded-full mb-4 group-hover:bg-emerald-100 transition-colors shadow-sm">
              <PenTool className="w-8 h-8" />
            </div>
            <h3 className="font-hand font-bold text-xl text-stone-800">Blank Page</h3>
          </button>
        </div>
      </div>
    );
  }

  // ===== RECORD MODE — Live transcription =====
  if (mode === 'RECORD') {
    return (
      <div className="flex flex-col h-full animate-fade-in">
        {/* Top bar */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-stone-200 bg-white">
          <div className="flex items-center gap-3">
            <div className="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
            <span className="font-hand font-bold text-xl text-stone-800">Recording Lecture</span>
            <span className="font-mono text-sm text-stone-500 bg-stone-100 px-2 py-1 rounded">{formatTime(recordingTime)}</span>
          </div>
          <button
            onClick={stopRecording}
            className="flex items-center gap-2 px-5 py-2.5 bg-red-500 hover:bg-red-600 text-white font-bold rounded-xl shadow-lg shadow-red-200 transition-all active:scale-95"
          >
            <Square className="w-4 h-4 fill-current" /> Stop Recording
          </button>
        </div>

        {/* Live Transcript Area */}
        <div className="flex-1 overflow-y-auto p-6 bg-stone-50">
          <div className="max-w-3xl mx-auto bg-white rounded-2xl shadow-sm border border-stone-200 p-8 min-h-[300px]">
            <h3 className="text-xs font-bold uppercase text-stone-400 tracking-wider mb-4 flex items-center gap-2">
              <Mic className="w-3.5 h-3.5" /> Live Transcript
            </h3>
            <div className="prose prose-lg prose-stone max-w-none font-serif leading-relaxed whitespace-pre-wrap">
              {liveTranscript || <span className="text-stone-300 italic">Start speaking... your words will appear here in real-time.</span>}
              {interimText && <span className="text-orange-400 italic">{interimText}</span>}
            </div>
          </div>
        </div>
      </div>
    );
  }

  // ===== REVIEW MODE — Post-recording review with summarize & save =====
  return (
    <div className="flex flex-col h-full animate-fade-in">
      {/* Top bar */}
      <div className="flex items-center justify-between px-6 py-4 border-b border-stone-200 bg-white">
        <div className="flex items-center gap-3">
          <FileText className="w-5 h-5 text-stone-500" />
          <span className="font-hand font-bold text-xl text-stone-800">Review Transcript</span>
          <span className="text-xs text-stone-400 bg-stone-100 px-2 py-1 rounded-full">
            {transcriptRef.current.split(/\s+/).filter(Boolean).length} words • {formatTime(recordingTime)}
          </span>
        </div>
        <div className="flex items-center gap-3">
          <button
            onClick={() => { setMode('SELECT'); setLiveTranscript(''); transcriptRef.current = ''; setRecordingTime(0); setSummary(''); }}
            className="flex items-center gap-2 px-4 py-2 text-stone-500 hover:bg-stone-100 rounded-xl font-bold transition-colors"
          >
            <X className="w-4 h-4" /> Discard
          </button>
          <button
            onClick={handleSummarize}
            disabled={isSummarizing}
            className="flex items-center gap-2 px-4 py-2 bg-purple-50 hover:bg-purple-100 text-purple-600 rounded-xl font-bold transition-colors border border-purple-200 disabled:opacity-50"
          >
            <Sparkles className="w-4 h-4" />
            {isSummarizing ? 'Summarizing...' : 'Summarize with AI'}
          </button>
          <button
            onClick={handleSaveNote}
            className="flex items-center gap-2 px-5 py-2 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-xl shadow-lg shadow-orange-200 transition-all active:scale-95"
          >
            <Save className="w-4 h-4" /> Save as Note
          </button>
        </div>
      </div>

      {/* Content */}
      <div className="flex-1 overflow-y-auto p-6 bg-stone-50">
        <div className="max-w-3xl mx-auto space-y-6">
          {/* Transcript */}
          <div className="bg-white rounded-2xl shadow-sm border border-stone-200 p-8">
            <h3 className="text-xs font-bold uppercase text-stone-400 tracking-wider mb-4 flex items-center gap-2">
              <Mic className="w-3.5 h-3.5" /> Transcript
            </h3>
            <div className="prose prose-lg prose-stone max-w-none font-serif leading-relaxed whitespace-pre-wrap">
              {liveTranscript || 'No transcript captured. Make sure you speak clearly and your microphone is working.'}
            </div>
          </div>

          {/* AI Summary */}
          {summary && (
            <div className="bg-purple-50 rounded-2xl shadow-sm border border-purple-200 p-8 animate-fade-in">
              <h3 className="text-xs font-bold uppercase text-purple-500 tracking-wider mb-4 flex items-center gap-2">
                <Sparkles className="w-3.5 h-3.5" /> AI Summary
              </h3>
              <div className="prose prose-stone max-w-none leading-relaxed whitespace-pre-wrap">
                {summary}
              </div>
            </div>
          )}

          {isSummarizing && (
            <div className="flex items-center justify-center gap-3 py-8 text-purple-500">
              <Loader2 className="w-5 h-5 animate-spin" />
              <span className="font-bold">DeepSeek is summarizing your lecture...</span>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default AudioRecorder;