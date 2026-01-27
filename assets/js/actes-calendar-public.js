/* global bootstrap */

/**
 * Calendari d'actes (frontend)
 * - Activa tooltips (Bootstrap) sobre punts/píndoles.
 * - En click sobre un dia amb esdeveniments, obre un modal en mode consulta.
 */
(function () {
  'use strict';

  function safeJsonParse(str) {
    try {
      return JSON.parse(str);
    } catch (e) {
      return null;
    }
  }

  function initTooltips(root) {
    if (!root || typeof bootstrap === 'undefined' || !bootstrap.Tooltip) return;
    var tooltipEls = root.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipEls.forEach(function (el) {
      // Evita doble init.
      if (el._fcsdTooltip) return;
      el._fcsdTooltip = new bootstrap.Tooltip(el, {
        trigger: 'hover focus',
        html: false,
        customClass: 'fcsd-tooltip',
        container: 'body',
        boundary: 'window'
      });
    });
  }

  function formatDayTitle(isoDay) {
    // isoDay = YYYY-MM-DD
    var parts = (isoDay || '').split('-');
    if (parts.length !== 3) return isoDay || '';
    var d = new Date(parts[0], parts[1] - 1, parts[2]);
    try {
      return d.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    } catch (e) {
      return isoDay;
    }
  }

  function buildEventHtml(ev) {
    var title = ev && ev.title ? ev.title : '';
    var permalink = ev && ev.permalink ? ev.permalink : '';
    var timeRange = ev && ev.time_range ? ev.time_range : '';
    var excerpt = ev && ev.excerpt ? ev.excerpt : '';
    var content = ev && ev.content ? ev.content : '';
    var needsTicket = !!(ev && ev.needs_ticket);
    var thumb = ev && ev.thumb ? ev.thumb : '';
    var color = ev && ev.color ? ev.color : '';

    var badge = needsTicket
      ? '<span class="badge text-bg-warning ms-2">Entrada prèvia</span>'
      : '';

    var media = '';
    if (thumb) {
      media = '<div class="fcsd-day-modal__thumb"><img src="' + thumb + '" alt="" loading="lazy"></div>';
    }

    var details = '';
    if (content) {
      details = '<div class="fcsd-day-modal__content">' + content + '</div>';
    } else if (excerpt) {
      details = '<p class="mb-0">' + excerpt + '</p>';
    }

    var left = color ? ' style="border-left-color:' + color + '"' : '';

    return (
      '<article class="fcsd-day-modal__event"' + left + '>' +
        '<header class="d-flex align-items-start justify-content-between gap-2">' +
          '<div>' +
            '<h3 class="h6 mb-1">' +
              (permalink ? '<a href="' + permalink + '">' + title + '</a>' : title) +
              badge +
            '</h3>' +
            (timeRange ? '<div class="text-muted small">' + timeRange + '</div>' : '') +
          '</div>' +
        '</header>' +
        '<div class="fcsd-day-modal__body d-flex gap-3 mt-3">' +
          media +
          '<div class="fcsd-day-modal__text flex-grow-1">' + details + '</div>' +
        '</div>' +
      '</article>'
    );
  }

  function ensureModal() {
    var modalEl = document.getElementById('fcsdActesDayModal');
    if (!modalEl) return null;
    if (typeof bootstrap === 'undefined' || !bootstrap.Modal) return null;
    if (!modalEl._fcsdModal) {
      modalEl._fcsdModal = new bootstrap.Modal(modalEl, { focus: true });
    }
    return modalEl._fcsdModal;
  }

  function openDayModal(dayEl) {
    if (!dayEl) return;
    var day = dayEl.getAttribute('data-day');
    var raw = dayEl.getAttribute('data-events');
    if (!day || !raw) return;

    var events = safeJsonParse(raw);
    if (!events || !Array.isArray(events) || events.length === 0) return;

    var modal = ensureModal();
    if (!modal) return;

    var modalEl = document.getElementById('fcsdActesDayModal');
    var titleEl = modalEl.querySelector('.modal-title');
    var bodyEl = modalEl.querySelector('.modal-body');

    if (titleEl) titleEl.textContent = formatDayTitle(day);

    if (bodyEl) {
      var html = '';
      events.forEach(function (ev) {
        html += buildEventHtml(ev);
      });
      bodyEl.innerHTML = html;
    }

    modal.show();
  }

  function initDayClicks(root) {
    var dayEls = root.querySelectorAll('.actes-calendar__day[data-events][data-day]');
    dayEls.forEach(function (dayEl) {
      if (dayEl._fcsdDayClick) return;
      dayEl._fcsdDayClick = true;

      dayEl.setAttribute('role', 'button');
      dayEl.setAttribute('tabindex', '0');

      function handler(e) {
        // Permite selección de texto en modal, etc.
        e.preventDefault();
        openDayModal(dayEl);
      }

      dayEl.addEventListener('click', handler);
      dayEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          handler(e);
        }
      });
    });

    // Si se hace click en el título del evento, abrimos el modal (modo consulta)
    // en lugar de navegar directamente. Dentro del modal habrá enlace al acto.
    var eventLinks = root.querySelectorAll('.actes-calendar__event-link');
    eventLinks.forEach(function (a) {
      if (a._fcsdEventLink) return;
      a._fcsdEventLink = true;
      a.addEventListener('click', function (e) {
        var dayEl = a.closest('.actes-calendar__day[data-events][data-day]');
        if (!dayEl) return;
        e.preventDefault();
        openDayModal(dayEl);
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var root = document;
    initTooltips(root);
    initDayClicks(root);
  });
})();
