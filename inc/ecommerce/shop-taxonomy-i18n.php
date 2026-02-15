<?php
/**
 * Shop taxonomy i18n + seeding
 *
 * Este tema NO usa plugins tipo WPML/Polylang. Para poder tener:
 * - nombres traducidos de categorías
 * - slugs traducidos (y por tanto URLs distintas por idioma)
 * - y algunas categorías accesibles sin /botiga|/tienda|/shop
 *
 * guardamos traducciones en termmeta:
 *   _fcsd_i18n_name_{lang}
 *   _fcsd_i18n_slug_{lang}
 *
 * y reescribimos enlaces/routing en inc/i18n-router.php e inc/i18n-links.php.
 */

defined('ABSPATH') || exit;

/**
 * Seed de categorías de fcsd_product
 */
function fcsd_shop_seed_product_categories(): void {
    if ( ! taxonomy_exists('fcsd_product_cat') ) return;

    // Definición (CA como canónico). Slugs por idioma guardados en termmeta.
    // Nota sobre duplicados:
    // - WP no permite “el mismo término” con dos padres distintos.
    // - Para el caso “Mitjons” también como subcategoría de Marxandatge,
    //   creamos un término independiente bajo Marxandatge.
    $defs = [
        // Top-level
        'llibres' => [
            'parent' => 0,
            'name'   => [ 'ca' => 'Llibres', 'es' => 'Libros', 'en' => 'Books' ],
            'slug'   => [ 'ca' => 'llibres', 'es' => 'libros', 'en' => 'books' ],
        ],
        'obres-dart' => [
            'parent' => 0,
            'name'   => [ 'ca' => "Obres d'Art", 'es' => 'Obras de arte', 'en' => 'Artworks' ],
            'slug'   => [ 'ca' => 'obres-dart', 'es' => 'obras-de-arte', 'en' => 'artworks' ],
        ],
        // “Mitjons d'autor” como categoría principal (y también accesible sin /botiga)
        'mitjons' => [
            'parent' => 0,
            'name'   => [ 'ca' => "Mitjons d'autor", 'es' => 'Calcetines de autor', 'en' => 'Designer socks' ],
            'slug'   => [ 'ca' => 'mitjons', 'es' => 'calcetines', 'en' => 'socks' ],
            'root_alias' => true,
        ],
        'marxandatge' => [
            'parent' => 0,
            'name'   => [ 'ca' => 'Marxandatge', 'es' => 'Merchandising', 'en' => 'Merchandise' ],
            'slug'   => [ 'ca' => 'marxandatge', 'es' => 'merchandising', 'en' => 'merchandise' ],
        ],
        'papereria' => [
            'parent' => 0,
            'name'   => [ 'ca' => 'Papereria', 'es' => 'Papelería', 'en' => 'Stationery' ],
            'slug'   => [ 'ca' => 'papereria', 'es' => 'papeleria', 'en' => 'stationery' ],
        ],
        'productes-personalitzables' => [
            'parent' => 0,
            'name'   => [ 'ca' => 'Productes personalitzables', 'es' => 'Productos personalizables', 'en' => 'Customizable product' ],
            'slug'   => [ 'ca' => 'productes-personalitzables', 'es' => 'productos-personalizables', 'en' => 'customizable-product' ],
        ],
        // Subcategorías bajo Marxandatge
        'marxandatge-mitjons' => [
            'parent' => 'marxandatge',
            'name'   => [ 'ca' => "Mitjons d'autor", 'es' => 'Calcetines de autor', 'en' => 'Designer socks' ],
            'slug'   => [ 'ca' => 'mitjons', 'es' => 'calcetines', 'en' => 'socks' ],
        ],
        'samarretes' => [
            'parent' => 'marxandatge',
            'name'   => [ 'ca' => 'Samarretes', 'es' => 'Camisetas', 'en' => 'T-shirts' ],
            'slug'   => [ 'ca' => 'samarretes', 'es' => 'camisetas', 'en' => 't-shirts' ],
        ],
        'bosses' => [
            'parent' => 'marxandatge',
            'name'   => [ 'ca' => 'Bosses', 'es' => 'Bolsas', 'en' => 'Bags' ],
            'slug'   => [ 'ca' => 'bosses', 'es' => 'bolsas', 'en' => 'bags' ],
        ],
    ];

    // 1) Crear primero top-level para poder resolver parents.
    $created_ids = [];
    foreach ( $defs as $key => $def ) {
        if ( $def['parent'] !== 0 ) continue;
        $created_ids[$key] = fcsd_shop_upsert_term($key, $def, 0);
    }
    // 2) Crear children.
    foreach ( $defs as $key => $def ) {
        if ( $def['parent'] === 0 ) continue;
        $parent_key = (string) $def['parent'];
        $parent_id  = $created_ids[$parent_key] ?? 0;
        if ( ! $parent_id ) {
            $p = get_term_by('slug', $parent_key, 'fcsd_product_cat');
            $parent_id = ( $p && !is_wp_error($p) ) ? (int) $p->term_id : 0;
        }
        $created_ids[$key] = fcsd_shop_upsert_term($key, $def, $parent_id);
    }
}

