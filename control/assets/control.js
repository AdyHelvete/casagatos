document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initTabs();
  initConfirmForms();
  initCopyButtons();
  initGalleryUpload();
  initFormEnhancements();
  initCodeEditor();
  initHelpTips();
});

function initSidebar() {
  const sidebar = document.getElementById('controlSidebar');
  const toggle = document.getElementById('controlMenuToggle');
  const overlay = document.getElementById('controlSidebarOverlay');

  if (!sidebar || !toggle) return;

  const open = () => {
    sidebar.classList.add('is-open');
    overlay?.classList.add('is-visible');
    overlay?.removeAttribute('hidden');
    toggle.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  };

  const close = () => {
    sidebar.classList.remove('is-open');
    overlay?.classList.remove('is-visible');
    overlay?.setAttribute('hidden', '');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  };

  toggle.addEventListener('click', () => {
    if (sidebar.classList.contains('is-open')) {
      close();
    } else {
      open();
    }
  });

  overlay?.addEventListener('click', close);

  sidebar.querySelectorAll('.control-nav__link').forEach((link) => {
    link.addEventListener('click', () => {
      if (window.matchMedia('(max-width: 900px)').matches) {
        close();
      }
    });
  });

  window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') close();
  });
}

function initTabs() {
  document.querySelectorAll('[data-control-tabs]').forEach((root) => {
    const nav = root.querySelector(':scope > .control-tabs__nav');
    const panelsWrap = root.querySelector(':scope > .control-tabs__panels');
    if (!nav || !panelsWrap) return;

    const tabs = Array.from(nav.querySelectorAll('.control-tabs__tab[role="tab"]'));
    const panels = Array.from(panelsWrap.querySelectorAll(':scope > .control-tabs__panel[role="tabpanel"]'));
    if (tabs.length === 0 || panels.length === 0) return;

    const groupId = root.dataset.tabsGroup || 'tabs';
    const hashRaw = window.location.hash.replace(/^#/, '');
    const hashTab = hashRaw.startsWith(`${groupId}-`) ? hashRaw.slice(groupId.length + 1) : '';
    const initial = hashTab && tabs.some((t) => t.dataset.tab === hashTab)
      ? hashTab
      : (root.dataset.initialTab || tabs[0].dataset.tab || '');

    const activate = (tabId, focusTab = false) => {
      tabs.forEach((tab) => {
        const isActive = tab.dataset.tab === tabId;
        tab.classList.toggle('is-active', isActive);
        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        tab.tabIndex = isActive ? 0 : -1;
      });

      panels.forEach((panel) => {
        const isActive = panel.dataset.panel === tabId;
        panel.classList.toggle('is-active', isActive);
        if (isActive) {
          panel.removeAttribute('hidden');
        } else {
          panel.setAttribute('hidden', '');
        }
      });

      if (focusTab) {
        tabs.find((t) => t.dataset.tab === tabId)?.focus();
      }

      const newHash = `#${groupId}-${tabId}`;
      if (window.location.hash !== newHash) {
        history.replaceState(null, '', newHash);
      }

      const hiddenField = root.closest('form')?.querySelector('[data-active-tab-field]');
      if (hiddenField instanceof HTMLInputElement) {
        hiddenField.value = tabId;
      }
    };

    activate(initial);

    tabs.forEach((tab, index) => {
      tab.addEventListener('click', () => activate(tab.dataset.tab || '', true));

      tab.addEventListener('keydown', (event) => {
        let targetIndex = index;
        if (event.key === 'ArrowRight') targetIndex = (index + 1) % tabs.length;
        else if (event.key === 'ArrowLeft') targetIndex = (index - 1 + tabs.length) % tabs.length;
        else if (event.key === 'Home') targetIndex = 0;
        else if (event.key === 'End') targetIndex = tabs.length - 1;
        else return;

        event.preventDefault();
        const nextTab = tabs[targetIndex];
        activate(nextTab.dataset.tab || '', true);
      });
    });

    const form = root.closest('form');
    form?.addEventListener('submit', () => {
      const active = tabs.find((t) => t.classList.contains('is-active'));
      if (active?.dataset.tab) {
        sessionStorage.setItem(`control-tab-${groupId}`, active.dataset.tab);
      }
    });

    const saved = sessionStorage.getItem(`control-tab-${groupId}`);
    if (saved && tabs.some((t) => t.dataset.tab === saved) && !hashTab) {
      activate(saved);
      sessionStorage.removeItem(`control-tab-${groupId}`);
    }
  });
}

function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function showToast(message) {
  const toast = document.getElementById('controlToast');
  if (!toast) return;
  toast.textContent = message;
  toast.hidden = false;
  toast.classList.add('is-visible');
  window.clearTimeout(showToast._timer);
  showToast._timer = window.setTimeout(() => {
    toast.classList.remove('is-visible');
    toast.hidden = true;
  }, 2600);
}

function initConfirmForms() {
  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const message = form.getAttribute('data-confirm') || '¿Confirmar acción?';
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });
}

