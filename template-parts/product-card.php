<?php
/**
 * Template part: Tarjeta de producto para el archivo / tienda
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$product_id   = get_the_ID();
$prices       = function_exists( 'fcsd_get_product_prices' ) ? fcsd_get_product_prices( $product_id ) : [
    'regular' => 0,
    'sale'    => 0,
    'member'  => 0,
];

// Metas de colores y tallas (mismas que en single)
$colors_meta  = get_post_meta( $product_id, '_fcsd_product_colors', true );
$sizes_meta   = get_post_meta( $product_id, '_fcsd_product_sizes', true );

$color_options = is_array( $colors_meta ) ? $colors_meta : [];
$size_options  = is_array( $sizes_meta )  ? $sizes_meta  : [];

$default_color = ! empty( $color_options ) ? trim( $color_options[0] ) : '';
$default_size  = ! empty( $size_options )  ? $size_options[0]           : '';

// Preview text (ya i18n en fcsd_get_product_card_preview)
$preview = function_exists( 'fcsd_get_product_card_preview' )
    ? fcsd_get_product_card_preview( $product_id, 18 )
    : '';
?>

<div class="col-md-6 col-lg-4 mb-4">
    <article <?php post_class( 'product-card h-100 d-flex flex-column' ); ?>>

        <!-- PARTE CLICABLE: imagen + precio + título + descripción -->
        <a href="<?php the_permalink(); ?>" class="product-card-link text-decoration-none text-reset d-block flex-grow-1">
            <div class="product-card-media position-relative mb-3">
                <div class="ratio ratio-4x3 rounded-4 overflow-hidden">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <?php echo get_the_post_thumbnail( $product_id, 'large', [
                            'class'   => 'img-fluid object-fit-cover w-100 h-100',
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

            <div class="product-card-body mb-2">
                <h2 class="product-card-title h5 mb-1"><?php the_title(); ?></h2>

                <?php if ( $preview ) : ?>
                    <p class="product-card-excerpt small text-muted mb-0"><?php echo esc_html( $preview ); ?></p>
                <?php endif; ?>

                <?php if ( ! empty( $prices['member'] ) ) : ?>
                    <p class="small text-muted mb-0">
                        <?php esc_html_e( 'Preu especial per usuaris registrats', 'fcsd' ); ?>
                    </p>
                <?php endif; ?>
            </div>
        </a>

        <!-- PARTE NO CLICABLE: opciones + cantidad + botón -->
        <div class="product-card-footer mt-auto pt-2">

            <?php
            $has_colors = ! empty( $color_options );
            $has_sizes  = ! empty( $size_options );
            $attr_col_class = ( $has_colors && $has_sizes ) ? 'col-6' : 'col-12';
            ?>

            <?php if ( $has_colors || $has_sizes ) : ?>
                <div class="product-card-attrs row g-3 mb-3">
                    <?php if ( $has_colors ) : ?>
                        <div class="<?php echo esc_attr( $attr_col_class ); ?>">
                            <div class="product-card-attr">
                                <span class="product-card-attr-label"><?php esc_html_e( 'Colors disponibles:', 'fcsd' ); ?></span>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <?php foreach ( $color_options as $color ) : ?>
                                        <?php
                                        $hex        = trim( $color );
                                        $is_default = ( $hex === $default_color );
                                        ?>
                                        <label class="fcsd-color-swatch mb-0" title="<?php echo esc_attr( $hex ); ?>">
                                            <input type="radio"
                                                   name="fcsd_color_<?php echo esc_attr( $product_id ); ?>"
                                                   class="d-none js-product-color-input"
                                                   value="<?php echo esc_attr( $hex ); ?>"
                                                   <?php checked( $is_default ); ?> />
                                            <span class="fcsd-color-circle" style="background-color: <?php echo esc_attr( $hex ); ?>;"></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( $has_sizes ) : ?>
                        <div class="<?php echo esc_attr( $attr_col_class ); ?>">
                            <div class="product-card-attr">
                                <span class="product-card-attr-label"><?php esc_html_e( 'Talles disponibles:', 'fcsd' ); ?></span>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ( $size_options as $size ) : ?>
                                        <?php $is_default = ( $size === $default_size ); ?>
                                        <label class="fcsd-size-pill mb-0">
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
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form class="add-to-cart-form add-to-cart-ajax product-card-actions"
                  data-product-id="<?php echo esc_attr( $product_id ); ?>">

                <?php wp_nonce_field( 'fcsd_add_to_cart', 'fcsd_add_to_cart_nonce' ); ?>

                <input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>">

                <?php if ( ! empty( $color_options ) ) : ?>
                    <input type="hidden" name="fcsd_color" class="js-selected-color-field" value="<?php echo esc_attr( $default_color ); ?>">
                <?php endif; ?>

                <?php if ( ! empty( $size_options ) ) : ?>
                    <input type="hidden" name="fcsd_size" class="js-selected-size-field" value="<?php echo esc_attr( $default_size ); ?>">
                <?php endif; ?>

                <div class="d-flex gap-2 align-items-stretch">
                    <div class="fcsd-qty-spinner input-group">
                        <button type="button" class="btn btn-outline-secondary js-qty-minus" aria-label="-" tabindex="-1">-</button>
                        <input type="number" name="quantity" class="form-control text-center js-qty-input" value="1" min="1" step="1" inputmode="numeric" />
                        <button type="button" class="btn btn-outline-secondary js-qty-plus" aria-label="+" tabindex="-1">+</button>
                    </div>

                    <button type="button" class="btn btn-primary flex-grow-1 js-add-to-cart-btn">
                        <?php esc_html_e( 'Afegir', 'fcsd' ); ?>
                    </button>
                </div>
            </form>
        </div>
    </article>
</div>
