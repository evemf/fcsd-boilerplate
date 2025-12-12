<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * CPT "acte" + metaboxes + helpers de calendari + pantalla d'admin.
 *
 * - CPT "acte" per gestionar actes generals i laborals.
 * - Cada acte té: títol, descripció (cos), data inici/fi, color, tipus (general/laboral) i imatge destacada.
 * - Helpers per obtenir actes per rang de dates i per tipus (+ contracte).
 * - Pantalla d'admin "Calendari d'actes" amb vista mensual / anual.
 * - Pantalla d'admin "Llista d'actes" (agrupats per any/mes) amb cerca + bulk.
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
    // IMPORTANT: mantenim el meta antic (fcsd_acte_is_official_holiday) per compatibilitat,
    // però el camp principal passa a ser “fcsd_acte_type”.
    $acte_type     = get_post_meta( $post->ID, 'fcsd_acte_type', true ); // festiu | vacances | horari_reduit | pont
    $is_official   = (bool) get_post_meta( $post->ID, 'fcsd_acte_is_official_holiday', true );
    $contract_type = get_post_meta( $post->ID, 'fcsd_acte_contract_type', true );

    // Compatibilitat: si ve d'antics registres amb checkbox de festiu,
    // i no hi ha tipus, assumim "festiu".
    if ( empty( $acte_type ) && $is_official ) {
        $acte_type = 'festiu';
    }

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
            <label for="fcsd_acte_type">
                <?php esc_html_e( 'Categoria del dia (calendari laboral)', 'fcsd' ); ?>
            </label><br>
            <select name="fcsd_acte_type" id="fcsd_acte_type">
                <option value="" <?php selected( $acte_type, '' ); ?>>
                    <?php esc_html_e( '— Cap —', 'fcsd' ); ?>
                </option>
                <option value="festiu" <?php selected( $acte_type, 'festiu' ); ?>>
                    <?php esc_html_e( 'Festiu', 'fcsd' ); ?>
                </option>
                <option value="vacances" <?php selected( $acte_type, 'vacances' ); ?>>
                    <?php esc_html_e( 'Vacances', 'fcsd' ); ?>
                </option>
                <option value="horari_reduit" <?php selected( $acte_type, 'horari_reduit' ); ?>>
                    <?php esc_html_e( 'Horari reduït', 'fcsd' ); ?>
                </option>
                <option value="pont" <?php selected( $acte_type, 'pont' ); ?>>
                    <?php esc_html_e( 'Pont', 'fcsd' ); ?>
                </option>
            </select>
            <span class="description">
                <?php esc_html_e( 'Serveix per pintar el contorn del dia al calendari (p. ex. “Festiu: Any nou”).', 'fcsd' ); ?>
            </span>
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
                    <?php checked( $is_official || 'festiu' === $acte_type ); ?> />
                <?php esc_html_e( 'Festiu oficial del calendari laboral (compatibilitat)', 'fcsd' ); ?>
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

    // Categoria del dia (tipus d'acte) – nou camp
    if ( isset( $_POST['fcsd_acte_type'] ) ) {
        $acte_type = sanitize_text_field( wp_unslash( $_POST['fcsd_acte_type'] ) );
        if ( ! in_array( $acte_type, array( '', 'festiu', 'vacances', 'horari_reduit', 'pont' ), true ) ) {
            $acte_type = '';
        }

        if ( $acte_type === '' ) {
            delete_post_meta( $post_id, 'fcsd_acte_type' );
        } else {
            update_post_meta( $post_id, 'fcsd_acte_type', $acte_type );
        }
    }

    // Festiu oficial del calendari laboral (meta LEGACY)
    // - Si el nou tipus és “festiu”, mantenim aquest meta per no trencar codi antic.
    // - Si el nou tipus és un altre (o buit), eliminem el meta legacy.
    $legacy_is_official = isset( $_POST['fcsd_acte_is_official_holiday'] ) ? '1' : '';
    $saved_type         = get_post_meta( $post_id, 'fcsd_acte_type', true );
    if ( $saved_type === 'festiu' || $legacy_is_official ) {
        update_post_meta( $post_id, 'fcsd_acte_is_official_holiday', '1' );
        // Si només han marcat el checkbox legacy, també ho reflectim al nou camp.
        if ( empty( $saved_type ) ) {
            update_post_meta( $post_id, 'fcsd_acte_type', 'festiu' );
        }
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
    $acte_type      = get_post_meta( $post_id, 'fcsd_acte_type', true ); // festiu | vacances | horari_reduit | pont
    $is_official    = (bool) get_post_meta( $post_id, 'fcsd_acte_is_official_holiday', true );
    $contract_type  = get_post_meta( $post_id, 'fcsd_acte_contract_type', true );

    // Compatibilitat: si només tenim el meta legacy, assumim "festiu".
    if ( empty( $acte_type ) && $is_official ) {
        $acte_type = 'festiu';
    }

    // El flag legacy continua exposant-se, però ara també es deriva del nou tipus.
    if ( $acte_type === 'festiu' ) {
        $is_official = true;
    }

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
        'acte_type'          => $acte_type,
        'is_official_holiday'=> $is_official,
        'contract_type'      => $contract_type,
    );
}

