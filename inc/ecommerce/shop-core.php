<?php
// inc/ecommerce/shop-core.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Carga de clases y helpers de la tienda
 * (estos archivos deben existir en inc/ecommerce/)
 */
require get_template_directory() . '/inc/ecommerce/class-shop-db.php';
require get_template_directory() . '/inc/ecommerce/class-shop-cart.php';
require get_template_directory() . '/inc/ecommerce/class-shop-orders.php';
require get_template_directory() . '/inc/ecommerce/class-shop-account.php';
require get_template_directory() . '/inc/ecommerce/class-shop-discounts.php';
require get_template_directory() . '/inc/ecommerce/template-tags-shop.php';

/**
 * Registrar Custom Post Type: product
 */
function fcsd_register_product_cpt() {
    $labels = [
        'name'               => __( 'Productes', 'fcsd' ),
        'singular_name'      => __( 'Producte', 'fcsd' ),
        'add_new'            => __( 'Añadir nuevo', 'fcsd' ),
        'add_new_item'       => __( 'Añadir nuevo producto', 'fcsd' ),
        'edit_item'          => __( 'Editar producto', 'fcsd' ),
        'new_item'           => __( 'Nuevo producto', 'fcsd' ),
        'view_item'          => __( 'Ver producto', 'fcsd' ),
        'search_items'       => __( 'Buscar productos', 'fcsd' ),
        'not_found'          => __( 'No se han encontrado productos', 'fcsd' ),
        'not_found_in_trash' => __( 'No hay productos en la papelera', 'fcsd' ),
    ];

    $args = [
        'label'         => __( 'Productos', 'fcsd' ),
        'labels'        => $labels,
        'public'        => true,
        'has_archive'   => true,
        'rewrite'       => [ 'slug' => 'tienda' ],
        'supports'      => [ 'title', 'editor', 'thumbnail' ],
        'show_in_rest'  => true,
        'menu_icon'     => 'dashicons-cart',
    ];

    register_post_type( 'product', $args );
}
add_action( 'init', 'fcsd_register_product_cpt' );

/**
 * Taxonomía: product_cat
 */
function fcsd_register_product_taxonomy() {
    $labels = [
        'name'          => __( 'Categorías de producto', 'fcsd' ),
        'singular_name' => __( 'Categoría de producto', 'fcsd' ),
        'search_items'  => __( 'Buscar categorías', 'fcsd' ),
        'all_items'     => __( 'Todas las categorías', 'fcsd' ),
        'edit_item'     => __( 'Editar categoría', 'fcsd' ),
        'update_item'   => __( 'Actualizar categoría', 'fcsd' ),
        'add_new_item'  => __( 'Añadir nueva categoría', 'fcsd' ),
        'new_item_name' => __( 'Nuevo nombre de categoría', 'fcsd' ),
    ];

    register_taxonomy( 'product_cat', 'product', [
        'labels'       => $labels,
        'public'       => true,
        'hierarchical' => true,
        'rewrite'      => [ 'slug' => 'categoria-producto' ],
        'show_in_rest' => true,
    ] );
}
add_action( 'init', 'fcsd_register_product_taxonomy' );

/**
 * Crear tablas de pedidos al activar el tema
 */
function fcsd_shop_on_theme_switch() {
    fcsd_Shop_DB::create_tables();
}
add_action( 'after_switch_theme', 'fcsd_shop_on_theme_switch' );

/**
 * Encolar assets de la tienda
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
        'ajax_url'        => admin_url( 'admin-ajax.php' ),
        'nonce'           => wp_create_nonce( 'fcsd_shop_nonce' ),
        'adding_text'     => __( 'Afegint...', 'fcsd' ),
        'added_text'      => __( 'Producte afegit!', 'fcsd' ),
        'default_text'    => __( 'Afegir a la cistella', 'fcsd' ),
        'error_text'      => __( 'Error', 'fcsd' ),
    ] );
}
add_action( 'wp_enqueue_scripts', 'fcsd_shop_assets' );

/**
 * Iniciar sesión PHP para carrito de invitados
 */
