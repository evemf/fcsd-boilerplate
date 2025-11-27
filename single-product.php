<?php
// single-product.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header(); ?>

<div class="single-product container">
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

        <?php
        $product_id   = get_the_ID();
        $prices       = fcsd_get_product_prices( $product_id );
        $product_type = get_post_meta( $product_id, '_fcsd_product_type', true );

        // Metas
        $colors_meta  = get_post_meta( $product_id, '_fcsd_product_colors', true );
        $sizes_meta   = get_post_meta( $product_id, '_fcsd_product_sizes', true );
        $sku          = get_post_meta( $product_id, '_fcsd_sku', true );
        $stock        = get_post_meta( $product_id, '_fcsd_stock', true );

        // Normalizamos a arrays
        $color_options = is_array( $colors_meta ) ? $colors_meta : [];
        $size_options  = is_array( $sizes_meta ) ? $sizes_meta : [];

        $product_url   = get_permalink( $product_id );
        $product_title = get_the_title();

        // Galería adjunta
        $attachments = get_attached_media( 'image', $product_id );

        // Frames para vista 360 (todas las imágenes adjuntas excepto la destacada)
        $frames = [];
        if ( $attachments ) {
            foreach ( $attachments as $attachment ) {
                if ( $attachment->ID === get_post_thumbnail_id( $product_id ) ) {
                    continue;
                }
                $src = wp_get_attachment_image_src( $attachment->ID, 'large' );
                if ( $src ) {
                    $frames[] = esc_url( $src[0] );
                }
            }
        }
        ?>

        <article <?php post_class( 'product-single-card row g-4 align-items-start align-items-md-stretch' ); ?>>
            <!-- COLUMNA IZQUIERDA: IMÁGENES -->
            <div class="col-md-6 product-single-media">
                <div class="product-media-wrapper">
                    <div class="product-main-image ratio ratio-1x1 mb-3">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php echo get_the_post_thumbnail( $product_id, 'large', [
                                'class'   => 'img-fluid object-fit-cover',
                                'loading' => 'eager',
                            ] ); ?>
                        <?php else : ?>
                            <div class="product-card-placeholder d-flex align-items-center justify-content-center">
                                <i class="bi bi-image"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ( count( $frames ) > 1 ) : ?>
                        <div class="product-360-wrapper mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h2 class="h6 mb-0">
                                    <?php esc_html_e( 'Vista 360º', 'fcsd' ); ?>
                                </h2>
                                <small class="text-muted">
                                    <?php esc_html_e( 'Arrossega per girar el producte', 'fcsd' ); ?>
                                </small>
                            </div>
                            <div class="fcsd-360-viewer ratio ratio-1x1"
                                 data-frames="<?php echo esc_attr( wp_json_encode( $frames ) ); ?>">
                                <img src="<?php echo esc_url( $frames[0] ); ?>"
                                     alt="<?php echo esc_attr( $product_title ); ?>"
                                     class="img-fluid object-fit-cover fcsd-360-frame" />
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( $attachments && count( $attachments ) > 1 ) : ?>
                        <div class="product-gallery d-flex flex-wrap gap-2">
                            <?php foreach ( $attachments as $attachment ) :
                                if ( $attachment->ID === get_post_thumbnail_id( $product_id ) ) {
                                    continue;
                                } ?>
                                <button type="button"
                                        class="product-gallery-item btn p-0 border-0"
                                        data-main-target=".product-main-image img, .fcsd-360-frame"
                                        data-src="<?php echo esc_attr( wp_get_attachment_image_url( $attachment->ID, 'large' ) ); ?>">
                                    <?php echo wp_get_attachment_image( $attachment->ID, 'thumbnail', false, [
                                        'class'   => 'img-fluid rounded',
                                        'loading' => 'lazy',
                                    ] ); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- COLUMNA DERECHA: INFO + FORM -->
            <div class="col-md-6 d-flex flex-column product-single-summary">
                <header class="product-single-header mb-3">
                    <h1 class="mb-2"><?php the_title(); ?></h1>

                    <div class="mb-2">
                        <?php if ( $prices['sale'] > 0 && $prices['sale'] < $prices['regular'] ) : ?>
                            <p class="h4 mb-1">
                                <span class="text-muted text-decoration-line-through me-2">
                                    <?php echo esc_html( number_format_i18n( $prices['regular'], 2 ) ); ?> €
                                </span>
                                <span class="fw-bold text-accent">
                                    <?php echo esc_html( number_format_i18n( $prices['sale'], 2 ) ); ?> €
                                </span>
                            </p>
                            <p class="small text-success mb-1">
                                <?php esc_html_e( 'Producte en oferta', 'fcsd' ); ?>
                            </p>
                        <?php else : ?>
                            <p class="h4 mb-1">
                                <span class="fw-bold text-accent">
                                    <?php echo esc_html( number_format_i18n( $prices['regular'], 2 ) ); ?> €
                                </span>
                            </p>
                        <?php endif; ?>

                        <?php if ( $prices['member'] > 0 ) : ?>
                            <p class="small text-muted mb-0">
                                <?php
                                printf(
                                    esc_html__( 'Preu per usuaris registrats: %s €', 'fcsd' ),
                                    esc_html( number_format_i18n( $prices['member'], 2 ) )
                                );
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <?php if ( $product_type ) : ?>
                        <p class="small text-muted mb-1">
                            <?php
                            $labels = [
                                'physical'     => __( 'Producte físic', 'fcsd' ),
                                'online'       => __( 'Servei / producte online', 'fcsd' ),
                                'subscription' => __( 'Subscripció', 'fcsd' ),
                                'service'      => __( 'Servei puntual', 'fcsd' ),
                            ];
                            echo esc_html( $labels[ $product_type ] ?? $product_type );
                            ?>
                        </p>
                    <?php endif; ?>

                    <?php if ( $sku || $stock ) : ?>
                        <p class="small text-muted mb-1">
                            <?php if ( $sku ) : ?>
                                <span class="me-3"><?php printf( 'SKU: %s', esc_html( $sku ) ); ?></span>
                            <?php endif; ?>
                            <?php if ( $stock !== '' ) : ?>
                                <span><?php printf( __( 'Estoc: %d', 'fcsd' ), (int) $stock ); ?></span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>

                    <?php if ( has_term( '', 'product_cat', $product_id ) ) : ?>
                        <p class="small text-muted mb-0">
                            <?php esc_html_e( 'Categories:', 'fcsd' ); ?>
                            <?php echo get_the_term_list( $product_id, 'product_cat', '<span>', ', ', '</span>' ); ?>
                        </p>
                    <?php endif; ?>
                </header>

                <!-- BARRA DE COMPARTIR -->
