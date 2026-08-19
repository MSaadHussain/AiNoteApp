/**
 * NoteNest AI - flashcard and quiz engine.
 *
 * Everything rendered here originates from an AI response, so every
 * interpolated value goes through esc() before it reaches innerHTML.
 */

const esc = (value) => {
  const div = document.createElement('div');
  div.textContent = value == null ? '' : String(value);
  return div.innerHTML;
};

class StudyEngine {
  constructor() {
    this.flashcards = [];
    this.currentCardIndex = 0;
    this.isCardFlipped = false;

    this.quizQuestions = [];
    this.userAnswers = {};
    this.quizSubmitted = false;
  }

  /* ==================== Flashcards ==================== */

  initFlashcards(cards) {
    this.flashcards = cards || [];
    this.currentCardIndex = 0;
    this.isCardFlipped = false;
    this.renderCard();
  }

  flipCard() {
    this.isCardFlipped = !this.isCardFlipped;
    const inner = document.getElementById('flashcard-inner');
    const trigger = document.getElementById('flashcard-trigger');
    if (inner) inner.classList.toggle('rotate-y-180', this.isCardFlipped);
    if (trigger) {
      trigger.setAttribute('aria-pressed', String(this.isCardFlipped));
      trigger.setAttribute(
        'aria-label',
        this.isCardFlipped ? 'Showing the answer. Activate to see the question.'
                           : 'Showing the question. Activate to reveal the answer.'
      );
    }
    this.announce(this.isCardFlipped ? 'Answer shown' : 'Question shown');
  }

  nextCard() {
    if (this.currentCardIndex < this.flashcards.length - 1) {
      this.currentCardIndex += 1;
      this.isCardFlipped = false;
      this.renderCard();
    }
  }

  prevCard() {
    if (this.currentCardIndex > 0) {
      this.currentCardIndex -= 1;
      this.isCardFlipped = false;
      this.renderCard();
    }
  }

  renderCard() {
    const card = this.flashcards[this.currentCardIndex];
    if (!card) return;

    const inner = document.getElementById('flashcard-inner');
    const trigger = document.getElementById('flashcard-trigger');
    if (inner) inner.classList.remove('rotate-y-180');
    if (trigger) {
      trigger.setAttribute('aria-pressed', 'false');
      trigger.setAttribute('aria-label', 'Showing the question. Activate to reveal the answer.');
    }

    const front = document.getElementById('card-front-text');
    const back = document.getElementById('card-back-text');
    if (front) front.textContent = card.front;
    if (back) back.textContent = card.back;

    const counter = document.getElementById('card-counter');
    if (counter) {
      counter.textContent = (this.currentCardIndex + 1) + ' of ' + this.flashcards.length;
    }

    const progress = document.getElementById('card-progress');
    if (progress) {
      const pct = ((this.currentCardIndex + 1) / this.flashcards.length) * 100;
      progress.style.width = pct + '%';
      progress.parentElement.setAttribute('aria-valuenow', String(this.currentCardIndex + 1));
      progress.parentElement.setAttribute('aria-valuemax', String(this.flashcards.length));
    }

    const prev = document.getElementById('btn-prev-card');
    const next = document.getElementById('btn-next-card');
    if (prev) prev.disabled = this.currentCardIndex === 0;
    if (next) next.disabled = this.currentCardIndex === this.flashcards.length - 1;

    this.announce('Card ' + (this.currentCardIndex + 1) + ' of ' + this.flashcards.length);
  }

  /** Politely announce state changes for screen reader users. */
  announce(message) {
    const region = document.getElementById('study-announcer');
    if (region) region.textContent = message;
  }

  /* ==================== Quiz ==================== */

  initQuiz(questions) {
    this.quizQuestions = questions || [];
    this.userAnswers = {};
    this.quizSubmitted = false;
    this.renderQuiz();
  }

  selectOption(questionId, optionIndex) {
    if (this.quizSubmitted) return;
    this.userAnswers[questionId] = optionIndex;
    this.updateSubmitState();
  }

