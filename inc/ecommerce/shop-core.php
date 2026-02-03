<?php
// inc/ecommerce/shop-core.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Càrrega de classes i helpers de la botiga
 * (aquests fitxers han d'existir a inc/ecommerce/)
 */
require get_template_directory() . '/inc/ecommerce/class-shop-db.php';
require get_template_directory() . '/inc/ecommerce/class-shop-cart.php';
require get_template_directory() . '/inc/ecommerce/class-shop-orders.php';
require get_template_directory() . '/inc/ecommerce/class-shop-account.php';
require get_template_directory() . '/inc/ecommerce/class-shop-discounts.php';
require get_template_directory() . '/inc/ecommerce/template-tags-shop.php';

/**
 * Registrar Custom Post Type: fcsd_product
 */
function fcsd_register_product_cpt() {
    $labels = [
        'name'               => __( 'Productes', 'fcsd' ),
        'singular_name'      => __( 'Producte', 'fcsd' ),
        'add_new'            => __( 'Afegeix nou', 'fcsd' ),
        'add_new_item'       => __( 'Afegeix un producte nou', 'fcsd' ),
        'edit_item'          => __( 'Edita el producte', 'fcsd' ),
        'new_item'           => __( 'Producte nou', 'fcsd' ),
        'view_item'          => __( 'Mostra el producte', 'fcsd' ),
        'search_items'       => __( 'Cerca productes', 'fcsd' ),
        'not_found'          => __( 'No s\'han trobat productes', 'fcsd' ),
        'not_found_in_trash' => __( 'No hi ha productes a la paperera', 'fcsd' ),
    ];

    $args = [
        'label'         => __( 'Productes', 'fcsd' ),
        'labels'        => $labels,
        'public'        => true,
        'has_archive'   => true,
        // IMPORTANT:
        // - Els rewrites del CPT han d'usar l'slug canònic (idioma per defecte).
        // - L'encaminador i18n del tema s'encarrega de:
        //     /es/tienda  -> /botiga
        //     /en/shop    -> /botiga
        // Així evitem duplicar plantilles (archive-product-es.php, etc.)
        'rewrite'       => [ 'slug' => function_exists('fcsd_default_slug') ? fcsd_default_slug('shop') : 'botiga' ],
        'supports'      => [ 'title', 'editor', 'thumbnail' ],
        'show_in_rest'  => true,
        'menu_icon'     => 'dashicons-cart',
    ];

    register_post_type( 'fcsd_product', $args );
}
add_action( 'init', 'fcsd_register_product_cpt' );

/**
 * Taxonomia: fcsd_product_cat
 */
function fcsd_register_product_taxonomy() {
    $labels = [
        'name'          => __( 'Categories de producte', 'fcsd' ),
        'singular_name' => __( 'Categoria de producte', 'fcsd' ),
        'search_items'  => __( 'Cerca categories', 'fcsd' ),
        'all_items'     => __( 'Totes les categories', 'fcsd' ),
        'edit_item'     => __( 'Edita la categoria', 'fcsd' ),
        'update_item'   => __( 'Actualitza la categoria', 'fcsd' ),
        'add_new_item'  => __( 'Afegeix una categoria nova', 'fcsd' ),
        'new_item_name' => __( 'Nom nou de la categoria', 'fcsd' ),
    ];

    register_taxonomy( 'fcsd_product_cat', 'fcsd_product', [
        'labels'       => $labels,
        'public'       => true,
        'hierarchical' => true,
        'rewrite'      => [ 'slug' => 'categoria-producte' ],
        'show_in_rest' => true,
    ] );
}
add_action( 'init', 'fcsd_register_product_taxonomy' );

/**
 * Crear taules de comandes en activar el tema
 */
function fcsd_shop_on_theme_switch() {
    fcsd_Shop_DB::create_tables();
}
add_action( 'after_switch_theme', 'fcsd_shop_on_theme_switch' );

/**
 * Encolar assets de la botiga
 */
function fcsd_shop_assets() {
    wp_enqueue_script(
        'fcsd-shop',
        get_template_directory_uri() . '/assets/js/shop.js',
        [ 'jquery' ],
        '1.0.0',
        true
    );

    wp_localize_script( 'fcsd-shop', 'fcsd_shop', [
        'ajax_url'     => admin_url( 'admin-ajax.php' ),
        'nonce'        => wp_create_nonce( 'fcsd_shop_nonce' ),
        'adding_text'  => __( 'Afegint...', 'fcsd' ),
        'added_text'   => __( 'Producte afegit!', 'fcsd' ),
        'default_text' => __( 'Afegir a la cistella', 'fcsd' ),
        'error_text'   => __( 'Error', 'fcsd' ),
        'i18n'         => [
            'add_error_fallback' => __( 'Error en afegir el producte.', 'fcsd' ),
            'connection_error'   => __( 'Error de connexió. Si us plau, torna-ho a provar.', 'fcsd' ),
            'reload_error'       => __( 'Error de connexió. Si us plau, recarrega la pàgina.', 'fcsd' ),
            'update_error'       => __( 'Error en actualitzar la cistella.', 'fcsd' ),
            'remove_error'       => __( 'Error en eliminar de la cistella.', 'fcsd' ),
            'removed_success'    => __( 'Producte eliminat de la cistella.', 'fcsd' ),
            'confirm_remove'     => __( 'Estàs segur que vols eliminar aquest producte de la cistella?', 'fcsd' ),
            'close'             => __( 'Tancar', 'fcsd' ),
        ],
    ] );
}
add_action( 'wp_enqueue_scripts', 'fcsd_shop_assets' );

/**
 * Iniciar sessió PHP per a carret de convidats
 */
add_action( 'init', function () {
    if ( ! session_id() ) {
        session_start();
    }
} );

/**
 * Handler per "Afegir a la cistella" (formularis POST)
 *
 * Colors i talles:
 * - Configurats via metas:
 *   - _fcsd_product_colors => array de colors (hex)
 *   - _fcsd_product_sizes  => array de talles
 * - Si no arriben en POST, s'usa el primer valor disponible com a per defecte.
 */
function fcsd_handle_add_to_cart() {
    if ( empty( $_POST['fcsd_add_to_cart_nonce'] ) ||
         ! wp_verify_nonce( $_POST['fcsd_add_to_cart_nonce'], 'fcsd_add_to_cart' ) ) {
        return;
    }

    $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
    $quantity   = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1;

    if ( ! $product_id ) {
        return;
    }

    // Verificar que el producte existeix
    if ( get_post_type( $product_id ) !== 'fcsd_product' ) {
        wp_die( __( 'Producte no vàlid.', 'fcsd' ) );
    }

    $has_variants = (int) get_post_meta( $product_id, '_fcsd_has_variants', true );

    // Metas de colors i talles (com a single-product.php)
    $colors_meta = get_post_meta( $product_id, '_fcsd_product_colors', true );
    $sizes_meta  = get_post_meta( $product_id, '_fcsd_product_sizes', true );

    $color_options = is_array( $colors_meta ) ? $colors_meta : [];
    $size_options  = is_array( $sizes_meta )  ? $sizes_meta  : [];

    $attributes = [];

    // Si el producte NO té variants, ignorem color/talla (encara que estiguin definits com a opcions visuals).
    if ( ! $has_variants ) {
        $color_options = [];
        $size_options  = [];
    }


    // 1) Valors enviats explícitament des del formulari
    $color = isset( $_POST['fcsd_color'] ) ? sanitize_text_field( wp_unslash( $_POST['fcsd_color'] ) ) : '';
    $size  = isset( $_POST['fcsd_size'] )  ? sanitize_text_field( wp_unslash( $_POST['fcsd_size'] ) )  : '';

    // 2) Si no arriba color i hi ha opcions, usem la primera (preseleccionada per defecte)
    if ( $color === '' && ! empty( $color_options ) ) {
        $first_color = reset( $color_options );
        if ( ! empty( $first_color ) ) {
            $color = $first_color;
        }
    }

    // 3) Si no arriba talla i hi ha opcions, usem la primera
    if ( $size === '' && ! empty( $size_options ) ) {
        $first_size = reset( $size_options );
        if ( ! empty( $first_size ) ) {
            $size = $first_size;
        }
    }

    // 4) Filtres per si vols sobreescriure des d'un plugin/hook
    $color = apply_filters( 'fcsd_default_color_for_product', $color, $product_id );
    $size  = apply_filters( 'fcsd_default_size_for_product',  $size,  $product_id );

    // 5) Guardar en atributs si existeixen
    if ( $color !== '' ) {
        $attributes['color'] = $color;
    }

    if ( $size !== '' ) {
        $attributes['size'] = $size;
    }

    // Afegir a la cistella
    fcsd_Shop_Cart::add_to_cart( $product_id, $quantity, $attributes );

    // Determinar redirecció:
    // - single-product envia redirect_to = carret
    // - arxiu/llistat NO envia redirect_to, així que tornem al referer (la botiga)
    if ( ! empty( $_POST['redirect_to'] ) ) {
        $redirect = esc_url_raw( wp_unslash( $_POST['redirect_to'] ) );
    } else {
        $redirect = wp_get_referer() ? wp_get_referer() : get_permalink( get_page_by_path( 'carrito' ) );
    }

    // Afegir paràmetre d'èxit per poder mostrar missatge si volem
    $redirect = add_query_arg( 'added_to_cart', $product_id, $redirect );

    wp_safe_redirect( $redirect );
    exit;
}
add_action( 'template_redirect', 'fcsd_handle_add_to_cart' );

/**
 * Handler per actualitzar quantitat al carret
 */
function fcsd_handle_update_cart() {
    if ( empty( $_POST['fcsd_update_cart_nonce'] ) ||
         ! wp_verify_nonce( $_POST['fcsd_update_cart_nonce'], 'fcsd_update_cart' ) ) {
        return;
    }

    if ( isset( $_POST['cart_key'] ) && isset( $_POST['quantity'] ) ) {
        $cart_key = sanitize_text_field( wp_unslash( $_POST['cart_key'] ) );
        $quantity = absint( $_POST['quantity'] );

        fcsd_Shop_Cart::update_cart_item( $cart_key, $quantity );

        wp_safe_redirect( wp_get_referer() );
        exit;
    }
}
add_action( 'template_redirect', 'fcsd_handle_update_cart' );

/**
 * Handler per eliminar del carret
 */
function fcsd_handle_remove_from_cart() {
    if ( empty( $_POST['fcsd_remove_cart_nonce'] ) ||
         ! wp_verify_nonce( $_POST['fcsd_remove_cart_nonce'], 'fcsd_remove_from_cart' ) ) {
        return;
    }

    if ( isset( $_POST['remove_from_cart'] ) && isset( $_POST['cart_key'] ) ) {
        $cart_key = sanitize_text_field( wp_unslash( $_POST['cart_key'] ) );

        fcsd_Shop_Cart::remove_from_cart( $cart_key );

        wp_safe_redirect( wp_get_referer() );
        exit;
    }
}
add_action( 'template_redirect', 'fcsd_handle_remove_from_cart' );

/**
 * Checkout (classe Orders)
 */
add_action( 'template_redirect', [ 'fcsd_Shop_Orders', 'handle_checkout' ] );

/**
 * Repetir comanda (classe Account)
 */
add_action( 'template_redirect', function () {
    if ( isset( $_GET['repeat_order'] ) ) {
        fcsd_Shop_Account::repeat_order( absint( $_GET['repeat_order'] ) );
    }
} );

/**
 * AJAX: Afegir producte a la cistella (des d'arxiu/llistat)
 */
function fcsd_ajax_add_to_cart() {
    check_ajax_referer( 'fcsd_shop_nonce', 'nonce' );

    if ( ! isset( $_POST['product_id'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Dades invàlides', 'fcsd' ) ) );
    }

    $product_id = absint( $_POST['product_id'] );
    $quantity   = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1;
    $color      = isset( $_POST['fcsd_color'] ) ? sanitize_text_field( wp_unslash( $_POST['fcsd_color'] ) ) : '';
    $size       = isset( $_POST['fcsd_size'] ) ? sanitize_text_field( wp_unslash( $_POST['fcsd_size'] ) ) : '';

    // Verificar que el producte existeix
    if ( get_post_type( $product_id ) !== 'fcsd_product' ) {
        wp_send_json_error( array( 'message' => __( 'Producte no vàlid', 'fcsd' ) ) );
    }

    // Obtenir opcions de colors i talles
    $colors_meta = get_post_meta( $product_id, '_fcsd_product_colors', true );
    $sizes_meta  = get_post_meta( $product_id, '_fcsd_product_sizes', true );

    $color_options = is_array( $colors_meta ) ? $colors_meta : [];
    $size_options  = is_array( $sizes_meta )  ? $sizes_meta  : [];

    // Si no s'ha enviat color però hi ha opcions, usar el primer
    if ( empty( $color ) && ! empty( $color_options ) ) {
        $color = reset( $color_options );
    }

    // Si no s'ha enviat talla però hi ha opcions, usar la primera
    if ( empty( $size ) && ! empty( $size_options ) ) {
        $size = reset( $size_options );
    }

    // Construir atributs
    $attributes = [];

    // Si el producte NO té variants, ignorem color/talla (encara que estiguin definits com a opcions visuals).
    if ( ! $has_variants ) {
        $color_options = [];
        $size_options  = [];
    }

    if ( ! empty( $color ) ) {
        $attributes['color'] = $color;
    }
    if ( ! empty( $size ) ) {
        $attributes['size'] = $size;
    }

    // Afegir a la cistella
    fcsd_Shop_Cart::add_to_cart( $product_id, $quantity, $attributes );

    wp_send_json_success( array(
        'message'    => __( 'Producte afegit a la cistella', 'fcsd' ),
        'cart_count' => fcsd_Shop_Cart::get_cart_count()
    ) );
}
add_action( 'wp_ajax_fcsd_add_to_cart', 'fcsd_ajax_add_to_cart' );
add_action( 'wp_ajax_nopriv_fcsd_add_to_cart', 'fcsd_ajax_add_to_cart' );

/**
 * AJAX: Actualitzar quantitat de producte al carret
 */
function fcsd_ajax_update_cart_item() {
    check_ajax_referer( 'fcsd_shop_nonce', 'nonce' );

    if ( ! isset( $_POST['cart_key'] ) || ! isset( $_POST['quantity'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Dades invàlides', 'fcsd' ) ) );
    }

    $cart_key = sanitize_text_field( wp_unslash( $_POST['cart_key'] ) );
    $quantity = absint( $_POST['quantity'] );

    $updated = fcsd_Shop_Cart::update_cart_item( $cart_key, $quantity );

    if ( $updated ) {
        wp_send_json_success( array(
            'message'    => __( 'Carret actualitzat', 'fcsd' ),
            'cart_count' => fcsd_Shop_Cart::get_cart_count()
        ) );
    } else {
        wp_send_json_error( array( 'message' => __( 'Error en actualitzar el carret', 'fcsd' ) ) );
    }
}
add_action( 'wp_ajax_fcsd_update_cart_item', 'fcsd_ajax_update_cart_item' );
add_action( 'wp_ajax_nopriv_fcsd_update_cart_item', 'fcsd_ajax_update_cart_item' );

/**
 * AJAX: Eliminar producte del carret
 */
function fcsd_ajax_remove_from_cart() {
    check_ajax_referer( 'fcsd_shop_nonce', 'nonce' );

    if ( ! isset( $_POST['cart_key'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Dades invàlides', 'fcsd' ) ) );
    }

    $cart_key = sanitize_text_field( wp_unslash( $_POST['cart_key'] ) );

    $removed = fcsd_Shop_Cart::remove_from_cart( $cart_key );

    if ( $removed ) {
        wp_send_json_success( array(
            'message'    => __( 'Producte eliminat del carret', 'fcsd' ),
            'cart_count' => fcsd_Shop_Cart::get_cart_count()
        ) );
    } else {
        wp_send_json_error( array( 'message' => __( 'Error en eliminar del carret', 'fcsd' ) ) );
    }
}
add_action( 'wp_ajax_fcsd_remove_from_cart', 'fcsd_ajax_remove_from_cart' );
add_action( 'wp_ajax_nopriv_fcsd_remove_from_cart', 'fcsd_ajax_remove_from_cart' );

/**
 * AJAX: Obtenir contingut del carret actualitzat
 */
function fcsd_ajax_get_cart_content() {
    check_ajax_referer( 'fcsd_shop_nonce', 'nonce' );

    ob_start();

    $cart = fcsd_get_cart_summary();
    ?>

    <h1><?php esc_html_e( 'Cistella', 'fcsd' ); ?></h1>

    <?php get_template_part( 'template-parts/shop/cart', 'table', [ 'cart' => $cart ] ); ?>

    <?php if ( ! empty( $cart['items'] ) ) : ?>
        <p class="cart-total">
            <?php esc_html_e( 'Total:', 'fcsd' ); ?>
            <?php echo esc_html( number_format_i18n( $cart['total'], 2 ) ); ?> €
        </p>

        <?php if ( is_user_logged_in() ) : ?>
            <p class="cart-discount-info">
                <?php esc_html_e( 'S\'han aplicat descomptes d\'usuari registrat (si escau).', 'fcsd' ); ?>
            </p>
        <?php else : ?>
            <p class="cart-discount-info">
                <?php esc_html_e( 'Registra\'t o inicia sessió per obtenir descomptes exclusius.', 'fcsd' ); ?>
            </p>
        <?php endif; ?>

        <?php $checkout_id = function_exists('fcsd_get_page_id_by_key') ? fcsd_get_page_id_by_key('checkout') : 0; ?>
        <a href="<?php echo esc_url( $checkout_id ? get_permalink( $checkout_id ) : home_url('/') ); ?>" class="btn btn-primary">
            <?php esc_html_e( 'Anar a pagar', 'fcsd' ); ?>
        </a>
    <?php endif; ?>

    <?php
    $html = ob_get_clean();

    wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_fcsd_get_cart_content', 'fcsd_ajax_get_cart_content' );
add_action( 'wp_ajax_nopriv_fcsd_get_cart_content', 'fcsd_ajax_get_cart_content' );

/**
 * Filtres de l'arxiu de productes (botiga)
 * - Categoria (fcsd_product_cat)
 * - Color (meta _fcsd_product_colors)
 * - Preu mínim/màxim (meta _fcsd_price_regular)
 */
function fcsd_apply_shop_filters( $query ) {

    // Només front-end, main query, arxiu de productes
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    if ( ! $query->is_post_type_archive( 'fcsd_product' ) && $query->get( 'post_type' ) !== 'fcsd_product' ) {
        return;
    }

    $meta_query = (array) $query->get( 'meta_query' );
    $tax_query  = (array) $query->get( 'tax_query' );

    // -------------------------------
    // Categoria de producte (fcsd_product_cat)
    // -------------------------------
    if ( ! empty( $_GET['shop_cat'] ) ) {
        $cat_id = (int) $_GET['shop_cat'];
        if ( $cat_id > 0 ) {
            $tax_query[] = [
                'taxonomy' => 'fcsd_product_cat',
                'field'    => 'term_id',
                'terms'    => $cat_id,
            ];
        }
    }

    // -------------------------------
    // Color (array de hex a _fcsd_product_colors)
    // Es guarden com a array serialitzat, usem LIKE
    // -------------------------------
    if ( ! empty( $_GET['color'] ) ) {
        $color_raw = sanitize_text_field( wp_unslash( $_GET['color'] ) );
        $color     = $color_raw;

        // Si existeix el helper, intentem mapar slug => hex
        if ( function_exists( 'fcsd_get_shop_colors' ) ) {
            $all_colors = fcsd_get_shop_colors();

            if ( isset( $all_colors[ $color_raw ]['hex'] ) ) {
                // p. ex. "red" -> "#ff0000"
                $color = $all_colors[ $color_raw ]['hex'];
            }
        }

        // Normalitzem: assegurem # al davant si encara no el té
        if ( $color !== '' && $color[0] !== '#' ) {
            $color = '#' . $color;
        }

        $meta_query[] = [
            'key'     => '_fcsd_product_colors',
            'value'   => $color,
            'compare' => 'LIKE',
        ];
    }

    // -------------------------------
    // Rang de preu (usem _fcsd_price_regular)
    // -------------------------------
    $price_min = isset( $_GET['price_min'] ) && $_GET['price_min'] !== ''
        ? (float) $_GET['price_min']
        : null;

    $price_max = isset( $_GET['price_max'] ) && $_GET['price_max'] !== ''
        ? (float) $_GET['price_max']
        : null;

    if ( $price_min === 0.0 && $price_max === 0.0 ) {
        $price_min = null;
        $price_max = null;
    }

    if ( $price_min !== null || $price_max !== null ) {
        $price_filter = [
            'key'     => '_fcsd_price_regular',
            'type'    => 'NUMERIC',
        ];

        if ( $price_min !== null && $price_max !== null ) {
            $price_filter['value']   = [ $price_min, $price_max ];
            $price_filter['compare'] = 'BETWEEN';
        } elseif ( $price_min !== null ) {
            $price_filter['value']   = $price_min;
            $price_filter['compare'] = '>=';
        } elseif ( $price_max !== null ) {
            $price_filter['value']   = $price_max;
            $price_filter['compare'] = '<=';
        }

        $meta_query[] = $price_filter;
    }

    if ( ! empty( $meta_query ) ) {
        $query->set( 'meta_query', $meta_query );
    }

    if ( ! empty( $tax_query ) ) {
        $query->set( 'tax_query', $tax_query );
    }

    // Opcional: ordenar per preu
    // $query->set( 'meta_key', '_fcsd_price_regular' );
    // $query->set( 'orderby', 'meta_value_num' );
    // $query->set( 'order', 'ASC' );
}
add_action( 'pre_get_posts', 'fcsd_apply_shop_filters' );

/**
 * Colors disponibles per als filtres de botiga
 * slug => [ 'label' => '', 'hex' => '' ]
 */
function fcsd_get_shop_colors() {
    return [
        'black' => [
            'label' => __( 'Negre', 'fcsd' ),
            'hex'   => '#000000',
        ],
        'white' => [
            'label' => __( 'Blanc', 'fcsd' ),
            'hex'   => '#ffffff',
        ],
        'red' => [
            'label' => __( 'Vermell', 'fcsd' ),
            'hex'   => '#ff1414',
        ],
        'blue' => [
            'label' => __( 'Blau', 'fcsd' ),
            'hex'   => '#26b79f',
        ],
        'green' => [
            'label' => __( 'Verd', 'fcsd' ),
            'hex'   => '#008000',
        ],
        'yellow' => [
            'label' => __( 'Groc', 'fcsd' ),
            'hex'   => '#eae720',
        ],
        'purple' => [
            'label' => __( 'Porpra', 'fcsd' ),
            'hex'   => '#800080',
        ],
        'orange' => [
            'label' => __( 'Taronja', 'fcsd' ),
            'hex'   => '#ffa500',
        ],
    ];
}
