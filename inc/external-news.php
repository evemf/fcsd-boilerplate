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

    /**
     * Reglas:
     * - Internas (no exit21): siempre visibles (el tema traduce contenido por idioma vía meta i18n).
     * - Exit21: solo CA y ES.
     */
    $meta_query = [
        'relation' => 'OR',
        // Internas
        [
            'relation' => 'OR',
            [
                'key'     => 'news_source',
                'compare' => 'NOT EXISTS',
            ],
            [
                'key'     => 'news_source',
                'value'   => 'exit21',
                'compare' => '!=',
            ],
            [
                'key'     => 'news_source',
                'value'   => '',
                'compare' => '=',
            ],
        ],
    ];

    if ( $lang === 'ca' || $lang === 'es' ) {
        $meta_query[] = [
            'relation' => 'AND',
            [
                'key'     => 'news_source',
                'value'   => 'exit21',
                'compare' => '=',
            ],
            [
                'key'     => 'news_language',
                'value'   => $lang,
                'compare' => '=',
            ],
        ];
    }

    return $meta_query;
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
    }
}, 20 );


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

/** Render del listado dentro del contenido de la página "actualitat". */
add_filter('the_content', function( $content ) {
    if ( is_admin() || ! is_page( array_values( fcsd_slug_map()['news_page'] ) ) || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    $paged = max( 1, get_query_var('paged') ?: get_query_var('page') );
    $q = new WP_Query([
        'post_type'      => 'news',
        'post_status'    => 'publish',
        'meta_query'     => fcsd_news_frontend_lang_meta_query(),

        'posts_per_page' => 10,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => false,
    ]);

    ob_start(); ?>
    <div class="news-list">
        <?php if ( $q->have_posts() ) : ?>
            <?php while ( $q->have_posts() ) : $q->the_post(); ?>
                <?php
                    $term = fcsd_news_primary_term( get_the_ID() );
                    $cat  = $term ? $term->name : '';
                    [$h,$s,$l] = fcsd_color_from_category( $cat );
                    $style_vars = $cat ? sprintf('--cat-h:%d;--cat-s:%d%%;--cat-l:%d%%;', $h, $s, $l) : '';
                ?>
                <article <?php post_class('news-item'); ?> style="<?php echo esc_attr( $style_vars ); ?>">
                    <div class="news-item__body">
                        <h2 class="news-item__title">
                            <?php echo fcsd_news_category_badge_html( get_the_ID() ); ?>
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>

                        <time class="news-item__date" datetime="<?php echo esc_attr( get_the_date('c') ); ?>">
                            <?php echo esc_html( get_the_date() ); ?>
                        </time>

                        <div class="news-item__excerpt">
                            <?php
                            if ( has_excerpt() ) {
                                the_excerpt();
                            } else {
                                echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_content() ), 28, '…' ) );
                            }
                            ?>
                        </div>
                    </div>

                    <div class="news-item__thumb">
                        <?php echo fcsd_news_thumb_html( get_the_ID(), 'news-thumb' ); ?>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php else : ?>
            <p><?php echo esc_html( fcsd_t([
        'ca' => 'No hi ha notícies disponibles.',
        'es' => 'No hay noticias disponibles.',
        'en' => 'No news items available.',
    ]) ); ?></p>
        <?php endif; ?>
    </div>

    <?php if ( $q->max_num_pages > 1 ) : ?>
        <nav class="pagination" aria-label="<?php esc_attr_e( 'Paginació', 'fcsd' ); ?>">
            <?php
            echo paginate_links([
                'total'   => (int) $q->max_num_pages,
                'current' => (int) $paged,
            ]);
            ?>
        </nav>
    <?php endif; ?>

    <?php
    wp_reset_postdata();
    return ob_get_clean();
}, 20);
