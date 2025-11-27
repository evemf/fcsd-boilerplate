<?php
/**
 * FCSD Orders & Favorites
 *
 * - Custom post type "Comandes" (fcsd_order).
 * - Helpers per crear comandes des del checkout.
 * - Reducció d'stock.
 * - Històric de comandes per usuari.
 * - Sistema de productes favorits per usuari.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Order CPT: fcsd_order.
 */
add_action( 'init', 'fcsd_register_order_cpt' );
function fcsd_register_order_cpt() {

    $labels = array(
        'name'               => __( 'Comandes', 'fcsd' ),
        'singular_name'      => __( 'Comanda', 'fcsd' ),
        'menu_name'          => __( 'Comandes', 'fcsd' ),
        'add_new'            => __( 'Afegir nova', 'fcsd' ),
        'add_new_item'       => __( 'Afegir nova comanda', 'fcsd' ),
        'edit_item'          => __( 'Editar comanda', 'fcsd' ),
        'new_item'           => __( 'Nova comanda', 'fcsd' ),
        'view_item'          => __( 'Veure comanda', 'fcsd' ),
        'search_items'       => __( 'Cercar comandes', 'fcsd' ),
        'not_found'          => __( 'No s\'han trobat comandes.', 'fcsd' ),
        'not_found_in_trash' => __( 'No hi ha comandes a la paperera.', 'fcsd' ),
    );

    register_post_type(
        'fcsd_order',
        array(
            'labels'       => $labels,
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => true,
            'supports'     => array( 'title' ),
            'has_archive'  => false,
        )
    );
}

/**
 * Possible order statuses.
 */
function fcsd_get_order_statuses() {
    return array(
        'pending'   => __( 'Pendent de pagament', 'fcsd' ),
        'paid'      => __( 'Pagat', 'fcsd' ),
        'shipped'   => __( 'Enviat', 'fcsd' ),
        'completed' => __( 'Completat', 'fcsd' ),
        'cancelled' => __( 'Cancel·lat', 'fcsd' ),
    );
}

/**
 * Get human label for order status.
 */
function fcsd_get_order_status_label( $status ) {
    $statuses = fcsd_get_order_statuses();
    return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
}

/**
 * Admin: columns for fcsd_order list.
 */
add_filter( 'manage_fcsd_order_posts_columns', 'fcsd_order_admin_columns' );
function fcsd_order_admin_columns( $columns ) {
    $new_columns = array();

    foreach ( $columns as $key => $label ) {
        if ( 'date' === $key ) {
            $new_columns['order_email']    = __( 'Email', 'fcsd' );
            $new_columns['order_total']    = __( 'Total', 'fcsd' );
            $new_columns['order_status']   = __( 'Estat', 'fcsd' );
            $new_columns['order_tracking'] = __( 'Tracking', 'fcsd' );
        }

        $new_columns[ $key ] = $label;
    }

    return $new_columns;
}

add_action( 'manage_fcsd_order_posts_custom_column', 'fcsd_order_admin_columns_content', 10, 2 );
function fcsd_order_admin_columns_content( $column, $post_id ) {
    switch ( $column ) {
        case 'order_email':
            $email = get_post_meta( $post_id, '_fcsd_order_email', true );
            echo esc_html( $email );
            break;

        case 'order_total':
            $total = get_post_meta( $post_id, '_fcsd_order_total', true );
            echo esc_html( number_format_i18n( (float) $total, 2 ) ) . ' €';
            break;

        case 'order_status':
            $status = get_post_meta( $post_id, '_fcsd_order_status', true );
            echo esc_html( fcsd_get_order_status_label( $status ) );
            break;

        case 'order_tracking':
            $tracking = get_post_meta( $post_id, '_fcsd_order_tracking', true );
            echo esc_html( $tracking );
            break;
    }
}

/**
 * Order meta box: details, status & tracking.
 */
add_action( 'add_meta_boxes', 'fcsd_add_order_meta_box' );
function fcsd_add_order_meta_box() {
    add_meta_box(
        'fcsd_order_details',
        __( 'Detalls de la comanda', 'fcsd' ),
        'fcsd_render_order_meta_box',
        'fcsd_order',
        'normal',
        'high'
    );
}

