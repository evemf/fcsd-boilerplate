<?php
/**
 * Template Name: Calendari laboral d'actes
 *
 * Calendari intern d'actes laborals (només per a correus @fcsd.org), amb vista mensual / anual.
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

// Normalitzar any
$year_now = (int) gmdate( 'Y', $current_time );
if ( $year < 1970 || $year > $year_now + 10 ) {
    $year = $year_now;
}

// Normalitzar mes
if ( $month < 1 || $month > 12 ) {
    $month = (int) gmdate( 'n', $current_time );
}

// Scope laboral
$scope = 'laboral';

// URL base neta
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

// Restricció d'accés a correus @fcsd.org
$can_view = false;

if ( is_user_logged_in() ) {
    $current_user = wp_get_current_user();
    $email        = $current_user && ! empty( $current_user->user_email ) ? $current_user->user_email : '';
    if ( $email && substr( $email, -9 ) === '@fcsd.org' ) {
        $can_view = true;
    }
}
?>
<div class="container content py-5">
    <article id="post-<?php the_ID(); ?>" <?php post_class( 'actes-page actes-page--work' ); ?>>
        <header class="page-header mb-4">
            <h1 class="mb-2"><?php the_title(); ?></h1>
        </header>

        <?php if ( ! $can_view ) : ?>
            <div class="alert alert-warning">
                <p>
                    <?php esc_html_e( 'Aquesta secció és només per a treballadores i treballadors de la Fundació amb correu @fcsd.org.', 'fcsd' ); ?>
                </p>
            </div>
        <?php else : ?>

            <div class="actes-calendar layout-panel actes-calendar--work">
                <div class="actes-calendar__toolbar">
                    <div class="actes-calendar__view-toggle">
                        <a href="<?php echo esc_url( add_query_arg( array(
                            'act_view'  => 'monthly',
                            'act_year'  => $year,
                            'act_month' => $month,
                        ), $base_url ) ); ?>"
                           class="button <?php echo ( 'monthly' === $view ) ? 'button-primary' : 'button-secondary'; ?>">
                            <?php esc_html_e( 'Vista mensual', 'fcsd' ); ?>
                        </a>
                        <a href="<?php echo esc_url( add_query_arg( array(
                            'act_view' => 'annual',
                            'act_year' => $year,
                        ), $base_url ) ); ?>"
                           class="button <?php echo ( 'annual' === $view ) ? 'button-primary' : 'button-secondary'; ?>">
                            <?php esc_html_e( 'Vista anual', 'fcsd' ); ?>
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

                                    $classes = array( 'actes-calendar__day' );
                                    if ( $day_key === $today_key ) {
                                        $classes[] = 'actes-calendar__day--today';
                                    }
                                    if ( ! empty( $day_items ) ) {
                                        $classes[] = 'actes-calendar__day--has-events';
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
                                                            <div class="actes-calendar__event-header">
                                                                <?php if ( $time_label ) : ?>
                                                                    <span class="actes-calendar__event-time">
                                                                        <?php echo esc_html( $time_label ); ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                                <span class="actes-calendar__event-title">
                                                                    <?php echo esc_html( $item['title'] ); ?>
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

                                                $classes = array( 'actes-calendar__day', 'actes-calendar__day--compact' );
                                                if ( $day_key === $today_key ) {
                                                    $classes[] = 'actes-calendar__day--today';
                                                }
                                                if ( ! empty( $day_items ) ) {
                                                    $classes[] = 'actes-calendar__day--has-events';
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
                                                                      data-title="<?php echo esc_attr( $item['title'] ); ?>"></span>
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

        <?php endif; ?>
    </article>
</div>

<?php
endif;
get_footer();
