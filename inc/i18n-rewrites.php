<?php
/**
 * FCSD i18n rewrites
 *
 * WordPress no conoce, por defecto, un prefijo de idioma tipo /es/ o /en/.
 * Eso hace que /es/lo-que-sea no case con ninguna regla y termine en 404
 * o en una query vacía.
 *
 * Solución:
 * - Duplicamos las reglas de rewrite existentes añadiendo el prefijo de idioma.
 * - Añadimos reglas explícitas para archivos de CPT con slug traducido.
 * - No tocamos el idioma por defecto (ca) para mantener URLs limpias.
 *
 * Con esto:
 *   /serveis/           -> archivo CPT service (ca)
 *   /es/servicios/     -> archivo CPT service (es)
 *   /en/services/      -> archivo CPT service (en)
 *
 * El router (inc/i18n-router.php) se encarga luego de resolver slugs traducidos.
 */

defined('ABSPATH') || exit;

add_filter('rewrite_rules_array', function (array $rules): array {

    if ( ! defined('FCSD_LANGUAGES') || ! defined('FCSD_DEFAULT_LANG') ) {
        return $rules;
    }

    // Idiomas NO por defecto (ej: es, en)
    $langs = array_keys(FCSD_LANGUAGES);
    $langs = array_values(array_filter(
        $langs,
        fn ($l) => $l !== FCSD_DEFAULT_LANG
    ));

    if ( empty($langs) ) {
        return $rules;
    }

    $prefixed = [];

    foreach ($langs as $lang) {

        /**
         * ---------------------------------------------------------------------
         * Home del idioma
         * /es/ , /en/
         * ---------------------------------------------------------------------
         */
        $prefixed[ $lang . '/?$' ] = 'index.php?fcsd_lang=' . $lang;

        /**
         * ---------------------------------------------------------------------
         * ARCHIVOS DE CPT CON SLUG TRADUCIDO
         * (esto es lo que te faltaba)
         * ---------------------------------------------------------------------
         */
        // Services
        $prefixed[
            $lang . '/' . fcsd_slug('services', $lang) . '/?$'
        ] = 'index.php?post_type=service&fcsd_lang=' . $lang;

        // Services (single) – base traducida
        // El CPT tiene rewrite slug fijo (ca) = /serveis/.
        // Sin esta regla, /es/servicios/<slug>/ no casará (solo existiría /es/serveis/<slug>/
        // por el duplicado genérico de reglas).
        $prefixed[
            $lang . '/' . fcsd_slug('services', $lang) . '/([^/]+)/?$'
        ] = 'index.php?post_type=service&name=$matches[1]&fcsd_lang=' . $lang;

        // Services (archive pagination) – base traducida
        $prefixed[
            $lang . '/' . fcsd_slug('services', $lang) . '/page/([0-9]{1,})/?$'
        ] = 'index.php?post_type=service&paged=$matches[1]&fcsd_lang=' . $lang;

        // News
        $prefixed[
            $lang . '/' . fcsd_slug('news', $lang) . '/?$'
        ] = 'index.php?post_type=news&fcsd_lang=' . $lang;

        // News (single) – base traducida
        $prefixed[
            $lang . '/' . fcsd_slug('news', $lang) . '/([^/]+)/?$'
        ] = 'index.php?post_type=news&name=$matches[1]&fcsd_lang=' . $lang;

        // News (archive pagination) – base traducida
        $prefixed[
            $lang . '/' . fcsd_slug('news', $lang) . '/page/([0-9]{1,})/?$'
        ] = 'index.php?post_type=news&paged=$matches[1]&fcsd_lang=' . $lang;

        // Shop / Products
        $prefixed[
            $lang . '/' . fcsd_slug('shop', $lang) . '/?$'
        ] = 'index.php?post_type=fcsd_product&fcsd_lang=' . $lang;

        /**
         * ---------------------------------------------------------------------
         * Duplicar TODAS las reglas existentes con prefijo de idioma
         * (single posts, taxonomías, paginación, etc.)
         * ---------------------------------------------------------------------
         */
        foreach ($rules as $regex => $query) {
            if ( is_string($regex) && $regex !== '' ) {
                $prefixed[ $lang . '/' . $regex ] = $query;
            }
        }

        /**
         * ---------------------------------------------------------------------
         * Fallback universal para páginas traducidas por slug
         * /es/patronato/ -> pagename=patronato&fcsd_lang=es
         * El router hará el mapeo al slug canónico.
         * ---------------------------------------------------------------------
         */
        $prefixed[
            $lang . '/(.+?)/?$'
        ] = 'index.php?pagename=$matches[1]&fcsd_lang=' . $lang;
    }

    /**
     * IMPORTANTE:
     * Las reglas con prefijo deben ir ANTES que las originales
     */
    return $prefixed + $rules;

}, 20);
