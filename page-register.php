<?php
/**
 * Template Name: Registre
 *
 * System page. Created/assigned automatically on theme activation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

// Si ya está logueado, lo mandamos al perfil.
if ( is_user_logged_in() ) {
    wp_safe_redirect( fcsd_get_system_page_url( 'profile' ) );
    exit;
}
?>

<main id="primary" class="site-main container content py-5 register-page">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card p-4 shadow-sm">
                <?php echo do_shortcode( '[fcsd_register_form]' ); ?>

                <hr class="my-4">

                <p class="mb-0 text-center">
                    <?php
                    printf(
                        '%s <a href="%s">%s</a>',
                        esc_html__( 'Ja tens compte?', 'fcsd' ),
                        esc_url( fcsd_get_system_page_url( 'login' ) ),
                        esc_html__( 'Inicia sessió', 'fcsd' )
                    );
                    ?>
                </p>
            </div>
        </div>
    </div>
</main>

<?php get_footer();