add_action( 'init', function () {
    if ( ! session_id() ) {
        session_start();
    }
} );

/**
 * Handler para "Añadir al carrito" (formularios POST)
 *
 * Colores y tallas:
 * - Configurados vía metas:
 *   - _fcsd_product_colors => array de colores (hex)
 *   - _fcsd_product_sizes  => array de tallas
 * - Si no llegan en POST, se usa el primer valor disponible como por defecto.
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

    // Verificar que el producto existe
    if ( get_post_type( $product_id ) !== 'product' ) {
        wp_die( __( 'Producto no válido.', 'fcsd' ) );
    }

    // Metas de colores y tallas (como en single-product.php)
    $colors_meta = get_post_meta( $product_id, '_fcsd_product_colors', true );
    $sizes_meta  = get_post_meta( $product_id, '_fcsd_product_sizes', true );

    $color_options = is_array( $colors_meta ) ? $colors_meta : [];
    $size_options  = is_array( $sizes_meta )  ? $sizes_meta  : [];

    $attributes = [];

    // 1) Valores enviados explícitamente desde el formulario
    $color = isset( $_POST['fcsd_color'] ) ? sanitize_text_field( wp_unslash( $_POST['fcsd_color'] ) ) : '';
    $size  = isset( $_POST['fcsd_size'] )  ? sanitize_text_field( wp_unslash( $_POST['fcsd_size'] ) )  : '';

    // 2) Si no llega color y hay opciones, usamos la primera (preseleccionada por defecto)
    if ( $color === '' && ! empty( $color_options ) ) {
        $first_color = reset( $color_options );
        if ( ! empty( $first_color ) ) {
            $color = $first_color;
        }
    }

    // 3) Si no llega talla y hay opciones, usamos la primera
    if ( $size === '' && ! empty( $size_options ) ) {
        $first_size = reset( $size_options );
        if ( ! empty( $first_size ) ) {
            $size = $first_size;
        }
    }

    // 4) Filtros por si quieres sobreescribir desde un plugin/hook
    $color = apply_filters( 'fcsd_default_color_for_product', $color, $product_id );
    $size  = apply_filters( 'fcsd_default_size_for_product',  $size,  $product_id );

    // 5) Guardar en atributos si existen
    if ( $color !== '' ) {
        $attributes['color'] = $color;
    }

    if ( $size !== '' ) {
        $attributes['size'] = $size;
    }

    // Añadir al carrito
    fcsd_Shop_Cart::add_to_cart( $product_id, $quantity, $attributes );

    // Determinar redirección:
    // - single-product manda redirect_to = carrito
    // - archivo/listado NO manda redirect_to, así que volvemos al referer (la tienda)
    if ( ! empty( $_POST['redirect_to'] ) ) {
        $redirect = esc_url_raw( wp_unslash( $_POST['redirect_to'] ) );
    } else {
        $redirect = wp_get_referer() ? wp_get_referer() : get_permalink( get_page_by_path( 'carrito' ) );
    }

    // Añadir parámetro de éxito para poder mostrar mensaje si queremos
    $redirect = add_query_arg( 'added_to_cart', $product_id, $redirect );

    wp_safe_redirect( $redirect );
    exit;
}
add_action( 'template_redirect', 'fcsd_handle_add_to_cart' );

/**
 * Handler para actualizar cantidad en el carrito
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
 * Handler para eliminar del carrito
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
 * Checkout (clase Orders)
 */
add_action( 'template_redirect', [ 'fcsd_Shop_Orders', 'handle_checkout' ] );

/**
 * Repetir pedido (clase Account)
 */
add_action( 'template_redirect', function () {
    if ( isset( $_GET['repeat_order'] ) ) {
        fcsd_Shop_Account::repeat_order( absint( $_GET['repeat_order'] ) );
    }
} );

