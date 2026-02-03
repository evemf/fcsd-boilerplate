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
/**
 * Idioma actual (ca|es|en).
 *
 * Importante: NO dependemos únicamente de una constante calculada “muy pronto”,
 * porque WordPress puede tocar el routing/query vars más tarde (request filters,
 * canonical redirects, etc.). En algunos entornos esto hacía que /en/ se
 * resolviera correctamente como URL, pero el tema siguiera usando CA.
 */
function fcsd_lang(): string {
    // 1) Si el router ya ha fijado el idioma en query_vars, es la fuente de verdad.
    if ( function_exists('get_query_var') ) {
        $qv = get_query_var('fcsd_lang');
        if ( is_string($qv) && $qv !== '' && isset(FCSD_LANGUAGES[$qv]) ) {
            return $qv;
        }
    }

    // 2) Algunos puntos del core aún no tienen query vars disponibles.
    if ( isset($GLOBALS['wp']) && is_object($GLOBALS['wp']) && ! empty($GLOBALS['wp']->query_vars['fcsd_lang']) ) {
        $qv = (string) $GLOBALS['wp']->query_vars['fcsd_lang'];
        if ( $qv !== '' && isset(FCSD_LANGUAGES[$qv]) ) {
            return $qv;
        }
    }

    // 3) Fallback robusto: prefijo de URL.
    $path = '/';
    if ( isset($_SERVER['REQUEST_URI']) ) {
        $path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }
    $path = trim($path, '/');
    $parts = $path === '' ? [] : explode('/', $path);
    $candidate = $parts[0] ?? '';
    if ( $candidate && isset(FCSD_LANGUAGES[$candidate]) ) {
        return $candidate;
    }
    return FCSD_DEFAULT_LANG;
}

// Mantener compatibilidad con código existente.
if ( ! defined('FCSD_LANG') ) {
    define('FCSD_LANG', fcsd_lang());
}

/** Locale WP para el idioma actual */
function fcsd_current_locale(): string {
    $lang = fcsd_lang();
    return FCSD_LANGUAGES[$lang] ?? FCSD_LANGUAGES[FCSD_DEFAULT_LANG];
}


/**
 * Corrige "mojibake" típico (p.ej. "CategorÃ­a") cuando una cadena UTF-8
 * se ha interpretado como ISO-8859-1 y luego se vuelve a imprimir como UTF-8.
 *
 * Solo se aplica si detectamos patrones comunes (Ã / Â) y si la conversión
 * produce una cadena UTF-8 válida.
 */
