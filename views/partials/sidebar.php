<?php
// Sidebar Partial for NoteNest AI
$currentSubject = $activeSubject ?? ($_GET['subject'] ?? '');
?>

<!-- Desktop Sidebar (Fixed) -->
<aside class="hidden md:flex w-72 bg-stone-50 text-stone-700 flex-col h-full border-r border-stone-200/60 flex-shrink-0 shadow-[4px_0_24px_rgba(0,0,0,0.02)] z-30">
  
  <!-- Brand Header -->
  <div class="p-6 pt-8 pb-4">
    <a href="/" class="flex items-center gap-3 text-stone-800 group">
      <div class="bg-orange-100 p-2.5 rounded-xl border border-orange-200 group-hover:scale-105 transition-transform">
        <i data-lucide="backpack" class="text-orange-600 w-6 h-6"></i>
      </div>
      <h1 class="font-hand font-bold text-2xl tracking-wide">NoteNest</h1>
    </a>
  </div>

  <!-- Global Search & Actions -->
  <div class="px-5 space-y-4 mb-4">
    <div class="relative group">
      <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400 group-focus-within:text-orange-500 transition-colors"></i>
      
      <input
        type="text"
        id="sidebar-search-input"
        placeholder="Search notes... (Press Enter)"
        value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
        onkeydown="if(event.key === 'Enter') { window.location.href = '/?q=' + encodeURIComponent(this.value); }"
        class="w-full bg-white border border-stone-200 rounded-xl py-2.5 pl-10 pr-9 text-sm text-stone-700 focus:outline-none focus:border-orange-300 focus:ring-2 focus:ring-orange-100 transition-all shadow-sm"
      />

      <button
        onclick="NoteNest.performSmartSearch(document.getElementById('sidebar-search-input').value)"
        class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-stone-300 hover:text-orange-500 transition-colors"
        title="AI Smart Search"
      >
        <i data-lucide="sparkles" class="w-4 h-4"></i>
      </button>
    </div>

    <a
      href="/recorder<?= $currentSubject ? '?subject=' . urlencode($currentSubject) : '' ?>"
      class="flex w-full bg-stone-800 hover:bg-stone-900 text-white py-3 px-4 rounded-xl items-center justify-center gap-2 font-medium transition-all shadow-lg shadow-stone-300 active:scale-95"
    >
      <i data-lucide="plus" class="w-5 h-5"></i>
      <span class="font-hand text-lg">New Entry</span>
    </a>
  </div>

  <!-- Navigation Bookshelf -->
  <nav class="flex-1 overflow-y-auto px-4 space-y-8 pb-6">
    
    <!-- Desk Items -->
    <div>
      <p class="text-xs font-bold uppercase text-stone-400 mb-3 px-2 tracking-wider">My Desk</p>
      <ul class="space-y-1">
        <li>
          <a
            href="/"
            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?= ($currentView ?? '') === 'DASHBOARD' && empty($currentSubject) ? 'bg-white shadow-sm border border-stone-100 text-orange-600' : 'hover:bg-stone-100 text-stone-600' ?>"
          >
            <i data-lucide="layout" class="w-4 h-4"></i>
            <span class="font-medium">All Notes</span>
          </a>
        </li>
        <li>
          <a
            href="/study"
            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?= ($currentView ?? '') === 'STUDY_MODE' ? 'bg-white shadow-sm border border-stone-100 text-orange-600' : 'hover:bg-stone-100 text-stone-600' ?>"
          >
            <i data-lucide="calendar" class="w-4 h-4"></i>
            <span class="font-medium">Study Planner</span>
          </a>
        </li>
      </ul>
    </div>

    <!-- Registers Shelf -->
    <div>
      <div class="flex items-center justify-between px-2 mb-3">
        <p class="text-xs font-bold uppercase text-stone-400 tracking-wider">Registers</p>
        <button
          onclick="document.getElementById('new-register-form').classList.toggle('hidden'); document.getElementById('new-reg-name').focus();"
          class="p-1 hover:bg-stone-200 rounded transition-colors"
          title="Create new register"
        >
          <i data-lucide="plus" class="w-3.5 h-3.5 text-stone-500"></i>
        </button>
      </div>

      <!-- Add Register Form -->
      <div id="new-register-form" class="hidden mb-2 px-2 animate-slide-down">
        <div class="flex items-center gap-2 bg-white p-2 rounded-lg border border-orange-200 shadow-sm">
          <input
            id="new-reg-name"
            type="text"
            placeholder="Subject Name..."
            class="w-full bg-transparent text-sm text-stone-800 focus:outline-none font-hand"
            onkeydown="if(event.key === 'Enter') { NoteNest.createRegister(this.value); }"
          />
          <button onclick="NoteNest.createRegister(document.getElementById('new-reg-name').value)" class="text-green-600 hover:text-green-700">
            <i data-lucide="check" class="w-4 h-4"></i>
          </button>
          <button onclick="document.getElementById('new-register-form').classList.add('hidden')" class="text-red-400 hover:text-red-500">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </div>
      </div>

      <ul class="space-y-1.5">
        <li>
          <a
            href="/"
            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?= empty($currentSubject) ? 'bg-white shadow-sm ring-1 ring-black/5 font-bold text-stone-900' : 'hover:bg-stone-100 text-stone-600' ?>"
          >
            <div class="w-1.5 h-6 rounded-sm bg-stone-300 shadow-sm"></div>
            <span class="truncate flex-1 text-left font-hand text-lg">All Subjects</span>
          </a>
        </li>

        <?php foreach ($registers ?? [] as $reg): ?>
          <li>
            <a
              href="/?subject=<?= urlencode($reg->name) ?>"
              class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all group relative overflow-hidden <?= $currentSubject === $reg->name ? 'bg-white shadow-sm ring-1 ring-black/5 font-bold text-stone-900' : 'hover:bg-stone-100 text-stone-600' ?>"
            >
              <div class="w-1.5 h-6 rounded-sm <?= explode(' ', $reg->color)[0] ?> shadow-sm"></div>
              <span class="truncate flex-1 text-left font-hand text-lg"><?= htmlspecialchars($reg->name) ?></span>
              <?php if (count($reg->noteIds) > 0): ?>
                <span class="text-[10px] font-bold text-stone-400 bg-stone-100 px-1.5 py-0.5 rounded-full">
                  <?= count($reg->noteIds) ?>
                </span>
              <?php endif; ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </nav>