/**
 * AJAX: Añadir producto al carrito (desde archive/listado)
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
    
    // Verificar que el producto existe
    if ( get_post_type( $product_id ) !== 'product' ) {
        wp_send_json_error( array( 'message' => __( 'Producte no vàlid', 'fcsd' ) ) );
    }
    
    // Obtener opciones de colores y tallas
    $colors_meta = get_post_meta( $product_id, '_fcsd_product_colors', true );
    $sizes_meta  = get_post_meta( $product_id, '_fcsd_product_sizes', true );
    
    $color_options = is_array( $colors_meta ) ? $colors_meta : [];
    $size_options  = is_array( $sizes_meta )  ? $sizes_meta  : [];
    
    // Si no se envió color pero hay opciones, usar el primero
    if ( empty( $color ) && ! empty( $color_options ) ) {
        $color = reset( $color_options );
    }
    
    // Si no se envió talla pero hay opciones, usar la primera
    if ( empty( $size ) && ! empty( $size_options ) ) {
        $size = reset( $size_options );
    }
    
    // Construir atributos
    $attributes = [];
    if ( ! empty( $color ) ) {
        $attributes['color'] = $color;
    }
    if ( ! empty( $size ) ) {
        $attributes['size'] = $size;
    }
    
    // Añadir al carrito
    fcsd_Shop_Cart::add_to_cart( $product_id, $quantity, $attributes );
    
    wp_send_json_success( array(
        'message'    => __( 'Producte afegit a la cistella', 'fcsd' ),
        'cart_count' => fcsd_Shop_Cart::get_cart_count()
    ) );
}
add_action( 'wp_ajax_fcsd_add_to_cart', 'fcsd_ajax_add_to_cart' );
add_action( 'wp_ajax_nopriv_fcsd_add_to_cart', 'fcsd_ajax_add_to_cart' );

/**
 * AJAX: Actualizar cantidad de producto en el carrito
 */
