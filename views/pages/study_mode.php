<?php
/**
 * Study Center - pick a note, then drill it with AI flashcards or a quiz.
 */
require_once dirname(__DIR__) . '/partials/helpers.php';

$noteList = $notes ?? [];
?>

<script src="/assets/js/study.js" defer></script>

<!-- Shared live region for flashcard/quiz state changes. -->
<div id="study-announcer" class="sr-only" role="status" aria-live="polite"></div>

<div class="h-full overflow-y-auto scrollbar-slim">

  <!-- One h1 for the page; each mode below is a section heading. -->
  <h1 class="sr-only">Study Center</h1>

  <!-- ==================== Pick a note ==================== -->
  <section id="study-mode-select" class="mx-auto max-w-5xl px-4 pb-40 pt-8 sm:px-6">
    <header class="mb-8">
      <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Study Center</h2>
      <p class="mt-1.5 text-sm text-content-muted">
        Choose a note and NoteNest will build flashcards or a practice quiz from it.
      </p>
    </header>

    <?php if (empty($noteList)): ?>
      <div class="card flex flex-col items-center px-6 py-12 text-center">
        <span class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-600">
          <i data-lucide="graduation-cap" class="h-6 w-6" aria-hidden="true"></i>
        </span>
        <h3 class="text-base font-bold">Nothing to study yet</h3>
        <p class="mx-auto mt-1.5 max-w-sm text-sm text-content-muted">
          Record a lecture or write a note first &mdash; study materials are generated from your own notes.
        </p>
        <div class="mt-5 flex flex-wrap justify-center gap-2">
          <a href="/recorder" class="btn-primary">
            <i data-lucide="mic" class="h-4 w-4" aria-hidden="true"></i> Record a lecture
          </a>
          <a href="/notepad" class="btn-secondary">
            <i data-lucide="pen-line" class="h-4 w-4" aria-hidden="true"></i> Write a note
          </a>
        </div>
      </div>
    <?php else: ?>
      <h3 class="section-label mb-4" id="study-notes-heading">Select a note</h3>

      <!-- Radiogroup: exactly one note can be selected, and arrow keys work. -->
      <div role="radiogroup" aria-labelledby="study-notes-heading"
           class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($noteList as $i => $n):
          $meta = note_type_meta($n->type);
          $inputId = 'study-note-' . $n->id;
        ?>
          <div class="animate-rise-in" style="animation-delay: <?= min($i * 40, 240) ?>ms">
            <input type="radio"
                   name="study-note"
                   id="<?= $inputId ?>"
                   class="peer sr-only"
                   value="<?= $n->id ?>"
                   aria-label="<?= htmlspecialchars($n->title) ?>, <?= htmlspecialchars($n->subject) ?>, <?= $meta['label'] ?>"
                   data-title="<?= htmlspecialchars($n->title) ?>"
                   data-content="<?= htmlspecialchars(trim($n->summary . ' ' . $n->rawContent)) ?>"
                   onchange="onStudyNoteSelected(this)">
            <label for="<?= $inputId ?>"
                   class="card flex h-full cursor-pointer flex-col p-4 transition-[border-color,box-shadow] duration-200 hover:border-brand-300 hover:shadow-raised peer-checked:border-brand-500 peer-checked:ring-2 peer-checked:ring-brand-200 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 peer-focus-visible:ring-offset-2">
              <div class="flex items-start justify-between gap-2">
                <span class="<?= $meta['badge'] ?>">
                  <i data-lucide="<?= $meta['icon'] ?>" class="h-3 w-3" aria-hidden="true"></i>
                  <?= $meta['label'] ?>
                </span>
                <!-- Check mark appears only when selected: a second, non-colour cue. -->
                <span class="selected-check text-brand-600" aria-hidden="true">
                  <i data-lucide="circle-check-big" class="h-5 w-5"></i>
                </span>
              </div>
              <span class="mt-2.5 block text-sm font-bold leading-snug"><?= htmlspecialchars($n->title) ?></span>
              <span class="mt-auto block pt-3 text-2xs font-semibold normal-case tracking-normal text-content-subtle">
                <?= htmlspecialchars($n->subject) ?> &middot; <?= htmlspecialchars($n->date) ?>
              </span>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- ==================== Flashcards ==================== -->
  <section id="study-mode-flashcards" hidden class="flex h-full flex-col">
    <header class="flex flex-shrink-0 items-center justify-between gap-3 border-b border-line bg-surface px-4 py-3">
      <h2 id="fc-header-title" class="min-w-0 truncate text-sm font-bold">Flashcards</h2>
      <button type="button" class="btn-ghost" onclick="closeStudyActivity()">
        <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
        Close
      </button>
    </header>

    <div class="flex min-h-0 flex-1 flex-col items-center justify-center gap-6 p-4 sm:p-8">
      <!-- Card -->
      <button type="button"
              id="flashcard-trigger"
              class="perspective-1000 h-72 w-full max-w-2xl sm:h-80"
              aria-pressed="false"
              aria-label="Showing the question. Activate to reveal the answer."
              onclick="studyEngine.flipCard()">
        <div id="flashcard-inner"
             class="transform-style-3d relative h-full w-full rounded-card shadow-raised transition-transform duration-500">

          <div class="backface-hidden absolute inset-0 flex flex-col items-center justify-center rounded-card border border-line bg-surface p-8 text-center">
            <span class="section-label mb-4 text-brand-700">Question</span>
            <p id="card-front-text" class="text-xl font-semibold leading-relaxed sm:text-2xl"></p>
            <span class="absolute bottom-5 text-2xs font-medium normal-case tracking-normal text-content-subtle">
              Click or press Enter to flip
            </span>
          </div>

          <div class="backface-hidden rotate-y-180 absolute inset-0 flex flex-col items-center justify-center rounded-card bg-brand-600 p-8 text-center text-content-inverse">
            <span class="mb-4 text-2xs font-bold uppercase tracking-widest text-brand-200">Answer</span>
            <p id="card-back-text" class="text-lg leading-relaxed sm:text-xl"></p>
          </div>
        </div>
      </button>

      <!-- Progress -->
      <div class="w-full max-w-2xl">
        <div class="h-1.5 w-full overflow-hidden rounded-full bg-surface-sunken"
             role="progressbar" aria-valuemin="1" aria-valuenow="1" aria-valuemax="1"
             aria-label="Flashcard progress">
          <div id="card-progress" class="h-full rounded-full bg-brand-500 transition-[width] duration-300" style="width:0%"></div>
        </div>
      </div>

      <!-- Controls -->
      <div class="flex items-center gap-3">
        <button type="button" id="btn-prev-card" class="btn-secondary" onclick="studyEngine.prevCard()">
          <i data-lucide="chevron-left" class="h-4 w-4" aria-hidden="true"></i>
          <span class="sr-only sm:not-sr-only">Previous</span>
        </button>
        <p id="card-counter" class="min-w-[5.5rem] text-center text-2xs font-bold uppercase tracking-wider tabular-nums text-content-muted"></p>
        <button type="button" id="btn-next-card" class="btn-secondary" onclick="studyEngine.nextCard()">
          <span class="sr-only sm:not-sr-only">Next</span>
          <i data-lucide="chevron-right" class="h-4 w-4" aria-hidden="true"></i>
        </button>
      </div>

      <p class="text-2xs font-medium normal-case tracking-normal text-content-subtle">
        Use <kbd class="rounded border border-line bg-surface px-1 font-sans font-bold">&larr;</kbd>
        and <kbd class="rounded border border-line bg-surface px-1 font-sans font-bold">&rarr;</kbd> to move between cards
      </p>
    </div>
  </section>

  <!-- ==================== Quiz ==================== -->
  <section id="study-mode-quiz" hidden class="flex h-full flex-col">
    <header class="flex flex-shrink-0 items-center justify-between gap-3 border-b border-line bg-surface px-4 py-3">
      <h2 id="quiz-header-title" class="min-w-0 truncate text-sm font-bold">Quiz</h2>
      <button type="button" class="btn-ghost" onclick="closeStudyActivity()">
        <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
        Exit quiz
      </button>
    </header>

    <div class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-6">
      <div id="quiz-container" class="mx-auto max-w-3xl pb-10"></div>
    </div>
  </section>
