<?php
/**
 * Recorder - choose a capture method, record a lecture, review the transcript.
 */
$selectedSub = $preSelectedSubject ?? '';
$registerList = $registers ?? [];
?>

<script src="<?= \NoteNest\Utils\Asset::url('/assets/js/speech.js') ?>" defer></script>

<div class="h-full overflow-y-auto scrollbar-slim">

  <!-- One h1 for the page; each step below is a section heading, so the
       outline stays valid whichever step is on screen. -->
  <h1 class="sr-only">New note</h1>

  <!-- ==================== Step 1: choose a method ==================== -->
  <section id="recorder-mode-select" class="mx-auto max-w-3xl px-4 pb-24 pt-8 sm:px-6 md:pb-12">
    <header class="mb-8">
      <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">New note</h2>
      <p class="mt-1.5 text-sm text-content-muted">
        Capture a lecture, import a document, or start from a blank page.
      </p>
    </header>

    <!-- Subject picker -->
    <div class="mb-8 max-w-sm">
      <label for="recorder-subject" class="field-label">Subject</label>
      <select id="recorder-subject" class="input" aria-describedby="recorder-subject-hint">
        <option value="General" <?= $selectedSub === '' ? 'selected' : '' ?>>General</option>
        <?php foreach ($registerList as $reg): ?>
          <option value="<?= htmlspecialchars($reg->name) ?>" <?= $selectedSub === $reg->name ? 'selected' : '' ?>>
            <?= htmlspecialchars($reg->name) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <p id="recorder-subject-hint" class="field-hint">Your note will be filed under this subject.</p>
    </div>

    <h3 class="section-label mb-4">How do you want to capture it?</h3>

    <ul class="grid grid-cols-1 gap-4 sm:grid-cols-3">
      <li>
        <button type="button"
                id="btn-record-lecture"
                onclick="startLectureRecording()"
                class="card-interactive flex h-full w-full flex-col items-start gap-3 p-5 text-left">
          <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
            <i data-lucide="mic" class="h-5 w-5" aria-hidden="true"></i>
          </span>
          <span class="block">
            <span class="block text-sm font-bold">Record a lecture</span>
            <span class="mt-1 block text-2xs font-medium normal-case tracking-normal leading-relaxed text-content-muted">
              Live speech-to-text, structured by AI when you save.
            </span>
          </span>
        </button>
      </li>

      <li>
        <input type="file" id="pdf-file-input" accept="application/pdf" class="sr-only"
               onchange="uploadLecturePdf(this)">
        <button type="button"
                onclick="document.getElementById('pdf-file-input').click()"
                class="card-interactive flex h-full w-full flex-col items-start gap-3 p-5 text-left">
          <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-info-soft text-info">
            <i data-lucide="file-up" class="h-5 w-5" aria-hidden="true"></i>
          </span>
          <span class="block">
            <span class="block text-sm font-bold">Upload a PDF</span>
            <span class="mt-1 block text-2xs font-medium normal-case tracking-normal leading-relaxed text-content-muted">
              Extract the text and read it side by side with an AI tutor.
            </span>
          </span>
        </button>
      </li>

      <li>
        <button type="button"
                onclick="openBlankNotepad()"
                class="card-interactive flex h-full w-full flex-col items-start gap-3 p-5 text-left">
          <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-success-soft text-success">
            <i data-lucide="pen-line" class="h-5 w-5" aria-hidden="true"></i>
          </span>
          <span class="block">
            <span class="block text-sm font-bold">Blank page</span>
            <span class="mt-1 block text-2xs font-medium normal-case tracking-normal leading-relaxed text-content-muted">
              Type notes with inline AI answers as you write.
            </span>
          </span>
        </button>
      </li>
    </ul>

    <!--
      Browser support notice. Web Speech API recognition ships in Chromium
      browsers only; saying so up front beats a dead button.
    -->
    <p id="speech-unsupported" hidden
       class="mt-6 flex items-start gap-2.5 rounded-card border border-warning/30 bg-warning-soft p-4 text-sm text-content">
      <i data-lucide="triangle-alert" class="mt-0.5 h-4 w-4 flex-shrink-0 text-warning" aria-hidden="true"></i>
      <span>
        <strong class="font-semibold">Live recording is not available in this browser.</strong>
        Speech-to-text needs Chrome, Edge, or another Chromium browser. You can still upload a PDF or write notes.
      </span>
    </p>
  </section>

  <!-- ==================== Step 2: recording ==================== -->
  <section id="recorder-mode-record" hidden class="flex h-full flex-col">
    <header class="flex flex-shrink-0 flex-wrap items-center justify-between gap-3 border-b border-line bg-surface px-4 py-3">
      <h2 class="flex items-center gap-2.5 text-sm font-bold">
        <span class="relative flex h-2.5 w-2.5" aria-hidden="true">
          <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-danger opacity-60"></span>
          <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-danger"></span>
        </span>
        Recording
        <span id="recording-timer" class="font-mono text-2xs font-semibold tabular-nums tracking-normal text-content-muted"
              role="timer" aria-live="off">0:00</span>
      </h2>

      <button type="button" onclick="stopLectureRecording()" class="btn-danger">
        <i data-lucide="square" class="h-4 w-4 fill-current" aria-hidden="true"></i>
        Stop recording
      </button>
    </header>

    <div class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-6">
      <div class="mx-auto max-w-3xl">
        <h3 class="section-label mb-3">
          <i data-lucide="captions" class="h-3.5 w-3.5" aria-hidden="true"></i>
          Live transcript
        </h3>
        <div class="card p-5 sm:p-8">
          <div id="live-transcript-box"
               class="prose-note min-h-[12rem] whitespace-pre-wrap"
               role="log"
               aria-live="polite"
               aria-label="Live transcript">
            <span class="font-sans text-sm italic text-content-subtle">
              Listening… your words will appear here as you speak.
            </span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ==================== Step 3: review ==================== -->
  <section id="recorder-mode-review" hidden class="flex h-full flex-col">
    <header class="flex flex-shrink-0 flex-wrap items-center justify-between gap-3 border-b border-line bg-surface px-4 py-3">
      <div class="flex min-w-0 items-center gap-2.5">
        <h2 class="text-sm font-bold">Review transcript</h2>
        <span id="review-stats" class="badge-neutral"></span>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <button type="button" onclick="discardRecording()" class="btn-ghost">
          <i data-lucide="trash-2" class="h-4 w-4" aria-hidden="true"></i>
          Discard
        </button>
        <button type="button" id="btn-summarize-ai" onclick="summarizeRecording(this)" class="btn-secondary">
          <i data-lucide="sparkles" class="h-4 w-4" aria-hidden="true"></i>
          Summarise
        </button>
        <button type="button" id="btn-save-recording" onclick="saveRecordedNote(this)" class="btn-primary">
          <i data-lucide="check" class="h-4 w-4" aria-hidden="true"></i>
          Save note
        </button>
      </div>
    </header>

    <div class="min-h-0 flex-1 overflow-y-auto p-4 pb-24 sm:p-6 md:pb-6">
      <div class="mx-auto max-w-3xl space-y-6">
        <section aria-labelledby="transcript-heading">
          <h3 id="transcript-heading" class="section-label mb-3">
            <i data-lucide="captions" class="h-3.5 w-3.5" aria-hidden="true"></i>
            Transcript
          </h3>
          <div class="card p-5 sm:p-8">
            <div id="review-transcript-box" class="prose-note whitespace-pre-wrap"></div>
          </div>
        </section>

        <section id="review-summary-container" hidden aria-labelledby="summary-heading">
          <h3 id="summary-heading" class="section-label mb-3 text-brand-700">
            <i data-lucide="sparkles" class="h-3.5 w-3.5" aria-hidden="true"></i>
            AI summary
          </h3>
          <div class="rounded-card border border-brand-200 bg-brand-50 p-5 sm:p-8">
            <div id="review-summary-content" class="whitespace-pre-wrap text-sm leading-relaxed text-content"></div>
          </div>
        </section>
      </div>
    </div>
  </section>