/**
 * Devuelve los actes entre 2 timestamps.
 *
 * @param int         $start_ts       Timestamp inicio (UTC).
 * @param int         $end_ts         Timestamp fin (UTC).
 * @param string|null $scope          'general', 'laboral' o null para todos.
 * @param string|null $contract_type  '35h' | '37h' (solo si scope=laboral)
 * @return array
 */
function fcsd_actes_get_in_range( $start_ts, $end_ts, $scope = null, $contract_type = null ) {
    $start_ts = (int) $start_ts;
    $end_ts   = (int) $end_ts;

    if ( $start_ts <= 0 || $end_ts <= 0 || $end_ts < $start_ts ) {
        return array();
    }

    $args = array(
        'post_type'      => 'acte',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_key'       => 'fcsd_acte_start',
        'orderby'        => array(
            'meta_value' => 'ASC',
            'date'       => 'ASC',
        ),
    );

    $meta_query = array();

    if ( $scope ) {
        $meta_query[] = array(
            'key'   => 'fcsd_acte_scope',
            'value' => $scope,
        );
    }

    if ( $contract_type ) {
        $contract_type = sanitize_text_field( $contract_type );
        if ( ! in_array( $contract_type, array( '35h', '37h' ), true ) ) {
            $contract_type = null;
        }

        if ( $contract_type ) {
            $meta_query[] = array(
                'key'   => 'fcsd_acte_contract_type',
                'value' => $contract_type,
            );
        }
    }

    if ( ! empty( $meta_query ) ) {
        $args['meta_query'] = ( count( $meta_query ) > 1 )
            ? array_merge( array( 'relation' => 'AND' ), $meta_query )
            : $meta_query;
    }

    $query  = new WP_Query( $args );
    $result = array();

    if ( $query->have_posts() ) {
        foreach ( $query->posts as $post ) {
            $item = fcsd_acte_get_calendar_item( $post );

            if ( ! $item['start_ts'] || ! $item['end_ts'] ) {
                continue;
            }

            if ( $item['end_ts'] < $start_ts || $item['start_ts'] > $end_ts ) {
                continue;
            }

            $result[] = $item;
        }
    }

    wp_reset_postdata();

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

    add_submenu_page(
        'edit.php?post_type=acte',
        __( 'Llista d\'actes', 'fcsd' ),
        __( 'Llista d\'actes', 'fcsd' ),
        'edit_posts',
        'fcsd-actes-list',
        'fcsd_actes_render_admin_list_page'
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

    $contract = null;
    if ( 'laboral' === $scope ) {
        $contract = isset( $_GET['contract'] ) ? sanitize_text_field( wp_unslash( $_GET['contract'] ) ) : '35h';
        if ( ! in_array( $contract, array( '35h', '37h' ), true ) ) {
            $contract = '35h';
        }
    }
    $contract_arg = ( 'laboral' === $scope && $contract ) ? array( 'contract' => $contract ) : array();

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
                array_merge(
                    array(
                        'view'  => 'monthly',
                        'year'  => $year,
                        'month' => $month,
                        'scope' => $scope,
                    ),
                    $contract_arg
                ),
                $base_url
            );
            $annual_url  = add_query_arg(
                array_merge(
                    array(
                        'view'  => 'annual',
                        'year'  => $year,
                        'scope' => $scope,
                    ),
                    $contract_arg
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
                    'scope'    => 'laboral',
                    'view'     => $view,
                    'year'     => $year,
                    'month'    => $month,
                    'contract' => $contract ? $contract : '35h',
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

        <?php if ( 'laboral' === $scope ) : ?>
            <?php
            $contract_35_url = add_query_arg(
                array(
                    'scope'    => 'laboral',
                    'contract' => '35h',
                    'view'     => $view,
                    'year'     => $year,
                    'month'    => $month,
                ),
                $base_url
            );
            $contract_37_url = add_query_arg(
                array(
                    'scope'    => 'laboral',
                    'contract' => '37h',
                    'view'     => $view,
                    'year'     => $year,
                    'month'    => $month,
                ),
                $base_url
            );
            ?>
            <h2 class="nav-tab-wrapper" style="margin-top:10px;">
                <a href="<?php echo esc_url( $contract_35_url ); ?>" class="nav-tab <?php echo ( '35h' === $contract ) ? 'nav-tab-active' : ''; ?>">Contracte 35h</a>
                <a href="<?php echo esc_url( $contract_37_url ); ?>" class="nav-tab <?php echo ( '37h' === $contract ) ? 'nav-tab-active' : ''; ?>">Contracte 37h</a>
            </h2>
        <?php endif; ?>

        <div class="fcsd-actes-calendar-admin__inner">
            <?php
            if ( 'monthly' === $view ) {

                $month_start    = gmmktime( 0, 0, 0, $month, 1, $year );
                $days_in_month  = (int) gmdate( 't', $month_start );
                $month_end      = gmmktime( 23, 59, 59, $month, $days_in_month, $year );
                $first_weekday  = (int) gmdate( 'N', $month_start );

                $actes      = fcsd_actes_get_in_range( $month_start, $month_end, $scope, $contract );
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

                $prev_url = add_query_arg(
                    array_merge(
                        array(
                            'view'  => 'monthly',
                            'year'  => $prev_year,
                            'month' => $prev_month,
                            'scope' => $scope,
                        ),
                        $contract_arg
                    ),
                    $base_url
                );

                $next_url = add_query_arg(
                    array_merge(
                        array(
                            'view'  => 'monthly',
                            'year'  => $next_year,
                            'month' => $next_month,
                            'scope' => $scope,
                        ),
                        $contract_arg
                    ),
                    $base_url
                );
                ?>
                <div class="fcsd-actes-calendar-admin__toolbar">
                    <a class="button" href="<?php echo esc_url( $prev_url ); ?>">&laquo;</a>

                    <strong>
                        <?php echo esc_html( $month_names[ $month ] . ' ' . $year ); ?>
                    </strong>

                    <a class="button" href="<?php echo esc_url( $next_url ); ?>">&raquo;</a>
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

                            // Categories de dia (segons meta fcsd_acte_type)
                            $has_festiu        = false;
                            $has_vacances      = false;
                            $has_horari_reduit = false;
                            $has_pont          = false;
                            if ( ! empty( $day_actes ) ) {
                                foreach ( $day_actes as $item ) {
                                    if ( ! empty( $item['is_official_holiday'] ) ) {
                                        $has_festiu = true;
                                    }
                                    $t = ! empty( $item['acte_type'] ) ? $item['acte_type'] : '';
                                    if ( $t === 'festiu' ) {
                                        $has_festiu = true;
                                    } elseif ( $t === 'vacances' ) {
                                        $has_vacances = true;
                                    } elseif ( $t === 'horari_reduit' ) {
                                        $has_horari_reduit = true;
                                    } elseif ( $t === 'pont' ) {
                                        $has_pont = true;
                                    }
                                }
                            }

                            $classes = array( 'fcsd-actes-calendar__day' );
                            if ( $is_today ) {
                                $classes[] = 'is-today';
                            }
                            if ( ! empty( $day_actes ) ) {
                                $classes[] = 'has-events';
                            }
                            if ( $has_pont ) {
                                $classes[] = 'fcsd-actes-calendar__day--type-pont';
                            }
                            if ( $has_horari_reduit ) {
                                $classes[] = 'fcsd-actes-calendar__day--type-horari-reduit';
                            }
                            if ( $has_vacances ) {
                                $classes[] = 'fcsd-actes-calendar__day--type-vacances';
                            }
                            if ( $has_festiu ) {
                                $classes[] = 'fcsd-actes-calendar__day--type-festiu';
                                $classes[] = 'fcsd-actes-calendar__day--official-holiday';
                            }

                            $events_payload = array();
                            foreach ( $day_actes as $item ) {
                                $events_payload[] = array(
                                    'id'        => $item['ID'],
                                    'title'     => $item['title'],
                                    'start'     => $item['start_ts'],
                                    'edit_link' => get_edit_post_link( $item['ID'] ),
                                );
                            }

                            $new_url = add_query_arg(
                                array(
                                    'post_type'      => 'acte',
                                    'fcsd_acte_date' => $day_key,
                                ),
                                admin_url( 'post-new.php' )
                            );

                            echo '<td class="' . esc_attr( implode( ' ', $classes ) ) . '"'
                                . ' data-date="' . esc_attr( $day_key ) . '"'
                                . ' data-events=\'' . esc_attr( wp_json_encode( $events_payload ) ) . '\'>' . "\n";

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
                $prev_year_url = add_query_arg(
                    array_merge(
                        array(
                            'view'  => 'annual',
                            'year'  => $year - 1,
                            'scope' => $scope,
                        ),
                        $contract_arg
                    ),
                    $base_url
                );
                $next_year_url = add_query_arg(
                    array_merge(
                        array(
                            'view'  => 'annual',
                            'year'  => $year + 1,
                            'scope' => $scope,
                        ),
                        $contract_arg
                    ),
                    $base_url
                );
                ?>
                <div class="fcsd-actes-calendar-admin__toolbar">
                    <a class="button" href="<?php echo esc_url( $prev_year_url ); ?>">&laquo;</a>

                    <strong><?php echo esc_html( $year ); ?></strong>

                    <a class="button" href="<?php echo esc_url( $next_year_url ); ?>">&raquo;</a>
                </div>

                <div class="fcsd-actes-calendar-admin__annual-grid">
                    <?php
                    for ( $m = 1; $m <= 12; $m++ ) {
                        $month_start   = gmmktime( 0, 0, 0, $m, 1, $year );
                        $days_in_month = (int) gmdate( 't', $month_start );
                        $month_end     = gmmktime( 23, 59, 59, $m, $days_in_month, $year );
                        $first_weekday = (int) gmdate( 'N', $month_start );

                        $actes      = fcsd_actes_get_in_range( $month_start, $month_end, $scope, $contract );
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

                                            // Categories de dia (segons meta fcsd_acte_type)
                                            $has_festiu        = false;
                                            $has_vacances      = false;
                                            $has_horari_reduit = false;
                                            $has_pont          = false;
                                            if ( ! empty( $day_actes ) ) {
                                                foreach ( $day_actes as $item ) {
                                                    if ( ! empty( $item['is_official_holiday'] ) ) {
                                                        $has_festiu = true;
                                                    }
                                                    $t = ! empty( $item['acte_type'] ) ? $item['acte_type'] : '';
                                                    if ( $t === 'festiu' ) {
                                                        $has_festiu = true;
                                                    } elseif ( $t === 'vacances' ) {
                                                        $has_vacances = true;
                                                    } elseif ( $t === 'horari_reduit' ) {
                                                        $has_horari_reduit = true;
                                                    } elseif ( $t === 'pont' ) {
                                                        $has_pont = true;
                                                    }
                                                }
                                            }

                                            $classes = array( 'fcsd-actes-calendar__day' );
                                            if ( ! empty( $day_actes ) ) {
                                                $classes[] = 'has-events';
                                            }
                                            if ( $has_pont ) {
                                                $classes[] = 'fcsd-actes-calendar__day--type-pont';
                                            }
                                            if ( $has_horari_reduit ) {
                                                $classes[] = 'fcsd-actes-calendar__day--type-horari-reduit';
                                            }
                                            if ( $has_vacances ) {
                                                $classes[] = 'fcsd-actes-calendar__day--type-vacances';
                                            }
                                            if ( $has_festiu ) {
                                                $classes[] = 'fcsd-actes-calendar__day--type-festiu';
                                                $classes[] = 'fcsd-actes-calendar__day--official-holiday';
                                            }

                                            echo '<td class="' . esc_attr( implode( ' ', $classes ) ) . '" data-date="' . esc_attr( $day_key ) . '">';
                                            echo '<div class="fcsd-actes-calendar__day-number">' . (int) $day . '</div>';

                                            if ( ! empty( $day_actes ) ) {
                                                echo '<div class="fcsd-actes-calendar__dots">';
                                                foreach ( $day_actes as $item ) {
                                                    $edit_link = get_edit_post_link( $item['ID'] );
                                                    $color     = ! empty( $item['color'] ) ? $item['color'] : '#0073aa';

                                                    echo '<a class="fcsd-actes-calendar__dot"'
                                                        . ' href="' . esc_url( $edit_link ) . '"'
                                                        . ' title="' . esc_attr( $item['title'] ) . '"'
                                                        . ' style="background:' . esc_attr( $color ) . '"'
                                                        . '></a>';
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
 * Admin: vista de llista d'actes, agrupats per any i mes.
 */
function fcsd_actes_render_admin_list_page() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        return;
    }

    // =========================================================
    // BULK ACTIONS (paperera / eliminar definitivament)
    // =========================================================
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['fcsd_actes_bulk_nonce'] ) ) {

        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fcsd_actes_bulk_nonce'] ) ), 'fcsd_actes_bulk_action' ) ) {
            wp_die( 'Nonce invàlid.' );
        }

        $bulk_action = isset( $_POST['bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['bulk_action'] ) ) : '';
        $ids         = isset( $_POST['acte_ids'] ) ? array_map( 'intval', (array) $_POST['acte_ids'] ) : array();
        $ids         = array_values( array_filter( $ids ) );

        $done = 0;

        if ( $bulk_action && $ids ) {
            foreach ( $ids as $post_id ) {
                if ( ! current_user_can( 'delete_post', $post_id ) ) {
                    continue;
                }

                if ( 'trash' === $bulk_action ) {
                    if ( wp_trash_post( $post_id ) ) {
                        $done++;
                    }
                } elseif ( 'delete' === $bulk_action ) {
                    if ( wp_delete_post( $post_id, true ) ) {
                        $done++;
                    }
                }
            }
        }

        $redirect = add_query_arg(
            array(
                'post_type'     => 'acte',
                'page'          => 'fcsd-actes-list',
                'year'          => isset( $_POST['year'] ) ? (int) $_POST['year'] : (int) gmdate( 'Y', current_time( 'timestamp' ) ),
                'scope'         => isset( $_POST['scope'] ) ? sanitize_key( wp_unslash( $_POST['scope'] ) ) : 'general',
                'contract'      => isset( $_POST['contract'] ) ? sanitize_text_field( wp_unslash( $_POST['contract'] ) ) : '',
                'orderby'       => isset( $_POST['orderby'] ) ? sanitize_key( wp_unslash( $_POST['orderby'] ) ) : 'date',
                'order'         => isset( $_POST['order'] ) ? sanitize_key( wp_unslash( $_POST['order'] ) ) : 'asc',
                's'             => isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '',
                'bulk_done'     => $done,
                'bulk_action'   => $bulk_action,
            ),
            admin_url( 'edit.php' )
        );

        wp_safe_redirect( $redirect );
        exit;
    }

    $current_time = current_time( 'timestamp' );

    $order_by = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'date';
    $order    = ( isset( $_GET['order'] ) && 'desc' === strtolower( $_GET['order'] ) ) ? 'desc' : 'asc';
    if ( ! in_array( $order_by, array( 'date', 'title' ), true ) ) {
        $order_by = 'date';
    }

    $year = isset( $_GET['year'] ) ? (int) $_GET['year'] : (int) gmdate( 'Y', $current_time );

    $scope  = ( isset( $_GET['scope'] ) && 'laboral' === $_GET['scope'] ) ? 'laboral' : 'general';

    // ✅ FIX: unslash + sanitize
    $search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

    $contract = null;
    if ( 'laboral' === $scope ) {
        $contract = isset( $_GET['contract'] ) ? sanitize_text_field( wp_unslash( $_GET['contract'] ) ) : '35h';
        if ( ! in_array( $contract, array( '35h', '37h' ), true ) ) {
            $contract = '35h';
        }
    }
    $contract_arg = ( 'laboral' === $scope && $contract ) ? array( 'contract' => $contract ) : array();

    $base_url = add_query_arg(
        array(
            'post_type' => 'acte',
            'page'      => 'fcsd-actes-list',
        ),
        admin_url( 'edit.php' )
    );

    $calendar_base_url = add_query_arg(
        array(
            'post_type' => 'acte',
            'page'      => 'fcsd-actes-calendar',
        ),
        admin_url( 'edit.php' )
    );

    $month  = (int) gmdate( 'n', $current_time );
    $monthly_url = add_query_arg(
        array_merge(
            array(
                'view'  => 'monthly',
                'year'  => $year,
                'month' => $month,
                'scope' => $scope,
            ),
            $contract_arg
        ),
        $calendar_base_url
    );
    $annual_url = add_query_arg(
        array_merge(
            array(
                'view'  => 'annual',
                'year'  => $year,
                'scope' => $scope,
            ),
            $contract_arg
        ),
        $calendar_base_url
    );
    $list_url = add_query_arg(
        array_merge(
            array(
                'year'  => $year,
                'scope' => $scope,
                'orderby' => $order_by,
                'order'   => $order,
                's'       => $search,
            ),
            $contract_arg
        ),
        $base_url
    );

    $year_start = gmmktime( 0, 0, 0, 1, 1, $year );
    $year_end   = gmmktime( 23, 59, 59, 12, 31, $year );

    $actes = function_exists( 'fcsd_actes_get_in_range' )
        ? fcsd_actes_get_in_range( $year_start, $year_end, $scope, $contract )
        : array();

    usort( $actes, function( $a, $b ) use ( $order_by, $order ) {
        if ( 'title' === $order_by ) {
            $cmp = strcasecmp( (string) $a['title'], (string) $b['title'] );
        } else {
            $cmp = ( (int) $a['start_ts'] ) <=> ( (int) $b['start_ts'] );
        }
        return ( 'asc' === $order ) ? $cmp : -$cmp;
    });

    // ✅ FIX: búsqueda robusta (acentos) + no falla con slashes
    if ( $search !== '' ) {
        $actes = array_filter( $actes, function( $item ) use ( $search ) {
            $haystack = (string) ( $item['title'] ?? '' );
            if ( function_exists( 'mb_stripos' ) ) {
                return mb_stripos( $haystack, $search, 0, 'UTF-8' ) !== false;
            }
            return stripos( $haystack, $search ) !== false;
        } );
    }

    $by_month = array();
    foreach ( $actes as $item ) {
        $start_ts = (int) $item['start_ts'];
        if ( ! $start_ts ) {
            continue;
        }
        $item_year  = (int) gmdate( 'Y', $start_ts );
        if ( $item_year !== $year ) {
            continue;
        }
        $item_month = (int) gmdate( 'n', $start_ts );
        if ( ! isset( $by_month[ $item_month ] ) ) {
            $by_month[ $item_month ] = array();
        }
        $by_month[ $item_month ][] = $item;
    }
    ksort( $by_month );

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

    $prev_year_url = add_query_arg(
        array_merge(
            array(
                'year'    => $year - 1,
                'scope'   => $scope,
                'orderby' => $order_by,
                'order'   => $order,
                's'       => $search,
            ),
            $contract_arg
        ),
        $base_url
    );
    $next_year_url = add_query_arg(
        array_merge(
            array(
                'year'    => $year + 1,
                'scope'   => $scope,
                'orderby' => $order_by,
                'order'   => $order,
                's'       => $search,
            ),
            $contract_arg
        ),
        $base_url
    );

    $scope_general_url = add_query_arg(
        array(
            'year'    => $year,
            'scope'   => 'general',
            'orderby' => $order_by,
            'order'   => $order,
            's'       => $search,
        ),
        $base_url
    );
    $scope_laboral_url = add_query_arg(
        array(
            'year'     => $year,
            'scope'    => 'laboral',
            'contract' => $contract ? $contract : '35h',
            'orderby'  => $order_by,
            'order'    => $order,
            's'        => $search,
        ),
        $base_url
    );

    // URLs d’ordenació (manté any, scope, contracte, cerca, etc.)
    $date_order  = ( 'date' === $order_by && 'asc' === $order ) ? 'desc' : 'asc';
    $title_order = ( 'title' === $order_by && 'asc' === $order ) ? 'desc' : 'asc';

    $date_url = add_query_arg(
        array_merge(
            array(
                'year'    => $year,
                'scope'   => $scope,
                'orderby' => 'date',
                'order'   => $date_order,
                's'       => $search,
            ),
            $contract_arg
        ),
        $base_url
    );

    $title_url = add_query_arg(
        array_merge(
            array(
                'year'    => $year,
                'scope'   => $scope,
                'orderby' => 'title',
                'order'   => $title_order,
                's'       => $search,
            ),
            $contract_arg
        ),
        $base_url
    );

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <?php esc_html_e( 'Llista d\'actes', 'fcsd' ); ?>
        </h1>

        <?php if ( isset( $_GET['bulk_done'], $_GET['bulk_action'] ) ) : ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php
                    $n = (int) $_GET['bulk_done'];
                    $a = sanitize_key( $_GET['bulk_action'] );
                    if ( 'trash' === $a ) {
                        echo esc_html( sprintf( 'Actes moguts a la paperera: %d', $n ) );
                    } elseif ( 'delete' === $a ) {
                        echo esc_html( sprintf( 'Actes eliminats definitivament: %d', $n ) );
                    } else {
                        echo esc_html( sprintf( 'Acció aplicada: %d', $n ) );
                    }
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <h2 class="nav-tab-wrapper">
            <a href="<?php echo esc_url( $monthly_url ); ?>" class="nav-tab">
                <?php esc_html_e( 'Vista mensual', 'fcsd' ); ?>
            </a>
            <a href="<?php echo esc_url( $annual_url ); ?>" class="nav-tab">
                <?php esc_html_e( 'Vista anual', 'fcsd' ); ?>
            </a>
            <a href="<?php echo esc_url( $list_url ); ?>" class="nav-tab nav-tab-active">
                <?php esc_html_e( 'Vista en llista', 'fcsd' ); ?>
            </a>
        </h2>

        <ul class="subsubsub fcsd-actes-calendar-admin__scope-tabs">
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

        <?php if ( 'laboral' === $scope ) : ?>
            <?php
            $contract_35_url = add_query_arg(
                array(
                    'year'     => $year,
                    'scope'    => 'laboral',
                    'contract' => '35h',
                    'orderby'  => $order_by,
                    'order'    => $order,
                    's'        => $search,
                ),
                $base_url
            );
            $contract_37_url = add_query_arg(
                array(
                    'year'     => $year,
                    'scope'    => 'laboral',
                    'contract' => '37h',
                    'orderby'  => $order_by,
                    'order'    => $order,
                    's'        => $search,
                ),
                $base_url
            );
            ?>
            <h2 class="nav-tab-wrapper" style="margin-top:10px;">
                <a href="<?php echo esc_url( $contract_35_url ); ?>" class="nav-tab <?php echo ( '35h' === $contract ) ? 'nav-tab-active' : ''; ?>">Contracte 35h</a>
                <a href="<?php echo esc_url( $contract_37_url ); ?>" class="nav-tab <?php echo ( '37h' === $contract ) ? 'nav-tab-active' : ''; ?>">Contracte 37h</a>
            </h2>
        <?php endif; ?>

        <div class="tablenav top" style="margin-top: 10px;">
            <div class="alignleft actions">
                <a class="button" href="<?php echo esc_url( $prev_year_url ); ?>">&laquo; <?php echo esc_html( $year - 1 ); ?></a>
                <span style="margin: 0 10px; font-weight: 600;"><?php echo esc_html( $year ); ?></span>
                <a class="button" href="<?php echo esc_url( $next_year_url ); ?>"><?php echo esc_html( $year + 1 ); ?> &raquo;</a>
            </div>
            <br class="clear" />
        </div>

        <!-- ✅ Buscador ÚNICO (no se repite por mes) -->
        <form method="get" style="margin:10px 0;">
            <input type="hidden" name="post_type" value="acte">
            <input type="hidden" name="page" value="fcsd-actes-list">
            <input type="hidden" name="year" value="<?php echo esc_attr( $year ); ?>">
            <input type="hidden" name="scope" value="<?php echo esc_attr( $scope ); ?>">
            <?php if ( 'laboral' === $scope && $contract ) : ?>
                <input type="hidden" name="contract" value="<?php echo esc_attr( $contract ); ?>">
            <?php endif; ?>
            <input type="hidden" name="orderby" value="<?php echo esc_attr( $order_by ); ?>">
            <input type="hidden" name="order" value="<?php echo esc_attr( $order ); ?>">

            <input type="search"
                name="s"
                value="<?php echo esc_attr( $search ); ?>"
                placeholder="Filtrar per títol…"
                style="min-width:250px;">

            <button class="button">Filtrar</button>

            <?php if ( $search !== '' ) : ?>
                <a class="button" style="margin-left:6px;"
                   href="<?php echo esc_url( add_query_arg( array_merge(
                       array(
                           'year'    => $year,
                           'scope'   => $scope,
                           'orderby' => $order_by,
                           'order'   => $order,
                           's'       => '',
                       ),
                       $contract_arg
                   ), $base_url ) ); ?>">
                    Netejar
                </a>
            <?php endif; ?>
        </form>

        <?php if ( empty( $by_month ) ) : ?>
            <p><?php esc_html_e( 'No hi ha actes per a aquest any i àmbit.', 'fcsd' ); ?></p>
        <?php else : ?>

            <?php foreach ( $by_month as $m => $items ) : ?>
                <?php
                if ( 'date' === $order_by ) {
                    usort(
                        $items,
                        function ( $a, $b ) {
                            $a_ts = (int) $a['start_ts'];
                            $b_ts = (int) $b['start_ts'];
                            if ( $a_ts === $b_ts ) {
                                return $a['ID'] <=> $b['ID'];
                            }
                            return $a_ts <=> $b_ts;
                        }
                    );
                }

                $month_label = isset( $month_names[ $m ] ) ? $month_names[ $m ] : $m;
                $new_url     = add_query_arg(
                    array(
                        'post_type'      => 'acte',
                        'fcsd_acte_date' => sprintf( '%04d-%02d-01', $year, $m ),
                    ),
                    admin_url( 'post-new.php' )
                );
                ?>

                <h2 style="margin-top:30px;">
                    <?php echo esc_html( $month_label . ' ' . $year ); ?>
                    <a href="<?php echo esc_url( $new_url ); ?>" class="page-title-action">
                        <?php esc_html_e( 'Afegir acte', 'fcsd' ); ?>
                    </a>
                </h2>

                <form method="post" class="fcsd-actes-bulk-form" style="margin:10px 0;">
                    <?php wp_nonce_field( 'fcsd_actes_bulk_action', 'fcsd_actes_bulk_nonce' ); ?>

                    <input type="hidden" name="post_type" value="acte">
                    <input type="hidden" name="page" value="fcsd-actes-list">
                    <input type="hidden" name="year" value="<?php echo esc_attr( $year ); ?>">
                    <input type="hidden" name="scope" value="<?php echo esc_attr( $scope ); ?>">
                    <?php if ( 'laboral' === $scope && $contract ) : ?>
                        <input type="hidden" name="contract" value="<?php echo esc_attr( $contract ); ?>">
                    <?php endif; ?>
                    <input type="hidden" name="orderby" value="<?php echo esc_attr( $order_by ); ?>">
                    <input type="hidden" name="order" value="<?php echo esc_attr( $order ); ?>">
                    <input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>">

                    <div class="tablenav top">
                        <div class="alignleft actions bulkactions">
                            <label class="screen-reader-text" for="fcsd-bulk-action"><?php esc_html_e( 'Accions massives', 'fcsd' ); ?></label>
                            <select name="bulk_action" id="fcsd-bulk-action">
                                <option value=""><?php esc_html_e( 'Accions massives', 'fcsd' ); ?></option>
                                <option value="trash"><?php esc_html_e( 'Moure a la paperera', 'fcsd' ); ?></option>
                                <option value="delete"><?php esc_html_e( 'Eliminar definitivament', 'fcsd' ); ?></option>
                            </select>

                            <button type="submit" class="button action" id="fcsd-actes-bulk-apply">
                                <?php esc_html_e( 'Aplicar', 'fcsd' ); ?>
                            </button>
                        </div>
                        <br class="clear" />
                    </div>

                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <td class="manage-column column-cb check-column">
                                    <input type="checkbox" class="fcsd-actes-select-all" />
                                </td>
                                <th>
                                    <a href="<?php echo esc_url( $date_url ); ?>">
                                        <?php esc_html_e( 'Data', 'fcsd' ); ?>
                                    </a>
                                </th>
                                <th><?php esc_html_e( 'Hora', 'fcsd' ); ?></th>
                                <th>
                                    <a href="<?php echo esc_url( $title_url ); ?>">
                                        <?php esc_html_e( 'Títol', 'fcsd' ); ?>
                                    </a>
                                </th>
                                <th><?php esc_html_e( 'Contracte', 'fcsd' ); ?></th>
                                <th><?php esc_html_e( 'Festiu oficial', 'fcsd' ); ?></th>
                                <th><?php esc_html_e( 'Accions', 'fcsd' ); ?></th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ( $items as $item ) :
                                $start_ts    = (int) $item['start_ts'];
                                $date_label  = $start_ts ? date_i18n( get_option( 'date_format' ), $start_ts ) : '';
                                $time_label  = $start_ts ? date_i18n( get_option( 'time_format' ), $start_ts ) : '';
                                $title       = ! empty( $item['title'] ) ? $item['title'] : get_the_title( $item['ID'] );
                                $contract_v  = ! empty( $item['contract_type'] ) ? $item['contract_type'] : '';
                                $is_official = ! empty( $item['is_official_holiday'] );

                                $edit_link   = get_edit_post_link( $item['ID'] );
                                $delete_link = get_delete_post_link( $item['ID'] );
                                ?>
                                <tr>
                                    <th class="check-column">
                                        <input type="checkbox"
                                            name="acte_ids[]"
                                            value="<?php echo esc_attr( (int) $item['ID'] ); ?>"
                                            class="fcsd-actes-row-checkbox" />
                                    </th>

                                    <td><?php echo esc_html( $date_label ); ?></td>
                                    <td><?php echo esc_html( $time_label ); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url( $edit_link ); ?>">
                                            <?php echo esc_html( $title ); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html( $contract_v ); ?></td>
                                    <td><?php echo $is_official ? '&#10003;' : ''; ?></td>
                                    <td>
                                        <a href="<?php echo esc_url( $edit_link ); ?>">
                                            <?php esc_html_e( 'Editar', 'fcsd' ); ?>
                                        </a>
                                        |
                                        <a href="<?php echo esc_url( $delete_link ); ?>">
                                            <?php esc_html_e( 'Moure a la paperera', 'fcsd' ); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                </form>
            <?php endforeach; ?>

        <?php endif; ?>
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
 */
function fcsd_actes_redirect_list_to_calendar() {
    global $typenow;

    if ( 'acte' !== $typenow ) {
        return;
    }

    if ( isset( $_GET['page'] ) && in_array( $_GET['page'], array( 'fcsd-actes-calendar', 'fcsd-actes-list' ), true ) ) {
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

    $allowed_hooks = array(
        'acte_page_fcsd-actes-calendar',
        'acte_page_fcsd-actes-list',
    );

    if ( ! in_array( $hook, $allowed_hooks, true ) ) {
        return;
    }

    wp_enqueue_style(
        'fcsd-actes-admin-style',
        get_template_directory_uri() . '/assets/css/calendari-admin.css',
        array(),
        defined( 'FCSD_VERSION' ) ? FCSD_VERSION : null
    );

    wp_enqueue_script(
        'fcsd-actes-admin',
        get_template_directory_uri() . '/assets/js/calendari-admin.js',
        array( 'jquery' ),
        defined( 'FCSD_VERSION' ) ? FCSD_VERSION : null,
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
