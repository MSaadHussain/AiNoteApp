<?php
/**
 * Note reader + Study Buddy AI panel.
 *
 * The reading column uses the serif face and a constrained measure; the app
 * chrome stays in the UI sans face so the two never compete.
 */
require_once dirname(__DIR__) . '/partials/helpers.php';

$meta = note_type_meta($note->type);
?>

<div class="flex h-full flex-col">

  <!-- ============ Top bar ============ -->
  <header class="z-20 flex h-16 flex-shrink-0 items-center justify-between gap-3 border-b border-line bg-surface/90 px-3 backdrop-blur sm:px-4">
    <div class="flex min-w-0 items-center gap-2">
      <a href="/?subject=<?= urlencode($note->subject) ?>" class="btn-icon" aria-label="Back to <?= htmlspecialchars($note->subject) ?>">
        <i data-lucide="arrow-left" class="h-5 w-5" aria-hidden="true"></i>
      </a>
      <div class="min-w-0">
        <h1 class="truncate text-sm font-bold leading-tight sm:text-base"><?= htmlspecialchars($note->title) ?></h1>
        <p class="flex items-center gap-1.5 truncate text-2xs font-semibold normal-case tracking-normal text-content-subtle">
          <span><?= htmlspecialchars($note->subject) ?></span>
          <span aria-hidden="true">&middot;</span>
          <span><?= htmlspecialchars($note->date) ?></span>
        </p>
      </div>
    </div>

    <div class="flex flex-shrink-0 items-center gap-1.5">
      <button type="button"
              id="study-buddy-toggle"
              class="btn-primary btn-sm sm:min-h-[2.75rem] sm:px-4 sm:text-sm sm:normal-case sm:tracking-normal"
              aria-expanded="false"
              aria-controls="study-buddy-panel"
              onclick="toggleStudyBuddy(this)">
        <i data-lucide="sparkles" class="h-4 w-4" aria-hidden="true"></i>
        <span class="hidden sm:inline">Study Buddy</span>
        <span class="sm:hidden">AI</span>
      </button>

      <div class="relative">
        <button type="button"
                class="btn-icon"
                data-menu-trigger="note-actions-menu"
                aria-haspopup="menu"
                aria-expanded="false"
                aria-label="More actions">
          <i data-lucide="ellipsis-vertical" class="h-5 w-5" aria-hidden="true"></i>
        </button>
        <div id="note-actions-menu" hidden role="menu" class="menu absolute right-0 top-full mt-1.5">
          <a role="menuitem" class="menu-item" href="/notepad?id=<?= $note->id ?>">
            <i data-lucide="pen-line" class="h-4 w-4" aria-hidden="true"></i> Edit note
          </a>
          <button role="menuitem" type="button" class="menu-item"
                  onclick="openReminderDialog(this, 'note', '<?= $note->id ?>', <?= htmlspecialchars(json_encode($note->title), ENT_QUOTES) ?>)">
            <i data-lucide="bell-plus" class="h-4 w-4" aria-hidden="true"></i> Set a reminder
          </button>
          <a role="menuitem" class="menu-item" href="/export/note/<?= $note->id ?>/pdf" target="_blank" rel="noopener">
            <i data-lucide="file-text" class="h-4 w-4" aria-hidden="true"></i> Export as PDF
          </a>
          <a role="menuitem" class="menu-item" href="/export/note/<?= $note->id ?>/markdown">
            <i data-lucide="file-down" class="h-4 w-4" aria-hidden="true"></i> Export as Markdown
          </a>
          <button role="menuitem" type="button" class="menu-item-danger"
                  onclick="NoteNest.deleteNote('<?= $note->id ?>', <?= htmlspecialchars(json_encode($note->title), ENT_QUOTES) ?>)">
            <i data-lucide="trash-2" class="h-4 w-4" aria-hidden="true"></i> Delete note
          </button>
        </div>
      </div>
    </div>
  </header>

  <div class="flex min-h-0 flex-1">

    <!-- ============ Reading column ============ -->
    <div class="scrollbar-slim min-w-0 flex-1 overflow-y-auto">
      <article class="mx-auto max-w-3xl px-4 pb-24 pt-8 sm:px-8 md:pb-12">

        <div class="mb-6 flex flex-wrap items-center gap-2">
          <span class="<?= $meta['badge'] ?>">
            <i data-lucide="<?= $meta['icon'] ?>" class="h-3 w-3" aria-hidden="true"></i>
            <?= $meta['label'] ?>
          </span>
          <span class="badge-neutral"><?= htmlspecialchars($note->subject) ?></span>
        </div>

        <h2 class="text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">
          <?= htmlspecialchars($note->title) ?>
        </h2>

        <!-- Summary callout -->
        <?php if (!empty($note->summary)): ?>
          <section class="mt-6 rounded-card border border-brand-200 bg-brand-50 p-5"
                   aria-labelledby="summary-heading">
            <div class="mb-2 flex items-center justify-between gap-2">
              <h3 id="summary-heading" class="section-label text-brand-700">
                <i data-lucide="sparkles" class="h-3.5 w-3.5" aria-hidden="true"></i>
                Summary
              </h3>
              <button type="button"
                      class="btn-icon btn-icon-compact text-brand-600 hover:bg-brand-100"
                      aria-label="Read aloud"
                      data-speaking="false"
                      onclick="NoteNest.speak(document.getElementById('note-summary-text').textContent, this)">
                <i data-lucide="volume-2" class="h-4 w-4" aria-hidden="true"></i>
              </button>
            </div>
            <p id="note-summary-text" class="font-reading text-base leading-relaxed text-content">
              <?= nl2br(htmlspecialchars($note->summary)) ?>
            </p>
          </section>
        <?php endif; ?>

        <!-- Body -->
        <?php if (!empty($note->sections)): ?>
          <div class="mt-10 space-y-10">
            <?php foreach ($note->sections as $idx => $sec):
              $heading = $sec['heading'] ?? 'Section';
              $body = $sec['content'] ?? '';
            ?>
              <section class="group" aria-labelledby="section-<?= $idx ?>">
                <div class="mb-3 flex flex-wrap items-center gap-2">
                  <h3 id="section-<?= $idx ?>" class="text-xl font-bold tracking-tight">
                    <?= htmlspecialchars($heading) ?>
                  </h3>
                  <span class="badge-neutral"><?= htmlspecialchars($sec['type'] ?? 'theory') ?></span>
                  <!--
                    Visible on focus as well as hover, so keyboard users can
                    reach it. Hover-only affordances are unusable without a mouse.
                  -->
                  <button type="button"
                          class="btn-icon btn-icon-compact opacity-0 transition-opacity focus-visible:opacity-100 group-hover:opacity-100"
                          aria-label="Ask Study Buddy about <?= htmlspecialchars($heading) ?>"
                          onclick="askAboutSection(<?= htmlspecialchars(json_encode($heading), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($body), ENT_QUOTES) ?>)">
                    <i data-lucide="message-circle-question" class="h-4 w-4" aria-hidden="true"></i>
                  </button>
                </div>
                <div class="prose-note"><?= nl2br(htmlspecialchars($body)) ?></div>
              </section>
            <?php endforeach; ?>
          </div>
        <?php elseif (!empty($note->rawContent)): ?>
          <div class="prose-note mt-10 whitespace-pre-wrap"><?= htmlspecialchars($note->rawContent) ?></div>
        <?php else: ?>
          <div class="mt-10 rounded-card border border-dashed border-line-strong p-8 text-center">
            <p class="text-sm font-semibold">This note has no content yet</p>
            <a href="/notepad?id=<?= $note->id ?>" class="btn-secondary mt-4">
              <i data-lucide="pen-line" class="h-4 w-4" aria-hidden="true"></i>
              Add content
            </a>
          </div>
        <?php endif; ?>

        <!-- Tags -->
        <?php if (!empty($note->tags)): ?>
          <footer class="mt-12 border-t border-line pt-6">
            <h3 class="section-label mb-3">Keywords</h3>
            <ul class="flex flex-wrap gap-2">
              <?php foreach ($note->tags as $tag): ?>
                <li><span class="badge-neutral">#<?= htmlspecialchars($tag) ?></span></li>
              <?php endforeach; ?>
            </ul>
          </footer>
        <?php endif; ?>
      </article>
    </div>

    <!-- ============ Study Buddy ============ -->
    <!-- Full-screen sheet under lg, docked side panel from lg up. -->
    <aside id="study-buddy-panel"
           hidden
           class="fixed inset-0 z-40 flex flex-col border-line bg-surface lg:static lg:z-auto lg:w-[24rem] lg:flex-shrink-0 lg:border-l"
           aria-labelledby="study-buddy-title">

      <div class="flex flex-shrink-0 items-center justify-between gap-2 border-b border-line px-4 py-3">
        <div class="flex min-w-0 items-center gap-2.5">
          <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
            <i data-lucide="sparkles" class="h-4 w-4" aria-hidden="true"></i>
          </span>
          <div class="min-w-0">
            <h2 id="study-buddy-title" class="text-sm font-bold leading-tight">Study Buddy</h2>
            <p class="truncate text-2xs font-medium normal-case tracking-normal text-content-subtle">
              Ask anything about this note
            </p>
          </div>
        </div>
        <button type="button" class="btn-icon flex-shrink-0" aria-label="Close Study Buddy"
                onclick="toggleStudyBuddy(document.getElementById('study-buddy-toggle'))">
          <i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i>
        </button>
      </div>

      <!--
        aria-live="polite" so assistant replies are announced as they arrive
        without yanking focus away from the input.
      -->
      <div id="buddy-messages"
           class="scrollbar-slim flex-1 space-y-3 overflow-y-auto bg-surface-sunken p-4"
           role="log"
           aria-live="polite"
           aria-label="Conversation with Study Buddy">
        <div class="rounded-card border border-line bg-surface p-4">
          <p class="text-sm font-semibold">Hi! I can help you study this note.</p>
          <p class="mt-1 text-2xs font-medium normal-case tracking-normal leading-relaxed text-content-muted">
            Ask for a simpler explanation, a worked example, or a step-by-step breakdown.
          </p>
          <div class="mt-3 flex flex-wrap gap-1.5">
            <button type="button" class="badge-brand transition-colors hover:bg-brand-100"
                    onclick="askSuggested('Explain this note in simple terms')">Explain simply</button>
            <button type="button" class="badge-brand transition-colors hover:bg-brand-100"
                    onclick="askSuggested('Give me a worked example')">Worked example</button>
            <button type="button" class="badge-brand transition-colors hover:bg-brand-100"
                    onclick="askSuggested('What are the key points to memorise?')">Key points</button>
          </div>
        </div>
      </div>

      <form id="buddy-form" class="flex-shrink-0 border-t border-line bg-surface p-3 pb-safe">
        <label for="buddy-input" class="sr-only">Ask Study Buddy a question</label>
        <div class="relative">
          <textarea id="buddy-input"
                    rows="2"
                    class="input-textarea pr-12"
                    placeholder="Ask a question…"
                    aria-describedby="buddy-input-hint"></textarea>
          <button type="submit" class="btn-icon absolute bottom-1.5 right-1.5 h-9 w-9 bg-brand-600 text-content-inverse hover:bg-brand-700 hover:text-content-inverse"
                  aria-label="Send question">
            <i data-lucide="arrow-up" class="h-4 w-4" aria-hidden="true"></i>
          </button>
        </div>
        <p id="buddy-input-hint" class="field-hint">Enter to send &middot; Shift + Enter for a new line</p>
      </form>
    </aside>
  </div>
