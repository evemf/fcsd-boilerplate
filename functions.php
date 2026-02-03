<?php
/**
 * Funciones principales del tema FCSD Boilerplate
 */

// --------------------------------------------------
// Constantes del tema
// --------------------------------------------------
if ( ! defined( 'FCSD_VERSION' ) ) {
    // Usa la versión del tema definida en style.css
    $theme = wp_get_theme();
    define( 'FCSD_VERSION', $theme->get( 'Version' ) ?: '1.0.0' );
}

// -----------------------------------------------------------------------------
// Flush rewrites on theme update (one-time)
// -----------------------------------------------------------------------------
// Las reglas i18n añaden bases traducidas para archives/singles. Si el tema se
// actualiza sobre una instalación existente, after_switch_theme no se ejecuta y
// las nuevas reglas pueden no estar activas hasta guardar Permalinks.
// Hacemos un flush seguro (solo 1 vez por versión de reglas) en admin.
add_action( 'admin_init', function(){
    $key     = 'fcsd_rewrite_rules_version';
    $version = '2026-02-03-services-single-i18n';
    if ( get_option( $key ) !== $version ) {
        flush_rewrite_rules();
        update_option( $key, $version );
    }
} );

if ( ! defined( 'FCSD_THEME_DIR' ) ) {
    // Usa la carpeta del tema activo (soporta tema hijo sin romper rutas).
    define( 'FCSD_THEME_DIR', get_stylesheet_directory() );
}

if ( ! defined( 'FCSD_THEME_URI' ) ) {
    define( 'FCSD_THEME_URI', get_stylesheet_directory_uri() );
}


// --------------------------------------------------
// i18n (sin plugins) – infraestructura
// --------------------------------------------------
require_once FCSD_THEME_DIR . '/inc/i18n.php';
require_once FCSD_THEME_DIR . '/inc/slugs.php';
require_once FCSD_THEME_DIR . '/inc/i18n-content.php';
require_once FCSD_THEME_DIR . '/inc/i18n-router.php';
require_once FCSD_THEME_DIR . '/inc/i18n-links.php';
require_once FCSD_THEME_DIR . '/inc/i18n-admin.php';
require_once FCSD_THEME_DIR . '/inc/i18n-menu.php';
require_once FCSD_THEME_DIR . '/inc/i18n-menu-admin.php';
require_once FCSD_THEME_DIR . '/inc/i18n-canonical.php';
require_once FCSD_THEME_DIR . '/inc/i18n-rewrites.php';
require_once FCSD_THEME_DIR . '/inc/theme-activate.php';

require_once FCSD_THEME_DIR . '/inc/class-nav-walker-mega.php';

// --------------------------------------------------
// Soporte del tema
// --------------------------------------------------
function fcsd_theme_setup() {
    load_theme_textdomain( 'fcsd', FCSD_THEME_DIR . '/languages' );

    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ] );
    add_theme_support( 'custom-logo', [
        'height'      => 80,
        'width'       => 240,
        'flex-height' => true,
        'flex-width'  => true,
    ] );

    // Menús base (idioma por defecto)
    $menus = [
        'primary' => __( 'Menú principal', 'fcsd' ),
        'topbar'  => __( 'Franja superior', 'fcsd' ),
        'footer'  => __( 'Menú del peu de pàgina', 'fcsd' ),
        'social'  => __( 'Enllaços socials', 'fcsd' ),
    ];

    // Menús per idioma: el tema utilitza un sol conjunt d'ubicacions.
    // Els títols i URLs dels ítems es tradueixen via inc/i18n-menu.php.

    register_nav_menus( $menus );
}
add_action( 'after_setup_theme', 'fcsd_theme_setup' );
 

