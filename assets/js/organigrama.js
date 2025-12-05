(function () {
  document.addEventListener('DOMContentLoaded', function () {
    // Imagen del organigrama en la página
    var img = document.querySelector('.org-image-block .org-image');
    if (!img) return;

    var container = img.parentNode;

    // Botón para abrir el visor
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'button org-image-open';
    btn.textContent = 'Veure a pantalla completa';
    container.appendChild(btn);

    // Crear lightbox
    var overlay = document.createElement('div');
    overlay.className = 'org-lightbox';
    overlay.setAttribute('aria-hidden', 'true');

    overlay.innerHTML =
      '<div class="org-lightbox-inner">' +
        '<div class="org-lightbox-toolbar">' +
          '<button type="button" class="org-zoom-out">−</button>' +
          '<button type="button" class="org-zoom-reset">100%</button>' +
          '<button type="button" class="org-zoom-in">+</button>' +
          '<button type="button" class="org-close" aria-label="Tancar">×</button>' +
        '</div>' +
        '<div class="org-lightbox-canvas">' +
          '<img class="org-lightbox-image" src="' + img.src + '" alt="' + (img.alt || '') + '">' +
        '</div>' +
      '</div>';

    document.body.appendChild(overlay);

    var lightbox   = overlay;
    var closeBtn   = overlay.querySelector('.org-close');
    var zoomInBtn  = overlay.querySelector('.org-zoom-in');
    var zoomOutBtn = overlay.querySelector('.org-zoom-out');
    var zoomReset  = overlay.querySelector('.org-zoom-reset');
    var bigImg     = overlay.querySelector('.org-lightbox-image');
    var canvas     = overlay.querySelector('.org-lightbox-canvas');

    var scale = 1;
    var posX = 0;
    var posY = 0;
    var dragging = false;
    var startX, startY;

    function applyTransform() {
      bigImg.style.transform =
        'translate(' + posX + 'px,' + posY + 'px) scale(' + scale + ')';
    }

    btn.addEventListener('click', function () {
      scale = 1;
      posX  = 0;
      posY  = 0;
      applyTransform();
      lightbox.classList.add('is-open');
      lightbox.setAttribute('aria-hidden', 'false');
    });

    closeBtn.addEventListener('click', function () {
      lightbox.classList.remove('is-open');
      lightbox.setAttribute('aria-hidden', 'true');
    });

    zoomInBtn.addEventListener('click', function () {
      scale = Math.min(scale + 0.2, 4);
      applyTransform();
    });

    zoomOutBtn.addEventListener('click', function () {
      scale = Math.max(scale - 0.2, 0.4);
      applyTransform();
    });

    zoomReset.addEventListener('click', function () {
      scale = 1;
      posX  = 0;
      posY  = 0;
      applyTransform();
    });

    canvas.addEventListener('mousedown', function (e) {
      dragging = true;
      canvas.style.cursor = 'grabbing';
      startX = e.clientX - posX;
      startY = e.clientY - posY;
    });

    window.addEventListener('mouseup', function () {
      dragging = false;
      canvas.style.cursor = 'grab';
    });

    window.addEventListener('mousemove', function (e) {
      if (!dragging) return;
      posX = e.clientX - startX;
      posY = e.clientY - startY;
      applyTransform();
    });

    canvas.addEventListener(
      'wheel',
      function (e) {
        e.preventDefault();
        var delta = e.deltaY < 0 ? 0.1 : -0.1;
        var newScale = Math.min(4, Math.max(0.4, scale + delta));
        scale = newScale;
        applyTransform();
      },
      { passive: false }
    );
  });
})();
