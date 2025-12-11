<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * CPT "acte" + metaboxes + helpers de calendari + pantalla d'admin.
 *
 * - CPT "acte" per gestionar actes generals i laborals.
 * - Cada acte té: títol, descripció (cos), data inici/fi, color, tipus (general/laboral) i imatge destacada.
 * - Helpers per obtenir actes per rang de dates i per tipus.
 * - Pantalla d'admin "Calendari d'actes" amb vista mensual / anual.
 */

/**
 * Registre del CPT "acte".
 */
function fcsd_register_cpt_acte() {
    $labels = array(
        'name'               => __( 'Actes', 'fcsd' ),
        'singular_name'      => __( 'Acte', 'fcsd' ),
        'add_new'            => __( 'Afegir acte', 'fcsd' ),
        'add_new_item'       => __( 'Afegir nou acte', 'fcsd' ),
        'edit_item'          => __( 'Editar acte', 'fcsd' ),
        'new_item'           => __( 'Nou acte', 'fcsd' ),
        'view_item'          => __( 'Veure acte', 'fcsd' ),
        'search_items'       => __( 'Cercar actes', 'fcsd' ),
        'not_found'          => __( 'No s\'han trobat actes', 'fcsd' ),
        'not_found_in_trash' => __( 'No s\'han trobat actes a la paperera', 'fcsd' ),
        'menu_name'          => __( 'Actes', 'fcsd' ),
    );

    $args = array(
        'label'               => __( 'Actes', 'fcsd' ),
        'labels'              => $labels,
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_icon'           => 'dashicons-calendar-alt',
        'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'has_archive'         => false,
        'rewrite'             => false,
        'show_in_rest'        => true,
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
    );

    register_post_type( 'acte', $args );
}
add_action( 'init', 'fcsd_register_cpt_acte' );

/**
 * Metabox de detalls de l'acte.
 */
