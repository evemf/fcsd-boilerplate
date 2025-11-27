(function ($) {
    'use strict';

    function initViewer($viewer) {
        var frames = $viewer.data('frames');
        if (!frames || !frames.length) return;

        var $img = $viewer.find('.fcsd-360-frame');
        var index = 0;
        var isDown = false;
        var startX = 0;

        function setFrame(i) {
            index = (i + frames.length) % frames.length;
            $img.attr('src', frames[index]);
        }

        $viewer.on('mousedown touchstart', function (e) {
            isDown = true;
            startX = e.pageX || (e.originalEvent.touches && e.originalEvent.touches[0].pageX) || 0;
        });

        $(document).on('mouseup touchend', function () {
            isDown = false;
        });

        $viewer.on('mousemove touchmove', function (e) {
            if (!isDown) return;

            var x = e.pageX || (e.originalEvent.touches && e.originalEvent.touches[0].pageX) || 0;
            var delta = x - startX;

            if (Math.abs(delta) > 10) {
                var step = delta > 0 ? -1 : 1;
                setFrame(index + step);
                startX = x;
            }
        });
    }

    $(function () {
        $('.fcsd-360-viewer').each(function () {
            initViewer($(this));
        });

        // Thumbnails que cambian la imagen principal (también 360)
        $(document).on('click', '.product-gallery-item', function () {
            var src = $(this).data('src');
            var target = $(this).data('main-target');
            if (src && target) {
                $(target).attr('src', src);
            }
        });

        // Botones de copiar enlace (barra de compartir)
        $(document).on('click', '.js-copy-link', function () {
            var link = $(this).data('link');
            if (!link || !navigator.clipboard) return;
            navigator.clipboard.writeText(link).then(function () {
                $('.js-copy-ok').removeClass('d-none');
                setTimeout(function () {
                    $('.js-copy-ok').addClass('d-none');
                }, 2000);
            });
        });
    });
})(jQuery);