function fcsd_ajax_update_cart_item() {
    check_ajax_referer( 'fcsd_shop_nonce', 'nonce' );
    
    if ( ! isset( $_POST['cart_key'] ) || ! isset( $_POST['quantity'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Datos inválidos', 'fcsd' ) ) );
    }
    
    $cart_key = sanitize_text_field( wp_unslash( $_POST['cart_key'] ) );
    $quantity = absint( $_POST['quantity'] );
    
    $updated = fcsd_Shop_Cart::update_cart_item( $cart_key, $quantity );
    
    if ( $updated ) {
        wp_send_json_success( array(
            'message'    => __( 'Carrito actualizado', 'fcsd' ),
            'cart_count' => fcsd_Shop_Cart::get_cart_count()
        ) );
    } else {
        wp_send_json_error( array( 'message' => __( 'Error al actualizar el carrito', 'fcsd' ) ) );
    }
}
add_action( 'wp_ajax_fcsd_update_cart_item', 'fcsd_ajax_update_cart_item' );
add_action( 'wp_ajax_nopriv_fcsd_update_cart_item', 'fcsd_ajax_update_cart_item' );

/**
 * AJAX: Eliminar producto del carrito
 */
function fcsd_ajax_remove_from_cart() {
    check_ajax_referer( 'fcsd_shop_nonce', 'nonce' );
    
    if ( ! isset( $_POST['cart_key'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Datos inválidos', 'fcsd' ) ) );
    }
    
    $cart_key = sanitize_text_field( wp_unslash( $_POST['cart_key'] ) );
    
    $removed = fcsd_Shop_Cart::remove_from_cart( $cart_key );
    
    if ( $removed ) {
        wp_send_json_success( array(
            'message'    => __( 'Producto eliminado del carrito', 'fcsd' ),
            'cart_count' => fcsd_Shop_Cart::get_cart_count()
        ) );
    } else {
        wp_send_json_error( array( 'message' => __( 'Error al eliminar del carrito', 'fcsd' ) ) );
    }
}
add_action( 'wp_ajax_fcsd_remove_from_cart', 'fcsd_ajax_remove_from_cart' );
add_action( 'wp_ajax_nopriv_fcsd_remove_from_cart', 'fcsd_ajax_remove_from_cart' );

/**
 * AJAX: Obtener contenido del carrito actualizado
 */
function fcsd_ajax_get_cart_content() {
    check_ajax_referer( 'fcsd_shop_nonce', 'nonce' );
    
    ob_start();
    
    $cart = fcsd_get_cart_summary();
    ?>
    
    <h1><?php esc_html_e( 'Carrito', 'fcsd' ); ?></h1>

    <?php get_template_part( 'template-parts/shop/cart', 'table', [ 'cart' => $cart ] ); ?>

    <?php if ( ! empty( $cart['items'] ) ) : ?>
        <p class="cart-total">
            <?php esc_html_e( 'Total:', 'fcsd' ); ?>
            <?php echo esc_html( number_format_i18n( $cart['total'], 2 ) ); ?> €
        </p>

        <?php if ( is_user_logged_in() ) : ?>
            <p class="cart-discount-info">
                <?php esc_html_e( 'Se han aplicado descuentos de usuario registrado (si corresponden).', 'fcsd' ); ?>
            </p>
        <?php else : ?>
            <p class="cart-discount-info">
                <?php esc_html_e( 'Regístrate o inicia sesión para obtener descuentos exclusivos.', 'fcsd' ); ?>
            </p>
        <?php endif; ?>

        <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'checkout' ) ) ); ?>" class="btn btn-primary">
            <?php esc_html_e( 'Ir a pagar', 'fcsd' ); ?>
        </a>
    <?php endif; ?>
    
    <?php
    $html = ob_get_clean();
    
    wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_fcsd_get_cart_content', 'fcsd_ajax_get_cart_content' );
add_action( 'wp_ajax_nopriv_fcsd_get_cart_content', 'fcsd_ajax_get_cart_content' );

/**
 * Filtros del archivo de productos (tienda)
 * - Categoria (product_cat)
 * - Color (meta _fcsd_product_colors)
 * - Precio mínimo/máximo (meta _fcsd_price_regular)
 */
function fcsd_apply_shop_filters( $query ) {

    // Sólo front-end, main query, archivo de productos
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    if ( ! $query->is_post_type_archive( 'product' ) && $query->get( 'post_type' ) !== 'product' ) {
        return;
    }

    $meta_query = (array) $query->get( 'meta_query' );
    $tax_query  = (array) $query->get( 'tax_query' );

    // -------------------------------
    // Categoría de producto (product_cat)
    // -------------------------------
    if ( ! empty( $_GET['product_cat'] ) ) {
        $cat_id = (int) $_GET['product_cat'];
        if ( $cat_id > 0 ) {
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $cat_id,
            ];
        }
    }

    // -------------------------------
    // Color (array de hex en _fcsd_product_colors)
    // Se guardan como array serializado, usamos LIKE
    // -------------------------------
    if ( ! empty( $_GET['color'] ) ) {
        $color = sanitize_text_field( wp_unslash( $_GET['color'] ) );

        // Normalizamos: aseguramos # delante
        if ( $color[0] !== '#' ) {
            $color = '#' . $color;
        }

        $meta_query[] = [
            'key'     => '_fcsd_product_colors',
            'value'   => $color,
            'compare' => 'LIKE',
        ];
    }

    // -------------------------------
    // Rango de precio (usamos _fcsd_price_regular)
    // -------------------------------
    $price_min = isset( $_GET['price_min'] ) && $_GET['price_min'] !== ''
        ? (float) $_GET['price_min']
        : null;

    $price_max = isset( $_GET['price_max'] ) && $_GET['price_max'] !== ''
        ? (float) $_GET['price_max']
        : null;

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

    // Opcional: ordenar por precio
    // $query->set( 'meta_key', '_fcsd_price_regular' );
    // $query->set( 'orderby', 'meta_value_num' );
    // $query->set( 'order', 'ASC' );
}
add_action( 'pre_get_posts', 'fcsd_apply_shop_filters' );

/**
 * Colores disponibles para filtros de tienda
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
            'hex'   => '#ff0000',
        ],
        'blue' => [
            'label' => __( 'Blau', 'fcsd' ),
            'hex'   => '#0000ff',
        ],
        'green' => [
            'label' => __( 'Verd', 'fcsd' ),
            'hex'   => '#008000',
        ],
        'yellow' => [
            'label' => __( 'Groc', 'fcsd' ),
            'hex'   => '#ffff00',
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