function fcsd_render_order_meta_box( $post ) {

    wp_nonce_field( 'fcsd_save_order', 'fcsd_order_nonce' );

    $email    = get_post_meta( $post->ID, '_fcsd_order_email', true );
    $name     = get_post_meta( $post->ID, '_fcsd_order_name', true );
    $notes    = get_post_meta( $post->ID, '_fcsd_order_notes', true );
    $total    = get_post_meta( $post->ID, '_fcsd_order_total', true );
    $status   = get_post_meta( $post->ID, '_fcsd_order_status', true );
    $tracking = get_post_meta( $post->ID, '_fcsd_order_tracking', true );
    $items    = get_post_meta( $post->ID, '_fcsd_order_items', true );

    if ( ! is_array( $items ) ) {
        $items = array();
    }

    ?>
    <table class="form-table">
        <tr>
            <th><label for="fcsd_order_email"><?php esc_html_e( 'Email', 'fcsd' ); ?></label></th>
            <td><input type="email" name="fcsd_order_email" id="fcsd_order_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="fcsd_order_name"><?php esc_html_e( 'Nom', 'fcsd' ); ?></label></th>
            <td><input type="text" name="fcsd_order_name" id="fcsd_order_name" value="<?php echo esc_attr( $name ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="fcsd_order_notes"><?php esc_html_e( 'Notes', 'fcsd' ); ?></label></th>
            <td>
                <textarea name="fcsd_order_notes" id="fcsd_order_notes" rows="4" class="large-text"><?php echo esc_textarea( $notes ); ?></textarea>
            </td>
        </tr>
        <tr>
            <th><label for="fcsd_order_total"><?php esc_html_e( 'Total', 'fcsd' ); ?></label></th>
            <td><input type="number" step="0.01" name="fcsd_order_total" id="fcsd_order_total" value="<?php echo esc_attr( $total ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="fcsd_order_status"><?php esc_html_e( 'Estat', 'fcsd' ); ?></label></th>
            <td>
                <select name="fcsd_order_status" id="fcsd_order_status">
                    <?php foreach ( fcsd_get_order_statuses() as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="fcsd_order_tracking"><?php esc_html_e( 'Tracking', 'fcsd' ); ?></label></th>
            <td><input type="text" name="fcsd_order_tracking" id="fcsd_order_tracking" value="<?php echo esc_attr( $tracking ); ?>" class="regular-text" /></td>
        </tr>
    </table>

    <h3><?php esc_html_e( 'Productes de la comanda', 'fcsd' ); ?></h3>

    <?php if ( ! empty( $items ) ) : ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Producte', 'fcsd' ); ?></th>
                    <th><?php esc_html_e( 'Quantitat', 'fcsd' ); ?></th>
                    <th><?php esc_html_e( 'Preu unitari', 'fcsd' ); ?></th>
                    <th><?php esc_html_e( 'Subtotal', 'fcsd' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $items as $item ) : ?>
                    <?php
                    $product_id = isset( $item['product_id'] ) ? (int) $item['product_id'] : 0;
                    $quantity   = isset( $item['quantity'] ) ? (int) $item['quantity'] : 0;
                    $price      = isset( $item['price'] ) ? (float) $item['price'] : 0;
                    $product    = $product_id ? get_post( $product_id ) : null;
                    ?>
                    <tr>
                        <td>
                            <?php if ( $product ) : ?>
                                <a href="<?php echo esc_url( get_edit_post_link( $product_id ) ); ?>">
                                    <?php echo esc_html( get_the_title( $product_id ) ); ?>
                                </a>
                            <?php else : ?>
                                <?php esc_html_e( '(Producte eliminat)', 'fcsd' ); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( $quantity ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( $price, 2 ) ); ?> €</td>
                        <td><?php echo esc_html( number_format_i18n( $price * $quantity, 2 ) ); ?> €</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php else : ?>
        <p><?php esc_html_e( 'No hi ha productes associats a aquesta comanda.', 'fcsd' ); ?></p>
    <?php endif; ?>
    <?php
}

/**
 * Save order meta from meta box.
 */
add_action( 'save_post_fcsd_order', 'fcsd_save_order_meta', 10, 2 );
function fcsd_save_order_meta( $post_id, $post ) {

    if ( ! isset( $_POST['fcsd_order_nonce'] ) || ! wp_verify_nonce( $_POST['fcsd_order_nonce'], 'fcsd_save_order' ) ) {
        return;
    }

    if ( 'fcsd_order' !== $post->post_type ) {
        return;
    }

    $email    = isset( $_POST['fcsd_order_email'] ) ? sanitize_email( wp_unslash( $_POST['fcsd_order_email'] ) ) : '';
    $name     = isset( $_POST['fcsd_order_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fcsd_order_name'] ) ) : '';
    $notes    = isset( $_POST['fcsd_order_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fcsd_order_notes'] ) ) : '';
    $total    = isset( $_POST['fcsd_order_total'] ) ? (float) $_POST['fcsd_order_total'] : 0;
    $status   = isset( $_POST['fcsd_order_status'] ) ? sanitize_text_field( wp_unslash( $_POST['fcsd_order_status'] ) ) : 'pending';
    $tracking = isset( $_POST['fcsd_order_tracking'] ) ? sanitize_text_field( wp_unslash( $_POST['fcsd_order_tracking'] ) ) : '';

    update_post_meta( $post_id, '_fcsd_order_email', $email );
    update_post_meta( $post_id, '_fcsd_order_name', $name );
    update_post_meta( $post_id, '_fcsd_order_notes', $notes );
    update_post_meta( $post_id, '_fcsd_order_total', $total );
    update_post_meta( $post_id, '_fcsd_order_status', $status );
    update_post_meta( $post_id, '_fcsd_order_tracking', $tracking );
}