/**
 * Crea/actualiza un término y guarda metas i18n.
 */
function fcsd_shop_upsert_term(string $key, array $def, int $parent_id): int {
    $slug_ca = sanitize_title( (string) ($def['slug']['ca'] ?? $key) );
    $name_ca = (string) ($def['name']['ca'] ?? $key);

    // Buscar por slug ca (canónico)
    $term = get_term_by('slug', $slug_ca, 'fcsd_product_cat');

    // Si es un child “duplicado” (p.ej. marxandatge-mitjons) puede existir ya el top-level con el mismo slug.
    // En ese caso, intentamos encontrarlo por meta key.
    if ( ! $term || is_wp_error($term) ) {
        $found = get_terms([
            'taxonomy'   => 'fcsd_product_cat',
            'hide_empty' => false,
            'fields'     => 'ids',
            'number'     => 1,
            'meta_query' => [
                [
                    'key'   => '_fcsd_seed_key',
                    'value' => $key,
                ],
            ],
        ]);
        if ( ! empty($found[0]) ) {
            $term = get_term((int)$found[0], 'fcsd_product_cat');
        }
    }

    if ( $term && ! is_wp_error($term) ) {
        // Asegurar parent y nombre canónico
        wp_update_term( (int)$term->term_id, 'fcsd_product_cat', [
            'name'   => $name_ca,
            'parent' => $parent_id,
        ]);
        $term_id = (int) $term->term_id;
    } else {
        $inserted = wp_insert_term( $name_ca, 'fcsd_product_cat', [
            'slug'   => $slug_ca,
            'parent' => $parent_id,
        ]);
        if ( is_wp_error($inserted) ) {
            return 0;
        }
        $term_id = (int) $inserted['term_id'];
    }

    // Guardar metas
    update_term_meta( $term_id, '_fcsd_seed_key', $key );
    foreach ( ['ca','es','en'] as $lang ) {
        if ( ! empty($def['name'][$lang]) ) {
            update_term_meta( $term_id, '_fcsd_i18n_name_' . $lang, (string) $def['name'][$lang] );
        }
        if ( ! empty($def['slug'][$lang]) ) {
            update_term_meta( $term_id, '_fcsd_i18n_slug_' . $lang, sanitize_title( (string) $def['slug'][$lang] ) );
        }
    }

    // Flag de alias “root” (p.ej. /es/calcetines)
    if ( ! empty($def['root_alias']) ) {
        update_term_meta( $term_id, '_fcsd_root_alias', '1' );
    }

    return $term_id;
}

// Ejecutar seed en activación y también en init (por si la taxonomía se registra después del switch).
add_action( 'after_switch_theme', 'fcsd_shop_seed_product_categories', 20 );
add_action( 'init', function(){
    static $done = false;
    if ( $done ) return;
    $done = true;
    // Solo en frontend/CLI. Evitar ruido en admin si no hace falta.
    fcsd_shop_seed_product_categories();
}, 30 );

