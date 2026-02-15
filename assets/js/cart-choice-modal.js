(function () {
  var data = window.fcsdCartChoiceData || {};
  var overlay = document.getElementById('fcsd-cart-choice-overlay');
  if (!overlay) return;

  var closeBtn = overlay.querySelector('.js-fcsd-cart-choice-close, .legal-modal__close');
  var goCartBtn = overlay.querySelector('.js-fcsd-go-cart');
  var continueBtn = overlay.querySelector('.js-fcsd-continue');

  function openModal() {
    // No mostrar si ya estamos en carrito
    if (data.isCartPage) return;

    overlay.hidden = false;
    document.body.classList.add('legal-modal-open');
    if (closeBtn) closeBtn.focus();
  }

  function closeModal() {
    overlay.hidden = true;
    document.body.classList.remove('legal-modal-open');
  }

  function goToCart() {
    if (data.cartUrl) {
      window.location.href = data.cartUrl;
      return;
    }
    closeModal();
  }

  document.addEventListener('click', function (e) {
    // cerrar al pulsar fuera
    if (e.target === overlay) {
      closeModal();
      return;
    }

    if (e.target.closest('.js-fcsd-cart-choice-close') || e.target.closest('.legal-modal__close')) {
      closeModal();
      return;
    }

    if (e.target.closest('.js-fcsd-continue')) {
      e.preventDefault();
      closeModal();
      return;
    }

    if (e.target.closest('.js-fcsd-go-cart')) {
      e.preventDefault();
      goToCart();
      return;
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !overlay.hidden) {
      closeModal();
    }
  });

  // API global para abrir desde shop.js
  window.fcsdCartChoiceModal = {
    open: openModal,
    close: closeModal
  };
})();
