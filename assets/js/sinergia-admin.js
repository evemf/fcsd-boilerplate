jQuery(function ($) {

  let eventsSortKey    = 'start_date'; // ordre per defecte esdeveniments
  let eventsSortDir    = 'asc';
  let contactsSortKey  = 'name';       // ordre per defecte contactes
  let contactsSortDir  = 'asc';


  /* -------------------------------------------------------------------------
     PING INICIAL A SINERGIA (estado real del login)
  ------------------------------------------------------------------------- */
  function pingSinergia() {
    $('#sinergia-login-status').text('Comprovant connexió amb Sinergia...');

    return $.post(fcsdSinergiaAjax.ajax_url, {
      action: 'fcsd_sinergia_ping',
      nonce: fcsdSinergiaAjax.nonce
    })
    .done(function (res) {
      if (res.success && res.data.logged) {
        $('#sinergia-login-status')
          .text('Connectat a Sinergia.')
          .css('color', '#46b450');
      } else {
        $('#sinergia-login-status')
          .text(res.data.message || 'No connectat.')
          .css('color', '#d63638');
      }
    })
    .fail(function () {
      $('#sinergia-login-status')
        .text('Error de connexió.')
        .css('color', '#d63638');
    });
  }

  pingSinergia();

  /* -------------------------------------------------------------------------
     LOGIN MANUAL
  ------------------------------------------------------------------------- */
  $('#sinergia-login-form').on('submit', function (e) {
    e.preventDefault();

    var $form = $(this);
    var data = $form.serialize();

    $('#sinergia-login-status')
      .text('Connectant amb Sinergia...')
      .css('color', '#666');

    $.post(fcsdSinergiaAjax.ajax_url, data)
      .done(function (res) {
        if (res.success) {
          $('#sinergia-login-status')
            .text('Connectat a Sinergia.')
            .css('color', '#46b450');
        } else {
          $('#sinergia-login-status')
            .text(res.data.message || 'Error de login.')
            .css('color', '#d63638');
        }
      })
      .fail(function () {
        $('#sinergia-login-status')
          .text('Error de connexió.')
          .css('color', '#d63638');
      });
  });

  /* -------------------------------------------------------------------------
     LOGOUT MANUAL
  ------------------------------------------------------------------------- */
  $('#sinergia-logout-btn').on('click', function (e) {
    e.preventDefault();

    $('#sinergia-login-status')
      .text('Tancant sessió a Sinergia...')
      .css('color', '#666');

    $.post(fcsdSinergiaAjax.ajax_url, {
      action: 'fcsd_sinergia_logout',
      nonce: fcsdSinergiaAjax.nonce
    })
    .done(function (res) {
      if (res.success) {
        $('#sinergia-login-status')
          .text('Sessió tancada.')
          .css('color', '#666');
      } else {
        $('#sinergia-login-status')
          .text(res.data.message || 'Error en tancar sessió.')
          .css('color', '#d63638');
      }
    })
    .fail(function () {
      $('#sinergia-login-status')
        .text('Error de connexió.')
        .css('color', '#d63638');
    });
  });

  /* -------------------------------------------------------------------------
     TABS
  ------------------------------------------------------------------------- */
  function openTab(tab) {
    $('.fcsd-tab-pane').hide();
    $('.nav-tab').removeClass('nav-tab-active');

    $('#tab-' + tab).show();
    $('.nav-tab[data-tab="' + tab + '"]').addClass('nav-tab-active');

    if (tab === 'contacts') {
      loadContactsPage(1, $('#contact-search').val() || '');
    } else if (tab === 'events') {
      loadEventsPage(1, $('#event-search').val() || '');
    }
  }

    // -------------------------------------------------------------------------
  // Marca visualment la columna ordenada en una taula
  // -------------------------------------------------------------------------
  function syncSortIndicators(tableSelector, sortKey, sortDir) {
    var $table = $(tableSelector);
    if (!$table.length || !sortKey) return;

    var $th = $table.find('th[data-sort-key="' + sortKey + '"]');
    if (!$th.length) return;

    // Netegem estat a totes les columnes
    $table.find('th')
      .removeClass('sorted-asc sorted-desc')
      .removeData('sort-dir');

    // Marquem la columna actual
    $th
      .data('sort-dir', sortDir)
      .addClass(sortDir === 'desc' ? 'sorted-desc' : 'sorted-asc');
  }

  /* -------------------------------------------------------------------------
     CARGA CONTACTS (cache)
  ------------------------------------------------------------------------ */
  function loadContactsPage(page, search) {
    var $content = $('#fcsd-contacts-content');
    var previous = $content.html();

    $content.html('<em>Carregant contacts...</em>');

    $.post(fcsdSinergiaAjax.ajax_url, {
        action:   'fcsd_sinergia_get_contacts',
        nonce:    fcsdSinergiaAjax.nonce,
        page:     page || 1,
        search:   search || '',
        sort_key: contactsSortKey,
        sort_dir: contactsSortDir
      })
      .done(function (res) {
        if (res.success) {
          $content.html(res.data.html);

          // ← Vuelve a marcar la columna ordenada
          syncSortIndicators('#contacts-table', contactsSortKey, contactsSortDir);

        } else {
          $content.html(previous);
          alert(res.data.message || 'Error carregant contacts');
        }
      })
      .fail(function () {
        $content.html(previous);
        alert('Error de connexió amb el servidor.');
      });
  }

  /* -------------------------------------------------------------------------
     CARGA ESDEVENIMENTS (cache)
  ------------------------------------------------------------------------ */
  function loadEventsPage(page, search) {
    var $content = $('#fcsd-events-content');
    var previous = $content.html();

    $content.html('<em>Carregant esdeveniments...</em>');

    $.post(fcsdSinergiaAjax.ajax_url, {
        action:   'fcsd_sinergia_get_events',
        nonce:    fcsdSinergiaAjax.nonce,
        page:     page || 1,
        search:   search || '',
        sort_key: eventsSortKey,
        sort_dir: eventsSortDir
      })
      .done(function (res) {
        if (res.success) {
          $content.html(res.data.html);

          // ← Vuelve a marcar la columna ordenada
          syncSortIndicators('#events-table', eventsSortKey, eventsSortDir);

        } else {
          $content.html(previous);
          alert(res.data.message || 'Error carregant esdeveniments');
        }
      })
      .fail(function () {
        $content.html(previous);
        alert('Error de connexió amb el servidor.');
      });
  }


  /* -------------------------------------------------------------------------
     BUSCADOR CONTACTES
  ------------------------------------------------------------------------ */
  $(document).on('submit', '#contact-search-form', function (e) {
    e.preventDefault();
    var search = $('#contact-search').val() || '';
    loadContactsPage(1, search);
  });

  /* -------------------------------------------------------------------------
     BUSCADOR ESDEVENIMENTS
  ------------------------------------------------------------------------ */
  $(document).on('submit', '#event-search-form', function (e) {
    e.preventDefault();
    var search = $('#event-search').val() || '';
    loadEventsPage(1, search);
  });

  /* -------------------------------------------------------------------------
     PAGINACIÓ CONTACTES
  ------------------------------------------------------------------------ */
  $(document).on('click', '.fcsd-contacts-pagination a', function (e) {
    e.preventDefault();

    var $link = $(this);
    var page  = $link.data('page');
    if (!page) return;

    loadContactsPage(page, $('#contact-search').val() || '');
  });

  /* -------------------------------------------------------------------------
     PAGINACIÓ ESDEVENIMENTS
  ------------------------------------------------------------------------ */
  $(document).on('click', '.fcsd-events-pagination a', function (e) {
    e.preventDefault();

    var $link = $(this);
    var page  = $link.data('page');
    if (!page) return;

    loadEventsPage(page, $('#event-search').val() || '');
  });

    /* -------------------------------------------------------------------------
     ORDENACIÓ DE COLUMNES (CONTACTES I ESDEVENIMENTS)
     → actualitza sort_key / sort_dir i recarrega PÀGINA 1
  ------------------------------------------------------------------------ */
  $(document).on(
    'click',
    '#events-table th[data-sort-key], #contacts-table th[data-sort-key]',
    function () {
      const $th      = $(this);
      const sortKey  = $th.data('sort-key');
      const $table   = $th.closest('table');
      const isEvents = $table.attr('id') === 'events-table';

      if (!sortKey) return;

      // Estat actual
      let currentKey = isEvents ? eventsSortKey : contactsSortKey;
      let currentDir = isEvents ? eventsSortDir : contactsSortDir;

      // Si canviem de columna, comencem en ASC; si repetim, fem toggle
      let newKey = sortKey;
      let newDir = (currentKey === sortKey)
        ? (currentDir === 'asc' ? 'desc' : 'asc')
        : 'asc';

      // Actualitzem globals i recarreguem pàgina 1
      if (isEvents) {
        eventsSortKey = newKey;
        eventsSortDir = newDir;
        loadEventsPage(1, $('#event-search').val() || '');
      } else {
        contactsSortKey = newKey;
        contactsSortDir = newDir;
        loadContactsPage(1, $('#contact-search').val() || '');
      }
    }
  );


  /* -------------------------------------------------------------------------
     BOTONS DE SINCRONITZACIÓ
  ------------------------------------------------------------------------- */
    /* -------------------------------------------------------------------------
     BOTÓ SINCRONITZAR CONTACTS (per batches)
  ------------------------------------------------------------------------- */
  $('#btn-sync-contacts').on('click', function (e) {
    e.preventDefault();

    if (!confirm('Segur que vols sincronitzar tots els Contacts des de Sinergia?')) {
      return;
    }

    var totalSaved     = 0;
    var totalProcessed = 0;
    var nextOffset     = 0;
    var finished       = false;

    $('#contacts-sync-status').text('Sincronitzant contacts...');
    $('#btn-sync-contacts, #btn-sync-events').prop('disabled', true);

    function runContactsBatch() {
      $.post(fcsdSinergiaAjax.ajax_url, {
        action: 'fcsd_sinergia_sync_contacts',
        nonce:  fcsdSinergiaAjax.nonce,
        offset: nextOffset
      })
      .done(function (res) {
        if (res && res.success) {
          var data       = res.data || {};
          var batchSaved = parseInt(data.saved, 10) || 0;
          var batchTotal = parseInt(data.total, 10) || 0;

          totalSaved     += batchSaved;
          // No tenim el "count" exacte del batch, però podem deduir que com a mínim hem intentat guardar batchSaved
          totalProcessed += batchSaved;

          if (data.finished) {
            finished = true;

            $('#contacts-sync-status')
              .text('Sincronització completada. Guardats: ' + totalSaved + ' de ' + (batchTotal || totalProcessed))
              .css('color', '#46b450');

            if (data.last_sync_human) {
              $('#contacts-last-sync').text(data.last_sync_human);
            }

            // Recarregar la taula de contacts
            loadContactsPage(1, $('#contact-search').val() || '');
          } else {
            nextOffset = parseInt(data.next_offset, 10) || (nextOffset + 200); // caiguda de seguretat

            $('#contacts-sync-status')
              .text('Sincronitzant contacts... (' + totalProcessed + ' processats)')
              .css('color', '');

            // crida següent batch
            runContactsBatch();
          }
        } else {
          finished = true;
          $('#contacts-sync-status')
            .text((res && res.data && res.data.message) || 'Error en sincronitzar contacts.')
            .css('color', '#d63638');
        }
      })
      .fail(function () {
        finished = true;
        $('#contacts-sync-status')
          .text('Error de connexió amb el servidor.')
          .css('color', '#d63638');
      })
      .always(function () {
        if (finished) {
          $('#btn-sync-contacts, #btn-sync-events').prop('disabled', false);
        }
      });
    }

    // Arrenquem el primer batch
    runContactsBatch();
  });




  $('#btn-sync-events').on('click', function (e) {
    e.preventDefault();

    if (!confirm('Segur que vols sincronitzar tots els Esdeveniments des de Sinergia?')) {
      return;
    }

    $('#events-sync-status').text('Sincronitzant esdeveniments...');
    $('#btn-sync-events').prop('disabled', true);

    $.post(fcsdSinergiaAjax.ajax_url, {
      action: 'fcsd_sinergia_sync_events',
      nonce: fcsdSinergiaAjax.nonce
    })
    .done(function (res) {
      if (res.success) {
        $('#events-sync-status')
          .text('Sincronització completada. Guardats: ' + res.data.saved + ' de ' + res.data.total)
          .css('color', '#46b450');

        if (res.data.last_sync_human) {
          $('#events-last-sync').text(res.data.last_sync_human);
        }

        loadEventsPage(1, $('#event-search').val() || '');
      } else {
        $('#events-sync-status')
          .text(res.data.message || 'Error en sincronitzar esdeveniments.')
          .css('color', '#d63638');
      }
    })
    .fail(function () {
      $('#events-sync-status')
        .text('Error de connexió amb el servidor.')
        .css('color', '#d63638');
    })
    .always(function () {
      $('#btn-sync-events').prop('disabled', false);
    });
  });

  /* -------------------------------------------------------------------------
     CLICK EN TABS
  ------------------------------------------------------------------------- */
  $(document).on('click', '.nav-tab', function (e) {
    e.preventDefault();
    openTab($(this).data('tab'));
  });

  /* -------------------------------------------------------------------------
     ARRANQUE: decidir tab inicial
  ------------------------------------------------------------------------- */
  pingSinergia().then(function () {
    const initialTab =
      $('.nav-tab.nav-tab-active').data('tab') ||
      new URLSearchParams(window.location.search).get('tab') ||
      'login';

    openTab(initialTab);
  });

  /* -------------------------------------------------------------------------
     DESPLEGABLE DE INSCRIPCIONES — *** NUEVO ***
  ------------------------------------------------------------------------- */
  const regsLoaded = {};

  $(document).on('click', '#contacts-table tbody tr.contact-row', function (e) {
    if ($(e.target).is('a,button,input,select,textarea,label')) {
      return;
    }

    const $row    = $(this);
    const contact = $row.data('contact-id');
    if (!contact) return;

    const $sub    = $('tr.registrations-row[data-contact-id="'+contact+'"]');
    const opened  = $sub.is(':visible');

    if (opened) {
      $sub.hide();
      return;
    }

    $sub.show();

    if (regsLoaded[contact]) return;

    $sub.find('.registrations-container').html('<em>Carregant inscripcions...</em>');

    $.post(fcsdSinergiaAjax.ajax_url, {
      action: 'fcsd_sinergia_get_contact_registrations_admin',
      nonce: fcsdSinergiaAjax.nonce,
      contact_id: contact,
      force: 1  
    })
    .done(function(res){
      if (res.success) {
        $sub.find('.registrations-container').html(res.data.html);
        regsLoaded[contact] = true;
      } else {
        $sub.find('.registrations-container')
          .html('<span style="color:#a00;">'+(res.data.message||'Error')+'</span>');
      }
    })
    .fail(function(xhr){
      $sub.find('.registrations-container')
        .html('<span style="color:#a00;">Error AJAX ('+xhr.status+')</span>');
    });

  });

  // Crear/actualitzar CPT event des de la taula d'esdeveniments de Sinergia
  // Publicar / despublicar CPT event des de la taula d'esdeveniments de Sinergia
  $(document).on('click', '.fcsd-sync-add-event', function (e) {
    e.preventDefault();

    var $btn   = $(this);
    var sinId  = $btn.data('sinergia-event-id');
    var postId = parseInt($btn.data('event-post-id'), 10) || 0;

    if (!sinId) {
      return;
    }

    $btn.prop('disabled', true);

    var ajaxData = {
      nonce: fcsdSinergiaAjax.nonce
    };

    // Si hi ha postId, vol dir que ja hi ha un CPT creat → DESPUBLICAR
    if (postId) {
      ajaxData.action        = 'fcsd_sinergia_delete_event_post';
      ajaxData.event_post_id = postId;
    } else {
      // Si no hi ha CPT → PUBLICAR (crear/actualitzar)
      ajaxData.action            = 'fcsd_sinergia_upsert_event_post';
      ajaxData.sinergia_event_id = sinId;
    }

    $.post(fcsdSinergiaAjax.ajax_url, ajaxData)
      .done(function (res) {
        if (res.success) {
          // Tornem a carregar la pàgina actual d'esdeveniments
          var $table = $('#events-table');
          var page   = $table.data('page') || 1;
          loadEventsPage(page, $('#event-search').val() || '');
        } else {
          alert((res.data && res.data.message) || 'Error publicant/despublicant l\'esdeveniment.');
        }
      })
      .fail(function () {
        $('#sinergia-login-status')
          .text('Error de connexió.')
          .css('color', '#d63638');
      })
      .always(function () {
        $btn.prop('disabled', false);
      });
  });

  syncSortIndicators('#contacts-table', contactsSortKey, contactsSortDir);
  syncSortIndicators('#events-table',   eventsSortKey,   eventsSortDir);

});
