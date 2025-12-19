<?php
// inc/ecommerce/template-tags-shop.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Obtiene el resumen del carrito
 */
function fcsd_get_cart_summary() {
    return fcsd_Shop_Cart::get_cart_items_with_data();
}

/**
 * Obtiene el número de productos en el carrito
 */
function fcsd_get_cart_count() {
    return fcsd_Shop_Cart::get_cart_count();
}

/**
 * Devuelve la estructura de precios de un producto:
 * - regular
 * - sale
 * - member
 * - effective (el que se debe cobrar/mostrar según el usuario)
 */
function fcsd_get_product_prices( $product_id ) {
    $regular = (float) get_post_meta( $product_id, '_fcsd_price_regular', true );
    $sale    = (float) get_post_meta( $product_id, '_fcsd_price_sale', true );
    $member  = (float) get_post_meta( $product_id, '_fcsd_price_member', true );

    $effective = $regular;

    // Si hay precio rebajado válido y menor al base
    if ( $sale > 0 && $sale < $regular ) {
        $effective = $sale;
    }

    // Si el usuario está logueado y hay precio member válido
    if ( is_user_logged_in() && $member > 0 ) {
        $effective = $member;
    }

    return [
        'regular'   => $regular,
        'sale'      => $sale,
        'member'    => $member,
        'effective' => $effective,
    ];
}

/**
 * Shortcode [fcsd_cart_placeholder]:
 * se usa en la pàgina "Cistella" que crea setup-content.php.
 */
function fcsd_cart_placeholder_shortcode() {

    // Si aún no tienes carrito montado, puedes dejar el placeholder:
    // return '<p>' . esc_html__( 'Aquí anirà la funcionalitat de cistella personalizada.', 'fcsd' ) . '</p>';

    // O, si ya usas la pàgina "Cistella" com a pura shell,
    // simplemente redibuja el contenido real del carrito aquí
    // reutilizando tus helpers de ecommerce:

    if ( function_exists( 'fcsd_get_cart_summary' ) ) {
        $cart = fcsd_get_cart_summary();
    } else {
        $cart = array(
            'items' => array(),
            'total' => 0,
        );
    }

    ob_start();
    ?>
    <div class="fcsd-cart-page">
        <h1><?php esc_html_e( 'Cistella', 'fcsd' ); ?></h1>

        <?php if ( empty( $cart['items'] ) ) : ?>
            <p><?php esc_html_e( 'La teva cistella està buida.', 'fcsd' ); ?></p>
        <?php else : ?>

            <?php
            // Aquí puedes reutilitzar un template part si ja el tens.
            // Si tienes un template-parts/cart-table.php, por ejemplo:
            // get_template_part( 'template-parts/cart', 'table', [ 'cart' => $cart ] );
            ?>

            <p class="cart-total">
                <?php esc_html_e( 'Total:', 'fcsd' ); ?>
                <?php echo esc_html( number_format_i18n( $cart['total'], 2 ) ); ?> €
            </p>

            <?php $checkout_id = function_exists('fcsd_get_page_id_by_key') ? fcsd_get_page_id_by_key('checkout') : 0; ?>
            <a href="<?php echo esc_url( $checkout_id ? get_permalink( $checkout_id ) : home_url('/') ); ?>"
               class="btn btn-primary">
                <?php esc_html_e( 'Pagar ara', 'fcsd' ); ?>
            </a>

        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'fcsd_cart_placeholder', 'fcsd_cart_placeholder_shortcode' );

/**
 * Shortcode [fcsd_checkout]: pantalla de checkout.
 *
 * La idea és que aquí dentro pegues el "cuerpo" de tu actual page-checkout.php,
 * pero sin get_header() / get_footer().
 */
function fcsd_checkout_shortcode() {

    if ( function_exists( 'fcsd_get_cart_summary' ) ) {
        $cart = fcsd_get_cart_summary();
    } else {
        $cart = array(
            'items' => array(),
            'total' => 0,
        );
    }

    ob_start();
    ?>
    <div class="container checkout-page">
        <h1><?php esc_html_e( 'Finalizar compra', 'fcsd' ); ?></h1>

        <?php if ( empty( $cart['items'] ) ) : ?>

            <p><?php esc_html_e( 'La cistella és buida.', 'fcsd' ); ?></p>

        <?php else : ?>

            <?php
            // 👉 AQUÍ pega el markup que tengas en page-checkout.php
            // para el resumen de carrito + el formulario de datos,
            // PERO sin get_header()/get_footer().

            // Ejemplo orientativo:
            ?>
            <div class="checkout-cart-summary">
                <?php
                // Si tienes un template-part del carrito, úsalo:
                // get_template_part( 'template-parts/cart', 'table', [ 'cart' => $cart ] );
                ?>
            </div>

            <hr>

            <form method="post" class="checkout-form">
                <?php wp_nonce_field( 'fcsd_process_checkout', 'fcsd_checkout_nonce' ); ?>

                <div class="mb-3">
                    <label class="form-label" for="checkout_name">
                        <?php esc_html_e( 'Nom complet', 'fcsd' ); ?>
                    </label>
                    <input type="text" name="checkout_name" id="checkout_name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="checkout_email">
                        <?php esc_html_e( 'Email', 'fcsd' ); ?>
                    </label>
                    <input type="email" name="checkout_email" id="checkout_email" class="form-control" required>
                </div>

                <!-- Afegir aquí la resta de camps que ja tinguis al teu checkout -->

                <button type="submit" class="btn btn-primary">
                    <?php esc_html_e( 'Realitzar la comanda', 'fcsd' ); ?>
                </button>
            </form>

        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'fcsd_checkout', 'fcsd_checkout_shortcode' );