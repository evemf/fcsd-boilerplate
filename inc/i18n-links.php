<?php
/**
 * FCSD i18n links
 *
 * - Todas las URLs públicas llevan prefijo de idioma: /ca/, /es/, /en/
 * - Traduce bases (primer segmento) y el segmento "product" en la tienda.
 * - Selector de idioma: intenta mantener el mismo contenido; si no hay slug traducido
 *   para el destino, cae al archivo/listado correspondiente (mejor UX).
 */
defined('ABSPATH') || exit;

if ( ! function_exists('str_starts_with') ) {
    function str_starts_with($haystack, $needle) {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}

function fcsd_is_static_asset_path(string $path): bool {
    if ($path === '') return false;
    // rutas core
    if (str_starts_with($path, '/wp-admin') || str_starts_with($path, '/wp-json') || str_starts_with($path, '/wp-includes') || str_starts_with($path, '/wp-content')) {
        return true;
    }
    // extensiones típicas
    return (bool) preg_match('#\.(css|js|map|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|eot|pdf|zip)$#i', $path);
}

function fcsd_add_lang_to_url(string $url, ?string $force_lang = null): string {
    if (is_admin()) return $url;

    $lang = $force_lang && defined('FCSD_LANGUAGES') && isset(FCSD_LANGUAGES[$force_lang])
        ? $force_lang
        : (function_exists('fcsd_lang') ? fcsd_lang() : (defined('FCSD_DEFAULT_LANG') ? FCSD_DEFAULT_LANG : 'ca'));

    $p = wp_parse_url($url);
    $is_relative = empty($p['host']);

    // Solo URLs del propio sitio
    $home = wp_parse_url(get_option('home'));
    if (!$is_relative) {
        if (empty($home['host']) || $home['host'] !== ($p['host'] ?? '')) return $url;
    }

    $path = $p['path'] ?? '/';
    if (fcsd_is_static_asset_path($path)) return $url;

    $path_trim = ltrim($path, '/');
    $segments = $path_trim === '' ? [] : explode('/', $path_trim);

    // Si ya hay prefijo de idioma, lo quitamos para re-aplicar el deseado
    if (!empty($segments[0]) && defined('FCSD_LANGUAGES') && isset(FCSD_LANGUAGES[$segments[0]])) {
        array_shift($segments);
    }

    // Traducir 1er segmento (bases)
    if (!empty($segments[0]) && function_exists('fcsd_slug_key_from_translated') && function_exists('fcsd_slug')) {
        $key = fcsd_slug_key_from_translated($segments[0]);
        if ($key) {
            $segments[0] = fcsd_slug($key, $lang);
        }
    }

    // Traducir 2º segmento para producto en tienda
    if (!empty($segments[1]) && function_exists('fcsd_slug_key_from_translated') && function_exists('fcsd_slug')) {
        $key2 = fcsd_slug_key_from_translated($segments[1]);
        if ($key2 === 'shop_product') {
            $segments[1] = fcsd_slug('shop_product', $lang);
        }
    }

    array_unshift($segments, $lang);
    $new_path = '/' . implode('/', array_filter($segments, fn($s)=>$s!=='')) . '/';

    $query = isset($p['query']) ? '?' . $p['query'] : '';
    $frag  = isset($p['fragment']) ? '#' . $p['fragment'] : '';

    if ($is_relative) {
        return $new_path . $query . $frag;
    }

    $scheme = $p['scheme'] ?? ($home['scheme'] ?? 'https');
    $host   = $p['host'];
    $port   = isset($p['port']) ? ':' . $p['port'] : '';
    return $scheme . '://' . $host . $port . $new_path . $query . $frag;
}

/**
 * Selector de idioma.
 * - Mantiene la misma entidad (page/post/term) si existe slug traducido para el idioma destino.
 * - Si NO existe slug traducido en destino, cae al archivo/listado del módulo actual.
 */
function fcsd_switch_lang_url(string $target_lang): string {
    if (!defined('FCSD_LANGUAGES') || !isset(FCSD_LANGUAGES[$target_lang])) {
        $target_lang = defined('FCSD_DEFAULT_LANG') ? FCSD_DEFAULT_LANG : 'ca';
    }

    $home = rtrim((string) get_option('home'), '/');

    // HOME
    $path = '/';
    if (isset($_SERVER['REQUEST_URI'])) {
        $path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }
    $trim = trim($path, '/');
    $parts = $trim === '' ? [] : explode('/', $trim);
    if (!empty($parts[0]) && isset(FCSD_LANGUAGES[$parts[0]])) array_shift($parts);

    if (empty($parts)) {
        return $home . '/' . $target_lang . '/';
    }

    // TAXONOMY shop category
    if (function_exists('is_tax') && is_tax('fcsd_product_cat')) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            $slug = $term->slug;
            $meta = get_term_meta((int)$term->term_id, '_fcsd_i18n_slug_' . $target_lang, true);
            if (is_string($meta) && $meta !== '') {
                $slug = sanitize_title($meta);
            }
            $root_alias = (string) get_term_meta((int)$term->term_id, '_fcsd_root_alias', true);
            if ($root_alias === '1') {
                return $home . '/' . $target_lang . '/' . $slug . '/';
            }
            $shop_base = function_exists('fcsd_slug') ? fcsd_slug('shop', $target_lang) : 'shop';
            return $home . '/' . $target_lang . '/' . trim($shop_base, '/') . '/' . $slug . '/';
        }
    }

    // SINGULAR: intentar mantener la entidad (post/page) con slug traducido
    if (function_exists('is_singular') && is_singular()) {
        global $post;
        if ($post instanceof WP_Post) {
            $translated = get_post_meta((int)$post->ID, '_fcsd_i18n_slug_' . $target_lang, true);
            $translated = is_string($translated) ? sanitize_title($translated) : '';

            // Si no hay slug traducido, usamos el slug canónico (CA) dentro
            // de la estructura del idioma destino (mejor UX + nunca 404).
            if ($translated === '') {
                $translated = $post->post_name;
            }

            // Construir path base según tipo
            if ($post->post_type === 'page') {
                return $home . '/' . $target_lang . '/' . $translated . '/';
            }

            if ($post->post_type === 'service') {
                return $home . '/' . $target_lang . '/' . fcsd_slug('services', $target_lang) . '/' . $translated . '/';
            }
            if ($post->post_type === 'event') {
                return $home . '/' . $target_lang . '/' . fcsd_slug('events', $target_lang) . '/' . $translated . '/';
            }
            if ($post->post_type === 'news') {
                return $home . '/' . $target_lang . '/' . fcsd_slug('news', $target_lang) . '/' . $translated . '/';
            }
            if ($post->post_type === 'fcsd_product') {
                return $home . '/' . $target_lang . '/' . fcsd_slug('shop', $target_lang) . '/' . fcsd_slug('shop_product', $target_lang) . '/' . $translated . '/';
            }
        }
    }

    // ARCHIVES conocidos (por el primer segmento del request sin idioma)
    $first = $parts[0] ?? '';
    if (function_exists('fcsd_slug_key_from_translated') && function_exists('fcsd_slug')) {
        $key = fcsd_slug_key_from_translated($first);
        if ($key) {
            $base = fcsd_slug($key, $target_lang);
            return $home . '/' . $target_lang . '/' . trim($base, '/') . '/';
        }
    }

    // Fallback: misma ruta traducida por segmentos
    $tail = implode('/', array_filter($parts, fn($s)=>$s!==''));
    return $home . '/' . $target_lang . '/' . ($tail ? $tail . '/' : '');
}

