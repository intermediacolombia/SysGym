document.addEventListener('DOMContentLoaded', () => {
  const body = document.body;
  const toggle = document.getElementById('themeToggle');
  const icon = document.getElementById('themeIcon');
  const labelIcon = document.getElementById('themeLabelIcon');

  // Leer estado guardado (sin tomar modo del sistema)
  const savedTheme = localStorage.getItem('theme-mode') || 'light';

  // Aplicar tema guardado
  if (savedTheme === 'dark') {
    body.classList.add('dark-mode');
    if (toggle) toggle.checked = true;
  }

  // Mostrar body con transición lista
  setTimeout(() => body.classList.add('theme-ready'), 50);

  // Función para actualizar íconos
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
      const isDark = this.checked;
      body.classList.toggle('dark-mode', isDark);
      localStorage.setItem('theme-mode', isDark ? 'dark' : 'light');
      updateIcons(isDark);
    });
  }
});


