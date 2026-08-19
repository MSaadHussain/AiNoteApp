<?php
/**
 * Smart document reader - extracted text (or the original PDF) beside an AI
 * chat panel, with in-document search and selection actions.
 */
require_once dirname(__DIR__) . '/partials/helpers.php';

$docText = $note->rawContent ?: $note->summary;
?>

<div class="flex h-full flex-col">

  <!-- ============ Top bar ============ -->
  <header class="z-20 flex h-16 flex-shrink-0 items-center justify-between gap-2 border-b border-line bg-surface px-3 sm:px-4">
    <div class="flex min-w-0 items-center gap-2">
      <a href="/?subject=<?= urlencode($note->subject) ?>" class="btn-icon" aria-label="Back to <?= htmlspecialchars($note->subject) ?>">
        <i data-lucide="arrow-left" class="h-5 w-5" aria-hidden="true"></i>
      </a>
      <div class="min-w-0">
        <h1 class="truncate text-sm font-bold leading-tight sm:text-base"><?= htmlspecialchars($note->title) ?></h1>
        <p class="truncate text-2xs font-semibold normal-case tracking-normal text-content-subtle">
          <?= htmlspecialchars($note->subject) ?> &middot; <?= htmlspecialchars($note->date) ?>
        </p>
      </div>
    </div>

    <div class="flex flex-shrink-0 items-center gap-2">
      <!-- View switcher -->
      <div role="tablist" aria-label="Document view" class="hidden rounded-control bg-surface-sunken p-1 sm:flex">
        <button role="tab"
                id="tab-text"
                aria-selected="true"
                aria-controls="panel-text"
                class="flex min-h-[2.25rem] items-center gap-1.5 rounded-lg px-3 text-2xs font-bold uppercase tracking-wide transition-colors duration-200"
                onclick="switchReaderTab('text')">
          <i data-lucide="file-text" class="h-3.5 w-3.5" aria-hidden="true"></i>
          Reader
        </button>
        <button role="tab"
                id="tab-pdf"
                aria-selected="false"
                aria-controls="panel-pdf"
                class="flex min-h-[2.25rem] items-center gap-1.5 rounded-lg px-3 text-2xs font-bold uppercase tracking-wide transition-colors duration-200"
                onclick="switchReaderTab('pdf')">
          <i data-lucide="book-open" class="h-3.5 w-3.5" aria-hidden="true"></i>
          Original
        </button>
      </div>

      <button type="button"
              id="chat-toggle"
              class="btn-primary btn-sm sm:min-h-[2.75rem] sm:px-4 sm:text-sm sm:normal-case sm:tracking-normal"
              aria-expanded="false"
              aria-controls="chat-panel"
              onclick="toggleChatPanel(this)">
        <i data-lucide="sparkles" class="h-4 w-4" aria-hidden="true"></i>
        <span class="hidden sm:inline">Ask AI</span>
        <span class="sm:hidden">AI</span>
      </button>
    </div>
  </header>

  <div class="flex min-h-0 flex-1">

    <!-- ============ Document ============ -->
    <div class="flex min-w-0 flex-1 flex-col">

      <!-- Search + summarise -->
      <div class="flex flex-shrink-0 flex-wrap items-center gap-2 border-b border-line bg-surface px-3 py-2 sm:px-4">
        <div class="relative min-w-0 flex-1">
          <label for="doc-search" class="sr-only">Search in this document</label>
          <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-content-subtle" aria-hidden="true"></i>
          <input id="doc-search"
                 type="search"
                 class="input pl-9"
                 placeholder="Search in document"
                 autocomplete="off"
                 oninput="highlightDocumentSearch(this.value)"
                 aria-describedby="match-counter">
        </div>
        <p id="match-counter" role="status" aria-live="polite"
           class="text-2xs font-semibold uppercase tracking-wider tabular-nums text-content-subtle"></p>
        <button type="button" class="btn-secondary btn-sm" onclick="summarizeDocument()">
          <i data-lucide="sparkles" class="h-3.5 w-3.5" aria-hidden="true"></i>
          Summarise
        </button>
      </div>

      <!-- Reader pane -->
      <div id="panel-text" role="tabpanel" aria-labelledby="tab-text"
           class="scrollbar-slim min-h-0 flex-1 overflow-y-auto bg-surface-sunken">
        <article class="mx-auto max-w-3xl px-4 pb-24 pt-8 sm:px-8 md:pb-10">
          <div class="mb-6 flex flex-wrap items-center gap-2">
            <span class="badge-warning">
              <i data-lucide="file-text" class="h-3 w-3" aria-hidden="true"></i>
              Document
            </span>
            <span class="badge-neutral"><?= htmlspecialchars($note->subject) ?></span>
          </div>

          <h2 class="text-2xl font-extrabold leading-tight tracking-tight sm:text-3xl">
            <?= htmlspecialchars($note->title) ?>
          </h2>
          <p class="mt-2 text-2xs font-medium normal-case tracking-normal text-content-subtle">
            Select any passage to ask the AI about it.
          </p>

          <div id="document-body"
               class="prose-note mt-8 whitespace-pre-wrap rounded-card border border-line bg-surface p-5 sm:p-8"><?= htmlspecialchars($docText) ?></div>
        </article>
      </div>

      <!-- Original PDF pane -->
      <div id="panel-pdf" role="tabpanel" aria-labelledby="tab-pdf" hidden
           class="min-h-0 flex-1 bg-surface-sunken">
        <?php if (!empty($note->pdfUrl)): ?>
          <iframe src="<?= htmlspecialchars($note->pdfUrl) ?>"
                  title="Original PDF: <?= htmlspecialchars($note->title) ?>"
                  class="h-full w-full border-0"></iframe>
        <?php else: ?>
          <div class="flex h-full flex-col items-center justify-center px-6 text-center">
            <span class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-surface text-content-subtle ring-1 ring-line">
              <i data-lucide="file-x" class="h-6 w-6" aria-hidden="true"></i>
            </span>
            <h3 class="text-base font-bold">Original file not stored</h3>
            <p class="mx-auto mt-1.5 max-w-sm text-sm text-content-muted">
              Only the extracted text was kept for this document. Switch to Reader to read and search it.
            </p>
            <button type="button" class="btn-secondary mt-5" onclick="switchReaderTab('text')">
              Open Reader
            </button>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ============ AI chat ============ -->
    <aside id="chat-panel"
           class="fixed inset-0 z-40 flex flex-col border-line bg-surface lg:static lg:z-auto lg:w-[24rem] lg:flex-shrink-0 lg:border-l"
           hidden
           aria-labelledby="chat-title">

      <div class="flex flex-shrink-0 items-center justify-between gap-2 border-b border-line px-4 py-3">
        <div class="flex min-w-0 items-center gap-2.5">
          <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
            <i data-lucide="sparkles" class="h-4 w-4" aria-hidden="true"></i>
          </span>
          <div class="min-w-0">
            <h2 id="chat-title" class="text-sm font-bold leading-tight">Document AI</h2>
            <p class="truncate text-2xs font-medium normal-case tracking-normal text-content-subtle">
              Grounded in this document
            </p>
          </div>
        </div>
        <div class="flex flex-shrink-0 items-center gap-1">
          <button type="button" class="btn-icon" aria-label="Clear conversation" onclick="clearChat()">
            <i data-lucide="eraser" class="h-4 w-4" aria-hidden="true"></i>
          </button>
          <button type="button" class="btn-icon" aria-label="Close AI panel"
                  onclick="toggleChatPanel(document.getElementById('chat-toggle'))">
            <i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i>
          </button>
        </div>
      </div>

      <div id="chat-messages"
           class="scrollbar-slim flex-1 space-y-3 overflow-y-auto bg-surface-sunken p-4"
           role="log" aria-live="polite" aria-label="Conversation about this document"></div>

      <!-- Selection chip: appears whenever text is selected, mouse or keyboard. -->
      <div id="selection-chip" hidden
           class="flex flex-shrink-0 items-center gap-2 border-t border-brand-200 bg-brand-50 px-3 py-2">
        <p class="min-w-0 flex-1 truncate text-2xs font-semibold normal-case tracking-normal text-brand-700">
          <span class="font-bold uppercase tracking-wider">Selected:</span>
          <span id="selection-chip-text"></span>
        </p>
        <button type="button" class="btn-primary btn-sm flex-shrink-0" onclick="explainSelection()">Explain</button>
        <button type="button" class="btn-icon btn-icon-compact flex-shrink-0" aria-label="Clear selection" onclick="clearSelection()">
          <i data-lucide="x" class="h-3.5 w-3.5" aria-hidden="true"></i>
        </button>
      </div>

      <form id="chat-form" class="flex-shrink-0 border-t border-line bg-surface p-3 pb-safe">
        <label for="chat-input" class="sr-only">Ask a question about this document</label>
        <div class="flex gap-2">
          <input id="chat-input" type="text" class="input" placeholder="Ask about this document…" autocomplete="off">
          <button type="submit" class="btn-primary flex-shrink-0 px-3" aria-label="Send question">
            <i data-lucide="arrow-up" class="h-4 w-4" aria-hidden="true"></i>
          </button>
        </div>
      </form>
    </aside>
  </div>
