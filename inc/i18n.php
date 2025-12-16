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

    // FCSD_THEME_DIR existe desde functions.php.
    if ( defined('FCSD_THEME_DIR') ) {
        load_theme_textdomain('fcsd', FCSD_THEME_DIR . '/languages');
    }
}

add_action('init', 'fcsd_apply_frontend_locale', 1);
