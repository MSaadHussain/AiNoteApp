<?php
/**
 * NoteView - Full Paper View with Study Buddy AI Assistant
 */
?>

<div class="flex h-full relative bg-desk">
  
  <!-- Top Bar -->
  <div class="absolute top-0 left-0 right-0 h-16 bg-white/80 backdrop-blur-md border-b border-stone-200 z-10 flex items-center justify-between px-6 shadow-sm">
    <div class="flex items-center gap-4">
      <a href="/" class="p-2 hover:bg-stone-100 rounded-full text-stone-500 transition-colors">
        <i data-lucide="arrow-left" class="w-5 h-5"></i>
      </a>
      <div>
        <h2 class="font-hand font-bold text-xl text-stone-800 leading-none"><?= htmlspecialchars($note->title) ?></h2>
        <span class="text-xs text-stone-400 font-bold uppercase tracking-wider"><?= htmlspecialchars($note->subject) ?> • <?= htmlspecialchars($note->date) ?></span>
      </div>
    </div>

    <div class="flex items-center gap-3">
      <button
        type="button"
        onclick="toggleStudyBuddy()"
        class="bg-yellow-300 hover:bg-yellow-400 text-stone-900 px-4 py-2 rounded-lg flex items-center gap-2 text-sm font-hand font-bold transform -rotate-1 shadow-sm transition-transform hover:rotate-0"
      >
        <i data-lucide="brain-circuit" class="w-4 h-4"></i>
        Study Buddy
      </button>

      <!-- Export Dropdown -->
      <div class="relative">
        <button
          onclick="document.getElementById('note-export-menu').classList.toggle('hidden')"
          class="p-2 hover:bg-stone-100 rounded-full text-stone-400"
        >
          <i data-lucide="more-vertical" class="w-5 h-5"></i>
        </button>

        <div id="note-export-menu" class="hidden absolute right-0 top-12 w-48 bg-white rounded-xl shadow-xl border border-stone-100 z-20 py-2 animate-fade-in">
          <p class="px-4 py-2 text-xs font-bold text-stone-400 uppercase tracking-wider border-b border-stone-100 mb-1">Actions</p>
          <a
            href="/export/note/<?= $note->id ?>/pdf"
            target="_blank"
            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-stone-600 hover:bg-orange-50 hover:text-orange-700"
          >
            <i data-lucide="file-text" class="w-4 h-4"></i> Export as PDF
          </a>
          <a
            href="/export/note/<?= $note->id ?>/markdown"
            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-stone-600 hover:bg-orange-50 hover:text-orange-700"
          >
            <i data-lucide="file" class="w-4 h-4"></i> Export as Markdown
          </a>
          <a
            href="/notepad?id=<?= $note->id ?>"
            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-stone-600 hover:bg-orange-50 hover:text-orange-700"
          >
            <i data-lucide="pen-tool" class="w-4 h-4"></i> Edit in Notepad
          </a>
          <div class="border-t border-stone-100 my-1"></div>
          <button
            onclick="NoteNest.deleteNote('<?= $note->id ?>')"
            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50"
          >
            <i data-lucide="trash-2" class="w-4 h-4"></i> Delete Note
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Content Area (Lined Paper Sheet) -->
  <div class="flex-1 overflow-y-auto pt-20 pb-20 px-4 md:px-0">
    <div class="max-w-3xl mx-auto bg-paper shadow-2xl min-h-[90vh] relative lined-paper mb-10">
      
      <div class="pl-16 pr-8 pt-12 pb-8">
        <h1 class="text-4xl font-hand font-bold text-ink mb-6"><?= htmlspecialchars($note->title) ?></h1>

        <!-- Summary Box -->
        <div class="relative mb-8 group">
          <div class="absolute -inset-1 bg-yellow-100/50 rounded-lg transform -rotate-1 group-hover:rotate-0 transition-transform"></div>
          <div class="relative border-l-4 border-yellow-400 pl-4 py-2">
            <div class="flex justify-between items-start">
              <h3 class="font-hand font-bold text-lg text-stone-500 uppercase tracking-widest mb-1">Summary</h3>
              <button onclick="NoteNest.speak(document.getElementById('note-summary-text').textContent)" class="text-stone-300 hover:text-stone-600 p-1">
                <i data-lucide="volume-2" class="w-4 h-4"></i>
              </button>
            </div>
            <p id="note-summary-text" class="text-stone-700 leading-8 font-serif italic text-lg">
              <?= nl2br(htmlspecialchars($note->summary)) ?>
            </p>
          </div>
        </div>

        <!-- Sections -->
        <?php if (!empty($note->sections)): ?>
          <div class="space-y-8">
            <?php foreach ($note->sections as $idx => $sec): ?>
              <div class="group relative">
                <!-- Hint Sparkle in Left Margin -->
                <div class="absolute -left-12 top-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button
                    onclick="askAboutSection('<?= htmlspecialchars(addslashes($sec['heading'] ?? 'Section')) ?>', '<?= htmlspecialchars(addslashes($sec['content'] ?? '')) ?>')"
                    class="bg-stone-100 p-1.5 rounded-full text-stone-400 hover:text-orange-500 hover:bg-orange-50 shadow-sm border border-stone-200"
                    title="Ask Study Buddy"
                  >
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                  </button>
                </div>

                <h2 class="text-2xl font-hand font-bold text-blue-800 mt-6 mb-2 flex items-baseline gap-2">
                  <?= htmlspecialchars($sec['heading'] ?? 'Section') ?>
                  <span class="text-[10px] font-sans text-stone-400 uppercase border border-stone-200 px-1 rounded bg-white">
                    <?= htmlspecialchars($sec['type'] ?? 'theory') ?>
                  </span>
                </h2>

                <div class="text-lg text-stone-800 leading-8">
                  <?= nl2br(htmlspecialchars($sec['content'] ?? '')) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php elseif (!empty($note->rawContent)): ?>
          <div class="text-lg text-stone-800 leading-8 font-hand whitespace-pre-wrap">
            <?= htmlspecialchars($note->rawContent) ?>
          </div>
        <?php endif; ?>

        <!-- Keywords Tags -->
        <?php if (!empty($note->tags)): ?>
          <div class="mt-12 pt-8 border-t-2 border-dashed border-stone-200/50">
            <div class="flex flex-wrap gap-3">
              <?php foreach ($note->tags as $tag): ?>
                <span class="px-3 py-1 font-hand text-xl bg-stone-100 text-stone-500 rounded-full border border-stone-200 transform hover:-rotate-2 transition-transform cursor-default">
                  #<?= htmlspecialchars($tag) ?>
                </span>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Study Buddy AI Slideout Drawer -->
  <div id="study-buddy-panel" class="hidden w-[400px] bg-white shadow-[-10px_0_30px_rgba(0,0,0,0.1)] flex-col absolute right-0 top-16 bottom-0 z-20 border-l border-stone-100">
    <div class="p-4 bg-yellow-50 border-b border-yellow-100 flex justify-between items-center">
      <div class="flex items-center gap-2">
        <div class="bg-yellow-400 p-1.5 rounded-full">
          <i data-lucide="brain-circuit" class="w-5 h-5 text-yellow-900"></i>
        </div>
        <div>
          <h3 class="font-hand font-bold text-xl text-stone-800 leading-none">Study Buddy</h3>
          <p class="text-xs text-stone-500">Ask questions about your notes</p>
        </div>
      </div>
      <button onclick="toggleStudyBuddy()" class="hover:bg-yellow-100 p-1 rounded-full text-stone-500">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
    </div>

    <div id="buddy-messages" class="flex-1 overflow-y-auto p-4 bg-desk space-y-4">
      <div class="sticky-note p-4 text-stone-800 rotate-1 max-w-[90%] mx-auto text-center">
        <p class="font-hand text-lg leading-tight mb-1">Hi! I am your AI study assistant.</p>
        <p class="text-xs font-sans text-stone-600">Ask for step-by-step explanations, simpler analogies, or solved examples!</p>
      </div>
    </div>

    <!-- Input Bar -->
    <div class="p-4 bg-white border-t border-stone-100">
      <div class="relative shadow-sm rounded-xl overflow-hidden border border-stone-300 focus-within:border-orange-400 focus-within:ring-2 focus-within:ring-orange-100 transition-all">
        <textarea
          id="buddy-input"
          placeholder="Ask a question..."
          rows="2"
          class="w-full pl-3 pr-10 py-3 bg-white text-sm focus:outline-none resize-none"
          onkeydown="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); sendBuddyQuestion(); }"
        ></textarea>
        <button
          onclick="sendBuddyQuestion()"
          class="absolute right-2 bottom-2 p-1.5 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors"
        >
          <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  let activeSectionContext = '';

  function toggleStudyBuddy() {
    const panel = document.getElementById('study-buddy-panel');
    panel.classList.toggle('hidden');
    panel.classList.toggle('flex');
    if (!panel.classList.contains('hidden')) {
      document.getElementById('buddy-input').focus();
    }
  }

  function askAboutSection(heading, content) {
    activeSectionContext = content;
    const panel = document.getElementById('study-buddy-panel');
    panel.classList.remove('hidden');
    panel.classList.add('flex');
    document.getElementById('buddy-input').value = `Explain this: "${heading}"`;
    sendBuddyQuestion();
  }

  async function sendBuddyQuestion() {
    const input = document.getElementById('buddy-input');
    const query = input.value.trim();
    if (!query) return;

    input.value = '';
    const messages = document.getElementById('buddy-messages');

    // Add user bubble
    const userBubble = document.createElement('div');
    userBubble.className = 'self-end bg-white border border-stone-200 p-3 rounded-2xl rounded-tr-sm shadow-sm max-w-[85%] ml-auto';
    userBubble.innerHTML = `<p class="text-sm text-stone-700">${escapeHtml(query)}</p>`;
    messages.appendChild(userBubble);

    // Add thinking bubble
    const thinkingBubble = document.createElement('div');
    thinkingBubble.className = 'flex items-center gap-2 text-stone-400 p-2';
    thinkingBubble.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 animate-spin text-orange-400"></i><span class="font-hand text-lg">Thinking...</span>`;
    messages.appendChild(thinkingBubble);
    lucide.createIcons();
    messages.scrollTop = messages.scrollHeight;

    try {
      const context = activeSectionContext || document.getElementById('note-summary-text').textContent;
      const res = await fetch('/api/ai/solve', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ context, question: query })
      });
      const data = await res.json();
      thinkingBubble.remove();

      const aiBubble = document.createElement('div');
      aiBubble.className = 'bg-white border-l-4 border-orange-400 p-4 rounded-r-xl shadow-sm animate-slide-up';
      aiBubble.innerHTML = `
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-bold uppercase text-orange-500 tracking-wider">Answer</span>
          <button onclick="NoteNest.speak(this.closest('.bg-white').querySelector('.prose').textContent)" class="p-1 text-stone-400 hover:text-stone-600">
            <i data-lucide="volume-2" class="w-3.5 h-3.5"></i>
          </button>
        </div>
        <div class="prose prose-sm prose-stone font-sans whitespace-pre-wrap leading-relaxed">${escapeHtml(data.response || 'No response.')}</div>
      `;
      messages.appendChild(aiBubble);
      lucide.createIcons();
      messages.scrollTop = messages.scrollHeight;
    } catch (e) {
      thinkingBubble.remove();
      const errBubble = document.createElement('div');
      errBubble.className = 'text-red-500 text-xs p-2';
      errBubble.textContent = 'Error connecting to AI tutor.';
      messages.appendChild(errBubble);
    }
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
</script>
