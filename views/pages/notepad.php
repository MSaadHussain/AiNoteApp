<?php
/**
 * Notepad - distraction-light writing surface with inline AI answering.
 */
$existingNote = $note ?? null;
$noteSubject = $subject ?? 'General';
?>

<div class="flex h-full flex-col">

  <!-- The visible title is an editable input, so the page still needs a real
       heading for the document outline. -->
  <h1 class="sr-only"><?= $existingNote ? 'Editing: ' . htmlspecialchars($existingNote->title) : 'New note' ?></h1>

  <!-- ============ Toolbar ============ -->
  <header class="z-20 flex flex-shrink-0 flex-wrap items-center justify-between gap-2 border-b border-line bg-surface/90 px-3 py-2.5 backdrop-blur sm:px-4">
    <div class="flex min-w-0 flex-1 items-center gap-2">
      <a href="/" class="btn-icon flex-shrink-0" aria-label="Back to all notes">
        <i data-lucide="arrow-left" class="h-5 w-5" aria-hidden="true"></i>
      </a>

      <div class="min-w-0 flex-1">
        <label for="notepad-title" class="sr-only">Note title</label>
        <input id="notepad-title"
               type="text"
               value="<?= htmlspecialchars($existingNote ? $existingNote->title : '') ?>"
               placeholder="Untitled note"
               maxlength="180"
               class="w-full max-w-md truncate rounded-control border border-transparent bg-transparent px-2 py-1.5 text-base font-bold text-content transition-colors placeholder:font-medium placeholder:text-content-subtle hover:border-line focus:border-brand-500 focus:bg-surface focus:outline-none focus:ring-2 focus:ring-brand-200">
      </div>

      <span class="badge-neutral hidden flex-shrink-0 sm:inline-flex"><?= htmlspecialchars($noteSubject) ?></span>
    </div>

    <div class="flex flex-shrink-0 items-center gap-2">
      <!-- Draft status. aria-live so the save state is announced, not just seen. -->
      <p id="draft-status"
         class="hidden items-center gap-1.5 text-2xs font-semibold normal-case tracking-normal text-content-subtle sm:flex"
         role="status"
         aria-live="polite"></p>

      <div id="ai-thinking"
           hidden
           class="items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1.5 text-2xs font-bold uppercase tracking-wider text-brand-700 sm:flex"
           role="status"
           aria-live="polite">
        <i data-lucide="loader-circle" class="h-3.5 w-3.5 animate-spin" aria-hidden="true"></i>
        Answering
      </div>

      <button type="button" id="notepad-save" class="btn-primary" onclick="saveNotepad(this)">
        <i data-lucide="check" class="h-4 w-4" aria-hidden="true"></i>
        Save
      </button>
    </div>
  </header>

  <!-- ============ Writing surface ============ -->
  <div class="min-h-0 flex-1 overflow-y-auto bg-surface-sunken px-0 py-0 sm:px-6 sm:py-6">
    <div class="mx-auto flex h-full max-w-3xl flex-col overflow-hidden border-line bg-surface-paper shadow-card sm:h-auto sm:min-h-full sm:rounded-card sm:border">
      <label for="notepad-textarea" class="sr-only">Note content</label>
      <textarea id="notepad-textarea"
                class="paper-sheet w-full flex-1 resize-none bg-transparent px-5 py-4 font-reading text-lg text-content placeholder:font-sans placeholder:text-base placeholder:leading-normal placeholder:text-content-subtle focus:outline-none sm:px-10 sm:py-8"
                placeholder="Start writing…&#10;&#10;Tip: end a question with ? and AI will answer it inline."
                spellcheck="true"
                aria-describedby="notepad-help"><?= htmlspecialchars($existingNote ? ($existingNote->rawContent ?: $existingNote->summary) : '') ?></textarea>
    </div>

    <div class="mx-auto max-w-3xl px-4 pb-24 pt-3 sm:px-0 md:pb-6">
      <p id="notepad-help" class="flex flex-wrap items-center gap-x-3 gap-y-1 text-2xs font-medium normal-case tracking-normal text-content-subtle">
        <span class="flex items-center gap-1.5">
          <i data-lucide="sparkles" class="h-3.5 w-3.5" aria-hidden="true"></i>
          Type a question ending in <kbd class="rounded border border-line bg-surface px-1 font-sans font-bold">?</kbd> for an inline answer
        </span>
        <span class="flex items-center gap-1.5">
          <i data-lucide="save" class="h-3.5 w-3.5" aria-hidden="true"></i>
          Drafts autosave locally &middot; <kbd class="rounded border border-line bg-surface px-1 font-sans font-bold">Ctrl</kbd>+<kbd class="rounded border border-line bg-surface px-1 font-sans font-bold">S</kbd> to save
        </span>
        <span id="word-count" class="tabular-nums"></span>
      </p>
    </div>
  </div>
</div>

