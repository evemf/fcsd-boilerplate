<?php
get_header();
?>

<div class="container content py-5 archive-services">
  <h1 class="mb-4"><?php post_type_archive_title(); ?></h1>

  <?php if ( have_posts() ) : ?>
    <div class="row g-4">
      <?php while ( have_posts() ) : the_post(); ?>

        <?php
        // Término principal de la taxonomía service_area
        $areas        = get_the_terms( get_the_ID(), 'service_area' );
        $primary_area = ( ! empty( $areas ) && ! is_wp_error( $areas ) ) ? $areas[0] : null;

        // Imagen de fondo según ámbito
        $bg_url = fcsd_get_service_area_bg_image_url( get_the_ID() );
        ?>

        <div class="col-12 col-md-6 col-lg-4">
          <article id="post-<?php the_ID(); ?>" <?php post_class( 'card h-100 service-card' ); ?>>

            <div class="service-card__header"
              <?php if ( $bg_url ) : ?>
                style="background-image:url('<?php echo esc_url( $bg_url ); ?>');"
              <?php endif; ?>
            >
              <?php if ( $primary_area ) : ?>
                <span class="service-card__area">
                  <?php echo esc_html( $primary_area->name ); ?>
                </span>
              <?php endif; ?>

              <h2 class="h5 service-card__title">
                <a href="<?php the_permalink(); ?>">
                  <?php the_title(); ?>
                </a>
              </h2>
            </div>

            <?php if ( has_post_thumbnail() ) : ?>
              <div class="card-img-top service-card__thumb">
                <?php the_post_thumbnail( 'medium_large', [ 'class' => 'img-fluid' ] ); ?>
              </div>
            <?php endif; ?>

            <div class="card-body service-card__body">
              <p class="card-text">
                <?php echo wp_trim_words( get_the_excerpt(), 20 ); ?>
              </p>
            </div>

          </article>
        </div>

      <?php endwhile; ?>
    </div>

    <?php the_posts_pagination(); ?>

  <?php else : ?>
    <p><?php _e( 'No hi ha serveis encara.', 'fcsd' ); ?></p>
  <?php endif; ?>
</div>

<?php
get_footer();