/**
 * Create an order from current cart and checkout data.
 *
 * @param array $data {
 *   @type string $email
 *   @type string $name
 *   @type string $notes
 * }
 *
 * @return int|WP_Error Order ID or error.
 */
function fcsd_create_order_from_cart( $data ) {

    $defaults = array(
        'email' => '',
        'name'  => '',
        'notes' => '',
    );
    $data     = wp_parse_args( $data, $defaults );

    if ( empty( $data['email'] ) || ! is_email( $data['email'] ) ) {
        return new WP_Error( 'invalid_email', __( "L'email no és vàlid.", 'fcsd' ) );
    }

    if ( ! function_exists( 'fcsd_get_cart_summary' ) ) {
        return new WP_Error( 'no_cart_function', __( 'No s\'ha trobat la funció de cistella.', 'fcsd' ) );
    }

    $cart = fcsd_get_cart_summary();

    if ( empty( $cart['items'] ) ) {
        return new WP_Error( 'empty_cart', __( 'La cistella està buida.', 'fcsd' ) );
    }

    $user_id = get_current_user_id();
    $total   = isset( $cart['total'] ) ? (float) $cart['total'] : 0;

    // Aplicar descomptes de l'usuari (si la classe de descomptes existeix).
    if ( class_exists( 'fcsd_Shop_Discounts' ) ) {
        $total = fcsd_Shop_Discounts::apply_user_discounts( $total );
    }

    // Crear comanda.
    $order_id = wp_insert_post(
        array(
            'post_type'   => 'fcsd_order',
            'post_status' => 'publish',
            'post_title'  => sprintf(
                __( 'Comanda %s - %s', 'fcsd' ),
                date_i18n( 'Ymd-His' ),
                $data['email']
            ),
        )
    );

    if ( is_wp_error( $order_id ) ) {
        return $order_id;
    }

    // Guardar meta bàsica.
    update_post_meta( $order_id, '_fcsd_order_user_id', $user_id );
    update_post_meta( $order_id, '_fcsd_order_email', sanitize_email( $data['email'] ) );
    update_post_meta( $order_id, '_fcsd_order_name', sanitize_text_field( $data['name'] ) );
    update_post_meta( $order_id, '_fcsd_order_notes', sanitize_textarea_field( $data['notes'] ) );
    update_post_meta( $order_id, '_fcsd_order_total', $total );
    update_post_meta( $order_id, '_fcsd_order_status', 'pending' ); // Per ara, pendent de pagament.
    update_post_meta( $order_id, '_fcsd_order_items', $cart['items'] );

    // Reduir stock.
    fcsd_reduce_stock_for_items( $cart['items'] );

    // Buidar cistella.
    if ( class_exists( 'fcsd_Shop_Cart' ) && method_exists( 'fcsd_Shop_Cart', 'clear_cart' ) ) {
        fcsd_Shop_Cart::clear_cart();
    }

    /**
     * Hook: after an order is created.
     */
    do_action( 'fcsd_order_created', $order_id, $data, $cart );

    return $order_id;
}

