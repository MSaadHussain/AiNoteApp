<?php
/**
 * Primary navigation: desktop rail, mobile drawer, and mobile bottom tabs.
 * All three render the same destinations in the same order so the mental model
 * survives the breakpoint change.
 */
require_once __DIR__ . '/helpers.php';

$currentSubject = $activeSubject ?? ($_GET['subject'] ?? '');
$view = $currentView ?? '';
$searchTerm = $_GET['q'] ?? '';

/** @var array<int, array{href:string, icon:string, label:string, view:string}> */
$primaryNav = [
    ['href' => '/',         'icon' => 'library',      'label' => 'All notes', 'view' => 'DASHBOARD'],
    ['href' => '/recorder', 'icon' => 'mic',          'label' => 'Record',    'view' => 'RECORDER'],
    ['href' => '/notepad',  'icon' => 'pen-line',     'label' => 'Notepad',   'view' => 'NOTEPAD'],
    ['href' => '/study',    'icon' => 'graduation-cap','label' => 'Study',    'view' => 'STUDY_MODE'],
];

$isNavActive = static function (array $item) use ($view, $currentSubject): bool {
    if ($item['view'] === 'DASHBOARD') {
        return $view === 'DASHBOARD' && $currentSubject === '';
    }
    return $view === $item['view'];
};
?>

<!-- ===================== Desktop sidebar ===================== -->
<aside class="hidden w-72 flex-shrink-0 flex-col border-r border-line bg-surface md:flex"
       aria-label="Main navigation">

  <div class="px-5 pb-4 pt-6">
    <a href="/" class="inline-flex items-center gap-2.5 rounded-control py-1">
      <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-content-inverse shadow-sm">
        <i data-lucide="notebook-pen" class="h-5 w-5" aria-hidden="true"></i>
      </span>
      <span>
        <span class="block text-base font-extrabold leading-none tracking-tight">NoteNest</span>
        <span class="block text-2xs font-semibold uppercase tracking-widest text-content-subtle">AI study workspace</span>
      </span>
    </a>
  </div>

  <!-- Search -->
  <div class="px-5">
    <form role="search" action="/" method="get" class="relative">
      <label for="global-search" class="sr-only">Search notes</label>
      <i data-lucide="search"
         class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-content-subtle"
         aria-hidden="true"></i>
      <input id="global-search"
             type="search"
             name="q"
             value="<?= htmlspecialchars($searchTerm) ?>"
             placeholder="Search notes"
             autocomplete="off"
             class="input pl-9 pr-11"
             aria-describedby="global-search-hint">
      <button type="button"
              class="btn-icon absolute right-0.5 top-1/2 h-10 w-10 -translate-y-1/2"
              aria-label="Search with AI"
              onclick="NoteNest.smartSearch(document.getElementById('global-search').value)">
        <i data-lucide="sparkles" class="h-4 w-4" aria-hidden="true"></i>
      </button>
    </form>
    <p id="global-search-hint" class="field-hint">
      Press <kbd class="rounded border border-line bg-surface-sunken px-1 font-sans font-bold">/</kbd> to search,
      or use AI to search by meaning.
    </p>
  </div>

  <!-- Primary action -->
  <div class="px-5 pb-2 pt-4">
    <a href="/recorder<?= $currentSubject ? '?subject=' . urlencode($currentSubject) : '' ?>"
       class="btn-primary w-full">
      <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
      New note
    </a>
  </div>

  <nav class="scrollbar-slim flex-1 overflow-y-auto px-3 pb-6" aria-label="Sections">
    <ul class="space-y-1 py-2">
      <?php foreach ($primaryNav as $item): $active = $isNavActive($item); ?>
        <li>
          <a href="<?= $item['href'] ?>"
             class="nav-item <?= $active ? 'nav-item-active' : '' ?>"
             <?= $active ? 'aria-current="page"' : '' ?>>
            <i data-lucide="<?= $item['icon'] ?>" class="h-4 w-4 flex-shrink-0" aria-hidden="true"></i>
            <?= $item['label'] ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <!-- Subjects -->
    <div class="mt-6">
      <div class="flex items-center justify-between px-3">
        <h2 class="section-label" id="subjects-heading">Subjects</h2>
        <button type="button"
                class="btn-icon btn-icon-compact"
                aria-label="Add a subject"
                aria-expanded="false"
                aria-controls="new-register-form"
                onclick="toggleRegisterForm(this)">
          <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
        </button>
      </div>

      <div id="new-register-form" hidden class="mt-2 px-3">
        <label for="new-reg-name" class="field-label">Subject name</label>
        <div class="flex items-center gap-2">
          <input id="new-reg-name"
                 type="text"
                 class="input"
                 placeholder="e.g. Organic Chemistry"
                 aria-describedby="new-reg-error"
                 onkeydown="if (event.key === 'Enter') { event.preventDefault(); NoteNest.createRegister(this.value, this); }">
          <button type="button"
                  class="btn-primary flex-shrink-0 px-3"
                  aria-label="Save subject"
                  onclick="NoteNest.createRegister(document.getElementById('new-reg-name').value, document.getElementById('new-reg-name'))">
            <i data-lucide="check" class="h-4 w-4" aria-hidden="true"></i>
          </button>
        </div>
        <p id="new-reg-error" class="field-error" hidden></p>
      </div>

      <ul class="mt-2 space-y-0.5" aria-labelledby="subjects-heading">
        <li>
          <a href="/"
             class="nav-item <?= $currentSubject === '' ? 'nav-item-active' : '' ?>"
             <?= $currentSubject === '' ? 'aria-current="page"' : '' ?>>
            <span class="h-2 w-2 flex-shrink-0 rounded-full bg-content-subtle" aria-hidden="true"></span>
            <span class="flex-1 truncate">All subjects</span>
          </a>
        </li>
        <?php foreach ($registers ?? [] as $reg):
          $accent = subject_accent($reg->color);
          $isActive = $currentSubject === $reg->name;
          $count = count($reg->noteIds);
        ?>
          <li>
            <a href="/?subject=<?= urlencode($reg->name) ?>"
               class="nav-item <?= $isActive ? 'nav-item-active' : '' ?>"
               <?= $isActive ? 'aria-current="page"' : '' ?>>
              <span class="h-2 w-2 flex-shrink-0 rounded-full <?= $accent['dot'] ?>" aria-hidden="true"></span>
              <span class="flex-1 truncate"><?= htmlspecialchars($reg->name) ?></span>
              <span class="text-2xs font-bold tabular-nums text-content-subtle">
                <?= $count ?><span class="sr-only"> notes</span>
              </span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </nav>