/**
 * Aplica el nombre traducido al cargar términos.
 */
add_filter('get_term', function($term, $taxonomy){
    if ( ! ($term instanceof WP_Term) ) return $term;
    if ( $taxonomy !== 'fcsd_product_cat' ) return $term;
    if ( is_admin() ) return $term;

    $lang = function_exists('fcsd_lang') ? fcsd_lang() : ( defined('FCSD_LANG') ? FCSD_LANG : 'ca' );
    if ( $lang === FCSD_DEFAULT_LANG ) return $term;

    $name = get_term_meta( (int)$term->term_id, '_fcsd_i18n_name_' . $lang, true );
    if ( is_string($name) && $name !== '' ) {
        $term->name = $name;
    }
    return $term;
}, 20, 2);

/**
 * Traduce el slug en los enlaces de términos.
 */
add_filter('term_link', function($url, $term, $taxonomy){
    if ( $taxonomy !== 'fcsd_product_cat' ) return $url;
    if ( ! ($term instanceof WP_Term) ) return $url;
    if ( is_admin() ) return $url;

    $lang = function_exists('fcsd_lang') ? fcsd_lang() : ( defined('FCSD_LANG') ? FCSD_LANG : 'ca' );
    $shop_key = 'shop';

    $home = rtrim((string) get_option('home'), '/');

    // Slug del término en el idioma actual
    $t_slug = (string) $term->slug;
    if ( $lang !== FCSD_DEFAULT_LANG ) {
        $meta_slug = get_term_meta( (int)$term->term_id, '_fcsd_i18n_slug_' . $lang, true );
        if ( is_string($meta_slug) && $meta_slug !== '' ) {
            $t_slug = sanitize_title($meta_slug);
        }
    }

    // ¿Debe poder accederse sin /shop?
    $root_alias = (string) get_term_meta( (int)$term->term_id, '_fcsd_root_alias', true );
    if ( $root_alias === '1' ) {
        // /{lang?}/{slug}/
        if ( $lang === FCSD_DEFAULT_LANG ) {
            return $home . '/' . $t_slug . '/';
        }
        return $home . '/' . $lang . '/' . $t_slug . '/';
    }

    // Base de tienda por idioma
    $shop_base = function_exists('fcsd_slug') ? fcsd_slug($shop_key, $lang) : ( function_exists('fcsd_default_slug') ? fcsd_default_slug($shop_key) : 'botiga' );

    // Montar URL: /{lang?}/{shop_base}/{slug}/
    if ( $lang === FCSD_DEFAULT_LANG ) {
        return $home . '/' . trim($shop_base, '/') . '/' . $t_slug . '/';
    }
    return $home . '/' . $lang . '/' . trim($shop_base, '/') . '/' . $t_slug . '/';
}, 20, 3);

/**
 * Helper: encontrar un término por slug traducido (termmeta).
 */
function fcsd_shop_find_term_by_translated_slug(string $maybe_slug, string $lang): ?WP_Term {
    $maybe_slug = sanitize_title($maybe_slug);
    if ( $maybe_slug === '' ) return null;

    // 1) Intento normal (por slug canónico)
    $term = get_term_by('slug', $maybe_slug, 'fcsd_product_cat');
    if ( $term && ! is_wp_error($term) ) return $term;

    // 2) Meta lookup
    $found = get_terms([
        'taxonomy'   => 'fcsd_product_cat',
        'hide_empty' => false,
        'fields'     => 'ids',
        'number'     => 1,
        'meta_query' => [
            [
                'key'   => '_fcsd_i18n_slug_' . $lang,
                'value' => $maybe_slug,
            ],
        ],
    ]);
    if ( ! empty($found[0]) ) {
        $t = get_term((int)$found[0], 'fcsd_product_cat');
        if ( $t && ! is_wp_error($t) ) return $t;
    }
    return null;
}
