<?php
/**
 * Notepad View - Blank Lined Paper with Instant '?' AI Integration
 */
$existingNote = $note ?? null;
?>

<div class="flex flex-col h-full bg-desk relative">
  
  <!-- Header (Toolbar) -->
  <div class="absolute top-0 left-0 right-0 h-16 bg-white/80 backdrop-blur-md border-b border-stone-200 z-10 flex items-center justify-between px-6 shadow-sm">
    <div class="flex items-center gap-4 flex-1">
      <a href="/" class="p-2 hover:bg-stone-100 rounded-full text-stone-500 transition-colors">
        <i data-lucide="arrow-left" class="w-5 h-5"></i>
      </a>
      <input
        type="text"
        id="notepad-title"
        value="<?= htmlspecialchars($existingNote ? $existingNote->title : 'Untitled Note') ?>"
        placeholder="Note Title..."
        class="text-2xl font-hand font-bold bg-transparent border-none focus:outline-none text-stone-800 placeholder-stone-400 w-full max-w-md"
      />
      <span class="hidden md:inline-block px-3 py-1 bg-stone-100 rounded-lg text-xs font-bold text-stone-500 uppercase">
        <?= htmlspecialchars($subject ?? 'General') ?>
      </span>
    </div>

    <div class="flex items-center gap-3">
      <!-- Autosave Status -->
      <div id="autosave-status" class="hidden md:flex items-center gap-1.5 text-xs text-stone-400 font-medium mr-2">
        <i data-lucide="cloud" class="w-3.5 h-3.5"></i>
        <span id="autosave-text">Ready</span>
      </div>

      <!-- AI Thinking Status -->
      <div id="ai-thinking-indicator" class="hidden flex items-center gap-2 text-orange-600 text-sm animate-pulse px-3 py-1 bg-orange-50 rounded-full border border-orange-200">
        <i data-lucide="bot" class="w-4 h-4"></i>
        <span class="font-hand font-bold">Thinking...</span>
      </div>

      <button
        onclick="saveNotepad()"
        class="flex items-center gap-2 px-6 py-2 bg-stone-800 hover:bg-stone-900 text-white rounded-lg font-hand text-lg transition-colors shadow-lg shadow-stone-300 active:scale-95"
      >
        <i data-lucide="save" class="w-4 h-4"></i>
        Save
      </button>
    </div>
  </div>

  <!-- Writing Area -->
  <div class="flex-1 overflow-hidden pt-20 pb-8 px-4 md:px-0">
    <div class="max-w-3xl mx-auto bg-paper shadow-2xl h-full relative lined-paper rounded-sm overflow-hidden flex flex-col">
      
      <!-- Top hole punches visual -->
      <div class="absolute top-4 left-0 right-0 flex justify-center gap-20 pointer-events-none opacity-20">
        <div class="w-4 h-4 rounded-full bg-stone-900"></div>
        <div class="w-4 h-4 rounded-full bg-stone-900"></div>
      </div>

      <textarea
        id="notepad-textarea"
        placeholder="Start writing... (Tip: Just type '?' to get an instant AI answer inline)"
        class="w-full h-full p-12 pt-16 text-xl text-ink leading-[2rem] resize-none focus:outline-none bg-transparent font-hand"
        spellcheck="false"
      ><?= htmlspecialchars($existingNote ? ($existingNote->rawContent ?: $existingNote->summary) : '') ?></textarea>
    </div>
  </div>
</div>

<script>
  const noteId = '<?= $existingNote ? $existingNote->id : "" ?>';
  const noteSubject = '<?= htmlspecialchars($subject ?? "General") ?>';
  const AUTOSAVE_KEY = 'notenest_draft_' + (noteId || 'new');

  const textarea = document.getElementById('notepad-textarea');
  const titleInput = document.getElementById('notepad-title');
  const autosaveStatus = document.getElementById('autosave-status');
  const autosaveText = document.getElementById('autosave-text');
  const thinkingIndicator = document.getElementById('ai-thinking-indicator');

  // Restore Draft if available
  if (!noteId) {
    const saved = localStorage.getItem(AUTOSAVE_KEY);
    if (saved) {
      try {
        const parsed = JSON.parse(saved);
        if (parsed.content) textarea.value = parsed.content;
        if (parsed.title) titleInput.value = parsed.title;
      } catch (e) {}
    }
  }

  // Autosave interval every 15 seconds
  setInterval(() => {
    const content = textarea.value;
    const title = titleInput.value;
    if (content.trim()) {
      localStorage.setItem(AUTOSAVE_KEY, JSON.stringify({ title, content, time: Date.now() }));
      autosaveStatus.classList.remove('hidden');
      autosaveText.textContent = 'Saved draft ' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
  }, 15000);

  // Instant '?' AI Question Trigger
  textarea.addEventListener('keydown', async (e) => {
    if (e.key === '?') {
      e.preventDefault();
      const pos = textarea.selectionStart;
      const val = textarea.value;

      // 1. Insert '?'
      const before = val.substring(0, pos);
      const after = val.substring(pos);
      textarea.value = before + '?' + after;
      textarea.selectionStart = textarea.selectionEnd = pos + 1;

      // 2. Find preceding sentence question
      const lastPunct = Math.max(
        before.lastIndexOf('.'),
        before.lastIndexOf('!'),
        before.lastIndexOf('?'),
        before.lastIndexOf('\n')
      );
      const question = before.substring(lastPunct + 1).trim();

      if (question.length > 2) {
        thinkingIndicator.classList.remove('hidden');
        try {
          const res = await fetch('/api/ai/answer', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              context: textarea.value,
              question: question + '?'
            })
          });
          const data = await res.json();
          thinkingIndicator.classList.add('hidden');

          if (data.answer) {
            const currentPos = textarea.selectionStart;
            const b = textarea.value.substring(0, currentPos);
            const a = textarea.value.substring(currentPos);
            textarea.value = b + "\n[AI]: " + data.answer + "\n\n" + a;
            textarea.selectionStart = textarea.selectionEnd = currentPos + data.answer.length + 10;
          }
        } catch (err) {
          thinkingIndicator.classList.add('hidden');
        }
      }
    }
  });

  async function saveNotepad() {
    const title = titleInput.value.trim() || 'Untitled Note';
    const content = textarea.value.trim();

    if (!content) {
      alert('Please write something before saving.');
      return;
    }

    try {
      const res = await fetch('/api/notes', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          id: noteId || undefined,
          title,
          subject: noteSubject,
          date: new Date().toLocaleDateString(),
          type: 'text',
          rawContent: content,
          summary: content.substring(0, 150) + '...',
          sections: [],
          tags: ['notepad', 'notes']
        })
      });

      const data = await res.json();
      if (data.success) {
        localStorage.removeItem(AUTOSAVE_KEY);
        window.location.href = '/note/' + data.note.id;
      } else {
        alert(data.error || 'Failed to save note');
      }
    } catch (e) {
      alert('Error saving note');
    }
  }
</script>
