<?php
/**
 * Acciones al activar el tema.
 *
 * Objetivo:
 * - NO duplicar plantillas por idioma.
 * - Mantener una única página/cpt “canónica” (idioma por defecto) y añadir:
 *     - slug traducido (meta: _fcsd_i18n_slug_{lang})
 *     - título traducido (meta: _fcsd_i18n_title_{lang})
 *     - contenido traducido (meta: _fcsd_i18n_content_{lang}) si se desea
 *
 * Con esto, /es/<slug-traducido> y /en/<slug-traducido> se resuelven al mismo post
 * y el render se traduce vía filtros (the_title / the_content) ya incluidos en el tema.
 */

defined('ABSPATH') || exit;

/**
 * Devuelve el ID de una página con slug exacto (post_name), o 0 si no existe.
 */
function fcsd_find_page_by_slug(string $slug): int {
    $page = get_page_by_path($slug, OBJECT, 'page');
    return $page instanceof WP_Post ? (int) $page->ID : 0;
}

/**
 * Crea una página si no existe.
 *
 * @param string $slug_ca Slug canónico (ca)
 * @param string $title_ca Título en ca (canónico)
 * @param string $template Opcional. Nombre de template asignable via _wp_page_template
 * @return int ID de la página existente o creada
 */
function fcsd_ensure_page(string $slug_ca, string $title_ca, string $template = ''): int {
    $existing_id = fcsd_find_page_by_slug($slug_ca);
    if ( $existing_id > 0 ) {
        // Asegura template si procede
        if ( $template && get_post_meta($existing_id, '_wp_page_template', true) !== $template ) {
            update_post_meta($existing_id, '_wp_page_template', $template);
        }
        return $existing_id;
    }

    $id = wp_insert_post([
        'post_type'    => 'page',
        'post_status'  => 'publish',
        'post_title'   => $title_ca,
        'post_name'    => $slug_ca,
        'post_content' => '',
    ], true);

    if ( is_wp_error($id) ) {
        return 0;
    }

    if ( $template ) {
        update_post_meta((int)$id, '_wp_page_template', $template);
    }

    return (int) $id;
}

/**
 * Guarda metas i18n de slug/título/contenido para una página.
 */
function fcsd_apply_page_i18n_meta(int $page_id, array $meta): void {
    foreach ( $meta as $k => $v ) {
        if ( $v === null ) continue;
        update_post_meta($page_id, $k, (string) $v);
    }
}

/**
 * Handler principal de activación.
 */
