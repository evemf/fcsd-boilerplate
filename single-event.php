<?php
/**
 * Plantilla single per al CPT "event"
 */

get_header();
?>

<div class="container content py-5">
<?php
while ( have_posts() ) :
    the_post();

    $post_id = get_the_ID();

    // --- Metadades bàsiques vinculades a Sinergia ---
    $sinergia_event_id = get_post_meta( $post_id, 'fcsd_sinergia_event_id', true );
    $event_start       = get_post_meta( $post_id, 'fcsd_event_start', true );
    $event_end         = get_post_meta( $post_id, 'fcsd_event_end', true );
    $event_price       = get_post_meta( $post_id, 'fcsd_event_price', true );

    // --- Dades ampliades des de la caché de Sinergia (per treure assigned_user_id, etc.) ---
    $cached_event      = [];
    $assigned_user_id  = '';

    if ( function_exists( 'fcsd_sinergia_events_table' ) && ! empty( $sinergia_event_id ) ) {
        global $wpdb;

        $table = fcsd_sinergia_events_table();
        $row   = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE sinergia_id = %s LIMIT 1",
                $sinergia_event_id
            ),
            ARRAY_A
        );

        if ( $row && ! empty( $row['payload'] ) ) {
            $decoded = json_decode( $row['payload'], true );
            if ( is_array( $decoded ) ) {
                $cached_event = $decoded;

                if ( isset( $decoded['assigned_user_id'] ) ) {
                    $assigned_user_id = $decoded['assigned_user_id'];
                }
            }
        }
    }
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class( 'single-event' ); ?>>

        <header class="mb-4">
            <h1 class="mb-3"><?php the_title(); ?></h1>

            <?php if ( has_post_thumbnail() ) : ?>
                <div class="mb-4">
                    <?php the_post_thumbnail( 'large', [ 'class' => 'img-fluid' ] ); ?>
                </div>
            <?php endif; ?>
        </header>

        <div class="row">
            <div class="col-md-8">

                <section class="event-content mb-4">
                    <?php
                    // Si el contingut porta el shortcode vell, només el mostrem "net"
                    $content = get_the_content();
                    $content = preg_replace( '/\[sinergia_form[^\]]*\]/', '', $content );
                    echo apply_filters( 'the_content', $content );
                    ?>
                </section>

                <?php if ( ! empty( $sinergia_event_id ) ) : ?>
                    <section class="event-registration mt-5">
                        <h2 class="h4 mb-3">
                            <?php esc_html_e( 'Inscripció a aquest esdeveniment', 'fcsd' ); ?>
                        </h2>

                        <?php
                        // 👉 AQUI ES ON S'INTEGRA EL FORMULARI DIRECTE AL TEMA
                        echo fcsd_sinergia_render_form( $sinergia_event_id, $assigned_user_id );
                        ?>
                    </section>
                <?php endif; ?>

            </div><!-- /.col-md-8 -->

            <aside class="col-md-4">
                <section class="event-details mb-4">
                    <h2 class="h5 mb-3">
                        <?php esc_html_e( 'Detalls de l\'esdeveniment', 'fcsd' ); ?>
                    </h2>

                    <dl class="event-meta">
                        <?php if ( ! empty( $event_start ) ) : ?>
                            <dt><?php esc_html_e( 'Data inici', 'fcsd' ); ?></dt>
                            <dd><?php echo esc_html( $event_start ); ?></dd>
                        <?php endif; ?>

                        <?php if ( ! empty( $event_end ) ) : ?>
                            <dt><?php esc_html_e( 'Data fi', 'fcsd' ); ?></dt>
                            <dd><?php echo esc_html( $event_end ); ?></dd>
                        <?php endif; ?>

                        <?php if ( ! empty( $event_price ) ) : ?>
                            <dt><?php esc_html_e( 'Preu', 'fcsd' ); ?></dt>
                            <dd><?php echo esc_html( $event_price ); ?></dd>
                        <?php endif; ?>

                        <?php if ( ! empty( $sinergia_event_id ) ) : ?>
                            <dt><?php esc_html_e( 'ID Sinergia', 'fcsd' ); ?></dt>
                            <dd><?php echo esc_html( $sinergia_event_id ); ?></dd>
                        <?php endif; ?>
                    </dl>
                </section>

                <?php if ( ! empty( $cached_event ) ) : ?>
                    <section class="event-sinergia-extra">
                        <h2 class="h6 mb-2">
                            <?php esc_html_e( 'Dades addicionals (Sinergia)', 'fcsd' ); ?>
                        </h2>
                        <dl class="event-meta-small">
                            <?php
                            foreach ( $cached_event as $key => $value ) {
                                if ( is_scalar( $value ) && $value !== '' ) {
                                    printf(
                                        '<dt>%s</dt><dd>%s</dd>',
                                        esc_html( ucwords( str_replace( '_', ' ', $key ) ) ),
                                        esc_html( (string) $value )
                                    );
                                }
                            }
                            ?>
                        </dl>
                    </section>
                <?php endif; ?>
            </aside><!-- /.col-md-4 -->
        </div><!-- /.row -->

    </article>

<?php
endwhile;
?>
</div><!-- /.container -->

<?php
get_footer();
