<?php

use NoteNest\Utils\Asset;

/**
 * Master layout - application shell.
 *
 * Structure: skip link -> mobile app bar -> sidebar nav -> <main> -> bottom nav.
 * Only <main> scrolls, so the chrome stays put while content moves.
 */
$pendingReminders = count(array_filter($reminders ?? [], fn($r) => !$r->completed));
$currentView = $currentView ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="color-scheme" content="light">
  <meta name="theme-color" content="#7C3AED">
  <meta name="description" content="NoteNest AI - record lectures, organise notes by subject, and study with AI-generated flashcards and quizzes.">
  <title><?= htmlspecialchars($pageTitle ?? 'Notes') ?> &middot; NoteNest AI</title>

  <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">

  <!-- Fonts: preconnect shaves a round trip off first paint; swap avoids invisible text. -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Lora:ital,wght@0,400;0,500;0,600;1,400&display=swap">

  <!-- Compiled + purged Tailwind. Built by `npm run build:css`. -->
  <link rel="stylesheet" href="<?= Asset::url('/assets/css/app.css') ?>">

  <!--
    Icons are deferred (365 KB, nothing depends on them during parse).

    app.js is deliberately NOT deferred and NOT at the end of <body>. Views
    contain inline <script> blocks that call window.NoteNest, and those run
    while the document is still parsing - i.e. before any deferred script has
    executed. Loading it here is what makes NoteNest.* safe to reference from
    any inline script on any page. It costs ~5.8 KB gzipped of blocking
    request against a guarantee that the API is always there; the file only
    defines objects and attaches document-level listeners, so it is safe to
    run before <body> exists.
  -->
  <script src="<?= Asset::url('/assets/vendor/lucide.min.js') ?>" defer></script>
  <script src="<?= Asset::url('/assets/js/app.js') ?>"></script>
</head>
<body class="h-dvh overflow-hidden bg-surface-sunken text-content flex flex-col md:flex-row">

  <a href="#main-content" class="skip-link">Skip to main content</a>

  <!--
    Toast region. aria-live="polite" means screen readers announce toasts
    without interrupting whatever the user is doing.
  -->
  <div id="toast-container"
       role="status"
       aria-live="polite"
       aria-atomic="false"
       class="pointer-events-none fixed right-4 top-4 z-[100] flex w-[min(24rem,calc(100vw-2rem))] flex-col gap-2">
  </div>

  <!-- ===== Mobile app bar ===== -->
  <header class="flex h-16 flex-shrink-0 items-center justify-between gap-2 border-b border-line bg-surface px-2 md:hidden">
    <button type="button"
            class="btn-icon"
            data-dialog-open="mobile-drawer"
            aria-label="Open navigation menu">
      <i data-lucide="menu" class="h-5 w-5" aria-hidden="true"></i>
    </button>

    <a href="/" class="flex min-h-[2.75rem] items-center gap-2 rounded-control px-2">
      <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-content-inverse">
        <i data-lucide="notebook-pen" class="h-4 w-4" aria-hidden="true"></i>
      </span>
      <span class="text-base font-bold tracking-tight">NoteNest</span>
    </a>

    <button type="button"
            class="btn-icon relative"
            data-dialog-open="mobile-reminders"
            aria-label="<?= $pendingReminders > 0
              ? 'Reminders, ' . $pendingReminders . ' pending'
              : 'Reminders, none pending' ?>">
      <i data-lucide="bell" class="h-5 w-5" aria-hidden="true"></i>
      <?php if ($pendingReminders > 0): ?>
        <!-- Count, not just a dot: colour alone must never carry the meaning. -->
        <span class="absolute right-1 top-1 flex h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-danger px-1 text-[0.6875rem] font-bold leading-none text-content-inverse ring-2 ring-surface">
          <?= $pendingReminders > 9 ? '9+' : $pendingReminders ?>
        </span>
      <?php endif; ?>
    </button>
  </header>

  <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <!-- ===== Main work area ===== -->
  <!--
    pb-20 on mobile reserves room for the fixed bottom nav so the last row of
    content is never trapped underneath it.
  -->
  <main id="main-content"
        tabindex="-1"
        class="relative flex-1 overflow-hidden focus:outline-none">
    <?= $content ?>
  </main>

  <?php require dirname(__DIR__) . '/partials/reminders.php'; ?>
</body>
</html>
