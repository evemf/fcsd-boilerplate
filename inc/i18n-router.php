<?php
/**
 * FCSD i18n router (sin plugins) – versión limpia
 *
 * Principios:
 * - NO modificamos $wp->request en parse_request (eso rompe el matcher de rewrites).
 * - El idioma se determina por:
 *   1) query var fcsd_lang (puesta por rewrites)
 *   2) prefijo /ca|es|en/ en la URL (fallback)
 * - Para slugs traducidos (name / term / pagename), convertimos a slug canónico (CA)
 *   usando metadatos:
 *     - posts/pages: _fcsd_i18n_slug_{lang}
 *     - terms:       _fcsd_i18n_slug_{lang}
 *
 * Con esto, los templates funcionan igual en todos los idiomas porque WP siempre
 * acaba consultando el mismo contenido (slug canónico).
 */
defined('ABSPATH') || exit;

add_filter('query_vars', function(array $vars): array {
    $vars[] = 'fcsd_lang';
    return $vars;
});

/**
 * Busca un post ID por slug traducido guardado en meta.
 */
function fcsd_find_post_id_by_translated_slug(string $post_type, string $lang, string $translated_slug): int {
    $translated_slug = sanitize_title($translated_slug);
    if ($translated_slug === '') return 0;

    $found = get_posts([
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'meta_key'       => '_fcsd_i18n_slug_' . $lang,
        'meta_value'     => $translated_slug,
        'fields'         => 'ids',
        'posts_per_page' => 1,
        'no_found_rows'  => true,
    ]);
    return !empty($found[0]) ? (int) $found[0] : 0;
}

/**
 * Busca un término por slug traducido guardado en meta.
 */
function fcsd_find_term_by_translated_slug(string $taxonomy, string $lang, string $translated_slug): ?WP_Term {
    $translated_slug = sanitize_title($translated_slug);
    if ($translated_slug === '') return null;

    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'number'     => 1,
        'meta_query' => [
            [
                'key'   => '_fcsd_i18n_slug_' . $lang,
                'value' => $translated_slug,
            ],
        ],
    ]);
    if (is_wp_error($terms) || empty($terms[0]) || !($terms[0] instanceof WP_Term)) return null;
    return $terms[0];
}

/**
 * Normaliza query vars para que:
 * - Los slugs en ES/EN apunten al contenido canónico (CA).
 * - Los archives/listados funcionen igual en todos los idiomas.
 */

/**
 * Virtual router: interpreta rutas /{lang}/... aunque las rewrite rules no estén flushed
 * o aunque WP haya resuelto la URL como pagename genérico.
 *
 * Objetivo: que /es/servicios, /en/services, /es/tienda, /en/shop, etc. carguen el
 * contenido correcto SIN redirecciones.
 */
