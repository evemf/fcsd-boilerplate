<?php
get_header();
?>

<main class="container content archive-events py-5">
    <header class="page-header">
        <h1 class="mb-4">
            <?php esc_html_e( 'Formacions i esdeveniments', 'fcsd' ); ?>
        </h1>
    </header>

    <?php
    $formation_terms = get_terms( [
        'taxonomy'   => 'event_formation',
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ] );
    ?>

    <section class="events-filters" aria-label="<?php echo esc_attr__( 'Filtres', 'fcsd' ); ?>">
        <div class="events-filters__row">
            <div class="events-filters__search">
                <label class="visually-hidden" for="events-search">
                    <?php esc_html_e( 'Cerca', 'fcsd' ); ?>
                </label>
                <input
                    id="events-search"
                    type="search"
                    inputmode="search"
                    placeholder="<?php echo esc_attr__( 'Cerca formacions…', 'fcsd' ); ?>"
                    aria-label="<?php echo esc_attr__( 'Cerca formacions', 'fcsd' ); ?>"
                    data-events-search
                />
            </div>
        </div>

        <div class="events-filters__chips" role="group" aria-label="<?php echo esc_attr__( 'Categories', 'fcsd' ); ?>">
            <button type="button" class="events-chip events-chip--all is-active" data-events-term="0" aria-pressed="true">
                <span class="events-chip__circle" aria-hidden="true">
                    <span class="events-chip__media">
                        <span class="fcsd-term-icon">•</span>
                    </span>
                </span>
                <p class="events-chip__label"><?php esc_html_e( 'Totes', 'fcsd' ); ?></p>
            </button>

            <?php if ( ! empty( $formation_terms ) && ! is_wp_error( $formation_terms ) ) : ?>
                <?php foreach ( $formation_terms as $t ) : ?>
                    <button type="button" class="events-chip" data-events-term="<?php echo (int) $t->term_id; ?>" aria-pressed="false">
                        <span class="events-chip__circle" aria-hidden="true">
                            <span class="events-chip__media">
                                <?php echo wp_kses_post( fcsd_event_formation_media_html( $t ) ); ?>
                            </span>
                        </span>
                        <p class="events-chip__label">
                            <?php echo esc_html( fcsd_event_formation_i18n_field( $t, 'name' ) ); ?>
                        </p>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <?php if ( have_posts() ) : ?>
        <div class="news-grid events-grid" data-max-pages="<?php echo (int) $GLOBALS['wp_query']->max_num_pages; ?>">
            <?php
            while ( have_posts() ) :
                the_post();
                fcsd_render_event_card( get_the_ID() );
            endwhile;
            ?>
        </div>

        <div class="events-empty" style="display:none;">
            <?php esc_html_e( 'No s\'han trobat resultats amb aquests filtres.', 'fcsd' ); ?>
        </div>

        <div class="events-loading" aria-live="polite">
            <?php esc_html_e( 'Carregant…', 'fcsd' ); ?>
        </div>

        <div class="events-load-more">
            <button type="button" data-events-load-more>
                <?php esc_html_e( 'Carregar més', 'fcsd' ); ?>
            </button>
        </div>

        <div class="events-sentinel" aria-hidden="true"></div>

        <div class="events-pagination mt-4">
            <?php the_posts_pagination(); ?>
        </div>

    <?php else : ?>
        <p><?php esc_html_e( 'Ara mateix no hi ha esdeveniments actius.', 'fcsd' ); ?></p>
    <?php endif; ?>
</main>

<?php
get_footer();
