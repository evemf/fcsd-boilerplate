<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Encola el CSS/JS del formulari de Sinergia només a single d'events.
 */
function fcsd_sinergia_form_enqueue_assets() {
    if ( ! is_singular( 'event' ) ) {
        return;
    }

    $base_uri = FCSD_THEME_URI . '/sinergia-form';

    // CSS del formulari (copiat del plugin)
    wp_enqueue_style(
        'fcsd-sinergia-form',
        $base_uri . '/assets/css/style.css',
        [],
        FCSD_VERSION
    );

    // JS del formulari (copiat del plugin)
    wp_enqueue_script(
        'fcsd-sinergia-form',
        $base_uri . '/assets/js/public-script.js',
        [ 'jquery' ],
        FCSD_VERSION,
        true
    );

    // Les dades que usava el plugin per al JS (la part important és ajax_url)
    wp_localize_script(
        'fcsd-sinergia-form',
        'ajax_object',
        [
            'ajax_url'    => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'sinergiacrm_nonce' ),
            'is_logged_in'=> 'no', // si més endavant gestiones sessió, ho pots canviar
        ]
    );
}
add_action( 'wp_enqueue_scripts', 'fcsd_sinergia_form_enqueue_assets' );

/**
 * Renderitza el formulari de Sinergia per a un event concret.
 *
 * @param string $event_id
 * @param string $assigned_user_id
 *
 * @return string HTML
 */
function fcsd_sinergia_render_form( $event_id = '', $assigned_user_id = '' ) {
    $event_id         = trim( (string) $event_id );
    $assigned_user_id = trim( (string) $assigned_user_id );

    $base_dir = FCSD_THEME_DIR . '/sinergia-form';

    ob_start();

    if ( $event_id ) {
        $template = $base_dir . '/assets/html/form-template-small.php';
    } else {
        $template = $base_dir . '/assets/html/form-template.php';
    }

    if ( file_exists( $template ) ) {
        // Aquestes variables les fa servir el template copiat del plugin
        $event_id         = esc_attr( $event_id );
        $assigned_user_id = esc_attr( $assigned_user_id );

        include $template;
    } else {
        echo '<p>' . esc_html__( 'Formulari temporalment no disponible.', 'fcsd' ) . '</p>';
    }

    return ob_get_clean();
}
