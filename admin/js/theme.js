document.addEventListener('DOMContentLoaded', () => {
  const body = document.body;
  const toggle = document.getElementById('themeToggle');
  const icon = document.getElementById('themeIcon');
  const labelIcon = document.getElementById('themeLabelIcon');

  // Leer modo guardado
  const savedTheme = localStorage.getItem('theme-mode');

  // Aplicar modo oscuro si estaba guardado
  if (savedTheme === 'dark') {
    body.classList.add('dark-mode');
    toggle.checked = true;
    icon.className = 'fas fa-moon text-info';
    labelIcon.className = 'fas fa-sun me-2 text-warning';
  } else {
    body.classList.remove('dark-mode');
    toggle.checked = false;
    icon.className = 'fas fa-sun text-warning';
    labelIcon.className = 'fas fa-moon me-2 text-info';
  }

  // Escuchar cambios del switch
  toggle.addEventListener('change', function () {
    if (this.checked) {
      body.classList.add('dark-mode');
      localStorage.setItem('theme-mode', 'dark');
      icon.className = 'fas fa-moon text-info';
      labelIcon.className = 'fas fa-sun me-2 text-warning';
    } else {
      body.classList.remove('dark-mode');
      localStorage.setItem('theme-mode', 'light');
      icon.className = 'fas fa-sun text-warning';
      labelIcon.className = 'fas fa-moon me-2 text-info';
    }
  });
});

