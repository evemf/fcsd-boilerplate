<?php
/**
 * Create basic pages and topbar menu on theme activation.
 *
 * These pages are structural: the site must work even without final content.
 * We keep content empty and attach system templates where needed.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function fcsd_create_initial_pages_and_menus() {

    // Páginas básicas del sitio (slug => args).
    $pages = array(
        // Topbar / institucionales
        'sobre-nosaltres' => array(
            'title'   => 'Sobre nosaltres',
            'content' => '',
        ),
           'patronat' => array(
            'title'   => 'Patronat',
            'content' => '',
        ),
          'historia' => array(
          'title'   => 'Història',
          'content' => '', 
        ),
        'intranet' => array(
            'title'   => 'Intranet',
            'content' => '',
        ),
        'ofertes' => array(
            'title'   => 'Ofertes',
            'content' => '',
        ),

        // Sistema: login y registro
        'accedir' => array(
            'title'   => 'Accedir',
            'content' => '', // lo pinta page-login.php
        ),
        'registre' => array(
            'title'   => 'Registre',
            'content' => '', // lo pinta page-register.php
        ),

        // Sistema: perfil
        'perfil-usuari' => array(
            'title'   => 'Perfil usuari',
            'content' => '',
        ),
        // Organigrama
        'organigrama' => array(
            'title'   => 'Organigrama',
            'content' => '', 
        ),
        // Ecommerce sistema
        'cistella' => array(
            'title'   => 'Cistella',
            'content' => '', // lo pinta page-cart.php (ya existente)
        ),
        'checkout' => array(
            'title'   => 'Checkout',
            'content' => '', // lo pinta page-checkout.php (ya existente)
        ),

        // Confirmación de registro
        'confirmar-registre' => array(
            'title'   => 'Confirmar registre',
            'content' => '[fcsd_confirm_registration]',
        ),

        // Otras estructurales (si las quieres como base vacía)
        'mi-cuenta' => array(
            'title'   => 'Mi cuenta',
            'content' => '',
        ),
        'pedido-completado' => array(
            'title'   => 'Pedido completado',
            'content' => '',
        ),
         'calendar-actes' => array(
            'title'   => 'Calendari',
            'content' => '',
        ),
        'calendar-work' => array(
            'title'   => 'Calendari Laboral',
            'content' => '',
        ),
           'contacte' => array(
            'title'   => 'Contacte',
            'content' => '',
        ),
    );

    // Plantillas del tema para páginas de sistema.
    // Solo asignamos las que sabemos que existen en el tema.
    $templates = array(
        'accedir'       => 'page-login.php',
        'registre'      => 'page-register.php',
        'perfil-usuari' => 'page-profile.php',
        'cistella'      => 'page-cart.php',
        'checkout'      => 'page-checkout.php',
        'intranet'      => 'page-intranet.php',
        'organigrama'      => 'page-organigrama.php',
        'patronat'      => 'page-patronat.php',
        'historia'      => 'page-historia.php',
        'contacte'      => 'page-contact.php',
        'calendar-work'      => 'calendar-work.php',
        'calendar-actes'      => 'calendar-actes.php',
    );

    foreach ( $pages as $slug => $config ) {

        $existing = get_page_by_path( $slug );

        if ( $existing ) {
            $page_id = $existing->ID;

            // Si quieres asegurar que el contenido estructural no se pierda, actualiza:
            if ( isset( $config['content'] ) && $config['content'] !== '' ) {
                wp_update_post(
                    array(
                        'ID'           => $page_id,
                        'post_content' => $config['content'],
                    )
                );
            }
        } else {
            $page_id = wp_insert_post(
                array(
                    'post_title'   => $config['title'],
                    'post_name'    => $slug,
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_content' => $config['content'],
                )
            );
        }

        if ( ! is_wp_error( $page_id ) && isset( $templates[ $slug ] ) ) {
            update_post_meta( $page_id, '_wp_page_template', $templates[ $slug ] );
        }
    }

    // -------------------------------------------------------------------------
    // Menú topbar (si no existe)
    // -------------------------------------------------------------------------
    if ( ! wp_get_nav_menu_object( 'Topbar' ) ) {

        $menu_id = wp_create_nav_menu( 'Topbar' );

        $menu_items = array(
            'sobre-nosaltres' => 'Sobre nosaltres',
            'calendari'   => 'Calendari',
            'ofertes'         => 'Ofertes',
            'intranet'        => 'Intranet',
        );

        foreach ( $menu_items as $slug => $label ) {
            $page = get_page_by_path( $slug );
            if ( $page ) {
                wp_update_nav_menu_item(
                    $menu_id,
                    0,
                    array(
                        'menu-item-title'     => $label,
                        'menu-item-object'    => 'page',
                        'menu-item-object-id' => $page->ID,
                        'menu-item-type'      => 'post_type',
                        'menu-item-status'    => 'publish',
                    )
                );
            }
        }

        // Asignar el menú nuevo a la ubicación 'topbar'
        $locations           = get_theme_mod( 'nav_menu_locations', array() );
        $locations['topbar'] = $menu_id;
        set_theme_mod( 'nav_menu_locations', $locations );
    }
}
add_action( 'after_switch_theme', 'fcsd_create_initial_pages_and_menus' );
