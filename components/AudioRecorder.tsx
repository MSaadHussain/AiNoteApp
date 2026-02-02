import React, { useState, useRef, useEffect } from 'react';
import { Mic, Square, Upload, FileAudio, Loader2, PlayCircle, AlertCircle, FileText, PenTool } from 'lucide-react';
import { transcribeAudio, organizeNote, processPdf, splitPdf } from '../services/geminiService';
import { Note, NoteSection, SubjectRegister } from '../types';

interface AudioRecorderProps {
  onNoteCreated: (note: Note | Note[]) => void;
  onOpenNotepad: (subject?: string) => void;
  registers: SubjectRegister[];
  preSelectedSubject?: string | null;
}

const AudioRecorder: React.FC<AudioRecorderProps> = ({ onNoteCreated, onOpenNotepad, registers, preSelectedSubject }) => {
  const [mode, setMode] = useState<'SELECT' | 'RECORD' | 'UPLOAD_AUDIO' | 'UPLOAD_PDF'>('SELECT');
  const [selectedSubject, setSelectedSubject] = useState(preSelectedSubject || '');
  
  // Recording State
  const [isRecording, setIsRecording] = useState(false);
  const [recordingTime, setRecordingTime] = useState(0);
  const [audioBlob, setAudioBlob] = useState<Blob | null>(null);
  
  // PDF State
  const [pdfBlob, setPdfBlob] = useState<Blob | null>(null);

  const [status, setStatus] = useState<'idle' | 'recording' | 'processing' | 'organizing' | 'done' | 'error'>('idle');
  const [statusMessage, setStatusMessage] = useState<string>('');
  const [errorMessage, setErrorMessage] = useState('');
  
  const mediaRecorderRef = useRef<MediaRecorder | null>(null);
  const chunksRef = useRef<Blob[]>([]);
  const timerRef = useRef<number | null>(null);
  const audioInputRef = useRef<HTMLInputElement>(null);
  const pdfInputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    return () => {
      if (timerRef.current) clearInterval(timerRef.current);
    };
  }, []);

  const formatTime = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  const startRecording = async () => {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      const mediaRecorder = new MediaRecorder(stream);
      mediaRecorderRef.current = mediaRecorder;
      chunksRef.current = [];

      mediaRecorder.ondataavailable = (e) => {
        if (e.data.size > 0) chunksRef.current.push(e.data);
      };

      mediaRecorder.onstop = () => {
        const blob = new Blob(chunksRef.current, { type: 'audio/webm' });
        setAudioBlob(blob);
        stream.getTracks().forEach(track => track.stop());
      };

      mediaRecorder.start();
      setIsRecording(true);
      setStatus('recording');
      setMode('RECORD');
      
      timerRef.current = window.setInterval(() => {
        setRecordingTime(prev => prev + 1);
      }, 1000);
    } catch (err) {
      console.error(err);
      setErrorMessage("Microphone access denied or not available.");
      setStatus('error');
    }
  };

  const stopRecording = () => {
    if (mediaRecorderRef.current && isRecording) {
      mediaRecorderRef.current.stop();
      setIsRecording(false);
      if (timerRef.current) clearInterval(timerRef.current);
    }
  };

  const handleAudioUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      setAudioBlob(e.target.files[0]);
      setStatus('idle');
      setMode('UPLOAD_AUDIO');
    }
  };

  const handlePdfUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      setPdfBlob(e.target.files[0]);
      setStatus('idle');
      setMode('UPLOAD_PDF');
    }
  };

  const processContent = async () => {
    try {
      setStatus('processing');
      setErrorMessage('');

      // AUDIO PROCESSING FLOW
      if (mode === 'RECORD' || mode === 'UPLOAD_AUDIO') {
        if (!audioBlob) return;
        setStatusMessage("Listening to audio...");
        const transcript = await transcribeAudio(audioBlob, audioBlob.type);
        
        setStatus('organizing');
        setStatusMessage("Writing notes...");
        const organizedData = await organizeNote(transcript);
        
        const finalSubject = (selectedSubject || organizedData.subject) || 'General';

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
            audioUrl: URL.createObjectURL(audioBlob)
        };
        
        onNoteCreated(newNote);
        setStatus('done');

      // PDF PROCESSING FLOW
      } else if (mode === 'UPLOAD_PDF') {
        if (!pdfBlob) return;
        
        setStatus('processing');
        setStatusMessage("Analyzing document structure...");

        // 1. Split PDF into chunks (5 pages each)
        const pdfChunks = await splitPdf(pdfBlob, 5);
        const createdNotes: Note[] = [];
        
        // 2. Process each chunk
        for (let i = 0; i < pdfChunks.length; i++) {
            setStatus('organizing');
            setStatusMessage(`Processing part ${i + 1} of ${pdfChunks.length}...`);
            
            const chunk = pdfChunks[i];
            const pdfData = await processPdf(chunk);
            
            let noteTitle = pdfData.title;
            // Add (Part X) to title if it's a multi-part upload
            if (pdfChunks.length > 1) {
                noteTitle = `${noteTitle} (Part ${i + 1})`;
            }

            const finalSubject = (selectedSubject) || 'General'; // Use user selection or let dashboard group them later

            const newNote: Note = {
                id: crypto.randomUUID(),
                title: noteTitle,
                subject: finalSubject,
                date: new Date().toLocaleDateString(),
                type: 'pdf',
                summary: pdfData.summary,
                sections: pdfData.sections,
                tags: pdfData.tags,
                pdfUrl: URL.createObjectURL(chunk) // Save the specific chunk
            };
            
            createdNotes.push(newNote);
        }

        // 3. Batch create notes
        // We reverse because onNoteCreated typically prepends. 
        // Actually App.tsx prepends spread. So we want Part 1 at bottom? 
        // Usually recent first. So Part 3, Part 2, Part 1.
        // Let's pass the array in natural order (Part 1, 2, 3) and let App handle it.
        onNoteCreated(createdNotes.reverse()); // Reverse so Part 1 ends up being the "last added" (or handled by sorting in dashboard)
        setStatus('done');
      }

    } catch (err) {
      console.error(err);
      setErrorMessage("Failed to process content. Please try again.");
      setStatus('error');
    }
  };

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
                    >
                        <option value="">Auto-detect Subject</option>
                        {registers.map(reg => (
                            <option key={reg.name} value={reg.name}>{reg.name}</option>
                        ))}
                    </select>
                 </div>
             </div>

             <div className="grid grid-cols-1 md:grid-cols-3 gap-6 w-full">
                 <button onClick={startRecording} className="flex flex-col items-center p-8 bg-white border-2 border-stone-100 rounded-3xl hover:border-orange-300 hover:shadow-floating transition-all group active:scale-95">
                     <div className="p-5 bg-orange-50 text-orange-500 rounded-full mb-4 group-hover:bg-orange-100 transition-colors shadow-sm">
                         <Mic className="w-8 h-8" />
                     </div>
                     <h3 className="font-hand font-bold text-xl text-stone-800">Record Lecture</h3>
                 </button>

                 <div className="relative">
                    <input type="file" accept="application/pdf" className="hidden" ref={pdfInputRef} onChange={handlePdfUpload} />
                    <button onClick={() => pdfInputRef.current?.click()} className="w-full h-full flex flex-col items-center p-8 bg-white border-2 border-stone-100 rounded-3xl hover:border-blue-300 hover:shadow-floating transition-all group active:scale-95">
                        <div className="p-5 bg-blue-50 text-blue-500 rounded-full mb-4 group-hover:bg-blue-100 transition-colors shadow-sm">
                            <Upload className="w-8 h-8" />
                        </div>
                        <h3 className="font-hand font-bold text-xl text-stone-800">Upload PDF</h3>
                    </button>
                 </div>

                 <button onClick={() => onOpenNotepad(selectedSubject)} className="flex flex-col items-center p-8 bg-white border-2 border-stone-100 rounded-3xl hover:border-emerald-300 hover:shadow-floating transition-all group active:scale-95">
                     <div className="p-5 bg-emerald-50 text-emerald-500 rounded-full mb-4 group-hover:bg-emerald-100 transition-colors shadow-sm">
                         <PenTool className="w-8 h-8" />
                     </div>
                     <h3 className="font-hand font-bold text-xl text-stone-800">Blank Page</h3>
                 </button>
             </div>
             
             <div className="mt-8">
                 <input type="file" accept="audio/*" className="hidden" ref={audioInputRef} onChange={handleAudioUpload} />
                 <button onClick={() => audioInputRef.current?.click()} className="text-stone-400 text-sm hover:text-stone-600 font-medium">
                     Or upload an audio recording...
                 </button>
             </div>
        </div>
    );
  }

  // Common Processing UI for Record/Upload modes
  return (
    <div className="flex flex-col items-center justify-center h-full p-8 max-w-2xl mx-auto">
      <div className="text-center mb-8">
        <h2 className="text-3xl font-hand font-bold text-stone-800 mb-2">Processing...</h2>
        <p className="text-stone-500">
            {statusMessage || "Organizing your notes into the register."}
        </p>
      </div>

      {status === 'error' && (
        <div className="bg-red-50 text-red-600 p-4 rounded-xl mb-6 flex items-center gap-2 shadow-sm border border-red-100">
          <AlertCircle className="w-5 h-5" />
          {errorMessage}
        </div>
      )}

      {/* Dynamic Status Circle (Tactile Feel) */}
      <div className="relative mb-10 group">
        <div className={`absolute inset-0 bg-orange-400 rounded-full blur-2xl opacity-20 transition-all duration-300 ${isRecording ? 'scale-150 opacity-40 animate-pulse' : ''}`}></div>
        
        {isRecording && (
          <button 
            onClick={stopRecording}
            className="relative bg-red-500 hover:bg-red-600 text-white w-32 h-32 rounded-full flex flex-col items-center justify-center transition-all shadow-xl shadow-red-200 active:scale-95"
          >
            <Square className="w-8 h-8 mb-2 fill-current" />
            <span className="text-sm font-bold font-mono tracking-widest">{formatTime(recordingTime)}</span>
          </button>
        )}

        {/* Ready State */}
        {!isRecording && (audioBlob || pdfBlob) && status !== 'processing' && status !== 'organizing' && (
          <div className="relative bg-stone-800 text-white w-32 h-32 rounded-full flex flex-col items-center justify-center shadow-xl">
            {mode === 'UPLOAD_PDF' ? <FileText className="w-10 h-10 mb-2 text-red-300" /> : <FileAudio className="w-10 h-10 mb-2 text-green-300" />}
            <span className="text-xs text-stone-400 font-bold uppercase tracking-wider">Ready</span>
          </div>
        )}

        {(status === 'processing' || status === 'organizing') && (
           <div className="relative bg-white border-4 border-stone-100 text-orange-500 w-32 h-32 rounded-full flex flex-col items-center justify-center shadow-xl">
            <Loader2 className="w-10 h-10 mb-2 animate-spin" />
            <span className="text-xs font-bold font-hand text-center px-2">
                {status === 'processing' ? 'Thinking...' : 'Writing...'}
            </span>
          </div>
        )}
      </div>

      {/* Controls */}
      <div className="space-y-4 w-full max-w-sm">
        {(audioBlob || pdfBlob) && status !== 'processing' && status !== 'organizing' && (
          <div className="flex gap-4">
            <button 
              onClick={() => { 
                  setAudioBlob(null); 
                  setPdfBlob(null); 
                  setRecordingTime(0); 
                  setMode('SELECT');
                  setStatus('idle');
                  setStatusMessage('');
              }}
              className="flex-1 py-3 rounded-xl border-2 border-stone-200 text-stone-600 hover:bg-stone-50 font-bold transition-colors font-hand text-lg"
            >
              Discard
            </button>
            <button 
              onClick={processContent}
              className="flex-1 py-3 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-bold text-lg font-hand shadow-lg shadow-orange-200 transition-all flex justify-center items-center gap-2 active:scale-95"
            >
              <PlayCircle className="w-5 h-5" />
              Save to Desk
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default AudioRecorder;