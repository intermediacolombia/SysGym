document.addEventListener('DOMContentLoaded', () => {
  const body = document.body;
  const savedTheme = localStorage.getItem('theme-mode');

  // Aplica el modo oscuro si estaba guardado
  if (savedTheme === 'dark') {
    body.classList.add('dark-mode');
  } else {
    body.classList.remove('dark-mode');
  }

  // Mostrar el contenido después de aplicar el tema
  body.classList.add('theme-ready');

  // Escuchar cambios desde el switch (si existe)
  const themeSwitch = document.getElementById('themeToggle');
  if (themeSwitch) {
    themeSwitch.addEventListener('change', (e) => {
      if (e.target.checked) {
        body.classList.add('dark-mode');
        localStorage.setItem('theme-mode', 'dark');
      } else {
        body.classList.remove('dark-mode');
        localStorage.setItem('theme-mode', 'light');
      }
    });
  }
});
