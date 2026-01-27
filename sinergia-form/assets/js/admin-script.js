jQuery(document).ready(function ($) {
    let allEventsData = [];

    //Pestanyes
    $('.nav-tab').on('click', function (e) {
        e.preventDefault();
        if ($(this).hasClass('nav-tab-disabled')) return;

        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        const tab = $(this).data('tab');
        $('.tab-pane').hide();
        $('#tab-' + tab).show();

        if (tab === 'events' && ajax_object.is_logged_in === 'yes') {
            loadEventsData();
        }
    });

    // Carregar pestanya activa per defecte segons url
    const params = new URLSearchParams(window.location.search);
    const tabFromURL = params.get('tab');
    if (tabFromURL) {
        $('.nav-tab[data-tab="' + tabFromURL + '"]').click();
    }

    // Carrega esdeveniments
    function loadEventsData() {
        $.ajax({
            url: ajax_object.ajax_url,
            method: 'POST',
            data: {
                action: 'sinergiacrm_get_esdeveniments_ajax',
                nonce: ajax_object.nonce
            },
            beforeSend: function () {
                $('#events-content').html('<p><em>Carregant esdeveniments...</em></p>');
            },
            success: function (response) {
                if (response.success) {
                    $('#events-content').html(response.data.html);
                    allEventsData = response.data.events_data;
                    initCheckboxHandlers();
            
                    // 🔍 Iniciar buscador
                    const searchInput = document.getElementById('event-search');
                    const table = document.getElementById('events-table');
                    if (searchInput && table) {
                        searchInput.addEventListener('keyup', function () {
                            const filter = this.value.toLowerCase();
                            const rows = table.querySelectorAll('tbody tr');
                            rows.forEach(row => {
                                const nameCell = row.cells[1];
                                const name = nameCell.textContent.toLowerCase();
                                row.style.display = name.includes(filter) ? '' : 'none';
                            });
                        });
                    }
            
                } else {
                    $('#events-content').html('<p style="color:red;">' + (response.data?.message || 'Error desconegut') + '</p>');
                }
            },
            error: function (xhr, status, error) {
                $('#events-content').html('<p style="color:red;">Error de connexió amb el servidor. Detalls: ' + error + '</p>');
            }
        });
    }

    //Inicia checkboxes post-petició
    function initCheckboxHandlers() {
        if ($('#event-actions').length === 0) {
            $('#events-table').before('<div id="event-actions" style="margin-bottom: 15px;"></div>');
        }
        if ($('#form-generator-container').length === 0) {
            $('#events-content').append('<div id="form-generator-container" style="display:none; margin-top:20px; padding:15px; background:#f8f8f8; border:1px solid #ddd;"><h3>Codi HTML del formulari</h3><div id="form-code-display"></div><button id="copy-form-code" class="button">Copiar codi</button><div id="copy-message" style="display:none; color:green; margin-top:5px;">Codi copiat!</div></div>');
        }

        //seleccionar/deseleccionar tots
        $('#select-all-events').on('change', function() {
            $('.event-checkbox').prop('checked', $(this).prop('checked'));
            $('#events-table tbody tr').toggleClass('selected-row', $(this).prop('checked'));
            updateFormButton();
        });

        //checkboxes individuals
        $(document).on('change', '.event-checkbox', function() {
            $(this).closest('tr').toggleClass('selected-row', $(this).prop('checked'));
            $('#select-all-events').prop('checked', $('.event-checkbox:checked').length === $('.event-checkbox').length);
            updateFormButton();
        });

        if ($('#event-selection-style').length === 0) {
            $('<style id="event-selection-style">\
            .selected-row { background-color: #e0f7fa !important; }\
            #form-code-display { background: #fff; padding: 10px; border: 1px solid #ccc; margin-bottom: 10px; max-height: 400px; overflow: auto; font-family: monospace; white-space: pre-wrap; }\
            </style>').appendTo('head');
        }

        updateFormButton();

        //botó de copiar
        $('#copy-form-code').on('click', function() {
            const text = $('#form-code-display').text();
            navigator.clipboard.writeText(text).then(() => {
                $('#copy-message').fadeIn().delay(2000).fadeOut();
            });
        });
    }

    //botó generar formulari
    function updateFormButton() {
        const selectedCount = $('.event-checkbox:checked').length;

        if (selectedCount > 0) {
            if ($('#generate-form-button').length === 0) {
                $('#event-actions').html('<button id="generate-form-button" class="button button-primary">Generar formulari</button>');
                $('#generate-form-button').on('click', generateForm);
            }

            $('#generate-form-button').text(
                selectedCount > 1
                    ? `Generar formulari (${selectedCount} esdeveniments)`
                    : 'Generar formulari'
            );
        } else {
            $('#event-actions').empty();
            $('#form-generator-container').hide();
        }
    }

    //Generar formulari
    function generateForm() {
        const selectedIds = $('.event-checkbox:checked').map(function () {
            return $(this).val();
        }).get();
    
        const selectedEvents = allEventsData.filter(event => selectedIds.includes(event.id));
    
        if (selectedEvents.length === 0) {
            alert('Si us plau, selecciona almenys un esdeveniment.');
            return;
        }

        else if (selectedEvents.length > 1) {
            let orderedEvents = [];

            selectedEvents.forEach(event => {
                const label = prompt(`Etiqueta per a "${event.name}"`, event.name) || event.name;
                const position = parseInt(prompt(`Posició (1-${selectedEvents.length}) per a "${label}"`), 10);
                
                orderedEvents.push({ ...event, label, position: isNaN(position) ? 9999 : position });
            });

            orderedEvents.sort((a, b) => a.position - b.position);

            let scheduleOptionsHtml = '';
            orderedEvents.forEach(event => {
                scheduleOptionsHtml += `<option value="${event.id}" data-assigned-user-id="${event.assigned_user_id}">${event.label}</option>`;
            });
            $('#form-generator-container').show();
            $('#form-code-display').html('<p><em>Generant formulari...</em></p>');
        
            $.ajax({
                url: ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'sinergiacrm_get_form_template',
                    nonce: ajax_object.nonce,
                    events: JSON.stringify(selectedEvents),
                    schedule_options: scheduleOptionsHtml
                },
                success: function (response) {
                    if (response.success) {
                        setTimeout(() => {
                            const offsetTop = $('#form-code-display').offset().top;
                            $('html, body').animate({ scrollTop: offsetTop }, 800);
                        }, 100);
                        $('#form-code-display').html(response.data.html);
                    } else {
                        $('#form-code-display').html('<p style="color:red;">Error: ' +
                            (response.data?.message || 'No s\'ha pogut generar el formulari') + '</p>');
                    }
                },
                error: function (xhr, status, error) {
                    $('#form-code-display').html('<p style="color:red;">Error de connexió amb el servidor</p>');
                }
            });
        } else {
            const selectedEvent = selectedEvents[0];
            
            $('#form-generator-container').show();
            $('#form-code-display').html('<p><em>Generant formulari...</em></p>');
        
            $.ajax({
                url: ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'sinergiacrm_get_single_form_template',
                    nonce: ajax_object.nonce,
                    events: JSON.stringify(selectedEvents),
                    event_id: selectedEvent.id,
                    assigned_user_id: selectedEvent.assigned_user_id,
                },
                success: function (response) {
                    if (response.success) {
                        setTimeout(() => {
                            const offsetTop = $('#form-code-display').offset().top;
                            $('html, body').animate({ scrollTop: offsetTop }, 800);
                        }, 100);
                        $('#form-code-display').html(response.data.html);            
                    } else {
                        $('#form-code-display').html('<p style="color:red;">Error: ' +
                            (response.data?.message || 'No s\'ha pogut generar el formulari') + '</p>');
                    }
                },
                error: function (xhr, status, error) {
                    $('#form-code-display').html('<p style="color:red;">Error de connexió amb el servidor</p>');
                }
            });
        }
    }
    
});