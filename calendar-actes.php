<?php
/**
 * Template Name: Calendari d'actes (general)
 *
 * Calendari públic d'actes generals, amb vista mensual / anual.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

if ( have_posts() ) :
    the_post();

    $current_time = current_time( 'timestamp' );

    // Vista: monthly (per defecte) o annual
    $view_raw = isset( $_GET['act_view'] ) ? sanitize_text_field( wp_unslash( $_GET['act_view'] ) ) : '';
    $view     = ( 'annual' === $view_raw ) ? 'annual' : 'monthly';

    // Any i mes actuals per defecte
    $year  = isset( $_GET['act_year'] ) ? (int) $_GET['act_year'] : (int) gmdate( 'Y', $current_time );
    $month = isset( $_GET['act_month'] ) ? (int) $_GET['act_month'] : (int) gmdate( 'n', $current_time );

    // Normalitzar any (evitem valors absurds que trenquin la navegació)
    $year_now = (int) gmdate( 'Y', $current_time );
    if ( $year < 1970 || $year > $year_now + 10 ) {
        $year = $year_now;
    }

    // Normalitzar mes
    if ( $month < 1 || $month > 12 ) {
        $month = (int) gmdate( 'n', $current_time );
    }

    // Tipus d'actes que mostra aquest template
    $scope = 'general';

    // URL base neta (eliminem els nous paràmetres també)
    $base_url = remove_query_arg(
        array( 'act_view', 'act_year', 'act_month', 'scope' ),
        get_permalink()
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

    $today_key = gmdate( 'Y-m-d', $current_time );

    /**
     * Fallback de títol:
     * - Si l'Acte té títol, el mostrem.
     * - Si no en té, mostrem el tipus (festiu/vacances/horari_reduit/pont).
     * - Legacy: is_official_holiday => festiu.
     */
    $fcsd_get_item_display_title = static function( array $item ) : string {
        $title = isset( $item['title'] ) ? trim( (string) $item['title'] ) : '';
        $type  = isset( $item['acte_type'] ) ? (string) $item['acte_type'] : '';

        if ( $type === '' && ! empty( $item['is_official_holiday'] ) ) {
            $type = 'festiu';
        }

        $labels = array(
            'festiu'        => __( 'Festiu', 'fcsd' ),
            'vacances'      => __( 'Vacances', 'fcsd' ),
            'horari_reduit' => __( 'Horari reduït', 'fcsd' ),
            'pont'          => __( 'Pont', 'fcsd' ),
        );

        if ( $title !== '' ) {
            return $title;
        }

        return $labels[ $type ] ?? __( 'Acte', 'fcsd' );
    
    };

    $fcsd_hex_to_rgba = static function( string $hex, float $alpha = 1.0 ) : string {
    $hex = ltrim( trim( $hex ), '#' );
    $alpha = max( 0.0, min( 1.0, $alpha ) );

    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }

    if ( strlen( $hex ) !== 6 ) {
        return 'rgba(0,0,0,' . $alpha . ')';
    }

    $r = hexdec( substr( $hex, 0, 2 ) );
    $g = hexdec( substr( $hex, 2, 2 ) );
    $b = hexdec( substr( $hex, 4, 2 ) );

    return "rgba($r,$g,$b,$alpha)";
};

    /**
     * Prepara el payload (JSON) dels actes d'un dia per al modal de consulta del frontend.
     */
    $fcsd_build_day_events_payload = static function( array $day_items ) use ( $fcsd_get_item_display_title ) : array {
        $out = array();
        foreach ( $day_items as $item ) {
            $id = ! empty( $item['ID'] ) ? (int) $item['ID'] : 0;
            $start_ts = ! empty( $item['start_ts'] ) ? (int) $item['start_ts'] : 0;
            $end_ts   = ! empty( $item['end_ts'] ) ? (int) $item['end_ts'] : 0;

            $time_range = '';
            if ( $start_ts ) {
                $start_date = date_i18n( get_option( 'date_format' ), $start_ts );
                $start_time = date_i18n( get_option( 'time_format' ), $start_ts );
                $time_range = $start_date . ' · ' . $start_time;
            }
            if ( $end_ts && $end_ts !== $start_ts ) {
                $end_date = date_i18n( get_option( 'date_format' ), $end_ts );
                $end_time = date_i18n( get_option( 'time_format' ), $end_ts );
                $time_range .= ' — ' . $end_date . ' · ' . $end_time;
            }

            $content = '';
            if ( $id ) {
                $raw_content = get_post_field( 'post_content', $id );
                if ( is_string( $raw_content ) && trim( $raw_content ) !== '' ) {
                    $content = wp_kses_post( apply_filters( 'the_content', $raw_content ) );
                }
            }

            $out[] = array(
                'ID'          => $id,
                'title'       => $fcsd_get_item_display_title( $item ),
                'permalink'   => $item['permalink'] ?? '',
                'excerpt'     => $item['excerpt'] ?? '',
                'content'     => $content,
                'thumb'       => $item['thumb'] ?? '',
                'time_range'  => $time_range,
                'color'       => $item['color'] ?? '',
                'needs_ticket'=> ! empty( $item['needs_ticket'] ),
                'acte_type'   => $item['acte_type'] ?? '',
                'scope'       => $item['scope'] ?? '',
            );
        }
        return $out;
    };

    ?>
    <div class="container content py-5">
        <article id="post-<?php the_ID(); ?>" <?php post_class( 'actes-page actes-page--public' ); ?>>
            <header class="page-header mb-4">
                <h1 class="mb-2"><?php the_title(); ?></h1>
            </header>

            <div class="actes-calendar layout-panel actes-calendar--public">
                <div class="actes-calendar__toolbar">
                    <div class="actes-calendar__view-toggle">
                        <a href="<?php echo esc_url( add_query_arg( array(
                            'act_view'  => 'monthly',
                            'act_year'  => $year,
                            'act_month' => $month,
                        ), $base_url ) ); ?>"
                           class="button <?php echo ( 'monthly' === $view ) ? 'button-primary' : 'button-secondary'; ?>">
                            <?php esc_html_e( 'Mensual', 'fcsd' ); ?>
                        </a>
                        <a href="<?php echo esc_url( add_query_arg( array(
                            'act_view' => 'annual',
                            'act_year' => $year,
                        ), $base_url ) ); ?>"
                           class="button <?php echo ( 'annual' === $view ) ? 'button-primary' : 'button-secondary'; ?>">
                            <?php esc_html_e( 'Anual', 'fcsd' ); ?>
                        </a>
                    </div>

                    <div class="actes-calendar__nav">
                        <?php if ( 'monthly' === $view ) : ?>
                            <?php
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
                            <a class="button button--ghost"
                               href="<?php echo esc_url( add_query_arg( array(
                                   'act_view'  => 'monthly',
                                   'act_year'  => $prev_year,
                                   'act_month' => $prev_month,
                               ), $base_url ) ); ?>">
                                &laquo;
                            </a>
                            <span class="actes-calendar__current">
                                <?php echo esc_html( $month_names[ $month ] . ' ' . $year ); ?>
                            </span>
                            <a class="button button--ghost"
                               href="<?php echo esc_url( add_query_arg( array(
                                   'act_view'  => 'monthly',
                                   'act_year'  => $next_year,
                                   'act_month' => $next_month,
                               ), $base_url ) ); ?>">
                                &raquo;
                            </a>
                        <?php else : ?>
                            <a class="button button--ghost"
                               href="<?php echo esc_url( add_query_arg( array(
                                   'act_view' => 'annual',
                                   'act_year' => $year - 1,
                               ), $base_url ) ); ?>">
                                &laquo;
                            </a>
                            <span class="actes-calendar__current">
                                <?php echo esc_html( $year ); ?>
                            </span>
                            <a class="button button--ghost"
                               href="<?php echo esc_url( add_query_arg( array(
                                   'act_view' => 'annual',
                                   'act_year' => $year + 1,
                               ), $base_url ) ); ?>">
                                &raquo;
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="actes-calendar__body">
                    <?php if ( 'monthly' === $view ) : ?>
                        <?php
                        $month_start   = gmmktime( 0, 0, 0, $month, 1, $year );
                        $days_in_month = (int) gmdate( 't', $month_start );
                        $month_end     = gmmktime( 23, 59, 59, $month, $days_in_month, $year );
                        $first_weekday = (int) gmdate( 'N', $month_start );

                        $items      = function_exists( 'fcsd_actes_get_in_range' ) ? fcsd_actes_get_in_range( $month_start, $month_end, $scope ) : array();
                        $items_days = function_exists( 'fcsd_actes_group_by_day' ) ? fcsd_actes_group_by_day( $items, $month_start, $month_end ) : array();
                        ?>
                        <div class="actes-calendar__grid">
                            <div class="actes-calendar__week-days">
                                <?php foreach ( $week_days_short as $label ) : ?>
                                    <div class="actes-calendar__week-day"><?php echo esc_html( $label ); ?></div>
                                <?php endforeach; ?>
                            </div>
                            <div class="actes-calendar__weeks">
                                <?php
                                // celdas vacías hasta el primer día
                                for ( $i = 1; $i < $first_weekday; $i++ ) {
                                    echo '<div class="actes-calendar__day actes-calendar__day--empty"></div>';
                                }

                                for ( $day = 1; $day <= $days_in_month; $day++ ) {
                                    $current_ts = gmmktime( 0, 0, 0, $month, $day, $year );
                                    $day_key    = gmdate( 'Y-m-d', $current_ts );
                                    $day_items  = isset( $items_days[ $day_key ] ) ? $items_days[ $day_key ] : array();

                                    // Categories de dia (segons meta fcsd_acte_type)
                                    $has_festiu        = false;
                                    $has_vacances      = false;
                                    $has_horari_reduit = false;
                                    $has_pont          = false;
                                    if ( ! empty( $day_items ) ) {
                                        foreach ( $day_items as $item ) {
                                            $event_color = ! empty( $item['color'] ) ? (string) $item['color'] : '#0073aa';
                                            $event_bg    = $fcsd_hex_to_rgba( $event_color, 0.12 );
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

                                    $classes = array( 'actes-calendar__day' );
                                    if ( $day_key === $today_key ) {
                                        $classes[] = 'actes-calendar__day--today';
                                    }
                                    if ( ! empty( $day_items ) ) {
                                        $classes[] = 'actes-calendar__day--has-events';
                                    }
                                    if ( $has_pont ) {
                                        $classes[] = 'actes-calendar__day--type-pont';
                                    }
                                    if ( $has_horari_reduit ) {
                                        $classes[] = 'actes-calendar__day--type-horari-reduit';
                                    }
                                    if ( $has_vacances ) {
                                        $classes[] = 'actes-calendar__day--type-vacances';
                                    }
                                    if ( $has_festiu ) {
                                        $classes[] = 'actes-calendar__day--type-festiu';
                                        $classes[] = 'actes-calendar__day--official-holiday';
                                    }
                                    ?>
                                    <?php
                                    $data_events = '';
                                    if ( ! empty( $day_items ) ) {
                                        $payload     = $fcsd_build_day_events_payload( $day_items );
                                        $data_events = esc_attr( wp_json_encode( $payload ) );
                                    }
                                    ?>
                                    <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"<?php echo ! empty( $day_items ) ? ' data-day="' . esc_attr( $day_key ) . '" data-events="' . $data_events . '"' : ''; ?>>
                                        <div class="actes-calendar__day-number">
                                            <?php echo (int) $day; ?>
                                        </div>

                                        <?php if ( ! empty( $day_items ) ) : ?>
                                            <ul class="actes-calendar__events">
                                                <?php foreach ( $day_items as $item ) : ?>
                                                    <?php
                                                    $time_label = '';
                                                    if ( ! empty( $item['start_ts'] ) ) {
                                                        $time_label = date_i18n( get_option( 'time_format' ), (int) $item['start_ts'] );
                                                    }
                                                    $display_title = $fcsd_get_item_display_title( $item );
                                                    $tooltip_text = $display_title;
                                                    if ( $time_label !== '' ) {
                                                        $tooltip_text .= "\n" . $time_label;
                                                    }
                                                    ?>
                                                    <li class="actes-calendar__event"
    style="border-left-color: <?php echo esc_attr( $event_color ); ?>;">

                                                        <a href="<?php echo esc_url( $item['permalink'] ); ?>" class="actes-calendar__event-link" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr( $tooltip_text ); ?>">
                                                            <div class="actes-calendar__event-header">
                                               
                                                                <span class="actes-calendar__event-title">
                                                                    <?php echo esc_html( $display_title ); ?>
                                                                </span>
                                                            </div>

                                                            <?php if ( ! empty( $item['needs_ticket'] ) ) : ?>
                                                                <span class="actes-calendar__event-badge">
                                                                    <?php esc_html_e( 'Entrada prèvia', 'fcsd' ); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                    <?php else : // VISTA ANUAL ?>
                        <div class="actes-calendar__annual">
                            <?php for ( $m = 1; $m <= 12; $m++ ) : ?>
                                <?php
                                $month_start   = gmmktime( 0, 0, 0, $m, 1, $year );
                                $days_in_month = (int) gmdate( 't', $month_start );
                                $month_end     = gmmktime( 23, 59, 59, $m, $days_in_month, $year );
                                $first_weekday = (int) gmdate( 'N', $month_start );

                                $items      = function_exists( 'fcsd_actes_get_in_range' ) ? fcsd_actes_get_in_range( $month_start, $month_end, $scope ) : array();
                                $items_days = function_exists( 'fcsd_actes_group_by_day' ) ? fcsd_actes_group_by_day( $items, $month_start, $month_end ) : array();
                                ?>
                                <section class="actes-calendar__annual-month">
                                    <h2 class="actes-calendar__annual-title">
                                        <?php echo esc_html( $month_names[ $m ] ); ?>
                                    </h2>
                                    <div class="actes-calendar__grid actes-calendar__grid--compact">
                                        <div class="actes-calendar__week-days">
                                            <?php foreach ( $week_days_short as $label ) : ?>
                                                <div class="actes-calendar__week-day">
                                                    <?php echo esc_html( $label ); ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="actes-calendar__weeks">
                                            <?php
                                            for ( $i = 1; $i < $first_weekday; $i++ ) {
                                                echo '<div class="actes-calendar__day actes-calendar__day--empty"></div>';
                                            }

                                            for ( $day = 1; $day <= $days_in_month; $day++ ) {
                                                $current_ts = gmmktime( 0, 0, 0, $m, $day, $year );
                                                $day_key    = gmdate( 'Y-m-d', $current_ts );
                                                $day_items  = isset( $items_days[ $day_key ] ) ? $items_days[ $day_key ] : array();

                                                // Categories de dia (segons meta fcsd_acte_type)
                                                $has_festiu        = false;
                                                $has_vacances      = false;
                                                $has_horari_reduit = false;
                                                $has_pont          = false;
                                                if ( ! empty( $day_items ) ) {
                                                    foreach ( $day_items as $item ) {
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

                                                $classes = array( 'actes-calendar__day', 'actes-calendar__day--compact' );
                                                if ( $day_key === $today_key ) {
                                                    $classes[] = 'actes-calendar__day--today';
                                                }
                                                if ( ! empty( $day_items ) ) {
                                                    $classes[] = 'actes-calendar__day--has-events';
                                                }
                                                if ( $has_pont ) {
                                                    $classes[] = 'actes-calendar__day--type-pont';
                                                }
                                                if ( $has_horari_reduit ) {
                                                    $classes[] = 'actes-calendar__day--type-horari-reduit';
                                                }
                                                if ( $has_vacances ) {
                                                    $classes[] = 'actes-calendar__day--type-vacances';
                                                }
                                                if ( $has_festiu ) {
                                                    $classes[] = 'actes-calendar__day--type-festiu';
                                                    $classes[] = 'actes-calendar__day--official-holiday';
                                                }
                                                ?>
                                                <?php
                                                $data_events = '';
                                                if ( ! empty( $day_items ) ) {
                                                    $payload     = $fcsd_build_day_events_payload( $day_items );
                                                    $data_events = esc_attr( wp_json_encode( $payload ) );
                                                }
                                                ?>
                                                <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"<?php echo ! empty( $day_items ) ? ' data-day="' . esc_attr( $day_key ) . '" data-events="' . $data_events . '"' : ''; ?>>
                                                    <div class="actes-calendar__day-number">
                                                        <?php echo (int) $day; ?>
                                                    </div>
                                                    <?php if ( ! empty( $day_items ) ) : ?>
                                                        <div class="actes-calendar__dots">
                                                            <?php foreach ( $day_items as $item ) : ?>
                                                                <?php 
                                                                $display_title = $fcsd_get_item_display_title( $item );
$dot_color     = ! empty( $item['color'] ) ? (string) $item['color'] : '#0073aa';

                                                                    ?>
                                                                <?php $display_title = $fcsd_get_item_display_title( $item ); ?>
                                                                <?php
                                                                $tooltip_text = $display_title;
                                                                if ( ! empty( $item['start_ts'] ) ) {
                                                                    $tooltip_text .= "\n" . date_i18n( get_option( 'time_format' ), (int) $item['start_ts'] );
                                                                }
                                                                ?>
                                                                <span class="actes-calendar__dot"
      style="background: <?php echo esc_attr( $dot_color ); ?>;"
      tabindex="0"
      data-bs-toggle="tooltip"
      data-bs-placement="top"
      aria-label="<?php echo esc_attr( $tooltip_text ); ?>"
      title="<?php echo esc_attr( $tooltip_text ); ?>"></span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </section>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </article>
    </div>

    <!-- Modal de consulta (frontend) -->
    <div class="modal fade" id="fcsdActesDayModal" tabindex="-1" aria-labelledby="fcsdActesDayModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="fcsdActesDayModalLabel"><?php esc_html_e( 'Actes del dia', 'fcsd' ); ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo esc_attr__( 'Tancar', 'fcsd' ); ?>"></button>
                </div>
                <div class="modal-body">
                    <!-- Omplert via JS -->
                </div>
            </div>
        </div>
    </div>

<?php
endif;
get_footer();
