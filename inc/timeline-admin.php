<?php
/**
 * Herramientas de administración para la cronologia
 * - Generar años automáticamente desde un año inicial hasta el actual
 * - Afegir / eliminar esdeveniments ràpidament
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

    $message       = '';
    $message_type  = 'updated';
    $last_year_id  = 0; // para recordar dónde estábamos

    // Gestión de formularios
    if ( ! empty( $_POST['fcsd_timeline_action'] ) ) {
        check_admin_referer( 'fcsd_timeline_tools' );

        $action = sanitize_text_field( wp_unslash( $_POST['fcsd_timeline_action'] ) );

        if ( isset( $_POST['fcsd_last_year'] ) ) {
            $last_year_id = (int) $_POST['fcsd_last_year'];
        }

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

        // 2) Crear varios eventos rápidos para uno o varios años a la vez (formulario global)
        } elseif ( 'create_events' === $action ) {

            $raw_titles   = isset( $_POST['event_title'] ) ? wp_unslash( $_POST['event_title'] ) : [];
            $raw_contents = isset( $_POST['event_content'] ) ? wp_unslash( $_POST['event_content'] ) : [];

            if ( empty( $raw_titles ) || ! is_array( $raw_titles ) ) {
                $message      = __( 'No s\'ha indicat cap esdeveniment a crear.', 'fcsd' );
                $message_type = 'error';
            } else {
                $created = 0;

                // $raw_titles: [year_id => [0 => 'Títol 1', 1 => 'Títol 2', ...]]
                foreach ( $raw_titles as $year_id => $titles_for_year ) {
                    $year_id = (int) $year_id;

                    if ( ! $year_id || empty( $titles_for_year ) || ! is_array( $titles_for_year ) ) {
                        continue;
                    }

                    foreach ( $titles_for_year as $index => $title ) {
                        $title = sanitize_text_field( $title );

                        // Sin título, no creamos nada
                        if ( '' === trim( $title ) ) {
                            continue;
                        }

                        $content = '';
                        if (
                            isset( $raw_contents[ $year_id ] ) &&
                            is_array( $raw_contents[ $year_id ] ) &&
                            isset( $raw_contents[ $year_id ][ $index ] )
                        ) {
                            $content = wp_kses_post( $raw_contents[ $year_id ][ $index ] );
                        }

                        $event_id = wp_insert_post(
                            [
                                'post_type'    => 'timeline_event',
                                'post_title'   => $title,
                                'post_content' => $content,
                                'post_status'  => 'publish',
                            ]
                        );

                        if ( $event_id && ! is_wp_error( $event_id ) ) {
                            // Relacionamos con el año
                            update_post_meta( $event_id, 'timeline_year_id', $year_id );
                            $created++;
                        }
                    }
                }

                if ( $created > 0 ) {
                    /* translators: %d: número de eventos creados */
                    $message = sprintf( _n( 'S\'ha creat %d esdeveniment.', 'S\'han creat %d esdeveniments.', $created, 'fcsd' ), $created );
                } else {
                    $message      = __( 'No s\'ha creat cap esdeveniment. Revisa que hi hagi títols vàlids.', 'fcsd' );
                    $message_type = 'error';
                }
            }

        // 3) Eliminar un evento existente
        } elseif ( 'delete_event' === $action ) {

            $event_id = isset( $_POST['timeline_event_id'] ) ? (int) $_POST['timeline_event_id'] : 0;

            if ( ! $event_id ) {
                $message      = __( 'Esdeveniment no vàlid.', 'fcsd' );
                $message_type = 'error';
            } else {
                if ( current_user_can( 'delete_post', $event_id ) ) {
                    // Si prefieres enviarlo a la papelera, pon false en el segundo parámetro
                    $deleted = wp_delete_post( $event_id, true );

                    if ( $deleted ) {
                        $message = __( 'Esdeveniment eliminat correctament.', 'fcsd' );
                    } else {
                        $message      = __( 'No s\'ha pogut eliminar l\'esdeveniment.', 'fcsd' );
                        $message_type = 'error';
                    }
                } else {
                    $message      = __( 'No tens permisos per eliminar aquest esdeveniment.', 'fcsd' );
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

        <!-- Listado de años con creación rápida de eventos (formulario global) -->
        <h2><?php esc_html_e( 'Afegir esdeveniments per any', 'fcsd' ); ?></h2>

        <?php if ( empty( $years ) ) : ?>
            <p><?php esc_html_e( 'Encara no hi ha cap any a la cronologia. Genera els anys amb el formulari anterior.', 'fcsd' ); ?></p>
        <?php else : ?>
            <p><?php esc_html_e( 'Fes servir els formularis següents per afegir o eliminar esdeveniments ràpidament i desa-ho tot amb un sol botó per les altes.', 'fcsd' ); ?></p>

            <form method="post" action="" id="fcsd-timeline-events-form">
                <?php wp_nonce_field( 'fcsd_timeline_tools' ); ?>

                <input type="hidden" name="fcsd_timeline_action" id="fcsd_timeline_action" value="create_events">
                <input type="hidden" name="timeline_event_id" id="fcsd_timeline_event_id" value="">
                <input type="hidden" name="fcsd_last_year" id="fcsd_last_year" value="<?php echo esc_attr( $last_year_id ); ?>">

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
                        <div
                            class="postbox"
                            id="fcsd-year-<?php echo esc_attr( $year->ID ); ?>"
                            data-year-id="<?php echo esc_attr( $year->ID ); ?>"
                            style="margin-top:20px;"
                        >
                            <div class="postbox-header">
                                <h2 class="hndle">
                                    <?php echo esc_html( get_the_title( $year ) ); ?>
                                </h2>
                            </div>
                            <div class="inside">
                                <?php if ( ! empty( $events ) ) : ?>
                                    <p><strong><?php esc_html_e( 'Esdeveniments existents:', 'fcsd' ); ?></strong></p>
                                    <ul class="fcsd-timeline-existing-events">
                                        <?php foreach ( $events as $event ) : ?>
                                            <li class="fcsd-timeline-existing-event" style="display:flex;align-items:center;gap:8px;">
                                                <span class="fcsd-timeline-existing-event-title">
                                                    <a href="<?php echo esc_url( get_edit_post_link( $event->ID ) ); ?>">
                                                        <?php echo esc_html( get_the_title( $event ) ); ?>
                                                    </a>
                                                </span>
                                                <button
                                                    type="submit"
                                                    class="button-link-delete fcsd-delete-existing-event"
                                                    data-event-id="<?php echo esc_attr( $event->ID ); ?>"
                                                    data-year-id="<?php echo esc_attr( $year->ID ); ?>"
                                                    onclick="return confirm('<?php echo esc_js( __( 'Segur que vols eliminar aquest esdeveniment?', 'fcsd' ) ); ?>');"
                                                >
                                                    ✕
                                                </button>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else : ?>
                                    <p><em><?php esc_html_e( 'Encara no hi ha esdeveniments per aquest any.', 'fcsd' ); ?></em></p>
                                <?php endif; ?>

                                <hr>

                                <h3><?php esc_html_e( 'Afegir esdeveniments ràpids per aquest any', 'fcsd' ); ?></h3>

                                <div class="fcsd-timeline-events-wrap">
                                    <div class="fcsd-timeline-event-group">
                                        <p>
                                            <label>
                                                <?php esc_html_e( 'Títol de l\'esdeveniment', 'fcsd' ); ?>
                                            </label><br>
                                            <input
                                                type="text"
                                                class="regular-text"
                                                name="event_title[<?php echo esc_attr( $year->ID ); ?>][]"
                                            >
                                        </p>

                                        <p>
                                            <label>
                                                <?php esc_html_e( 'Descripció breu (opcional)', 'fcsd' ); ?>
                                            </label><br>
                                            <textarea
                                                name="event_content[<?php echo esc_attr( $year->ID ); ?>][]"
                                                rows="3"
                                                class="large-text"
                                            ></textarea>
                                        </p>

                                        <hr>
                                    </div>
                                </div>

                                <p>
                                    <button type="button" class="button fcsd-add-timeline-event">
                                        <?php esc_html_e( 'Afegir un altre esdeveniment per aquest any', 'fcsd' ); ?>
                                    </button>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php submit_button( __( 'Afegir esdeveniments', 'fcsd' )); ?>
            </form>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var form              = document.getElementById('fcsd-timeline-events-form');
        var actionInput       = document.getElementById('fcsd_timeline_action');
        var eventIdInput      = document.getElementById('fcsd_timeline_event_id');
        var lastYearInput     = document.getElementById('fcsd_last_year');

        // Botones "Afegir un altre esdeveniment per aquest any"
        document.querySelectorAll('.fcsd-add-timeline-event').forEach(function (button) {
            button.addEventListener('click', function (e) {
                e.preventDefault();

                var postbox = button.closest('.postbox');
                if (!postbox) {
                    return;
                }

                var wrap = postbox.querySelector('.fcsd-timeline-events-wrap');
                if (!wrap) {
                    return;
                }

                var groups = wrap.querySelectorAll('.fcsd-timeline-event-group');
                if (!groups.length) {
                    return;
                }

                var last  = groups[groups.length - 1];
                var clone = last.cloneNode(true);

                // Limpiar los valores de inputs y textareas en el clon
                clone.querySelectorAll('input, textarea').forEach(function (field) {
                    field.value = '';
                });

                wrap.appendChild(clone);

                // Guardamos el año actual como último usado
                var yearId = postbox.getAttribute('data-year-id');
                if (lastYearInput && yearId) {
                    lastYearInput.value = yearId;
                }
            });
        });

        // Clic en la X de un evento existente: cambiamos acción a delete_event
        document.querySelectorAll('.fcsd-delete-existing-event').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!form || !actionInput || !eventIdInput) {
                    return;
                }

                var eventId = button.getAttribute('data-event-id');
                var yearId  = button.getAttribute('data-year-id');

                actionInput.value  = 'delete_event';
                eventIdInput.value = eventId || '';

                if (lastYearInput && yearId) {
                    lastYearInput.value = yearId;
                }
            });
        });

        // Cuando el usuario enfoque cualquier campo de evento nuevo, guardamos el año
        document.addEventListener('focusin', function (e) {
            if (!e.target.matches('.fcsd-timeline-event-group input, .fcsd-timeline-event-group textarea')) {
                return;
            }

            var postbox = e.target.closest('.postbox');
            if (!postbox) {
                return;
            }

            var yearId = postbox.getAttribute('data-year-id');
            if (lastYearInput && yearId) {
                lastYearInput.value = yearId;
            }
        });
    });
    </script>

    <?php if ( $last_year_id ) : ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var target = document.getElementById('fcsd-year-<?php echo (int) $last_year_id; ?>');
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
        </script>
    <?php endif; ?>

    <?php
}
