<?php
/**
 * Reminders drawer + create dialog.
 *
 * Lives in the layout rather than the dashboard because the app-bar bell is
 * present on every page - a control that does nothing on 5 of 6 screens is
 * worse than no control at all.
 */
$reminderList = $reminders ?? [];
?>

<!-- ===================== Reminders drawer (mobile / tablet) ===================== -->
<div id="mobile-reminders" hidden class="fixed inset-0 z-50 xl:hidden">
  <div class="absolute inset-0 bg-content/50 backdrop-blur-sm" data-dialog-close></div>

  <div role="dialog"
       aria-modal="true"
       aria-labelledby="mobile-reminders-title"
       class="absolute inset-y-0 right-0 flex w-[min(22rem,90vw)] animate-slide-from-right flex-col border-l border-line bg-surface">

    <div class="flex items-center justify-between gap-2 border-b border-line px-4 py-3">
      <h2 id="mobile-reminders-title" class="text-base font-bold">Reminders</h2>
      <div class="flex items-center gap-1">
        <button type="button" class="btn-icon" aria-label="Add a reminder" onclick="openReminderDialog(this)">
          <i data-lucide="plus" class="h-5 w-5" aria-hidden="true"></i>
        </button>
        <button type="button" class="btn-icon" data-dialog-close aria-label="Close reminders">
          <i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i>
        </button>
      </div>
    </div>

    <?php if (empty($reminderList)): ?>
      <div class="flex flex-1 flex-col items-center justify-center px-6 text-center">
        <span class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-brand-600">
          <i data-lucide="bell" class="h-5 w-5" aria-hidden="true"></i>
        </span>
        <p class="text-sm font-semibold">Nothing due</p>
        <p class="mt-1 text-2xs font-medium normal-case tracking-normal text-content-subtle">
          Add a reminder to keep track of readings and deadlines.
        </p>
      </div>
    <?php else: ?>
      <ul class="scrollbar-slim flex-1 divide-y divide-line overflow-y-auto pb-safe">
        <?php foreach ($reminderList as $rem): ?>
          <li class="flex items-start gap-3 px-4 py-3">
            <input type="checkbox"
                   id="m-rem-<?= $rem->id ?>"
                   class="mt-0.5 h-4 w-4 flex-shrink-0 cursor-pointer rounded border-line-strong text-brand-600 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                   <?= $rem->completed ? 'checked' : '' ?>
                   onchange="NoteNest.toggleReminder('<?= $rem->id ?>', this)">
            <div class="min-w-0 flex-1">
              <label for="m-rem-<?= $rem->id ?>"
                     class="block cursor-pointer text-sm font-medium leading-snug <?= $rem->completed ? 'text-content-subtle line-through' : '' ?>">
                <?= htmlspecialchars($rem->text) ?>
              </label>
              <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-2xs font-semibold normal-case tracking-normal text-content-subtle">
                <span class="badge-neutral"><?= htmlspecialchars($rem->targetName ?: 'General') ?></span>
                <span><?= htmlspecialchars($rem->dueDate) ?></span>
              </p>
            </div>
            <button type="button"
                    class="btn-icon btn-icon-compact flex-shrink-0"
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

<!-- ===================== Reminder dialog ===================== -->
<!-- Bottom sheet on phones, centred modal from sm up. -->
<div id="reminder-dialog" hidden class="fixed inset-0 z-[80] flex items-end justify-center sm:items-center sm:p-4">
  <div class="absolute inset-0 bg-content/50 backdrop-blur-sm" data-dialog-close></div>

  <div role="dialog"
       aria-modal="true"
       aria-labelledby="reminder-dialog-title"
       class="relative w-full max-w-md animate-scale-in rounded-t-card border border-line bg-surface p-6 pb-safe shadow-overlay sm:rounded-card sm:pb-6">

    <div class="mb-5 flex items-start justify-between gap-3">
      <div class="min-w-0">
        <h2 id="reminder-dialog-title" class="text-lg font-bold">Set a reminder</h2>
        <p id="reminder-dialog-target" class="mt-0.5 truncate text-2xs font-semibold normal-case tracking-normal text-content-subtle"></p>
      </div>
      <button type="button" class="btn-icon -mr-2 -mt-2 flex-shrink-0" data-dialog-close aria-label="Close dialog">
        <i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i>
      </button>
    </div>

    <form id="reminder-form" novalidate class="space-y-4">
      <div>
        <label for="reminder-text" class="field-label">What should we remind you about?</label>
        <input id="reminder-text"
               name="text"
               type="text"
               class="input"
               placeholder="e.g. Revise chapter 4 before the quiz"
               required
               data-autofocus
               aria-describedby="reminder-text-error">
        <p id="reminder-text-error" class="field-error" hidden>
          <i data-lucide="circle-alert" class="h-3.5 w-3.5" aria-hidden="true"></i>
          <span>Enter what you want to be reminded about.</span>
        </p>
      </div>

      <div>
        <label for="reminder-date" class="field-label">Due</label>
        <input id="reminder-date" name="dueDate" type="datetime-local" class="input"
               aria-describedby="reminder-date-hint">
        <p id="reminder-date-hint" class="field-hint">Leave blank to file this under &ldquo;Tomorrow&rdquo;.</p>
      </div>

      <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
        <button type="button" class="btn-secondary" data-dialog-close>Cancel</button>
        <button type="submit" class="btn-primary" id="reminder-submit">Save reminder</button>
      </div>
    </form>
  </div>
</div>

<script>
  let reminderContext = { type: 'general', targetId: null, targetName: '' };

  /** Opens the reminder dialog, optionally pre-linked to a note. */
  function openReminderDialog(trigger, type = 'general', targetId = null, targetName = '') {
    NoteNest.menu.closeAll();
    reminderContext = { type, targetId, targetName };

    const form = document.getElementById('reminder-form');
    form.reset();
    clearReminderError();

    document.getElementById('reminder-text').value = targetName ? 'Review: ' + targetName : '';
    document.getElementById('reminder-dialog-target').textContent =
      targetName ? 'Linked to "' + targetName + '"' : 'General reminder';

    NoteNest.dialog.open('reminder-dialog', trigger);
  }

  function clearReminderError() {
    const input = document.getElementById('reminder-text');
    input.removeAttribute('aria-invalid');
    document.getElementById('reminder-text-error').hidden = true;
  }

  document.getElementById('reminder-text').addEventListener('input', clearReminderError);

  document.getElementById('reminder-form').addEventListener('submit', async (event) => {
    event.preventDefault();

    const input = document.getElementById('reminder-text');
    const text = input.value.trim();

    // Inline validation, announced next to the field it belongs to.
    if (!text) {
      input.setAttribute('aria-invalid', 'true');
      document.getElementById('reminder-text-error').hidden = false;
      input.focus();
      return;
    }

    const rawDate = document.getElementById('reminder-date').value;
    await NoteNest.withBusy(document.getElementById('reminder-submit'), 'Saving…', () =>
      NoteNest.saveReminder({
        text,
        dueDate: rawDate ? new Date(rawDate).toLocaleString() : 'Tomorrow',
        type: reminderContext.type,
        targetId: reminderContext.targetId,
        targetName: reminderContext.targetName,
      })
    );
  });
</script>