</div>

<!-- ==================== Sticky action bar ==================== -->
<!-- bottom-14 on mobile clears the bottom tab bar. -->
<div id="study-action-bar" hidden
     class="fixed inset-x-0 bottom-14 z-30 border-t border-line bg-surface/95 p-3 backdrop-blur md:bottom-0">
  <div class="mx-auto flex max-w-5xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <p class="min-w-0 truncate text-2xs font-semibold uppercase tracking-wider text-content-subtle">
      Selected: <span id="study-selected-title" class="text-content"></span>
    </p>
    <div class="flex gap-2">
      <button type="button" id="btn-gen-flashcards" class="btn-primary flex-1 sm:flex-none" onclick="generateFlashcards(this)">
        <i data-lucide="layers" class="h-4 w-4" aria-hidden="true"></i>
        Flashcards
      </button>
      <button type="button" id="btn-gen-quiz" class="btn-secondary flex-1 sm:flex-none" onclick="generateQuiz(this)">
        <i data-lucide="list-checks" class="h-4 w-4" aria-hidden="true"></i>
        Quiz
      </button>
    </div>
  </div>
</div>

<script>
  let selectedNote = { id: '', title: '', content: '' };

  const panes = {
    select: document.getElementById('study-mode-select'),
    flashcards: document.getElementById('study-mode-flashcards'),
    quiz: document.getElementById('study-mode-quiz'),
  };
  const actionBar = document.getElementById('study-action-bar');

  function onStudyNoteSelected(input) {
    selectedNote = {
      id: input.value,
      title: input.dataset.title,
      content: input.dataset.content,
    };
    document.getElementById('study-selected-title').textContent = selectedNote.title;
    actionBar.hidden = false;
  }

  function showPane(name) {
    Object.entries(panes).forEach(([key, el]) => { el.hidden = key !== name; });
    actionBar.hidden = name !== 'select' || !selectedNote.id;
  }

  function closeStudyActivity() {
    showPane('select');
    document.querySelector('input[name="study-note"]:checked')?.focus();
  }

  async function generate(button, endpoint, busyLabel, onSuccess) {
    if (!selectedNote.content) {
      NoteNest.toast('Pick a note first', 'error', 'Select the note you want to study.');
      return;
    }

    await NoteNest.withBusy(button, busyLabel, async () => {
      try {
        const data = await NoteNest.api(endpoint, {
          method: 'POST',
          body: { content: selectedNote.content },
        });
        onSuccess(data);
      } catch (e) {
        NoteNest.toast('Could not build study materials', 'error', e.message);
      }
    });
  }

  function generateFlashcards(button) {
    return generate(button, '/api/ai/flashcards', 'Building…', (data) => {
      if (!data.flashcards || data.flashcards.length === 0) {
        NoteNest.toast('No flashcards generated', 'error', 'This note may be too short. Try a longer one.');
        return;
      }
      document.getElementById('fc-header-title').textContent = selectedNote.title + ' · Flashcards';
      showPane('flashcards');
      studyEngine.initFlashcards(data.flashcards);
      document.getElementById('flashcard-trigger').focus();
    });
  }

  function generateQuiz(button) {
    return generate(button, '/api/ai/quiz', 'Building…', (data) => {
      if (!data.questions || data.questions.length === 0) {
        NoteNest.toast('No quiz generated', 'error', 'This note may be too short. Try a longer one.');
        return;
      }
      document.getElementById('quiz-header-title').textContent = selectedNote.title + ' · Quiz';
      showPane('quiz');
      studyEngine.initQuiz(data.questions);
    });
  }
</script>
