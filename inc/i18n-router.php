<?php
/**
 * FCSD i18n router
 * - Quita el prefijo de idioma del request antes de que WP resuelva rewrites
 * - Traduce slugs entrantes (es/en) al slug canónico (ca) para que los rewrites funcionen
 */
defined('ABSPATH') || exit;

add_filter('query_vars', function($vars){
    $vars[] = 'fcsd_lang';
    return $vars;
});

add_action('parse_request', function($wp){
    if ( is_admin() ) return;

    $req = ltrim((string) $wp->request, '/'); // p.ej: "es/noticias/foo"
    if ($req === '') return;

    $parts = explode('/', $req);
    $lang = $parts[0] ?? '';
    if ( ! $lang || ! isset(FCSD_LANGUAGES[$lang]) ) return;

    // Guardamos el idioma para lecturas posteriores
    $wp->query_vars['fcsd_lang'] = $lang;

    // Quitamos el prefijo de idioma del request
    array_shift($parts);

    // Si el primer slug tras el idioma es una traducción, lo mapeamos al slug canónico (ca)
    if ( ! empty($parts[0]) ) {
        $maybe = $parts[0];
        $key = fcsd_slug_key_from_translated($maybe);
        if ( $key ) {
            $parts[0] = fcsd_default_slug($key);
        }
    }

    $wp->request = implode('/', array_filter($parts, fn($p)=>$p!=='' ));
}, 0);


add_filter('request', function($qv){
    if ( is_admin() ) return $qv;
    if ( FCSD_LANG === FCSD_DEFAULT_LANG ) return $qv;

    $lang = FCSD_LANG;

    /**
     * Normalización crítica del routing.
     *
     * WordPress resuelve rewrites ANTES de disparar el action `parse_request`.
     * Para URLs tipo `/es/` o `/en/`, WP suele interpretar el request como un
     * `pagename`/`name` igual al propio código de idioma ("es"/"en").
     *
     * Si no existe una página/post con ese slug, WP acaba en 404 y puede
     * ejecutar `redirect_guess_404_permalink()`, provocando redirecciones
     * “raras” hacia cualquier contenido existente.
     *
     * Aquí corregimos el query vars para que `/es/` y `/en/` sean HOME real y
     * para que `/es/algo` se resuelva como `algo`.
     */
    $path = '/';
    if ( isset($_SERVER['REQUEST_URI']) ) {
        $path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }
    $path = trim($path, '/');

    // Caso exacto: /es o /en -> HOME (sin query vars)
    if ( $path === $lang ) {
        return [];
    }

    // Caso: WP ha resuelto pagename/name incluyendo el prefijo (es/foo)
    // o el propio idioma (es). Lo limpiamos.
    foreach (['pagename','name'] as $k) {
        if ( ! empty($qv[$k]) && is_string($qv[$k]) ) {
            if ( $qv[$k] === $lang ) {
                unset($qv[$k]);
                continue;
            }
            $prefix = $lang . '/';
            if ( $prefix !== '' && strpos($qv[$k], $prefix) === 0 ) {
                $qv[$k] = substr($qv[$k], strlen($prefix));
            }
        }
    }

    // Páginas: pagename
    if ( ! empty($qv['pagename']) && is_string($qv['pagename']) ) {
        $slug = $qv['pagename'];
        $found = get_posts([
            'post_type'   => 'page',
            'post_status' => 'publish',
            'meta_key'    => '_fcsd_i18n_slug_' . $lang,
            'meta_value'  => $slug,
            'fields'      => 'ids',
            'numberposts' => 1,
        ]);
        if ( ! empty($found[0]) ) {
            $canonical = get_post_field('post_name', (int)$found[0]);
            if ( $canonical ) $qv['pagename'] = $canonical;
        }
    }

    // Entradas/CPT: name
    if ( ! empty($qv['name']) && is_string($qv['name']) ) {
        $slug = $qv['name'];
        $found = get_posts([
            'post_type'   => 'any',
            'post_status' => 'publish',
            'meta_key'    => '_fcsd_i18n_slug_' . $lang,
            'meta_value'  => $slug,
            'fields'      => 'ids',
            'numberposts' => 1,
        ]);
        if ( ! empty($found[0]) ) {
            $canonical = get_post_field('post_name', (int)$found[0]);
            if ( $canonical ) $qv['name'] = $canonical;
        }
    }

    return $qv;
}, 20);
