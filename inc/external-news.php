<?php
/**
 * FCSD – Página /actualitat
 * - Miniatura a la derecha (featured, o 1ª imagen del contenido, o meta 'news_image_src')
 * - Etiqueta con categoría (círculo + nombre) y color estable por categoría
 * - Paginación
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Meta_query de idioma para el frontend:
 * - En CA (por defecto): muestra CA + sin definir (para no ocultar contenido legacy/interno).
 * - En ES: muestra solo ES.
 */
function fcsd_news_frontend_lang_meta_query(): array {
    $lang = function_exists('fcsd_lang') ? fcsd_lang() : ( defined('FCSD_LANG') ? FCSD_LANG : 'ca' );
    $lang = strtolower((string) $lang);

    // En frontend, cada home y listados públicos deben mostrar SOLO noticias del idioma activo.
    // Regla (alineada con page-news.php):
    // - CA: muestra CA + sin definir (legacy)
    // - ES/EN: muestra solo ES/EN
    if ( $lang === 'ca' ) {
        return [
            'relation' => 'OR',
            [ 'key' => 'news_language', 'compare' => 'NOT EXISTS' ],
            [ 'key' => 'news_language', 'value' => '', 'compare' => '=' ],
            [ 'key' => 'news_language', 'value' => 'ca', 'compare' => '=' ],
        ];
    }

    if ( in_array( $lang, [ 'es', 'en' ], true ) ) {
        return [
            [ 'key' => 'news_language', 'value' => $lang, 'compare' => '=' ],
        ];
    }

    // Fallback seguro
    return [
        [ 'key' => 'news_language', 'value' => 'ca', 'compare' => '=' ],
    ];
}

/**
 * Aplica el filtro de idioma a:
 * - Archivo del CPT "news" (archive-news.php / is_post_type_archive).
 * - Consultas principales de noticias en el frontend.
 */
add_action( 'pre_get_posts', function ( $query ) {
    if ( is_admin() || ! ( $query instanceof WP_Query ) ) {
        return;
    }
    if ( ! $query->is_main_query() ) {
        return;
    }

    // Archivo de noticias: limitar por idioma/fuente.
    if ( $query->is_post_type_archive( 'news' ) ) {
        $meta_query = fcsd_news_frontend_lang_meta_query();
        $query->set( 'meta_query', $meta_query );

        // En el listado/página de noticias SOLO mostramos las categorías seleccionadas
        // en "News → Página de noticias" (fallback: fcsd).
        $slugs = function_exists( 'fcsd_news_page_selected_category_slugs' )
            ? fcsd_news_page_selected_category_slugs()
            : [ 'fcsd' ];

        $query->set( 'tax_query', [
            [
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => $slugs,
            ],
        ] );
    }
}, 20 );

/**
 * Regla de negocio:
 * - Las noticias con meta news_source = 'exit21' solo deben aparecer en el
 *   listado público si además pertenecen a la categoría 'fcsd'.
 *
 * En admin se muestran todas (sin filtrar).
 *
 * Implementación: si una WP_Query lleva el flag "fcsd_require_exit21_fcsd",
 * añadimos una condición SQL con un EXISTS sobre la taxonomía category.
 */
add_filter( 'posts_clauses', function( $clauses, $query ) {
    if ( is_admin() || ! ( $query instanceof WP_Query ) ) {
        return $clauses;
    }

    // El flag puede ser:
    // - true/1: requiere categoría 'fcsd'
    // - array de slugs: permite esas categorías (fallback: fcsd)
    $flag = $query->get( 'fcsd_require_exit21_fcsd' );
    if ( ! $flag ) {
        return $clauses;
    }

    $allowed_slugs = [];
    if ( is_array( $flag ) ) {
        $allowed_slugs = array_values( array_filter( array_map( 'sanitize_title', $flag ) ) );
    }
    if ( empty( $allowed_slugs ) ) {
        // Compatibilidad: comportamiento anterior
        $allowed_slugs = [ 'fcsd' ];
    }

    global $wpdb;

    // Join al meta news_source para poder discriminar EXIT21.
    if ( strpos( $clauses['join'], 'fcsd_ns.meta_key' ) === false ) {
        $clauses['join'] .= "\nLEFT JOIN {$wpdb->postmeta} AS fcsd_ns ON (fcsd_ns.post_id = {$wpdb->posts}.ID AND fcsd_ns.meta_key = 'news_source')\n";
    }

    // Subquery: post pertenece a alguna de las categorías permitidas.
    $in = "('" . implode( "','", array_map( 'esc_sql', $allowed_slugs ) ) . "')";

    $exists_allowed = "EXISTS (
        SELECT 1
        FROM {$wpdb->term_relationships} tr
        INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
        INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
        WHERE tr.object_id = {$wpdb->posts}.ID
          AND tt.taxonomy = 'category'
          AND t.slug IN {$in}
    )";

    // Condición:
    // - Si NO es exit21 -> OK
    // - Si es exit21 -> requiere categoría fcsd
    $clauses['where'] .= "\nAND ( (fcsd_ns.meta_value IS NULL OR fcsd_ns.meta_value = '' OR fcsd_ns.meta_value <> 'exit21') OR {$exists_allowed} )\n";

    return $clauses;
}, 20, 2 );


