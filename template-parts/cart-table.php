<?php
// template-parts/cart-table.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** @var array $args */
$cart = isset( $args['cart'] ) ? $args['cart'] : fcsd_get_cart_summary();
?>

<?php if ( empty( $cart['items'] ) ) : ?>
    <p><?php esc_html_e( 'Tu carrito está vacío.', 'fcsd' ); ?></p>
<?php else : ?>
    <table class="cart-table table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Producto', 'fcsd' ); ?></th>
                <th><?php esc_html_e( 'Cantidad', 'fcsd' ); ?></th>
                <th><?php esc_html_e( 'Precio', 'fcsd' ); ?></th>
                <th><?php esc_html_e( 'Subtotal', 'fcsd' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $cart['items'] as $item ) : ?>
                <tr>
                    <td>
                        <a href="<?php echo esc_url( get_permalink( $item['product_id'] ) ); ?>">
                            <?php echo esc_html( get_the_title( $item['product_id'] ) ); ?>
                        </a>
                    </td>
                    <td><?php echo esc_html( $item['quantity'] ); ?></td>
                    <td><?php echo esc_html( number_format_i18n( $item['price'], 2 ) ); ?> €</td>
                    <td><?php echo esc_html( number_format_i18n( $item['subtotal'], 2 ) ); ?> €</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