<div class="product-share-bar mb-3">
    <span class="me-2 fw-semibold">
        <?php esc_html_e( 'Compartir', 'fcsd' ); ?>:
    </span>

    <?php
    $encoded_url   = rawurlencode( $product_url );
    $encoded_title = rawurlencode( $product_title );
    ?>

    <a class="btn btn-sm btn-outline-secondary me-1"
       href="https://api.whatsapp.com/send?text=<?php echo $encoded_title . '%20' . $encoded_url; ?>"
       target="_blank" rel="noopener noreferrer"
       aria-label="<?php esc_attr_e( 'Compartir per WhatsApp', 'fcsd' ); ?>"
       title="<?php esc_attr_e( 'Compartir per WhatsApp', 'fcsd' ); ?>">
        <i class="bi bi-whatsapp"></i>
        <span class="visually-hidden">
            <?php esc_html_e( 'Compartir per WhatsApp', 'fcsd' ); ?>
        </span>
    </a>

    <a class="btn btn-sm btn-outline-secondary me-1"
       href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $encoded_url; ?>"
       target="_blank" rel="noopener noreferrer"
       aria-label="<?php esc_attr_e( 'Compartir per Facebook', 'fcsd' ); ?>"
       title="<?php esc_attr_e( 'Compartir per Facebook', 'fcsd' ); ?>">
        <i class="bi bi-facebook"></i>
        <span class="visually-hidden">
            <?php esc_html_e( 'Compartir per Facebook', 'fcsd' ); ?>
        </span>
    </a>

    <button type="button"
            class="btn btn-sm btn-outline-secondary me-1 js-copy-link"
            data-link="<?php echo esc_attr( $product_url ); ?>"
            aria-label="<?php esc_attr_e( 'Compartir per Instagram', 'fcsd' ); ?>"
            title="<?php esc_attr_e( 'Compartir per Instagram', 'fcsd' ); ?>">
        <i class="bi bi-instagram"></i>
        <span class="visually-hidden">
            <?php esc_html_e( 'Compartir per Instagram', 'fcsd' ); ?>
        </span>
    </button>

    <button type="button"
            class="btn btn-sm btn-outline-secondary me-1 js-copy-link"
            data-link="<?php echo esc_attr( $product_url ); ?>"
            aria-label="<?php esc_attr_e( 'Compartir per TikTok', 'fcsd' ); ?>"
            title="<?php esc_attr_e( 'Compartir per TikTok', 'fcsd' ); ?>">
        <i class="bi bi-tiktok"></i>
        <span class="visually-hidden">
            <?php esc_html_e( 'Compartir per TikTok', 'fcsd' ); ?>
        </span>
    </button>

    <button type="button"
            class="btn btn-sm btn-outline-secondary js-copy-link"
            data-link="<?php echo esc_attr( $product_url ); ?>"
            aria-label="<?php esc_attr_e( 'Copiar enllaç', 'fcsd' ); ?>"
            title="<?php esc_attr_e( 'Copiar enllaç', 'fcsd' ); ?>">
        <i class="bi bi-link-45deg"></i>
        <span class="visually-hidden">
            <?php esc_html_e( 'Copiar enllaç', 'fcsd' ); ?>
        </span>
    </button>

    <small class="text-success d-none js-copy-ok ms-2">
        <?php esc_html_e( 'Enllaç copiat', 'fcsd' ); ?>
    </small>
