<?php
/**
 * Dashboard View - NoteNest Study Desk
 */
$displayRegisters = $registers ?? [];
$activeSub = $activeSubject ?? null;
?>

<div id="dashboard-desk" class="h-full overflow-y-auto bg-desk relative pb-20 md:pb-0 select-none">
  
  <!-- Hidden File Inputs for Context Menu Uploads -->
  <input type="file" id="context-image-input" accept="image/*" class="hidden" onchange="handleContextImageUpload(this)" />
  <input type="file" id="context-pdf-input" accept="application/pdf" class="hidden" onchange="handleContextPdfUpload(this)" />

  <div class="p-6 md:p-8 max-w-6xl mx-auto pr-0 md:pr-80">
    
    <!-- Welcome Header -->
    <header class="mb-6 md:mb-10 flex flex-col md:flex-row md:justify-between md:items-end gap-4">
      <div>
        <h1 class="text-3xl md:text-4xl font-hand font-bold text-stone-800 mb-2">
          <?= $activeSub ? htmlspecialchars($activeSub) : "Study Desk" ?>
        </h1>
        <p class="text-stone-500 text-sm md:text-base">
          <?= $activeSub ? "You have " . count($notes) . " notes in this register." : "Welcome back to your digital study workspace!" ?>
        </p>
      </div>
      <div class="hidden md:block text-sm font-hand text-stone-400 rotate-2">
        <?= date('l, F j') ?>
      </div>
    </header>

    <!-- Registers (Notebooks) Stack -->
    <?php if (!$activeSub): ?>
      <section class="mb-12">
        <h2 class="text-lg font-bold text-stone-400 uppercase tracking-widest text-xs mb-6">My Notebooks</h2>

        <?php if (empty($displayRegisters)): ?>
          <div class="text-center py-12 bg-white/50 border-2 border-dashed border-stone-200 rounded-2xl">
            <p class="text-stone-400 mb-4 font-hand text-xl">Your desk is empty.</p>
            <a href="/recorder" class="text-orange-600 font-bold hover:underline">Start a new notebook</a>
          </div>
        <?php else: ?>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($displayRegisters as $reg): ?>
              <div class="relative group perspective-1000">
                
                <!-- Notebook Cover -->
                <div
                  onclick="window.location.href='/?subject=<?= urlencode($reg->name) ?>'"
                  class="relative h-56 md:h-64 rounded-r-2xl rounded-l-md shadow-notebook transition-all duration-300 transform group-hover:-translate-y-2 group-hover:shadow-xl <?= $reg->color ?> border-l-8 border-black/10 cursor-pointer"
                >
                  <!-- Notebook Binding Gradient -->
                  <div class="absolute left-0 top-0 bottom-0 w-4 bg-gradient-to-r from-black/20 to-transparent pointer-events-none"></div>
                  <div class="absolute left-2 top-0 bottom-0 w-[1px] bg-white/20 pointer-events-none"></div>

                  <!-- Center Paper Label -->
                  <div class="absolute top-10 left-0 right-0 p-6 pointer-events-none">
                    <div class="bg-white/90 backdrop-blur-sm p-4 shadow-sm transform -rotate-1 rounded-sm">
                      <h3 class="font-hand font-bold text-2xl text-stone-800 leading-none text-center">
                        <?= htmlspecialchars($reg->name) ?>
                      </h3>
                    </div>
                  </div>

                  <!-- Pages Layer Effect at bottom -->
                  <div class="absolute bottom-0 left-4 right-0 h-3 bg-white rounded-br-2xl border-t border-stone-200 pointer-events-none"></div>
                  <div class="absolute bottom-1 left-4 right-1 h-3 bg-stone-100 rounded-br-2xl border-t border-stone-200 pointer-events-none"></div>

                  <!-- Stats Badge -->
                  <div class="absolute bottom-6 left-6 pointer-events-none">
                    <span class="bg-black/10 text-black/60 px-2 py-1 rounded text-xs font-bold">
                      <?= count($reg->noteIds) ?> Notes
                    </span>
                  </div>

                  <!-- Export Menu Dropdown -->
                  <div class="absolute top-4 right-4 z-10 hidden md:block">
                    <button
                      type="button"
                      onclick="event.stopPropagation(); toggleExportMenu('<?= md5($reg->name) ?>');"
                      class="p-1.5 bg-white/50 hover:bg-white rounded-full text-stone-600 hover:text-orange-600 transition-all opacity-0 group-hover:opacity-100 shadow-sm"
                      title="Export Notebook"
                    >
                      <i data-lucide="download" class="w-4 h-4"></i>
                    </button>

                    <div id="export-menu-<?= md5($reg->name) ?>" class="hidden absolute right-0 top-8 w-40 bg-white rounded-xl shadow-xl border border-stone-100 py-1 overflow-hidden z-20">
                      <a
                        href="/export/register/<?= urlencode($reg->name) ?>/pdf"
                        target="_blank"
                        class="w-full flex items-center gap-2 px-3 py-2 text-xs font-bold text-stone-600 hover:bg-orange-50 hover:text-orange-600 text-left"
                      >
                        <i data-lucide="file-text" class="w-3.5 h-3.5"></i> PDF
                      </a>
                      <a
                        href="/export/register/<?= urlencode($reg->name) ?>/markdown"
                        class="w-full flex items-center gap-2 px-3 py-2 text-xs font-bold text-stone-600 hover:bg-orange-50 hover:text-orange-600 text-left"
                      >
                        <i data-lucide="file" class="w-3.5 h-3.5"></i> Markdown
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>

            <!-- New Subject Card Placeholder -->
            <div
              onclick="document.getElementById('new-register-form').classList.remove('hidden'); document.getElementById('new-reg-name').focus();"
              class="h-56 md:h-64 rounded-2xl border-4 border-dashed border-stone-200 flex flex-col items-center justify-center cursor-pointer hover:border-orange-300 hover:bg-orange-50/50 transition-all group"
            >
              <div class="bg-white p-4 rounded-full shadow-sm mb-3 group-hover:scale-110 transition-transform">
                <i data-lucide="book" class="w-6 h-6 text-stone-400 group-hover:text-orange-500"></i>
              </div>
              <span class="font-hand text-xl text-stone-400 group-hover:text-orange-600">New Subject</span>
            </div>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <!-- Loose Papers (Recent Notes) -->
    <section>
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-bold text-stone-400 uppercase tracking-widest text-xs flex items-center gap-2">
          <i data-lucide="clock" class="w-4 h-4"></i>
          <?= $activeSub ? "Subject Notes" : "Recent Papers" ?>
        </h2>
        <?php if ($activeSub): ?>
          <div class="flex items-center gap-2">
            <a href="/notepad?subject=<?= urlencode($activeSub) ?>" class="px-3 py-1 bg-white border border-stone-200 rounded-lg text-xs font-bold text-stone-600 hover:text-orange-600 flex items-center gap-1.5 shadow-sm">
              <i data-lucide="pen-tool" class="w-3.5 h-3.5"></i> New Note
            </a>
            <a href="/export/register/<?= urlencode($activeSub) ?>/pdf" target="_blank" class="px-3 py-1 bg-white border border-stone-200 rounded-lg text-xs font-bold text-stone-600 hover:text-orange-600 flex items-center gap-1.5 shadow-sm">
              <i data-lucide="download" class="w-3.5 h-3.5"></i> Export
            </a>
          </div>
        <?php endif; ?>
      </div>

      <?php if (empty($notes)): ?>
        <div class="p-12 text-center bg-white/40 border-2 border-dashed border-stone-200 rounded-2xl">
          <p class="text-stone-400 font-hand text-xl mb-2">No notes found here.</p>
          <a href="/notepad<?= $activeSub ? '?subject=' . urlencode($activeSub) : '' ?>" class="text-orange-600 font-bold hover:underline">
            Write your first note &rarr;
          </a>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <?php foreach ($notes as $idx => $note): ?>
            <div
              onclick="window.location.href='<?= $note->type === 'pdf' ? '/pdf/' . $note->id : ($note->type === 'text' ? '/notepad?id=' . $note->id : '/note/' . $note->id) ?>'"
              oncontextmenu="openContextMenu(event, 'note', '<?= $note->id ?>', '<?= htmlspecialchars(addslashes($note->title)) ?>')"
              class="relative group cursor-pointer"
              style="transform: rotate(<?= $idx % 2 === 0 ? '0.7deg' : '-0.7deg' ?>)"
            >
              <!-- Paper Stack Shadow Effect -->
              <div class="absolute inset-0 bg-stone-200 rounded-sm transform translate-x-1 translate-y-2"></div>

              <!-- Lined Paper Sheet -->
              <div class="relative bg-paper p-6 rounded-sm shadow-sm border border-stone-200 h-52 flex flex-col transition-transform group-hover:-translate-y-1">
                
                <!-- 3 Hole Punches -->
                <div class="absolute top-0 bottom-0 left-4 flex flex-col justify-evenly">
                  <div class="w-3 h-3 bg-desk rounded-full shadow-inner"></div>
                  <div class="w-3 h-3 bg-desk rounded-full shadow-inner"></div>
                  <div class="w-3 h-3 bg-desk rounded-full shadow-inner"></div>
                </div>

                <div class="pl-8 h-full flex flex-col">
                  <div class="flex justify-between items-start mb-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded <?= $note->type === 'audio' ? 'bg-blue-100 text-blue-700' : ($note->type === 'pdf' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700') ?>">
                      <?= $note->type ?>
                    </span>
                    <span class="font-hand text-stone-400 text-sm"><?= htmlspecialchars($note->date) ?></span>
                  </div>

                  <h3 class="font-hand font-bold text-2xl text-stone-800 mb-2 leading-tight group-hover:text-orange-700 transition-colors line-clamp-2">
                    <?= htmlspecialchars($note->title) ?>
                  </h3>

                  <div class="relative flex-1 overflow-hidden">
                    <p class="text-sm text-stone-500 font-sans leading-relaxed line-clamp-3">
                      <?= htmlspecialchars($note->summary) ?>
                    </p>
                    <div class="absolute bottom-0 left-0 right-0 h-8 bg-gradient-to-t from-paper to-transparent"></div>
                  </div>

                  <div class="mt-2 pt-2 border-t border-stone-100 flex justify-between items-center">
                    <span class="text-xs text-stone-400 font-bold uppercase tracking-wide"><?= htmlspecialchars($note->subject) ?></span>
                    <i data-lucide="arrow-right" class="w-4 h-4 text-stone-300 group-hover:text-orange-500 transition-colors"></i>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <!-- Sticky Note Reminders (Desktop Fixed) -->
  <aside class="hidden md:flex absolute right-8 top-8 bottom-8 w-72 flex-col pointer-events-none">
    <div class="bg-yellow-200 shadow-xl transform -rotate-1 p-6 rounded-sm pointer-events-auto flex flex-col max-h-full border border-yellow-300 relative">
      <!-- Tape visual -->
      <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-16 h-8 bg-white/40 rotate-1 shadow-sm backdrop-blur-sm border-l border-r border-white/50"></div>
      
      <div class="flex items-center justify-between border-b-2 border-stone-800/10 pb-2 mb-4">
        <h3 class="font-hand font-bold text-2xl text-stone-800 flex items-center gap-2">
          <i data-lucide="bell" class="w-5 h-5"></i> Reminders
        </h3>
        <button onclick="openCreateReminderModal()" class="p-1 hover:bg-yellow-300 rounded text-stone-700" title="Add reminder">
          <i data-lucide="plus" class="w-4 h-4"></i>
        </button>
      </div>

      <!-- Reminder Items -->
      <div class="flex-1 overflow-y-auto pr-1 space-y-3">
        <?php if (empty($reminders)): ?>
          <p class="font-hand text-stone-500 text-lg text-center mt-10">Nothing to do yet!</p>
        <?php else: ?>
          <?php foreach ($reminders as $rem): ?>
            <div class="group flex items-start gap-2 p-2 rounded hover:bg-yellow-300/50 transition-colors cursor-pointer <?= $rem->completed ? 'opacity-50' : '' ?>">
              <button
                type="button"
                onclick="NoteNest.toggleReminder('<?= $rem->id ?>')"
                class="mt-1 w-4 h-4 rounded border border-stone-500 flex items-center justify-center <?= $rem->completed ? 'bg-stone-600 border-stone-600' : 'bg-white' ?>"
              >
                <?php if ($rem->completed): ?>
                  <i data-lucide="check" class="w-3 h-3 text-white"></i>
                <?php endif; ?>
              </button>

              <div class="flex-1">
                <p class="font-hand text-lg leading-tight text-stone-800 <?= $rem->completed ? 'line-through' : '' ?>">
                  <?= htmlspecialchars($rem->text) ?>
                </p>
                <div class="flex items-center gap-2 mt-1">
                  <span class="text-[10px] uppercase font-bold text-stone-500 bg-white/50 px-1 rounded">
                    <?= htmlspecialchars($rem->targetName ?: 'General') ?>
                  </span>
                  <span class="text-xs font-hand text-stone-500">
                    <?= htmlspecialchars($rem->dueDate) ?>
                  </span>
                </div>
              </div>

              <button
                type="button"
                onclick="NoteNest.deleteReminder('<?= $rem->id ?>')"
                class="text-stone-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"
              >
                <i data-lucide="trash-2" class="w-4 h-4"></i>
              </button>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </aside>

  <!-- Mobile Reminders Drawer -->
  <div id="mobile-reminders" class="hidden fixed inset-0 z-50 md:hidden flex justify-end">
    <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" onclick="document.getElementById('mobile-reminders').classList.add('hidden')"></div>
    <div class="w-80 bg-yellow-100 h-full shadow-2xl relative flex flex-col animate-slide-left border-l-4 border-yellow-300 z-10 p-6 pt-10">
      <button onclick="document.getElementById('mobile-reminders').classList.add('hidden')" class="absolute top-4 right-4 p-2 text-stone-500">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
      <h3 class="font-hand font-bold text-3xl text-stone-800 mb-6 flex items-center gap-2">
        <i data-lucide="bell" class="w-6 h-6"></i> Reminders
      </h3>
      <div class="flex-1 overflow-y-auto space-y-3">
        <?php foreach ($reminders ?? [] as $rem): ?>
          <div class="flex items-start gap-2 p-2 rounded bg-yellow-200/50">
            <button onclick="NoteNest.toggleReminder('<?= $rem->id ?>')" class="mt-1 w-4 h-4 rounded border border-stone-500 bg-white">
              <?php if ($rem->completed): ?><i data-lucide="check" class="w-3 h-3 text-stone-800"></i><?php endif; ?>
            </button>
            <div class="flex-1">
              <p class="font-hand text-lg text-stone-800 <?= $rem->completed ? 'line-through' : '' ?>"><?= htmlspecialchars($rem->text) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Context Menu Popup -->
  <div id="context-menu" class="hidden fixed bg-white rounded-xl shadow-2xl border border-stone-200 py-2 z-50 animate-fade-in w-56">
    <div class="px-4 py-2 border-b border-stone-100 mb-1 flex justify-between items-center">
      <span id="context-type-label" class="text-xs font-bold text-stone-400 uppercase tracking-wider">Note</span>
      <button onclick="closeContextMenu()"><i data-lucide="x" class="w-3 h-3 text-stone-400"></i></button>
    </div>
    <button onclick="openCreateReminderFromContext()" class="w-full text-left px-4 py-2 text-sm text-stone-700 hover:bg-orange-50 hover:text-orange-600 flex items-center gap-2">
      <i data-lucide="bell" class="w-4 h-4"></i> Remind me
    </button>
    <button id="context-delete-btn" onclick="deleteFromContext()" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
      <i data-lucide="trash-2" class="w-4 h-4"></i> Delete
    </button>
  </div>

  <!-- Create Reminder Modal -->
  <div id="reminder-modal" class="hidden fixed inset-0 bg-black/20 backdrop-blur-sm z-[60] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md animate-slide-up border border-stone-200">
      <h3 class="font-hand font-bold text-2xl text-stone-800 mb-6 flex items-center gap-2">
        <i data-lucide="clock" class="text-orange-500"></i> Set Reminder
      </h3>
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-bold uppercase text-stone-400 mb-1">Reminder</label>
          <input
            type="text"
            id="modal-reminder-text"
            placeholder="What do you need to remember?"
            class="w-full border-b-2 border-stone-200 focus:border-orange-400 outline-none py-2 font-hand text-xl bg-transparent"
          />
        </div>
        <div>
          <label class="block text-xs font-bold uppercase text-stone-400 mb-1">Due Date</label>
          <input
            type="datetime-local"
            id="modal-reminder-date"
            class="w-full p-3 rounded-xl bg-stone-50 border border-stone-200 text-sm font-sans"
          />
        </div>
      </div>
      <div class="flex gap-3 mt-8">
        <button onclick="document.getElementById('reminder-modal').classList.add('hidden')" class="flex-1 py-3 text-stone-500 hover:bg-stone-50 rounded-xl font-bold">
          Cancel
        </button>
        <button onclick="saveModalReminder()" class="flex-1 py-3 bg-stone-800 text-white hover:bg-stone-900 rounded-xl font-bold shadow-lg shadow-stone-200">
          Save
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  let currentContext = { type: 'general', targetId: null, targetName: '' };

  function toggleExportMenu(id) {
    const el = document.getElementById('export-menu-' + id);
    if (el) el.classList.toggle('hidden');
  }

  function openContextMenu(e, type, targetId, targetName) {
    e.preventDefault();
    e.stopPropagation();
    currentContext = { type, targetId, targetName };

    const menu = document.getElementById('context-menu');
    document.getElementById('context-type-label').textContent = type;
    menu.style.left = Math.min(e.clientX, window.innerWidth - 240) + 'px';
    menu.style.top = Math.min(e.clientY, window.innerHeight - 150) + 'px';
    menu.classList.remove('hidden');
  }

  function closeContextMenu() {
    document.getElementById('context-menu').classList.add('hidden');
  }

  document.addEventListener('click', () => closeContextMenu());

  function openCreateReminderModal() {
    currentContext = { type: 'general', targetId: null, targetName: '' };
    document.getElementById('modal-reminder-text').value = '';
    document.getElementById('reminder-modal').classList.remove('hidden');
  }

  function openCreateReminderFromContext() {
    closeContextMenu();
    document.getElementById('modal-reminder-text').value = 'Review: ' + currentContext.targetName;
    document.getElementById('reminder-modal').classList.remove('hidden');
  }

  async function saveModalReminder() {
    const text = document.getElementById('modal-reminder-text').value.trim();
    const date = document.getElementById('modal-reminder-date').value;
    if (!text) return;

    await NoteNest.saveReminder({
      text,
      dueDate: date ? new Date(date).toLocaleString() : 'Tomorrow',
      type: currentContext.type,
      targetId: currentContext.targetId,
      targetName: currentContext.targetName
    });
    document.getElementById('reminder-modal').classList.add('hidden');
  }

  function deleteFromContext() {
    closeContextMenu();
    if (currentContext.type === 'note' && currentContext.targetId) {
      NoteNest.deleteNote(currentContext.targetId);
    }
  }
</script>