</aside>

<!-- ===================== Mobile drawer ===================== -->
<div id="mobile-drawer" hidden class="fixed inset-0 z-50 md:hidden">
  <div class="absolute inset-0 bg-content/50 backdrop-blur-sm" data-dialog-close></div>

  <div role="dialog"
       aria-modal="true"
       aria-labelledby="mobile-drawer-title"
       class="relative flex h-full w-[min(20rem,85vw)] animate-slide-from-left flex-col border-r border-line bg-surface">

    <div class="flex items-center justify-between border-b border-line px-4 py-3">
      <h2 id="mobile-drawer-title" class="text-base font-bold">Menu</h2>
      <button type="button" class="btn-icon" data-dialog-close aria-label="Close menu">
        <i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i>
      </button>
    </div>

    <nav class="scrollbar-slim flex-1 overflow-y-auto p-3" aria-label="Mobile navigation">
      <ul class="space-y-1">
        <?php foreach ($primaryNav as $item): $active = $isNavActive($item); ?>
          <li>
            <a href="<?= $item['href'] ?>"
               class="nav-item <?= $active ? 'nav-item-active' : '' ?>"
               <?= $active ? 'aria-current="page"' : '' ?>>
              <i data-lucide="<?= $item['icon'] ?>" class="h-4 w-4 flex-shrink-0" aria-hidden="true"></i>
              <?= $item['label'] ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>

      <h3 class="section-label mt-6 px-3">Subjects</h3>
      <ul class="mt-2 space-y-0.5">
        <?php foreach ($registers ?? [] as $reg):
          $accent = subject_accent($reg->color);
          $isActive = $currentSubject === $reg->name;
        ?>
          <li>
            <a href="/?subject=<?= urlencode($reg->name) ?>"
               class="nav-item <?= $isActive ? 'nav-item-active' : '' ?>"
               <?= $isActive ? 'aria-current="page"' : '' ?>>
              <span class="h-2 w-2 flex-shrink-0 rounded-full <?= $accent['dot'] ?>" aria-hidden="true"></span>
              <span class="flex-1 truncate"><?= htmlspecialchars($reg->name) ?></span>
              <span class="text-2xs font-bold tabular-nums text-content-subtle">
                <?= count($reg->noteIds) ?><span class="sr-only"> notes</span>
              </span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <div class="border-t border-line p-3 pb-safe">
      <a href="/recorder" class="btn-primary w-full">
        <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
        New note
      </a>
    </div>
  </div>
</div>

<!-- ===================== Mobile bottom tabs ===================== -->
<!-- Four destinations, matching the desktop rail. -->
<nav class="fixed inset-x-0 bottom-0 z-40 flex border-t border-line bg-surface/95 pb-safe backdrop-blur md:hidden"
     aria-label="Primary">
  <?php foreach ($primaryNav as $item): $active = $isNavActive($item); ?>
    <a href="<?= $item['href'] ?>"
       class="flex min-h-[3.5rem] flex-1 flex-col items-center justify-center gap-1 px-1 py-2 text-2xs font-bold tracking-normal transition-colors duration-200 <?= $active ? 'text-brand-700' : 'text-content-subtle' ?>"
       <?= $active ? 'aria-current="page"' : '' ?>>
      <i data-lucide="<?= $item['icon'] ?>" class="h-5 w-5" aria-hidden="true"></i>
      <span class="normal-case"><?= $item['label'] ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<script>
  function toggleRegisterForm(trigger) {
    const form = document.getElementById('new-register-form');
    const willOpen = form.hidden;
    form.hidden = !willOpen;
    trigger.setAttribute('aria-expanded', String(willOpen));
    if (willOpen) document.getElementById('new-reg-name').focus();
  }
</script>
