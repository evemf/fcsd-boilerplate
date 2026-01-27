<?php
/**
 * Template Name: Cistella
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

// Mostrar mensaje de éxito si se añadió un producto
if ( isset( $_GET['added_to_cart'] ) ) {
    $product_id = absint( $_GET['added_to_cart'] );
    $product_title = get_the_title( $product_id );
    ?>
    <div class="container mt-4">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php printf( 
                esc_html__( "\"%s\" s'ha afegit a la cistella correctament.", 'fcsd' ),
                esc_html( $product_title ) 
            ); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php esc_attr_e( 'Tancar', 'fcsd' ); ?>"></button>
        </div>
    </div>
    <?php
}

$cart = fcsd_get_cart_summary();
?>

<div class="container cart-page">
    <h1><?php esc_html_e( 'Cistella', 'fcsd' ); ?></h1>

    <?php get_template_part( 'template-parts/shop/cart', 'table', [ 'cart' => $cart ] ); ?>

    <?php if ( ! empty( $cart['items'] ) ) : ?>
        <p class="cart-total">
            <?php esc_html_e( 'Total:', 'fcsd' ); ?>
            <?php echo esc_html( number_format_i18n( $cart['total'], 2 ) ); ?> €
        </p>

        <?php if ( is_user_logged_in() ) : ?>
            <p class="cart-discount-info">
                <?php esc_html_e( "S'han aplicat els descomptes d'usuari registrat (si escau).", 'fcsd' ); ?>
            </p>
        <?php else : ?>
            <p class="cart-discount-info">
                <?php esc_html_e( "Registra't o inicia sessió per obtenir descomptes exclusius.", 'fcsd' ); ?>
            </p>
        <?php endif; ?>

        <?php $checkout_id = function_exists('fcsd_get_page_id_by_key') ? fcsd_get_page_id_by_key('checkout') : 0; ?>
        <a href="<?php echo esc_url( $checkout_id ? get_permalink( $checkout_id ) : home_url('/') ); ?>" class="btn btn-primary">
            <?php esc_html_e( 'Anar a pagar', 'fcsd' ); ?>
        </a>
    <?php endif; ?>
</div>

<?php
get_footer();
