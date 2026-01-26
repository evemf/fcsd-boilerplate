<?php
/**
 * Template for Service Areas taxonomy archives (service_area)
 */

get_header();

$term = get_queried_object();
?>

<div class="container content py-5 archive-services archive-service-area">
  <header class="mb-4">
    <p class="text-muted mb-1"><?php echo esc_html__( 'Àmbit', 'fcsd' ); ?></p>
    <h1 class="mb-2"><?php single_term_title(); ?></h1>
    <?php if ( ! empty( $term->description ) ) : ?>
      <div class="text-muted"><?php echo wp_kses_post( wpautop( $term->description ) ); ?></div>
    <?php endif; ?>
  </header>

  <?php if ( have_posts() ) : ?>
    <div class="row g-4">
      <?php while ( have_posts() ) : the_post(); ?>

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

              <h2 class="h5 service-card__title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
              </h2>
            </div>

            <?php if ( has_post_thumbnail() ) : ?>
              <div class="card-img-top service-card__thumb">
                <?php the_post_thumbnail( 'medium_large', [ 'class' => 'img-fluid' ] ); ?>
              </div>
            <?php endif; ?>

            <div class="card-body service-card__body">
              <p class="card-text"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
            </div>

          </article>
        </div>

      <?php endwhile; ?>
    </div>

    <?php the_posts_pagination(); ?>

  <?php else : ?>
    <p class="text-muted mb-0"><?php echo esc_html__( 'No hi ha serveis en aquest àmbit encara.', 'fcsd' ); ?></p>
  <?php endif; ?>
</div>

<?php
get_footer();