/**
 * Reduce stock for items.
 *
 * @param array $items
 */
function fcsd_reduce_stock_for_items( $items ) {
    if ( empty( $items ) || ! is_array( $items ) ) {
        return;
    }

    foreach ( $items as $item ) {
        $product_id = isset( $item['product_id'] ) ? (int) $item['product_id'] : 0;
        $quantity   = isset( $item['quantity'] ) ? (int) $item['quantity'] : 0;

        if ( ! $product_id || ! $quantity ) {
            continue;
        }

        // Usar la misma meta que en el resto del tema
        $stock = (int) get_post_meta( $product_id, '_fcsd_stock', true );

        if ( $stock <= 0 ) {
            continue;
        }

        $new_stock = max( 0, $stock - $quantity );

        update_post_meta( $product_id, '_fcsd_stock', $new_stock );
    }
}


/**
 * Get user orders (WP_Query) by meta user id.
 */
function fcsd_get_user_orders_query( $user_id, $args = array() ) {

    $base = array(
        'post_type'      => 'fcsd_order',
        'posts_per_page' => 20,
        'post_status'    => 'publish',
        'meta_key'       => '_fcsd_order_user_id',
        'meta_value'     => (int) $user_id,
    );

    $query_args = wp_parse_args( $args, $base );

    return new WP_Query( $query_args );
}

/**
 * Favorites system for users.
 */
function fcsd_get_user_favorites( $user_id = 0 ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }

    if ( ! $user_id ) {
        return array();
    }

    $favorites = get_user_meta( $user_id, '_fcsd_favorites', true );

    if ( ! is_array( $favorites ) ) {
        $favorites = array();
    }

    return $favorites;
}

function fcsd_toggle_favorite_product( $product_id, $user_id = 0 ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }

    if ( ! $user_id || ! $product_id ) {
        return;
    }

    $favorites = fcsd_get_user_favorites( $user_id );

    if ( in_array( $product_id, $favorites, true ) ) {
        $favorites = array_diff( $favorites, array( $product_id ) );
    } else {
        $favorites[] = $product_id;
    }

    update_user_meta( $user_id, '_fcsd_favorites', array_values( $favorites ) );
}

/**
 * Shortcode to display user favorites.
 */
add_shortcode( 'fcsd_user_favorites', 'fcsd_user_favorites_shortcode' );
function fcsd_user_favorites_shortcode() {

    if ( ! is_user_logged_in() ) {
        return '<p>' . esc_html__( 'Has d\'iniciar sessió per veure els teus productes favorits.', 'fcsd' ) . '</p>';
    }

    $user_id   = get_current_user_id();
    $favorites = fcsd_get_user_favorites( $user_id );

    if ( empty( $favorites ) ) {
        return '<p>' . esc_html__( 'No tens productes favorits.', 'fcsd' ) . '</p>';
    }

    ob_start();

    echo '<ul class="list-group mb-4">';
    foreach ( $favorites as $product_id ) {
        $title = get_the_title( $product_id );
        $url   = get_permalink( $product_id );

        if ( ! $title || ! $url ) {
            continue;
        }

        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
        echo '<a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>';
        echo '</li>';
    }
    echo '</ul>';

    return ob_get_clean();
}
