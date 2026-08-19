<?php
/**
 * Recorder View - Lecture Voice Recording, Live Transcription, & PDF Ingestion
 */
$selectedSub = $preSelectedSubject ?? '';
?>

<!-- Include Speech Recognition Engine -->
<script src="/assets/js/speech.js"></script>

<div class="h-full flex flex-col bg-desk relative">
  
  <!-- ===== SELECT MODE ===== -->
  <div id="recorder-mode-select" class="flex flex-col items-center justify-center h-full p-8 max-w-4xl mx-auto animate-fade-in">
    <div class="text-center mb-10">
      <h2 class="text-4xl font-hand font-bold text-stone-800 mb-2">New Entry</h2>
      <p class="text-stone-500 font-sans">What are we learning today?</p>
    </div>

    <!-- Subject Register Picker -->
    <div class="w-full max-w-md mb-8">
      <label class="block text-xs font-bold uppercase text-stone-400 mb-2 tracking-wider">Select Register</label>
      <div class="relative">
        <select
          id="recorder-subject-select"
          class="block w-full pl-4 pr-10 py-3 text-base border-stone-200 focus:outline-none focus:ring-orange-200 focus:border-orange-300 rounded-xl border bg-white shadow-sm font-hand text-lg"
          onchange="updateSubjectSelection()"
        >
          <option value="" disabled <?= empty($selectedSub) ? 'selected' : '' ?>>— Select a Subject —</option>
          <?php foreach ($registers ?? [] as $reg): ?>
            <option value="<?= htmlspecialchars($reg->name) ?>" <?= $selectedSub === $reg->name ? 'selected' : '' ?>>
              <?= htmlspecialchars($reg->name) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- 3 Action Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full">
      <!-- Record Lecture -->
      <button
        id="btn-record-lecture"
        onclick="startLectureRecording()"
        class="flex flex-col items-center p-8 bg-white border-2 border-stone-100 rounded-3xl transition-all group active:scale-95 hover:border-orange-300 hover:shadow-floating"
      >
        <div class="p-5 bg-orange-50 text-orange-500 rounded-full mb-4 group-hover:bg-orange-100 transition-colors shadow-sm">
          <i data-lucide="mic" class="w-8 h-8"></i>
        </div>
        <h3 class="font-hand font-bold text-xl text-stone-800">Record Lecture</h3>
        <p class="text-xs text-stone-400 mt-1">Live speech-to-text</p>
      </button>

      <!-- Upload PDF -->
      <div class="relative">
        <input type="file" id="pdf-file-input" accept="application/pdf" class="hidden" onchange="uploadLecturePdf(this)" />
        <button
          onclick="document.getElementById('pdf-file-input').click()"
          class="w-full h-full flex flex-col items-center p-8 bg-white border-2 border-stone-100 rounded-3xl transition-all group active:scale-95 hover:border-blue-300 hover:shadow-floating"
        >
          <div class="p-5 bg-blue-50 text-blue-500 rounded-full mb-4 group-hover:bg-blue-100 transition-colors shadow-sm">
            <i data-lucide="upload" class="w-8 h-8"></i>
          </div>
          <h3 class="font-hand font-bold text-xl text-stone-800">Upload PDF</h3>
          <p class="text-xs text-stone-400 mt-1">Extract text & Smart Read</p>
        </button>
      </div>

      <!-- Blank Notepad -->
      <button
        onclick="openBlankNotepad()"
        class="flex flex-col items-center p-8 bg-white border-2 border-stone-100 rounded-3xl transition-all group active:scale-95 hover:border-emerald-300 hover:shadow-floating"
      >
        <div class="p-5 bg-emerald-50 text-emerald-500 rounded-full mb-4 group-hover:bg-emerald-100 transition-colors shadow-sm">
          <i data-lucide="pen-tool" class="w-8 h-8"></i>
        </div>
        <h3 class="font-hand font-bold text-xl text-stone-800">Blank Page</h3>
        <p class="text-xs text-stone-400 mt-1">Type notes with AI</p>
      </button>
    </div>
  </div>

  <!-- ===== RECORD MODE ===== -->
  <div id="recorder-mode-record" class="hidden flex-col h-full animate-fade-in">
    <!-- Top Bar -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-stone-200 bg-white">
      <div class="flex items-center gap-3">
        <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
        <span class="font-hand font-bold text-xl text-stone-800">Recording Lecture</span>
        <span id="recording-timer" class="font-mono text-sm text-stone-500 bg-stone-100 px-2 py-1 rounded">0:00</span>
      </div>
      <button
        onclick="stopLectureRecording()"
        class="flex items-center gap-2 px-5 py-2.5 bg-red-500 hover:bg-red-600 text-white font-bold rounded-xl shadow-lg shadow-red-200 transition-all active:scale-95"
      >
        <i data-lucide="square" class="w-4 h-4 fill-current"></i> Stop Recording
      </button>
    </div>

    <!-- Live Transcript Area -->
    <div class="flex-1 overflow-y-auto p-6 bg-stone-50">
      <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-sm border border-stone-200 p-8 min-h-[300px]">
        <h3 class="text-xs font-bold uppercase text-stone-400 tracking-wider mb-4 flex items-center gap-2">
          <i data-lucide="mic" class="w-3.5 h-3.5"></i> Live Transcript
        </h3>
        <div id="live-transcript-box" class="prose prose-lg prose-stone max-w-none font-serif leading-relaxed whitespace-pre-wrap text-stone-800">
          <span class="text-stone-300 italic">Start speaking... your words will appear here in real-time.</span>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== REVIEW MODE ===== -->
  <div id="recorder-mode-review" class="hidden flex-col h-full animate-fade-in">
    <!-- Top Bar -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-stone-200 bg-white">
      <div class="flex items-center gap-3">
        <i data-lucide="file-text" class="w-5 h-5 text-stone-500"></i>
        <span class="font-hand font-bold text-xl text-stone-800">Review Transcript</span>
        <span id="review-stats" class="text-xs text-stone-400 bg-stone-100 px-2 py-1 rounded-full">0 words</span>
      </div>

      <div class="flex items-center gap-3">
        <button
          onclick="discardRecording()"
          class="flex items-center gap-2 px-4 py-2 text-stone-500 hover:bg-stone-100 rounded-xl font-bold transition-colors"
        >
          <i data-lucide="x" class="w-4 h-4"></i> Discard
        </button>

        <button
          id="btn-summarize-ai"
          onclick="summarizeRecording()"
          class="flex items-center gap-2 px-4 py-2 bg-purple-50 hover:bg-purple-100 text-purple-600 rounded-xl font-bold transition-colors border border-purple-200"
        >
          <i data-lucide="sparkles" class="w-4 h-4"></i> Summarize with AI
        </button>

        <button
          onclick="saveRecordedNote()"
          class="flex items-center gap-2 px-5 py-2 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-xl shadow-lg shadow-orange-200 transition-all active:scale-95"
        >
          <i data-lucide="save" class="w-4 h-4"></i> Save as Note
        </button>
      </div>
    </div>

    <!-- Review Content -->
    <div class="flex-1 overflow-y-auto p-6 bg-stone-50">
      <div class="max-w-3xl mx-auto space-y-6">
        
        <!-- Transcript Box -->
        <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-8">
          <h3 class="text-xs font-bold uppercase text-stone-400 tracking-wider mb-4 flex items-center gap-2">
            <i data-lucide="mic" class="w-3.5 h-3.5"></i> Transcript
          </h3>
          <div id="review-transcript-box" class="prose prose-lg prose-stone max-w-none font-serif leading-relaxed whitespace-pre-wrap text-stone-800">
          </div>
        </div>

        <!-- AI Summary Box -->
        <div id="review-summary-container" class="hidden bg-purple-50 rounded-2xl shadow-sm border border-purple-200 p-8 animate-fade-in">
          <h3 class="text-xs font-bold uppercase text-purple-500 tracking-wider mb-4 flex items-center gap-2">
            <i data-lucide="sparkles" class="w-3.5 h-3.5"></i> AI Summary
          </h3>
          <div id="review-summary-content" class="prose prose-stone max-w-none leading-relaxed whitespace-pre-wrap text-stone-800">
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  let recorder = null;
  let currentRecordingText = '';
  let currentSummaryText = '';

  document.addEventListener('DOMContentLoaded', () => {
    recorder = new SpeechRecorder({
      onTranscriptChange: (finalText, interimText) => {
        currentRecordingText = finalText;
        const box = document.getElementById('live-transcript-box');
        if (box) {
          box.innerHTML = `${escapeHtml(finalText)} <span class="text-orange-400 italic">${escapeHtml(interimText)}</span>`;
        }
      },
      onTimerTick: (seconds) => {
        const timer = document.getElementById('recording-timer');
        if (timer) timer.textContent = recorder.getFormattedTime();
      }
    });
  });

  function getSelectedSubject() {
    const select = document.getElementById('recorder-subject-select');
    return select.value || 'General';
  }

  function startLectureRecording() {
    if (!recorder.start()) return;

    document.getElementById('recorder-mode-select').classList.add('hidden');
    document.getElementById('recorder-mode-record').classList.remove('hidden');
    document.getElementById('recorder-mode-record').classList.add('flex');
  }

  function stopLectureRecording() {
    recorder.stop();
    const finalTranscript = recorder.getTranscript();

    document.getElementById('recorder-mode-record').classList.add('hidden');
    document.getElementById('recorder-mode-record').classList.remove('flex');
    document.getElementById('recorder-mode-review').classList.remove('hidden');
    document.getElementById('recorder-mode-review').classList.add('flex');

    const wordCount = finalTranscript.split(/\s+/).filter(Boolean).length;
    document.getElementById('review-stats').textContent = `${wordCount} words • ${recorder.getFormattedTime()}`;
    document.getElementById('review-transcript-box').textContent = finalTranscript || 'No speech recorded.';
  }

  async function summarizeRecording() {
    const text = currentRecordingText.trim();
    if (!text) return;

    const btn = document.getElementById('btn-summarize-ai');
    btn.disabled = true;
    btn.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Summarizing...`;
    lucide.createIcons();

    try {
      const res = await fetch('/api/ai/answer', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          context: text.substring(0, 6000),
          question: 'Summarize this lecture transcript with key points and main takeaways in bullet points.'
        })
      });
      const data = await res.json();
      currentSummaryText = data.answer || 'Summary ready.';

      document.getElementById('review-summary-container').classList.remove('hidden');
      document.getElementById('review-summary-content').textContent = currentSummaryText;
    } catch (e) {
      alert('Error generating AI summary');
    } finally {
      btn.disabled = false;
      btn.innerHTML = `<i data-lucide="sparkles" class="w-4 h-4"></i> Summarize with AI`;
      lucide.createIcons();
    }
  }

  async function saveRecordedNote() {
    const text = currentRecordingText.trim();
    if (!text) {
      alert('No transcript to save.');
      return;
    }

    const toast = NoteNest.showToast('Organizing Lecture Notes...', 'loading', 'Structuring content with DeepSeek AI...');

    try {
      // 1. Ask AI to organize note into subject, title, summary, and sections
      const orgRes = await fetch('/api/ai/organize', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ transcript: text })
      });
      const orgData = await orgRes.json();
      const organized = orgData.data || {};

      const subject = getSelectedSubject() || organized.subject || 'General';

      // 2. Save note to SQLite DB
      const saveRes = await fetch('/api/notes', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          title: organized.title || 'Lecture Note',
          subject: subject,
          date: new Date().toLocaleDateString(),
          type: 'audio',
          originalTranscript: text,
          summary: currentSummaryText || organized.summary || text.substring(0, 150) + '...',
          sections: organized.sections || [],
          tags: organized.tags || ['lecture', 'audio'],
          rawContent: text
        })
      });

      const saved = await saveRes.json();
      toast.remove();

      if (saved.success) {
        window.location.href = '/note/' + saved.note.id;
      } else {
        alert('Failed to save note');
      }
    } catch (e) {
      toast.remove();
      alert('Error saving organized note');
    }
  }

  function discardRecording() {
    if (confirm('Discard this recording?')) {
      window.location.href = '/';
    }
  }

  function openBlankNotepad() {
    const sub = getSelectedSubject();
    window.location.href = '/notepad?subject=' + encodeURIComponent(sub);
  }

  async function uploadLecturePdf(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const sub = getSelectedSubject();

    const toast = NoteNest.showToast('Extracting PDF Text...', 'loading', 'Reading document pages...');

    const formData = new FormData();
    formData.append('pdf', file);
    formData.append('subject', sub);

    try {
      const res = await fetch('/api/ai/pdf', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      toast.remove();

      if (data.success) {
        // Save note to database
        const saveRes = await fetch('/api/notes', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            title: file.name.replace('.pdf', '') + ' (PDF)',
            subject: sub,
            date: new Date().toLocaleDateString(),
            type: 'pdf',
            rawContent: data.data.rawContent,
            summary: data.data.summary,
            sections: data.data.sections || [],
            tags: data.data.tags || ['pdf', 'document']
          })
        });
        const saved = await saveRes.json();
        if (saved.success) {
          window.location.href = '/pdf/' + saved.note.id;
        }
      }
    } catch (e) {
      toast.remove();
      alert('Error processing PDF');
    }
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
</script>
