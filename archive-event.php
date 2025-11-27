<?php
get_header();
?>

<div class="container content py-5">
    <h1 class="mb-4">
        <?php esc_html_e( 'Formacions i esdeveniments', 'fcsd' ); ?>
    </h1>

    <?php if ( have_posts() ) : ?>
        <div class="row g-4">
            <?php while ( have_posts() ) : the_post(); ?>
                <div class="col-md-4">
                    <article <?php post_class( 'event-card h-100' ); ?>>
                        <a href="<?php the_permalink(); ?>" class="text-decoration-none">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <div class="mb-3">
                                    <?php the_post_thumbnail( 'medium_large', [ 'class' => 'img-fluid' ] ); ?>
                                </div>
                            <?php endif; ?>

                            <h2 class="h5 mb-2"><?php the_title(); ?></h2>

                            <?php
                            $start = get_post_meta( get_the_ID(), 'fcsd_event_start', true );
                            if ( $start ) :
                                ?>
                                <p class="text-muted mb-1">
                                    <?php echo esc_html( $start ); ?>
                                </p>
                            <?php endif; ?>

                            <p class="mb-0">
                                <?php echo wp_trim_words( get_the_excerpt() ?: get_the_content(), 20 ); ?>
                            </p>
                        </a>
                    </article>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="mt-4">
            <?php the_posts_pagination(); ?>
        </div>
    <?php else : ?>
        <p><?php esc_html_e( 'Ara mateix no hi ha esdeveniments actius.', 'fcsd' ); ?></p>
    <?php endif; ?>
</div>

<?php
get_footer();
