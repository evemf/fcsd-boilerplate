<?php
/**
 * FCSD i18n rewrites (sin plugins)
 *
 * Objetivo:
 * - Prefijo de idioma para TODOS los idiomas: /ca/, /es/, /en/
 * - El contenido NO se duplica por idioma: el mismo post sirve para CA/ES/EN
 *   y se traduce con metadatos i18n (con fallback a CA si falta traducción).
 *
 * Nota importante:
 * - NO usamos rewrite_rules_array para "inyectar" reglas en masa, porque es
 *   fácil provocar colisiones con reglas genéricas del core (pagename) y
 *   acabar en listados vacíos en ES/EN.
 * - En su lugar, declaramos reglas explícitas con add_rewrite_rule(..., 'top')
 *   para que tengan máxima prioridad y sean deterministas.
 */

defined('ABSPATH') || exit;

add_filter('query_vars', static function(array $vars): array {
    $vars[] = 'fcsd_lang';
    return $vars;
});

/**
 * Añade las reglas de reescritura i18n con prioridad máxima.
 */
add_action('init', static function(): void {
    if ( ! defined('FCSD_LANGUAGES') || ! is_array(FCSD_LANGUAGES) ) {
        return;
    }
    if ( ! function_exists('fcsd_slug') ) {
        return;
    }

    $langs = array_keys(FCSD_LANGUAGES);
    if (empty($langs)) {
        return;
    }

    // HOME por idioma
    foreach ($langs as $lang) {
        add_rewrite_rule(
            '^' . preg_quote($lang, '#') . '/?$',
            'index.php?fcsd_lang=' . $lang,
            'top'
        );
    }

    // Páginas de sistema (canónicas en CA). En ES/EN deben rutear a la página CA.
    $system_pages = [
        'about','patronat','organigrama','history','intranet','offers',
        'calendar_actes','calendar_work','memories','press','volunteering','alliances',
        'cart','checkout','my_account','login','register','profile','contact',
    ];

    foreach ($langs as $lang) {
        foreach ($system_pages as $key) {
            $canonical   = function_exists('fcsd_default_slug') ? fcsd_default_slug($key) : $key;
            $translated  = (string) fcsd_slug($key, $lang);
            if ($translated === '') continue;

            add_rewrite_rule(
                '^' . preg_quote($lang, '#') . '/' . preg_quote(trim($translated, '/'), '#') . '/?$',
                'index.php?pagename=' . $canonical . '&fcsd_lang=' . $lang,
                'top'
            );
        }
    }

    // --- CPT + taxonomías ---
    foreach ($langs as $lang) {
        $services = trim((string) fcsd_slug('services', $lang), '/');
        $events   = trim((string) fcsd_slug('events', $lang), '/');
        $news     = trim((string) fcsd_slug('news', $lang), '/');
        $shop     = trim((string) fcsd_slug('shop', $lang), '/');
        $product  = trim((string) fcsd_slug('shop_product', $lang), '/');

        // Services (service)
        add_rewrite_rule('^' . preg_quote($lang,'#') . '/' . preg_quote($services,'#') . '/?$',
            'index.php?post_type=service&fcsd_lang=' . $lang,
            'top'
        );
        add_rewrite_rule('^' . preg_quote($lang,'#') . '/' . preg_quote($services,'#') . '/page/([0-9]{1,})/?$',
            'index.php?post_type=service&paged=$matches[1]&fcsd_lang=' . $lang,
            'top'
        );
        add_rewrite_rule('^' . preg_quote($lang,'#') . '/' . preg_quote($services,'#') . '/([^/]+)/?$',
            'index.php?post_type=service&name=$matches[1]&fcsd_lang=' . $lang,
            'top'
        );

        // Events (event)
        add_rewrite_rule('^' . preg_quote($lang,'#') . '/' . preg_quote($events,'#') . '/?$',
            'index.php?post_type=event&fcsd_lang=' . $lang,
            'top'
        );
        add_rewrite_rule('^' . preg_quote($lang,'#') . '/' . preg_quote($events,'#') . '/page/([0-9]{1,})/?$',
            'index.php?post_type=event&paged=$matches[1]&fcsd_lang=' . $lang,
            'top'
        );
        add_rewrite_rule('^' . preg_quote($lang,'#') . '/' . preg_quote($events,'#') . '/([^/]+)/?$',
            'index.php?post_type=event&name=$matches[1]&fcsd_lang=' . $lang,
            'top'
        );

        // News: listado en página canónica + single news
        $news_page_slug = function_exists('fcsd_default_slug') ? fcsd_default_slug('news') : 'noticies';
        add_rewrite_rule('^' . preg_quote($lang,'#') . '/' . preg_quote($news,'#') . '/?$',
            'index.php?pagename=' . $news_page_slug . '&fcsd_lang=' . $lang,
            'top'
        );
        add_rewrite_rule('^' . preg_quote($lang,'#') . '/' . preg_quote($news,'#') . '/page/([0-9]{1,})/?$',
            'index.php?pagename=' . $news_page_slug . '&paged=$matches[1]&fcsd_lang=' . $lang,
            'top'
        );
        add_rewrite_rule('^' . preg_quote($lang,'#') . '/' . preg_quote($news,'#') . '/([^/]+)/?$',
            'index.php?post_type=news&name=$matches[1]&fcsd_lang=' . $lang,
            'top'
        );

        // Shop (fcsd_product) + single product + category tax
        add_rewrite_rule('^' . preg_quote($lang,'#') . '/' . preg_quote($shop,'#') . '/?$',
            'index.php?post_type=fcsd_product&fcsd_lang=' . $lang,
            'top'
        );
        add_rewrite_rule('^' . preg_quote($lang,'#') . '/' . preg_quote($shop,'#') . '/' . preg_quote($product,'#') . '/([^/]+)/?$',
            'index.php?post_type=fcsd_product&name=$matches[1]&fcsd_lang=' . $lang,
            'top'
        );
        add_rewrite_rule('^' . preg_quote($lang,'#') . '/' . preg_quote($shop,'#') . '/([^/]+)/?$',
            'index.php?fcsd_product_cat=$matches[1]&fcsd_lang=' . $lang,
            'top'
        );
        add_rewrite_rule('^' . preg_quote($lang,'#') . '/' . preg_quote($shop,'#') . '/([^/]+)/page/([0-9]{1,})/?$',
            'index.php?fcsd_product_cat=$matches[1]&paged=$matches[2]&fcsd_lang=' . $lang,
            'top'
        );

        // Fallback final a páginas dentro del idioma
        add_rewrite_rule('^' . preg_quote($lang,'#') . '/(.+?)/page/([0-9]{1,})/?$',
            'index.php?pagename=$matches[1]&paged=$matches[2]&fcsd_lang=' . $lang,
            'bottom'
        );
        add_rewrite_rule('^' . preg_quote($lang,'#') . '/(.+?)/?$',
            'index.php?pagename=$matches[1]&fcsd_lang=' . $lang,
            'bottom'
        );
    }
});
