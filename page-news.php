<?php
/**
 * Template Name: News (Listado)
 *
 * Página/listado de noticias.
 * - Importación: se traen TODAS las noticias.
 * - Aquí: se filtra por categorías seleccionadas en "News → Página de noticias".
 * - Además: aplicamos el filtro de idioma para noticias EXIT21.
 */

defined('ABSPATH') || exit;

get_header();

$paged = max( 1, (int) ( get_query_var('paged') ?: get_query_var('page') ) );

// Idioma actual (ca|es|en)
$lang = function_exists('fcsd_lang') ? fcsd_lang() : ( defined('FCSD_LANG') ? FCSD_LANG : 'ca' );

// Meta_query de idioma para TODAS las noticias (internas + EXIT21):
// - CA: muestra CA + sin definir (legacy)
// - ES/EN: muestra solo ES/EN
$meta_query = [];
if ( $lang === 'ca' ) {
    $meta_query = [
        'relation' => 'OR',
        [ 'key' => 'news_language', 'compare' => 'NOT EXISTS' ],
        [ 'key' => 'news_language', 'value' => '', 'compare' => '=' ],
        [ 'key' => 'news_language', 'value' => 'ca', 'compare' => '=' ],
    ];
} else {
    $meta_query = [
        [ 'key' => 'news_language', 'value' => $lang, 'compare' => '=' ],
    ];
}

$allowed_cats = function_exists( 'fcsd_news_page_selected_category_slugs' )
    ? fcsd_news_page_selected_category_slugs()
    : [ 'fcsd' ];

$q = new WP_Query([
    'post_type'      => 'news',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'no_found_rows'  => false,
    'meta_query'     => $meta_query,
    // Regla de negocio:
    // - Las noticias EXIT21 solo deben aparecer si tienen la categoría 'fcsd'.
    // - Las internas (creadas a mano o importadas por XML) siempre pueden aparecer.
    // EXIT21: solo mostrar categorías permitidas (fallback: fcsd)
    'fcsd_require_exit21_fcsd' => $allowed_cats,
]);
?>

<main class="container archive-news">
  <header class="page-header">
    <h1><?php echo esc_html( fcsd_t([
        'ca' => 'Notícies',
        'es' => 'Noticias',
        'en' => 'News',
    ]) ); ?></h1>
  </header>

  <?php if ( $q->have_posts() ) : ?>
    <div class="news-grid">
      <?php while ( $q->have_posts() ) : $q->the_post(); ?>

        <?php
        /**
         * Fondo según categoría principal
         */
        $primary_cat = null;
        $bg_url      = '';

        // Imagen específica para la categoría por defecto (Sin categoría)
        $sin_cat_rel = '/assets/images/news/bg-news-sin-categoria.png';
        $sin_cat_abs = get_stylesheet_directory() . $sin_cat_rel;
        $sin_cat_url = file_exists( $sin_cat_abs ) ? ( get_stylesheet_directory_uri() . $sin_cat_rel ) : '';

        $terms = get_the_terms( get_the_ID(), 'category' );

        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
          foreach ( $terms as $term ) {
            $primary_cat = $term;

            $normalized = strtolower( remove_accents( $term->name ) );
            $normalized = preg_replace( '/[^a-z0-9]+/', '-', $normalized );
            $normalized = trim( $normalized, '-' );

            // Caso especial: SIN CATEGORÍA / Uncategorized
            if ( $sin_cat_url && in_array( $normalized, [ 'sin-categoria', 'uncategorized', 'sense-categoria' ], true ) ) {
              $bg_url = $sin_cat_url;
              break;
            }

            $relative_path = '/assets/images/news/bg-news-' . $normalized . '.png';
            $absolute_path = get_stylesheet_directory() . $relative_path;

            if ( file_exists( $absolute_path ) ) {
              $bg_url = get_stylesheet_directory_uri() . $relative_path;
              break;
            }
          }
        }

        // Si no hay términos, tratamos la noticia como "Sin categoría"
        if ( ( empty( $terms ) || is_wp_error( $terms ) ) && $sin_cat_url ) {
          $primary_cat = (object) [ 'name' => __( 'Sin categoría', 'fcsd' ) ];
          $bg_url = $sin_cat_url;
        }

        if ( ! $bg_url ) {
          $default_rel = '/assets/images/news/bg-news-default.png';
          $default_abs = get_stylesheet_directory() . $default_rel;

          if ( file_exists( $default_abs ) ) {
            $bg_url = get_stylesheet_directory_uri() . $default_rel;
          }
        }
        ?>

        <?php
        $news_source     = (string) get_post_meta( get_the_ID(), 'news_source', true );
        $is_xml_imported = (bool) get_post_meta( get_the_ID(), '_fcsd_imported_xml', true );

        // Noticias internas importadas vía XML: ocultar cabecera superpuesta.
        $no_header     = ( $is_xml_imported && $news_source !== 'exit21' );
        $extra_classes = $no_header ? ' news-card--xml-import' : '';
        ?>

        <article <?php post_class( 'news-card' . $extra_classes ); ?>>

          <a href="<?php the_permalink(); ?>"
             class="news-card__thumb"
             aria-label="<?php the_title_attribute(); ?>">

            <?php if ( ! $no_header ) : ?>
              <div class="news-card__header"
                   <?php if ( $bg_url ) : ?>
                     style="background-image:url('<?php echo esc_url( $bg_url ); ?>');"
                   <?php endif; ?>>
                <?php if ( $primary_cat ) : ?>
                  <span class="news-card__cat">
                    <?php echo esc_html( $primary_cat->name ); ?>
                  </span>
                <?php endif; ?>

                <h2 class="news-card__title">
                  <?php the_title(); ?>
                </h2>
              </div>
            <?php endif; ?>
            <?php if ( has_post_thumbnail() ) : ?>
              <?php the_post_thumbnail(
                'medium_large',
                [ 'class' => 'news-card__img', 'loading' => 'lazy' ]
              ); ?>
            <?php else : ?>
              <div class="news-card__img news-card__img--placeholder"></div>
            <?php endif; ?>
          </a>

          <div class="news-card__body">
            <div class="news-card__excerpt">
              <?php the_excerpt(); ?>
            </div>

            <a class="news-card__more" href="<?php the_permalink(); ?>">
              <?php esc_html_e( 'Llegir-ne més', 'fcsd' ); ?>
            </a>
          </div>

        </article>

      <?php endwhile; ?>
    </div>

    <?php
    echo paginate_links([
        'total'   => (int) $q->max_num_pages,
        'current' => (int) $paged,
        'mid_size'=> 2,
        'prev_text' => '← ' . __( 'Anterior', 'fcsd' ),
        'next_text' => __( 'Següent', 'fcsd' ) . ' →',
    ]);
    ?>

  <?php else : ?>
    <p><?php echo esc_html( fcsd_t([
        'ca' => 'No hi ha notícies.',
        'es' => 'No hay noticias.',
        'en' => 'There are no news items.',
    ]) ); ?></p>
  <?php endif; ?>

  <?php wp_reset_postdata(); ?>
</main>

<?php
get_footer();