</div>


                <div class="product-description mb-3">
                    <?php the_content(); ?>
                </div>

                <!-- FORMULARIO AÑADIR A LA CESTA -->
<form method="post" class="add-to-cart-form mt-auto">
    <?php wp_nonce_field( 'fcsd_add_to_cart', 'fcsd_add_to_cart_nonce' ); ?>

    <input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>">

    <input type="hidden" name="redirect_to"
           value="<?php echo esc_url( get_permalink( get_page_by_path( 'carrito' ) ) ); ?>">

    <?php if ( ! empty( $color_options ) ) : ?>
        <div class="mb-3 fcsd-product-attribute fcsd-product-colors">
            <label class="form-label d-block mb-1">
                <?php esc_html_e( 'Color', 'fcsd' ); ?>
            </label>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ( $color_options as $i => $color ) : ?>
                    <?php $hex = trim( $color ); ?>
                    <label class="fcsd-color-swatch">
                        <input type="radio"
                               name="fcsd_color"
                               value="<?php echo esc_attr( $hex ); ?>"
                               <?php checked( $i, 0 ); ?> />
                        <span class="fcsd-color-circle"
                              style="background-color: <?php echo esc_attr( $hex ); ?>;"></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $size_options ) ) : ?>
        <div class="mb-3 fcsd-product-attribute fcsd-product-sizes">
            <label class="form-label d-block mb-1">
                <?php esc_html_e( 'Talla', 'fcsd' ); ?>
            </label>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ( $size_options as $i => $size ) : ?>
                    <label class="fcsd-size-pill">
                        <input type="radio"
                               name="fcsd_size"
                               value="<?php echo esc_attr( $size ); ?>"
                               <?php checked( $i, 0 ); ?> />
                        <span><?php echo esc_html( $size ); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="mb-3">
        <label for="quantity" class="form-label">
            <?php esc_html_e( 'Quantitat', 'fcsd' ); ?>
        </label>
        <input type="number" name="quantity" id="quantity" value="1" min="1" class="form-control"
               style="max-width:120px;">
    </div>

    <button type="submit" class="btn btn-primary"
            data-loading-text="<?php esc_attr_e( 'Afegint...', 'fcsd' ); ?>">
        <?php esc_html_e( 'Afegir a la cistella', 'fcsd' ); ?>
    </button>
</form>
            </div>
        </article>

        <?php
        // Recomendaciones personalizadas para usuarios registrados
        if ( is_user_logged_in() && function_exists( 'fcsd_get_recommended_products' ) ) :
            $recommended = fcsd_get_recommended_products( $product_id, 4 );
            if ( $recommended ) :
                get_template_part( 'template-parts/recommendations', null, [
                    'products' => $recommended,
                ] );
            endif;
        endif;
        ?>

    <?php endwhile; endif; ?>
</div>

<?php
get_footer();
