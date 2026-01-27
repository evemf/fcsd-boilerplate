<?php
/**
 * Template part: Tabla del carrito
 * 
 * @param array $args['cart'] - Datos del carrito
 */

if ( ! isset( $args['cart'] ) ) {
    return;
}

$cart = $args['cart'];
?>

<?php if ( empty( $cart['items'] ) ) : ?>
    
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        <?php esc_html_e( 'La teva cistella és buida.', 'fcsd' ); ?>
    </div>
    
    <a href="<?php echo esc_url( get_post_type_archive_link( 'fcsd_product' ) ); ?>" class="btn btn-primary">
        <?php esc_html_e( 'Anar a la botiga', 'fcsd' ); ?>
    </a>

<?php else : ?>

    <div class="table-responsive">
        <table class="table cart-table">
            <thead>
                <tr>
					<th><?php esc_html_e( 'Producte', 'fcsd' ); ?></th>
					<th><?php esc_html_e( 'Preu', 'fcsd' ); ?></th>
					<th><?php esc_html_e( 'Quantitat', 'fcsd' ); ?></th>
                    <th><?php esc_html_e( 'Subtotal', 'fcsd' ); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $cart['items'] as $item ) : ?>
                    <tr data-cart-key="<?php echo esc_attr( $item['cart_key'] ); ?>">
                        
                        <!-- Producto -->
                        <td class="cart-item-product">
                            <div class="d-flex align-items-center gap-3">
                                <?php if ( $item['thumbnail'] ) : ?>
                                    <img src="<?php echo esc_url( $item['thumbnail'] ); ?>" 
                                         alt="<?php echo esc_attr( $item['title'] ); ?>"
                                         class="cart-item-thumbnail"
                                         style="width: 80px; height: 80px; object-fit: cover;">
                                <?php endif; ?>
                                
                                <div>
                                    <a href="<?php echo esc_url( $item['permalink'] ); ?>" class="fw-bold text-decoration-none">
                                        <?php echo esc_html( $item['title'] ); ?>
                                    </a>
                                    
                                    <?php if ( ! empty( $item['attributes'] ) ) : ?>
                                        <div class="small text-muted mt-1">
                                            <?php foreach ( $item['attributes'] as $key => $value ) : ?>
                                                <span class="d-block">
                                                    <strong><?php echo esc_html( ucfirst( $key ) ); ?>:</strong> 
                                                    <?php echo esc_html( $value ); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Precio -->
                        <td class="cart-item-price">
                            <?php echo esc_html( number_format_i18n( $item['price'], 2 ) ); ?> €
                        </td>
                        
                        <!-- Cantidad -->
                        <td class="cart-item-quantity">
                            <input type="number" 
                                   class="form-control cart-quantity-input" 
                                   data-cart-key="<?php echo esc_attr( $item['cart_key'] ); ?>"
                                   value="<?php echo esc_attr( $item['quantity'] ); ?>" 
                                   min="0" 
                                   max="99"
                                   style="max-width: 80px;">
                        </td>
                        
                        <!-- Subtotal -->
                        <td class="cart-item-subtotal">
                            <strong><?php echo esc_html( number_format_i18n( $item['subtotal'], 2 ) ); ?> €</strong>
                        </td>
                        
                        <!-- Eliminar -->
                        <td class="cart-item-remove">
                            <button type="button" 
                                    class="btn btn-link text-danger remove-from-cart p-0" 
                                    data-cart-key="<?php echo esc_attr( $item['cart_key'] ); ?>"
                                    aria-label="<?php esc_attr_e( 'Eliminar producto', 'fcsd' ); ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                        
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>