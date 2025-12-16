<?php
get_header();
?>
<main class="container archive-news">
  <header class="page-header">
    <h1><?php post_type_archive_title(); ?></h1>
  </header>

  <?php if ( have_posts() ) : ?>
    <div class="news-grid">
      <?php while ( have_posts() ) : the_post(); ?>

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
          $primary_cat = (object) [ 'name' => __( 'Sin categoría', 'tu-textdomain' ) ];
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
              <?php esc_html_e( 'Llegir-ne més', 'tu-textdomain' ); ?>
            </a>
          </div>

        </article>

      <?php endwhile; ?>
    </div>

    <?php
    the_posts_pagination( [
      'mid_size'  => 2,
      'prev_text' => '← ' . __( 'Anterior', 'tu-textdomain' ),
      'next_text' => __( 'Següent', 'tu-textdomain' ) . ' →',
    ] );
    ?>

  <?php else : ?>
    <p><?php esc_html_e( 'No hi ha notícies.', 'tu-textdomain' ); ?></p>
  <?php endif; ?>
</main>
<?php
get_footer();