</div>

<script>
  let recorder = null;
  let transcriptText = '';
  let summaryText = '';

  const panes = {
    select: document.getElementById('recorder-mode-select'),
    record: document.getElementById('recorder-mode-record'),
    review: document.getElementById('recorder-mode-review'),
  };

  function showPane(name) {
    Object.entries(panes).forEach(([key, el]) => { el.hidden = key !== name; });
  }

  function getSelectedSubject() {
    return document.getElementById('recorder-subject').value || 'General';
  }

  document.addEventListener('DOMContentLoaded', () => {
    // Feature-detect before offering the control, not after it fails.
    const supported = 'webkitSpeechRecognition' in window || 'SpeechRecognition' in window;
    if (!supported) {
      const btn = document.getElementById('btn-record-lecture');
      btn.disabled = true;
      btn.classList.add('cursor-not-allowed', 'opacity-60');
      btn.setAttribute('aria-describedby', 'speech-unsupported');
      document.getElementById('speech-unsupported').hidden = false;
      return;
    }

    recorder = new SpeechRecorder({
      onTranscriptChange: (finalText, interimText) => {
        transcriptText = finalText;
        const box = document.getElementById('live-transcript-box');
        if (!box) return;
        box.innerHTML =
          NoteNest.escapeHtml(finalText) +
          (interimText
            ? ' <span class="text-content-subtle italic">' + NoteNest.escapeHtml(interimText) + '</span>'
            : '');
      },
      onTimerTick: () => {
        const timer = document.getElementById('recording-timer');
        if (timer) timer.textContent = recorder.getFormattedTime();
      },
    });
  });

  function startLectureRecording() {
    if (!recorder || !recorder.start()) {
      NoteNest.toast('Could not start recording', 'error', 'Check that microphone access is allowed for this site.');
      return;
    }
    showPane('record');
  }

  function stopLectureRecording() {
    recorder.stop();
    const finalTranscript = recorder.getTranscript();
    transcriptText = finalTranscript;

    const words = finalTranscript.split(/\s+/).filter(Boolean).length;
    document.getElementById('review-stats').textContent =
      words + (words === 1 ? ' word' : ' words') + ' · ' + recorder.getFormattedTime();

    const box = document.getElementById('review-transcript-box');
    if (finalTranscript) {
      box.textContent = finalTranscript;
    } else {
      box.innerHTML =
        '<p class="font-sans text-sm text-content-muted">No speech was captured. ' +
        'Check your microphone and try recording again.</p>';
    }

    showPane('review');
  }

  async function summarizeRecording(button) {
    const text = transcriptText.trim();
    if (!text) {
      NoteNest.toast('Nothing to summarise', 'error', 'Record some audio first.');
      return;
    }

    const container = document.getElementById('review-summary-container');
    const target = document.getElementById('review-summary-content');

    // Reserve the space and show a skeleton so the page does not jump.
    container.hidden = false;
    target.innerHTML =
      '<div class="space-y-2.5"><div class="skeleton h-3.5 w-full"></div>' +
      '<div class="skeleton h-3.5 w-11/12"></div><div class="skeleton h-3.5 w-4/5"></div>' +
      '<div class="skeleton h-3.5 w-2/3"></div></div>';

    await NoteNest.withBusy(button, 'Summarising…', async () => {
      try {
        const data = await NoteNest.api('/api/ai/answer', {
          method: 'POST',
          body: {
            context: text.slice(0, 6000),
            question: 'Summarise this lecture transcript with key points and main takeaways in bullet points.',
          },
        });
        summaryText = data.answer || '';
        target.textContent = summaryText || 'No summary was returned.';
      } catch (e) {
        container.hidden = true;
        NoteNest.toast('Could not summarise', 'error', e.message);
      }
    });
  }

  async function saveRecordedNote(button) {
    const text = transcriptText.trim();
    if (!text) {
      NoteNest.toast('Nothing to save', 'error', 'No transcript was captured.');
      return;
    }

    await NoteNest.withBusy(button, 'Saving…', async () => {
      const pending = NoteNest.toast('Structuring your lecture', 'loading', 'Organising into sections with AI…');
      try {
        const organised = (await NoteNest.api('/api/ai/organize', {
          method: 'POST',
          body: { transcript: text },
        })).data || {};

        const saved = await NoteNest.api('/api/notes', {
          method: 'POST',
          body: {
            title: organised.title || 'Lecture note',
            subject: getSelectedSubject() || organised.subject || 'General',
            date: new Date().toLocaleDateString(),
            type: 'audio',
            originalTranscript: text,
            summary: summaryText || organised.summary || text.slice(0, 150) + '…',
            sections: organised.sections || [],
            tags: organised.tags || ['lecture'],
            rawContent: text,
          },
        });

        pending.remove();
        window.location.href = '/note/' + saved.note.id;
      } catch (e) {
        pending.remove();
        NoteNest.toast('Could not save note', 'error', e.message);
      }
    });
  }

  async function discardRecording() {
    const ok = await NoteNest.confirm({
      title: 'Discard this recording?',
      body: 'The transcript has not been saved and cannot be recovered.',
      confirmLabel: 'Discard',
    });
    if (ok) window.location.href = '/';
  }

  function openBlankNotepad() {
    window.location.href = '/notepad?subject=' + encodeURIComponent(getSelectedSubject());
  }

  async function uploadLecturePdf(input) {
    const file = input.files && input.files[0];
    if (!file) return;

    // 25 MB ceiling keeps the request inside typical PHP upload limits.
    if (file.size > 25 * 1024 * 1024) {
      NoteNest.toast('File too large', 'error', 'Please choose a PDF under 25 MB.');
      input.value = '';
      return;
    }

    const pending = NoteNest.toast('Reading your PDF', 'loading', 'Extracting text from the document…');
    const formData = new FormData();
    formData.append('pdf', file);
    formData.append('subject', getSelectedSubject());

    try {
      const data = await NoteNest.api('/api/ai/pdf', { method: 'POST', body: formData });
      const saved = await NoteNest.api('/api/notes', {
        method: 'POST',
        body: {
          title: file.name.replace(/\.pdf$/i, ''),
          subject: getSelectedSubject(),
          date: new Date().toLocaleDateString(),
          type: 'pdf',
          rawContent: data.data.rawContent,
          summary: data.data.summary,
          sections: data.data.sections || [],
          tags: data.data.tags || ['pdf'],
        },
      });

      pending.remove();
      window.location.href = '/pdf/' + saved.note.id;
    } catch (e) {
      pending.remove();
      NoteNest.toast('Could not read that PDF', 'error', e.message);
    } finally {
      input.value = '';
    }
  }
</script>