function fcsd_virtual_route_parse_request(\WP $wp): void {
    if ( is_admin() ) return;

    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = (string) parse_url($uri, PHP_URL_PATH);
    $path = trim($path, '/');

    if ($path === '') {
        return;
    }

    $parts = explode('/', $path);
    $lang  = $parts[0] ?? '';
    if ( ! $lang || ! defined('FCSD_LANGUAGES') || ! isset(FCSD_LANGUAGES[$lang]) ) {
        return; // sin prefijo de idioma => no tocamos
    }

    // Fijamos idioma siempre que haya prefijo
    $wp->query_vars['fcsd_lang'] = $lang;

    $rest = array_slice($parts, 1);
    // /{lang}/
    if (empty($rest)) {
        // Home por idioma: limpiamos posibles pagename=lang
        unset($wp->query_vars['pagename'], $wp->query_vars['name']);
        return;
    }

    // Helpers de slugs base
    $services = trim((string) (function_exists('fcsd_slug') ? fcsd_slug('services', $lang) : ''), '/');
    $events   = trim((string) (function_exists('fcsd_slug') ? fcsd_slug('events', $lang) : ''), '/');
    $news     = trim((string) (function_exists('fcsd_slug') ? fcsd_slug('news', $lang) : ''), '/');
    $shop     = trim((string) (function_exists('fcsd_slug') ? fcsd_slug('shop', $lang) : ''), '/');
    $product  = trim((string) (function_exists('fcsd_slug') ? fcsd_slug('shop_product', $lang) : ''), '/');

    $seg0 = $rest[0] ?? '';

    // -------------------------
    // Services: /{lang}/{services}/[page/N]|[single]
    // -------------------------
    if ($seg0 === $services && $services !== '') {
        // reset posibles vars de página
        unset($wp->query_vars['pagename'], $wp->query_vars['name']);

        $wp->query_vars['post_type'] = 'service';

        // paginación
        if (isset($rest[1]) && $rest[1] === 'page' && isset($rest[2]) && preg_match('/^\d+$/', $rest[2])) {
            $wp->query_vars['paged'] = (int) $rest[2];
            return;
        }

        // single
        if (isset($rest[1]) && $rest[1] !== '' && $rest[1] !== 'page') {
            $wp->query_vars['name'] = sanitize_title($rest[1]);
            return;
        }
        return;
    }

    // -------------------------
    // Events: /{lang}/{events}/[page/N]|[single]
    // -------------------------
    if ($seg0 === $events && $events !== '') {
        unset($wp->query_vars['pagename'], $wp->query_vars['name']);

        $wp->query_vars['post_type'] = 'event';

        if (isset($rest[1]) && $rest[1] === 'page' && isset($rest[2]) && preg_match('/^\d+$/', $rest[2])) {
            $wp->query_vars['paged'] = (int) $rest[2];
            return;
        }

        if (isset($rest[1]) && $rest[1] !== '' && $rest[1] !== 'page') {
            $wp->query_vars['name'] = sanitize_title($rest[1]);
            return;
        }
        return;
    }

    // -------------------------
    // News:
    // - listado: /{lang}/{news}/[page/N] => página canónica (ca) con template page-news.php
    // - single:  /{lang}/{news}/{slug}   => CPT news
    // -------------------------
    if ($seg0 === $news && $news !== '') {
        unset($wp->query_vars['pagename'], $wp->query_vars['name'], $wp->query_vars['post_type']);

        if (isset($rest[1]) && $rest[1] !== '' && $rest[1] !== 'page') {
            $wp->query_vars['post_type'] = 'news';
            $wp->query_vars['name']      = sanitize_title($rest[1]);
            return;
        }

        // listado: pagename = slug canónico de la página "news" (en CA)
        $news_page_slug = function_exists('fcsd_default_slug') ? fcsd_default_slug('news') : 'noticies';
        $wp->query_vars['pagename'] = $news_page_slug;

        if (isset($rest[1]) && $rest[1] === 'page' && isset($rest[2]) && preg_match('/^\d+$/', $rest[2])) {
            $wp->query_vars['paged'] = (int) $rest[2];
        }
        return;
    }

    // -------------------------
    // Shop:
    // - archive: /{lang}/{shop}/
    // - single:  /{lang}/{shop}/{product}/{slug}
    // - cat:     /{lang}/{shop}/{cat}/[page/N]
    // -------------------------
    if ($seg0 === $shop && $shop !== '') {
        unset($wp->query_vars['pagename'], $wp->query_vars['name'], $wp->query_vars['post_type']);

        $seg1 = $rest[1] ?? '';
        $seg2 = $rest[2] ?? '';
        $seg3 = $rest[3] ?? '';

        // Slug canónico del segmento "product" (siempre en idioma por defecto)
        $product_canonical = function_exists('fcsd_default_slug') ? fcsd_default_slug('shop_product') : 'producte';

        // 1) Single producto con segmento explícito (traducido o canónico)
        //    /{lang}/{shop}/{product}/{slug}
        if ($seg1 !== '' && ($seg1 === $product || $seg1 === $product_canonical) && $seg2 !== '') {
            $maybe = sanitize_title($seg2);

            // Primero probamos slug canónico (post_name)
            $p = get_page_by_path($maybe, OBJECT, 'fcsd_product');
            if ($p instanceof \WP_Post) {
                $wp->query_vars['post_type'] = 'fcsd_product';
                $wp->query_vars['name']      = $maybe;
                return;
            }

            // Si no existe como post_name, probamos slug traducido guardado en meta
            $pid = fcsd_find_post_id_by_translated_slug('fcsd_product', $lang, $maybe);
            if ($pid) {
                $canonical = (string) get_post_field('post_name', $pid);
                $wp->query_vars['post_type'] = 'fcsd_product';
                $wp->query_vars['p']         = (int) $pid;
                if ($canonical !== '') {
                    $wp->query_vars['name'] = $canonical;
                } else {
                    unset($wp->query_vars['name']);
                }
                return;
            }

            // Fallback: deja que WP resuelva (no content si no existe)
            $wp->query_vars['post_type'] = 'fcsd_product';
            $wp->query_vars['name']      = $maybe;
            return;
        }

        // 2) Categoría (si existe el término; evita confundirlo con un producto)
        //    /{lang}/{shop}/{cat}/[page/N]
        if ($seg1 !== '' && $seg1 !== 'page') {
            $maybe_term_slug = sanitize_title($seg1);
            $term = get_term_by('slug', $maybe_term_slug, 'fcsd_product_cat');
            if (!($term instanceof \WP_Term)) {
                $term = fcsd_find_term_by_translated_slug('fcsd_product_cat', $lang, $maybe_term_slug);
            }
            if ($term instanceof \WP_Term) {
                $wp->query_vars['fcsd_product_cat'] = $term->slug;
                $wp->query_vars['taxonomy']         = 'fcsd_product_cat';
                $wp->query_vars['term']             = $term->slug;
                if ($seg2 === 'page' && $seg3 !== '' && preg_match('/^\d+$/', $seg3)) {
                    $wp->query_vars['paged'] = (int) $seg3;
                }
                return;
            }
        }

        // 3) Single producto sin segmento "product" (compat / enlaces antiguos)
        //    /{lang}/{shop}/{slug}
        if ($seg1 !== '' && $seg1 !== 'page') {
            $maybe_product = sanitize_title($seg1);
            $p = get_page_by_path($maybe_product, OBJECT, 'fcsd_product');
            if (!($p instanceof \WP_Post)) {
                $pid = fcsd_find_post_id_by_translated_slug('fcsd_product', $lang, $maybe_product);
                if ($pid) {
                    $canonical = (string) get_post_field('post_name', $pid);
                    if ($canonical !== '') {
                        $maybe_product = $canonical;
                        $p = get_page_by_path($maybe_product, OBJECT, 'fcsd_product');
                    }
                }
            }
            if ($p instanceof \WP_Post) {
                $wp->query_vars['post_type'] = 'fcsd_product';
                $wp->query_vars['name']      = $maybe_product;
                return;
            }
        }

        // archive
        $wp->query_vars['post_type'] = 'fcsd_product';
        return;
    }

    // -------------------------
    // Páginas genéricas traducidas por meta _fcsd_i18n_slug_{lang}
    // Soporta páginas jerárquicas /a/b/c (busca por la parte final).
    // -------------------------
    $candidate = end($rest);
    $candidate = is_string($candidate) ? sanitize_title($candidate) : '';
    if ($candidate !== '' && function_exists('fcsd_find_post_id_by_translated_slug')) {
        $pid = fcsd_find_post_id_by_translated_slug('page', $lang, $candidate);
        if ($pid) {
            $canonical = (string) get_post_field('post_name', $pid);
            if ($canonical !== '') {
                unset($wp->query_vars['post_type'], $wp->query_vars['name']);
                $wp->query_vars['pagename'] = $canonical;
                return;
            }
        }
    }

    // Si el slug pertenece al mapa central (system pages), normalizamos a su canónico.
    if (function_exists('fcsd_slug_key_from_translated') && function_exists('fcsd_default_slug')) {
        $key = fcsd_slug_key_from_translated($seg0);
        if ($key) {
            $wp->query_vars['pagename'] = fcsd_default_slug($key);
            return;
        }
    }
}
add_action('parse_request', 'fcsd_virtual_route_parse_request', 1);

