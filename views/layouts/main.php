<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'NoteNest AI') ?> - NoteNest</title>
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            paper: '#fdfbf7',
            'paper-line': '#e2e8f0',
            'paper-margin': '#fca5a5',
            ink: '#334155',
            desk: '#f5f5f4',
            wood: '#e7e5e4',
            highlight: '#fef3c7',
          },
          fontFamily: {
            sans: ['Nunito', 'sans-serif'],
            hand: ['Patrick Hand', 'cursive'],
          },
          boxShadow: {
            'notebook': '2px 4px 12px -2px rgba(0, 0, 0, 0.1)',
            'floating': '0 8px 30px rgba(0,0,0,0.08)',
          }
        }
      }
    }
  </script>

  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&family=Patrick+Hand&display=swap" rel="stylesheet">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="bg-stone-100 text-stone-800 h-screen overflow-hidden flex flex-col md:flex-row">

  <!-- Toast / Background Notification Container -->
  <div id="toast-container" class="fixed top-4 right-4 z-[100] flex flex-col gap-3 pointer-events-none">
    <!-- Toasts append here dynamically with pointer-events-auto -->
  </div>

  <!-- Mobile Header -->
  <div class="md:hidden h-16 bg-white border-b border-stone-200 flex items-center justify-between px-4 z-40 flex-shrink-0">
    <button onclick="document.getElementById('mobile-drawer').classList.remove('hidden')" class="p-2 text-stone-600">
      <i data-lucide="menu" class="w-6 h-6"></i>
    </button>

    <div class="flex items-center gap-2">
      <div class="bg-orange-100 p-1.5 rounded-lg border border-orange-200">
        <i data-lucide="backpack" class="text-orange-600 w-4 h-4"></i>
      </div>
      <a href="/" class="font-hand font-bold text-xl text-stone-800">NoteNest</a>
    </div>

    <button onclick="document.getElementById('mobile-reminders').classList.remove('hidden')" class="p-2 text-stone-600 relative">
      <i data-lucide="bell" class="w-6 h-6"></i>
      <?php 
        $pendingCount = count(array_filter($reminders ?? [], fn($r) => !$r->completed));
        if ($pendingCount > 0): 
      ?>
        <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full border border-white"></span>
      <?php endif; ?>
    </button>
  </div>

  <!-- Navigation Sidebar (Desktop & Mobile) -->
  <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <!-- Main Work Desk -->
  <main class="flex-1 overflow-hidden relative p-0 md:p-6 shadow-inner bg-stone-100">
    <div class="h-full md:rounded-3xl bg-desk border-t md:border border-stone-200/50 shadow-sm overflow-hidden relative">
      <?= $content ?>
    </div>
  </main>

  <!-- Global Scripts -->
  <script src="/assets/js/app.js"></script>
</body>
</html>
