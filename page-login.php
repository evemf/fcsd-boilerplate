<?php
/**
 * Template Name: Accedir (Login)
 *
 * System page. Created/assigned automatically on theme activation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

// Si ya está logueado, lo mandamos al perfil.
if ( is_user_logged_in() ) {
    wp_safe_redirect( fcsd_get_page_url_by_slug( 'perfil-usuari' ) );
    exit;
}
?>

<main id="primary" class="site-main container content py-5 login-page">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card p-4 shadow-sm">
                <?php echo do_shortcode( '[fcsd_login_form]' ); ?>

                <hr class="my-4">

                <p class="mb-0 text-center">
                    <?php
                    printf(
                        '%s <a href="%s">%s</a>',
                        esc_html__( 'No tens compte?', 'fcsd' ),
                        esc_url( fcsd_get_page_url_by_slug( 'registre' ) ),
                        esc_html__( "Registra't aquí", 'fcsd' )
                    );
                    ?>
                </p>
            </div>
        </div>
    </div>
</main>

<?php get_footer();