add_filter('request', function(array $qv): array {
    if (is_admin()) return $qv;

    $lang = function_exists('fcsd_lang') ? fcsd_lang() : (defined('FCSD_DEFAULT_LANG') ? FCSD_DEFAULT_LANG : 'ca');
    $is_non_default = defined('FCSD_DEFAULT_LANG') ? ($lang !== FCSD_DEFAULT_LANG) : ($lang !== 'ca');

    // HOME por idioma: si alguien resuelve /ca/ como pagename=ca, limpiamos.
    foreach (['pagename', 'name'] as $k) {
        if (!empty($qv[$k]) && is_string($qv[$k]) && $qv[$k] === $lang) {
            unset($qv[$k]);
        }
    }

    if (!$is_non_default) {
        return $qv;
    }

    // -------------------------
    // CPT singles: service, event, news, fcsd_product
    // -------------------------
    $cpt_types = ['service','event','news','fcsd_product'];
    if (!empty($qv['post_type']) && is_string($qv['post_type']) && in_array($qv['post_type'], $cpt_types, true)) {
        $pt = $qv['post_type'];

        if (!empty($qv['name']) && is_string($qv['name'])) {
            $incoming = sanitize_title($qv['name']);

            // Si ya existe como post_name, no tocamos.
            $p = get_page_by_path($incoming, OBJECT, $pt);
            if (!($p instanceof WP_Post)) {
                // Buscar por meta traducida y convertir a canónico.
                $pid = fcsd_find_post_id_by_translated_slug($pt, $lang, $incoming);
                if ($pid) {
                    $canonical = (string) get_post_field('post_name', $pid);
                    if ($canonical !== '') {
                        $qv['name'] = $canonical;
                    }
                }
            }
        }
    }

    // -------------------------
    // Pages: traducidas por meta _fcsd_i18n_slug_{lang}
    // -------------------------
    if (!empty($qv['pagename']) && is_string($qv['pagename'])) {
        $incoming = trim($qv['pagename'], '/');
        $incoming = sanitize_title($incoming);

        // Si WP ya encuentra página por slug, ok.
        $p = get_page_by_path($incoming, OBJECT, 'page');
        if (!($p instanceof WP_Post)) {
            $pid = fcsd_find_post_id_by_translated_slug('page', $lang, $incoming);
            if ($pid) {
                $canonical = (string) get_post_field('post_name', $pid);
                if ($canonical !== '') {
                    $qv['pagename'] = $canonical;
                }
            } else {
                // Si es un slug de sistema definido en el mapa, lo normalizamos.
                if (function_exists('fcsd_slug_key_from_translated') && function_exists('fcsd_default_slug')) {
                    $key = fcsd_slug_key_from_translated($incoming);
                    if ($key) {
                        $qv['pagename'] = fcsd_default_slug($key);
                    }
                }
            }
        }
    }

    // -------------------------
    // Taxonomía de categorías de producto (shop)
    // -------------------------
    if (!empty($qv['fcsd_product_cat']) && is_string($qv['fcsd_product_cat'])) {
        $incoming = sanitize_title($qv['fcsd_product_cat']);
        $term = get_term_by('slug', $incoming, 'fcsd_product_cat');
        if (!($term instanceof WP_Term)) {
            $term = fcsd_find_term_by_translated_slug('fcsd_product_cat', $lang, $incoming);
            if ($term) {
                $qv['fcsd_product_cat'] = $term->slug;
            }
        }
    }

    return $qv;
}, 10, 1);

/**
 * Evitar que WP "adivine" permalinks y redirija a contenido equivocado en ES/EN.
 */
add_filter('redirect_guess_404_permalink', function($link) {
    $path = '';
    if (!empty($_SERVER['REQUEST_URI'])) {
        $path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }
    $path = trim($path, '/');
    if ($path === '') return $link;

    $first = explode('/', $path)[0] ?? '';
    if ($first && defined('FCSD_LANGUAGES') && isset(FCSD_LANGUAGES[$first])) {
        return false;
    }
    return $link;
});
