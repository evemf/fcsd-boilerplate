<?php
/**
 * Intranet utilities: roles, CPT and user management.
 *
 * - Columna "Empleado FCSD" en la tabla de usuarios.
 * - AJAX para activar/desactivar el rol "worker" (solo @fcsd.org).
 * - Roles de backend (intranet/news/shop) y CPT para avisos de intranet.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Columna "Empleado FCSD" en Usuarios.
 */
add_filter( 'manage_users_columns', function ( $columns ) {
    $columns['fcsd_employee'] = __( 'Empleado FCSD', 'fcsd' );
    return $columns;
} );

add_filter( 'manage_users_custom_column', function ( $value, $column_name, $user_id ) {

    if ( 'fcsd_employee' !== $column_name ) {
        return $value;
    }

    $user      = get_userdata( $user_id );
    $is_worker = in_array( 'worker', (array) $user->roles, true );
    $checked   = $is_worker ? 'checked="checked"' : '';

    return sprintf(
        '<input type="checkbox" class="fcsd-toggle-worker" data-user="%d" %s />',
        $user_id,
        $checked
    );
}, 10, 3 );

/**
 * JS solo en la pantalla de usuarios.
 */
add_action( 'admin_enqueue_scripts', function ( $hook ) {

    if ( 'users.php' !== $hook ) {
        return;
    }

    wp_enqueue_script(
        'fcsd-admin-users',
        FCSD_THEME_URI . '/assets/js/admin-users.js',
        array( 'jquery' ),
        FCSD_VERSION,
        true
    );

    wp_localize_script(
        'fcsd-admin-users',
        'fcsdUsers',
        array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'fcsd_toggle_worker' ),
        )
    );
} );

/**
 * AJAX: activar/desactivar rol worker desde la lista de usuarios.
 */
add_action( 'wp_ajax_fcsd_toggle_worker', function () {

    if ( ! current_user_can( 'list_users' ) && ! current_user_can( 'promote_users' ) ) {
        wp_send_json_error( __( 'Permisos insuficientes.', 'fcsd' ) );
    }

    check_ajax_referer( 'fcsd_toggle_worker', 'nonce' );

    $user_id   = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;
    $is_worker = ! empty( $_POST['is_worker'] );

    if ( ! $user_id ) {
        wp_send_json_error( __( 'Usuario no válido.', 'fcsd' ) );
    }

    $user  = new WP_User( $user_id );
    $email = $user->user_email;

    // Solo emails @fcsd.org pueden ser "empleados".
    if ( $is_worker && ! preg_match( '/@fcsd\.org$/i', $email ) ) {
        wp_send_json_error( __( 'Solo los emails @fcsd.org pueden ser empleados.', 'fcsd' ) );
    }

    // Aseguramos que el rol worker existe.
    if ( ! get_role( 'worker' ) ) {
        add_role(
            'worker',
            'FCSD Worker',
            array(
                'read' => true,
            )
        );
    }

    if ( $is_worker ) {
        // Añadimos el rol worker sin quitar otros (admin, etc.).
        $user->add_role( 'worker' );
    } else {
        // Quitamos solo el rol worker.
        $user->remove_role( 'worker' );
    }

    wp_send_json_success();
} );

/**
 * Roles de intranet + roles admin específicos.
 */
add_action( 'init', function () {

    // Worker para empleados (por si no se ha creado ya en auth.php).
    if ( ! get_role( 'worker' ) ) {
        add_role(
            'worker',
            'FCSD Worker',
            array(
                'read' => true,
            )
        );
    }

    // Admin de Intranet.
    if ( ! get_role( 'intranet_admin' ) ) {
        add_role(
            'intranet_admin',
            __( 'Administrador Intranet', 'fcsd' ),
            array(
                'read'                                => true,
                'read_intranet_notice'                => true,
                'read_private_intranet_notices'       => true,
                'edit_intranet_notices'               => true,
                'edit_others_intranet_notices'        => true,
                'publish_intranet_notices'            => true,
                'delete_intranet_notices'             => true,
            )
        );
    }

    // Admin de noticias (clona capacidades de editor).
    if ( ! get_role( 'news_admin' ) ) {
        $editor = get_role( 'editor' );
        $caps   = $editor ? $editor->capabilities : array( 'read' => true, 'edit_posts' => true );
        add_role( 'news_admin', __( 'Administrador Noticias', 'fcsd' ), $caps );
    }

    // Admin de tienda (también tipo editor).
    if ( ! get_role( 'shop_admin' ) ) {
        $editor = get_role( 'editor' );
        $caps   = $editor ? $editor->capabilities : array( 'read' => true, 'edit_posts' => true );
        add_role( 'shop_admin', __( 'Administrador Tienda', 'fcsd' ), $caps );
    }

    // El administrador general tiene también capacidades de intranet.
    $admin = get_role( 'administrator' );
    if ( $admin ) {
        $caps = array(
            'read_intranet_notice',
            'read_private_intranet_notices',
            'edit_intranet_notices',
            'edit_others_intranet_notices',
            'publish_intranet_notices',
            'delete_intranet_notices',
        );

        foreach ( $caps as $cap ) {
            $admin->add_cap( $cap );
        }
    }
} );

/**
 * CPT para avisos internos de intranet (tablón).
 */
add_action( 'init', function () {

    $labels = array(
        'name'               => __( 'Avisos intranet', 'fcsd' ),
        'singular_name'      => __( 'Aviso intranet', 'fcsd' ),
        'add_new'            => __( 'Añadir aviso', 'fcsd' ),
        'add_new_item'       => __( 'Añadir nuevo aviso', 'fcsd' ),
        'edit_item'          => __( 'Editar aviso', 'fcsd' ),
        'new_item'           => __( 'Nuevo aviso', 'fcsd' ),
        'view_item'          => __( 'Ver aviso', 'fcsd' ),
        'search_items'       => __( 'Buscar avisos', 'fcsd' ),
        'not_found'          => __( 'No se han encontrado avisos.', 'fcsd' ),
        'menu_name'          => __( 'Intranet', 'fcsd' ),
    );

    register_post_type(
        'intranet_notice',
        array(
            'labels'        => $labels,
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => true,
            'supports'      => array( 'title', 'editor', 'thumbnail' ),
            'has_archive'   => false,
            'capability_type' => array( 'intranet_notice', 'intranet_notices' ),
            'map_meta_cap'  => true,
        )
    );
} );
