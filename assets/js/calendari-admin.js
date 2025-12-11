jQuery(function ($) {

    /**
     * Construye el modal de lista de actos de un día concreto
     * (CRUD básico: ver, editar, eliminar + acceso a "Nuevo acto").
     */
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

            listHtml += '<table class="fcsd-actes-modal__table">';
            listHtml +=   '<thead><tr>';
            listHtml +=     '<th>Hora</th>';
            listHtml +=     '<th>Acte</th>';
            listHtml +=     '<th class="fcsd-actes-modal__table-actions">Accions</th>';
            listHtml +=   '</tr></thead>';
            listHtml +=   '<tbody>';

            events.forEach(function (e) {
                var timeLabel = '';
                if (e.start) {
                    var d  = new Date(e.start * 1000);
                    var hh = ('0' + d.getHours()).slice(-2);
                    var mm = ('0' + d.getMinutes()).slice(-2);
                    timeLabel = hh + ':' + mm;
                }

                listHtml += '<tr data-id="' + e.id + '">';
                listHtml +=   '<td class="fcsd-actes-modal__event-time">' + (timeLabel || '&ndash;') + '</td>';
                listHtml +=   '<td class="fcsd-actes-modal__event-title">';
                listHtml +=       '<a href="' + e.edit_link + '" target="_blank" rel="noopener noreferrer">' + e.title + '</a>';
                listHtml +=   '</td>';
                listHtml +=   '<td class="fcsd-actes-modal__table-actions">';
                listHtml +=       '<a href="' + e.edit_link + '" class="button-link" target="_blank" rel="noopener noreferrer">Editar</a>';
                listHtml +=       ' &middot; ';
                listHtml +=       '<button type="button" class="button-link fcsd-actes-modal__delete" data-id="' + e.id + '">Eliminar</button>';
                listHtml +=   '</td>';
                listHtml += '</tr>';
            });

            listHtml +=   '</tbody>';
            listHtml += '</table>';
        }

        var $modal = $(
            '<div class="fcsd-actes-modal-overlay">' +
                '<div class="fcsd-actes-modal">' +
                    '<button type="button" class="fcsd-actes-modal__close">&times;</button>' +
                    '<h2>' + date + '</h2>' +
                    listHtml +
                    '<p class="fcsd-actes-modal__actions">' +
                        '<button type="button" class="button button-primary fcsd-actes-modal__new" data-date="' + date + '">Nou acte</button>' +
                    '</p>' +
                '</div>' +
            '</div>'
        );

        $('body').append($modal);
    }

    /**
     * Construye el modal de creación rápida de acte para una fecha concreta.
     */
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

    function closeModal() {
        $('.fcsd-actes-modal-overlay').remove();
    }

    function openModal(date) {
        buildModal(date);
    }

    /*************************************************
     * Eventos
     *************************************************/

    // Click en día: abrir listado de actos (modal CRUD)
    $(document).on('click', '.fcsd-actes-calendar__day[data-date]', function (e) {
        // Si el click viene del botón "+", dejamos seguir su enlace (crear acto rápido por defecto WP)
        if ($(e.target).closest('.fcsd-actes-calendar__add').length) {
            return;
        }

        e.preventDefault();

        var $cell   = $(this);
        var date    = $cell.data('date');
        var payload = $cell.data('events') || [];

        if (typeof payload === 'string') {
            try {
                payload = JSON.parse(payload);
            } catch (err) {
                payload = [];
            }
        }

        closeModal();
        buildEventsModal(date, payload);
    });

    // Desde el modal de lista → abrir modal de creación
    $(document).on('click', '.fcsd-actes-modal__new', function (e) {
        e.preventDefault();
        var date = $(this).data('date');
        closeModal();
        openModal(date);
    });

    // Doble click en día → abrir modal de creación directa
    $(document).on('dblclick', '.fcsd-actes-calendar__day[data-date]', function (e) {
        e.preventDefault();
        var date = $(this).data('date');
        if (!date) {
            return;
        }
        closeModal();
        openModal(date);
    });

    // Cerrar modal (botón X o Cancelar)
    $(document).on('click', '.fcsd-actes-modal__close, .fcsd-actes-modal__cancel', function (e) {
        e.preventDefault();
        closeModal();
    });

    // Eliminar acto desde la tabla del modal
    $(document).on('click', '.fcsd-actes-modal__delete', function (e) {
        e.preventDefault();

        var $btn = $(this);
        var id   = $btn.data('id');

        if (!id) {
            return;
        }

        if (!window.confirm('¿Seguro que quieres eliminar este acto?')) {
            return;
        }

        $btn.prop('disabled', true);

        $.post(fcsdActesAdmin.ajaxUrl, {
            action: 'fcsd_actes_quick_delete',
            nonce:  fcsdActesAdmin.nonce,
            id:     id
        }, function (response) {
            $btn.prop('disabled', false);

            if (!response || !response.success) {
                alert(response && response.data && response.data.message
                    ? response.data.message
                    : 'Error al eliminar el acto');
                return;
            }

            // Recargar la página para ver el calendario actualizado
            window.location.reload();
        });
    });

    // Envío del formulario de creación rápida
    $(document).on('submit', '#fcsd-actes-modal-form', function (e) {
        e.preventDefault();

        var $form = $(this);

        var data = {
            action: 'fcsd_actes_quick_create',
            nonce:  fcsdActesAdmin.nonce
        };

        $.each($form.serializeArray(), function (_, field) {
            data[field.name] = field.value;
        });

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
