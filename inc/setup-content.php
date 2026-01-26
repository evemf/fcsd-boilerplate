<?php
/**
 * (Deprecated) Legacy setup routine.
 *
 * This file was part of an earlier approach that created pages/menus on
 * theme activation.
 *
 * The theme now uses `inc/theme-activate.php` + `inc/slugs.php` and the
 * built-in FCSD i18n layer (no plugins) to:
 * - create a single canonical page per section (Catalan)
 * - attach translated slugs/titles via postmeta
 * - route /es/... and /en/... transparently.
 *
 * Kept here only to preserve history; it is intentionally NOT hooked.
 *
 * These pages are structural: the site must work even without final content.
 * We keep content empty and attach system templates where needed.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function fcsd_create_initial_pages_and_menus() {

    // Pàgines bàsiques del lloc (slug => args).
    $pages = array(
        // Seccions principals (navegació principal)
        'qui-som' => array(
            'title'   => 'Qui som',
            'content' => '',
        ),
        'serveis' => array(
            'title'   => 'Serveis',
            'content' => '',
        ),
        'formacions-i-esdeveniments' => array(
            'title'   => 'Formacions i esdeveniments',
            'content' => '',
        ),
        'botiga' => array(
            'title'   => 'Botiga',
            'content' => '',
        ),
        'transparencia' => array(
            'title'   => 'Transparència',
            'content' => '',
        ),
        'actualitat' => array(
            'title'   => 'Actualitat',
            'content' => '',
        ),
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
    // Menú de franja superior (Topbar) (si no existe)
    // -------------------------------------------------------------------------
    if ( ! wp_get_nav_menu_object( 'Topbar' ) ) {

        $menu_id = wp_create_nav_menu( 'Topbar' );

        $menu_items = array(
            'sobre-nosaltres' => 'Sobre nosaltres',
            'calendar-actes'  => 'Calendari',
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


// -------------------------------------------------------------------------
// Menú principal (Primary) (si no existe)
// -------------------------------------------------------------------------
if ( ! wp_get_nav_menu_object( 'Primary' ) ) {

    $primary_id = wp_create_nav_menu( 'Primary' );

    // Ítems base (CA). Les versions ES/EN es mostren via inc/i18n-menu.php.
    $primary_items = array(
        'about'        => array('ca' => 'Qui som', 'es' => 'Quiénes somos', 'en' => 'About'),
        'services'     => array('ca' => 'Serveis', 'es' => 'Servicios', 'en' => 'Services'),
        'events'       => array('ca' => 'Formacions i esdeveniments', 'es' => 'Formaciones y eventos', 'en' => 'Training & events'),
        'shop'         => array('ca' => 'Botiga', 'es' => 'Tienda', 'en' => 'Shop'),
        'transparency' => array('ca' => 'Transparència', 'es' => 'Transparencia', 'en' => 'Transparency'),
        'news'         => array('ca' => 'Actualitat', 'es' => 'Actualidad', 'en' => 'News'),
        'contact'      => array('ca' => 'Contacte', 'es' => 'Contacto', 'en' => 'Contact'),
    );

    foreach ( $primary_items as $slug_key => $titles ) {
        $slug = function_exists('fcsd_slug') ? fcsd_slug( $slug_key, 'ca' ) : $slug_key;
        $page = get_page_by_path( $slug );
        if ( $page && isset($page->ID) ) {
            $item_id = wp_update_nav_menu_item(
                $primary_id,
                0,
                array(
                    'menu-item-title'     => $titles['ca'],
                    'menu-item-object'    => 'page',
                    'menu-item-object-id' => (int) $page->ID,
                    'menu-item-type'      => 'post_type',
                    'menu-item-status'    => 'publish',
                )
            );

            // Desa títols traduïbles (metes utilitzades per inc/i18n-menu.php)
            if ( ! is_wp_error($item_id) ) {
                update_post_meta( (int) $item_id, '_fcsd_i18n_title_es', $titles['es'] );
                update_post_meta( (int) $item_id, '_fcsd_i18n_title_en', $titles['en'] );
            }
        }
    }

    $locations              = get_theme_mod( 'nav_menu_locations', array() );
    $locations['primary']   = $primary_id;
    set_theme_mod( 'nav_menu_locations', $locations );
}
    }

    // -------------------------------------------------------------------------
    // Menú principal (si no existe)
    // -------------------------------------------------------------------------
    if ( ! wp_get_nav_menu_object( 'Principal' ) ) {

        $menu_id = wp_create_nav_menu( 'Principal' );

        $menu_items = array(
            'qui-som'                   => 'Qui som',
            'serveis'                   => 'Serveis',
            'formacions-i-esdeveniments'=> 'Formacions i esdeveniments',
            'botiga'                    => 'Botiga',
            'transparencia'             => 'Transparència',
            'actualitat'                => 'Actualitat',
            'contacte'                  => 'Contacte',
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

        // Assigna el menú nou a la ubicació 'primary'
        $locations            = get_theme_mod( 'nav_menu_locations', array() );
        $locations['topbar'] = $menu_id;
        set_theme_mod( 'nav_menu_locations', $locations );
    }
}

// Crea estructures bàsiques (pàgines i menús) en activar el tema.
add_action( 'after_switch_theme', 'fcsd_create_initial_pages_and_menus' );
