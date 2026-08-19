<?php
/**
 * StudyMode View - AI Flashcards with 3D Flip Card & Interactive Quiz Center
 */
?>

<!-- Include Study Engine -->
<script src="/assets/js/study.js"></script>

<div class="h-full overflow-y-auto bg-stone-50 relative">
  
  <!-- ===== SELECT MODE ===== -->
  <div id="study-mode-select" class="p-8 h-full">
    <a href="/" class="mb-6 text-sm text-stone-500 hover:text-stone-800 flex items-center gap-1 inline-block">
      &larr; Back to Dashboard
    </a>

    <div class="max-w-4xl mx-auto text-center mb-12">
      <h2 class="text-3xl font-bold text-stone-900 mb-3 flex items-center justify-center gap-3">
        <i data-lucide="zap" class="w-8 h-8 text-yellow-500 fill-yellow-500"></i>
        Study Center
      </h2>
      <p class="text-stone-500">Select a lecture note to generate AI-powered study materials.</p>
    </div>

    <?php if (empty($notes)): ?>
      <div class="text-center text-stone-400 mt-20 font-hand text-2xl">
        No notes available to study yet. Record or write a note first!
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto mb-28">
        <?php foreach ($notes as $n): ?>
          <div
            id="study-card-<?= $n->id ?>"
            onclick="selectStudyNote('<?= $n->id ?>', '<?= htmlspecialchars(addslashes($n->title)) ?>', <?= json_encode($n->summary . ' ' . $n->rawContent) ?>)"
            class="study-note-card cursor-pointer p-6 rounded-2xl border-2 border-stone-200 bg-white hover:border-indigo-400 hover:shadow-md transition-all"
          >
            <span class="text-[10px] font-bold uppercase tracking-wider bg-white border border-stone-200 text-stone-600 px-2 py-1 rounded mb-3 inline-block">
              <?= htmlspecialchars($n->subject) ?>
            </span>
            <h3 class="font-bold text-stone-800 mb-2 truncate text-lg"><?= htmlspecialchars($n->title) ?></h3>
            <p class="text-xs text-stone-500"><?= htmlspecialchars($n->date) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Bottom Action Bar (Fixed when note is selected) -->
    <div id="study-bottom-bar" class="hidden fixed bottom-0 left-0 md:left-72 right-0 p-6 bg-white/95 backdrop-blur-md border-t border-stone-200 flex justify-center gap-4 animate-slide-up shadow-2xl z-20">
      <button
        id="btn-gen-flashcards"
        onclick="generateFlashcards()"
        class="flex items-center gap-2 px-8 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-base md:text-lg shadow-lg shadow-indigo-200 transition-all active:scale-95"
      >
        <i data-lucide="book-open" class="w-5 h-5"></i>
        Generate Flashcards
      </button>

      <button
        id="btn-gen-quiz"
        onclick="generateQuiz()"
        class="flex items-center gap-2 px-8 py-3.5 bg-white border-2 border-stone-200 hover:border-indigo-500 text-stone-700 hover:text-indigo-600 rounded-xl font-bold text-base md:text-lg transition-all active:scale-95"
      >
        <i data-lucide="brain-circuit" class="w-5 h-5"></i>
        Take Quiz
      </button>
    </div>
  </div>

  <!-- ===== FLASHCARDS MODE ===== -->
  <div id="study-mode-flashcards" class="hidden flex-col h-full bg-stone-100 animate-fade-in">
    <header class="p-6 flex justify-between items-center bg-white shadow-sm z-10">
      <h3 id="fc-header-title" class="font-bold text-lg text-stone-700">Lecture Flashcards</h3>
      <button onclick="closeStudyActivity()" class="text-sm font-medium text-stone-500 hover:text-stone-800">
        Close
      </button>
    </header>

    <div class="flex-1 flex flex-col items-center justify-center p-8">
      <!-- 3D Card Box -->
      <div class="perspective-1000 w-full max-w-2xl h-96 cursor-pointer group" onclick="studyEngine.flipCard()">
        <div id="flashcard-inner" class="relative w-full h-full transition-transform duration-500 transform-style-3d shadow-xl rounded-3xl">
          
          <!-- Front (Question) -->
          <div class="absolute inset-0 backface-hidden bg-white rounded-3xl flex flex-col items-center justify-center p-12 text-center border border-stone-200">
            <span class="text-xs uppercase font-bold text-indigo-500 mb-4 tracking-widest">Question</span>
            <p id="card-front-text" class="text-2xl font-medium text-stone-800 leading-relaxed"></p>
            <p class="absolute bottom-6 text-xs text-stone-400">Click anywhere to flip</p>
          </div>

          <!-- Back (Answer) -->
          <div class="absolute inset-0 backface-hidden bg-indigo-600 text-white rounded-3xl flex flex-col items-center justify-center p-12 text-center rotate-y-180">
            <span class="text-xs uppercase font-bold text-indigo-200 mb-4 tracking-widest">Answer</span>
            <p id="card-back-text" class="text-xl leading-relaxed font-sans"></p>
          </div>
        </div>
      </div>

      <!-- Navigation Arrows -->
      <div class="flex items-center gap-6 mt-8">
        <button
          id="btn-prev-card"
          onclick="studyEngine.prevCard()"
          class="p-3 rounded-full bg-white shadow-md hover:bg-stone-50 disabled:opacity-40 text-stone-600"
        >
          &larr; Prev
        </button>
        <span id="card-counter" class="font-bold text-stone-500 text-sm">1 / 10</span>
        <button
          id="btn-next-card"
          onclick="studyEngine.nextCard()"
          class="p-3 rounded-full bg-white shadow-md hover:bg-stone-50 disabled:opacity-40 text-stone-600"
        >
          Next &rarr;
        </button>
      </div>
    </div>
  </div>

  <!-- ===== QUIZ MODE ===== -->
  <div id="study-mode-quiz" class="hidden flex-col h-full bg-stone-50 animate-fade-in">
    <header class="p-6 flex justify-between items-center bg-white shadow-sm z-10">
      <h3 id="quiz-header-title" class="font-bold text-lg text-stone-700">Lecture Quiz</h3>
      <button onclick="closeStudyActivity()" class="text-sm font-medium text-stone-500 hover:text-stone-800">
        Exit Quiz
      </button>
    </header>

    <div class="flex-1 overflow-y-auto p-8 max-w-3xl mx-auto w-full">
      <div id="quiz-container">
        <!-- Quiz questions rendered dynamically by StudyEngine -->
      </div>
    </div>
  </div>