// Filtros: asegurar que WP genere enlaces ya con prefijo y slugs traducidos
add_filter('home_url', function($url) {
    return fcsd_add_lang_to_url($url);
}, 20);

add_filter('page_link', function($url, $post_id){
    $lang = function_exists('fcsd_lang') ? fcsd_lang() : (defined('FCSD_DEFAULT_LANG') ? FCSD_DEFAULT_LANG : 'ca');
    $url = fcsd_add_lang_to_url($url, $lang);
    $slug = get_post_meta((int)$post_id, '_fcsd_i18n_slug_' . $lang, true);
    if (is_string($slug) && $slug !== '') {
        $url = preg_replace('#/[^/]+/?$#', '/' . sanitize_title($slug) . '/', $url);
    }
    return $url;
}, 20, 2);

add_filter('post_type_link', function($url, $post, $leavename, $sample){
    if (!($post instanceof WP_Post)) return $url;
    $lang = function_exists('fcsd_lang') ? fcsd_lang() : (defined('FCSD_DEFAULT_LANG') ? FCSD_DEFAULT_LANG : 'ca');

    // Añadir prefijo y traducir bases
    $url = fcsd_add_lang_to_url($url, $lang);

    // Si hay slug traducido para el post, sustituir último segmento.
    $slug = get_post_meta((int)$post->ID, '_fcsd_i18n_slug_' . $lang, true);
    if (is_string($slug) && $slug !== '') {
        $url = preg_replace('#/[^/]+/?$#', '/' . sanitize_title($slug) . '/', $url);
    }
    return $url;
}, 20, 4);