function fcsd_acte_add_meta_box() {
    add_meta_box(
        'fcsd_acte_details',
        __( 'Detalls de l\'acte', 'fcsd' ),
        'fcsd_acte_render_meta_box',
        'acte',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'fcsd_acte_add_meta_box' );

/**
 * Render del metabox.
 */
function fcsd_acte_render_meta_box( $post ) {
    wp_nonce_field( 'fcsd_acte_details_nonce', 'fcsd_acte_details_nonce' );

    $start  = get_post_meta( $post->ID, 'fcsd_acte_start', true );
    $end    = get_post_meta( $post->ID, 'fcsd_acte_end', true );
    $color  = get_post_meta( $post->ID, 'fcsd_acte_color', true );
    $scope  = get_post_meta( $post->ID, 'fcsd_acte_scope', true ); // general | laboral

    // Nous metadades
    $is_official   = (bool) get_post_meta( $post->ID, 'fcsd_acte_is_official_holiday', true );
    $contract_type = get_post_meta( $post->ID, 'fcsd_acte_contract_type', true );

    if ( empty( $scope ) ) {
        $scope = 'general';
    }

    // Normalitzem per a <input type="datetime-local">
    $start_attr = '';
    if ( ! empty( $start ) && false !== strtotime( $start ) ) {
        $start_attr = gmdate( 'Y-m-d\TH:i', strtotime( $start ) );
    }

    $end_attr = '';
    if ( ! empty( $end ) && false !== strtotime( $end ) ) {
        $end_attr = gmdate( 'Y-m-d\TH:i', strtotime( $end ) );
    }

    if ( empty( $color ) ) {
        $color = '#0073aa'; // color per defecte
    }
    ?>
    <div class="fcsd-acte-metabox">
        <p>
            <label for="fcsd_acte_scope">
                <?php esc_html_e( 'Tipus d\'acte', 'fcsd' ); ?>
            </label><br>
            <select name="fcsd_acte_scope" id="fcsd_acte_scope">
                <option value="general" <?php selected( $scope, 'general' ); ?>>
                    <?php esc_html_e( 'Acte general (visible per tothom)', 'fcsd' ); ?>
                </option>
                <option value="laboral" <?php selected( $scope, 'laboral' ); ?>>
                    <?php esc_html_e( 'Acte laboral (només treballadors @fcsd.org)', 'fcsd' ); ?>
                </option>
            </select>
        </p>

        <p>
            <label for="fcsd_acte_start">
                <?php esc_html_e( 'Data i hora d\'inici', 'fcsd' ); ?>
            </label><br>
            <input type="datetime-local"
                   id="fcsd_acte_start"
                   name="fcsd_acte_start"
                   value="<?php echo esc_attr( $start_attr ); ?>"
                   class="regular-text" />
        </p>

        <p>
            <label for="fcsd_acte_end">
                <?php esc_html_e( 'Data i hora de fi', 'fcsd' ); ?>
            </label><br>
            <input type="datetime-local"
                   id="fcsd_acte_end"
                   name="fcsd_acte_end"
                   value="<?php echo esc_attr( $end_attr ); ?>"
                   class="regular-text" />
        </p>

        <p>
            <label for="fcsd_acte_color">
                <?php esc_html_e( 'Color de l\'acte al calendari', 'fcsd' ); ?>
            </label><br>
            <input type="color"
                   id="fcsd_acte_color"
                   name="fcsd_acte_color"
                   value="<?php echo esc_attr( $color ); ?>" />
            <span class="description">
                <?php esc_html_e( 'S\'utilitza per marcar visualment l\'acte al calendari.', 'fcsd' ); ?>
            </span>
        </p>

        <p>
            <label for="fcsd_acte_is_official_holiday">
                <input type="checkbox"
                       id="fcsd_acte_is_official_holiday"
                       name="fcsd_acte_is_official_holiday"
                       value="1"
                    <?php checked( $is_official ); ?> />
                <?php esc_html_e( 'Festiu oficial del calendari laboral', 'fcsd' ); ?>
            </label>
        </p>

        <p>
            <label for="fcsd_acte_contract_type">
                <?php esc_html_e( 'Tipus de contracte (calendari laboral)', 'fcsd' ); ?>
            </label><br>
            <select name="fcsd_acte_contract_type" id="fcsd_acte_contract_type">
                <option value="">
                    <?php esc_html_e( 'Sense especificar', 'fcsd' ); ?>
                </option>
                <option value="37h" <?php selected( $contract_type, '37h' ); ?>>
                    <?php esc_html_e( 'Contracte de 37 hores', 'fcsd' ); ?>
                </option>
                <option value="35h" <?php selected( $contract_type, '35h' ); ?>>
                    <?php esc_html_e( 'Contracte de 35 hores', 'fcsd' ); ?>
                </option>
            </select>
        </p>
        <?php
        $needs_ticket = (bool) get_post_meta( $post->ID, 'fcsd_acte_needs_ticket', true );
        ?>
        <p>
            <label for="fcsd_acte_needs_ticket">
                <input type="checkbox"
                       id="fcsd_acte_needs_ticket"
                       name="fcsd_acte_needs_ticket"
                       value="1"
                    <?php checked( $needs_ticket ); ?> />
                <?php esc_html_e( 'Requereix entrada prèvia', 'fcsd' ); ?>
            </label>
        </p>

        <p class="description">
            <?php esc_html_e( 'El títol, la descripció i la imatge destacada es gestionen amb els camps estàndard de WordPress.', 'fcsd' ); ?>
        </p>
    </div>
    <?php
}

/**
 * Guardar metadades de l'acte.
 */
function fcsd_acte_save_meta_box( $post_id ) {
    if ( ! isset( $_POST['fcsd_acte_details_nonce'] ) ||
         ! wp_verify_nonce( $_POST['fcsd_acte_details_nonce'], 'fcsd_acte_details_nonce' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Tipus
    if ( isset( $_POST['fcsd_acte_scope'] ) ) {
        $scope = sanitize_text_field( wp_unslash( $_POST['fcsd_acte_scope'] ) );
        if ( ! in_array( $scope, array( 'general', 'laboral' ), true ) ) {
            $scope = 'general';
        }
        update_post_meta( $post_id, 'fcsd_acte_scope', $scope );
    }

    // Dates
    if ( isset( $_POST['fcsd_acte_start'] ) ) {
        $raw = sanitize_text_field( wp_unslash( $_POST['fcsd_acte_start'] ) );
        if ( ! empty( $raw ) ) {
            $ts = strtotime( $raw );
            if ( false !== $ts ) {
                update_post_meta( $post_id, 'fcsd_acte_start', gmdate( 'Y-m-d H:i', $ts ) );
            }
        } else {
            delete_post_meta( $post_id, 'fcsd_acte_start' );
        }
    }

    if ( isset( $_POST['fcsd_acte_end'] ) ) {
        $raw = sanitize_text_field( wp_unslash( $_POST['fcsd_acte_end'] ) );
        if ( ! empty( $raw ) ) {
            $ts = strtotime( $raw );
            if ( false !== $ts ) {
                update_post_meta( $post_id, 'fcsd_acte_end', gmdate( 'Y-m-d H:i', $ts ) );
            }
        } else {
            delete_post_meta( $post_id, 'fcsd_acte_end' );
        }
    }

    // Color
    if ( isset( $_POST['fcsd_acte_color'] ) ) {
        $color = sanitize_text_field( wp_unslash( $_POST['fcsd_acte_color'] ) );
        update_post_meta( $post_id, 'fcsd_acte_color', $color );
    }

    // Festiu oficial del calendari laboral
    $is_official = isset( $_POST['fcsd_acte_is_official_holiday'] ) ? '1' : '';
    if ( $is_official ) {
        update_post_meta( $post_id, 'fcsd_acte_is_official_holiday', '1' );
    } else {
        delete_post_meta( $post_id, 'fcsd_acte_is_official_holiday' );
    }

    // Tipus de contracte (calendari laboral)
    if ( isset( $_POST['fcsd_acte_contract_type'] ) ) {
        $contract_type = sanitize_text_field( wp_unslash( $_POST['fcsd_acte_contract_type'] ) );
        update_post_meta( $post_id, 'fcsd_acte_contract_type', $contract_type );
    }

    // Entrada prèvia
    $needs_ticket = isset( $_POST['fcsd_acte_needs_ticket'] ) ? '1' : '';
    if ( $needs_ticket ) {
        update_post_meta( $post_id, 'fcsd_acte_needs_ticket', '1' );
    } else {
        delete_post_meta( $post_id, 'fcsd_acte_needs_ticket' );
    }

}
add_action( 'save_post_acte', 'fcsd_acte_save_meta_box' );

/**
 * Helper: normalitza un acte a un item de calendari.
 */
function fcsd_acte_get_calendar_item( $post ) {
    $post_id = is_object( $post ) ? $post->ID : (int) $post;

    $title     = get_the_title( $post_id );
    $permalink = get_permalink( $post_id );
    $excerpt   = get_the_excerpt( $post_id );
    $thumb_url = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
    $start_raw = get_post_meta( $post_id, 'fcsd_acte_start', true );
    $end_raw   = get_post_meta( $post_id, 'fcsd_acte_end', true );
    $scope     = get_post_meta( $post_id, 'fcsd_acte_scope', true );
    $color     = get_post_meta( $post_id, 'fcsd_acte_color', true );
    $needs_ticket   = (bool) get_post_meta( $post_id, 'fcsd_acte_needs_ticket', true );
    $is_official    = (bool) get_post_meta( $post_id, 'fcsd_acte_is_official_holiday', true );
    $contract_type  = get_post_meta( $post_id, 'fcsd_acte_contract_type', true );

    if ( empty( $scope ) ) {
        $scope = 'general';
    }

    $start_ts = $start_raw && false !== strtotime( $start_raw ) ? strtotime( $start_raw ) : get_post_timestamp( $post_id );
    $end_ts   = $end_raw && false !== strtotime( $end_raw ) ? strtotime( $end_raw ) : $start_ts;

    if ( empty( $color ) ) {
        $color = '#0073aa';
    }

    return array(
        'ID'                 => $post_id,
        'title'              => $title,
        'permalink'          => $permalink,
        'excerpt'            => $excerpt,
        'thumb'              => $thumb_url,
        'start_ts'           => $start_ts,
        'end_ts'             => $end_ts,
        'scope'              => $scope,
        'color'              => $color,
        'needs_ticket'       => $needs_ticket,
        'is_official_holiday'=> $is_official,
        'contract_type'      => $contract_type,
    );
}

/**
 * Devuelve los actes entre 2 timestamps.
 *
 * @param int         $start_ts Timestamp inicio (UTC).
 * @param int         $end_ts   Timestamp fin (UTC).
 * @param string|null $scope    'general', 'laboral' o null para todos.
 * @return array
 */
function fcsd_actes_get_in_range( $start_ts, $end_ts, $scope = null ) {
    $start_ts = (int) $start_ts;
    $end_ts   = (int) $end_ts;

    if ( $start_ts <= 0 || $end_ts <= 0 || $end_ts < $start_ts ) {
        return array();
    }

    $args = array(
        'post_type'      => 'acte',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        // Orden principal por meta de inicio, secundario por fecha de creación.
        'meta_key'       => 'fcsd_acte_start',
        'orderby'        => array(
            'meta_value' => 'ASC',
            'date'       => 'ASC',
        ),
    );

    if ( $scope ) {
        $args['meta_query'] = array(
            array(
                'key'   => 'fcsd_acte_scope',
                'value' => $scope,
            ),
        );
    }

    $query  = new WP_Query( $args );
    $result = array();

    if ( $query->have_posts() ) {
        foreach ( $query->posts as $post ) {
            $item = fcsd_acte_get_calendar_item( $post );

            // Item mal formado → fuera.
            if ( ! $item['start_ts'] || ! $item['end_ts'] ) {
                continue;
            }

            // Fuera de rango.
            if ( $item['end_ts'] < $start_ts || $item['start_ts'] > $end_ts ) {
                continue;
            }

            $result[] = $item;
        }
    }

    wp_reset_postdata();

    // Extra: aseguramos orden por hora de inicio y, si coincide, por ID (≈ fecha creación).
    usort(
        $result,
        function ( $a, $b ) {
            if ( $a['start_ts'] === $b['start_ts'] ) {
                return $a['ID'] <=> $b['ID'];
            }

            return $a['start_ts'] <=> $b['start_ts'];
        }
    );

    return $result;
}

/**
 * Petita helper: distribueix actes per dies (Y-m-d => [items]).
 */
function fcsd_actes_group_by_day( $items, $range_start_ts, $range_end_ts ) {
    $by_day = array();

    foreach ( $items as $item ) {
        $start_ts = (int) $item['start_ts'];
        $end_ts   = (int) $item['end_ts'];

        if ( $start_ts <= 0 || $end_ts <= 0 ) {
            continue;
        }

        $loop_start = max( $start_ts, $range_start_ts );
        $loop_end   = min( $end_ts, $range_end_ts );

        $cursor = $loop_start;

        while ( $cursor <= $loop_end ) {
            $key = gmdate( 'Y-m-d', $cursor );
            if ( ! isset( $by_day[ $key ] ) ) {
                $by_day[ $key ] = array();
            }
            $by_day[ $key ][] = $item;

            $cursor = strtotime( '+1 day', $cursor );
        }
    }

    return $by_day;
}

/**
 * Admin: registra submenú "Calendari d'actes".
 */
function fcsd_actes_register_admin_calendar_page() {
    add_submenu_page(
        'edit.php?post_type=acte',
        __( 'Calendari', 'fcsd' ),
        __( 'Calendari', 'fcsd' ),
        'edit_posts',
        'fcsd-actes-calendar',
        'fcsd_actes_render_admin_calendar_page'
    );
}
add_action( 'admin_menu', 'fcsd_actes_register_admin_calendar_page' );

/**
 * Admin: pantalla del calendari d'actes (mensual / anual).
 */
function fcsd_actes_render_admin_calendar_page() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        return;
    }

    $current_time = current_time( 'timestamp' );
    $view         = ( isset( $_GET['view'] ) && 'annual' === $_GET['view'] ) ? 'annual' : 'monthly';

    $year  = isset( $_GET['year'] ) ? (int) $_GET['year'] : (int) gmdate( 'Y', $current_time );
    $month = isset( $_GET['month'] ) ? (int) $_GET['month'] : (int) gmdate( 'n', $current_time );
    if ( $month < 1 || $month > 12 ) {
        $month = (int) gmdate( 'n', $current_time );
    }

    $scope = isset( $_GET['scope'] ) && 'laboral' === $_GET['scope'] ? 'laboral' : 'general';

    $base_url = add_query_arg(
        array(
            'post_type' => 'acte',
            'page'      => 'fcsd-actes-calendar',
        ),
        admin_url( 'edit.php' )
    );

    $month_names = array(
        1  => __( 'Gener', 'fcsd' ),
        2  => __( 'Febrer', 'fcsd' ),
        3  => __( 'Març', 'fcsd' ),
        4  => __( 'Abril', 'fcsd' ),
        5  => __( 'Maig', 'fcsd' ),
        6  => __( 'Juny', 'fcsd' ),
        7  => __( 'Juliol', 'fcsd' ),
        8  => __( 'Agost', 'fcsd' ),
        9  => __( 'Setembre', 'fcsd' ),
        10 => __( 'Octubre', 'fcsd' ),
        11 => __( 'Novembre', 'fcsd' ),
        12 => __( 'Desembre', 'fcsd' ),
    );

    $week_days_short = array(
        __( 'Dl', 'fcsd' ),
        __( 'Dt', 'fcsd' ),
        __( 'Dc', 'fcsd' ),
        __( 'Dj', 'fcsd' ),
        __( 'Dv', 'fcsd' ),
        __( 'Ds', 'fcsd' ),
        __( 'Dg', 'fcsd' ),
    );

    ?>
    <div class="wrap fcsd-actes-calendar-admin">
        <h1 class="wp-heading-inline">
            <?php esc_html_e( 'Calendari d\'actes', 'fcsd' ); ?>
        </h1>

        <hr class="wp-header-end">

        <h2 class="nav-tab-wrapper">
            <?php
            $monthly_url = add_query_arg(
                array(
                    'view'  => 'monthly',
                    'year'  => $year,
                    'month' => $month,
                    'scope' => $scope,
                ),
                $base_url
            );
            $annual_url  = add_query_arg(
                array(
                    'view'  => 'annual',
                    'year'  => $year,
                    'scope' => $scope,
                ),
                $base_url
            );
            ?>
            <a href="<?php echo esc_url( $monthly_url ); ?>" class="nav-tab <?php echo ( 'monthly' === $view ) ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Vista mensual', 'fcsd' ); ?>
            </a>
            <a href="<?php echo esc_url( $annual_url ); ?>" class="nav-tab <?php echo ( 'annual' === $view ) ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Vista anual', 'fcsd' ); ?>
            </a>
        </h2>

        <ul class="subsubsub">
            <?php
            $scope_general_url = add_query_arg(
                array(
                    'scope' => 'general',
                    'view'  => $view,
                    'year'  => $year,
                    'month' => $month,
                ),
                $base_url
            );
            $scope_laboral_url = add_query_arg(
                array(
                    'scope' => 'laboral',
                    'view'  => $view,
                    'year'  => $year,
                    'month' => $month,
                ),
                $base_url
            );
            ?>
            <li>
                <a href="<?php echo esc_url( $scope_general_url ); ?>" class="<?php echo ( 'general' === $scope ) ? 'current' : ''; ?>">
                    <?php esc_html_e( 'Actes generals', 'fcsd' ); ?>
                </a> |
            </li>
            <li>
                <a href="<?php echo esc_url( $scope_laboral_url ); ?>" class="<?php echo ( 'laboral' === $scope ) ? 'current' : ''; ?>">
                    <?php esc_html_e( 'Actes laborals', 'fcsd' ); ?>
                </a>
            </li>
        </ul>

        <div class="fcsd-actes-calendar-admin__inner">
            <?php
            if ( 'monthly' === $view ) {

                $month_start    = gmmktime( 0, 0, 0, $month, 1, $year );
                $days_in_month  = (int) gmdate( 't', $month_start );
                $month_end      = gmmktime( 23, 59, 59, $month, $days_in_month, $year );
                $first_weekday  = (int) gmdate( 'N', $month_start ); // 1 (dl) - 7 (dg)

                $actes      = fcsd_actes_get_in_range( $month_start, $month_end, $scope );
                $actes_days = fcsd_actes_group_by_day( $actes, $month_start, $month_end );

                $today_key = gmdate( 'Y-m-d', $current_time );

                $prev_month = $month - 1;
                $prev_year  = $year;
                if ( $prev_month < 1 ) {
                    $prev_month = 12;
                    $prev_year--;
                }

                $next_month = $month + 1;
                $next_year  = $year;
                if ( $next_month > 12 ) {
                    $next_month = 1;
                    $next_year++;
                }
                ?>
                <div class="fcsd-actes-calendar-admin__toolbar">
                    <a class="button"
                       href="<?php echo esc_url( add_query_arg( array(
                           'view'  => 'monthly',
                           'year'  => $prev_year,
                           'month' => $prev_month,
                           'scope' => $scope,
                       ), $base_url ) ); ?>">
                        &laquo;
                    </a>

                    <strong>
                        <?php echo esc_html( $month_names[ $month ] . ' ' . $year ); ?>
                    </strong>

                    <a class="button"
                       href="<?php echo esc_url( add_query_arg( array(
                           'view'  => 'monthly',
                           'year'  => $next_year,
                           'month' => $next_month,
                           'scope' => $scope,
                       ), $base_url ) ); ?>">
                        &raquo;
                    </a>
                </div>

                <table class="widefat fixed fcsd-actes-calendar-table">
                    <thead>
                        <tr>
                            <?php foreach ( $week_days_short as $label ) : ?>
                                <th><?php echo esc_html( $label ); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                        <?php
                        for ( $i = 1; $i < $first_weekday; $i++ ) {
                            echo '<td class="fcsd-actes-calendar__day fcsd-actes-calendar__day--empty"></td>';
                        }

                        $col = $first_weekday;

                        for ( $day = 1; $day <= $days_in_month; $day++ ) {

                            $current_ts = gmmktime( 0, 0, 0, $month, $day, $year );
                            $day_key    = gmdate( 'Y-m-d', $current_ts );
                            $day_actes  = isset( $actes_days[ $day_key ] ) ? $actes_days[ $day_key ] : array();
                            $is_today   = ( $day_key === $today_key );

                            // Dia amb algun festiu oficial?
                            $has_official = false;
                            if ( ! empty( $day_actes ) ) {
                                foreach ( $day_actes as $item ) {
                                    if ( ! empty( $item['is_official_holiday'] ) ) {
                                        $has_official = true;
                                        break;
                                    }
                                }
                            }

                            // Ordenamos los actes del día por hora de inicio.
                            if ( ! empty( $day_actes ) ) {
                                echo '<div class="fcsd-actes-calendar__dots">';
                                foreach ( $day_actes as $item ) {
                                    $color = ! empty( $item['color'] ) ? $item['color'] : '#0073aa';
                                    $title = ! empty( $item['title'] ) ? $item['title'] : get_the_title( $item['ID'] );

                                    echo '<div class="fcsd-actes-calendar__event">';
                                        echo '<span class="fcsd-actes-calendar__dot"'
                                            . ' style="background:' . esc_attr( $color ) . '"></span>';
                                        echo '<span class="fcsd-actes-calendar__event-title">'
                                            . esc_html( $title ) . '</span>';
                                    echo '</div>';
                                }
                                echo '</div>';
                            }

                            $classes = array( 'fcsd-actes-calendar__day' );
                            if ( $is_today ) {
                                $classes[] = 'is-today';
                            }
                            if ( ! empty( $day_actes ) ) {
                                $classes[] = 'has-events';
                            }
                            if ( $has_official ) {
                                $classes[] = 'fcsd-actes-calendar__day--official-holiday';
                            }

                            // Payload minimal para el modal JS.
                            $events_payload = array();
                            foreach ( $day_actes as $item ) {
                                $events_payload[] = array(
                                    'id'        => $item['ID'],
                                    'title'     => $item['title'],
                                    'start'     => $item['start_ts'],
                                    'edit_link' => get_edit_post_link( $item['ID'] ),
                                );
                            }

                            // Link "nuevo acte" pre-rellenando la fecha.
                            $new_url = add_query_arg(
                                array(
                                    'post_type'      => 'acte',
                                    'fcsd_acte_date' => $day_key,
                                ),
                                admin_url( 'post-new.php' )
                            );

                            echo '<td class="' . esc_attr( implode( ' ', $classes ) ) . '"'
                                . ' data-date="' . esc_attr( $day_key ) . '"'
                                . ' data-events=\'' . esc_attr( wp_json_encode( $events_payload ) ) . '\'>';

                                echo '<div class="fcsd-actes-calendar__day-header">';
                                    echo '<span class="fcsd-actes-calendar__day-number">' . (int) $day . '</span>';
                                    echo '<a class="fcsd-actes-calendar__add" href="' . esc_url( $new_url ) . '">+</a>';
                                echo '</div>';

                                if ( ! empty( $day_actes ) ) {
                                    echo '<div class="fcsd-actes-calendar__dots">';

                                    foreach ( $day_actes as $item ) {
                                        $color = ! empty( $item['color'] ) ? $item['color'] : '#0073aa';
                                        $title = ! empty( $item['title'] ) ? $item['title'] : get_the_title( $item['ID'] );

                                        $time_label = '';
                                        if ( ! empty( $item['start_ts'] ) ) {
                                            $time_label = date_i18n( get_option( 'time_format' ), (int) $item['start_ts'] );
                                        }

                                        echo '<div class="fcsd-actes-calendar__event">';

                                            echo '<span class="fcsd-actes-calendar__dot"'
                                                . ' style="background:' . esc_attr( $color ) . '"></span>';

                                            if ( $time_label ) {
                                                echo '<span class="fcsd-actes-calendar__event-time">'
                                                    . esc_html( $time_label ) . '</span>';
                                            }

                                            echo '<span class="fcsd-actes-calendar__event-title">'
                                                . esc_html( $title ) . '</span>';

                                        echo '</div>';
                                    }

                                    echo '</div>';
                                }

                            echo '</td>';


                            if ( $col % 7 === 0 ) {
                                echo '</tr><tr>';
                            }

                            $col++;
                        }

                        while ( ( $col - 1 ) % 7 !== 0 ) {
                            echo '<td class="fcsd-actes-calendar__day fcsd-actes-calendar__day--empty"></td>';
                            $col++;
                        }
                        ?>
                        </tr>
                    </tbody>
                </table>
                <?php
            } else {
                // Vista anual
                ?>
                <div class="fcsd-actes-calendar-admin__toolbar">
                    <a class="button"
                       href="<?php echo esc_url( add_query_arg( array(
                           'view'  => 'annual',
                           'year'  => $year - 1,
                           'scope' => $scope,
                       ), $base_url ) ); ?>">
                        &laquo;
                    </a>

                    <strong><?php echo esc_html( $year ); ?></strong>

                    <a class="button"
                       href="<?php echo esc_url( add_query_arg( array(
                           'view'  => 'annual',
                           'year'  => $year + 1,
                           'scope' => $scope,
                       ), $base_url ) ); ?>">
                        &raquo;
                    </a>
                </div>

                <div class="fcsd-actes-calendar-admin__annual-grid">
                    <?php
                    for ( $m = 1; $m <= 12; $m++ ) {
                        $month_start   = gmmktime( 0, 0, 0, $m, 1, $year );
                        $days_in_month = (int) gmdate( 't', $month_start );
                        $month_end     = gmmktime( 23, 59, 59, $m, $days_in_month, $year );
                        $first_weekday = (int) gmdate( 'N', $month_start );

                        $actes      = fcsd_actes_get_in_range( $month_start, $month_end, $scope );
                        $actes_days = fcsd_actes_group_by_day( $actes, $month_start, $month_end );
                        ?>
                        <div class="fcsd-actes-calendar-admin__month">
                            <h3 class="fcsd-actes-calendar-admin__month-title">
                                <?php echo esc_html( $month_names[ $m ] ); ?>
                            </h3>
                            <table class="widefat fixed fcsd-actes-calendar-table fcsd-actes-calendar-table--compact">
                                <thead>
                                    <tr>
                                        <?php foreach ( $week_days_short as $label ) : ?>
                                            <th><?php echo esc_html( $label ); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <?php
                                        for ( $i = 1; $i < $first_weekday; $i++ ) {
                                            echo '<td class="fcsd-actes-calendar__day fcsd-actes-calendar__day--empty"></td>';
                                        }

                                        $col = $first_weekday;

                                        for ( $day = 1; $day <= $days_in_month; $day++ ) {
                                            $current_ts = gmmktime( 0, 0, 0, $m, $day, $year );
                                            $day_key    = gmdate( 'Y-m-d', $current_ts );
                                            $day_actes  = isset( $actes_days[ $day_key ] ) ? $actes_days[ $day_key ] : array();

                                            // Dia amb algun festiu oficial?
                                            $has_official = false;
                                            if ( ! empty( $day_actes ) ) {
                                                foreach ( $day_actes as $item ) {
                                                    if ( ! empty( $item['is_official_holiday'] ) ) {
                                                        $has_official = true;
                                                        break;
                                                    }
                                                }
                                            }

                                            $classes = array( 'fcsd-actes-calendar__day' );
                                            if ( ! empty( $day_actes ) ) {
                                                $classes[] = 'has-events';
                                            }
                                            if ( $has_official ) {
                                                $classes[] = 'fcsd-actes-calendar__day--official-holiday';
                                            }

                                            echo '<td class="' . esc_attr( implode( ' ', $classes ) ) . '" data-date="' . esc_attr( $day_key ) . '">';
                                            echo '<div class="fcsd-actes-calendar__day-number">' . (int) $day . '</div>';

                                            if ( ! empty( $day_actes ) ) {
                                                echo '<div class="fcsd-actes-calendar__dots">';
                                                foreach ( $day_actes as $item ) {
                                                    $edit_link = get_edit_post_link( $item['ID'] );
                                                    echo '<a class="fcsd-actes-calendar__dot" href="' . esc_url( $edit_link ) . '" title="' . esc_attr( $item['title'] ) . '"></a>';
                                                }
                                                echo '</div>';
                                            }

                                            echo '</td>';

                                            if ( $col % 7 === 0 ) {
                                                echo '</tr><tr>';
                                            }
                                            $col++;
                                        }

                                        while ( ( $col - 1 ) % 7 !== 0 ) {
                                            echo '<td class="fcsd-actes-calendar__day fcsd-actes-calendar__day--empty"></td>';
                                            $col++;
                                        }
                                        ?>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Prefill de data quan es crea un acte des del calendari (admin).
 */
function fcsd_actes_prefill_date_on_new() {
    if ( ! is_admin() ) {
        return;
    }

    if ( ! isset( $_GET['post_type'], $_GET['fcsd_acte_date'] ) ) {
        return;
    }

    if ( 'acte' !== $_GET['post_type'] ) {
        return;
    }

    $date = sanitize_text_field( wp_unslash( $_GET['fcsd_acte_date'] ) );
    if ( ! $date || false === strtotime( $date ) ) {
        return;
    }

    // Quan es desa per primer cop, si no té data d'inici, li assignem aquesta.
    add_action(
        'save_post_acte',
        function( $post_id ) use ( $date ) {
            $existing = get_post_meta( $post_id, 'fcsd_acte_start', true );
            if ( empty( $existing ) ) {
                $ts = strtotime( $date . ' 09:00' );
                if ( false !== $ts ) {
                    update_post_meta( $post_id, 'fcsd_acte_start', gmdate( 'Y-m-d H:i', $ts ) );
                }
            }
        },
        10,
        1
    );
}
add_action( 'load-post-new.php', 'fcsd_actes_prefill_date_on_new' );

/**
 * Redirige la lista estándar del CPT "acte" al calendari d'actes.
 * Cuando el usuario entra en "Actes" verá directamente el calendario.
 */
function fcsd_actes_redirect_list_to_calendar() {
    global $typenow;

    // Solo afectamos al CPT "acte".
    if ( 'acte' !== $typenow ) {
        return;
    }

    // Si ya estamos en la página del calendario, no hacemos nada.
    if ( isset( $_GET['page'] ) && 'fcsd-actes-calendar' === $_GET['page'] ) {
        return;
    }

    $url = add_query_arg(
        array(
            'post_type' => 'acte',
            'page'      => 'fcsd-actes-calendar',
        ),
        admin_url( 'edit.php' )
    );

    wp_safe_redirect( $url );
    exit;
}
add_action( 'load-edit.php', 'fcsd_actes_redirect_list_to_calendar' );

/**
 * Assets específics per a la pantalla "Calendari d'actes".
 */
function fcsd_actes_admin_assets( $hook ) {
    // Slug de la pàgina: acte_page_fcsd-actes-calendar
    if ( 'acte_page_fcsd-actes-calendar' !== $hook ) {
        return;
    }

    wp_enqueue_style(
        'fcsd-actes-admin-style',
        get_template_directory_uri() . '/assets/css/calendari-admin.css',
        array(),
        FCSD_VERSION
    );

    wp_enqueue_script(
        'fcsd-actes-admin',
        get_template_directory_uri() . '/assets/js/calendari-admin.js',
        array( 'jquery' ),
        FCSD_VERSION,
        true
    );

    wp_localize_script(
        'fcsd-actes-admin',
        'fcsdActesAdmin',
        array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'fcsd_actes_quick_edit' ),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'fcsd_actes_admin_assets' );

/**
 * Alta ràpida d'un acte des del calendari (AJAX).
 */
function fcsd_actes_ajax_quick_create() {
    check_ajax_referer( 'fcsd_actes_quick_edit', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array(
            'message' => __( 'No tens permisos per crear actes.', 'fcsd' ),
        ) );
    }

    $title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
    $content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
    $date    = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';

    if ( '' === $title || '' === $date ) {
        wp_send_json_error( array(
            'message' => __( 'Falta títol o data.', 'fcsd' ),
        ) );
    }

    $start_raw = isset( $_POST['start'] ) ? sanitize_text_field( wp_unslash( $_POST['start'] ) ) : '';
    $end_raw   = isset( $_POST['end'] ) ? sanitize_text_field( wp_unslash( $_POST['end'] ) ) : '';

    if ( '' === $start_raw ) {
        $start_raw = $date . ' 09:00';
    }
    if ( '' === $end_raw ) {
        $end_raw = $date . ' 10:00';
    }

    $start_ts = strtotime( $start_raw );
    $end_ts   = strtotime( $end_raw );

    if ( ! $start_ts || ! $end_ts ) {
        wp_send_json_error( array(
            'message' => __( 'Dates invàlides.', 'fcsd' ),
        ) );
    }

    $post_id = wp_insert_post(
        array(
            'post_type'    => 'acte',
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'publish',
        ),
        true
    );

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( array(
            'message' => $post_id->get_error_message(),
        ) );
    }

    update_post_meta( $post_id, 'fcsd_acte_start', gmdate( 'Y-m-d H:i', $start_ts ) );
    update_post_meta( $post_id, 'fcsd_acte_end', gmdate( 'Y-m-d H:i', $end_ts ) );
    // Por defecto, los actes creados desde aquí los marcamos como "general".
    update_post_meta( $post_id, 'fcsd_acte_scope', 'general' );

    $needs_ticket = isset( $_POST['needs_ticket'] ) ? '1' : '';
    if ( $needs_ticket ) {
        update_post_meta( $post_id, 'fcsd_acte_needs_ticket', '1' );
    }

    wp_send_json_success( array(
        'id'       => $post_id,
        'editLink' => get_edit_post_link( $post_id, 'raw' ),
    ) );
}
add_action( 'wp_ajax_fcsd_actes_quick_create', 'fcsd_actes_ajax_quick_create' );

/**
 * Eliminació ràpida d'un acte des del calendari (AJAX).
 */
function fcsd_actes_ajax_quick_delete() {
    check_ajax_referer( 'fcsd_actes_quick_edit', 'nonce' );

    if ( ! current_user_can( 'delete_posts' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'No tens permisos per eliminar actes.', 'fcsd' ),
            )
        );
    }

    $post_id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

    if ( ! $post_id || 'acte' !== get_post_type( $post_id ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Acte no vàlid.', 'fcsd' ),
            )
        );
    }

    // L'enviem a la paperera
    $result = wp_trash_post( $post_id );

    if ( ! $result ) {
        wp_send_json_error(
            array(
                'message' => __( 'No s\'ha pogut eliminar l\'acte.', 'fcsd' ),
            )
        );
    }

    wp_send_json_success();
}
add_action( 'wp_ajax_fcsd_actes_quick_delete', 'fcsd_actes_ajax_quick_delete' );