function fcsd_fix_mojibake( $str ) {
    if ( ! is_string( $str ) || $str === '' ) {
        return $str;
    }

    // Patrones habituales cuando UTF-8 se muestra como Latin-1/Windows-1252.
    if ( strpos( $str, 'Ã' ) === false && strpos( $str, 'Â' ) === false ) {
        return $str;
    }

    // Intento de reparación: tratar la cadena actual como ISO-8859-1 y convertir a UTF-8.
    $fixed = function_exists( 'iconv' ) ? @iconv( 'ISO-8859-1', 'UTF-8//IGNORE', $str ) : $str;

    if ( is_string( $fixed ) && $fixed != '' && preg_match( '//u', $fixed ) ) {
        // Si parece mejorar (menos mojibake), nos quedamos con el fix.
        $before = substr_count( $str, 'Ã' ) + substr_count( $str, 'Â' );
        $after  = substr_count( $fixed, 'Ã' ) + substr_count( $fixed, 'Â' );
        if ( $after < $before ) {
            return $fixed;
        }
    }

    return $str;
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
 * Salvaguarda: si una traducción existe pero está vacía (msgstr ""),
 * WordPress devolverá una cadena vacía y el frontend parece “incompleto”.
 *
 * En este tema (sin plugins) preferimos que, si falta traducción,
 * se muestre el texto original (Catalán por defecto) antes que un vacío.
 */
function fcsd_gettext_no_empty_translation( $translation, $text, $domain ) {
    if ( $domain !== 'fcsd' ) {
        return $translation;
    }

    // 1) Si la traducción está vacía pero el texto original no lo está, fallback.
    if ( $translation === '' && $text !== '' ) {
        return fcsd_fix_mojibake( $text );
    }

    // 2) Salvaguarda i18n (sin depender del locale/.mo):
    // En algunas instalaciones el locale puede quedar “enganchado” aunque la URL
    // sea /es/ o /en/. Esto hace que los textos fijos del tema no se traduzcan.
    // Para NO romper otras funcionalidades, solo intervenimos cuando WP devuelve
    // exactamente el texto original (sin traducir).
    if ( $translation === $text && is_string( $text ) && $text !== '' ) {
        $lang = function_exists('fcsd_lang') ? fcsd_lang() : ( defined('FCSD_LANG') ? FCSD_LANG : 'ca' );

        if ( $lang !== 'ca' ) {
            $map = array(
                'es' => array(
                    'Àmbits de treball' => 'Ámbitos de trabajo',
                    'Institucional' => 'Institucional',
                    'Vida independent' => 'Vida independiente',
                    'Treball' => 'Trabajo',
                    'Formació' => 'Formación',
                    'Oci' => 'Ocio',
                    'Salut' => 'Salud',
                    'Merchandising' => 'Merchandising',
                    'Èxit 21' => 'Èxit 21',
                    'Assemblea DH' => 'Asamblea DH',
                    'Voluntariat' => 'Voluntariado',
                    'Saber més' => 'Saber más',
                    'Qui som' => 'Quiénes somos',
                    'Com arribar-hi' => 'Cómo llegar',
                    'Contrast' => 'Contraste',
                    'Donar' => 'Donar',
                    'Treballem per la plena inclusió i igualtat de drets.' => 'Trabajamos por la plena inclusión e igualdad de derechos.',

                    // Shop (navbar-filters.php)
                    'Mostrar filtres' => 'Mostrar filtros',
                    'Filtres' => 'Filtros',
                    'Buscar' => 'Buscar',
                    'Cerca productes…' => 'Buscar productos…',
                    'Categoria' => 'Categoría',
                    'Totes' => 'Todas',
                    'Qualsevol' => 'Cualquiera',
                    'Preu mín.' => 'Precio mín.',
                    'Preu màx.' => 'Precio máx.',
                    'Aplicar filtres' => 'Aplicar filtros',
                    'Netejar' => 'Limpiar',
                ),
                'en' => array(
                    'Àmbits de treball' => 'Areas of work',
                    'Institucional' => 'Institutional',
                    'Vida independent' => 'Independent living',
                    'Treball' => 'Work',
                    'Formació' => 'Training',
                    'Oci' => 'Leisure',
                    'Salut' => 'Health',
                    'Merchandising' => 'Merchandising',
                    'Èxit 21' => 'Èxit 21',
                    'Assemblea DH' => 'DH Assembly',
                    'Voluntariat' => 'Volunteering',
                    'Saber més' => 'Learn more',
                    'Qui som' => 'About us',
                    'Com arribar-hi' => 'Get directions',
                    'Contrast' => 'Contrast',
                    'Donar' => 'Donate',
                    'Treballem per la plena inclusió i igualtat de drets.' => 'We work for full inclusion and equal rights.',

                    // Shop (navbar-filters.php)
                    'Mostrar filtres' => 'Show filters',
                    'Filtres' => 'Filters',
                    'Buscar' => 'Search',
                    'Cerca productes…' => 'Search products…',
                    'Categoria' => 'Category',
                    'Totes' => 'All',
                    'Qualsevol' => 'Any',
                    'Preu mín.' => 'Min. price',
                    'Preu màx.' => 'Max. price',
                    'Aplicar filtres' => 'Apply filters',
                    'Netejar' => 'Clear',
                ),
            );

            if ( isset( $map[ $lang ][ $text ] ) ) {
                return fcsd_fix_mojibake( $map[ $lang ][ $text ] );
            }
        }
    }

    return fcsd_fix_mojibake( $translation );
}
add_filter( 'gettext', 'fcsd_gettext_no_empty_translation', 10, 3 );
add_filter( 'gettext_with_context', 'fcsd_gettext_no_empty_translation', 10, 3 );

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
    // Importante: en este tema el idioma se decide por prefijo de URL y
    // el router fija `fcsd_lang` en `parse_request`. Si intentamos comparar
    // contra determine_locale() demasiado pronto, podemos quedarnos con el
    // locale por defecto (ca_ES) aunque la URL sea /es/ o /en/.
    // Por eso, aquí hacemos el switch de forma idempotente: si ya es el
    // mismo locale, WP no cambia nada.
    if ( function_exists('switch_to_locale') ) {
        switch_to_locale($locale);
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


// Nota: el tema se carga DESPUÉS de `plugins_loaded` y `setup_theme`, así que
// engancharse ahí desde functions.php no garantiza ejecución en la misma request.
// Para que las traducciones estén listas antes de registrar CPTs/taxonomías
// (que suele ocurrir en `init`), forzamos el locale/textdomain en `after_setup_theme`
// con prioridad 0 y repetimos en `init` con prioridad 0.

// Lo antes posible en el frontend.
add_action('after_setup_theme', 'fcsd_apply_frontend_locale', 0);

// Antes de que otros componentes en `init` registren labels.
add_action('init', 'fcsd_apply_frontend_locale', 0);

// Punto “seguro”: aquí ya han corrido las rewrites y el router (parse_request)
// y `fcsd_lang` está fijado. Esto evita el caso en el que el frontend usa
// siempre el locale por defecto aunque la URL sea /es/ o /en/.
add_action('parse_request', function () {
    fcsd_apply_frontend_locale();
}, 1);

add_action('wp', 'fcsd_apply_frontend_locale', 0);
