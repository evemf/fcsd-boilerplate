<?php
/**
 * Template Name: Calendari laboral d'actes
 *
 * Mostra només actes laborals (scope = laboral) i només per a usuaris
 * registrats amb email @fcsd.org.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

if ( have_posts() ) :
    the_post();
endif;

// Comprovem usuari i domini
$can_view = false;

if ( is_user_logged_in() ) {
    $current_user = wp_get_current_user();
    $email        = $current_user && ! empty( $current_user->user_email ) ? $current_user->user_email : '';
    if ( $email && substr( $email, -9 ) === '@fcsd.org' ) {
        $can_view = true;
    }
}

$current_time = current_time( 'timestamp' );
$view         = ( isset( $_GET['view'] ) && 'annual' === $_GET['view'] ) ? 'annual' : 'monthly';
$year         = isset( $_GET['year'] ) ? (int) $_GET['year'] : (int) gmdate( 'Y', $current_time );
$month        = isset( $_GET['month'] ) ? (int) $_GET['month'] : (int) gmdate( 'n', $current_time );
if ( $month < 1 || $month > 12 ) {
    $month = (int) gmdate( 'n', $current_time );
}

$scope    = 'laboral';
$base_url = get_permalink();

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
<div class="container content py-5">
    <article id="post-<?php the_ID(); ?>" <?php post_class( 'actes-page actes-page--laboral' ); ?>>
        <header class="page-header mb-4">
            <h1 class="mb-2"><?php the_title(); ?></h1>
            <p class="text-muted">
                <?php esc_html_e( 'Calendari laboral intern per a persones treballadores de la Fundació.', 'fcsd' ); ?>
            </p>
        </header>

        <?php if ( ! $can_view ) : ?>
            <div class="alert alert-warning">
                <p>
                    <?php esc_html_e( 'Aquesta secció és només per a persones treballadores de la Fundació amb correu @fcsd.org.', 'fcsd' ); ?>
                </p>
            </div>
        <?php else : ?>

            <div class="actes-calendar layout-panel actes-calendar--work">
                <div class="actes-calendar__toolbar">
                    <div class="actes-calendar__view-toggle">
                        <a href="<?php echo esc_url( add_query_arg( array(
                            'view'  => 'monthly',
                            'year'  => $year,
                            'month' => $month,
                        ), $base_url ) ); ?>"
                           class="button button--ghost <?php echo ( 'monthly' === $view ) ? 'is-active' : ''; ?>">
                            <?php esc_html_e( 'Vista mensual', 'fcsd' ); ?>
                        </a>
                        <a href="<?php echo esc_url( add_query_arg( array(
                            'view'  => 'annual',
                            'year'  => $year,
                        ), $base_url ) ); ?>"
                           class="button button--ghost <?php echo ( 'annual' === $view ) ? 'is-active' : ''; ?>">
                            <?php esc_html_e( 'Vista anual', 'fcsd' ); ?>
                        </a>
                    </div>

                    <?php if ( 'monthly' === $view ) : ?>
                        <div class="actes-calendar__month-nav">
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
                                   'view'  => 'monthly',
                                   'year'  => $prev_year,
                                   'month' => $prev_month,
                               ), $base_url ) ); ?>">
                                &laquo;
                            </a>

                            <span class="actes-calendar__current">
                                <?php echo esc_html( $month_names[ $month ] . ' ' . $year ); ?>
                            </span>

                            <a class="button button--ghost"
                               href="<?php echo esc_url( add_query_arg( array(
                                   'view'  => 'monthly',
                                   'year'  => $next_year,
                                   'month' => $next_month,
                               ), $base_url ) ); ?>">
                                &raquo;
                            </a>
                        </div>
                    <?php else : ?>
                        <div class="actes-calendar__year-nav">
                            <span class="actes-calendar__current">
                                <?php echo esc_html( $year ); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="actes-calendar__body">
                    <?php
                    if ( 'monthly' === $view ) :
                        $month_start   = gmmktime( 0, 0, 0, $month, 1, $year );
                        $days_in_month = (int) gmdate( 't', $month_start );
                        $month_end     = gmmktime( 23, 59, 59, $month, $days_in_month, $year );
                        $first_weekday = (int) gmdate( 'N', $month_start );

                        $items      = function_exists( 'fcsd_actes_get_in_range' ) ? fcsd_actes_get_in_range( $month_start, $month_end, $scope ) : array();
                        $items_days = function_exists( 'fcsd_actes_group_by_day' ) ? fcsd_actes_group_by_day( $items, $month_start, $month_end ) : array();

                        $today_key = gmdate( 'Y-m-d', $current_time );
                        ?>
                        <div class="actes-calendar__grid">
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
                                    $current_ts = gmmktime( 0, 0, 0, $month, $day, $year );
                                    $day_key    = gmdate( 'Y-m-d', $current_ts );
                                    $day_items  = isset( $items_days[ $day_key ] ) ? $items_days[ $day_key ] : array();
                                    $is_today   = ( $day_key === $today_key );

                                    $classes = array( 'actes-calendar__day' );
                                    if ( $is_today ) {
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
                                            <div class="actes-calendar__events">
                                                <?php foreach ( $day_items as $item ) : ?>
                                                    <article class="actes-calendar__event">
                                                        <a href="<?php echo esc_url( $item['permalink'] ); ?>" class="actes-calendar__event-main">
                                                            <?php if ( ! empty( $item['thumb'] ) ) : ?>
                                                                <span class="actes-calendar__event-thumb"
                                                                      style="background-image:url('<?php echo esc_url( $item['thumb'] ); ?>');"></span>
                                                            <?php endif; ?>
                                                            <span class="actes-calendar__event-title">
                                                                <?php echo esc_html( $item['title'] ); ?>
                                                            </span>
                                                        </a>
                                                        <?php if ( ! empty( $item['excerpt'] ) ) : ?>
                                                            <p class="actes-calendar__event-excerpt">
                                                                <?php echo esc_html( wp_trim_words( $item['excerpt'], 12 ) ); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </article>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                    <?php else : // Vista anual ?>
                        <div class="actes-calendar__annual">
                            <?php
                            for ( $m = 1; $m <= 12; $m++ ) :
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

                                                $classes = array( 'actes-calendar__day' );
                                                if ( ! empty( $day_items ) ) {
                                                    $classes[] = 'actes-calendar__day--has-events';
                                                }
                                                ?>
                                                <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
                                                    <div class="actes-calendar__day-number">
                                                        <?php echo (int) $day; ?>
                                                    </div>
                                                    <?php if ( ! empty( $day_items ) ) : ?>
                                                        <div class="actes-calendar__events">
                                                            <?php foreach ( $day_items as $item ) : ?>
                                                                <div class="actes-calendar__event actes-calendar__event--dot">
                                                                    <a href="<?php echo esc_url( $item['permalink'] ); ?>"
                                                                       class="actes-calendar__event-dot"
                                                                       style="background-color:<?php echo esc_attr( $item['color'] ); ?>;"
                                                                       title="<?php echo esc_attr( $item['title'] ); ?>">
                                                                        •
                                                                    </a>
                                                                </div>
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
get_footer();
