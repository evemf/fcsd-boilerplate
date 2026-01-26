<?php
/**
 * Template Name: Checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$cart = fcsd_get_cart_summary();
?>

<div class="container checkout-page">
    <h1><?php esc_html_e( 'Finalizar compra', 'fcsd' ); ?></h1>

    <?php if ( empty( $cart['items'] ) ) : ?>
        <p><?php esc_html_e( 'La cistella és buida.', 'fcsd' ); ?></p>
    <?php else : ?>
        <?php get_template_part( 'template-parts/shop/cart', 'table', [ 'cart' => $cart ] ); ?>

        <p class="checkout-total">
            <?php esc_html_e( 'Total a pagar:', 'fcsd' ); ?>
            <?php echo esc_html( number_format_i18n( $cart['total'], 2 ) ); ?> €
        </p>

        <form method="post" class="checkout-form">
            <?php wp_nonce_field( 'fcsd_process_checkout', 'fcsd_checkout_nonce' ); ?>

            <div class="form-group">
                <label for="checkout_email"><?php esc_html_e( 'Email', 'fcsd' ); ?></label>
                <input type="email" name="email" id="checkout_email" required class="form-control">
            </div>

            <!-- Aquí podrías añadir dirección, métodos de envío, etc. -->

            <button type="submit" class="btn btn-success">
                <?php esc_html_e( 'Realizar pedido', 'fcsd' ); ?>
            </button>
        </form>
    <?php endif; ?>
</div>

<?php
get_footer();
