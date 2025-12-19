<?php
/**
 * Template Name: Intranet (empleados)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

// 1) El usuario debe estar logueado.
if ( ! is_user_logged_in() ) {
    // Lo mandamos al login y, tras iniciar sesión, vuelve a esta página.
    wp_redirect( wp_login_url( get_permalink() ) );
    exit;
}

$user   = wp_get_current_user();
$roles  = (array) $user->roles;

// 2) Acceso a empleados.
//    - Por rol: worker / intranet_admin / administrator
//    - Por email: @fcsd.org (cubre usuarios legacy/importados sin rol)
$allowed_roles = array( 'worker', 'intranet_admin', 'administrator' );
$has_access    = array_intersect( $allowed_roles, $roles );

$email_domain_access = (bool) preg_match( '/@fcsd\.org$/i', (string) $user->user_email );

if ( empty( $has_access ) && ! $email_domain_access ) {
    ?>
    <div class="container py-5">
        <p><?php esc_html_e( 'No tienes acceso a la intranet.', 'fcsd' ); ?></p>
    </div>
    <?php
    get_footer();
    exit;
}
?>

<div class="container py-5">
    <h1 class="mb-4"><?php the_title(); ?></h1>
    <div class="mt-3 mb-5">
        <a href="/calendar-work"
     class="position-relative icon-link"
     aria-label="<?php esc_attr_e( 'Calendari laboral', 'fcsd' ); ?>">
    <?php echo esc_attr_e( 'Veure Calendari laboral', 'fcsd' ); ?></a>
    </div>

    <?php
    // 3) Listado de avisos de intranet (CPT intranet_notice).
    $q = new WP_Query( array(
        'post_type'      => 'intranet_notice',
        'posts_per_page' => 20,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );

    if ( $q->have_posts() ) :
        echo '<div class="intranet-notices">';
        while ( $q->have_posts() ) :
            $q->the_post();
            ?>
            <article class="intranet-notice mb-4">
                <h2 class="h4 mb-1"><?php the_title(); ?></h2>
                <p class="text-muted small mb-2">
                    <?php echo esc_html( get_the_date() ); ?>
                </p>
                <div class="intranet-notice__content">
                    <?php the_content(); ?>
                </div>
            </article>
            <?php
        endwhile;
        echo '</div>';
        wp_reset_postdata();
    else :
        echo '<p>' . esc_html__( 'De momento no hay anuncios internos.', 'fcsd' ) . '</p>';
    endif;
    ?>
</div>

<?php
get_footer();
