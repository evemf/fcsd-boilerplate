jQuery(function ($) {
    $(document).on('change', '.fcsd-toggle-worker', function () {
        var $cb     = $(this);
        var checked = $cb.is(':checked');

        $.post(fcsdUsers.ajaxurl, {
            action:    'fcsd_toggle_worker',
            nonce:     fcsdUsers.nonce,
            user_id:   $cb.data('user'),
            is_worker: checked ? 1 : 0
        }).done(function (resp) {
            if (!resp || !resp.success) {
                alert(resp && resp.data ? resp.data : 'No se ha podido guardar el cambio.');
                $cb.prop('checked', !checked);
            }
        }).fail(function () {
            alert('Error de comunicación con el servidor.');
            $cb.prop('checked', !checked);
        });
    });
});