function fcsd_on_theme_activation(): void {
    // En instalaciones limpias, es habitual que WP aún no haya cargado todas las rules.
    // Creamos primero las páginas y luego flush.

    $pages = [
        // key => [template, title_ca, title_es, title_en]
        'about'          => [ '',                  'Qui som',                 'Quiénes somos',            'About' ],
        'patronat'       => [ 'page-patronat.php',  'Patronat',                'Patronato',                'Board of Trustees' ],
        'organigrama'    => [ 'page-organigrama.php','Organigrama',            'Organigrama',              'Organisation chart' ],
        'history'        => [ 'page-historia.php',  'Història',                'Historia',                 'History' ],
        'memories'       => [ '',                  'Memòries',                'Memorias',                 'Annual reports' ],
        'press'          => [ '',                  'Premsa',                  'Prensa',                   'Press' ],
        'volunteering'   => [ '',                  'Voluntariat',             'Voluntariado',             'Volunteering' ],
        'alliances'      => [ '',                  'Aliances',                'Alianzas',                 'Partnerships' ],
        'offers'         => [ '',                  'Ofertes',                 'Ofertas',                  'Offers' ],
        'intranet'       => [ 'page-intranet.php', 'Intranet',                'Intranet',                 'Intranet' ],
        'calendar_actes' => [ 'calendar-actes.php','Calendari',               'Calendario',               'Calendar' ],
        'calendar_work'  => [ 'calendar-work.php', 'Calendari laboral',       'Calendario laboral',       'Work calendar' ],

        // Sistema
        'login'          => [ 'page-login.php',      'Iniciar sessió',          'Iniciar sesión',           'Login' ],
        'register'       => [ 'page-register.php',   'Registre',                'Registro',                 'Register' ],
        'profile'        => [ 'page-profile.php',    'Perfil',                  'Perfil',                   'Profile' ],
        'cart'           => [ 'page-cart.php',       'Cistella',                'Carrito',                  'Cart' ],
        'checkout'       => [ 'page-checkout.php',   'Finalitzar compra',       'Finalizar compra',         'Checkout' ],
        'my_account'     => [ 'page-my-account.php', 'El meu compte',           'Mi cuenta',                'My account' ],
        // Contacte: mantenim un sol UI (page-contact.php) i deixem page-contacte.php com a delegador.
        'contact'        => [ 'page-contact.php',    'Contacte',                'Contacto',                 'Contact' ],
    ];

    foreach ( $pages as $key => $def ) {
        [$template, $title_ca, $title_es, $title_en] = $def;

        // Slug canónico (ca) desde el mapa central
        $slug_ca = function_exists('fcsd_default_slug') ? fcsd_default_slug($key) : $key;

        $page_id = fcsd_ensure_page($slug_ca, $title_ca, $template);
        if ( ! $page_id ) continue;

        // Metas de slugs traducidos
        $slug_es = function_exists('fcsd_slug') ? fcsd_slug($key, 'es') : '';
        $slug_en = function_exists('fcsd_slug') ? fcsd_slug($key, 'en') : '';

        fcsd_apply_page_i18n_meta($page_id, [
            '_fcsd_i18n_slug_es'   => $slug_es,
            '_fcsd_i18n_slug_en'   => $slug_en,
            '_fcsd_i18n_title_es'  => $title_es,
            '_fcsd_i18n_title_en'  => $title_en,
            // El contenido traducido se deja vacío por defecto.
            // Si quieres, puedes precargar bloques/shortcodes aquí.
            '_fcsd_i18n_content_es' => '',
            '_fcsd_i18n_content_en' => '',
        ]);
    }

    // ---------------------------------------------------------------------
    // Menús base (un menú por ubicación + títulos traducidos por metadatos)
    // ---------------------------------------------------------------------
    $ensure_menu = function(string $name, string $location, array $items): void {
        $menu = wp_get_nav_menu_object( $name );
        if ( ! $menu ) {
            $menu_id = wp_create_nav_menu( $name );
        } else {
            $menu_id = (int) $menu->term_id;
        }

        // Asignar ubicación.
        $locations = get_theme_mod( 'nav_menu_locations', [] );
        if ( empty($locations[$location]) ) {
            $locations[$location] = $menu_id;
            set_theme_mod( 'nav_menu_locations', $locations );
        }

        // Crear items si el menú está vacío.
        $existing_items = wp_get_nav_menu_items( $menu_id );
        if ( is_array($existing_items) && ! empty($existing_items) ) {
            return;
        }

        foreach ( $items as $it ) {
            $type = $it['type'] ?? 'page';
            $key  = $it['key'] ?? '';
            $label_ca = $it['label_ca'] ?? '';
            $label_es = $it['label_es'] ?? '';
            $label_en = $it['label_en'] ?? '';

            $args = [
                'menu-item-title'  => $label_ca,
                'menu-item-status' => 'publish',
            ];

            if ( $type === 'page' && $key ) {
                $slug_ca = function_exists('fcsd_default_slug') ? fcsd_default_slug($key) : $key;
                $page = get_page_by_path( $slug_ca );
                if ( $page instanceof WP_Post ) {
                    $args['menu-item-object']    = 'page';
                    $args['menu-item-object-id'] = (int) $page->ID;
                    $args['menu-item-type']      = 'post_type';
                } else {
                    $args['menu-item-type'] = 'custom';
                    $args['menu-item-url']  = home_url('/');
                }
            } elseif ( $type === 'custom' && ! empty($it['url']) ) {
                $args['menu-item-type'] = 'custom';
                $args['menu-item-url']  = $it['url'];
            } else {
                continue;
            }

            $item_id = wp_update_nav_menu_item( $menu_id, 0, $args );
            if ( is_wp_error($item_id) || ! $item_id ) continue;

            if ( $label_es ) update_post_meta( (int)$item_id, '_fcsd_i18n_title_es', $label_es );
            if ( $label_en ) update_post_meta( (int)$item_id, '_fcsd_i18n_title_en', $label_en );
        }
    };

    $ensure_menu(
        'Primary',
        'primary',
        [
            [ 'type' => 'page', 'key' => 'about',    'label_ca' => 'Qui som',     'label_es' => 'Quiénes somos', 'label_en' => 'About' ],
            [ 'type' => 'custom', 'url' => get_post_type_archive_link('service') ?: home_url('/'), 'label_ca' => 'Serveis', 'label_es' => 'Servicios', 'label_en' => 'Services' ],
            [ 'type' => 'custom', 'url' => get_post_type_archive_link('news') ?: home_url('/'),    'label_ca' => 'Actualitat', 'label_es' => 'Actualidad', 'label_en' => 'News' ],
            [ 'type' => 'custom', 'url' => get_post_type_archive_link('fcsd_product') ?: home_url('/'), 'label_ca' => 'Botiga',   'label_es' => 'Tienda', 'label_en' => 'Shop' ],
            [ 'type' => 'page', 'key' => 'contact',  'label_ca' => 'Contacte',   'label_es' => 'Contacto', 'label_en' => 'Contact' ],
        ]
    );

    $ensure_menu(
        'Topbar',
        'topbar',
        [
            [ 'type' => 'page', 'key' => 'offers',        'label_ca' => 'Ofertes',   'label_es' => 'Ofertas',   'label_en' => 'Offers' ],
            [ 'type' => 'page', 'key' => 'calendar_actes','label_ca' => 'Calendari', 'label_es' => 'Calendario','label_en' => 'Calendar' ],
            [ 'type' => 'page', 'key' => 'intranet',      'label_ca' => 'Intranet',  'label_es' => 'Intranet',  'label_en' => 'Intranet' ],
        ]
    );

    // Si quieres que la HOME sea una página concreta, puedes añadir lógica aquí.
    // Ejemplo:
    // $front_id = fcsd_find_page_by_slug('inici');
    // if ($front_id) { update_option('show_on_front','page'); update_option('page_on_front',$front_id); }

    flush_rewrite_rules();
}

// Ejecuta al activar el tema.
add_action('after_switch_theme', 'fcsd_on_theme_activation');
