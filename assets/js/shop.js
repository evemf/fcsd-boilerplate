/**
 * Shop JavaScript - Sistema de carrito con AJAX
 * Versión: 1.0.0
 */

jQuery(document).ready(function($) {
    
    /**
     * AJAX: Añadir producto al carrito desde archive/listado
     */
    $(document).on('click', '.js-add-to-cart-btn', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $form   = $button.closest('.add-to-cart-ajax');
        
        // Verificar que es un formulario AJAX
        if (!$form.length || !$form.hasClass('add-to-cart-ajax')) {
            return;
        }
        
        // Evitar múltiples clics
        if ($button.hasClass('is-loading') || $button.hasClass('is-added')) {
            return;
        }
        
        // Obtener datos del formulario
        const productId = $form.data('product-id');
        const color     = $form.find('.js-selected-color-field').val() || '';
        const size      = $form.find('.js-selected-size-field').val() || '';
        const quantity  = $form.find('input[name="quantity"]').val() || 1;
        const nonce     = $form.find('input[name="fcsd_add_to_cart_nonce"]').val();
        
        // Guardar texto original
        const originalText = $button.text();
        
        // Estado: cargando
        $button.addClass('is-loading')
               .prop('disabled', true)
               .text(fcsd_shop.adding_text);
        
        // Petición AJAX
        $.ajax({
            url:  fcsd_shop.ajax_url,
            type: 'POST',
            data: {
                action:     'fcsd_add_to_cart',
                nonce:      fcsd_shop.nonce,
                product_id: productId,
                fcsd_color: color,
                fcsd_size:  size,
                quantity:   quantity
            },
            success: function(response) {
                if (response.success) {
                    // Estado: añadido con éxito
                    $button.removeClass('is-loading')
                           .addClass('is-added')
                           .text(fcsd_shop.added_text);
                    
                    // Actualizar contador del carrito en el header
                    updateCartBadge(response.data.cart_count);
                    
                    // Volver al estado normal después de 2.5 segundos
                    setTimeout(function() {
                        $button.removeClass('is-added')
                               .prop('disabled', false)
                               .text(originalText);
                    }, 2500);
                    
                } else {
                    // Error
                    $button.removeClass('is-loading')
                           .addClass('is-error')
                           .text(fcsd_shop.error_text);
                    
                    alert(response.data.message || fcsd_shop.i18n.add_error_fallback);
                    
                    // Restaurar después de 2 segundos
                    setTimeout(function() {
                        $button.removeClass('is-error')
                               .prop('disabled', false)
                               .text(originalText);
                    }, 2000);
                }
            },
            error: function() {
                $button.removeClass('is-loading')
                       .addClass('is-error')
                       .text(fcsd_shop.error_text);
                
                alert(fcsd_shop.i18n.connection_error);
                
                // Restaurar después de 2 segundos
                setTimeout(function() {
                    $button.removeClass('is-error')
                           .prop('disabled', false)
                           .text(originalText);
                }, 2000);
            }
        });
    });
    
    /**
     * Actualizar cantidad de producto en el carrito
     */
    $(document).on('change', '.cart-quantity-input', function() {
        const $input   = $(this);
        const cartKey  = $input.data('cart-key');
        const quantity = parseInt($input.val()) || 0;
        
        updateCartItem(cartKey, quantity);
    });
    
    /**
     * Eliminar producto del carrito
     */
    $(document).on('click', '.remove-from-cart', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const cartKey = $button.data('cart-key');
        
        if (!confirm(fcsd_shop.i18n.confirm_remove)) {
            return;
        }
        
        removeFromCart(cartKey);
    });
    
    /**
     * Función para actualizar cantidad
     */
    function updateCartItem(cartKey, quantity) {
        $.ajax({
            url:  fcsd_shop.ajax_url,
            type: 'POST',
            data: {
                action:   'fcsd_update_cart_item',
                nonce:    fcsd_shop.nonce,
                cart_key: cartKey,
                quantity: quantity
            },
            beforeSend: function() {
                $('.cart-page').css('opacity', '0.5');
            },
            success: function(response) {
                if (response.success) {
                    // Recargar el contenido del carrito
                    reloadCartContent();
                    // Actualizar el contador del header
                    updateCartBadge(response.data.cart_count);
                } else {
                    alert(response.data.message || fcsd_shop.i18n.update_error);
                    location.reload();
                }
            },
            error: function() {
                alert(fcsd_shop.i18n.reload_error);
                location.reload();
            }
        });
    }
    
    /**
     * Función para eliminar producto
     */
    function removeFromCart(cartKey) {
        $.ajax({
            url:  fcsd_shop.ajax_url,
            type: 'POST',
            data: {
                action:   'fcsd_remove_from_cart',
                nonce:    fcsd_shop.nonce,
                cart_key: cartKey
            },
            beforeSend: function() {
                $('.cart-page').css('opacity', '0.5');
            },
            success: function(response) {
                if (response.success) {
                    // Recargar el contenido del carrito
                    reloadCartContent();
                    // Actualizar el contador del header
                    updateCartBadge(response.data.cart_count);
                    
                    // Mostrar mensaje de éxito
                    showCartMessage(fcsd_shop.i18n.removed_success, 'success');
                } else {
                    alert(response.data.message || fcsd_shop.i18n.remove_error);
                    location.reload();
                }
            },
            error: function() {
                alert(fcsd_shop.i18n.reload_error);
                location.reload();
            }
        });
    }
    
    /**
     * Recargar el contenido del carrito
     */
    function reloadCartContent() {
        $.ajax({
            url:  fcsd_shop.ajax_url,
            type: 'POST',
            data: {
                action: 'fcsd_get_cart_content',
                nonce:  fcsd_shop.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.cart-page').html(response.data.html);
                } else {
                    location.reload();
                }
            },
            error: function() {
                location.reload();
            },
            complete: function() {
                $('.cart-page').css('opacity', '1');
            }
        });
    }
    
    /**
     * Actualizar el badge del carrito en el header
     */
    function updateCartBadge(count) {
        const $cartLink = $('.bi-bag').closest('a');
        const $badge    = $cartLink.find('.badge');
        
        if (count > 0) {
            if ($badge.length) {
                $badge.text(count);
            } else {
                $cartLink.append(
                    '<span class="position-absolute translate-middle badge rounded-pill bg-accent" style="top:-.25rem; left:80%;">' + 
                    count + 
                    '</span>'
                );
            }
        } else if ($badge.length) {
            $badge.remove();
        }
    }
    
    /**
     * Mostrar mensaje temporal
     */
    function showCartMessage(message, type) {
        type = type || 'success';
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon       = type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill';
        
        // Eliminar alertas anteriores
        $('.cart-page > .alert').remove();
        
        const $alert = $(
            '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                '<i class="bi bi-' + icon + ' me-2"></i>' +
                message +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="' + fcsd_shop.i18n.close + '"></button>' +
            '</div>'
        );
        
        $('.cart-page').prepend($alert);
        
        // Auto-cerrar después de 3 segundos
        setTimeout(function() {
            $alert.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    /**
     * Prevenir múltiples envíos del formulario de añadir al carrito (formularios tradicionales)
     */
    $('form.add-to-cart-form:not(.add-to-cart-ajax)').on('submit', function() {
        const $form   = $(this);
        const $button = $form.find('button[type="submit"]');
        
        // Deshabilitar botón temporalmente
        $button.prop('disabled', true);
        
        // Re-habilitar después de 2 segundos (por si hay error)
        setTimeout(function() {
            $button.prop('disabled', false);
        }, 2000);
    });

    /**
     * Selección de color/talla en tarjetas de producto (archive / tienda)
     * Sincroniza los radios visibles con los inputs hidden del formulario.
     */
    
    // Colores
    $('.product-card').on('change', '.js-product-color-input', function () {
        const $radio = $(this);
        const $card  = $radio.closest('.product-card');
        const value  = $radio.val();
        
        // Actualizar campo hidden que se envía en el form
        const $hiddenField = $card.find('.js-selected-color-field');
        if ($hiddenField.length) {
            $hiddenField.val(value);
        }

        // Estado visual activo en el swatch
        $card.find('.fcsd-color-swatch').removeClass('is-active');
        $radio.closest('.fcsd-color-swatch').addClass('is-active');
    });

    // Tallas
    $('.product-card').on('change', '.js-product-size-input', function () {
        const $radio = $(this);
        const $card  = $radio.closest('.product-card');
        const value  = $radio.val();
        
        // Actualizar campo hidden
        const $hiddenField = $card.find('.js-selected-size-field');
        if ($hiddenField.length) {
            $hiddenField.val(value);
        }

        // Estado visual activo en la talla
        $card.find('.fcsd-size-pill').removeClass('is-active');
        $radio.closest('.fcsd-size-pill').addClass('is-active');
    });
    
});