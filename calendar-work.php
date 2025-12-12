<?php
/**
 * Template Name: Calendari laboral d'actes
 *
 * Calendari intern d'actes laborals (només per a correus @fcsd.org), amb vista mensual / anual.
 * + Tabs per contracte (35h / 37h) filtrant per meta fcsd_acte_contract_type.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

if ( have_posts() ) :
    the_post();

    $user     = wp_get_current_user();
    $can_view = false;

    if ( $user && $user->exists() ) {
        $email = $user->user_email;
        if ( preg_match( '/@fcsd\.org$/i', $email ) ) {
            $can_view = true;
        }
    }
    ?>
    <main id="primary" class="site-main site-main--calendar">
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="page-header">
                <h1 class="page-title"><?php the_title(); ?></h1>
                <?php if ( has_excerpt() ) : ?>
                    <div class="page-intro">
                        <?php the_excerpt(); ?>
                    </div>
                <?php endif; ?>
            </header>

            <div class="page-content page-content--calendar-work">
                <?php
                if ( ! $can_view ) :
                    ?>
                    <div class="notice notice--warning">
                        <p>
                            <?php esc_html_e( 'Aquest calendari només és accessible per a persones treballadores de la Fundació (correu @fcsd.org).', 'fcsd' ); ?>
                        </p>
                    </div>
                    <?php
                else :

                    $current_time = current_time( 'timestamp' );

                    $view = ( isset( $_GET['act_view'] ) && 'annual' === $_GET['act_view'] ) ? 'annual' : 'monthly';

                    $year  = isset( $_GET['act_year'] ) ? (int) $_GET['act_year'] : (int) gmdate( 'Y', $current_time );
                    $month = isset( $_GET['act_month'] ) ? (int) $_GET['act_month'] : (int) gmdate( 'n', $current_time );

                    // Normalitzar any
                    $year_now = (int) gmdate( 'Y', $current_time );
                    if ( $year < 1970 || $year > $year_now + 10 ) {
                        $year = $year_now;
                    }

                    // Normalitzar mes
                    if ( $month < 1 || $month > 12 ) {
                        $month = (int) gmdate( 'n', $current_time );
                    }

                    // Scope laboral fix (aquí només mostrem actes laborals)
                    $scope = 'laboral';

                    $base_url = get_permalink();

                    // Contracte: 35h / 37h
                    $contract = isset( $_GET['act_contract'] ) ? sanitize_text_field( wp_unslash( $_GET['act_contract'] ) ) : '37h';
                    if ( ! in_array( $contract, array( '35h', '37h' ), true ) ) {
                        $contract = '37h';
                    }

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
                    ?>
                    <div class="actes-calendar actes-calendar--laboral">
                        <div class="actes-calendar__toolbar">
                            <div class="actes-calendar__view-switch">
                                <a href="<?php echo esc_url( add_query_arg( array(
                                    'act_view'     => 'monthly',
                                    'act_year'     => $year,
                                    'act_month'    => $month,
                                    'act_contract' => $contract,
                                ), $base_url ) ); ?>"
                                   class="button <?php echo ( 'monthly' === $view ) ? 'button-primary' : 'button-secondary'; ?>">
                                    <?php esc_html_e( 'Vista mensual', 'fcsd' ); ?>
                                </a>
                                <a href="<?php echo esc_url( add_query_arg( array(
                                    'act_view'     => 'annual',
                                    'act_year'     => $year,
                                    'act_contract' => $contract,
                                ), $base_url ) ); ?>"
                                   class="button <?php echo ( 'annual' === $view ) ? 'button-primary' : 'button-secondary'; ?>">
                                    <?php esc_html_e( 'Vista anual', 'fcsd' ); ?>
                                </a>
                            </div>

                            <div class="actes-calendar__contract-switch">
                                <a href="<?php echo esc_url( add_query_arg( array(
                                    'act_view'     => $view,
                                    'act_year'     => $year,
                                    'act_month'    => $month,
                                    'act_contract' => '37h',
                                ), $base_url ) ); ?>"
                                   class="button <?php echo ( '37h' === $contract ) ? 'button-primary' : 'button-secondary'; ?>">
                                    <?php esc_html_e( 'Contracte 37h', 'fcsd' ); ?>
                                </a>

                                <a href="<?php echo esc_url( add_query_arg( array(
                                    'act_view'     => $view,
                                    'act_year'     => $year,
                                    'act_month'    => $month,
                                    'act_contract' => '35h',
                                ), $base_url ) ); ?>"
                                   class="button <?php echo ( '35h' === $contract ) ? 'button-primary' : 'button-secondary'; ?>">
                                    <?php esc_html_e( 'Contracte 35h', 'fcsd' ); ?>
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
                                           'act_view'     => 'monthly',
                                           'act_year'     => $prev_year,
                                           'act_month'    => $prev_month,
                                           'act_contract' => $contract,
                                       ), $base_url ) ); ?>">
                                        &laquo;
                                    </a>
                                    <span class="actes-calendar__current">
                                        <?php echo esc_html( $month_names[ $month ] . ' ' . $year ); ?>
                                    </span>
                                    <a class="button button--ghost"
                                       href="<?php echo esc_url( add_query_arg( array(
                                           'act_view'     => 'monthly',
                                           'act_year'     => $next_year,
                                           'act_month'    => $next_month,
                                           'act_contract' => $contract,
                                       ), $base_url ) ); ?>">
                                        &raquo;
                                    </a>
                                <?php else : ?>
                                    <a class="button button--ghost"
                                       href="<?php echo esc_url( add_query_arg( array(
                                           'act_view'     => 'annual',
                                           'act_year'     => $year - 1,
                                           'act_contract' => $contract,
                                       ), $base_url ) ); ?>">
                                        &laquo;
                                    </a>
                                    <span class="actes-calendar__current">
                                        <?php echo esc_html( $year ); ?>
                                    </span>
                                    <a class="button button--ghost"
                                       href="<?php echo esc_url( add_query_arg( array(
                                           'act_view'     => 'annual',
                                           'act_year'     => $year + 1,
                                           'act_contract' => $contract,
                                       ), $base_url ) ); ?>">
                                        &raquo;
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ( 'monthly' === $view ) : ?>

                            <?php
                            $month_start    = gmmktime( 0, 0, 0, $month, 1, $year );
                            $days_in_month  = (int) gmdate( 't', $month_start );
                            $month_end      = gmmktime( 23, 59, 59, $month, $days_in_month, $year );
                            $first_weekday  = (int) gmdate( 'N', $month_start ); // 1 (dl) - 7 (dg)

                            // FILTRADO POR CONTRATO (35h/37h)
                            $items      = fcsd_actes_get_in_range( $month_start, $month_end, $scope, $contract );
                            $items_days = fcsd_actes_group_by_day( $items, $month_start, $month_end );
                            ?>

                            <div class="actes-calendar__grid actes-calendar__grid--monthly">
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

                                        // Dia amb algun festiu oficial al calendari laboral?
                                        $has_official = false;
                                        if ( ! empty( $day_items ) ) {
                                            foreach ( $day_items as $item ) {
                                                if ( ! empty( $item['is_official_holiday'] ) ) {
                                                    $has_official = true;
                                                    break;
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
                                        if ( $has_official ) {
                                            $classes[] = 'actes-calendar__day--official-holiday';
                                        }
                                        ?>
                                        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
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
                                                        ?>
                                                        <li class="actes-calendar__event">
                                                            <a href="<?php echo esc_url( $item['permalink'] ); ?>" class="actes-calendar__event-link">
                                                                <?php if ( $time_label ) : ?>
                                                                    <span class="actes-calendar__event-time">
                                                                        <?php echo esc_html( $time_label ); ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                                <span class="actes-calendar__event-title">
                                                                    <?php echo esc_html( $item['title'] ); ?>
                                                                </span>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                    }

                                    // celdas vacías al final
                                    $total_cells = $first_weekday - 1 + $days_in_month;
                                    $remaining   = $total_cells % 7;
                                    if ( $remaining > 0 ) {
                                        for ( $i = 0; $i < 7 - $remaining; $i++ ) {
                                            echo '<div class="actes-calendar__day actes-calendar__day--empty"></div>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>

                        <?php else : ?>

                            <div class="actes-calendar__annual">
                                <?php for ( $m = 1; $m <= 12; $m++ ) : ?>
                                    <?php
                                    $month_start   = gmmktime( 0, 0, 0, $m, 1, $year );
                                    $days_in_month = (int) gmdate( 't', $month_start );
                                    $month_end     = gmmktime( 23, 59, 59, $m, $days_in_month, $year );
                                    $first_weekday = (int) gmdate( 'N', $month_start );

                                    // FILTRADO POR CONTRATO (35h/37h)
                                    $items      = fcsd_actes_get_in_range( $month_start, $month_end, $scope, $contract );
                                    $items_days = fcsd_actes_group_by_day( $items, $month_start, $month_end );
                                    ?>
                                    <section class="actes-calendar__month-card">
                                        <header class="actes-calendar__month-header">
                                            <h2 class="actes-calendar__month-title">
                                                <?php echo esc_html( $month_names[ $m ] ); ?>
                                            </h2>
                                        </header>
                                        <div class="actes-calendar__grid actes-calendar__grid--compact">
                                            <div class="actes-calendar__weekdays">
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

                                                    // Dia amb algun festiu oficial al calendari laboral?
                                                    $has_official = false;
                                                    if ( ! empty( $day_items ) ) {
                                                        foreach ( $day_items as $item ) {
                                                            if ( ! empty( $item['is_official_holiday'] ) ) {
                                                                $has_official = true;
                                                                break;
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
                                                    if ( $has_official ) {
                                                        $classes[] = 'actes-calendar__day--official-holiday';
                                                    }
                                                    ?>
                                                    <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
                                                        <div class="actes-calendar__day-number">
                                                            <?php echo (int) $day; ?>
                                                        </div>
                                                        <?php if ( ! empty( $day_items ) ) : ?>
                                                            <div class="actes-calendar__dots">
                                                                <?php foreach ( $day_items as $item ) : ?>
                                                                    <span class="actes-calendar__dot"
                                                                          tabindex="0"
                                                                          aria-label="<?php echo esc_attr( $item['title'] ); ?>"
                                                                          title="<?php echo esc_attr( $item['title'] ); ?>"></span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php
                                                }

                                                $total_cells = $first_weekday - 1 + $days_in_month;
                                                $remaining   = $total_cells % 7;
                                                if ( $remaining > 0 ) {
                                                    for ( $i = 0; $i < 7 - $remaining; $i++ ) {
                                                        echo '<div class="actes-calendar__day actes-calendar__day--empty"></div>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </section>
                                <?php endfor; ?>
                            </div>

                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    </main>
    <?php
endif;

get_footer();
