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

if ( ! defined( 'FCSD_THEME_DIR' ) ) {
    define( 'FCSD_THEME_DIR', get_template_directory() );
}

if ( ! defined( 'FCSD_THEME_URI' ) ) {
    define( 'FCSD_THEME_URI', get_template_directory_uri() );
}

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

    register_nav_menus( [
        'primary' => __( 'Menú principal', 'fcsd' ),
        'topbar'  => __( 'Franja superior', 'fcsd' ),
        'footer'  => __( 'Menú del peu de pàgina', 'fcsd' ),
        'social'  => __( 'Enllaços socials', 'fcsd' ),
    ] );
}
add_action( 'after_setup_theme', 'fcsd_theme_setup' );

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

    // ----- Pasar los textos legales del Customizer al JS -----
    $legal_data = [
        'privacy'   => [
            'title'   => get_theme_mod( 'fcsd_legal_privacy_title', __( 'Política de privacitat', 'fcsd' ) ),
            'content' => wp_kses_post( get_theme_mod( 'fcsd_legal_privacy_content', '' ) ),
        ],
        'cookies'   => [
            'title'   => get_theme_mod( 'fcsd_legal_cookies_title', __( 'Política de cookies', 'fcsd' ) ),
            'content' => wp_kses_post( get_theme_mod( 'fcsd_legal_cookies_content', '' ) ),
        ],
        'legal'     => [
            'title'   => get_theme_mod( 'fcsd_legal_legal_title', __( 'Avís legal', 'fcsd' ) ),
            'content' => wp_kses_post( get_theme_mod( 'fcsd_legal_legal_content', '' ) ),
        ],
        'closeText' => __( 'Tancar', 'fcsd' ),
    ];

    wp_localize_script( 'fcsd-legal-modal', 'fcsdLegalData', $legal_data );
}
add_action( 'wp_enqueue_scripts', 'fcsd_enqueue_assets' );

// --------------------------------------------------
// Includes del tema
// --------------------------------------------------
require_once FCSD_THEME_DIR . '/inc/customizer.php';
require_once FCSD_THEME_DIR . '/inc/cpts.php';
require_once FCSD_THEME_DIR . '/inc/services-areas.php';
require_once FCSD_THEME_DIR . '/inc/service-meta.php';
require_once FCSD_THEME_DIR . '/inc/template-tags.php';
require_once FCSD_THEME_DIR . '/inc/setup-content.php';
require_once FCSD_THEME_DIR . '/inc/auth.php';
require_once FCSD_THEME_DIR . '/inc/setup.php';
require_once FCSD_THEME_DIR . '/inc/sinergia-api.php';
require_once FCSD_THEME_DIR . '/inc/sinergia-cache.php';
require_once FCSD_THEME_DIR . '/inc/sinergia-sync.php';
require_once FCSD_THEME_DIR . '/inc/sinergia-form.php';
if ( is_admin() ) {
  require_once FCSD_THEME_DIR . '/inc/sinergia-admin.php';
}
require_once FCSD_THEME_DIR . '/inc/external-news.php';
require_once FCSD_THEME_DIR . '/inc/news-sync-exit21.php';
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
    $val = get_theme_mod( $key );
    return $val !== false ? $val : $default;
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
            'post_type'   => 'product',
            'post_status' => 'publish',
            'paged'       => $paged,
        ]
    );

    ob_start();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            get_template_part( 'template-parts/shop/product', 'card' );
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