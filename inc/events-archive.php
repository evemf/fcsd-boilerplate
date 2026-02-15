<?php
/**
 * Event archive enhancements: filters + progressive loading (no plugins).
 */

defined( 'ABSPATH' ) || exit;

// -----------------------------------------------------------------------------
// Query defaults for the archive
// -----------------------------------------------------------------------------
add_action( 'pre_get_posts', function ( WP_Query $q ) {
    if ( is_admin() || ! $q->is_main_query() ) {
        return;
    }

    if ( $q->is_post_type_archive( 'event' ) ) {
        // Keep the first paint light; the rest loads progressively.
        $q->set( 'posts_per_page', 12 );

        // Optional: order by start date meta if present; fall back to date.
        $q->set( 'meta_key', 'fcsd_event_start' );
        $q->set( 'orderby', [ 'meta_value' => 'ASC', 'date' => 'DESC' ] );
    }
} );

// -----------------------------------------------------------------------------
// Card renderer (shared by PHP first render + AJAX)
// -----------------------------------------------------------------------------
function fcsd_render_event_card( int $post_id ): void {
    $event_start = get_post_meta( $post_id, 'fcsd_event_start', true );
    $event_end   = get_post_meta( $post_id, 'fcsd_event_end', true );
    $event_price = get_post_meta( $post_id, 'fcsd_event_price', true );

    $formations = wp_get_post_terms( $post_id, 'event_formation' );
    ?>
    <article <?php post_class( 'news-card event-card', $post_id ); ?>>
        <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
           class="news-card__thumb"
           aria-label="<?php echo esc_attr( get_the_title( $post_id ) ); ?>">

            <div class="news-card__header training">
                <?php if ( ! empty( $formations ) && ! is_wp_error( $formations ) ) : ?>
                    <span class="news-card__cat">
                        <?php
                        echo wp_kses_post( fcsd_event_formation_media_html( $formations[0] ) );
                        echo esc_html( fcsd_event_formation_i18n_field( $formations[0], 'name' ) );
                        ?>
                    </span>
                <?php endif; ?>

                <h2 class="news-card__title">
                    <?php echo esc_html( get_the_title( $post_id ) ); ?>
                </h2>
            </div>

            <?php if ( has_post_thumbnail( $post_id ) ) : ?>
                <?php
                echo get_the_post_thumbnail(
                    $post_id,
                    'medium_large',
                    [
                        'class'   => 'news-card__img',
                        'loading' => 'lazy',
                    ]
                );
                ?>
            <?php else : ?>
                <div class="news-card__img event-card__img--placeholder"></div>
            <?php endif; ?>
        </a>

        <div class="news-card__body">
            <ul class="event-card__meta">
                <?php if ( ! empty( $event_start ) ) : ?>
                    <li class="event-card__meta-item">
                        <span class="event-card__meta-label"><?php esc_html_e( 'Data inici', 'fcsd' ); ?></span>
                        <span class="event-card__meta-value"><?php echo esc_html( $event_start ); ?></span>
                    </li>
                <?php endif; ?>

                <?php if ( ! empty( $event_end ) ) : ?>
                    <li class="event-card__meta-item">
                        <span class="event-card__meta-label"><?php esc_html_e( 'Data fi', 'fcsd' ); ?></span>
                        <span class="event-card__meta-value"><?php echo esc_html( $event_end ); ?></span>
                    </li>
                <?php endif; ?>

                <?php if ( ! empty( $event_price ) ) : ?>
                    <li class="event-card__meta-item">
                        <span class="event-card__meta-label"><?php esc_html_e( 'Preu', 'fcsd' ); ?></span>
                        <span class="event-card__meta-value"><?php echo esc_html( $event_price ); ?></span>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="news-card__excerpt">
                <?php
                $post = get_post( $post_id );
                $raw  = $post ? ( $post->post_excerpt ?: $post->post_content ) : '';
                echo wp_kses_post( wp_trim_words( wp_strip_all_tags( $raw ), 25 ) );
                ?>
            </div>

            <a class="news-card__more event-card__cta" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
                <?php esc_html_e( 'Inscriu-te', 'fcsd' ); ?>
            </a>
        </div>
    </article>
    <?php
}

// -----------------------------------------------------------------------------
// AJAX endpoint: returns HTML cards for a page + max_pages
// -----------------------------------------------------------------------------
function fcsd_ajax_events_query(): void {
    check_ajax_referer( 'fcsd_events_archive', 'nonce' );

    $page     = isset( $_POST['page'] ) ? max( 1, (int) $_POST['page'] ) : 1;
    $term_id  = isset( $_POST['term_id'] ) ? (int) $_POST['term_id'] : 0;
    $search   = isset( $_POST['search'] ) ? sanitize_text_field( (string) $_POST['search'] ) : '';
    $per_page = isset( $_POST['per_page'] ) ? max( 1, min( 48, (int) $_POST['per_page'] ) ) : 12;

    $args = [
        'post_type'      => 'event',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        's'              => $search,
        'meta_key'       => 'fcsd_event_start',
        'orderby'        => [ 'meta_value' => 'ASC', 'date' => 'DESC' ],
    ];

    if ( $term_id > 0 ) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'event_formation',
                'field'    => 'term_id',
                'terms'    => [ $term_id ],
            ],
        ];
    }

    $q = new WP_Query( $args );

    ob_start();
    if ( $q->have_posts() ) {
        while ( $q->have_posts() ) {
            $q->the_post();
            fcsd_render_event_card( get_the_ID() );
        }
    }
    wp_reset_postdata();
    $html = ob_get_clean();

    wp_send_json_success( [
        'html'      => $html,
        'max_pages' => (int) $q->max_num_pages,
        'found'     => (int) $q->found_posts,
    ] );
}

add_action( 'wp_ajax_fcsd_events_query', 'fcsd_ajax_events_query' );
add_action( 'wp_ajax_nopriv_fcsd_events_query', 'fcsd_ajax_events_query' );
