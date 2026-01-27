<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $fcsd_contact_errors, $fcsd_contact_success;
$fcsd_contact_errors  = array();
$fcsd_contact_success = false;

/**
 * Procesa el POST del formulario de contacto.
 */
function fcsd_handle_contact_form() {
    if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
        return;
    }

    if ( empty( $_POST['fcsd_contact_nonce'] ) || ! wp_verify_nonce( $_POST['fcsd_contact_nonce'], 'fcsd_contact' ) ) {
        return;
    }

    global $fcsd_contact_errors, $fcsd_contact_success;

    $fcsd_contact_errors  = array();
    $fcsd_contact_success = false;

    $name    = isset( $_POST['fcsd_contact_name'] ) ? trim( wp_unslash( $_POST['fcsd_contact_name'] ) ) : '';
    $contact = isset( $_POST['fcsd_contact_contact'] ) ? trim( wp_unslash( $_POST['fcsd_contact_contact'] ) ) : '';
    $message = isset( $_POST['fcsd_contact_message'] ) ? trim( wp_unslash( $_POST['fcsd_contact_message'] ) ) : '';

    if ( '' === $name || '' === $contact || '' === $message ) {
        $fcsd_contact_errors[] = __( 'Cal omplir tots els camps.', 'fcsd' );
        return;
    }

    if ( false !== strpos( $contact, '@' ) && ! is_email( $contact ) ) {
        $fcsd_contact_errors[] = __( "L'email no és vàlid.", 'fcsd' );
        return;
    }

    // Email configurable en el Customizer:
    $to = trim( get_theme_mod( 'fcsd_contact_email', '' ) );
    if ( '' === $to ) {
        $to = get_theme_mod( 'fcsd_footer_email', '' );
    }
    if ( ! is_email( $to ) ) {
        $to = get_option( 'admin_email' );
    }

    $subject = sprintf(
        __( 'Nou missatge de contacte de %s', 'fcsd' ),
        $name
    );

    $current_url = home_url( add_query_arg( array(), $_SERVER['REQUEST_URI'] ) );

    $body  = sprintf(
        __( "Nom: %s\nDada de contacte: %s\n\nMissatge:\n%s\n\n---\nEnviat des del formulari de contacte:\n%s", 'fcsd' ),
        $name,
        $contact,
        $message,
        $current_url
    );

    $headers = array();
    if ( is_email( $contact ) ) {
        $headers[] = 'Reply-To: ' . $contact;
    }

    $sent = wp_mail( $to, $subject, $body, $headers );

    if ( ! $sent ) {
        $fcsd_contact_errors[] = __( "S'ha produït un error en enviar el missatge. Torna-ho a intentar més tard.", 'fcsd' );
        return;
    }

    $fcsd_contact_success = true;
}
add_action( 'template_redirect', 'fcsd_handle_contact_form' );

/**
 * Render del formulario (Bootstrap).
 */
function fcsd_render_contact_form() {
    global $fcsd_contact_errors, $fcsd_contact_success;

    $errors  = is_array( $fcsd_contact_errors ) ? $fcsd_contact_errors : array();
    $success = ! empty( $fcsd_contact_success );

    $value_name    = isset( $_POST['fcsd_contact_name'] ) ? esc_attr( wp_unslash( $_POST['fcsd_contact_name'] ) ) : '';
    $value_contact = isset( $_POST['fcsd_contact_contact'] ) ? esc_attr( wp_unslash( $_POST['fcsd_contact_contact'] ) ) : '';
    $value_msg     = isset( $_POST['fcsd_contact_message'] ) ? esc_textarea( wp_unslash( $_POST['fcsd_contact_message'] ) ) : '';

    ob_start();

    if ( $success ) : ?>
        <div class="alert alert-success">
            <?php esc_html_e( 'Hem rebut el teu missatge. Et respondrem el més aviat possible.', 'fcsd' ); ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $errors ) ) : ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ( $errors as $e ) : ?>
                    <li><?php echo esc_html( $e ); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="mt-3" novalidate>
        <?php wp_nonce_field( 'fcsd_contact', 'fcsd_contact_nonce' ); ?>

        <div class="mb-3">
            <label for="fcsd_contact_name" class="form-label">
                <?php _e( 'Nom', 'fcsd' ); ?>
            </label>
            <input
                type="text"
                id="fcsd_contact_name"
                name="fcsd_contact_name"
                class="form-control"
                required
                value="<?php echo $value_name; ?>"
            >
        </div>

        <div class="mb-3">
            <label for="fcsd_contact_contact" class="form-label">
                <?php _e( 'Email o telèfon de contacte', 'fcsd' ); ?>
            </label>
            <input
                type="text"
                id="fcsd_contact_contact"
                name="fcsd_contact_contact"
                class="form-control"
                required
                value="<?php echo $value_contact; ?>"
            >
        </div>

        <div class="mb-3">
            <label for="fcsd_contact_message" class="form-label">
                <?php _e( 'Missatge', 'fcsd' ); ?>
            </label>
            <textarea
                id="fcsd_contact_message"
                name="fcsd_contact_message"
                class="form-control"
                rows="5"
                required
            ><?php echo $value_msg; ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">
            <?php _e( 'Enviar missatge', 'fcsd' ); ?>
        </button>
    </form>

    <?php
    return ob_get_clean();
}
add_shortcode( 'fcsd_contact_form', 'fcsd_render_contact_form' );
