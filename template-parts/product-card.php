<?php
/**
 * Template part: Tarjeta de producto para el archivo / tienda
 */

// Seguridad
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$product_id   = get_the_ID();
$prices       = function_exists( 'fcsd_get_product_prices' ) ? fcsd_get_product_prices( $product_id ) : [
    'regular' => 0,
    'sale'    => 0,
    'member'  => 0,
];

// Metas de colores y tallas (mismas que en single-product.php)
$colors_meta  = get_post_meta( $product_id, '_fcsd_product_colors', true );
$sizes_meta   = get_post_meta( $product_id, '_fcsd_product_sizes', true );

$color_options = is_array( $colors_meta ) ? $colors_meta : [];
$size_options  = is_array( $sizes_meta )  ? $sizes_meta  : [];

// Valores por defecto (primer elemento), igual que en single
$default_color = ! empty( $color_options ) ? trim( $color_options[0] ) : '';
$default_size  = ! empty( $size_options )  ? $size_options[0]           : '';
?>

<div class="col-md-6 col-lg-4 mb-4">
    <article <?php post_class( 'product-card h-100 d-flex flex-column' ); ?>>

        <!-- PARTE CLICABLE: imagen + título + texto -->
        <a href="<?php the_permalink(); ?>" class="product-card-link text-decoration-none text-reset d-flex flex-column flex-grow-1">
            <div class="product-card-image position-relative mb-3">
                <div class="ratio ratio-4x3 rounded-4 overflow-hidden">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <?php echo get_the_post_thumbnail( $product_id, 'large', [
                            'class'   => 'img-fluid object-fit-cover',
                            'loading' => 'lazy',
                        ] ); ?>
                    <?php else : ?>
                        <div class="product-card-placeholder d-flex align-items-center justify-content-center">
                            <i class="bi bi-image fs-1 text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ( ! empty( $prices['regular'] ) ) : ?>
                    <div class="product-card-price-badge position-absolute top-0 end-0 m-3 px-3 py-1 rounded-pill bg-accent text-light fw-semibold shadow-sm">
                        <?php echo esc_html( number_format_i18n( $prices['regular'], 2 ) ); ?> €
                    </div>
                <?php endif; ?>
            </div>

            <div class="product-card-body mb-3">
                <h2 class="h5 mb-1"><?php the_title(); ?></h2>

                <?php if ( ! empty( $prices['member'] ) ) : ?>
                    <p class="small text-muted mb-0">
                        <?php esc_html_e( 'Preu especial per usuaris registrats', 'fcsd' ); ?>
                    </p>
                <?php endif; ?>
            </div>
        </a>

        <!-- PARTE NO CLICABLE: opciones + botón añadir -->
        <div class="product-card-footer mt-auto pt-2">
            <?php if ( ! empty( $color_options ) ) : ?>
                <div class="mb-2 fcsd-product-attribute fcsd-product-colors">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <?php foreach ( $color_options as $i => $color ) : ?>
                            <?php
                            $hex          = trim( $color );
                            $is_default   = ( $hex === $default_color );
                            $active_class = $is_default ? ' is-active' : '';
                            ?>
                            <label class="fcsd-color-swatch mb-0<?php echo esc_attr( $active_class ); ?>"
                                   title="<?php echo esc_attr( $hex ); ?>">
                                <input type="radio"
                                       name="fcsd_color_<?php echo esc_attr( $product_id ); ?>"
                                       class="d-none js-product-color-input"
                                       value="<?php echo esc_attr( $hex ); ?>"
                                       <?php checked( $is_default ); ?> />
                                <span class="fcsd-color-circle"
                                      style="width:20px;height:20px;border-radius:999px;border:2px solid #fff;box-shadow:0 0 0 1px #ccc;background-color: <?php echo esc_attr( $hex ); ?>;"></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $size_options ) ) : ?>
                <div class="mb-3 fcsd-product-attribute fcsd-product-sizes">
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ( $size_options as $i => $size ) : ?>
                            <?php
                            $is_default   = ( $size === $default_size );
                            $active_class = $is_default ? ' is-active' : '';
                            ?>
                            <label class="fcsd-size-pill mb-0<?php echo esc_attr( $active_class ); ?>">
                                <input type="radio"
                                       name="fcsd_size_<?php echo esc_attr( $product_id ); ?>"
                                       class="d-none js-product-size-input"
                                       value="<?php echo esc_attr( $size ); ?>"
                                       <?php checked( $is_default ); ?> />
                                <span><?php echo esc_html( $size ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!--
                NOTA:
                - Mantenemos la clase add-to-cart-form para tu JS actual (deshabilitar botón, etc.).
                - Añadimos add-to-cart-ajax + data-product-id para el flujo AJAX.
                - El botón es type="button" para que NO haga submit clásico (no redirección).
                - El JS debe engancharse a .js-add-to-cart-btn para llamar al AJAX y cambiar el texto/color.
            -->
            <form class="add-to-cart-form add-to-cart-ajax"
                  data-product-id="<?php echo esc_attr( $product_id ); ?>">

                <?php wp_nonce_field( 'fcsd_add_to_cart', 'fcsd_add_to_cart_nonce' ); ?>

                <input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>">
                <!-- No mandamos redirect_to: el flujo AJAX se encarga; el POST clásico no se usa. -->

                <!-- Campos ocultos para enviar color/talla seleccionados -->
                <?php if ( ! empty( $color_options ) ) : ?>
                    <input type="hidden"
                           name="fcsd_color"
                           class="js-selected-color-field"
                           value="<?php echo esc_attr( $default_color ); ?>">
                <?php endif; ?>

                <?php if ( ! empty( $size_options ) ) : ?>
                    <input type="hidden"
                           name="fcsd_size"
                           class="js-selected-size-field"
                           value="<?php echo esc_attr( $default_size ); ?>">
                <?php endif; ?>

                <input type="hidden" name="quantity" value="1">

                <button type="button"
                        class="btn btn-primary w-100 js-add-to-cart-btn">
                    <?php esc_html_e( 'Afegir a la cistella', 'fcsd' ); ?>
                </button>
            </form>
        </div>
    </article>
</div>