  updateSubmitState() {
    const submit = document.getElementById('quiz-submit');
    if (!submit) return;
    const answered = Object.keys(this.userAnswers).length;
    const total = this.quizQuestions.length;
    submit.disabled = answered < total;
    const remaining = document.getElementById('quiz-remaining');
    if (remaining) {
      remaining.textContent = answered < total
        ? (total - answered) + ' question' + (total - answered === 1 ? '' : 's') + ' left'
        : 'All questions answered';
    }
  }

  submitQuiz() {
    this.quizSubmitted = true;
    this.renderQuiz();
    const result = document.getElementById('quiz-result');
    if (result) {
      result.focus();
      result.scrollIntoView({ block: 'start' });
    }
  }

  restartQuiz() {
    this.userAnswers = {};
    this.quizSubmitted = false;
    this.renderQuiz();
    document.getElementById('quiz-container')?.scrollIntoView({ block: 'start' });
  }

  calculateScore() {
    return this.quizQuestions.reduce(
      (score, q) => score + (this.userAnswers[q.id] === q.correctAnswer ? 1 : 0),
      0
    );
  }

  renderQuiz() {
    const container = document.getElementById('quiz-container');
    if (!container) return;

    const total = this.quizQuestions.length;
    let html = '';

    /* ---- Result banner ---- */
    if (this.quizSubmitted) {
      const score = this.calculateScore();
      const pct = total ? Math.round((score / total) * 100) : 0;
      // Full class names, never string-concatenated - a purging build can only
      // keep classes it can see written out in the source.
      const toneBar = pct >= 80 ? 'bg-success' : pct >= 50 ? 'bg-warning' : 'bg-danger';

      html +=
        '<div id="quiz-result" tabindex="-1" role="status" ' +
        'class="mb-8 rounded-card border border-line bg-surface p-6 shadow-card focus:outline-none">' +
          '<div class="flex flex-wrap items-center justify-between gap-4">' +
            '<div>' +
              '<p class="section-label">Your score</p>' +
              '<p class="mt-1 text-3xl font-extrabold tabular-nums">' +
                score + ' <span class="text-content-subtle">/ ' + total + '</span>' +
              '</p>' +
              '<p class="mt-1 text-sm text-content-muted">' +
                (pct >= 80 ? 'Strong result - you know this material.'
                           : pct >= 50 ? 'A solid start. Review the ones you missed.'
                                       : 'Worth another pass through the note.') +
              '</p>' +
            '</div>' +
            '<button type="button" class="btn-secondary" onclick="studyEngine.restartQuiz()">' +
              '<i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i> Retake quiz' +
            '</button>' +
          '</div>' +
          // Progress bar carries a text label too - never colour alone.
          '<div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-surface-sunken" ' +
               'role="progressbar" aria-valuenow="' + pct + '" aria-valuemin="0" aria-valuemax="100" ' +
               'aria-label="Score percentage">' +
            '<div class="h-full rounded-full ' + toneBar + '" style="width:' + pct + '%"></div>' +
          '</div>' +
        '</div>';
    }

    /* ---- Questions ---- */
    this.quizQuestions.forEach((q, idx) => {
      const selected = this.userAnswers[q.id];
      const answeredCorrectly = selected === q.correctAnswer;

      let frame = 'border-line bg-surface';
      if (this.quizSubmitted) {
        frame = answeredCorrectly
          ? 'border-success/40 bg-success-soft'
          : 'border-danger/40 bg-danger-soft';
      }

      html +=
        '<fieldset class="mb-5 rounded-card border ' + frame + ' p-5 shadow-xs">' +
          '<legend class="flex items-start gap-2 px-1 text-base font-bold">' +
            '<span class="text-brand-600">Q' + (idx + 1) + '.</span>' +
            '<span>' + esc(q.question) + '</span>' +
          '</legend>';

      if (this.quizSubmitted) {
        html +=
          '<p class="mb-3 mt-2 flex items-center gap-1.5 text-2xs font-bold uppercase tracking-wider ' +
          (answeredCorrectly ? 'text-success' : 'text-danger') + '">' +
            '<i data-lucide="' + (answeredCorrectly ? 'circle-check' : 'circle-x') +
            '" class="h-3.5 w-3.5" aria-hidden="true"></i>' +
            (answeredCorrectly ? 'Correct' : 'Incorrect') +
          '</p>';
      }

      html += '<div class="mt-3 space-y-2">';

      q.options.forEach((opt, optIdx) => {
        const inputId = 'q-' + esc(q.id) + '-opt-' + optIdx;
        const isCorrect = optIdx === q.correctAnswer;
        const isChosen = selected === optIdx;

        let optionClass = 'border-line bg-surface hover:border-brand-300 hover:bg-brand-50';
        let marker = '';

        if (this.quizSubmitted) {
          if (isCorrect) {
            optionClass = 'border-success/50 bg-success-soft';
            marker = '<span class="badge-success flex-shrink-0">Correct answer</span>';
          } else if (isChosen) {
            optionClass = 'border-danger/50 bg-danger-soft';
            marker = '<span class="badge-danger flex-shrink-0">Your answer</span>';
          } else {
            optionClass = 'border-line bg-surface-sunken opacity-70';
          }
        } else if (isChosen) {
          optionClass = 'border-brand-500 bg-brand-50 ring-1 ring-brand-200';
        }

        // A real radio input: arrow keys move between options, and the group
        // is announced as "N of M" by assistive tech - neither is true of
        // a pile of <button> elements.
        html +=
          '<label for="' + inputId + '" ' +
                 'class="flex min-h-[2.75rem] cursor-pointer items-center gap-3 rounded-control border px-4 py-3 ' +
                 'text-sm transition-colors duration-200 ' + optionClass +
                 (this.quizSubmitted ? ' cursor-default' : '') + '">' +
            '<input type="radio" id="' + inputId + '" name="q-' + esc(q.id) + '" ' +
                   'value="' + optIdx + '"' +
                   (isChosen ? ' checked' : '') +
                   (this.quizSubmitted ? ' disabled' : '') +
                   ' class="h-4 w-4 flex-shrink-0 border-line-strong text-brand-600 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2" ' +
                   'onchange="studyEngine.selectOption(\'' + esc(q.id) + '\', ' + optIdx + ')">' +
            '<span class="flex-1">' + esc(opt) + '</span>' +
            marker +
          '</label>';
      });

      html += '</div>';

      if (this.quizSubmitted && q.explanation) {
        html +=
          '<div class="mt-4 rounded-control border border-line bg-surface p-3.5">' +
            '<p class="section-label mb-1.5">Why</p>' +
            '<p class="text-sm leading-relaxed text-content-muted">' + esc(q.explanation) + '</p>' +
          '</div>';
      }

      html += '</fieldset>';
    });

    /* ---- Submit ---- */
    if (!this.quizSubmitted) {
      html +=
        '<div class="sticky bottom-0 -mx-1 flex flex-wrap items-center justify-between gap-3 ' +
             'border-t border-line bg-surface/95 px-1 py-4 backdrop-blur">' +
          '<p id="quiz-remaining" class="text-2xs font-semibold uppercase tracking-wider text-content-subtle"></p>' +
          '<button type="button" id="quiz-submit" class="btn-primary" onclick="studyEngine.submitQuiz()" disabled>' +
            'Submit answers' +
          '</button>' +
        '</div>';
    }

    container.innerHTML = html;
    if (window.NoteNest) window.NoteNest.refreshIcons(container);
    else if (window.lucide) window.lucide.createIcons();

    this.updateSubmitState();
  }
}

window.studyEngine = new StudyEngine();

/* Keyboard support for the flashcard deck: arrows to move, space/enter to flip. */
document.addEventListener('keydown', (event) => {
  const deck = document.getElementById('study-mode-flashcards');
  if (!deck || deck.hidden) return;

  const tag = document.activeElement?.tagName;
  if (tag === 'INPUT' || tag === 'TEXTAREA') return;

  if (event.key === 'ArrowRight') {
    event.preventDefault();
    window.studyEngine.nextCard();
  } else if (event.key === 'ArrowLeft') {
    event.preventDefault();
    window.studyEngine.prevCard();
  }
});