</div>

<script>
  let selectedNoteContext = { id: '', title: '', content: '' };

  function selectStudyNote(id, title, content) {
    selectedNoteContext = { id, title, content };

    document.querySelectorAll('.study-note-card').forEach(el => {
      el.className = 'study-note-card cursor-pointer p-6 rounded-2xl border-2 border-stone-200 bg-white hover:border-indigo-400 hover:shadow-md transition-all';
    });

    const activeCard = document.getElementById('study-card-' + id);
    if (activeCard) {
      activeCard.className = 'study-note-card cursor-pointer p-6 rounded-2xl border-2 border-indigo-600 bg-indigo-50/50 shadow-md transition-all';
    }

    document.getElementById('study-bottom-bar').classList.remove('hidden');
  }

  async function generateFlashcards() {
    if (!selectedNoteContext.content) return;

    const btn = document.getElementById('btn-gen-flashcards');
    btn.disabled = true;
    btn.innerHTML = `<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i> Generating...`;
    lucide.createIcons();

    try {
      const res = await fetch('/api/ai/flashcards', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ content: selectedNoteContext.content })
      });
      const data = await res.json();

      if (data.flashcards && data.flashcards.length > 0) {
        document.getElementById('fc-header-title').textContent = selectedNoteContext.title + ' - Flashcards';
        document.getElementById('study-mode-select').classList.add('hidden');
        document.getElementById('study-mode-flashcards').classList.remove('hidden');
        document.getElementById('study-mode-flashcards').classList.add('flex');

        studyEngine.initFlashcards(data.flashcards);
      } else {
        alert('Could not generate flashcards.');
      }
    } catch (e) {
      alert('Error generating flashcards.');
    } finally {
      btn.disabled = false;
      btn.innerHTML = `<i data-lucide="book-open" class="w-5 h-5"></i> Generate Flashcards`;
      lucide.createIcons();
    }
  }

  async function generateQuiz() {
    if (!selectedNoteContext.content) return;

    const btn = document.getElementById('btn-gen-quiz');
    btn.disabled = true;
    btn.innerHTML = `<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i> Generating...`;
    lucide.createIcons();

    try {
      const res = await fetch('/api/ai/quiz', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ content: selectedNoteContext.content })
      });
      const data = await res.json();

      if (data.questions && data.questions.length > 0) {
        document.getElementById('quiz-header-title').textContent = selectedNoteContext.title + ' - Quiz';
        document.getElementById('study-mode-select').classList.add('hidden');
        document.getElementById('study-mode-quiz').classList.remove('hidden');
        document.getElementById('study-mode-quiz').classList.add('flex');

        studyEngine.initQuiz(data.questions);
      } else {
        alert('Could not generate quiz.');
      }
    } catch (e) {
      alert('Error generating quiz.');
    } finally {
      btn.disabled = false;
      btn.innerHTML = `<i data-lucide="brain-circuit" class="w-5 h-5"></i> Take Quiz`;
      lucide.createIcons();
    }
  }

  function closeStudyActivity() {
    document.getElementById('study-mode-flashcards').classList.add('hidden');
    document.getElementById('study-mode-flashcards').classList.remove('flex');
    document.getElementById('study-mode-quiz').classList.add('hidden');
    document.getElementById('study-mode-quiz').classList.remove('flex');
    document.getElementById('study-mode-select').classList.remove('hidden');
  }
</script>