function initCopyButtons() {
  document.querySelectorAll('[data-copy-url]').forEach((button) => {
    button.addEventListener('click', async () => {
      const value = button.getAttribute('data-copy-value') || '';
      if (!value) return;

      try {
        await navigator.clipboard.writeText(value);
        showToast('Copiado al portapapeles');
      } catch (error) {
        const input = button.closest('.control-copy-field')?.querySelector('[data-copy-source]');
        if (input) {
          input.select();
          document.execCommand('copy');
          showToast('Copiado al portapapeles');
        }
      }
    });
  });
}

function initGalleryUpload() {
  const form = document.getElementById('galleryUploadForm');
  const input = document.getElementById('galleryFileInput');
  const pickButton = document.querySelector('[data-gallery-pick]');

  if (!form || !input) return;

  pickButton?.addEventListener('click', () => input.click());

  input.addEventListener('change', () => {
    if (input.files?.length) {
      uploadGalleryFiles(form, input.files);
    }
  });

  ['dragenter', 'dragover'].forEach((eventName) => {
    form.addEventListener(eventName, (event) => {
      event.preventDefault();
      form.classList.add('is-dragover');
    });
  });

  ['dragleave', 'drop'].forEach((eventName) => {
    form.addEventListener(eventName, (event) => {
      event.preventDefault();
      form.classList.remove('is-dragover');
    });
  });

  form.addEventListener('drop', (event) => {
    const files = event.dataTransfer?.files;
    if (files?.length) {
      uploadGalleryFiles(form, files);
    }
  });

  form.addEventListener('submit', (event) => event.preventDefault());
}

async function uploadGalleryFiles(form, fileList) {
  const endpoint = form.getAttribute('data-upload-endpoint') || form.action || window.location.pathname;
  const formData = new FormData(form);

  formData.delete('images[]');
  Array.from(fileList).forEach((file) => {
    formData.append('images[]', file);
  });

  form.classList.add('is-uploading');

  try {
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': getCsrfToken(),
      },
      body: formData,
      credentials: 'same-origin',
    });

    const payload = await response.json();
    if (!response.ok || !payload.ok) {
      throw new Error((payload.errors || []).join(' ') || payload.error || 'No se pudieron subir las imágenes.');
    }

    showToast(`${payload.uploaded?.length || 0} imagen(es) subida(s)`);
    window.setTimeout(() => window.location.reload(), 700);
  } catch (error) {
    showToast(error.message || 'Error al subir imágenes');
  } finally {
    form.classList.remove('is-uploading');
    const input = document.getElementById('galleryFileInput');
    if (input) input.value = '';
  }
}

function initFormEnhancements() {
  document.querySelectorAll('.control-form input, .control-form textarea, .control-form select').forEach((field) => {
    field.addEventListener('invalid', () => {
      field.closest('label')?.classList.add('is-invalid');
    });
    field.addEventListener('input', () => {
      field.closest('label')?.classList.remove('is-invalid');
    });
  });

  document.querySelectorAll('.control-form input[type="file"]').forEach((input) => {
    input.addEventListener('change', () => {
      const label = input.closest('label');
      if (!label) return;
      const name = input.files?.[0]?.name;
      label.dataset.fileName = name || '';
    });
  });
}

function initCodeEditor() {
  document.querySelectorAll('[data-code-editor]').forEach((editor) => {
    const initialValue = editor.value;

    editor.addEventListener('keydown', (event) => {
      if (event.key === 'Tab') {
        event.preventDefault();
        insertAtCursor(editor, '  ');
        return;
      }

      if ((event.ctrlKey || event.metaKey) && event.key === 's') {
        event.preventDefault();
        editor.form?.requestSubmit();
      }
    });

    editor.addEventListener('input', () => {
      editor.dataset.dirty = editor.value === initialValue ? '' : '1';
    });

    editor.form?.addEventListener('submit', () => {
      editor.dataset.dirty = '';
    });

    window.addEventListener('beforeunload', (event) => {
      if (editor.dataset.dirty === '1') {
        event.preventDefault();
        event.returnValue = '';
      }
    });
  });
}

function insertAtCursor(field, text) {
  const start = field.selectionStart;
  const end = field.selectionEnd;
  field.value = field.value.slice(0, start) + text + field.value.slice(end);
  field.selectionStart = field.selectionEnd = start + text.length;
  field.dispatchEvent(new Event('input', { bubbles: true }));
}

function initHelpTips() {
  document.querySelectorAll('.control-help').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      const willOpen = !button.classList.contains('is-open');
      document.querySelectorAll('.control-help.is-open').forEach((el) => el.classList.remove('is-open'));
      button.classList.toggle('is-open', willOpen);
    });
  });

  document.addEventListener('click', () => {
    document.querySelectorAll('.control-help.is-open').forEach((el) => el.classList.remove('is-open'));
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      document.querySelectorAll('.control-help.is-open').forEach((el) => el.classList.remove('is-open'));
    }
  });
}
