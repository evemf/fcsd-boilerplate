(function () {
  // El tema localiza datos como `fcsdLegalData` (desde functions.php).
  // Versiones anteriores podían usar `fcsdLegalModals`. Aceptamos ambos.
  var data = window.fcsdLegalData || window.fcsdLegalModals;
  if (typeof data === 'undefined') {
    return;
  }
  var overlay  = document.getElementById('fcsd-legal-overlay');
  if (!overlay) return;

  var titleEl  = overlay.querySelector('#fcsd-legal-title');
  var bodyEl   = overlay.querySelector('.legal-modal__body');
  var closeBtn = overlay.querySelector('.legal-modal__close');

  // Accesibilidad: etiqueta del botón de cierre si llega por localización.
  if (data.closeText && closeBtn) {
    closeBtn.setAttribute('aria-label', data.closeText);
  }

  function openModal(key) {
    var item = data[key];
    if (!item) return;

    titleEl.textContent = item.title || '';
    bodyEl.innerHTML    = item.content || '';

    overlay.hidden = false;
    document.body.classList.add('legal-modal-open');
    if (closeBtn) closeBtn.focus();
  }

  function closeModal() {
    overlay.hidden = true;
    document.body.classList.remove('legal-modal-open');
  }

  document.addEventListener('click', function (e) {
    // abrir
    var trigger = e.target.closest('[data-legal-key]');
    if (trigger) {
      e.preventDefault();
      openModal(trigger.getAttribute('data-legal-key'));
      return;
    }

    // cerrar al pulsar fuera
    if (e.target === overlay) {
      closeModal();
    }

    // cerrar botón
    if (e.target.closest('.legal-modal__close')) {
      closeModal();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !overlay.hidden) {
      closeModal();
    }
  });
})();
