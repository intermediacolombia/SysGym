document.addEventListener('DOMContentLoaded', () => {
  const body = document.body;
  const toggle = document.getElementById('themeToggle');
  const icon = document.getElementById('themeIcon');
  const labelIcon = document.getElementById('themeLabelIcon');

  // Leer estado guardado
  const savedTheme = localStorage.getItem('theme-mode');

  // Aplicar tema guardado antes de mostrar contenido
  if (savedTheme === 'dark') {
    body.classList.add('dark-mode');
    if (toggle) toggle.checked = true;
  }

  // Marcar como listo (mostrar body)
  setTimeout(() => body.classList.add('theme-ready'), 50);

  // Sincronizar íconos
  function updateIcons(isDark) {
    if (!icon || !labelIcon) return;
    if (isDark) {
      icon.className = 'fas fa-moon text-info';
      labelIcon.className = 'fas fa-sun me-2 text-warning';
    } else {
      icon.className = 'fas fa-sun text-warning';
      labelIcon.className = 'fas fa-moon me-2 text-info';
    }
  }

  updateIcons(savedTheme === 'dark');

  // Evento del switch
  if (toggle) {
    toggle.addEventListener('change', function () {
      if (this.checked) {
        body.classList.add('dark-mode');
        localStorage.setItem('theme-mode', 'dark');
        updateIcons(true);
      } else {
        body.classList.remove('dark-mode');
        localStorage.setItem('theme-mode', 'light');
        updateIcons(false);
      }
    });
  }
});

