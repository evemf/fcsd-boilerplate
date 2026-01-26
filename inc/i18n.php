<?php
/**
 * FCSD i18n core
 * - Detecta idioma por prefijo de URL (/ca/, /es/, /en/)
 * - Define constantes y helpers base
 */
defined('ABSPATH') || exit;

if ( ! defined('FCSD_LANGUAGES') ) {
    define('FCSD_LANGUAGES', [
        'ca' => 'ca_ES',
        'es' => 'es_ES',
        'en' => 'en_US',
    ]);
}

if ( ! defined('FCSD_DEFAULT_LANG') ) {
    define('FCSD_DEFAULT_LANG', 'ca');
}

/**
 * Devuelve el idioma actual (ca|es|en) detectado desde la URL.
 * Nota: la normalización del request (routing) se hace en inc/i18n-router.php.
 */
function fcsd_detect_lang(): string {
    static $lang = null;
    if ( $lang !== null ) return $lang;

    $path = '/';
    if ( isset($_SERVER['REQUEST_URI']) ) {
        $path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }
    $path = trim($path, '/');
    $parts = $path === '' ? [] : explode('/', $path);

    $candidate = $parts[0] ?? '';
    if ( $candidate && isset(FCSD_LANGUAGES[$candidate]) ) {
        $lang = $candidate;
    } else {
        $lang = FCSD_DEFAULT_LANG;
    }
    return $lang;
}

if ( ! defined('FCSD_LANG') ) {
    define('FCSD_LANG', fcsd_detect_lang());
}

/** Locale WP para el idioma actual */
function fcsd_current_locale(): string {
    return FCSD_LANGUAGES[FCSD_LANG] ?? FCSD_LANGUAGES[FCSD_DEFAULT_LANG];
}

/**
 * Fuerza el locale del request a partir del prefijo de URL.
 *
 * Esto debe ejecutarse MUY pronto (antes de after_setup_theme) para que
 * load_theme_textdomain() cargue el MO correcto en la misma request.
 */
function fcsd_filter_request_locale( string $locale ): string {
    if ( is_admin() ) {
        return $locale;
    }
    return fcsd_current_locale();
}

add_filter( 'locale', 'fcsd_filter_request_locale', 0 );
add_filter( 'determine_locale', 'fcsd_filter_request_locale', 0 );

/**
 * Aplica el locale del frontend (core + tema) en cada request.
 *
 * Importante: load_theme_textdomain() se ejecuta en after_setup_theme y, si el locale
 * cambia por prefijo de URL (/es/, /en/), WordPress no recarga automáticamente el MO
 * del tema. Esto hace que el selector de idioma “parezca” no funcionar.
 *
 * Aquí forzamos el locale efectivo y recargamos el textdomain del tema.
 */
function fcsd_apply_frontend_locale(): void {
    if ( is_admin() ) return;

    $locale = fcsd_current_locale();

    // Cambia el locale de WordPress (core/plugins) si existe.
    if ( function_exists('switch_to_locale') ) {
        // Evita apilar locales repetidos.
        if ( determine_locale() !== $locale ) {
            switch_to_locale($locale);
        }
    }

    // Recarga traducciones del tema para el locale actual.
    if ( function_exists('unload_textdomain') ) {
        unload_textdomain('fcsd');
    }

    if ( ! defined('FCSD_THEME_DIR') ) {
        return;
    }

    // 1) Carga estándar del tema (WordPress localizará fcsd-LOCALE.mo en /languages)
    load_theme_textdomain('fcsd', FCSD_THEME_DIR . '/languages');

    // 2) Salvaguarda: carga directa del MO exacto.
    // En algunas instalaciones (caché de MO, orden de hooks, child/parent) la carga estándar
    // puede quedar “enganchada” al locale por defecto. Forzamos el fichero esperado.
    $mo = trailingslashit(FCSD_THEME_DIR) . 'languages/fcsd-' . $locale . '.mo';
    if ( file_exists($mo) && function_exists('load_textdomain') ) {
        load_textdomain('fcsd', $mo);
    }
}


// Aún más temprano: antes de que otros componentes (CPTs, plantillas, etc.) lean strings.
// - plugins_loaded: casi lo primero tras cargar plugins/tema
// - setup_theme: antes de after_setup_theme
add_action('plugins_loaded', 'fcsd_apply_frontend_locale', 0);
add_action('setup_theme', 'fcsd_apply_frontend_locale', 0);

// Recarga del locale/textdomain lo antes posible en el frontend.
// after_setup_theme asegura que el locale efectivo ya está determinado y que,
// si el tema se usa como hijo, la ruta a /languages es la correcta.
add_action('after_setup_theme', 'fcsd_apply_frontend_locale', 1);

// Salvaguarda adicional (algunas instalaciones cargan traducciones tardías).
add_action('init', 'fcsd_apply_frontend_locale', 1);
