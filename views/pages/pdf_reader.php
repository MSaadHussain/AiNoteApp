<?php
/**
 * PdfReader View - Smart Document Reader with Split AI Chat
 */
?>

<div class="flex h-full bg-stone-100 overflow-hidden relative">
  
  <!-- Header -->
  <div class="absolute top-0 left-0 right-0 h-14 bg-white border-b border-stone-200 flex items-center justify-between px-4 z-20">
    <div class="flex items-center gap-3">
      <a href="/" class="p-2 hover:bg-stone-100 rounded-full text-stone-500">
        <i data-lucide="arrow-left" class="w-5 h-5"></i>
      </a>
      <h2 class="font-bold text-stone-700 truncate max-w-md"><?= htmlspecialchars($note->title) ?></h2>
    </div>

    <div class="flex bg-stone-100 p-1 rounded-lg">
      <button
        id="tab-pdf"
        onclick="switchReaderTab('pdf')"
        class="px-3 py-1.5 rounded-md text-sm font-bold flex items-center gap-2 transition-all text-stone-500 hover:text-stone-700"
      >
        <i data-lucide="book-open" class="w-4 h-4"></i> PDF View
      </button>
      <button
        id="tab-text"
        onclick="switchReaderTab('text')"
        class="px-3 py-1.5 rounded-md text-sm font-bold flex items-center gap-2 transition-all bg-white shadow-sm text-stone-800"
      >
        <i data-lucide="file-text" class="w-4 h-4"></i> Smart Reader
      </button>
    </div>

    <button
      onclick="toggleChatPanel()"
      class="p-2 rounded-lg bg-orange-100 text-orange-600 hover:bg-orange-200 transition-colors"
      title="Toggle AI Chat"
    >
      <i data-lucide="brain-circuit" class="w-5 h-5"></i>
    </button>
  </div>

  <!-- Main Content & Split Reader -->
  <div id="reader-container" class="flex-1 flex pt-14 h-full transition-all mr-[400px]">
    <div class="flex-1 bg-stone-200 h-full overflow-hidden relative flex flex-col">
      
      <!-- Search Bar -->
      <div id="search-bar" class="bg-white border-b border-stone-200 px-4 py-2 flex items-center gap-3">
        <i data-lucide="search" class="w-4 h-4 text-stone-400"></i>
        <input
          type="text"
          id="doc-search-input"
          placeholder="Search in document..."
          oninput="highlightDocumentSearch(this.value)"
          class="flex-1 text-sm bg-transparent focus:outline-none text-stone-700 placeholder-stone-400"
        />
        <span id="match-counter" class="hidden text-xs text-stone-500 bg-stone-100 px-2 py-1 rounded-full">0 matches</span>
        <button
          onclick="summarizeDocument()"
          class="flex items-center gap-1.5 px-3 py-1.5 bg-orange-50 hover:bg-orange-100 text-orange-600 rounded-lg text-xs font-bold transition-colors whitespace-nowrap border border-orange-200"
        >
          <i data-lucide="list-filter" class="w-3.5 h-3.5"></i> Summarize
        </button>
      </div>

      <!-- Smart Text Reader -->
      <div
        id="view-text-pane"
        class="flex-1 overflow-y-auto p-8 bg-white max-w-3xl mx-auto shadow-sm w-full select-text"
        onmouseup="handleTextSelection()"
        oncontextmenu="handleTextContextMenu(event)"
      >
        <h1 class="text-3xl font-bold mb-2 text-stone-900"><?= htmlspecialchars($note->title) ?></h1>
        <p class="text-sm text-stone-400 mb-6 border-b border-stone-100 pb-4">
          <?= htmlspecialchars($note->subject) ?> • <?= htmlspecialchars($note->date) ?> • Select text to ask AI or right-click for options
        </p>
        <div id="document-body" class="prose prose-lg prose-stone max-w-none font-serif leading-relaxed whitespace-pre-wrap">
          <?= htmlspecialchars($note->rawContent ?: $note->summary) ?>
        </div>
      </div>

      <!-- Raw PDF View (if PDF URL available) -->
      <div id="view-pdf-pane" class="hidden flex-1 bg-stone-100 flex items-center justify-center">
        <?php if (!empty($note->pdfUrl)): ?>
          <iframe src="<?= htmlspecialchars($note->pdfUrl) ?>" class="w-full h-full border-none"></iframe>
        <?php else: ?>
          <div class="text-stone-400 font-hand text-xl">PDF file attached as text content. Use Smart Reader mode!</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- AI Chat Panel (Right Sidebar) -->
  <div id="chat-panel" class="absolute top-14 bottom-0 right-0 w-[400px] bg-white border-l border-stone-200 transform transition-transform duration-300 z-10 flex flex-col">
    
    <div class="p-4 border-b border-stone-100 bg-orange-50/50 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <i data-lucide="sparkles" class="w-5 h-5 text-orange-500"></i>
        <h3 class="font-bold text-stone-700">AI Chat</h3>
      </div>
      <button onclick="clearChatHistory()" class="text-xs text-stone-400 hover:text-stone-600 px-2 py-1 rounded hover:bg-stone-100">
        Clear
      </button>
    </div>

    <!-- Chat Messages Scroll Container -->
    <div id="chat-messages-container" class="flex-1 overflow-y-auto p-4 space-y-3 bg-stone-50">
      <div class="text-center text-stone-400 mt-10 p-4">
        <i data-lucide="brain-circuit" class="w-12 h-12 mx-auto mb-3 opacity-50"></i>
        <p class="text-sm font-medium mb-1">Ask me anything about this document!</p>
        <p class="text-xs text-stone-400">Select text in Smart Reader and right-click to explain, or type a question below.</p>
      </div>
    </div>

    <!-- Selected Text Indicator Chip -->
    <div id="selected-text-chip" class="hidden px-4 py-2 bg-yellow-50 border-t border-yellow-100 flex items-center justify-between">
      <p class="text-xs text-yellow-700 truncate flex-1">
        <span class="font-bold">Selected:</span> "<span id="selected-chip-text"></span>"
      </p>
      <button
        onclick="askAboutSelectionFromChip()"
        class="text-xs bg-yellow-200 hover:bg-yellow-300 text-yellow-800 px-2 py-1 rounded font-bold ml-2 whitespace-nowrap"
      >
        Ask AI
      </button>
    </div>

    <!-- Chat Input Box -->
    <div class="p-3 bg-white border-t border-stone-200">
      <div class="relative flex gap-2">
        <input
          type="text"
          id="chat-input"
          placeholder="Ask about this document..."
          onkeydown="if(event.key === 'Enter') sendChatMessage();"
          class="flex-1 pl-4 pr-4 py-2.5 bg-stone-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-200"
        />
        <button
          onclick="sendChatMessage()"
          class="p-2.5 bg-orange-500 text-white rounded-xl hover:bg-orange-600 transition-colors"
        >
          <i data-lucide="send" class="w-4 h-4"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Text Selection Context Menu -->
  <div id="selection-context-menu" class="hidden fixed bg-white rounded-xl shadow-2xl border border-stone-200 py-2 z-50 animate-fade-in w-52">
    <button onclick="explainSelectedText()" class="w-full text-left px-4 py-2.5 text-sm text-stone-700 hover:bg-orange-50 hover:text-orange-600 flex items-center gap-3">
      <i data-lucide="sparkles" class="w-4 h-4"></i> Explain This
    </button>
    <button onclick="askAboutSelection()" class="w-full text-left px-4 py-2.5 text-sm text-stone-700 hover:bg-orange-50 hover:text-orange-600 flex items-center gap-3">
      <i data-lucide="brain-circuit" class="w-4 h-4"></i> Ask About This
    </button>
    <div class="border-t border-stone-100 my-1"></div>
    <button onclick="createReminderFromSelection()" class="w-full text-left px-4 py-2.5 text-sm text-stone-700 hover:bg-orange-50 hover:text-orange-600 flex items-center gap-3">
      <i data-lucide="bell" class="w-4 h-4"></i> Create Reminder
    </button>
    <button onclick="createNoteFromSelection()" class="w-full text-left px-4 py-2.5 text-sm text-stone-700 hover:bg-orange-50 hover:text-orange-600 flex items-center gap-3">
      <i data-lucide="pen-tool" class="w-4 h-4"></i> Save as Note
    </button>
  </div>