<script>
  const noteId = <?= json_encode($existingNote ? $existingNote->id : '') ?>;
  const noteSubject = <?= json_encode($noteSubject) ?>;
  const DRAFT_KEY = 'notenest_draft_' + (noteId || 'new');

  const textarea = document.getElementById('notepad-textarea');
  const titleInput = document.getElementById('notepad-title');
  const draftStatus = document.getElementById('draft-status');
  const thinking = document.getElementById('ai-thinking');
  const wordCount = document.getElementById('word-count');

  let dirty = false;

  /* ---- Word count ---------------------------------------------------- */
  function updateWordCount() {
    const words = textarea.value.trim().split(/\s+/).filter(Boolean).length;
    wordCount.textContent = words + (words === 1 ? ' word' : ' words');
  }

  /* ---- Drafts -------------------------------------------------------- */
  function restoreDraft() {
    if (noteId) return;
    try {
      const saved = JSON.parse(localStorage.getItem(DRAFT_KEY) || 'null');
      if (!saved) return;
      if (saved.content) textarea.value = saved.content;
      if (saved.title) titleInput.value = saved.title;
      setDraftStatus('Restored unsaved draft');
    } catch (e) {
      localStorage.removeItem(DRAFT_KEY);
    }
  }

  function setDraftStatus(message) {
    draftStatus.textContent = message;
    draftStatus.classList.remove('hidden');
    draftStatus.classList.add('sm:flex');
  }

  function persistDraft() {
    if (!dirty || !textarea.value.trim()) return;
    localStorage.setItem(DRAFT_KEY, JSON.stringify({
      title: titleInput.value,
      content: textarea.value,
      time: Date.now(),
    }));
    dirty = false;
    setDraftStatus('Draft saved ' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
  }

  [textarea, titleInput].forEach((el) =>
    el.addEventListener('input', () => {
      dirty = true;
      if (el === textarea) updateWordCount();
    })
  );

  window.setInterval(persistDraft, 10000);
  window.addEventListener('beforeunload', (event) => {
    persistDraft();
    if (dirty) {
      event.preventDefault();
      event.returnValue = '';
    }
  });

  /* ---- Inline AI answering ------------------------------------------- */
  // Fires when the user types "?" at the end of a question.
  textarea.addEventListener('keydown', async (event) => {
    if (event.key !== '?') return;

    const pos = textarea.selectionStart;
    const before = textarea.value.slice(0, pos);

    const lastBreak = Math.max(
      before.lastIndexOf('.'), before.lastIndexOf('!'),
      before.lastIndexOf('?'), before.lastIndexOf('\n')
    );
    const question = before.slice(lastBreak + 1).trim();
    if (question.length < 3) return;   // let the "?" type normally

    event.preventDefault();

    // Insert the "?" the user pressed, then append the answer after it.
    const after = textarea.value.slice(pos);
    textarea.value = before + '?' + after;
    textarea.selectionStart = textarea.selectionEnd = pos + 1;
    dirty = true;
    updateWordCount();

    thinking.hidden = false;
    try {
      const data = await NoteNest.api('/api/ai/answer', {
        method: 'POST',
        body: { context: textarea.value.slice(0, 6000), question: question + '?' },
      });

      if (data.answer) {
        const insertAt = textarea.selectionStart;
        const block = '\n\nAI: ' + data.answer + '\n\n';
        textarea.value = textarea.value.slice(0, insertAt) + block + textarea.value.slice(insertAt);
        textarea.selectionStart = textarea.selectionEnd = insertAt + block.length;
        textarea.focus();
        dirty = true;
        updateWordCount();
      }
    } catch (e) {
      NoteNest.toast('Could not answer that', 'error', e.message);
    } finally {
      thinking.hidden = true;
    }
  });

  /* ---- Save ---------------------------------------------------------- */
  async function saveNotepad(button) {
    const title = titleInput.value.trim() || 'Untitled note';
    const content = textarea.value.trim();

    if (!content) {
      NoteNest.toast('Nothing to save', 'error', 'Write something before saving this note.');
      textarea.focus();
      return;
    }

    await NoteNest.withBusy(button, 'Saving…', async () => {
      try {
        const data = await NoteNest.api('/api/notes', {
          method: 'POST',
          body: {
            id: noteId || undefined,
            title,
            subject: noteSubject,
            date: new Date().toLocaleDateString(),
            type: 'text',
            rawContent: content,
            summary: content.slice(0, 150) + (content.length > 150 ? '…' : ''),
            sections: [],
            tags: ['notepad'],
          },
        });

        localStorage.removeItem(DRAFT_KEY);
        dirty = false;
        window.location.href = '/note/' + data.note.id;
      } catch (e) {
        NoteNest.toast('Could not save note', 'error', e.message);
      }
    });
  }

  document.addEventListener('keydown', (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
      event.preventDefault();
      saveNotepad(document.getElementById('notepad-save'));
    }
  });

  restoreDraft();
  updateWordCount();
</script>
