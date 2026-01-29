<?php
/**
 * Taxonomy archive: service_area
 *
 * Requisit:
 * - En fer click a un àmbit, mostrar:
 *   1) Serveis d'aquest àmbit
 *   2) Notícies d'aquest mateix àmbit (amb el mateix format)
 */

defined('ABSPATH') || exit;

get_header();

$term = get_queried_object();
if ( ! $term || is_wp_error( $term ) ) : ?>
  <main class="container content py-5">
    <p><?php esc_html_e( 'Àmbit no trobat.', 'fcsd' ); ?></p>
  </main>
  <?php get_footer();
  return;
endif;

// ----------------------------
// Query: Serveis d'aquest àmbit
// ----------------------------
$services = new WP_Query([
  'post_type'      => 'service',
  'posts_per_page' => -1,
  'no_found_rows'  => true,
  'tax_query'      => [[
    'taxonomy' => 'service_area',
    'field'    => 'term_id',
    'terms'    => (int) $term->term_id,
  ]],
  'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
]);

// ----------------------------
// Query: Notícies d'aquest àmbit
// ----------------------------
$paged = max( 1, (int) get_query_var( 'paged' ) );
$news  = new WP_Query([
  'post_type'      => 'news',
  'posts_per_page' => 12,
  'paged'          => $paged,
  'tax_query'      => [[
    'taxonomy' => 'service_area',
    'field'    => 'term_id',
    'terms'    => (int) $term->term_id,
  ]],
  'orderby'        => 'date',
  'order'          => 'DESC',
]);
?>

<main class="container content py-5 taxonomy-service-area">

  <header class="page-header mb-4">
    <h1><?php echo esc_html( single_term_title( '', false ) ); ?></h1>
    <?php if ( ! empty( $term->description ) ) : ?>
      <div class="taxonomy-description">
        <?php echo wp_kses_post( wpautop( $term->description ) ); ?>
      </div>
    <?php endif; ?>
  </header>

  <section class="taxonomy-service-area__services mb-5" aria-label="<?php echo esc_attr__( 'Serveis', 'fcsd' ); ?>">
    <h2 class="mb-4"><?php esc_html_e( 'Serveis', 'fcsd' ); ?></h2>

    <?php if ( $services->have_posts() ) : ?>
      <div class="row g-4">
        <?php while ( $services->have_posts() ) : $services->the_post(); ?>
          <?php
          $areas        = get_the_terms( get_the_ID(), 'service_area' );
          $primary_area = ( ! empty( $areas ) && ! is_wp_error( $areas ) ) ? $areas[0] : null;

          $area_data = function_exists( 'fcsd_get_service_area_for_post' )
            ? fcsd_get_service_area_for_post( get_the_ID() )
            : null;

          $header_style = '';
          if ( $area_data && ! empty( $area_data['hero_images'] ) && is_array( $area_data['hero_images'] ) ) {
            $imgs = array_values( array_filter( $area_data['hero_images'] ) );
            if ( count( $imgs ) === 1 ) {
              $header_style = 'background-image:url(' . esc_url( $imgs[0] ) . ');';
            } elseif ( count( $imgs ) >= 2 ) {
              $header_style = sprintf(
                'background-image:url(%1$s),url(%2$s);background-size:50%% 100%%,50%% 100%%;background-position:left center,right center;background-repeat:no-repeat,no-repeat;',
                esc_url( $imgs[0] ),
                esc_url( $imgs[1] )
              );
            }
          }
          ?>

          <div class="col-12 col-md-6 col-lg-4">
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'card h-100 service-card' ); ?>>

              <div class="service-card__header"<?php echo $header_style ? ' style="' . esc_attr( $header_style ) . '"' : ''; ?>>
                <?php if ( $primary_area ) : ?>
                  <span class="service-card__area"><?php echo esc_html( $primary_area->name ); ?></span>
                <?php endif; ?>

                <h3 class="h5 service-card__title">
                  <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>
              </div>

              <?php if ( has_post_thumbnail() ) : ?>
                <div class="card-img-top service-card__thumb">
                  <?php the_post_thumbnail( 'medium_large', [ 'class' => 'img-fluid' ] ); ?>
                </div>
              <?php endif; ?>

            </article>
          </div>

        <?php endwhile; wp_reset_postdata(); ?>
      </div>

    <?php else : ?>
      <p><?php esc_html_e( 'No hi ha serveis per aquest àmbit.', 'fcsd' ); ?></p>
    <?php endif; ?>
  </section>


  <section class="taxonomy-service-area__news" aria-label="<?php echo esc_attr__( 'Notícies', 'fcsd' ); ?>">
    <h2 class="mb-4"><?php esc_html_e( 'Notícies', 'fcsd' ); ?></h2>

    <?php if ( $news->have_posts() ) : ?>
      <div class="news-grid">
        <?php while ( $news->have_posts() ) : $news->the_post(); ?>

          <?php
          // Igual que archive-news.php
          $primary_cat = null;
          $bg_url      = '';

          $sin_cat_rel = '/assets/images/news/bg-news-sin-categoria.png';
          $sin_cat_abs = get_stylesheet_directory() . $sin_cat_rel;
          $sin_cat_url = file_exists( $sin_cat_abs ) ? ( get_stylesheet_directory_uri() . $sin_cat_rel ) : '';

          $terms = get_the_terms( get_the_ID(), 'category' );

          if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            foreach ( $terms as $t ) {
              $primary_cat = $t;

              $normalized = strtolower( remove_accents( $t->name ) );
              $normalized = preg_replace( '/[^a-z0-9]+/', '-', $normalized );
              $normalized = trim( $normalized, '-' );

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

          <article <?php post_class( 'news-card' ); ?>>

            <a href="<?php the_permalink(); ?>"
               class="news-card__thumb"
               aria-label="<?php the_title_attribute(); ?>">

              <div class="news-card__header"<?php echo $bg_url ? ' style="background-image:url(\'' . esc_url( $bg_url ) . '\');"' : ''; ?>>
                <?php if ( $primary_cat ) : ?>
                  <span class="news-card__cat"><?php echo esc_html( $primary_cat->name ); ?></span>
                <?php endif; ?>

                <h3 class="news-card__title"><?php the_title(); ?></h3>
              </div>

              <?php if ( has_post_thumbnail() ) : ?>
                <?php the_post_thumbnail( 'medium_large', [ 'class' => 'news-card__img', 'loading' => 'lazy' ] ); ?>
              <?php else : ?>
                <div class="news-card__img news-card__img--placeholder"></div>
              <?php endif; ?>
            </a>

            <div class="news-card__body">
              <div class="news-card__excerpt"><?php the_excerpt(); ?></div>
              <a class="news-card__more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Llegir-ne més', 'fcsd' ); ?></a>
            </div>

          </article>

        <?php endwhile; wp_reset_postdata(); ?>
      </div>

      <?php
      the_posts_pagination( [
        'mid_size'  => 2,
        'prev_text' => '← ' . __( 'Anterior', 'fcsd' ),
        'next_text' => __( 'Següent', 'fcsd' ) . ' →',
      ] );
      ?>

    <?php else : ?>
      <p><?php echo esc_html( fcsd_t([
        'ca' => 'No hi ha notícies per aquest àmbit.',
        'es' => 'No hay noticias para este ámbito.',
        'en' => 'There are no news items for this area.',
    ]) ); ?></p>
    <?php endif; ?>
  </section>

</main>

<?php
get_footer();