</div>

<script>
  const currentNoteId = '<?= $note->id ?>';
  const originalDocText = <?= json_encode($note->rawContent ?: $note->summary) ?>;
  let currentSelection = '';

  function switchReaderTab(tab) {
    const textPane = document.getElementById('view-text-pane');
    const pdfPane = document.getElementById('view-pdf-pane');
    const tabPdf = document.getElementById('tab-pdf');
    const tabText = document.getElementById('tab-text');

    if (tab === 'pdf') {
      textPane.classList.add('hidden');
      pdfPane.classList.remove('hidden');
      tabPdf.className = 'px-3 py-1.5 rounded-md text-sm font-bold flex items-center gap-2 transition-all bg-white shadow-sm text-stone-800';
      tabText.className = 'px-3 py-1.5 rounded-md text-sm font-bold flex items-center gap-2 transition-all text-stone-500 hover:text-stone-700';
    } else {
      pdfPane.classList.add('hidden');
      textPane.classList.remove('hidden');
      tabText.className = 'px-3 py-1.5 rounded-md text-sm font-bold flex items-center gap-2 transition-all bg-white shadow-sm text-stone-800';
      tabPdf.className = 'px-3 py-1.5 rounded-md text-sm font-bold flex items-center gap-2 transition-all text-stone-500 hover:text-stone-700';
    }
  }

  function toggleChatPanel() {
    const panel = document.getElementById('chat-panel');
    const container = document.getElementById('reader-container');
    panel.classList.toggle('translate-x-full');
    container.classList.toggle('mr-[400px]');
  }

  function handleTextSelection() {
    const sel = window.getSelection();
    if (sel && sel.toString().trim().length > 0) {
      currentSelection = sel.toString().trim();
      document.getElementById('selected-chip-text').textContent = currentSelection.substring(0, 50) + '...';
      document.getElementById('selected-text-chip').classList.remove('hidden');
    }
  }

  function handleTextContextMenu(e) {
    if (currentSelection) {
      e.preventDefault();
      const menu = document.getElementById('selection-context-menu');
      menu.style.left = Math.min(e.clientX, window.innerWidth - 220) + 'px';
      menu.style.top = Math.min(e.clientY, window.innerHeight - 200) + 'px';
      menu.classList.remove('hidden');
    }
  }

  document.addEventListener('click', () => {
    document.getElementById('selection-context-menu').classList.add('hidden');
  });

  function explainSelectedText() {
    document.getElementById('selection-context-menu').classList.add('hidden');
    sendDirectChatMessage(`Explain this: "${currentSelection.substring(0, 400)}"`);
  }

  function askAboutSelection() {
    document.getElementById('selection-context-menu').classList.add('hidden');
    document.getElementById('chat-input').value = `About "${currentSelection.substring(0, 100)}...": `;
    document.getElementById('chat-input').focus();
  }

  function askAboutSelectionFromChip() {
    sendDirectChatMessage(`Explain this: "${currentSelection.substring(0, 400)}"`);
  }

  async function createReminderFromSelection() {
    document.getElementById('selection-context-menu').classList.add('hidden');
    await NoteNest.saveReminder({
      text: 'Read: ' + currentSelection.substring(0, 40) + '...',
      dueDate: 'Tomorrow',
      type: 'note',
      targetId: currentNoteId,
      targetName: <?= json_encode($note->title) ?>
    });
    NoteNest.showToast('Reminder Created', 'success', 'Linked to this document.');
  }

  function createNoteFromSelection() {
    document.getElementById('selection-context-menu').classList.add('hidden');
    window.location.href = '/notepad?subject=' + encodeURIComponent('<?= $note->subject ?>');
  }

  // --- Document Search Highlight ---
  function highlightDocumentSearch(query) {
    const body = document.getElementById('document-body');
    const counter = document.getElementById('match-counter');
    const q = query.trim();

    if (!q) {
      body.textContent = originalDocText;
      counter.classList.add('hidden');
      return;
    }

    const regex = new RegExp(`(${escapeRegex(q)})`, 'gi');
    const matches = originalDocText.match(regex) || [];
    counter.textContent = matches.length + ' matches';
    counter.classList.remove('hidden');

    const highlighted = originalDocText.replace(regex, '<mark class="bg-yellow-300 text-yellow-900 px-0.5 rounded">$1</mark>');
    body.innerHTML = highlighted;
  }

  function escapeRegex(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  // --- AI Chat Logic ---
  async function sendChatMessage() {
    const input = document.getElementById('chat-input');
    const msg = input.value.trim();
    if (!msg) return;
    input.value = '';
    sendDirectChatMessage(msg);
  }

  async function sendDirectChatMessage(message) {
    const container = document.getElementById('chat-messages-container');

    // Append User message
    const userMsg = document.createElement('div');
    userMsg.className = 'flex justify-end';
    userMsg.innerHTML = `<div class="max-w-[85%] rounded-2xl px-4 py-2.5 text-sm bg-orange-500 text-white rounded-tr-sm">${escapeHtml(message)}</div>`;
    container.appendChild(userMsg);

    // Append Thinking bubble
    const thinkingMsg = document.createElement('div');
    thinkingMsg.className = 'flex justify-start';
    thinkingMsg.innerHTML = `
      <div class="max-w-[85%] rounded-2xl px-4 py-2.5 text-sm bg-white border border-stone-200 text-stone-700 rounded-tl-sm shadow-sm flex items-center gap-2">
        <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin text-orange-500"></i>
        <span class="text-xs">Thinking...</span>
      </div>
    `;
    container.appendChild(thinkingMsg);
    lucide.createIcons();
    container.scrollTop = container.scrollHeight;

    try {
      const res = await fetch('/api/ai/answer', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          context: originalDocText.substring(0, 4000),
          question: message
        })
      });
      const data = await res.json();
      thinkingMsg.remove();

      const aiMsg = document.createElement('div');
      aiMsg.className = 'flex justify-start';
      aiMsg.innerHTML = `
        <div class="max-w-[85%] rounded-2xl px-4 py-2.5 text-sm bg-white border border-stone-200 text-stone-700 rounded-tl-sm shadow-sm whitespace-pre-wrap leading-relaxed">
          ${escapeHtml(data.answer || "I couldn't find an answer in this document.")}
        </div>
      `;
      container.appendChild(aiMsg);
      container.scrollTop = container.scrollHeight;
    } catch (e) {
      thinkingMsg.remove();
    }
  }

  function summarizeDocument() {
    sendDirectChatMessage('Provide a structured summary of this document.');
  }

  function clearChatHistory() {
    document.getElementById('chat-messages-container').innerHTML = `
      <div class="text-center text-stone-400 mt-10 p-4">
        <p class="text-sm font-medium">Chat cleared. Ask anything about this document!</p>
      </div>
    `;
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
</script>
