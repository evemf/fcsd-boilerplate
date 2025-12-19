<?php
/**
 * FCSD i18n rewrites
 *
 * WordPress no conoce, por defecto, un prefijo de idioma tipo /es/ o /en/.
 * Eso hace que /es/lo-que-sea no case con ninguna regla y termine en 404.
 *
 * Solución:
 * - Duplicamos las reglas de rewrite existentes añadiendo el prefijo de idioma.
 * - No tocamos el idioma por defecto (ca) para mantener URLs limpias.
 *
 * Con esto, /es/patronato/ casará con la MISMA regla que /patronat/, y luego
 * el router (inc/i18n-router.php) mapeará slugs traducidos a la página canónica.
 */
defined('ABSPATH') || exit;

add_filter('rewrite_rules_array', function(array $rules): array {
    if ( ! defined('FCSD_LANGUAGES') || ! defined('FCSD_DEFAULT_LANG') ) {
        return $rules;
    }

    $langs = array_keys(FCSD_LANGUAGES);
    $langs = array_values(array_filter($langs, fn($l) => $l !== FCSD_DEFAULT_LANG));
    if ( empty($langs) ) return $rules;

    $prefixed = [];
    foreach ($langs as $lang) {
        // Home del idioma: /es/ y /en/
        $prefixed[ $lang . '/?$' ] = 'index.php?fcsd_lang=' . $lang;

        foreach ($rules as $regex => $query) {
            // No duplicamos algunas rutas internas raras.
            if ( is_string($regex) && $regex !== '' ) {
                $prefixed[ $lang . '/' . $regex ] = $query;
            }
        }

        // Fallback universal para páginas traducidas por meta slug:
        // /es/patronato/ -> index.php?pagename=patronato&fcsd_lang=es
        // (Luego inc/i18n-router.php lo mapea al slug canónico)
        $prefixed[ $lang . '/(.+?)/?$' ] = 'index.php?pagename=$matches[1]&fcsd_lang=' . $lang;
    }

    // Prefijo primero para que tenga prioridad sobre reglas genéricas.
    return $prefixed + $rules;
}, 20);
