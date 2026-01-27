(function () {
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
  const body = document.body;
  const btn  = document.getElementById('contrastToggle');

  if (!btn) {
    return; // No hay botón de contraste en el DOM, no hacemos nada
  }

  // Cargar tema guardado o respetar preferencia del sistema
  const saved = localStorage.getItem('fcsd-theme');
  if (saved === 'dark' || (!saved && prefersDark.matches)) {
    body.classList.add('theme-dark');
  }

  function updateBtn() {
    const isDark = body.classList.contains('theme-dark');
    btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
  }

  btn.addEventListener('click', () => {
    body.classList.toggle('theme-dark');
    const isDark = body.classList.contains('theme-dark');
    localStorage.setItem('fcsd-theme', isDark ? 'dark' : 'light');
    updateBtn();
  });

  updateBtn();
})();
