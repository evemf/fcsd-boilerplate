jQuery(function ($) {
    function buildEventsModal(date, events) {
    var listHtml = '';

    if (!events || !events.length) {
        listHtml = '<p>No hay actos para este día.</p>';
    } else {
        // Ordenar por timestamp (el PHP ya viene ordenado, pero por si acaso)
        events.sort(function (a, b) {
            if (a.start === b.start) { return 0; }
            return a.start < b.start ? -1 : 1;
        });

        listHtml += '<ul class="fcsd-actes-modal__events">';

        events.forEach(function (e) {
            var timeLabel = '';
            if (e.start) {
                var d  = new Date(e.start * 1000);
                var hh = ('0' + d.getHours()).slice(-2);
                var mm = ('0' + d.getMinutes()).slice(-2);
                timeLabel = hh + ':' + mm + ' – ';
            }

            listHtml += '<li>';
            listHtml += '<span class="fcsd-actes-modal__event-time">' + timeLabel + '</span>';
            listHtml += '<a href="' + e.edit_link + '">';
            listHtml += e.title;
            listHtml += '</a>';
            listHtml += '</li>';
        });

        listHtml += '</ul>';
    }

    var $modal = $(
        '<div class="fcsd-actes-modal-overlay">' +
            '<div class="fcsd-actes-modal">' +
                '<button type="button" class="fcsd-actes-modal__close">&times;</button>' +
                '<h2>' + date + '</h2>' +
                listHtml +
                '<p class="fcsd-actes-modal__actions">' +
                    '<button type="button" class="button button-primary fcsd-actes-modal__new" data-date="' + date + '">Nuevo acto</button>' +
                '</p>' +
            '</div>' +
        '</div>'
    );

    $('body').append($modal);
}

    function buildModal(date) {
        var $modal = $(
            '<div class="fcsd-actes-modal-overlay">' +
                '<div class="fcsd-actes-modal">' +
                    '<button type="button" class="fcsd-actes-modal__close">&times;</button>' +
                    '<h2>' + date + '</h2>' +
                    '<form id="fcsd-actes-modal-form">' +
                        '<input type="hidden" name="date" value="' + date + '">' +
                        '<p>' +
                            '<label>' +
                                'Título<br>' +
                                '<input type="text" name="title" class="regular-text" required>' +
                            '</label>' +
                        '</p>' +
                        '<p>' +
                            '<label>' +
                                'Descripción<br>' +
                                '<textarea name="content" rows="4" class="large-text"></textarea>' +
                            '</label>' +
                        '</p>' +
                        '<p>' +
                            '<label>' +
                                'Inicio<br>' +
                                '<input type="datetime-local" name="start">' +
                            '</label>' +
                        '</p>' +
                        '<p>' +
                            '<label>' +
                                'Fin<br>' +
                                '<input type="datetime-local" name="end">' +
                            '</label>' +
                        '</p>' +
                        '<p>' +
                            '<label>' +
                                '<input type="checkbox" name="needs_ticket" value="1"> ' +
                                'Requiere entrada previa' +
                            '</label>' +
                        '</p>' +
                        '<p>' +
                            '<button type="submit" class="button button-primary">Guardar acto</button>' +
                            '<button type="button" class="button fcsd-actes-modal__cancel">Cancelar</button>' +
                        '</p>' +
                    '</form>' +
                '</div>' +
            '</div>'
        );

        $('body').append($modal);
    }

    function openModal(date) {
        buildModal(date);
    }

    function closeModal() {
        $('.fcsd-actes-modal-overlay').remove();
    }

        // Click simple en día → mostrar lista de actos
    $(document).on('click', '.fcsd-actes-calendar__day[data-date]', function (e) {
        // Si el click viene del botón "＋", dejamos seguir su enlace (crear acto rápido).
        if ($(e.target).closest('.fcsd-actes-calendar__add').length) {
            return;
        }

        e.preventDefault();

        var $cell   = $(this);
        var date    = $cell.data('date');
        var payload = $cell.data('events') || [];

        if (typeof payload === 'string') {
            try { payload = JSON.parse(payload); } catch (err) { payload = []; }
        }

        buildEventsModal(date, payload);
    });

    // Desde el modal de lista → abrir modal de creación
    $(document).on('click', '.fcsd-actes-modal__new', function (e) {
        e.preventDefault();
        var date = $(this).data('date');
        closeModal();
        openModal(date);
    });


    // Doble click en día → abrir modal
    $(document).on('dblclick', '.fcsd-actes-calendar__day[data-date]', function (e) {
        e.preventDefault();
        var date = $(this).data('date');
        if (!date) {
            return;
        }
        openModal(date);
    });

    // Cerrar modal
    $(document).on('click', '.fcsd-actes-modal__close, .fcsd-actes-modal__cancel', function (e) {
        e.preventDefault();
        closeModal();
    });

    // Envío del formulario
    $(document).on('submit', '#fcsd-actes-modal-form', function (e) {
        e.preventDefault();

        var $form = $(this);

        var data = $form.serializeArray();
        data.push({ name: 'action', value: 'fcsd_actes_quick_create' });
        data.push({ name: 'nonce', value: fcsdActesAdmin.nonce });

        $.post(fcsdActesAdmin.ajaxUrl, data, function (response) {
            if (!response || !response.success) {
                alert(response && response.data && response.data.message
                    ? response.data.message
                    : 'Error al crear el acto');
                return;
            }

            // Recargar la página para ver el acto en el calendario
            window.location.reload();
        });
    });
});
