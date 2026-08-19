/**
 * NoteNest AI - Main Client Application Logic
 */

const NoteNest = {
  // --- Toast & Background Task Notifications ---
  showToast(message, type = 'info', details = '') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `p-4 bg-white border rounded-xl shadow-xl flex items-start gap-3 animate-slide-down transition-all duration-300 w-80 z-50 ${
      type === 'success' ? 'border-green-200 text-green-800' :
      type === 'error' ? 'border-red-200 text-red-800' :
      'border-stone-200 text-stone-800'
    }`;

    let icon = '<i data-lucide="info" class="w-5 h-5 text-orange-500"></i>';
    if (type === 'success') icon = '<i data-lucide="check-circle" class="w-5 h-5 text-green-500"></i>';
    if (type === 'error') icon = '<i data-lucide="x-circle" class="w-5 h-5 text-red-500"></i>';
    if (type === 'loading') icon = '<i data-lucide="loader-2" class="w-5 h-5 text-orange-500 animate-spin"></i>';

    toast.innerHTML = `
      <div class="flex-shrink-0 mt-0.5">${icon}</div>
      <div class="flex-1">
        <h4 class="font-bold text-sm leading-tight">${message}</h4>
        ${details ? `<p class="text-xs text-stone-500 mt-1 leading-relaxed">${details}</p>` : ''}
      </div>
      <button onclick="this.parentElement.remove()" class="text-stone-400 hover:text-stone-600">
        <i data-lucide="x" class="w-4 h-4"></i>
      </button>
    `;

    container.appendChild(toast);
    lucide.createIcons();

    if (type !== 'loading') {
      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-10px)';
        setTimeout(() => toast.remove(), 300);
      }, 4000);
    }

    return toast;
  },

  // --- Speech Synthesis (Text-to-Speech) ---
  speak(text) {
    if (!('speechSynthesis' in window)) {
      alert("Text-to-speech is not supported in this browser.");
      return;
    }

    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.rate = 1.0;
    utterance.pitch = 1.0;
    utterance.lang = 'en-US';

    const voices = window.speechSynthesis.getVoices();
    const preferred = voices.find(v => v.name.includes('Google') || v.name.includes('Natural') || v.name.includes('Samantha'));
    if (preferred) utterance.voice = preferred;

    window.speechSynthesis.speak(utterance);
  },

  stopSpeaking() {
    if ('speechSynthesis' in window) {
      window.speechSynthesis.cancel();
    }
  },

  // --- Smart AI Semantic Search ---
  async performSmartSearch(query) {
    if (!query.trim()) return;
    
    const toast = this.showToast('Searching Notes...', 'loading', 'Analyzing note concepts with AI...');

    try {
      // 1. Fetch current notes metadata
      const notesRes = await fetch('/api/notes');
      const notes = await notesRes.json();

      const metadata = notes.map(n => ({
        id: n.id,
        title: n.title,
        subject: n.subject,
        tags: n.tags || []
      }));

      // 2. Call AI Semantic Search
      const searchRes = await fetch('/api/ai/search', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ query, metadata })
      });

      const data = await searchRes.json();
      toast.remove();

      if (data.noteIds && data.noteIds.length > 0) {
        // Highlight or filter matching notes
        const matchedNoteId = data.noteIds[0];
        window.location.href = `/note/${matchedNoteId}`;
      } else {
        window.location.href = `/?q=${encodeURIComponent(query)}`;
      }
    } catch (e) {
      toast.remove();
      this.showToast('Search Failed', 'error', 'Could not run smart AI search.');
      window.location.href = `/?q=${encodeURIComponent(query)}`;
    }
  },

  // --- Subject Registers ---
  async createRegister(name) {
    if (!name.trim()) return;
    try {
      const res = await fetch('/api/registers', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: name.trim() })
      });
      const data = await res.json();
      if (data.success) {
        window.location.reload();
      } else {
        alert(data.error || 'Failed to create register');
      }
    } catch (e) {
      alert('Error creating register');
    }
  },

  // --- Reminders ---
  async toggleReminder(id) {
    try {
      const res = await fetch(`/api/reminders/${id}/toggle`, { method: 'POST' });
      const data = await res.json();
      if (data.success) {
        window.location.reload();
      }
    } catch (e) {
      console.error(e);
    }
  },

  async deleteReminder(id) {
    try {
      const res = await fetch(`/api/reminders/${id}`, { method: 'DELETE' });
      const data = await res.json();
      if (data.success) {
        window.location.reload();
      }
    } catch (e) {
      console.error(e);
    }
  },

  async saveReminder(reminderData) {
    try {
      const res = await fetch('/api/reminders', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(reminderData)
      });
      const data = await res.json();
      if (data.success) {
        window.location.reload();
      }
    } catch (e) {
      alert('Error saving reminder');
    }
  },

  // --- Delete Note ---
  async deleteNote(id) {
    if (!confirm('Are you sure you want to delete this note?')) return;
    try {
      const res = await fetch(`/api/notes/${id}`, { method: 'DELETE' });
      const data = await res.json();
      if (data.success) {
        window.location.href = '/';
      }
    } catch (e) {
      alert('Failed to delete note');
    }
  }
};

// Global initialization
document.addEventListener('DOMContentLoaded', () => {
  if (window.lucide) {
    lucide.createIcons();
  }
});
