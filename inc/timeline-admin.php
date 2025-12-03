<?php
/**
 * Herramientas de administración para la cronologia
 * - Generar años automáticamente desde un año inicial hasta el actual
 * - Añadir eventos rápidos por año
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Añade un submenú bajo "Cronologia" (timeline_year)
 */
function fcsd_timeline_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=timeline_year',             // parent slug (Cronologia)
        __( 'Eines de cronologia', 'fcsd' ),            // page title
        __( 'Eines de cronologia', 'fcsd' ),            // menu title
        'edit_posts',                                   // capability
        'fcsd-timeline-tools',                          // menu slug
        'fcsd_timeline_admin_page_callback'             // callback
    );
}
add_action( 'admin_menu', 'fcsd_timeline_admin_menu' );

/**
 * Página de herramientas de cronologia
 */
function fcsd_timeline_admin_page_callback() {

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( __( 'No tens permisos per veure aquesta pàgina.', 'fcsd' ) );
    }

    $message = '';
    $message_type = 'updated';

    // Gestión de formularios
    if ( ! empty( $_POST['fcsd_timeline_action'] ) ) {
        check_admin_referer( 'fcsd_timeline_tools' );

        $action = sanitize_text_field( wp_unslash( $_POST['fcsd_timeline_action'] ) );

        // 1) Generar años automáticamente
        if ( 'generate_years' === $action ) {
            $start_year   = isset( $_POST['start_year'] ) ? (int) $_POST['start_year'] : 0;
            $current_year = (int) current_time( 'Y' );

            if ( $start_year <= 0 || $start_year > $current_year ) {
                $message      = __( 'Any inicial no vàlid.', 'fcsd' );
                $message_type = 'error';
            } else {
                $created = 0;

                for ( $year = $start_year; $year <= $current_year; $year++ ) {
                    // ¿Existe ya este año?
                    $existing = get_page_by_title( (string) $year, OBJECT, 'timeline_year' );

                    if ( ! $existing ) {
                        $post_id = wp_insert_post(
                            [
                                'post_type'   => 'timeline_year',
                                'post_title'  => (string) $year,
                                'post_status' => 'publish',
                            ]
                        );

                        if ( $post_id && ! is_wp_error( $post_id ) ) {
                            $created++;
                        }
                    }
                }

                if ( $created > 0 ) {
                    /* translators: %d: número de años creados */
                    $message = sprintf( _n( 'S\'ha creat %d any.', 'S\'han creat %d anys.', $created, 'fcsd' ), $created );
                } else {
                    $message = __( 'Tots els anys ja existien, no s\'ha creat cap any nou.', 'fcsd' );
                }
            }
        }

        // 2) Crear un evento rápido para un año concreto
        if ( 'create_event' === $action ) {
            $year_id       = isset( $_POST['timeline_year_id'] ) ? (int) $_POST['timeline_year_id'] : 0;
            $event_title   = isset( $_POST['event_title'] ) ? sanitize_text_field( wp_unslash( $_POST['event_title'] ) ) : '';
            $event_content = isset( $_POST['event_content'] ) ? wp_kses_post( wp_unslash( $_POST['event_content'] ) ) : '';

            if ( ! $year_id || '' === $event_title ) {
                $message      = __( 'Cal indicar un títol i un any vàlids per crear l\'esdeveniment.', 'fcsd' );
                $message_type = 'error';
            } else {
                $event_id = wp_insert_post(
                    [
                        'post_type'    => 'timeline_event',
                        'post_title'   => $event_title,
                        'post_content' => $event_content,
                        'post_status'  => 'publish',
                    ]
                );

                if ( $event_id && ! is_wp_error( $event_id ) ) {
                    // Guardamos la relación con el año usando la misma meta que el metabox
                    update_post_meta( $event_id, 'timeline_year_id', $year_id );
                    $message = __( 'Esdeveniment creat correctament.', 'fcsd' );
                } else {
                    $message      = __( 'Hi ha hagut un error en crear l\'esdeveniment.', 'fcsd' );
                    $message_type = 'error';
                }
            }
        }
    }

    // Obtenemos todos los años para pintarlos en la página
    $years = get_posts(
        [
            'post_type'      => 'timeline_year',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]
    );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Eines de cronologia', 'fcsd' ); ?></h1>

        <?php if ( $message ) : ?>
            <div class="<?php echo ( 'error' === $message_type ) ? 'notice notice-error' : 'notice notice-success'; ?>">
                <p><?php echo esc_html( $message ); ?></p>
            </div>
        <?php endif; ?>

        <hr>

        <!-- Formulario para generar años -->
        <h2><?php esc_html_e( 'Generar anys automàticament', 'fcsd' ); ?></h2>
        <p><?php esc_html_e( 'Introdueix l\'any inicial i es crearan tots els anys fins a l\'any actual. Els anys que ja existeixin no es duplicaran.', 'fcsd' ); ?></p>

        <form method="post" action="">
            <?php wp_nonce_field( 'fcsd_timeline_tools' ); ?>
            <input type="hidden" name="fcsd_timeline_action" value="generate_years">

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="start_year"><?php esc_html_e( 'Any inicial', 'fcsd' ); ?></label>
                    </th>
                    <td>
                        <input
                            name="start_year"
                            id="start_year"
                            type="number"
                            class="small-text"
                            min="1900"
                            max="<?php echo esc_attr( current_time( 'Y' ) ); ?>"
                            value="1982"
                        >
                        <p class="description">
                            <?php esc_html_e( 'Per exemple: 1982', 'fcsd' ); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button( __( 'Generar anys', 'fcsd' ) ); ?>
        </form>

        <hr>

        <!-- Listado de años con creación rápida de eventos -->
        <h2><?php esc_html_e( 'Afegir esdeveniments per any', 'fcsd' ); ?></h2>

        <?php if ( empty( $years ) ) : ?>
            <p><?php esc_html_e( 'Encara no hi ha cap any a la cronologia. Genera els anys amb el formulari anterior.', 'fcsd' ); ?></p>
        <?php else : ?>
            <p><?php esc_html_e( 'Fes servir els formularis següents per afegir esdeveniments ràpidament a cada any.', 'fcsd' ); ?></p>

            <div class="fcsd-timeline-years">
                <?php foreach ( $years as $year ) : ?>
                    <?php
                    // Obtenemos los eventos de este año para mostrarlos como referencia
                    $events = get_posts(
                        [
                            'post_type'      => 'timeline_event',
                            'posts_per_page' => -1,
                            'orderby'        => 'menu_order',
                            'order'          => 'ASC',
                            'meta_key'       => 'timeline_year_id',
                            'meta_value'     => $year->ID,
                        ]
                    );
                    ?>
                    <div class="postbox" style="margin-top:20px;">
                        <div class="postbox-header">
                            <h2 class="hndle">
                                <?php echo esc_html( get_the_title( $year ) ); ?>
                            </h2>
                        </div>
                        <div class="inside">
                            <?php if ( ! empty( $events ) ) : ?>
                                <p><strong><?php esc_html_e( 'Esdeveniments existents:', 'fcsd' ); ?></strong></p>
                                <ul>
                                    <?php foreach ( $events as $event ) : ?>
                                        <li>
                                            <a href="<?php echo esc_url( get_edit_post_link( $event->ID ) ); ?>">
                                                <?php echo esc_html( get_the_title( $event ) ); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <p><em><?php esc_html_e( 'Encara no hi ha esdeveniments per aquest any.', 'fcsd' ); ?></em></p>
                            <?php endif; ?>

                            <hr>

                            <h3><?php esc_html_e( 'Afegir esdeveniment ràpid', 'fcsd' ); ?></h3>

                            <form method="post" action="">
                                <?php wp_nonce_field( 'fcsd_timeline_tools' ); ?>
                                <input type="hidden" name="fcsd_timeline_action" value="create_event">
                                <input type="hidden" name="timeline_year_id" value="<?php echo esc_attr( $year->ID ); ?>">

                                <p>
                                    <label for="event_title_<?php echo esc_attr( $year->ID ); ?>">
                                        <?php esc_html_e( 'Títol de l\'esdeveniment', 'fcsd' ); ?>
                                    </label><br>
                                    <input
                                        type="text"
                                        class="regular-text"
                                        name="event_title"
                                        id="event_title_<?php echo esc_attr( $year->ID ); ?>"
                                        required
                                    >
                                </p>

                                <p>
                                    <label for="event_content_<?php echo esc_attr( $year->ID ); ?>">
                                        <?php esc_html_e( 'Descripció breu (opcional)', 'fcsd' ); ?>
                                    </label><br>
                                    <textarea
                                        name="event_content"
                                        id="event_content_<?php echo esc_attr( $year->ID ); ?>"
                                        rows="3"
                                        class="large-text"
                                    ></textarea>
                                </p>

                                <?php submit_button( __( 'Afegir esdeveniment', 'fcsd' ), 'secondary', '', false ); ?>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