// Añade clases semánticas a items concretos del menú (sin que el editor tenga que hacerlo).
add_filter('nav_menu_css_class', function($classes, $item, $args, $depth){
    if ( ! ($item instanceof WP_Post) ) return $classes;
    if ( empty($args->theme_location) ) return $classes;

    // Solo en menús principales.
    if ( ! in_array($args->theme_location, ['primary'], true) ) {
        return $classes;
    }

    $url = (string) ($item->url ?? '');
    if ( $url === '' ) return $classes;

    $path = (string) parse_url($url, PHP_URL_PATH);
    $path = trim($path, '/');
    if ( $path === '' ) return $classes;
    $parts = explode('/', $path);
    // quitar prefijo de idioma si existe
    if ( ! empty($parts[0]) && defined('FCSD_LANGUAGES') && isset(FCSD_LANGUAGES[$parts[0]]) ) {
        array_shift($parts);
    }

    $first = $parts[0] ?? '';
    if ( $first === '' ) return $classes;

    // Mapeo: slug -> clase de mega menú
    $key = function_exists('fcsd_slug_key_from_translated') ? fcsd_slug_key_from_translated($first) : null;
    $slug = $key ? $key : $first;

    // Secciones (puedes ampliar el mapa en inc/slugs.php)
    if ( in_array($slug, ['about','quisom','qui-som'], true) || in_array($first, ['qui-som','quisom'], true) ) {
        $classes[] = 'fcsd-mega-quisom';
    }
    if ( in_array($slug, ['services','serveis'], true) || $first === 'serveis' ) {
        $classes[] = 'fcsd-mega-serveis';
    }

    return array_values(array_unique($classes));
}, 20, 4);

// --------------------------------------------------
// Tamaños de imagen
// --------------------------------------------------
function fcsd_setup_image_sizes() {
    add_image_size( 'news-thumb', 400, 250, true );
    add_image_size( 'news-large', 1200, 630, true );
    add_image_size( 'hero-desktop', 1920, 800, true );
    add_image_size( 'hero-mobile', 768, 600, true );
}
add_action( 'after_setup_theme', 'fcsd_setup_image_sizes' );

