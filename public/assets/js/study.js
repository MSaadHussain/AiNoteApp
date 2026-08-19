/**
 * NoteNest AI - Flashcard & Quiz Interactive Engine
 */

class StudyEngine {
  constructor() {
    this.flashcards = [];
    this.currentCardIndex = 0;
    this.isCardFlipped = false;

    this.quizQuestions = [];
    this.userAnswers = {};
    this.quizSubmitted = false;
  }

  // --- Flashcards ---
  initFlashcards(cards) {
    this.flashcards = cards || [];
    this.currentCardIndex = 0;
    this.isCardFlipped = false;
    this.renderCard();
  }

  flipCard() {
    this.isCardFlipped = !this.isCardFlipped;
    const cardEl = document.getElementById('flashcard-inner');
    if (cardEl) {
      if (this.isCardFlipped) {
        cardEl.classList.add('rotate-y-180');
      } else {
        cardEl.classList.remove('rotate-y-180');
      }
    }
  }

  nextCard() {
    if (this.currentCardIndex < this.flashcards.length - 1) {
      this.currentCardIndex++;
      this.isCardFlipped = false;
      this.renderCard();
    }
  }

  prevCard() {
    if (this.currentCardIndex > 0) {
      this.currentCardIndex--;
      this.isCardFlipped = false;
      this.renderCard();
    }
  }

  renderCard() {
    const card = this.flashcards[this.currentCardIndex];
    if (!card) return;

    const frontEl = document.getElementById('card-front-text');
    const backEl = document.getElementById('card-back-text');
    const counterEl = document.getElementById('card-counter');
    const cardEl = document.getElementById('flashcard-inner');

    if (cardEl) cardEl.classList.remove('rotate-y-180');
    if (frontEl) frontEl.textContent = card.front;
    if (backEl) backEl.textContent = card.back;
    if (counterEl) counterEl.textContent = `${this.currentCardIndex + 1} / ${this.flashcards.length}`;

    const prevBtn = document.getElementById('btn-prev-card');
    const nextBtn = document.getElementById('btn-next-card');
    if (prevBtn) prevBtn.disabled = this.currentCardIndex === 0;
    if (nextBtn) nextBtn.disabled = this.currentCardIndex === this.flashcards.length - 1;
  }

  // --- Quiz ---
  initQuiz(questions) {
    this.quizQuestions = questions || [];
    this.userAnswers = {};
    this.quizSubmitted = false;
    this.renderQuiz();
  }

  selectOption(questionId, optionIndex) {
    if (this.quizSubmitted) return;
    this.userAnswers[questionId] = optionIndex;
    this.renderQuiz();
  }

  submitQuiz() {
    this.quizSubmitted = true;
    this.renderQuiz();
  }

  calculateScore() {
    let score = 0;
    this.quizQuestions.forEach(q => {
      if (this.userAnswers[q.id] === q.correctAnswer) {
        score++;
      }
    });
    return score;
  }

  renderQuiz() {
    const container = document.getElementById('quiz-container');
    if (!container) return;

    let html = '';

    if (this.quizSubmitted) {
      const score = this.calculateScore();
      html += `
        <div class="bg-indigo-600 text-white p-6 rounded-2xl mb-8 flex items-center justify-between shadow-lg animate-slide-down">
          <div>
            <h4 class="text-2xl font-bold mb-1">Score: ${score} / ${this.quizQuestions.length}</h4>
            <p class="text-indigo-200">${score === this.quizQuestions.length ? 'Perfect score! Excellent work.' : 'Great job practicing!'}</p>
          </div>
          <button onclick="studyEngine.restartQuiz()" class="bg-white text-indigo-600 px-4 py-2 rounded-lg font-bold text-sm hover:bg-indigo-50">
            Retake Quiz
          </button>
        </div>
      `;
    }

    this.quizQuestions.forEach((q, idx) => {
      const selected = this.userAnswers[q.id];
      const isCorrect = selected === q.correctAnswer;

      let cardBorder = 'border-stone-200 bg-white';
      if (this.quizSubmitted) {
        cardBorder = isCorrect ? 'border-green-300 bg-green-50/40' : 'border-red-300 bg-red-50/40';
      }

      html += `
        <div class="p-6 rounded-2xl border-2 ${cardBorder} mb-6 shadow-sm">
          <h4 class="font-bold text-lg text-stone-800 mb-4 flex gap-3">
            <span class="text-indigo-600">Q${idx + 1}.</span>
            <span>${q.question}</span>
          </h4>
          <div class="space-y-3">
      `;

      q.options.forEach((opt, optIdx) => {
        let optClass = 'border-stone-200 hover:border-indigo-300 hover:bg-stone-50 bg-white text-stone-700';

        if (this.quizSubmitted) {
          if (optIdx === q.correctAnswer) {
            optClass = 'bg-green-100 border-green-500 text-green-800 font-bold';
          } else if (selected === optIdx) {
            optClass = 'bg-red-100 border-red-500 text-red-800 line-through';
          } else {
            optClass = 'bg-stone-50 border-stone-100 text-stone-400 opacity-60';
          }
        } else if (selected === optIdx) {
          optClass = 'bg-indigo-50 border-indigo-500 text-indigo-800 font-bold';
        }

        html += `
          <button
            type="button"
            onclick="studyEngine.selectOption('${q.id}', ${optIdx})"
            ${this.quizSubmitted ? 'disabled' : ''}
            class="w-full text-left p-4 rounded-xl border transition-all flex justify-between items-center ${optClass}"
          >
            <span>${opt}</span>
            ${this.quizSubmitted && optIdx === q.correctAnswer ? '<i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>' : ''}
            ${this.quizSubmitted && selected === optIdx && optIdx !== q.correctAnswer ? '<i data-lucide="x-circle" class="w-5 h-5 text-red-500"></i>' : ''}
          </button>
        `;
      });

      html += `</div>`;

      if (this.quizSubmitted && q.explanation) {
        html += `
          <div class="mt-4 p-3 bg-white/70 rounded-lg text-sm text-stone-600 border border-stone-200">
            <strong class="text-stone-800">Explanation:</strong> ${q.explanation}
          </div>
        `;
      }

      html += `</div>`;
    });

    if (!this.quizSubmitted) {
      const allAnswered = Object.keys(this.userAnswers).length === this.quizQuestions.length;
      html += `
        <div class="flex justify-center mt-8 mb-12">
          <button
            type="button"
            onclick="studyEngine.submitQuiz()"
            ${!allAnswered ? 'disabled' : ''}
            class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 disabled:opacity-50 transition-all text-lg"
          >
            Submit Quiz
          </button>
        </div>
      `;
    }

    container.innerHTML = html;
    if (window.lucide) lucide.createIcons();
  }

  restartQuiz() {
    this.userAnswers = {};
    this.quizSubmitted = false;
    this.renderQuiz();
  }
}

window.studyEngine = new StudyEngine();
