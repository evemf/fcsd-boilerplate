<?php
/**
 * Template Name: Patronat
 */

get_header();
?>

<main class="container content py-5 page-patronat">

  <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
    <header class="mb-4">
      <h1 class="mb-3"><?php the_title(); ?></h1>

      <?php if ( get_the_content() ) : ?>
        <div class="page-intro mb-4">
          <?php the_content(); ?>
        </div>
      <?php endif; ?>
    </header>
  <?php endwhile; endif; ?>

  <?php
  // Query de persones del Patronat
  $persones = new WP_Query( array(
      'post_type'      => 'persona',
      'posts_per_page' => -1,
      'orderby'        => array(
          'menu_order' => 'ASC',
          'title'      => 'ASC',
      ),
  ) );
  ?>

  <?php if ( $persones->have_posts() ) : ?>
    <div class="row g-4 patronat-grid">
      <?php while ( $persones->have_posts() ) : $persones->the_post();

        $cargo       = get_post_meta( get_the_ID(), 'persona_cargo', true );
        $linkedin    = get_post_meta( get_the_ID(), 'persona_linkedin', true );
        $otros_links = get_post_meta( get_the_ID(), 'persona_otros_links', true );
        ?>
        <div class="col-md-6 col-lg-4">
          <article <?php post_class( 'card h-100 patronat-card' ); ?>>
            <div class="d-flex justify-content-between align-items-start">
                <div class="me-3 flex-grow-1">
                    <h2 class="h5 mb-1"><?php the_title(); ?></h2>

                    <?php if ( $cargo ) : ?>
                    <p class="small text-muted mb-2">
                        <?php echo esc_html( $cargo ); ?>
                    </p>
                    <?php endif; ?>

                    <?php if ( has_excerpt() ) : ?>
                    <p class="mb-2">
                        <?php echo esc_html( get_the_excerpt() ); ?>
                    </p>
                    <?php endif; ?>

                    <div class="patronat-links mt-2">
                    <?php if ( $linkedin ) : ?>
                        <a href="<?php echo esc_url( $linkedin ); ?>"
                        class="btn btn-sm btn-outline-primary me-1 mb-1 patronat-linkedin"
                        target="_blank" rel="noopener">
                        <span class="visually-hidden">LinkedIn</span>
                        <i class="bi bi-linkedin" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>

                    <?php
                    if ( $otros_links ) :
                        $links = preg_split( '/\r\n|\r|\n/', $otros_links );
                        foreach ( $links as $link ) :
                        $link = trim( $link );
                        if ( ! $link ) {
                            continue;
                        }
                        ?>
                        <a href="<?php echo esc_url( $link ); ?>"
                            class="btn btn-sm btn-outline-secondary me-1 mb-1"
                            target="_blank" rel="noopener">
                            <?php echo esc_html( parse_url( $link, PHP_URL_HOST ) ?: $link ); ?>
                        </a>
                        <?php
                        endforeach;
                    endif;
                    ?>
                    </div>
                </div>

                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="patronat-avatar ms-2">
                    <?php if ( $linkedin ) : ?>
                        <a href="<?php echo esc_url( $linkedin ); ?>"
                        target="_blank"
                        rel="noopener"
                        aria-label="<?php printf( esc_attr__( 'Perfil de LinkedIn de %s', 'fcsd' ), get_the_title() ); ?>">
                    <?php endif; ?>

                    <?php the_post_thumbnail( 'thumbnail', array(
                        'class' => 'rounded-circle img-fluid patronat-avatar-img',
                        'alt'   => get_the_title(),
                    ) ); ?>

                    <?php if ( $linkedin ) : ?>
                        </a>
                    <?php endif; ?>
                    </div>
                <?php endif; ?>

                </div>
          </article>
        </div>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>
  <?php else : ?>

    <p><?php _e( "Encara no hi ha membres del Patronat definits.", 'fcsd' ); ?></p>

  <?php endif; ?>

</main>

<?php
get_footer();
