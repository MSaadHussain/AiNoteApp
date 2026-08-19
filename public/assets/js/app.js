/**
 * NoteNest AI - core client runtime.
 *
 * Provides the accessible primitives the whole app builds on: dialogs with a
 * real focus trap, menus wired to aria-expanded, polite toasts, a promise-based
 * confirm that replaces window.confirm, and a fetch wrapper with error handling.
 */
(function () {
  'use strict';

  const FOCUSABLE = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
  ].join(',');

  const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /** Re-render Lucide glyphs after injecting markup. */
  function refreshIcons(root) {
    if (window.lucide) {
      window.lucide.createIcons(root ? { nameAttr: 'data-lucide', root } : undefined);
    }
  }

  function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
  }

  /* ======================================================================
     Dialogs (modals + drawers)
     ====================================================================== */

  const Dialog = {
    /** Stack so nested dialogs restore focus in the right order. */
    stack: [],

    open(id, trigger) {
      const el = document.getElementById(id);
      if (!el || this.stack.some((entry) => entry.el === id)) return;

      el.hidden = false;
      el.dataset.open = 'true';
      document.body.style.overflow = 'hidden';

      const entry = {
        el: id,
        node: el,
        returnFocus: trigger || document.activeElement,
      };
      this.stack.push(entry);

      // Focus the element the author marked, else the first focusable thing,
      // else the dialog itself so screen readers land inside it.
      const target =
        el.querySelector('[data-autofocus]') ||
        el.querySelector(FOCUSABLE) ||
        el;
      if (target === el && !el.hasAttribute('tabindex')) el.setAttribute('tabindex', '-1');

      // Focus synchronously: requestAnimationFrame is throttled or paused when
      // the page is not compositing, which would leave the dialog open with
      // focus stranded on the trigger behind the scrim.
      target.focus({ preventScroll: true });
      if (document.activeElement !== target) {
        window.setTimeout(() => target.focus({ preventScroll: true }), 0);
      }

      if (trigger) trigger.setAttribute('aria-expanded', 'true');
    },

    close(id) {
      const index = this.stack.findIndex((entry) => entry.el === id);
      if (index === -1) return;

      const [entry] = this.stack.splice(index, 1);
      entry.node.hidden = true;
      delete entry.node.dataset.open;

      if (this.stack.length === 0) document.body.style.overflow = '';

      document
        .querySelectorAll('[data-dialog-open="' + id + '"]')
        .forEach((btn) => btn.setAttribute('aria-expanded', 'false'));

      // Returning focus to the trigger is what makes keyboard navigation
      // feel continuous instead of dumping the user back at <body>. Skip it if
      // the trigger is no longer rendered - a breakpoint change can hide the
      // control that opened the dialog, and focusing a display:none element
      // silently drops focus to <body> anyway.
      if (
        entry.returnFocus &&
        document.contains(entry.returnFocus) &&
        entry.returnFocus.getClientRects().length > 0
      ) {
        entry.returnFocus.focus({ preventScroll: true });
      }
    },

    /**
     * A dialog can be dismissed by the *layout* rather than by the user:
     * #mobile-drawer is md:hidden and #mobile-reminders is xl:hidden, so
     * widening the window past that breakpoint makes the node display:none
     * while this controller still considers it open. The result is a page that
     * is scroll-locked with a focus trap armed on something nobody can see.
     *
     * Rather than special-casing those two ids, close whatever the layout has
     * hidden - that covers every current dialog and any added later.
     */
    closeIfHiddenByLayout() {
      if (this.stack.length === 0) return;
      // Copy first: close() mutates the stack while we iterate.
      this.stack.slice().forEach((entry) => {
        if (window.getComputedStyle(entry.node).display === 'none') {
          this.close(entry.el);
        }
      });
    },

    closeTop() {
      const top = this.stack[this.stack.length - 1];
      if (top) this.close(top.el);
    },

    /** Keep Tab cycling inside the top-most dialog. */
    trap(event) {
      const top = this.stack[this.stack.length - 1];
      if (!top) return;

      const items = Array.from(top.node.querySelectorAll(FOCUSABLE)).filter(
        (node) => node.offsetParent !== null || node === document.activeElement
      );
      if (items.length === 0) {
        event.preventDefault();
        top.node.focus();
        return;
      }

      const first = items[0];
      const last = items[items.length - 1];

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    },
  };

  /* ======================================================================
     Menus (dropdowns / popovers)
     ====================================================================== */

  const Menu = {
    openId: null,

    toggle(id, trigger) {
      if (this.openId === id) {
        this.closeAll();
      } else {
        this.closeAll();
        const el = document.getElementById(id);
        if (!el) return;
        el.hidden = false;
        this.openId = id;
        if (trigger) trigger.setAttribute('aria-expanded', 'true');
        const first = el.querySelector(FOCUSABLE);
        if (first) first.focus({ preventScroll: true });
      }
    },

    closeAll(restoreFocus) {
      if (!this.openId) return;
      const el = document.getElementById(this.openId);
      if (el) el.hidden = true;
      document
        .querySelectorAll('[data-menu-trigger="' + this.openId + '"]')
        .forEach((btn) => {
          btn.setAttribute('aria-expanded', 'false');
          if (restoreFocus) btn.focus({ preventScroll: true });
        });
      this.openId = null;
    },
  };

  /* ======================================================================
     Public API
     ====================================================================== */

  const NoteNest = {
    refreshIcons,
    escapeHtml,
    dialog: Dialog,
    menu: Menu,

    /* ---- Toasts ------------------------------------------------------ */

    /**
     * @param {string} message  Short headline.
     * @param {'info'|'success'|'error'|'loading'} type
     * @param {string} details  Optional supporting line.
     * @returns {{remove: () => void}}
     */
    toast(message, type = 'info', details = '') {
      const container = document.getElementById('toast-container');
      if (!container) return { remove() {} };

      const tone = {
        info: { icon: 'info', ring: 'ring-brand-200', fg: 'text-brand-600' },
        success: { icon: 'circle-check', ring: 'ring-success/25', fg: 'text-success' },
        error: { icon: 'circle-alert', ring: 'ring-danger/25', fg: 'text-danger' },
        loading: { icon: 'loader-circle', ring: 'ring-brand-200', fg: 'text-brand-600' },
      }[type] || { icon: 'info', ring: 'ring-brand-200', fg: 'text-brand-600' };

      const toast = document.createElement('div');
      toast.className =
        'pointer-events-auto flex items-start gap-3 rounded-card border border-line bg-surface p-3.5 shadow-overlay ring-1 ' +
        tone.ring +
        ' animate-slide-from-top';
      toast.innerHTML =
        '<span class="mt-0.5 flex-shrink-0 ' + tone.fg + '">' +
        '<i data-lucide="' + tone.icon + '" class="h-5 w-5' +
        (type === 'loading' ? ' animate-spin' : '') +
        '" aria-hidden="true"></i></span>' +
        '<div class="min-w-0 flex-1">' +
        '<p class="text-sm font-semibold leading-snug text-content">' + escapeHtml(message) + '</p>' +
        (details
          ? '<p class="mt-0.5 text-2xs font-medium normal-case tracking-normal text-content-muted">' +
            escapeHtml(details) + '</p>'
          : '') +
        '</div>' +
        (type === 'loading'
          ? ''
          : '<button type="button" class="btn-icon -my-1.5 -mr-1.5 h-9 w-9" data-toast-dismiss aria-label="Dismiss notification">' +
            '<i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i></button>');

      container.appendChild(toast);
      refreshIcons(toast);

      const remove = () => {
        if (!toast.isConnected) return;
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-8px)';
        toast.style.transition = 'opacity 180ms ease, transform 180ms ease';
        window.setTimeout(() => toast.remove(), prefersReducedMotion() ? 0 : 180);
      };

      toast.querySelector('[data-toast-dismiss]')?.addEventListener('click', remove);
      if (type !== 'loading') window.setTimeout(remove, 5000);

      return { remove, element: toast };
    },

    /* ---- Confirm ----------------------------------------------------- */

    /**
     * Accessible replacement for window.confirm. Resolves true/false.
     * Native confirm() cannot be styled, blocks the main thread, and is
     * suppressed entirely in some embedded browsers.
     */
    confirm({ title, body, confirmLabel = 'Confirm', tone = 'danger' } = {}) {
      return new Promise((resolve) => {
        const id = 'confirm-dialog';
        document.getElementById(id)?.remove();

        const wrap = document.createElement('div');
        wrap.id = id;
        wrap.hidden = true;
        wrap.className = 'fixed inset-0 z-[90] flex items-center justify-center p-4';
        wrap.innerHTML =
          '<div class="absolute inset-0 bg-content/50 backdrop-blur-sm" data-dialog-close></div>' +
          '<div role="alertdialog" aria-modal="true" aria-labelledby="' + id + '-title" ' +
          'aria-describedby="' + id + '-body" ' +
          'class="relative w-full max-w-sm animate-scale-in rounded-card border border-line bg-surface p-6 shadow-overlay">' +
          '<h2 id="' + id + '-title" class="text-lg font-bold">' + escapeHtml(title) + '</h2>' +
          '<p id="' + id + '-body" class="mt-2 text-sm leading-relaxed text-content-muted">' +
          escapeHtml(body) + '</p>' +
          '<div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">' +
          '<button type="button" class="btn-secondary" data-confirm-cancel>Cancel</button>' +
          '<button type="button" class="' +
          (tone === 'danger' ? 'btn-danger' : 'btn-primary') +
          '" data-confirm-ok data-autofocus>' + escapeHtml(confirmLabel) + '</button>' +
          '</div></div>';

        document.body.appendChild(wrap);

        const finish = (value) => {
          Dialog.close(id);
          wrap.remove();
          resolve(value);
        };

        wrap.querySelector('[data-confirm-ok]').addEventListener('click', () => finish(true));
        wrap.querySelector('[data-confirm-cancel]').addEventListener('click', () => finish(false));
        wrap.querySelector('[data-dialog-close]').addEventListener('click', () => finish(false));
        wrap.addEventListener('keydown', (e) => {
          if (e.key === 'Escape') finish(false);
        });

        Dialog.open(id);
      });
    },

    /* ---- Async button state ------------------------------------------ */

    /**
     * Swaps a button into a spinner + busy state for the duration of `task`,
     * so no async action ever leaves the UI looking frozen.
     */
    async withBusy(button, label, task) {
      if (!button) return task();
      const original = button.innerHTML;
      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
      button.innerHTML =
        '<i data-lucide="loader-circle" class="h-4 w-4 animate-spin" aria-hidden="true"></i>' +
        '<span>' + escapeHtml(label) + '</span>';
      refreshIcons(button);
      try {
        return await task();
      } finally {
        button.disabled = false;
        button.removeAttribute('aria-busy');
        button.innerHTML = original;
        refreshIcons(button);
      }
    },

    /* ---- API --------------------------------------------------------- */

    /** fetch wrapper that turns non-2xx and malformed JSON into thrown Errors. */
    async api(url, options = {}) {
      const init = { ...options };
      if (init.body && !(init.body instanceof FormData)) {
        init.headers = { 'Content-Type': 'application/json', ...(init.headers || {}) };
        if (typeof init.body !== 'string') init.body = JSON.stringify(init.body);
      }

      const res = await fetch(url, init);
      const text = await res.text();

      let data = null;
      try {
        data = text ? JSON.parse(text) : null;
      } catch (e) {
        throw new Error('The server returned an unexpected response.');
      }

      if (!res.ok) {
        throw new Error((data && data.error) || 'Request failed (' + res.status + ').');
      }
      return data;
    },

    /* ---- Speech synthesis -------------------------------------------- */

    speak(text, button) {
      if (!('speechSynthesis' in window)) {
        this.toast('Read aloud unavailable', 'error', 'This browser does not support speech synthesis.');
        return;
      }
      if (window.speechSynthesis.speaking) {
        window.speechSynthesis.cancel();
        document.querySelectorAll('[data-speaking="true"]').forEach((b) => {
          b.dataset.speaking = 'false';
          b.setAttribute('aria-label', 'Read aloud');
        });
        return;
      }

      const utterance = new SpeechSynthesisUtterance(text);
      utterance.rate = 1;
      utterance.lang = document.documentElement.lang || 'en-US';

      const voices = window.speechSynthesis.getVoices();
      const preferred = voices.find((v) =>
        /Google|Natural|Samantha/.test(v.name)
      );
      if (preferred) utterance.voice = preferred;

      if (button) {
        button.dataset.speaking = 'true';
        button.setAttribute('aria-label', 'Stop reading aloud');
        utterance.onend = () => {
          button.dataset.speaking = 'false';
          button.setAttribute('aria-label', 'Read aloud');
        };
      }

      window.speechSynthesis.speak(utterance);
    },

    stopSpeaking() {
      if ('speechSynthesis' in window) window.speechSynthesis.cancel();
    },

    /* ---- Search ------------------------------------------------------ */

    async smartSearch(query) {
      const q = (query || '').trim();
      if (!q) return;

      const pending = this.toast('Searching your notes', 'loading', 'Matching concepts with AI…');
      try {
        const notes = await this.api('/api/notes');
        const metadata = notes.map((n) => ({
          id: n.id,
          title: n.title,
          subject: n.subject,
          tags: n.tags || [],
        }));

        const data = await this.api('/api/ai/search', {
          method: 'POST',
          body: { query: q, metadata },
        });
        pending.remove();

        if (data.noteIds && data.noteIds.length > 0) {
          window.location.href = '/note/' + data.noteIds[0];
        } else {
          window.location.href = '/?q=' + encodeURIComponent(q);
        }
      } catch (e) {
        pending.remove();
        this.toast('Smart search unavailable', 'error', 'Falling back to keyword search.');
        window.location.href = '/?q=' + encodeURIComponent(q);
      }
    },

    /* ---- Registers --------------------------------------------------- */

    async createRegister(name, input) {
      const value = (name || '').trim();
      const errorEl = input ? document.getElementById(input.getAttribute('aria-describedby')) : null;

      if (!value) {
        if (input) {
          input.setAttribute('aria-invalid', 'true');
          if (errorEl) {
            errorEl.hidden = false;
            errorEl.textContent = 'Enter a subject name.';
          }
          input.focus();
        }
        return;
      }

      try {
        await this.api('/api/registers', { method: 'POST', body: { name: value } });
        window.location.reload();
      } catch (e) {
        if (input) input.setAttribute('aria-invalid', 'true');
        if (errorEl) {
          errorEl.hidden = false;
          errorEl.textContent = e.message;
        } else {
          this.toast('Could not create subject', 'error', e.message);
        }
      }
    },

    /* ---- Reminders --------------------------------------------------- */

    async toggleReminder(id, checkbox) {
      try {
        await this.api('/api/reminders/' + id + '/toggle', { method: 'POST' });
        window.location.reload();
      } catch (e) {
        if (checkbox) checkbox.checked = !checkbox.checked;
        this.toast('Could not update reminder', 'error', e.message);
      }
    },

    async deleteReminder(id, label) {
      const ok = await this.confirm({
        title: 'Delete this reminder?',
        body: label
          ? 'Reminder "' + label + '" will be removed. This cannot be undone.'
          : 'This reminder will be removed. This cannot be undone.',
        confirmLabel: 'Delete reminder',
      });
      if (!ok) return;

      try {
        await this.api('/api/reminders/' + id, { method: 'DELETE' });
        window.location.reload();
      } catch (e) {
        this.toast('Could not delete reminder', 'error', e.message);
      }
    },

    async saveReminder(payload) {
      try {
        await this.api('/api/reminders', { method: 'POST', body: payload });
        window.location.reload();
        return true;
      } catch (e) {
        this.toast('Could not save reminder', 'error', e.message);
        return false;
      }
    },

    /* ---- Notes ------------------------------------------------------- */

    async deleteNote(id, title) {
      const ok = await this.confirm({
        title: 'Delete this note?',
        body: title
          ? '"' + title + '" and its AI chat history will be permanently deleted.'
          : 'This note and its AI chat history will be permanently deleted.',
        confirmLabel: 'Delete note',
      });
      if (!ok) return;

      try {
        await this.api('/api/notes/' + id, { method: 'DELETE' });
        window.location.href = '/';
      } catch (e) {
        this.toast('Could not delete note', 'error', e.message);
      }
    },
  };

  /* ======================================================================
     Global wiring
     ====================================================================== */

  document.addEventListener('click', (event) => {
    const openBtn = event.target.closest('[data-dialog-open]');
    if (openBtn) {
      event.preventDefault();
      Dialog.open(openBtn.dataset.dialogOpen, openBtn);
      return;
    }

    const closeBtn = event.target.closest('[data-dialog-close]');
    if (closeBtn) {
      event.preventDefault();
      const host = closeBtn.closest('[id]');
      if (host) Dialog.close(host.id);
      return;
    }

    const menuTrigger = event.target.closest('[data-menu-trigger]');
    if (menuTrigger) {
      event.preventDefault();
      event.stopPropagation();
      Menu.toggle(menuTrigger.dataset.menuTrigger, menuTrigger);
      return;
    }

    // A click anywhere outside an open menu dismisses it.
    if (Menu.openId && !event.target.closest('#' + Menu.openId)) {
      Menu.closeAll();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      if (Menu.openId) {
        Menu.closeAll(true);
        return;
      }
      if (Dialog.stack.length) Dialog.closeTop();
      return;
    }
    if (event.key === 'Tab' && Dialog.stack.length) Dialog.trap(event);
  });

  // "/" focuses search the way it does in most developer tools, but never
  // while the user is already typing somewhere.
  document.addEventListener('keydown', (event) => {
    if (event.key !== '/' || event.metaKey || event.ctrlKey || event.altKey) return;
    const tag = document.activeElement?.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA' || document.activeElement?.isContentEditable) return;
    const search = document.getElementById('global-search');
    if (search) {
      event.preventDefault();
      search.focus();
      search.select();
    }
  });

  // Guard against a dialog being hidden out from under the user by a viewport
  // change (window resize, device rotation, on-screen keyboard).
  //
  // ResizeObserver on the root element is the reliable signal here: it fires
  // whenever the layout box actually changes, including under device emulation
  // and zoom, where a window 'resize' event is not always dispatched. It also
  // needs no knowledge of which breakpoints the dialogs happen to use.
  const onViewportChange = () => Dialog.closeIfHiddenByLayout();

  if ('ResizeObserver' in window) {
    new ResizeObserver(onViewportChange).observe(document.documentElement);
  }
  window.addEventListener('resize', onViewportChange, { passive: true });
  window.addEventListener('orientationchange', onViewportChange);

  // The Tailwind breakpoints every responsive utility in the app is built on.
  // A dialog can only be hidden by the layout at one of these widths, so a
  // change listener on each is a precise signal - and it is delivered even in
  // environments that throttle resize/ResizeObserver callbacks.
  [640, 768, 1024, 1280].forEach((width) => {
    const query = window.matchMedia('(min-width: ' + width + 'px)');
    // addEventListener on MediaQueryList is the modern API; addListener is the
    // deprecated fallback still needed by older Safari.
    if (query.addEventListener) query.addEventListener('change', onViewportChange);
    else if (query.addListener) query.addListener(onViewportChange);
  });

  document.addEventListener('DOMContentLoaded', () => refreshIcons());
  if (document.readyState !== 'loading') refreshIcons();

  window.NoteNest = NoteNest;
})();
