<?php
/**
 * Dashboard - subject overview, recent notes, and reminders.
 */
require_once dirname(__DIR__) . '/partials/helpers.php';

$displayRegisters = $registers ?? [];
$activeSub = $activeSubject ?? null;
$noteList = $notes ?? [];
$reminderList = $reminders ?? [];
$query = trim((string)($_GET['q'] ?? ''));

$openReminders = array_values(array_filter($reminderList, fn($r) => !$r->completed));
$totalNotes = $activeSub
    ? count($noteList)
    : array_sum(array_map(fn($r) => count($r->noteIds), $displayRegisters));
?>

<div class="h-full overflow-y-auto scrollbar-slim">
  <div class="mx-auto max-w-7xl px-4 pb-24 pt-6 sm:px-6 md:pb-10 lg:px-8">

    <!-- ============ Page header ============ -->
    <header class="mb-8">
      <?php if ($activeSub): ?>
        <nav aria-label="Breadcrumb" class="mb-3">
          <ol class="flex items-center gap-1.5 text-2xs font-semibold uppercase tracking-wider text-content-subtle">
            <li><a href="/" class="rounded transition-colors hover:text-brand-700">All notes</a></li>
            <li aria-hidden="true"><i data-lucide="chevron-right" class="h-3 w-3"></i></li>
            <li aria-current="page" class="text-content-muted"><?= htmlspecialchars($activeSub) ?></li>
          </ol>
        </nav>
      <?php endif; ?>

      <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
          <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">
            <?= $activeSub ? htmlspecialchars($activeSub) : 'Your workspace' ?>
          </h1>
          <p class="mt-1.5 text-sm text-content-muted">
            <?php if ($query): ?>
              <?= count($noteList) ?> result<?= count($noteList) === 1 ? '' : 's' ?>
              for &ldquo;<?= htmlspecialchars($query) ?>&rdquo;
            <?php elseif ($activeSub): ?>
              <?= count($noteList) ?> note<?= count($noteList) === 1 ? '' : 's' ?> in this subject
            <?php else: ?>
              <?= count($displayRegisters) ?> subject<?= count($displayRegisters) === 1 ? '' : 's' ?>
              &middot; <?= $totalNotes ?> note<?= $totalNotes === 1 ? '' : 's' ?>
              &middot; <?= count($openReminders) ?> reminder<?= count($openReminders) === 1 ? '' : 's' ?> due
            <?php endif; ?>
          </p>
        </div>

        <div class="flex flex-shrink-0 flex-wrap items-center gap-2">
          <?php if ($activeSub): ?>
            <a href="/notepad?subject=<?= urlencode($activeSub) ?>" class="btn-secondary">
              <i data-lucide="pen-line" class="h-4 w-4" aria-hidden="true"></i>
              New note
            </a>
            <div class="relative">
              <button type="button"
                      class="btn-secondary"
                      data-menu-trigger="subject-export-menu"
                      aria-haspopup="menu"
                      aria-expanded="false">
                <i data-lucide="download" class="h-4 w-4" aria-hidden="true"></i>
                Export
              </button>
              <div id="subject-export-menu" hidden role="menu" class="menu absolute right-0 top-full mt-1.5">
                <a role="menuitem" class="menu-item" target="_blank" rel="noopener"
                   href="/export/register/<?= urlencode($activeSub) ?>/pdf">
                  <i data-lucide="file-text" class="h-4 w-4" aria-hidden="true"></i> Export as PDF
                </a>
                <a role="menuitem" class="menu-item"
                   href="/export/register/<?= urlencode($activeSub) ?>/markdown">
                  <i data-lucide="file-down" class="h-4 w-4" aria-hidden="true"></i> Export as Markdown
                </a>
              </div>
            </div>
          <?php else: ?>
            <a href="/recorder" class="btn-primary">
              <i data-lucide="mic" class="h-4 w-4" aria-hidden="true"></i>
              Record a lecture
            </a>
          <?php endif; ?>
        </div>
      </div>
    </header>

    <div class="grid grid-cols-1 gap-8 xl:grid-cols-[minmax(0,1fr)_19rem]">
      <div class="min-w-0 space-y-10">

        <!-- ============ Subjects ============ -->
        <?php if (!$activeSub && !$query): ?>
          <section aria-labelledby="subjects-title">
            <h2 id="subjects-title" class="section-label mb-4">
              <i data-lucide="library" class="h-3.5 w-3.5" aria-hidden="true"></i>
              Subjects
            </h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
              <?php foreach ($displayRegisters as $i => $reg):
                $accent = subject_accent($reg->color);
                $count = count($reg->noteIds);
                $menuId = 'reg-menu-' . md5($reg->name);
              ?>
                <article class="card-interactive group relative animate-rise-in overflow-hidden"
                         style="animation-delay: <?= min($i * 50, 300) ?>ms">
                  <!-- Accent bar carries the subject colour without tinting the text. -->
                  <div class="h-1.5 w-full bg-gradient-to-r <?= $accent['bar'] ?>" aria-hidden="true"></div>

                  <div class="p-5">
                    <div class="flex items-start justify-between gap-2">
                      <h3 class="min-w-0 text-base font-bold leading-snug">
                        <!--
                          The whole card is clickable via the stretched link, but
                          the link itself is what receives keyboard focus.
                        -->
                        <a href="/?subject=<?= urlencode($reg->name) ?>"
                           class="after:absolute after:inset-0 after:content-[''] group-hover:text-brand-700">
                          <?= htmlspecialchars($reg->name) ?>
                        </a>
                      </h3>

                      <!-- z-10 lifts the menu above the stretched link. -->
                      <div class="relative z-10 -mr-2 -mt-2 flex-shrink-0">
                        <button type="button"
                                class="btn-icon btn-icon-compact"
                                data-menu-trigger="<?= $menuId ?>"
                                aria-haspopup="menu"
                                aria-expanded="false"
                                aria-label="Actions for <?= htmlspecialchars($reg->name) ?>">
                          <i data-lucide="ellipsis-vertical" class="h-4 w-4" aria-hidden="true"></i>
                        </button>
                        <div id="<?= $menuId ?>" hidden role="menu" class="menu absolute right-0 top-full mt-1">
                          <a role="menuitem" class="menu-item" href="/?subject=<?= urlencode($reg->name) ?>">
                            <i data-lucide="folder-open" class="h-4 w-4" aria-hidden="true"></i> Open subject
                          </a>
                          <a role="menuitem" class="menu-item" target="_blank" rel="noopener"
                             href="/export/register/<?= urlencode($reg->name) ?>/pdf">
                            <i data-lucide="file-text" class="h-4 w-4" aria-hidden="true"></i> Export as PDF
                          </a>
                          <a role="menuitem" class="menu-item"
                             href="/export/register/<?= urlencode($reg->name) ?>/markdown">
                            <i data-lucide="file-down" class="h-4 w-4" aria-hidden="true"></i> Export as Markdown
                          </a>
                        </div>
                      </div>
                    </div>

                    <dl class="mt-4 flex items-center gap-4">
                      <div>
                        <dt class="sr-only">Notes</dt>
                        <dd class="flex items-baseline gap-1.5">
                          <span class="text-xl font-extrabold tabular-nums"><?= $count ?></span>
                          <span class="text-2xs font-semibold uppercase tracking-wider text-content-subtle">
                            note<?= $count === 1 ? '' : 's' ?>
                          </span>
                        </dd>
                      </div>
                    </dl>
                  </div>
                </article>
              <?php endforeach; ?>

              <!-- Add subject -->
              <button type="button"
                      onclick="focusNewSubject()"
                      class="flex min-h-[9rem] cursor-pointer flex-col items-center justify-center gap-2 rounded-card border-2 border-dashed border-line-strong bg-surface/50 p-5 text-content-muted transition-colors duration-200 hover:border-brand-400 hover:bg-brand-50 hover:text-brand-700">
                <i data-lucide="plus" class="h-5 w-5" aria-hidden="true"></i>
                <span class="text-sm font-semibold">Add a subject</span>
              </button>
            </div>
          </section>
        <?php endif; ?>

        <!-- ============ Notes ============ -->
        <section aria-labelledby="notes-title">
          <h2 id="notes-title" class="section-label mb-4">
            <i data-lucide="clock" class="h-3.5 w-3.5" aria-hidden="true"></i>
            <?= $query ? 'Search results' : ($activeSub ? 'Notes in this subject' : 'Recent notes') ?>
          </h2>

          <?php if (empty($noteList)): ?>
            <!-- Empty state: says what happened and offers the next step. -->
            <div class="card flex flex-col items-center px-6 py-12 text-center">
              <span class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                <i data-lucide="<?= $query ? 'search-x' : 'notebook-pen' ?>" class="h-6 w-6" aria-hidden="true"></i>
              </span>
              <h3 class="text-base font-bold">
                <?= $query ? 'No notes match that search' : 'No notes yet' ?>
              </h3>
              <p class="mx-auto mt-1.5 max-w-sm text-sm text-content-muted">
                <?= $query
                  ? 'Try a different keyword, or use AI search to look by meaning instead of exact words.'
                  : 'Record a lecture and let AI structure it for you, or start writing on a blank page.' ?>
              </p>
              <div class="mt-5 flex flex-wrap justify-center gap-2">
                <?php if ($query): ?>
                  <a href="/" class="btn-secondary">Clear search</a>
                <?php endif; ?>
                <a href="/recorder<?= $activeSub ? '?subject=' . urlencode($activeSub) : '' ?>" class="btn-primary">
                  <i data-lucide="mic" class="h-4 w-4" aria-hidden="true"></i>
                  Record a lecture
                </a>
                <a href="/notepad<?= $activeSub ? '?subject=' . urlencode($activeSub) : '' ?>" class="btn-secondary">
                  <i data-lucide="pen-line" class="h-4 w-4" aria-hidden="true"></i>
                  Write a note
                </a>
              </div>
            </div>
          <?php else: ?>
            <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <?php foreach ($noteList as $i => $note):
                $meta = note_type_meta($note->type);
                $menuId = 'note-menu-' . $note->id;
              ?>
                <li class="animate-rise-in" style="animation-delay: <?= min($i * 45, 300) ?>ms">
                  <article class="card-interactive group relative flex h-full flex-col p-5">
                    <div class="flex items-start justify-between gap-3">
                      <span class="<?= $meta['badge'] ?>">
                        <i data-lucide="<?= $meta['icon'] ?>" class="h-3 w-3" aria-hidden="true"></i>
                        <?= $meta['label'] ?>
                      </span>

                      <div class="relative z-10 -mr-2 -mt-2 flex-shrink-0">
                        <button type="button"
                                class="btn-icon btn-icon-compact"
                                data-menu-trigger="<?= $menuId ?>"
                                aria-haspopup="menu"
                                aria-expanded="false"
                                aria-label="Actions for <?= htmlspecialchars($note->title) ?>">
                          <i data-lucide="ellipsis-vertical" class="h-4 w-4" aria-hidden="true"></i>
                        </button>
                        <div id="<?= $menuId ?>" hidden role="menu" class="menu absolute right-0 top-full mt-1">
                          <button role="menuitem" type="button" class="menu-item"
                                  onclick="openReminderDialog(this, 'note', '<?= $note->id ?>', <?= htmlspecialchars(json_encode($note->title), ENT_QUOTES) ?>)">
                            <i data-lucide="bell-plus" class="h-4 w-4" aria-hidden="true"></i> Set a reminder
                          </button>
                          <a role="menuitem" class="menu-item" target="_blank" rel="noopener"
                             href="/export/note/<?= $note->id ?>/pdf">
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

                    <h3 class="mt-3 text-base font-bold leading-snug">
                      <a href="<?= note_href($note) ?>"
                         class="after:absolute after:inset-0 after:content-[''] group-hover:text-brand-700">
                        <?= htmlspecialchars($note->title) ?>
                      </a>
                    </h3>

                    <p class="mt-2 line-clamp-3 flex-1 text-sm leading-relaxed text-content-muted">
                      <?= htmlspecialchars($note->summary) ?>
                    </p>

                    <footer class="mt-4 flex items-center justify-between gap-2 border-t border-line pt-3">
                      <span class="truncate text-2xs font-bold uppercase tracking-wider text-content-subtle">
                        <?= htmlspecialchars($note->subject) ?>
                      </span>
                      <time class="flex-shrink-0 text-2xs font-semibold normal-case tracking-normal text-content-subtle">
                        <?= htmlspecialchars($note->date) ?>
                      </time>
                    </footer>
                  </article>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </section>
      </div>

      <!-- ============ Reminders (desktop rail) ============ -->
      <aside class="hidden xl:block" aria-labelledby="reminders-title">
        <div class="sticky top-0">
          <div class="card overflow-hidden">
            <div class="flex items-center justify-between gap-2 border-b border-line px-4 py-3">
              <h2 id="reminders-title" class="section-label">
                <i data-lucide="bell" class="h-3.5 w-3.5" aria-hidden="true"></i>
                Reminders
              </h2>
              <button type="button"
                      class="btn-icon btn-icon-compact"
                      aria-label="Add a reminder"
                      onclick="openReminderDialog(this)">
                <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
              </button>
            </div>

            <?php if (empty($reminderList)): ?>
              <div class="px-4 py-8 text-center">
                <p class="text-sm font-semibold">Nothing due</p>
                <p class="mt-1 text-2xs font-medium normal-case tracking-normal text-content-subtle">
                  Add a reminder to keep track of readings and deadlines.
                </p>
              </div>
            <?php else: ?>
              <ul class="scrollbar-slim max-h-[28rem] divide-y divide-line overflow-y-auto">
                <?php foreach ($reminderList as $rem): ?>
                  <li class="group flex items-start gap-3 px-4 py-3 transition-colors hover:bg-surface-sunken">
                    <!-- A real checkbox: keyboard operable and announced correctly. -->
                    <input type="checkbox"
                           id="rem-<?= $rem->id ?>"
                           class="mt-0.5 h-4 w-4 flex-shrink-0 cursor-pointer rounded border-line-strong text-brand-600 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                           <?= $rem->completed ? 'checked' : '' ?>
                           onchange="NoteNest.toggleReminder('<?= $rem->id ?>', this)">
                    <div class="min-w-0 flex-1">
                      <label for="rem-<?= $rem->id ?>"
                             class="block cursor-pointer text-sm font-medium leading-snug <?= $rem->completed ? 'text-content-subtle line-through' : 'text-content' ?>">
                        <?= htmlspecialchars($rem->text) ?>
                      </label>
                      <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-2xs font-semibold normal-case tracking-normal text-content-subtle">
                        <span class="badge-neutral"><?= htmlspecialchars($rem->targetName ?: 'General') ?></span>
                        <span><?= htmlspecialchars($rem->dueDate) ?></span>
                      </p>
                    </div>
                    <button type="button"
                            class="btn-icon btn-icon-compact flex-shrink-0 opacity-0 transition-opacity hover:text-danger focus-visible:opacity-100 group-hover:opacity-100"
                            aria-label="Delete reminder: <?= htmlspecialchars($rem->text) ?>"
                            onclick="NoteNest.deleteReminder('<?= $rem->id ?>', <?= htmlspecialchars(json_encode($rem->text), ENT_QUOTES) ?>)">
                      <i data-lucide="trash-2" class="h-4 w-4" aria-hidden="true"></i>
                    </button>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      </aside>
    </div>
  </div>
</div>

<script>
  function focusNewSubject() {
    const trigger = document.querySelector('[aria-controls="new-register-form"]');
    if (trigger) {
      if (document.getElementById('new-register-form').hidden) trigger.click();
      else document.getElementById('new-reg-name').focus();
    }
  }
</script>