</div>

<script>
  let activeSectionContext = '';

  const buddyPanel = document.getElementById('study-buddy-panel');
  const buddyToggle = document.getElementById('study-buddy-toggle');
  const buddyMessages = document.getElementById('buddy-messages');
  const buddyInput = document.getElementById('buddy-input');

  function isDockedLayout() {
    return window.matchMedia('(min-width: 1024px)').matches;
  }

  // Keep the trigger's aria-expanded truthful no matter what closed the panel
  // (button, close icon, or Escape via the dialog controller).
  new MutationObserver(() => {
    buddyToggle.setAttribute('aria-expanded', String(!buddyPanel.hidden));
  }).observe(buddyPanel, { attributes: true, attributeFilter: ['hidden'] });

  function toggleStudyBuddy(trigger) {
    // Below lg the panel covers the page, so it behaves as a modal dialog and
    // needs the focus trap. Docked beside the note it is just another region.
    const modal = !isDockedLayout();

    if (buddyPanel.hidden) {
      if (modal) {
        buddyPanel.setAttribute('role', 'dialog');
        buddyPanel.setAttribute('aria-modal', 'true');
        NoteNest.dialog.open('study-buddy-panel', trigger);
      } else {
        buddyPanel.removeAttribute('role');
        buddyPanel.removeAttribute('aria-modal');
        buddyPanel.hidden = false;
        buddyInput.focus();
      }
      return;
    }

    if (NoteNest.dialog.stack.some((entry) => entry.el === 'study-buddy-panel')) {
      NoteNest.dialog.close('study-buddy-panel');
    } else {
      buddyPanel.hidden = true;
    }
  }

  // Crossing the lg breakpoint changes what this panel *is*: a docked column
  // above it, a modal sheet below. Close it on the way across rather than
  // leaving a docked panel covering the whole screen with no scrim, or a
  // focus-trapped modal stranded in a layout that no longer needs one.
  window.matchMedia('(min-width: 1024px)').addEventListener('change', () => {
    if (buddyPanel.hidden) return;
    if (NoteNest.dialog.stack.some((entry) => entry.el === 'study-buddy-panel')) {
      NoteNest.dialog.close('study-buddy-panel');
    } else {
      buddyPanel.hidden = true;
    }
    buddyPanel.removeAttribute('role');
    buddyPanel.removeAttribute('aria-modal');
  });

  function openBuddy() {
    if (buddyPanel.hidden) toggleStudyBuddy(buddyToggle);
  }

  function askSuggested(question) {
    openBuddy();
    buddyInput.value = question;
    document.getElementById('buddy-form').requestSubmit();
  }

  function askAboutSection(heading, content) {
    activeSectionContext = content;
    openBuddy();
    buddyInput.value = 'Explain this section: "' + heading + '"';
    document.getElementById('buddy-form').requestSubmit();
  }

  function appendBubble(html, className) {
    const el = document.createElement('div');
    el.className = className;
    el.innerHTML = html;
    buddyMessages.appendChild(el);
    NoteNest.refreshIcons(el);
    buddyMessages.scrollTop = buddyMessages.scrollHeight;
    return el;
  }

  buddyInput.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      document.getElementById('buddy-form').requestSubmit();
    }
  });

  document.getElementById('buddy-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const question = buddyInput.value.trim();
    if (!question) return;
    buddyInput.value = '';

    appendBubble(
      '<p class="text-sm leading-relaxed">' + NoteNest.escapeHtml(question) + '</p>',
      'ml-auto max-w-[85%] rounded-card rounded-tr-sm bg-brand-600 px-3.5 py-2.5 text-content-inverse'
    );

    // Skeleton rather than a frozen panel, so the wait reads as progress.
    const pending = appendBubble(
      '<div class="flex items-center gap-2 text-2xs font-semibold uppercase tracking-wider text-content-subtle">' +
      '<i data-lucide="loader-circle" class="h-3.5 w-3.5 animate-spin" aria-hidden="true"></i> Thinking</div>' +
      '<div class="mt-2.5 space-y-2"><div class="skeleton h-3 w-full"></div>' +
      '<div class="skeleton h-3 w-11/12"></div><div class="skeleton h-3 w-3/5"></div></div>',
      'max-w-[92%] rounded-card border border-line bg-surface p-3.5'
    );

    try {
      const context = activeSectionContext ||
        (document.getElementById('note-summary-text')?.textContent ?? '');

      const data = await NoteNest.api('/api/ai/solve', {
        method: 'POST',
        body: { context, question },
      });

      pending.remove();
      const answer = data.response || 'No response.';
      const bubble = appendBubble(
        '<div class="mb-2 flex items-center justify-between gap-2">' +
        '<span class="section-label text-brand-700">Answer</span>' +
        '<button type="button" class="btn-icon btn-icon-compact" aria-label="Read answer aloud" data-speak>' +
        '<i data-lucide="volume-2" class="h-3.5 w-3.5" aria-hidden="true"></i></button></div>' +
        '<div class="whitespace-pre-wrap text-sm leading-relaxed text-content" data-answer>' +
        NoteNest.escapeHtml(answer) + '</div>',
        'max-w-[92%] animate-rise-in rounded-card border border-line bg-surface p-3.5 shadow-xs'
      );
      bubble.querySelector('[data-speak]').addEventListener('click', (e) =>
        NoteNest.speak(bubble.querySelector('[data-answer]').textContent, e.currentTarget)
      );
    } catch (e) {
      pending.remove();
      appendBubble(
        '<p class="flex items-center gap-2 text-sm font-medium text-danger">' +
        '<i data-lucide="circle-alert" class="h-4 w-4 flex-shrink-0" aria-hidden="true"></i>' +
        NoteNest.escapeHtml(e.message) + '</p>' +
        '<p class="mt-1 text-2xs font-medium text-content-muted">Check your connection and try again.</p>',
        'max-w-[92%] rounded-card border border-danger/30 bg-danger-soft p-3.5'
      );
    }
  });
</script>
