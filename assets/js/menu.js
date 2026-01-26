(function () {
  const nav = document.querySelector('.navbar.mainnav');
  if (!nav) {
    return; // No hay navbar principal, no hacemos nada
  }

  const isDesktop = () => window.matchMedia('(min-width: 768px)').matches;

  function openMega(id, link) {
    if (!isDesktop()) {
      return;
    }
    closeAll();
    const panel = document.getElementById(id);
    if (panel) {
      panel.classList.add('show');
      link.setAttribute('aria-expanded', 'true');
    }
  }

  function closeAll() {
    nav.querySelectorAll('.mega.show').forEach(m =>
      m.classList.remove('show')
    );
    nav.querySelectorAll('.nav-link[aria-expanded="true"]').forEach(a =>
      a.setAttribute('aria-expanded', 'false')
    );
  }

  // Hover/focus handlers para elementos con mega menú
  nav.querySelectorAll('[data-mega]').forEach(link => {
    const id = link.getAttribute('data-mega');

    link.addEventListener('mouseenter', () => openMega(id, link));
    link.addEventListener('focus',     () => openMega(id, link));

    link.addEventListener('click', e => {
      if (isDesktop()) {
        e.preventDefault(); // En desktop mantenemos el mega abierto al hacer click
        openMega(id, link);
      }
    });
  });

  // Cerrar cuando se sale de la navbar o se pulsa ESC
  nav.addEventListener('mouseleave', closeAll);

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      closeAll();
    }
  });

  document.addEventListener('click', e => {
    if (!nav.contains(e.target)) {
      closeAll();
    }
  });
})();