/** Tamaño consistente para miniaturas del listado */
add_action('after_setup_theme', function () {
    if ( function_exists('add_image_size') ) {
        add_image_size('news-thumb', 240, 160, true);
    }
});

/** HTML de miniatura con fallback a meta 'news_image_src' */
function fcsd_news_thumb_html( $post_id, $size = 'news-thumb' ) {
    // 1) Destacada
    if ( has_post_thumbnail( $post_id ) ) {
        return get_the_post_thumbnail(
            $post_id,
            $size,
            [ 'class' => 'news-thumb', 'loading' => 'lazy', 'decoding' => 'async', 'aria-hidden' => 'true' ]
        );
    }

    // 2) 1ª <img> del contenido
    $content = get_post_field( 'post_content', $post_id );
    if ( $content && preg_match( '/<img[^>]+src=["\']([^"\']+)/i', $content, $m ) ) {
        $src = esc_url( $m[1] );
        $alt = esc_attr( get_the_title( $post_id ) );
        return '<img class="news-thumb" src="' . $src . '" alt="' . $alt . '" loading="lazy" decoding="async" aria-hidden="true" />';
    }

    // 3) Fallback: meta guardada durant la importació
    $src = get_post_meta( $post_id, 'news_image_src', true );
    if ( $src ) {
        $src = esc_url( $src );
        $alt = esc_attr( get_the_title( $post_id ) );
        return '<img class="news-thumb" src="' . $src . '" alt="' . $alt . '" loading="lazy" decoding="async" aria-hidden="true" />';
    }

    return '';
}

/** Término principal (category) o fallback desde meta 'news_categories_raw'. */
function fcsd_news_primary_term( $post_id ) {
    $terms = get_the_terms( $post_id, 'category' );
    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        $t = array_shift( $terms );
        return (object)[ 'name' => $t->name, 'slug' => $t->slug ];
    }
    $raw = get_post_meta( $post_id, 'news_categories_raw', true );
    if ( is_array( $raw ) && ! empty( $raw ) ) {
        $name = reset( $raw );
        return (object)[ 'name' => (string) $name, 'slug' => sanitize_title( (string) $name ) ];
    }
    if ( is_string( $raw ) && $raw !== '' ) {
        return (object)[ 'name' => $raw, 'slug' => sanitize_title( $raw ) ];
    }

    // Si no hay categoría asignada, mostramos explícitamente "Sin categoría"
    // (esto además permite aplicar estilos coherentes en el listado).
    return (object)[ 'name' => __( 'Sin categoría', 'fcsd' ), 'slug' => 'sin-categoria' ];
}

/** Color HSL determinista por categoría (pastel). Devuelve [h,s,l]. */
function fcsd_color_from_category( $name ) {
    $hash = sprintf('%u', crc32( wp_strip_all_tags( (string) $name ) ));
    $h = (int) ($hash % 360);
    $s = 70;
    $l = 90;
    return [ $h, $s, $l ];
}

/** Chip de categoría (círculo + nombre). */
function fcsd_news_category_badge_html( $post_id ) {
    $term = fcsd_news_primary_term( $post_id );
    if ( ! $term || empty( $term->name ) ) return '';
    return '<span class="news-badge"><span class="news-badge__dot" aria-hidden="true"></span>' . esc_html( $term->name ) . '</span>';
}

// Nota: antes existía una página "Actualitat" que incrustaba el listado vía the_content.
// Ya no se usa. El listado vive en una única página (slug: noticies) con template page-news.php.