// --------------------------------------------------
// Widgets
// --------------------------------------------------
function fcsd_widgets_init() {
    register_sidebar( [
        'name'          => __( 'Barra lateral', 'fcsd' ),
        'id'            => 'sidebar-1',
        'description'   => __( 'Afegeix aquí els teus widgets.', 'fcsd' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title h5">',
        'after_title'   => '</h2>',
    ] );
}
add_action( 'widgets_init', 'fcsd_widgets_init' );

// --------------------------------------------------
// Helpers de idioma (ca|es|en) para textos legales del footer
// --------------------------------------------------

/**
 * Devuelve el código de idioma actual (ca|es|en).
 *
 * Compatibilidad:
 * - Polylang: pll_current_language('slug')
 * - WPML: ICL_LANGUAGE_CODE
 * - WordPress: get_locale()
 */
function fcsd_get_current_lang_code() {
    // Polylang.
    if ( function_exists( 'pll_current_language' ) ) {
        $slug = pll_current_language( 'slug' );
        if ( is_string( $slug ) && $slug ) {
            $slug = strtolower( $slug );
            if ( in_array( $slug, [ 'ca', 'es', 'en' ], true ) ) {
                return $slug;
            }
        }
    }

    // WPML.
    if ( defined( 'ICL_LANGUAGE_CODE' ) && is_string( ICL_LANGUAGE_CODE ) && ICL_LANGUAGE_CODE ) {
        $code = strtolower( ICL_LANGUAGE_CODE );
        if ( in_array( $code, [ 'ca', 'es', 'en' ], true ) ) {
            return $code;
        }
    }

    // Locale de WordPress.
    $locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
    $locale = is_string( $locale ) ? strtolower( $locale ) : '';

    if ( 0 === strpos( $locale, 'es' ) ) {
        return 'es';
    }
    if ( 0 === strpos( $locale, 'en' ) ) {
        return 'en';
    }
    return 'ca';
}

/**
 * Obtiene un theme_mod legal por idioma.
 *
 * Reglas de fallback:
 * - Si el valor del idioma actual NO existe o está vacío, intenta con catalán (_ca) y después con legacy (sin sufijo).
 * - Si todo está vacío, devuelve $default.
 */
function fcsd_get_legal_mod( $base_key, $lang, $default = '' ) {
    $lang = in_array( $lang, [ 'ca', 'es', 'en' ], true ) ? $lang : 'ca';

    // Helper: distinguir entre "no configurado" y "configurado como string vacío".
    $raw_get = static function ( $key ) {
        $sentinel = '__FCSD_NOT_SET__';
        $v        = get_theme_mod( $key, $sentinel );
        return ( $sentinel === $v ) ? null : $v;
    };

    $is_non_empty = static function ( $v ) {
        if ( null === $v ) {
            return false;
        }
        if ( is_string( $v ) ) {
            return '' !== trim( $v );
        }
        return ! empty( $v );
    };

    // 1) Intentar el valor del idioma actual.
    $value = $raw_get( $base_key . '_' . $lang );
    if ( $is_non_empty( $value ) ) {
        return $value;
    }

    // 2) Fallback a catalán si estamos en ES/EN.
    if ( 'ca' !== $lang ) {
        $ca_value = $raw_get( $base_key . '_ca' );
        if ( $is_non_empty( $ca_value ) ) {
            return $ca_value;
        }
    }

    // 3) Fallback a legacy (sin sufijo) (sirve tanto para CA como para ES/EN si venimos sin datos).
    $legacy = $raw_get( $base_key );
    if ( $is_non_empty( $legacy ) ) {
        return $legacy;
    }

    return $default;
}

/**
 * Devuelve los textos legales (title/content) en el idioma actual.
 */
function fcsd_get_legal_texts() {
    $lang = fcsd_get_current_lang_code();

    $defaults = [
        'ca' => [
            'privacy_title'   => 'Política de privacitat',
            'cookies_title'   => 'Política de cookies',
            'notice_title'    => 'Avís legal',
            'copyright_title' => 'Copyright',
            'close_text'      => 'Tancar',
        ],
        'es' => [
            'privacy_title'   => 'Política de privacidad',
            'cookies_title'   => 'Política de cookies',
            'notice_title'    => 'Aviso legal',
            'copyright_title' => 'Copyright',
            'close_text'      => 'Cerrar',
        ],
        'en' => [
            'privacy_title'   => 'Privacy policy',
            'cookies_title'   => 'Cookies policy',
            'notice_title'    => 'Legal notice',
            'copyright_title' => 'Copyright',
            'close_text'      => 'Close',
        ],
    ];

    $d = $defaults[ $lang ] ?? $defaults['ca'];

    return [
        'privacy'   => [
            'title'   => fcsd_get_legal_mod( 'fcsd_legal_privacy_title', $lang, $d['privacy_title'] ),
            'content' => wp_kses_post( fcsd_get_legal_mod( 'fcsd_legal_privacy_content', $lang, '' ) ),
        ],
        'cookies'   => [
            'title'   => fcsd_get_legal_mod( 'fcsd_legal_cookies_title', $lang, $d['cookies_title'] ),
            'content' => wp_kses_post( fcsd_get_legal_mod( 'fcsd_legal_cookies_content', $lang, '' ) ),
        ],
        'legal'     => [
            'title'   => fcsd_get_legal_mod( 'fcsd_legal_notice_title', $lang, $d['notice_title'] ),
            'content' => wp_kses_post( fcsd_get_legal_mod( 'fcsd_legal_notice_content', $lang, '' ) ),
        ],
        'copyright' => [
            'title'   => fcsd_get_legal_mod( 'fcsd_legal_copyright_title', $lang, $d['copyright_title'] ),
            'content' => wp_kses_post( fcsd_get_legal_mod( 'fcsd_legal_copyright_content', $lang, '' ) ),
        ],
        'closeText' => $d['close_text'],
    ];
}

// --------------------------------------------------
// Encolar scripts y estilos
// --------------------------------------------------
function fcsd_enqueue_assets() {
    // ---------- CSS principal ----------
    wp_enqueue_style(
        'fcsd-style',
        get_stylesheet_uri(),
        [],
        FCSD_VERSION
    );

    // ---------- Bootstrap ----------
    wp_enqueue_style(
        'bootstrap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
        [],
        '5.3.3'
    );

    // ---------- Iconos (Bootstrap Icons) ----------
    wp_enqueue_style(
        'bootstrap-icons',
        'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css',
        [],
        '1.11.3'
    );

    // CSS para el mobile-nav
    wp_enqueue_style(
        'fcsd-mobile-nav',
        FCSD_THEME_URI . '/assets/css/mobile-nav.css',
        [ 'fcsd-style' ],
        FCSD_VERSION
    );

    // CSS para mejoras de tienda
    wp_enqueue_style(
        'fcsd-shop-enhancements',
        FCSD_THEME_URI . '/assets/css/shop-enhancements.css',
        [ 'fcsd-style' ],
        FCSD_VERSION
    );

    // ---------- JS de terceros ----------
    wp_enqueue_script(
        'bootstrap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
        [],
        '5.3.3',
        true
    );

    // ---------- JS del tema ----------
    wp_enqueue_script(
        'fcsd-menu',
        FCSD_THEME_URI . '/assets/js/menu.js',
        [ 'bootstrap' ],
        FCSD_VERSION,
        true
    );

    wp_enqueue_script(
        'fcsd-contrast',
        FCSD_THEME_URI . '/assets/js/contrast.js',
        [],
        FCSD_VERSION,
        true
    );

    wp_enqueue_script( 'jquery' );

    wp_enqueue_script(
        'fcsd-lazy-products',
        FCSD_THEME_URI . '/assets/js/lazy-products.js',
        [ 'jquery' ],
        FCSD_VERSION,
        true
    );

    wp_enqueue_script(
        'fcsd-product-360',
        FCSD_THEME_URI . '/assets/js/product-360.js',
        [ 'jquery' ],
        FCSD_VERSION,
        true
    );

    if ( is_page_template( 'page-organigrama.php' ) ) {
        wp_enqueue_script(
            'fcsd-organigrama',
            FCSD_THEME_URI . '/assets/js/organigrama.js',
            [],
            FCSD_VERSION,
            true
        );
    }

    // NOTA: shop.js se encola desde shop-core.php con wp_localize_script

    wp_enqueue_script(
        'fcsd-chatbot',
        FCSD_THEME_URI . '/assets/js/chatbot.js',
        [ 'jquery' ],
        FCSD_VERSION,
        true
    );

    wp_enqueue_script(
        'fcsd-legal-modal',
        FCSD_THEME_URI . '/assets/js/legal-modal.js',
        [],
        FCSD_VERSION,
        true
    );

    // Home (front-page) enhancements
    if ( is_front_page() ) {
        wp_enqueue_style(
            'fcsd-home',
            FCSD_THEME_URI . '/assets/css/home.css',
            [ 'bootstrap', 'fcsd-style' ],
            FCSD_VERSION
        );

        wp_enqueue_script(
            'fcsd-home',
            FCSD_THEME_URI . '/assets/js/home.js',
            [],
            FCSD_VERSION,
            true
        );
    }

    // Calendari d'actes (frontend): tooltips + modal de consulta per dia.
    if ( is_page_template( 'calendar-actes.php' ) || is_page_template( 'calendar-work.php' ) ) {
        wp_enqueue_script(
            'fcsd-actes-calendar-public',
            FCSD_THEME_URI . '/assets/js/actes-calendar-public.js',
            [ 'bootstrap' ],
            FCSD_VERSION,
            true
        );
    }

    // Single servei: iniciar el vídeo de YouTube amb so després d'una interacció (scroll/click).
    if ( is_singular( 'service' ) ) {
        wp_enqueue_script(
            'fcsd-service-video-autoplay',
            FCSD_THEME_URI . '/assets/js/service-video-autoplay.js',
            [],
            FCSD_VERSION,
            true
        );
    }

    // ----- Pasar los textos legales (por idioma) del Customizer al JS -----
    $legal_data = fcsd_get_legal_texts();

    wp_localize_script( 'fcsd-legal-modal', 'fcsdLegalData', $legal_data );
}
add_action( 'wp_enqueue_scripts', 'fcsd_enqueue_assets' );

// --------------------------------------------------
// Includes del tema
// --------------------------------------------------
require_once FCSD_THEME_DIR . '/inc/customizer.php';
require_once FCSD_THEME_DIR . '/inc/cpts.php';
require_once FCSD_THEME_DIR . '/inc/timeline-admin.php';
require_once FCSD_THEME_DIR . '/inc/services-areas.php';
require_once FCSD_THEME_DIR . '/inc/service-meta.php';
require_once FCSD_THEME_DIR . '/inc/transparency-meta.php';
require_once FCSD_THEME_DIR . '/inc/template-tags.php';
require_once FCSD_THEME_DIR . '/inc/setup-content.php';
require_once FCSD_THEME_DIR . '/inc/auth.php';
require_once FCSD_THEME_DIR . '/inc/contact-form.php';
require_once FCSD_THEME_DIR . '/inc/setup.php';
require_once FCSD_THEME_DIR . '/inc/organigrama.php';
require_once FCSD_THEME_DIR . '/inc/actes.php';
require_once FCSD_THEME_DIR . '/inc/intranet.php';
require_once FCSD_THEME_DIR . '/inc/sinergia-api.php';
require_once FCSD_THEME_DIR . '/inc/sinergia-cache.php';
require_once FCSD_THEME_DIR . '/inc/sinergia-sync.php';
require_once FCSD_THEME_DIR . '/inc/sinergia-form.php';
if ( is_admin() ) {
  require_once FCSD_THEME_DIR . '/inc/sinergia-admin.php';
}
require_once FCSD_THEME_DIR . '/inc/external-news.php';
require_once FCSD_THEME_DIR . '/inc/news-sync-exit21.php';
require_once FCSD_THEME_DIR . '/inc/ecommerce/helpers.php';
require_once FCSD_THEME_DIR . '/inc/ecommerce/shop-core.php';
require_once FCSD_THEME_DIR . '/inc/ecommerce/orders.php';
require_once FCSD_THEME_DIR . '/inc/ecommerce/product-meta.php';
require_once FCSD_THEME_DIR . '/inc/ecommerce/template-tags-shop.php';

// --------------------------------------------------
// Helpers
// --------------------------------------------------
/**
 * Helper simple para leer opciones del Customizer.
 *
 * @param string $key
 * @param mixed  $default
 * @return mixed
 */
function fcsd_get_option( $key, $default = '' ) {
    // Soporte i18n para opciones del Customizer.
    // Si existe la variante por idioma (p.ej. home_intro_es), se usa.
    $lang = function_exists('fcsd_lang')
        ? fcsd_lang()
        : ( defined('FCSD_LANG') ? FCSD_LANG : ( defined('FCSD_DEFAULT_LANG') ? FCSD_DEFAULT_LANG : 'ca' ) );

    /**
     * Importante:
     * En el Customizer, WordPress puede “previsualizar” defaults de settings aunque
     * aún no estén guardados como theme_mod. En frontend, get_theme_mod() NO conoce
     * esos defaults salvo que se los pasemos.
     *
     * Resultado del bug: en /es/ o /en/ el hero se veía en catalán porque
     * home_intro_es/en no existían aún como theme_mod y caía al fallback.
     */
    $i18n_defaults = array(
        'home_intro' => array(
            'ca' => 'Acompanyem a persones amb SD a construir una vida més autònoma, plena i connectada.',
            'es' => 'Acompañamos a personas con SD a construir una vida más autónoma, plena y conectada.',
            'en' => 'We support people with Down syndrome to build a more independent, fulfilling and connected life.',
        ),
        'home_cta_label' => array(
            'ca' => 'Qui som',
            'es' => 'Quiénes somos',
            'en' => 'About us',
        ),
    );

    $lang_default = $default;
    if ( isset( $i18n_defaults[ $key ] ) && is_array( $i18n_defaults[ $key ] ) ) {
        $lang_default = $i18n_defaults[ $key ][ $lang ]
            ?? $i18n_defaults[ $key ][ ( defined('FCSD_DEFAULT_LANG') ? FCSD_DEFAULT_LANG : 'ca' ) ]
            ?? $default;
    }

    $val_lang = get_theme_mod( $key . '_' . $lang, $lang_default );
    if ( $val_lang !== null && $val_lang !== '' ) {
        return $val_lang;
    }

    $val = get_theme_mod( $key, null );
    return $val !== null ? $val : $default;
}

// --------------------------------------------------
// Configuración SMTP (wp_mail)
// --------------------------------------------------
add_action( 'phpmailer_init', 'fcsd_configure_smtp' );

/**
 * Configura SMTP a partir de opciones del Customizer.
 *
 * No fuerza SMTP si no hay host configurado.
 *
 * @param PHPMailer $phpmailer
 */
function fcsd_configure_smtp( $phpmailer ) {
    $host = get_theme_mod( 'fcsd_smtp_host', '' );

    if ( empty( $host ) ) {
        return; // No forzar SMTP si no está configurado.
    }

    $phpmailer->isSMTP();
    $phpmailer->Host     = $host;
    $phpmailer->Port     = (int) get_theme_mod( 'fcsd_smtp_port', 587 );
    $phpmailer->SMTPAuth = true;
    $phpmailer->Username = get_theme_mod( 'fcsd_smtp_user', '' );
    $phpmailer->Password = get_theme_mod( 'fcsd_smtp_pass', '' );
    $secure              = get_theme_mod( 'fcsd_smtp_secure', 'tls' );

    if ( ! empty( $secure ) ) {
        $phpmailer->SMTPSecure = $secure;
    }

    $from_email = get_theme_mod( 'fcsd_smtp_from_email', '' );
    $from_name  = get_theme_mod( 'fcsd_smtp_from_name', get_bloginfo( 'name' ) );

    if ( ! empty( $from_email ) ) {
        $phpmailer->setFrom( $from_email, $from_name );
    }
}

// --------------------------------------------------
// Lazy load de productos (AJAX)
// --------------------------------------------------
function fcsd_ajax_load_more_products() {
    check_ajax_referer( 'fcsd_lazy_products', 'nonce' );

    $paged = isset( $_GET['page'] ) ? max( 1, (int) $_GET['page'] ) : 1;

    $query = new WP_Query(
        [
            'post_type'   => 'fcsd_product',
            'post_status' => 'publish',
            'paged'       => $paged,
        ]
    );

    ob_start();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            get_template_part( 'template-parts/product', 'card' );
        }
    }

    wp_reset_postdata();

    wp_send_json_success(
        [
            'html'      => ob_get_clean(),
            'have_more' => ( $paged < $query->max_num_pages ),
            'next_page' => $paged + 1,
        ]
    );
}
add_action( 'wp_ajax_fcsd_load_more_products', 'fcsd_ajax_load_more_products' );
add_action( 'wp_ajax_nopriv_fcsd_load_more_products', 'fcsd_ajax_load_more_products' );

