<?php
/**
 * FCSD i18n links
 * - Añade prefijo de idioma a enlaces del frontend
 * - Traduce slugs "canónicos" (ca) a su versión por idioma
 */
defined('ABSPATH') || exit;

if ( ! function_exists('str_starts_with') ) {
    function str_starts_with($haystack, $needle) {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}

function fcsd_add_lang_to_url(string $url): string {
    if ( is_admin() ) return $url;
    if ( ! defined('FCSD_LANG') ) return $url;

    // Idioma por defecto sin prefijo (/). Los otros con /es/ y /en/.
    if ( FCSD_LANG === FCSD_DEFAULT_LANG ) {
        return $url;
    }

    $p = wp_parse_url($url);

    // Soportar URLs relativas (muy comunes en componentes que generen URLs relativas).
    // Si es relativa, la tratamos como path del propio sitio.
    $is_relative = empty($p['host']);

    // Solo modificamos URLs del propio sitio.
    // Evitar recursión: aquí NO usamos home_url() porque estamos dentro del filtro 'home_url'
    $home = wp_parse_url(get_option('home'));
    if ( ! $is_relative ) {
        if ( empty($home['host']) || $home['host'] !== $p['host'] ) return $url;
    }

    $path = $p['path'] ?? '/';
    // No tocar wp-admin ni wp-json ni assets
    if ( str_starts_with($path, '/wp-admin') || str_starts_with($path, '/wp-json') ) return $url;

    $path_trim = ltrim($path, '/');
    $segments = $path_trim === '' ? [] : explode('/', $path_trim);

    // Si ya tiene prefijo de idioma, no duplicar
    if ( ! empty($segments[0]) && isset(FCSD_LANGUAGES[$segments[0]]) ) {
        return $url;
    }

    // Traducir primer segmento (slug canónico -> slug por idioma)
    if ( ! empty($segments[0]) ) {
        $key = fcsd_slug_key_from_translated($segments[0]); // sirve también para detectar canónico, porque contiene ca
        if ( $key ) {
            $segments[0] = fcsd_slug($key, FCSD_LANG);
        }
    }

    array_unshift($segments, FCSD_LANG);
    $new_path = '/' . implode('/', array_filter($segments)) . '/';

    // Reconstruir URL
    $query = isset($p['query']) ? '?' . $p['query'] : '';
    $frag  = isset($p['fragment']) ? '#' . $p['fragment'] : '';

    if ( $is_relative ) {
        // Conservamos relativo. Evitamos forzar scheme/host.
        return $new_path . $query . $frag;
    }

    $scheme = $p['scheme'] ?? ($home['scheme'] ?? 'https');
    $host   = $p['host'];
    $port   = isset($p['port']) ? ':' . $p['port'] : '';

    return $scheme . '://' . $host . $port . $new_path . $query . $frag;
}



/**
 * URL de la misma ruta en otro idioma (mejor esfuerzo).
 * - Mantiene el path actual
 * - Cambia prefijo /ca|es|en/
 * - Traduce el primer segmento si está en el mapa de slugs
 */
function fcsd_switch_lang_url(string $target_lang): string {
    if ( ! isset(FCSD_LANGUAGES[$target_lang]) ) {
        $target_lang = FCSD_DEFAULT_LANG;
    }

    // Detectar "home" de forma robusta.
    // A veces is_front_page() puede no ser fiable (p. ej. cuando hay redirecciones/canonicals
    // o el request se reescribe). Si la ruta (sin prefijo de idioma) es vacía, forzamos la raíz.
    $raw_path = '/';
    if ( isset($_SERVER['REQUEST_URI']) ) {
        $raw_path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }
    $raw_path = trim($raw_path, '/');
    $raw_parts = $raw_path === '' ? [] : explode('/', $raw_path);
    if ( ! empty($raw_parts[0]) && isset(FCSD_LANGUAGES[$raw_parts[0]]) ) {
        array_shift($raw_parts);
    }
    $is_root_request = empty($raw_parts);

    // En home/front-page queremos siempre la raíz del idioma.
    if ( $is_root_request || ( function_exists('is_front_page') && is_front_page() ) ) {
        $home = rtrim((string) get_option('home'), '/');
        if ( $target_lang === FCSD_DEFAULT_LANG ) {
            return $home . '/';
        }
        return $home . '/' . $target_lang . '/';
    }

    // Mejor fuente del path actual: $wp->request (ya normalizado por i18n-router.php)
    // Fallback: REQUEST_URI.
    $segments = [];
    if ( isset($GLOBALS['wp']) && $GLOBALS['wp'] instanceof WP ) {
        $req = trim((string) $GLOBALS['wp']->request, '/');
        if ( $req !== '' ) {
            $segments = explode('/', $req);
        }
    }

    if ( empty($segments) ) {
        $path = '/';
        if ( isset($_SERVER['REQUEST_URI']) ) {
            $path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        }
        $path = trim($path, '/');
        $segments = $path === '' ? [] : explode('/', $path);
    }

    // quitar prefijo actual si existe
    if ( ! empty($segments[0]) && isset(FCSD_LANGUAGES[$segments[0]]) ) {
        array_shift($segments);
    }

    // traducir primer segmento si aplica
    if ( ! empty($segments[0]) ) {
        $key = fcsd_slug_key_from_translated($segments[0]);
        if ( $key ) {
            $segments[0] = fcsd_slug($key, $target_lang);
        }
    }

    $home = rtrim((string) get_option('home'), '/');
    $tail = implode('/', array_filter($segments));

    if ( $target_lang === FCSD_DEFAULT_LANG ) {
        return $home . '/' . ($tail ? $tail . '/' : '');
    }

    return $home . '/' . $target_lang . '/' . ($tail ? $tail . '/' : '');
}



add_filter('page_link', function($url, $post_id){
    $url = fcsd_add_lang_to_url($url);
    if ( FCSD_LANG !== FCSD_DEFAULT_LANG ) {
        $slug = get_post_meta((int)$post_id, '_fcsd_i18n_slug_' . FCSD_LANG, true);
        if ( is_string($slug) && $slug !== '' ) {
            $url = preg_replace('#/[^/]+/?$#', '/' . sanitize_title($slug) . '/', $url);
        }
    }
    return $url;
}, 20, 2);

add_filter('post_link', function($url, $post, $leavename){
    $url = fcsd_add_lang_to_url($url);
    if ( FCSD_LANG !== FCSD_DEFAULT_LANG && $post instanceof WP_Post ) {
        $slug = get_post_meta((int)$post->ID, '_fcsd_i18n_slug_' . FCSD_LANG, true);
        if ( is_string($slug) && $slug !== '' ) {
            $url = preg_replace('#/[^/]+/?$#', '/' . sanitize_title($slug) . '/', $url);
        }
    }
    return $url;
}, 20, 3);

add_filter('post_type_link', function($url, $post, $leavename, $sample){
    $url = fcsd_add_lang_to_url($url);
    if ( FCSD_LANG !== FCSD_DEFAULT_LANG && $post instanceof WP_Post ) {
        $slug = get_post_meta((int)$post->ID, '_fcsd_i18n_slug_' . FCSD_LANG, true);
        if ( is_string($slug) && $slug !== '' ) {
            $url = preg_replace('#/[^/]+/?$#', '/' . sanitize_title($slug) . '/', $url);
        }
    }
    return $url;
}, 20, 4);

add_filter('term_link', 'fcsd_add_lang_to_url', 20);

// paginate_links devuelve HTML; reescribimos hrefs del sitio
add_filter('paginate_links', function($html){
    if ( ! is_string($html) || $html === '' ) return $html;
    return preg_replace_callback('/href=["\']([^"\']+)["\']/', function($m){
        $u = $m[1];
        return 'href="' . esc_url(fcsd_add_lang_to_url($u)) . '"';
    }, $html);
}, 20);