</aside>

<!-- Mobile Side Drawer (Overlay) -->
<div id="mobile-drawer" class="hidden fixed inset-0 z-50 md:hidden flex">
  <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" onclick="document.getElementById('mobile-drawer').classList.add('hidden')"></div>
  <div class="w-64 bg-stone-50 h-full shadow-2xl relative flex flex-col animate-slide-right z-10">
    <button onclick="document.getElementById('mobile-drawer').classList.add('hidden')" class="absolute top-4 right-4 p-2 text-stone-400 hover:text-stone-600">
      <i data-lucide="x" class="w-5 h-5"></i>
    </button>
    <div class="p-6 pb-2">
      <h2 class="font-hand font-bold text-2xl text-stone-800">My Shelf</h2>
    </div>
    <div class="p-4 space-y-4">
      <a href="/" class="flex items-center gap-3 p-2 rounded-lg hover:bg-stone-100 text-stone-700 font-hand text-lg">
        <i data-lucide="layout" class="w-5 h-5"></i> All Notes
      </a>
      <a href="/recorder" class="flex items-center gap-3 p-2 rounded-lg bg-orange-50 text-orange-600 font-hand text-lg font-bold">
        <i data-lucide="mic" class="w-5 h-5"></i> Record Lecture
      </a>
      <a href="/notepad" class="flex items-center gap-3 p-2 rounded-lg hover:bg-stone-100 text-stone-700 font-hand text-lg">
        <i data-lucide="pen-tool" class="w-5 h-5"></i> Blank Notepad
      </a>
      <a href="/study" class="flex items-center gap-3 p-2 rounded-lg hover:bg-stone-100 text-stone-700 font-hand text-lg">
        <i data-lucide="calendar" class="w-5 h-5"></i> Study Planner
      </a>
    </div>
  </div>
</div>

<!-- Mobile Bottom Navigation Bar -->
<div class="md:hidden fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-lg border-t border-stone-200 h-16 flex items-center justify-around z-40 pb-safe">
  <a href="/" class="flex flex-col items-center gap-1 p-2 <?= ($currentView ?? '') === 'DASHBOARD' ? 'text-orange-600' : 'text-stone-400' ?>">
    <i data-lucide="home" class="w-5 h-5"></i>
    <span class="text-[10px] font-bold uppercase">Desk</span>
  </a>

  <a href="/recorder" class="relative -top-4 bg-stone-800 text-white p-3.5 rounded-full shadow-lg shadow-stone-300 active:scale-95 transition-transform">
    <i data-lucide="plus" class="w-6 h-6"></i>
  </a>

  <a href="/study" class="flex flex-col items-center gap-1 p-2 <?= ($currentView ?? '') === 'STUDY_MODE' ? 'text-orange-600' : 'text-stone-400' ?>">
    <i data-lucide="calendar" class="w-5 h-5"></i>
    <span class="text-[10px] font-bold uppercase">Study</span>
  </a>
</div>