// --------------------------------------------------
// Service Areas (service_area): ensure archive queries return Services
// --------------------------------------------------
add_action( 'pre_get_posts', function ( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    if ( $query->is_tax( 'service_area' ) ) {
        $query->set( 'post_type', 'service' );
        $query->set( 'posts_per_page', 12 );
        // Prefer manual ordering if used, otherwise stable by title
        $query->set( 'orderby', [ 'menu_order' => 'ASC', 'title' => 'ASC' ] );
    }
} );

// --------------------------------------------------
    // Shop archives: show more products per page (and keep pagination).
// --------------------------------------------------
add_action( 'pre_get_posts', function ( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    $is_shop_archive = $query->is_post_type_archive( 'fcsd_product' );
    $is_shop_tax     = $query->is_tax( 'fcsd_product_cat' );

    if ( $is_shop_archive || $is_shop_tax ) {
        $query->set( 'posts_per_page', 24 );
    }
} );
// Flush rewrites on theme switch (needed for language prefix routing)
// Activación del tema: crear páginas necesarias + flush de rewrites
add_action('after_switch_theme', 'fcsd_on_theme_activation');


/**
 * Filtra el archivo de News por idioma del tema (FCSD_LANG).
 * No afecta al admin ni a otros listados que ya tengan lógica propia.
 */
