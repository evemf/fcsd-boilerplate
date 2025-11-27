<?php
/**
 * Template Name: Pedido completado
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
?>

<div class="container order-success-page">
    <h1><?php esc_html_e( 'Gracias por tu pedido', 'fcsd' ); ?></h1>

    <?php if ( $order_id ) : ?>
        <p>
            <?php
            printf(
                esc_html__( 'Tu número de pedido es #%d.', 'fcsd' ),
                $order_id
            );
            ?>
        </p>
    <?php endif; ?>

    <p>
        <a href="<?php echo esc_url( get_post_type_archive_link( 'product' ) ); ?>" class="btn btn-primary">
            <?php esc_html_e( 'Volver a la tienda', 'fcsd' ); ?>
        </a>
    </p>
</div>

<?php
get_footer();
