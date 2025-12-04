(function(){
    function initLightbox(){
        var wrapper   = document.querySelector('.fcsd-org-page');
        if(!wrapper) return;

        var openBtn   = wrapper.querySelector('.fcsd-org-open');
        var lightbox  = wrapper.querySelector('.fcsd-org-lightbox');
        if(!openBtn || !lightbox) return;

        var closeBtn  = lightbox.querySelector('.fcsd-org-close');
        var zoomInBtn = lightbox.querySelector('.fcsd-org-zoom-in');
        var zoomOutBtn= lightbox.querySelector('.fcsd-org-zoom-out');
        var zoomReset = lightbox.querySelector('.fcsd-org-zoom-reset');
        var img       = lightbox.querySelector('.fcsd-org-image-full');
        var canvas    = lightbox.querySelector('.fcsd-org-lightbox-canvas');

        var scale = 1;
        var posX = 0, posY = 0;
        var dragging = false;
        var startX, startY;

        function applyTransform(){
            img.style.transform = 'translate('+posX+'px,'+posY+'px) scale('+scale+')';
        }

        openBtn.addEventListener('click', function(){
            scale = 1; posX = 0; posY = 0; applyTransform();
            lightbox.classList.add('is-open');
            lightbox.setAttribute('aria-hidden','false');
        });

        closeBtn.addEventListener('click', function(){
            lightbox.classList.remove('is-open');
            lightbox.setAttribute('aria-hidden','true');
        });

        zoomInBtn.addEventListener('click', function(){
            scale = Math.min(scale + 0.2, 4);
            applyTransform();
        });

        zoomOutBtn.addEventListener('click', function(){
            scale = Math.max(scale - 0.2, 0.4);
            applyTransform();
        });

        zoomReset.addEventListener('click', function(){
            scale = 1; posX = 0; posY = 0;
            applyTransform();
        });

        canvas.addEventListener('mousedown', function(e){
            dragging = true;
            canvas.style.cursor = 'grabbing';
            startX = e.clientX - posX;
            startY = e.clientY - posY;
        });

        window.addEventListener('mouseup', function(){
            dragging = false;
            canvas.style.cursor = 'grab';
        });

        window.addEventListener('mousemove', function(e){
            if(!dragging) return;
            posX = e.clientX - startX;
            posY = e.clientY - startY;
            applyTransform();
        });

        canvas.addEventListener('wheel', function(e){
            e.preventDefault();
            var delta = e.deltaY < 0 ? 0.1 : -0.1;
            var newScale = Math.min(4, Math.max(0.4, scale + delta));
            scale = newScale;
            applyTransform();
        }, { passive:false });
    }

    document.addEventListener('DOMContentLoaded', initLightbox);
})();