function fcsd_filter_news_archive_lang( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) return;
    if ( ! $query->is_post_type_archive( 'news' ) ) return;

    $lang = function_exists('fcsd_lang') ? fcsd_lang() : ( defined('FCSD_LANG') ? FCSD_LANG : 'ca' );

    /**
     * Reglas:
     * - Noticias internas (no exit21): visibles en TODOS los idiomas (el contenido se traduce vía i18n meta del tema).
     * - Noticias Exit21: solo existen en CA y ES.
     *   * CA: mostrar exit21 ca
     *   * ES: mostrar exit21 es
     *   * EN: NO mostrar exit21
     */
    $meta_query = [
        'relation' => 'OR',
        // 1) Internas (sin news_source o distinta de exit21)
        [
            'relation' => 'OR',
            [
                'key'     => 'news_source',
                'compare' => 'NOT EXISTS',
            ],
            [
                'key'     => 'news_source',
                'value'   => 'exit21',
                'compare' => '!=',
            ],
            [
                'key'     => 'news_source',
                'value'   => '',
                'compare' => '=',
            ],
        ],
    ];

    // 2) Exit21 por idioma (solo CA/ES)
    if ( $lang === 'ca' || $lang === 'es' ) {
        $meta_query[] = [
            'relation' => 'AND',
            [
                'key'     => 'news_source',
                'value'   => 'exit21',
                'compare' => '=',
            ],
            [
                'key'     => 'news_language',
                'value'   => $lang,
                'compare' => '=',
            ],
        ];
    }

    $query->set( 'meta_query', $meta_query );
    $query->set( 'orderby', 'date' );
    $query->set( 'order', 'DESC' );
}
add_action( 'pre_get_posts', 'fcsd_filter_news_archive_lang' );



/**
 * Redirects legacy news URLs to the canonical translated slugs.
 * - /actualitat -> /noticies
 * - /es/actualidad -> /es/noticias
 * - /en/actualidad or /en/actualitat -> /en/news (if someone linked it)
 */
add_action('template_redirect', function () {
    if ( is_admin() ) return;

    $path = isset($_SERVER['REQUEST_URI']) ? (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
    $path = '/' . trim($path, '/');

    $lang = function_exists('fcsd_lang') ? fcsd_lang() : ( defined('FCSD_LANG') ? FCSD_LANG : 'ca' );

    // Only redirect exact legacy archives (avoid breaking singles).
    if ( preg_match('#^/actualitat/?$#', $path) ) {
        wp_redirect( home_url('/' . fcsd_slug('news', 'ca') . '/') , 301 );
        exit;
    }
    if ( preg_match('#^/es/actualidad/?$#', $path) ) {
        wp_redirect( home_url('/es/' . fcsd_slug('news', 'es') . '/') , 301 );
        exit;
    }
    if ( preg_match('#^/en/(actualidad|actualitat)/?$#', $path) ) {
        wp_redirect( home_url('/en/' . fcsd_slug('news', 'en') . '/') , 301 );
        exit;
    }
});
