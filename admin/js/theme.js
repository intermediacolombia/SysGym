document.addEventListener('DOMContentLoaded', () => {
  const cb = document.getElementById('themeToggle');
  if (!cb) return;

  const root = document.documentElement;
  const body = document.body;

  function applyTheme(mode, options = { persist: true, syncToggle: true }) {
    if (mode === 'dark') {
      root.classList.add('dark-mode');
      body.classList.add('dark-mode');
      if (options.syncToggle) cb.checked = true;
      if (options.persist) localStorage.setItem('theme-mode', 'dark');
    } else {
      root.classList.remove('dark-mode');
      body.classList.remove('dark-mode');
      if (options.syncToggle) cb.checked = false;
      if (options.persist) localStorage.setItem('theme-mode', 'light');
    }
  }

  // 1) Determinar tema inicial
  const saved = localStorage.getItem('theme-mode');
  const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  const initialMode = saved ? saved : (prefersDark ? 'dark' : 'light');

  // 2) Aplicar tema inicial y sincronizar el estado del switch
  applyTheme(initialMode, { persist: false, syncToggle: true });

  // 3) Responder a cambios del switch
  cb.addEventListener('change', () => {
    applyTheme(cb.checked ? 'dark' : 'light', { persist: true, syncToggle: false });
  });

  // 4) (Opcional) Si el sistema cambia de tema, y no hay preferencia guardada, seguirlo
  if (!saved && window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
      applyTheme(e.matches ? 'dark' : 'light', { persist: false, syncToggle: true });
    });
  }
});
