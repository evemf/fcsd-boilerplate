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

// If already logged in, redirect to profile.
if ( is_user_logged_in() ) {
    wp_safe_redirect( fcsd_get_system_page_url( 'profile' ) );
    exit;
}

$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

// Render password reset UI when:
// - user clicked the theme link (?action=reset)
// - user arrives from email with token (key/login)
// - after successful reset (?reset=done)
$is_reset_view = (
    in_array( $action, array( 'reset', 'lostpassword', 'rp', 'resetpass' ), true )
    || ( isset( $_GET['key'], $_GET['login'] ) )
    || ( isset( $_GET['reset'] ) && 'done' === (string) $_GET['reset'] )
);

?>

<main id="primary" class="site-main container content py-5 login-page">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card p-4 shadow-sm">
                <?php echo do_shortcode( $is_reset_view ? '[fcsd_password_reset]' : '[fcsd_login_form]' ); ?>

                <hr class="my-4">

                <p class="mb-0 text-center">
                    <?php
                    printf(
                        '%s <a href="%s">%s</a>',
                        esc_html__( 'No tens compte?', 'fcsd' ),
                        esc_url( fcsd_get_system_page_url( 'register' ) ),
                        esc_html__( "Registra't aquí", 'fcsd' )
                    );
                    ?>
                </p>
            </div>
        </div>
    </div>
</main>

<?php
get_footer();