</div>

<script>
  const currentNoteId = <?= json_encode($note->id) ?>;
  const noteTitle = <?= json_encode($note->title) ?>;
  const noteSubject = <?= json_encode($note->subject) ?>;
  const originalDocText = <?= json_encode($docText) ?>;

  let currentSelection = '';

  const chatPanel = document.getElementById('chat-panel');
  const chatToggle = document.getElementById('chat-toggle');
  const chatMessages = document.getElementById('chat-messages');
  const chatInput = document.getElementById('chat-input');

  const isDocked = () => window.matchMedia('(min-width: 1024px)').matches;

  /* ---- Tabs ---------------------------------------------------------- */
  const TAB_ACTIVE = ['bg-surface', 'text-content', 'shadow-xs'];
  const TAB_IDLE = ['text-content-muted', 'hover:text-content'];

  function switchReaderTab(tab) {
    const isText = tab === 'text';
    document.getElementById('panel-text').hidden = !isText;
    document.getElementById('panel-pdf').hidden = isText;

    [['tab-text', isText], ['tab-pdf', !isText]].forEach(([id, active]) => {
      const btn = document.getElementById(id);
      btn.setAttribute('aria-selected', String(active));
      btn.classList.remove(...TAB_ACTIVE, ...TAB_IDLE);
      btn.classList.add(...(active ? TAB_ACTIVE : TAB_IDLE));
    });
  }

  /* ---- Chat panel ---------------------------------------------------- */
  new MutationObserver(() => {
    chatToggle.setAttribute('aria-expanded', String(!chatPanel.hidden));
  }).observe(chatPanel, { attributes: true, attributeFilter: ['hidden'] });

  function toggleChatPanel(trigger) {
    if (chatPanel.hidden) {
      if (isDocked()) {
        chatPanel.removeAttribute('role');
        chatPanel.removeAttribute('aria-modal');
        chatPanel.hidden = false;
        chatInput.focus();
      } else {
        chatPanel.setAttribute('role', 'dialog');
        chatPanel.setAttribute('aria-modal', 'true');
        NoteNest.dialog.open('chat-panel', trigger);
      }
      return;
    }

    if (NoteNest.dialog.stack.some((entry) => entry.el === 'chat-panel')) {
      NoteNest.dialog.close('chat-panel');
    } else {
      chatPanel.hidden = true;
    }
  }

  function openChat() {
    if (chatPanel.hidden) toggleChatPanel(chatToggle);
  }

  /* ---- Text selection ------------------------------------------------ */
  // selectionchange fires for keyboard selection too, so this is not a
  // mouse-only affordance the way a right-click menu would be.
  document.addEventListener('selectionchange', () => {
    const selection = window.getSelection();
    const text = selection ? selection.toString().trim() : '';
    const body = document.getElementById('document-body');

    if (!text || !selection.anchorNode || !body.contains(selection.anchorNode)) return;

    currentSelection = text;
    document.getElementById('selection-chip-text').textContent =
      text.length > 60 ? text.slice(0, 60) + '…' : text;
    document.getElementById('selection-chip').hidden = false;
  });

  function clearSelection() {
    currentSelection = '';
    document.getElementById('selection-chip').hidden = true;
    window.getSelection()?.removeAllRanges();
  }

  function explainSelection() {
    if (!currentSelection) return;
    openChat();
    sendMessage('Explain this passage: "' + currentSelection.slice(0, 400) + '"');
  }

  function summarizeDocument() {
    openChat();
    sendMessage('Provide a structured summary of this document.');
  }

  /* ---- In-document search -------------------------------------------- */
  function escapeRegex(value) {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  function highlightDocumentSearch(query) {
    const body = document.getElementById('document-body');
    const counter = document.getElementById('match-counter');
    const q = query.trim();

    if (!q) {
      body.textContent = originalDocText;
      counter.textContent = '';
      return;
    }

    const regex = new RegExp('(' + escapeRegex(q) + ')', 'gi');
    const matches = originalDocText.match(regex) || [];
    counter.textContent = matches.length + (matches.length === 1 ? ' match' : ' matches');

    // Escape first, then wrap - the source text is never treated as markup.
    body.innerHTML = NoteNest.escapeHtml(originalDocText).replace(
      regex,
      '<mark class="rounded bg-warning-soft px-0.5 text-content ring-1 ring-warning/40">$1</mark>'
    );
  }

  /* ---- Chat ---------------------------------------------------------- */
  function emptyChatState() {
    chatMessages.innerHTML =
      '<div class="rounded-card border border-line bg-surface p-4">' +
        '<p class="text-sm font-semibold">Ask anything about this document</p>' +
        '<p class="mt-1 text-2xs font-medium normal-case tracking-normal leading-relaxed text-content-muted">' +
          'Select a passage and choose Explain, or type a question below.</p>' +
      '</div>';
    NoteNest.refreshIcons(chatMessages);
  }

  function appendBubble(html, className) {
    const el = document.createElement('div');
    el.className = className;
    el.innerHTML = html;
    chatMessages.appendChild(el);
    NoteNest.refreshIcons(el);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    return el;
  }

  async function sendMessage(message) {
    const text = (message || '').trim();
    if (!text) return;

    appendBubble(
      '<p class="text-sm leading-relaxed">' + NoteNest.escapeHtml(text) + '</p>',
      'ml-auto max-w-[85%] rounded-card rounded-tr-sm bg-brand-600 px-3.5 py-2.5 text-content-inverse'
    );

    const pending = appendBubble(
      '<div class="flex items-center gap-2 text-2xs font-semibold uppercase tracking-wider text-content-subtle">' +
      '<i data-lucide="loader-circle" class="h-3.5 w-3.5 animate-spin" aria-hidden="true"></i> Thinking</div>' +
      '<div class="mt-2.5 space-y-2"><div class="skeleton h-3 w-full"></div>' +
      '<div class="skeleton h-3 w-10/12"></div><div class="skeleton h-3 w-2/3"></div></div>',
      'max-w-[92%] rounded-card border border-line bg-surface p-3.5'
    );

    try {
      const data = await NoteNest.api('/api/ai/answer', {
        method: 'POST',
        body: { context: originalDocText.slice(0, 4000), question: text },
      });
      pending.remove();
      appendBubble(
        '<p class="whitespace-pre-wrap text-sm leading-relaxed">' +
        NoteNest.escapeHtml(data.answer || 'I could not find an answer in this document.') + '</p>',
        'max-w-[92%] animate-rise-in rounded-card rounded-tl-sm border border-line bg-surface p-3.5 shadow-xs'
      );
    } catch (e) {
      pending.remove();
      appendBubble(
        '<p class="flex items-center gap-2 text-sm font-medium text-danger">' +
        '<i data-lucide="circle-alert" class="h-4 w-4 flex-shrink-0" aria-hidden="true"></i>' +
        NoteNest.escapeHtml(e.message) + '</p>',
        'max-w-[92%] rounded-card border border-danger/30 bg-danger-soft p-3.5'
      );
    }
  }

  document.getElementById('chat-form').addEventListener('submit', (event) => {
    event.preventDefault();
    const value = chatInput.value.trim();
    if (!value) return;
    chatInput.value = '';
    sendMessage(value);
  });

  async function clearChat() {
    const ok = await NoteNest.confirm({
      title: 'Clear this conversation?',
      body: 'The messages in this panel will be removed.',
      confirmLabel: 'Clear',
    });
    if (ok) emptyChatState();
  }

  /* ---- Init ---------------------------------------------------------- */
  // The panel is a docked column at lg+ and a modal sheet below it. Resizing
  // or rotating across that breakpoint has to move it between the two modes,
  // otherwise a docked panel becomes a full-screen overlay with no scrim, or
  // a trapped modal survives into a layout that no longer needs one.
  const wideQuery = window.matchMedia('(min-width: 1024px)');

  function syncChatToViewport() {
    const inDialog = NoteNest.dialog.stack.some((entry) => entry.el === 'chat-panel');

    if (wideQuery.matches) {
      if (inDialog) NoteNest.dialog.close('chat-panel');
      chatPanel.removeAttribute('role');
      chatPanel.removeAttribute('aria-modal');
      chatPanel.hidden = false;             // docked by default on wide screens
    } else if (!inDialog) {
      chatPanel.hidden = true;              // opt-in only on narrow screens
    }

    chatToggle.setAttribute('aria-expanded', String(!chatPanel.hidden));
  }

  wideQuery.addEventListener('change', syncChatToViewport);

  // app.js is deferred, so NoteNest does not exist while this inline script is
  // parsed. Deferred scripts always run before DOMContentLoaded, so waiting for
  // it is what makes NoteNest.* safe to call during init.
  document.addEventListener('DOMContentLoaded', () => {
    switchReaderTab('text');
    emptyChatState();
    syncChatToViewport();
  });
</script>
