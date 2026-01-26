<?php
get_header();
?>

<main class="container content archive-events py-5">
    <header class="page-header">
        <h1 class="mb-4">
            <?php esc_html_e( 'Formacions i esdeveniments', 'fcsd' ); ?>
        </h1>
    </header>

    <?php if ( have_posts() ) : ?>
        <div class="news-grid events-grid">
            <?php
            $shown_formations = [];

            while ( have_posts() ) :
                the_post();

                $post_id = get_the_ID();

                $terms_ids = wp_get_post_terms( $post_id, 'event_formation', [ 'fields' => 'ids' ] );

                if ( ! empty( $terms_ids ) && ! is_wp_error( $terms_ids ) ) {
                    $key = 't_' . $terms_ids[0];
                } else {
                    $key = 'p_' . $post_id;
                }

                if ( isset( $shown_formations[ $key ] ) ) {
                    continue;
                }
                $shown_formations[ $key ] = true;

                $event_start = get_post_meta( $post_id, 'fcsd_event_start', true );
                $event_end   = get_post_meta( $post_id, 'fcsd_event_end', true );
                $event_price = get_post_meta( $post_id, 'fcsd_event_price', true );

                $formations = wp_get_post_terms( $post_id, 'event_formation' );
                ?>

                <article <?php post_class( 'news-card event-card' ); ?>>
                    <a href="<?php the_permalink(); ?>"
                       class="news-card__thumb"
                       aria-label="<?php the_title_attribute(); ?>">

                        <div class="news-card__header">
                            <?php if ( ! empty( $formations ) && ! is_wp_error( $formations ) ) : ?>
                                <span class="news-card__cat">
                                    <?php echo esc_html( $formations[0]->name ); ?>
                                </span>
                            <?php endif; ?>

                            <h2 class="news-card__title">
                                <?php the_title(); ?>
                            </h2>
                        </div>

                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php the_post_thumbnail(
                                'medium_large',
                                [
                                    'class'   => 'news-card__img',
                                    'loading' => 'lazy',
                                ]
                            ); ?>
                        <?php else : ?>
                            <div class="news-card__img event-card__img--placeholder"></div>
                        <?php endif; ?>
                    </a>

                    <div class="news-card__body">
                        <ul class="event-card__meta">
                            <?php if ( ! empty( $event_start ) ) : ?>
                                <li class="event-card__meta-item">
                                    <span class="event-card__meta-label">
                                        <?php esc_html_e( 'Data inici', 'fcsd' ); ?>
                                    </span>
                                    <span class="event-card__meta-value">
                                        <?php echo esc_html( $event_start ); ?>
                                    </span>
                                </li>
                            <?php endif; ?>

                            <?php if ( ! empty( $event_end ) ) : ?>
                                <li class="event-card__meta-item">
                                    <span class="event-card__meta-label">
                                        <?php esc_html_e( 'Data fi', 'fcsd' ); ?>
                                    </span>
                                    <span class="event-card__meta-value">
                                        <?php echo esc_html( $event_end ); ?>
                                    </span>
                                </li>
                            <?php endif; ?>

                            <?php if ( ! empty( $event_price ) ) : ?>
                                <li class="event-card__meta-item">
                                    <span class="event-card__meta-label">
                                        <?php esc_html_e( 'Preu', 'fcsd' ); ?>
                                    </span>
                                    <span class="event-card__meta-value">
                                        <?php echo esc_html( $event_price ); ?>
                                    </span>
                                </li>
                            <?php endif; ?>
                        </ul>

                        <div class="news-card__excerpt">
                            <?php
                            echo wp_kses_post(
                                wp_trim_words(
                                    get_the_excerpt() ?: get_the_content(),
                                    25
                                )
                            );
                            ?>
                        </div>

                        <a class="news-card__more event-card__cta" href="<?php the_permalink(); ?>">
                            <?php esc_html_e( 'Inscriu-te', 'fcsd' ); ?>
                        </a>
                    </div>
                </article>

            <?php endwhile; ?>
        </div>

        <div class="mt-4">
            <?php the_posts_pagination(); ?>
        </div>

    <?php else : ?>
        <p><?php esc_html_e( 'Ara mateix no hi ha esdeveniments actius.', 'fcsd' ); ?></p>
    <?php endif; ?>
</main>

<?php
get_footer();
