<?php
/**
 * User registration, login, profile and intranet helpers (FCSD Theme).
 *
 * Professional pattern:
 *  - Sensitive logic (login/register/set-pass/redirects) runs in template_redirect
 *    BEFORE any HTML is sent.
 *  - Shortcodes/templates only render views.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// -----------------------------------------------------------------------------
// Global auth state (ONLY for rendering messages in shortcodes/templates)
// -----------------------------------------------------------------------------
global $fcsd_auth_errors, $fcsd_auth_success_reg, $fcsd_setpass_errors;
$fcsd_auth_errors      = array();
$fcsd_auth_success_reg = false;
$fcsd_setpass_errors   = array();

// Password reset (lost password) state (for rendering messages)
global $fcsd_reset_errors, $fcsd_reset_success, $fcsd_reset_done;
$fcsd_reset_errors  = array();
$fcsd_reset_success = false;
$fcsd_reset_done    = false;


// -----------------------------------------------------------------------------
// EARLY FORM HANDLERS (pattern WP correct)
// -----------------------------------------------------------------------------
function fcsd_handle_auth_forms() {

    if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
        return;
    }

    global $fcsd_auth_errors, $fcsd_auth_success_reg, $fcsd_setpass_errors;
    global $fcsd_reset_errors, $fcsd_reset_success, $fcsd_reset_done;
    $fcsd_auth_errors      = array();
    $fcsd_auth_success_reg = false;
    $fcsd_setpass_errors   = array();
    $fcsd_reset_errors     = array();
    $fcsd_reset_success    = false;
    $fcsd_reset_done       = false;

    // -----------------------------------------------------------------
    // REGISTRATION
    // -----------------------------------------------------------------
    if (
        ! empty( $_POST['fcsd_register_nonce'] ) &&
        wp_verify_nonce( $_POST['fcsd_register_nonce'], 'fcsd_register' )
    ) {

        $first_name = isset( $_POST['fcsd_first_name'] ) ? sanitize_text_field( $_POST['fcsd_first_name'] ) : '';
        $last_name  = isset( $_POST['fcsd_last_name'] ) ? sanitize_text_field( $_POST['fcsd_last_name'] ) : '';
        $email      = isset( $_POST['fcsd_email'] ) ? sanitize_email( $_POST['fcsd_email'] ) : '';

        if ( empty( $first_name ) || empty( $last_name ) || empty( $email ) ) {
            $fcsd_auth_errors[] = __( 'Cal omplir tots els camps de registre.', 'fcsd' );
            return;
        }

        if ( ! is_email( $email ) ) {
            $fcsd_auth_errors[] = __( "L'email no és vàlid.", 'fcsd' );
            return;
        }

        if ( email_exists( $email ) ) {
            $fcsd_auth_errors[] = __( 'Ja existeix un usuari amb aquest email.', 'fcsd' );
            return;
        }

        $user_id = wp_insert_user(
            array(
                'user_login' => $email,
                'user_email' => $email,
                'first_name' => $first_name,
                'last_name'  => $last_name,
                'user_pass'  => wp_generate_password( 32 ),
                'role'       => 'subscriber',
            )
        );

        if ( is_wp_error( $user_id ) ) {
            $fcsd_auth_errors[] = __( "S'ha produït un error en crear l'usuari.", 'fcsd' );
            return;
        }

        // If email is from FCSD domain, assign worker role.
        if ( preg_match( '/@fcsd\.org$/i', $email ) ) {
            $u = new WP_User( $user_id );
            $u->set_role( 'worker' );
        }

        // Ensure confirmation page exists.
        if ( ! get_page_by_path( 'confirmar-registre' ) ) {
            wp_insert_post(
                array(
                    'post_title'   => 'Confirmar registre',
                    'post_name'    => 'confirmar-registre',
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_content' => '[fcsd_confirm_registration]',
                )
            );
        }

        fcsd_send_verification_email( $user_id );
        $fcsd_auth_success_reg = true;

        return;
    }

    // -----------------------------------------------------------------
    // LOGIN
    // -----------------------------------------------------------------
    if (
        ! empty( $_POST['fcsd_login_nonce'] ) &&
        wp_verify_nonce( $_POST['fcsd_login_nonce'], 'fcsd_login' )
    ) {

        $email    = isset( $_POST['fcsd_login_email'] ) ? sanitize_email( $_POST['fcsd_login_email'] ) : '';
        $password = isset( $_POST['fcsd_login_password'] ) ? $_POST['fcsd_login_password'] : '';

        $creds = array(
            'user_login'    => $email,
            'user_password' => $password,
            'remember'      => true,
        );

        $user = wp_signon( $creds, false );
        if ( is_wp_error( $user ) ) {
            $fcsd_auth_errors[] = __( 'Credencials incorrectes.', 'fcsd' );
            return;
        }

        $verified_values = get_user_meta( $user->ID, 'fcsd_email_verified', false ); // array
        $verified = false;

        foreach ( $verified_values as $v ) {
            if ( (string) $v === '1' || $v === 1 || $v === true ) {
                $verified = true;
                break;
            }
        }

        if ( ! $verified ) {
            wp_logout();
            $fcsd_auth_errors[] = __( "Has de verificar el teu email abans d'iniciar sessió.", 'fcsd' );
            return;
        }

        // Legacy users (manually created/imported) might not have the worker role.
        // If they use an @fcsd.org email, we grant it on login.
        fcsd_maybe_add_worker_role( $user );

        $default_redirect = fcsd_get_page_url_by_slug( 'perfil-usuari' );
        $redirect_to      = '';

        if ( isset( $_POST['redirect_to'] ) ) {
            $redirect_to = esc_url_raw( wp_unslash( $_POST['redirect_to'] ) );
        } elseif ( isset( $_GET['redirect_to'] ) ) {
            // Fallback if someone posts without the hidden field.
            $redirect_to = esc_url_raw( wp_unslash( $_GET['redirect_to'] ) );
        }

        $final_redirect = $redirect_to ? wp_validate_redirect( $redirect_to, $default_redirect ) : $default_redirect;
        wp_safe_redirect( $final_redirect );
        exit;
    }

    // -----------------------------------------------------------------

    // -----------------------------------------------------------------
    // PASSWORD RESET (REQUEST EMAIL)
    // -----------------------------------------------------------------
    if (
        ! empty( $_POST['fcsd_reset_request_nonce'] ) &&
        wp_verify_nonce( $_POST['fcsd_reset_request_nonce'], 'fcsd_reset_request' )
    ) {

        $email = isset( $_POST['fcsd_reset_email'] ) ? sanitize_email( wp_unslash( $_POST['fcsd_reset_email'] ) ) : '';

        if ( empty( $email ) || ! is_email( $email ) ) {
            $fcsd_reset_errors[] = __( 'Introdueix un email vàlid.', 'fcsd' );
            return;
        }

        $user = get_user_by( 'email', $email );

        // Evitem enumeració d'usuaris: sempre mostrem èxit encara que no existeixi.
        if ( ! $user ) {
            $fcsd_reset_success = true;
            return;
        }

        $key = get_password_reset_key( $user );
        if ( is_wp_error( $key ) ) {
            $fcsd_reset_errors[] = __( "No s'ha pogut generar l'enllaç de recuperació.", 'fcsd' );
            return;
        }

        $url = add_query_arg(
            array(
                'action' => 'reset',
                'login'  => rawurlencode( $user->user_login ),
                'key'    => rawurlencode( $key ),
            ),
            fcsd_get_system_page_url( 'login' )
        );

        $subject = __( 'Recuperació de contrasenya', 'fcsd' );
        $message = sprintf(
            __( "Has sol·licitat restablir la teva contrasenya.\n\nFes clic aquí per crear-ne una de nova:\n\n%s\n\nSi no has fet aquesta sol·licitud, ignora aquest missatge.", 'fcsd' ),
            $url
        );

        wp_mail( $user->user_email, $subject, $message );
        $fcsd_reset_success = true;
        return;
    }


    // -----------------------------------------------------------------
    // PASSWORD RESET (SET NEW PASSWORD)
    // -----------------------------------------------------------------
    if (
        ! empty( $_POST['fcsd_reset_password_nonce'] ) &&
        wp_verify_nonce( $_POST['fcsd_reset_password_nonce'], 'fcsd_reset_password' )
    ) {

        $login = isset( $_GET['login'] ) ? sanitize_text_field( wp_unslash( $_GET['login'] ) ) : '';
        $key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';

        if ( empty( $login ) || empty( $key ) ) {
            $fcsd_reset_errors[] = __( 'Enllaç invàlid.', 'fcsd' );
            return;
        }

        $user = check_password_reset_key( $key, $login );
        if ( is_wp_error( $user ) || ! ( $user instanceof \WP_User ) ) {
            $fcsd_reset_errors[] = __( "L'enllaç de recuperació no és vàlid o ha caducat.", 'fcsd' );
            return;
        }

        $pass1 = isset( $_POST['fcsd_pass1'] ) ? $_POST['fcsd_pass1'] : '';
        $pass2 = isset( $_POST['fcsd_pass2'] ) ? $_POST['fcsd_pass2'] : '';

        if ( empty( $pass1 ) || empty( $pass2 ) ) {
            $fcsd_reset_errors[] = __( "Has d'omplir tots els camps.", 'fcsd' );
            return;
        }

        if ( $pass1 !== $pass2 ) {
            $fcsd_reset_errors[] = __( 'Les contrasenyes no coincideixen.', 'fcsd' );
            return;
        }

        if ( ! fcsd_is_valid_password( $pass1 ) ) {
            $fcsd_reset_errors[] = __( 'La contrasenya ha de tenir mínim 6 caràcters, una lletra i un número.', 'fcsd' );
            return;
        }

        reset_password( $user, $pass1 );

        $fcsd_reset_done = true;
        wp_safe_redirect( add_query_arg( array( 'reset' => 'done' ), fcsd_get_system_page_url( 'login' ) ) );
        exit;
    }

    // SET PASSWORD / CONFIRM EMAIL (from email link)
    // -----------------------------------------------------------------
    if (
        ! empty( $_POST['fcsd_setpass_nonce'] ) &&
        wp_verify_nonce( $_POST['fcsd_setpass_nonce'], 'fcsd_setpass' )
    ) {

        $uid = isset( $_GET['uid'] ) ? absint( $_GET['uid'] ) : 0;
        $key = isset( $_GET['key'] ) ? sanitize_text_field( $_GET['key'] ) : '';

        if ( ! $uid || ! $key ) {
            $fcsd_setpass_errors[] = __( 'Enllaç invàlid.', 'fcsd' );
            return;
        }

        $stored_key = get_user_meta( $uid, 'fcsd_email_verification_key', true );
        if ( ! $stored_key || $stored_key !== $key ) {
            $fcsd_setpass_errors[] = __( "L'enllaç de verificació no és correcte o ja s'ha utilitzat.", 'fcsd' );
            return;
        }

        $pass1 = isset( $_POST['fcsd_pass1'] ) ? $_POST['fcsd_pass1'] : '';
        $pass2 = isset( $_POST['fcsd_pass2'] ) ? $_POST['fcsd_pass2'] : '';

        if ( empty( $pass1 ) || empty( $pass2 ) ) {
            $fcsd_setpass_errors[] = __( "Has d'omplir tots els camps.", 'fcsd' );
            return;
        }

        if ( $pass1 !== $pass2 ) {
            $fcsd_setpass_errors[] = __( 'Les contrasenyes no coincideixen.', 'fcsd' );
            return;
        }

        if ( ! fcsd_is_valid_password( $pass1 ) ) {
            $fcsd_setpass_errors[] = __( 'La contrasenya ha de tenir mínim 6 caràcters, una lletra i un número.', 'fcsd' );
            return;
        }

        wp_update_user(
            array(
                'ID'        => $uid,
                'user_pass' => $pass1,
            )
        );
        delete_user_meta( $uid, 'fcsd_email_verified' );
        add_user_meta( $uid, 'fcsd_email_verified', 1, true );

        delete_user_meta( $uid, 'fcsd_email_verification_key' );

        // Auto-login after setting password.
        $user = get_userdata( $uid );
        if ( $user ) {
            wp_set_current_user( $uid, $user->user_login );
            wp_set_auth_cookie( $uid );
        }

        wp_safe_redirect( fcsd_get_page_url_by_slug( 'perfil-usuari' ) );
        exit;
    }
}
add_action( 'template_redirect', 'fcsd_handle_auth_forms' );


// -----------------------------------------------------------------------------
// Roles
// -----------------------------------------------------------------------------
add_action('init', function () {

    // Crear / asegurar rol worker sin caps "false" que puedan pisar permisos
    if ( ! get_role('worker') ) {
        add_role(
            'worker',
            'FCSD Worker',
            array(
                'read' => true,
            )
        );
    } else {
        // Si ya existía de antes con caps mal, lo reparamos
        $role = get_role('worker');
        if ( $role ) {
            $role->add_cap('read');
            $role->remove_cap('edit_posts');
            $role->remove_cap('delete_posts');
        }
    }

}, 1);

/**
 * Ensure users from FCSD domain have the "worker" role.
 *
 * Note: we use add_role (NOT set_role) to avoid overriding existing roles
 * (e.g. administrator, intranet_admin, etc.).
 */
function fcsd_maybe_add_worker_role( $user ) {
    if ( ! $user instanceof WP_User ) {
        return;
    }

    $email = (string) $user->user_email;
    if ( ! $email || ! preg_match( '/@fcsd\.org$/i', $email ) ) {
        return;
    }

    if ( ! in_array( 'worker', (array) $user->roles, true ) ) {
        $user->add_role( 'worker' );
    }
}

// Also enforce role on every login (covers legacy/imported users).
add_action(
    'wp_login',
    function ( $user_login, $user ) {
        fcsd_maybe_add_worker_role( $user );
    },
    10,
    2
);


// -----------------------------------------------------------------------------
// Helpers
// -----------------------------------------------------------------------------

/**
 * Password validation: at least 6 chars, one letter and one digit.
 */
function fcsd_is_valid_password( $password ) {
    return (bool) preg_match( '/^(?=.*[A-Za-z])(?=.*\d).{6,}$/', $password );
}

/**
 * Helper: get page URL by slug.
 */
function fcsd_get_page_url_by_slug( $slug ) {
    $page = get_page_by_path( $slug );
    if ( $page ) {
        return get_permalink( $page );
    }
    return home_url( '/' );
}


/**
 * URL pública de una página de sistema (login/register/profile) según idioma.
 * No depende del slug real en la BD; el router la resuelve por template.
 */
function fcsd_get_system_page_url( string $key ): string {
    if ( ! function_exists('fcsd_slug') ) {
        return home_url('/');
    }
    $lang = function_exists('fcsd_lang')
        ? fcsd_lang()
        : ( defined('FCSD_LANG') ? FCSD_LANG : ( defined('FCSD_DEFAULT_LANG') ? FCSD_DEFAULT_LANG : 'ca' ) );
    $slug = fcsd_slug($key, $lang);
    $prefix = (defined('FCSD_DEFAULT_LANG') && $lang === FCSD_DEFAULT_LANG) ? '' : ($lang . '/');
    return home_url('/' . $prefix . trim($slug,'/') . '/');
}

if ( ! function_exists( 'fcsd_get_option' ) ) {
    /**
     * Helper para obtener opciones del tema.
     * 1) Mira primero en theme_mod (Customizer)
     * 2) Si no hay nada, mira en options
     */
    function fcsd_get_option( $key, $default = '' ) {
        // 1. Probar como theme_mod
        $mod = get_theme_mod( $key, null );
        if ( null !== $mod && $mod !== '' ) {
            return $mod;
        }

        // 2. Fallback a opción normal
        return get_option( $key, $default );
    }
}



/**
 * Send verification email with link to confirm page.
 */
function fcsd_send_verification_email( $user_id ) {

    $user = get_userdata( $user_id );
    if ( ! $user ) {
        return;
    }

    $key = wp_generate_password( 20, false );
    // Limpieza preventiva de duplicados
    delete_user_meta( $user_id, 'fcsd_email_verification_key' );
    delete_user_meta( $user_id, 'fcsd_email_verified' );

    add_user_meta( $user_id, 'fcsd_email_verification_key', $key, true );
    add_user_meta( $user_id, 'fcsd_email_verified', 0, true );

    $confirm_page = get_page_by_path( 'confirmar-registre' );
    if ( ! $confirm_page ) {
        return;
    }

    $url = add_query_arg(
        array(
            'uid' => $user_id,
            'key' => $key,
        ),
        get_permalink( $confirm_page )
    );

    $subject = __( 'Confirma el teu registre a FCSD', 'fcsd' );
    $message = sprintf(
        __( "Hola %s,\n\nPer completar el teu registre fes clic en aquest enllaç:\n\n%s\n\nSi no has fet aquesta sol·licitud, ignora aquest missatge.\n\nGràcies.", 'fcsd' ),
        $user->first_name,
        $url
    );

    wp_mail( $user->user_email, $subject, $message );
}


// -----------------------------------------------------------------------------
// Shortcodes (views only)
// -----------------------------------------------------------------------------

/**
 * Shortcode [fcsd_login_register]: combined register + login (legacy / optional).
 */
function fcsd_login_register_shortcode() {

    if ( is_user_logged_in() ) {
        $profile_url = fcsd_get_page_url_by_slug( 'perfil-usuari' );
        return '<p>' . sprintf(
            __( "Ja tens la sessió iniciada. Ves al teu <a href=\"%s\">perfil</a>.", 'fcsd' ),
            esc_url( $profile_url )
        ) . '</p>';
    }

    global $fcsd_auth_errors, $fcsd_auth_success_reg;

    $errors      = is_array( $fcsd_auth_errors ) ? $fcsd_auth_errors : array();
    $success_reg = ! empty( $fcsd_auth_success_reg );

    ob_start();

    if ( $success_reg ) {
        echo '<div class="alert alert-success">';
        echo esc_html__( "T'hem enviat un email amb un enllaç per completar el registre.", 'fcsd' );
        echo '</div>';
    }

    if ( ! empty( $errors ) ) {
        echo '<div class="alert alert-danger"><ul>';
        foreach ( $errors as $e ) {
            echo '<li>' . esc_html( $e ) . '</li>';
        }
        echo '</ul></div>';
    }
    ?>
    <div class="row g-4">
        <div class="col-md-6">
            <h2><?php _e( 'Registrar-se', 'fcsd' ); ?></h2>
            <form method="post" novalidate>
                <?php wp_nonce_field( 'fcsd_register', 'fcsd_register_nonce' ); ?>
                <div class="mb-3">
                    <label for="fcsd_first_name" class="form-label"><?php _e( 'Nom', 'fcsd' ); ?></label>
                    <input id="fcsd_first_name" type="text" name="fcsd_first_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="fcsd_last_name" class="form-label"><?php _e( 'Cognoms', 'fcsd' ); ?></label>
                    <input id="fcsd_last_name" type="text" name="fcsd_last_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="fcsd_email" class="form-label"><?php _e( 'Email', 'fcsd' ); ?></label>
                    <input id="fcsd_email" type="email" name="fcsd_email" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <?php _e( 'Registrar-me', 'fcsd' ); ?>
                </button>
            </form>
        </div>

        <div class="col-md-6">
            <h2><?php _e( 'Iniciar sessió', 'fcsd' ); ?></h2>
            <form method="post" novalidate>
                <?php wp_nonce_field( 'fcsd_login', 'fcsd_login_nonce' ); ?>
                <div class="mb-3">
                    <label for="fcsd_login_email" class="form-label"><?php _e( 'Email', 'fcsd' ); ?></label>
                    <input id="fcsd_login_email" type="email" name="fcsd_login_email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="fcsd_login_password" class="form-label"><?php _e( 'Contrasenya', 'fcsd' ); ?></label>
                    <input id="fcsd_login_password" type="password" name="fcsd_login_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-secondary">
                    <?php _e( 'Entrar', 'fcsd' ); ?>
                </button>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'fcsd_login_register', 'fcsd_login_register_shortcode' );


/**
 * Shortcode [fcsd_register_form]: register-only form.
 */
function fcsd_register_form_shortcode() {

    if ( is_user_logged_in() ) {
        return '';
    }

    global $fcsd_auth_errors, $fcsd_auth_success_reg;

    $errors      = is_array( $fcsd_auth_errors ) ? $fcsd_auth_errors : array();
    $success_reg = ! empty( $fcsd_auth_success_reg );

    ob_start();

    if ( $success_reg ) {
        echo '<div class="alert alert-success">';
        echo esc_html__( "T'hem enviat un email amb un enllaç per completar el registre.", 'fcsd' );
        echo '</div>';
    }

    if ( ! empty( $errors ) ) {
        echo '<div class="alert alert-danger"><ul>';
        foreach ( $errors as $e ) {
            echo '<li>' . esc_html( $e ) . '</li>';
        }
        echo '</ul></div>';
    }
    ?>
    <h2 class="mb-3"><?php _e( 'Registrar-se', 'fcsd' ); ?></h2>
    <form method="post" novalidate>
        <?php wp_nonce_field( 'fcsd_register', 'fcsd_register_nonce' ); ?>
        <div class="mb-3">
            <label for="fcsd_first_name" class="form-label"><?php _e( 'Nom', 'fcsd' ); ?></label>
            <input id="fcsd_first_name" type="text" name="fcsd_first_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="fcsd_last_name" class="form-label"><?php _e( 'Cognoms', 'fcsd' ); ?></label>
            <input id="fcsd_last_name" type="text" name="fcsd_last_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="fcsd_email" class="form-label"><?php _e( 'Email', 'fcsd' ); ?></label>
            <input id="fcsd_email" type="email" name="fcsd_email" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">
            <?php _e( 'Registrar-me', 'fcsd' ); ?>
        </button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode( 'fcsd_register_form', 'fcsd_register_form_shortcode' );


/**
 * Shortcode [fcsd_login_form]: login-only form.
 */
function fcsd_login_form_shortcode() {

    if ( is_user_logged_in() ) {
        return '';
    }

    global $fcsd_auth_errors;
    $errors = is_array( $fcsd_auth_errors ) ? $fcsd_auth_errors : array();

    $redirect_to = '';
    if ( isset( $_GET['redirect_to'] ) ) {
        // We'll validate on redirect; here we just carry it through the POST.
        $redirect_to = esc_url_raw( wp_unslash( $_GET['redirect_to'] ) );
    }

    ob_start();

    if ( ! empty( $errors ) ) {
        echo '<div class="alert alert-danger"><ul>';
        foreach ( $errors as $e ) {
            echo '<li>' . esc_html( $e ) . '</li>';
        }
        echo '</ul></div>';
    }
    ?>
    <h2 class="mb-3"><?php _e( 'Iniciar sessió', 'fcsd' ); ?></h2>
    <form method="post" novalidate>
        <?php wp_nonce_field( 'fcsd_login', 'fcsd_login_nonce' ); ?>
        <?php if ( ! empty( $redirect_to ) ) : ?>
            <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label for="fcsd_login_email" class="form-label"><?php _e( 'Email', 'fcsd' ); ?></label>
            <input id="fcsd_login_email" type="email" name="fcsd_login_email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="fcsd_login_password" class="form-label"><?php _e( 'Contrasenya', 'fcsd' ); ?></label>
            <input id="fcsd_login_password" type="password" name="fcsd_login_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-secondary w-100">
            <?php _e( 'Entrar', 'fcsd' ); ?>
        </button>

        <div class="mt-3 text-center">
            <a class="small" href="<?php echo esc_url( add_query_arg( array( 'action' => 'reset' ), fcsd_get_system_page_url( 'login' ) ) ); ?>">
                <?php esc_html_e( 'Reseteja el teu compte', 'fcsd' ); ?>
            </a>
        </div>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode( 'fcsd_login_form', 'fcsd_login_form_shortcode' );


/**
 * Shortcode [fcsd_password_reset]: renders password reset flow (request + set new password)
 * on the theme login system page.
 */
function fcsd_password_reset_shortcode() {

    if ( is_user_logged_in() ) {
        return '';
    }

    global $fcsd_reset_errors, $fcsd_reset_success;
    $errors  = is_array( $fcsd_reset_errors ) ? $fcsd_reset_errors : array();
    $success = ! empty( $fcsd_reset_success );

    $login = isset( $_GET['login'] ) ? sanitize_text_field( wp_unslash( $_GET['login'] ) ) : '';
    $key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
    $done  = ( isset( $_GET['reset'] ) && 'done' === (string) $_GET['reset'] );

    // If we have a token, validate it now so the view can show the right form/message.
    $token_user = null;
    if ( $login && $key ) {
        $token_user = check_password_reset_key( $key, $login );
        if ( is_wp_error( $token_user ) ) {
            $errors[] = __( "L'enllaç de recuperació no és vàlid o ha caducat.", 'fcsd' );
            $token_user = null;
        }
    }

    ob_start();

    if ( $done ) {
        echo '<div class="alert alert-success">' . esc_html__( 'Contrasenya actualitzada correctament. Ja pots iniciar sessió.', 'fcsd' ) . '</div>';
    }

    if ( $success ) {
        echo '<div class="alert alert-success">' . esc_html__( "Si l'email existeix, t'hem enviat un enllaç per restablir la contrasenya.", 'fcsd' ) . '</div>';
    }

    if ( ! empty( $errors ) ) {
        echo '<div class="alert alert-danger"><ul>';
        foreach ( $errors as $e ) {
            echo '<li>' . esc_html( $e ) . '</li>';
        }
        echo '</ul></div>';
    }

    // 1) If we have a valid token -> show set new password form.
    if ( $token_user instanceof WP_User ) {
        ?>
        <h2 class="mb-3"><?php _e( 'Restablir contrasenya', 'fcsd' ); ?></h2>
        <form method="post" novalidate>
            <?php wp_nonce_field( 'fcsd_reset_password', 'fcsd_reset_password_nonce' ); ?>
            <div class="mb-3">
                <label for="fcsd_pass1" class="form-label"><?php _e( 'Nova contrasenya', 'fcsd' ); ?></label>
                <input id="fcsd_pass1" type="password" name="fcsd_pass1" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="fcsd_pass2" class="form-label"><?php _e( 'Repeteix la nova contrasenya', 'fcsd' ); ?></label>
                <input id="fcsd_pass2" type="password" name="fcsd_pass2" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <?php _e( 'Desar contrasenya', 'fcsd' ); ?>
            </button>
        </form>
        <div class="mt-3 text-center">
            <a class="small" href="<?php echo esc_url( fcsd_get_system_page_url( 'login' ) ); ?>"><?php esc_html_e( 'Tornar a iniciar sessió', 'fcsd' ); ?></a>
        </div>
        <?php
        return ob_get_clean();
    }

    // 2) Otherwise show request-reset form.
    ?>
    <h2 class="mb-3"><?php _e( 'Recuperar accés', 'fcsd' ); ?></h2>
    <form method="post" novalidate>
        <?php wp_nonce_field( 'fcsd_reset_request', 'fcsd_reset_request_nonce' ); ?>
        <div class="mb-3">
            <label for="fcsd_reset_email" class="form-label"><?php _e( 'Email', 'fcsd' ); ?></label>
            <input id="fcsd_reset_email" type="email" name="fcsd_reset_email" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">
            <?php _e( 'Enviar enllaç de recuperació', 'fcsd' ); ?>
        </button>
    </form>
    <div class="mt-3 text-center">
        <a class="small" href="<?php echo esc_url( fcsd_get_system_page_url( 'login' ) ); ?>"><?php esc_html_e( 'Tornar a iniciar sessió', 'fcsd' ); ?></a>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode( 'fcsd_password_reset', 'fcsd_password_reset_shortcode' );


/**
 * Shortcode [fcsd_confirm_registration]: render set-password form.
 */
function fcsd_confirm_registration_shortcode() {

    $uid = isset( $_GET['uid'] ) ? absint( $_GET['uid'] ) : 0;
    $key = isset( $_GET['key'] ) ? sanitize_text_field( $_GET['key'] ) : '';

    if ( ! $uid || ! $key ) {
        return '<div class="alert alert-danger">' . esc_html__( 'Enllaç invàlid.', 'fcsd' ) . '</div>';
    }

    $stored_key = get_user_meta( $uid, 'fcsd_email_verification_key', true );
    if ( ! $stored_key || $stored_key !== $key ) {
        return '<div class="alert alert-danger">' . esc_html__( "L'enllaç de verificació no és correcte o ja s'ha utilitzat.", 'fcsd' ) . '</div>';
    }

    global $fcsd_setpass_errors;

    ob_start();

    if ( ! empty( $fcsd_setpass_errors ) ) {
        echo '<div class="alert alert-danger"><ul>';
        foreach ( $fcsd_setpass_errors as $e ) {
            echo '<li>' . esc_html( $e ) . '</li>';
        }
        echo '</ul></div>';
    }
    ?>
    <h2><?php _e( 'Crea la teva contrasenya', 'fcsd' ); ?></h2>
    <form method="post" novalidate>
        <?php wp_nonce_field( 'fcsd_setpass', 'fcsd_setpass_nonce' ); ?>
        <div class="mb-3">
            <label for="fcsd_pass1" class="form-label"><?php _e( 'Contrasenya', 'fcsd' ); ?></label>
            <input id="fcsd_pass1" type="password" name="fcsd_pass1" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="fcsd_pass2" class="form-label"><?php _e( 'Repeteix la contrasenya', 'fcsd' ); ?></label>
            <input id="fcsd_pass2" type="password" name="fcsd_pass2" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">
            <?php _e( 'Desar contrasenya', 'fcsd' ); ?>
        </button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode( 'fcsd_confirm_registration', 'fcsd_confirm_registration_shortcode' );


/**
 * Shortcode [fcsd_profile]: basic user profile view.
 */
function fcsd_profile_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<div class="alert alert-warning">' . esc_html__( "Has d'iniciar sessió per veure el teu perfil.", 'fcsd' ) . '</div>';
    }

    $user = wp_get_current_user();

    // Metadades existents
    $extra_address    = get_user_meta( $user->ID, 'fcsd_address', true );
    $extra_disability = get_user_meta( $user->ID, 'fcsd_disability', true );

    // Nous camps
    $phone = get_user_meta( $user->ID, 'fcsd_phone', true );
    $bio   = get_user_meta( $user->ID, 'fcsd_profile_bio', true );

    // Adreça d'enviament per a compres
    $ship_name     = get_user_meta( $user->ID, 'fcsd_ship_name', true );
    $ship_address  = get_user_meta( $user->ID, 'fcsd_ship_address', true );
    $ship_postcode = get_user_meta( $user->ID, 'fcsd_ship_postcode', true );
    $ship_city     = get_user_meta( $user->ID, 'fcsd_ship_city', true );
    $ship_province = get_user_meta( $user->ID, 'fcsd_ship_province', true );
    $ship_notes    = get_user_meta( $user->ID, 'fcsd_ship_notes', true );

    // Foto de perfil
    $photo_id  = get_user_meta( $user->ID, 'fcsd_profile_photo_id', true );
    $photo_url = $photo_id ? wp_get_attachment_image_url( $photo_id, 'thumbnail' ) : '';

    // 🔹 Dades sincronitzades des de Sinergia (Contacts)
    $sinergia_data = get_user_meta( $user->ID, 'sinergia_person_data', true );
    if ( ! is_array( $sinergia_data ) ) {
        $sinergia_data = array();
    }
    $sinergia_id = get_user_meta( $user->ID, 'sinergia_person_id', true );

    $success = false;
    $errors  = array();

    // Processar formulari
    if (
        ! empty( $_POST['fcsd_profile_nonce'] ) &&
        wp_verify_nonce( $_POST['fcsd_profile_nonce'], 'fcsd_profile' )
    ) {
        // Dades personals
        $first_name = isset( $_POST['fcsd_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fcsd_first_name'] ) ) : '';
        $last_name  = isset( $_POST['fcsd_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fcsd_last_name'] ) ) : '';

        // Adreça principal
        $address = isset( $_POST['fcsd_address'] ) ? sanitize_text_field( wp_unslash( $_POST['fcsd_address'] ) ) : '';
        $disab   = ! empty( $_POST['fcsd_disability'] ) ? 'yes' : 'no';

        // Contacte
        $phone = isset( $_POST['fcsd_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['fcsd_phone'] ) ) : '';
        $bio   = isset( $_POST['fcsd_profile_bio'] ) ? wp_kses_post( wp_unslash( $_POST['fcsd_profile_bio'] ) ) : '';

        // Adreça d'enviament
        $ship_name     = isset( $_POST['fcsd_ship_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fcsd_ship_name'] ) ) : '';
        $ship_address  = isset( $_POST['fcsd_ship_address'] ) ? sanitize_text_field( wp_unslash( $_POST['fcsd_ship_address'] ) ) : '';
        $ship_postcode = isset( $_POST['fcsd_ship_postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['fcsd_ship_postcode'] ) ) : '';
        $ship_city     = isset( $_POST['fcsd_ship_city'] ) ? sanitize_text_field( wp_unslash( $_POST['fcsd_ship_city'] ) ) : '';
        $ship_province = isset( $_POST['fcsd_ship_province'] ) ? sanitize_text_field( wp_unslash( $_POST['fcsd_ship_province'] ) ) : '';
        $ship_notes    = isset( $_POST['fcsd_ship_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fcsd_ship_notes'] ) ) : '';

        // Actualitzar usuari bàsic
        wp_update_user(
            array(
                'ID'         => $user->ID,
                'first_name' => $first_name,
                'last_name'  => $last_name,
            )
        );

        // Guardar metadades existents
        update_user_meta( $user->ID, 'fcsd_address', $address );
        update_user_meta( $user->ID, 'fcsd_disability', $disab );

        // Nous metadades
        update_user_meta( $user->ID, 'fcsd_phone', $phone );
        update_user_meta( $user->ID, 'fcsd_profile_bio', $bio );

        update_user_meta( $user->ID, 'fcsd_ship_name', $ship_name );
        update_user_meta( $user->ID, 'fcsd_ship_address', $ship_address );
        update_user_meta( $user->ID, 'fcsd_ship_postcode', $ship_postcode );
        update_user_meta( $user->ID, 'fcsd_ship_city', $ship_city );
        update_user_meta( $user->ID, 'fcsd_ship_province', $ship_province );
        update_user_meta( $user->ID, 'fcsd_ship_notes', $ship_notes );

        // Foto de perfil (opcional)
        if ( ! empty( $_FILES['fcsd_profile_photo']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $file = $_FILES['fcsd_profile_photo'];

            if ( ! empty( $file['tmp_name'] ) ) {
                $upload_overrides = array( 'test_form' => false );
                $movefile         = wp_handle_upload( $file, $upload_overrides );

                if ( isset( $movefile['error'] ) ) {
                    $errors[] = $movefile['error'];
                } else {
                    $attachment = array(
                        'guid'           => $movefile['url'],
                        'post_mime_type' => $movefile['type'],
                        'post_title'     => sanitize_file_name( $file['name'] ),
                        'post_content'   => '',
                        'post_status'    => 'inherit',
                    );

                    $attach_id   = wp_insert_attachment( $attachment, $movefile['file'] );
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $movefile['file'] );
                    wp_update_attachment_metadata( $attach_id, $attach_data );

                    update_user_meta( $user->ID, 'fcsd_profile_photo_id', $attach_id );
                    $photo_id  = $attach_id;
                    $photo_url = wp_get_attachment_image_url( $photo_id, 'thumbnail' );
                }
            }
        }

        if ( empty( $errors ) ) {
            $success = true;
        }

        // Actualitzar variables locals
        $extra_address    = $address;
        $extra_disability = $disab;
    }

    // Comandes
    $orders = array();
    if ( class_exists( 'fcsd_Shop_Account' ) ) {
        $orders = fcsd_Shop_Account::get_user_orders( $user->ID );
    }

    // Inscripcions a formacions / esdeveniments (opcional)
    $registrations = array();
    if ( function_exists( 'fcsd_get_user_registrations' ) ) {
        $registrations = fcsd_get_user_registrations( $user->ID );
    }

    ob_start();
    ?>

    <div class="my-account-page fcsd-account">
        <?php if ( $success ) : ?>
            <div class="alert alert-success mb-3">
                <?php esc_html_e( 'Perfil actualitzat correctament.', 'fcsd' ); ?>
            </div>
        <?php endif; ?>

        <?php if ( ! empty( $errors ) ) : ?>
            <div class="alert alert-danger mb-3">
                <ul class="mb-0">
                    <?php foreach ( $errors as $e ) : ?>
                        <li><?php echo esc_html( $e ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="fcsd-profile-form">
            <?php wp_nonce_field( 'fcsd_profile', 'fcsd_profile_nonce' ); ?>

            <div class="card p-3 p-md-4">

                <!-- CABECERA COMPACTA -->
                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="profile-avatar">
                            <?php if ( $photo_url ) : ?>
                                <img src="<?php echo esc_url( $photo_url ); ?>" alt="<?php echo esc_attr( $user->display_name ); ?>">
                            <?php else : ?>
                                <div class="profile-avatar__initials">
                                    <?php echo esc_html( strtoupper( mb_substr( $user->display_name ?: $user->user_login, 0, 1 ) ) ); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="fw-semibold">
                                <?php echo esc_html( trim( $user->first_name . ' ' . $user->last_name ) ?: $user->user_login ); ?>
                            </div>
                            <div class="small text-muted">
                                <?php echo esc_html( $user->user_email ); ?>
                            </div>
                            <?php if ( ! empty( $sinergia_id ) ) : ?>
                                <div class="small mt-1">
                                    <span class="badge bg-success">
                                        <?php esc_html_e( 'Connectat a SinergiaCRM', 'fcsd' ); ?>
                                    </span>
                                    <span class="text-muted ms-2">
                                        ID: <?php echo esc_html( $sinergia_id ); ?>
                                    </span>
                                </div>
                            <?php else : ?>
                                <div class="small mt-1 text-muted">
                                    <?php esc_html_e( 'Aquest compte encara no està vinculat a SinergiaCRM.', 'fcsd' ); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <?php esc_html_e( 'Desar canvis', 'fcsd' ); ?>
                        </button>
                        <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="btn btn-outline-secondary btn-sm">
                            <?php esc_html_e( 'Tancar sessió', 'fcsd' ); ?>
                        </a>
                    </div>
                </div>

                <hr class="my-3">

                <!-- TABS PRINCIPALS -->
                <ul class="nav nav-tabs fcsd-account-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-personal-tab" data-bs-toggle="tab" data-bs-target="#tab-personal" type="button" role="tab" aria-controls="tab-personal" aria-selected="true">
                            <?php esc_html_e( 'Dades personals', 'fcsd' ); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-contact-tab" data-bs-toggle="tab" data-bs-target="#tab-contact" type="button" role="tab" aria-controls="tab-contact" aria-selected="false">
                            <?php esc_html_e( 'Contacte', 'fcsd' ); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-addresses-tab" data-bs-toggle="tab" data-bs-target="#tab-addresses" type="button" role="tab" aria-controls="tab-addresses" aria-selected="false">
                            <?php esc_html_e( 'Adreces', 'fcsd' ); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-orders-tab" data-bs-toggle="tab" data-bs-target="#tab-orders" type="button" role="tab" aria-controls="tab-orders" aria-selected="false">
                            <?php esc_html_e( 'Històric de compres', 'fcsd' ); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-training-tab" data-bs-toggle="tab" data-bs-target="#tab-training" type="button" role="tab" aria-controls="tab-training" aria-selected="false">
                            <?php esc_html_e( 'Formacions i esdeveniments', 'fcsd' ); ?>
                        </button>
                    </li>
                    <!-- 🔹 Nou tab Sinergia -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-sinergia-tab" data-bs-toggle="tab" data-bs-target="#tab-sinergia" type="button" role="tab" aria-controls="tab-sinergia" aria-selected="false">
                            <?php esc_html_e( 'SinergiaCRM', 'fcsd' ); ?>
                        </button>
                    </li>
                </ul>

                <div class="tab-content pt-3">

                    <!-- TABA: DADES PERSONALS -->
                    <div class="tab-pane fade show active" id="tab-personal" role="tabpanel" aria-labelledby="tab-personal-tab">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="fcsd_first_name"><?php esc_html_e( 'Nom', 'fcsd' ); ?></label>
                                <input type="text" id="fcsd_first_name" name="fcsd_first_name" class="form-control" value="<?php echo esc_attr( $user->first_name ); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="fcsd_last_name"><?php esc_html_e( 'Cognoms', 'fcsd' ); ?></label>
                                <input type="text" id="fcsd_last_name" name="fcsd_last_name" class="form-control" value="<?php echo esc_attr( $user->last_name ); ?>">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label" for="fcsd_profile_bio"><?php esc_html_e( 'Sobre mi', 'fcsd' ); ?></label>
                                <textarea id="fcsd_profile_bio" name="fcsd_profile_bio" rows="3" class="form-control" placeholder="<?php esc_attr_e( 'Explica breument qui ets, el teu rol, interessos...', 'fcsd' ); ?>"><?php echo esc_textarea( $bio ); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: CONTACTE -->
                    <div class="tab-pane fade" id="tab-contact" role="tabpanel" aria-labelledby="tab-contact-tab">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php esc_html_e( 'Correu electrònic', 'fcsd' ); ?> *</label>
                                <input type="email" class="form-control" value="<?php echo esc_attr( $user->user_email ); ?>" disabled>
                                <div class="form-text">
                                    <?php esc_html_e( 'Per canviar el correu, contacta amb l\'administració.', 'fcsd' ); ?>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="fcsd_phone"><?php esc_html_e( 'Telèfon de contacte', 'fcsd' ); ?></label>
                                <input type="text" id="fcsd_phone" name="fcsd_phone" class="form-control" value="<?php echo esc_attr( $phone ); ?>" placeholder="+34 600 000 000">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="fcsd_profile_photo">
                                    <?php esc_html_e( 'Foto de perfil', 'fcsd' ); ?>
                                </label>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <label class="btn btn-outline-secondary btn-sm mb-0">
                                        <?php esc_html_e( 'Seleccionar imatge', 'fcsd' ); ?>
                                        <input type="file" id="fcsd_profile_photo" name="fcsd_profile_photo" accept="image/*" class="d-none">
                                    </label>
                                    <?php if ( ! $photo_url ) : ?>
                                        <span class="small text-muted"><?php esc_html_e( 'Encara no has pujat cap foto.', 'fcsd' ); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="form-text">
                                    <?php esc_html_e( 'Formats recomanats: JPG o PNG. Mida màxima 2MB.', 'fcsd' ); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: ADRECES -->
                    <div class="tab-pane fade" id="tab-addresses" role="tabpanel" aria-labelledby="tab-addresses-tab">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <h6 class="fw-semibold mb-2"><?php esc_html_e( 'Adreça principal', 'fcsd' ); ?></h6>
                                <label class="form-label" for="fcsd_address"><?php esc_html_e( 'Adreça', 'fcsd' ); ?></label>
                                <input type="text" id="fcsd_address" name="fcsd_address" class="form-control mb-2" value="<?php echo esc_attr( $extra_address ); ?>">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="fcsd_disability" name="fcsd_disability" <?php checked( $extra_disability, 'yes' ); ?>>
                                    <label class="form-check-label" for="fcsd_disability">
                                        <?php esc_html_e( 'Tinc una discapacitat', 'fcsd' ); ?>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-2"><?php esc_html_e( "Adreça d'enviament per a compres", 'fcsd' ); ?></h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="fcsd_ship_name"><?php esc_html_e( 'Nom i cognoms per al paquet', 'fcsd' ); ?></label>
                                <input type="text" id="fcsd_ship_name" name="fcsd_ship_name" class="form-control" value="<?php echo esc_attr( $ship_name ); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="fcsd_ship_address"><?php esc_html_e( "Adreça d'enviament", 'fcsd' ); ?></label>
                                <input type="text" id="fcsd_ship_address" name="fcsd_ship_address" class="form-control" value="<?php echo esc_attr( $ship_address ); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="fcsd_ship_postcode"><?php esc_html_e( 'Codi postal', 'fcsd' ); ?></label>
                                <input type="text" id="fcsd_ship_postcode" name="fcsd_ship_postcode" class="form-control" value="<?php echo esc_attr( $ship_postcode ); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="fcsd_ship_city"><?php esc_html_e( 'Població', 'fcsd' ); ?></label>
                                <input type="text" id="fcsd_ship_city" name="fcsd_ship_city" class="form-control" value="<?php echo esc_attr( $ship_city ); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="fcsd_ship_province"><?php esc_html_e( 'Província', 'fcsd' ); ?></label>
                                <input type="text" id="fcsd_ship_province" name="fcsd_ship_province" class="form-control" value="<?php echo esc_attr( $ship_province ); ?>">
                            </div>
                            <div class="col-12 mb-0">
                                <label class="form-label" for="fcsd_ship_notes"><?php esc_html_e( "Comentaris per al repartiment", 'fcsd' ); ?></label>
                                <textarea id="fcsd_ship_notes" name="fcsd_ship_notes" rows="2" class="form-control"><?php echo esc_textarea( $ship_notes ); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: HISTÒRIC DE COMPRES -->
                    <div class="tab-pane fade" id="tab-orders" role="tabpanel" aria-labelledby="tab-orders-tab">
                        <?php if ( empty( $orders ) ) : ?>
                            <p class="text-muted mb-0"><?php esc_html_e( 'Encara no has fet cap compra.', 'fcsd' ); ?></p>
                        <?php else : ?>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e( 'ID', 'fcsd' ); ?></th>
                                            <th><?php esc_html_e( 'Data', 'fcsd' ); ?></th>
                                            <th><?php esc_html_e( 'Total', 'fcsd' ); ?></th>
                                            <th><?php esc_html_e( 'Estat', 'fcsd' ); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ( $orders as $order ) : ?>
                                            <tr>
                                                <td>#<?php echo esc_html( $order->id ); ?></td>
                                                <td><?php echo esc_html( $order->created_at ); ?></td>
                                                <td><?php echo esc_html( number_format_i18n( $order->total, 2 ) ); ?> €</td>
                                                <td><?php echo esc_html( $order->status ); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- TAB: FORMACIONS I ESDEVENIMENTS -->
                    <div class="tab-pane fade" id="tab-training" role="tabpanel" aria-labelledby="tab-training-tab">
                        <?php if ( empty( $registrations ) ) : ?>
                            <p class="text-muted mb-0">
                                <?php esc_html_e( 'Encara no tens inscripcions actives. Quan et donis d\'alta a una formació o esdeveniment, apareixerà aquí.', 'fcsd' ); ?>
                            </p>
                        <?php else : ?>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e( 'Títol', 'fcsd' ); ?></th>
                                            <th><?php esc_html_e( 'Data', 'fcsd' ); ?></th>
                                            <th><?php esc_html_e( 'Estat', 'fcsd' ); ?></th>
                                            <th class="text-end"><?php esc_html_e( 'Accions', 'fcsd' ); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ( $registrations as $reg ) : ?>
                                            <tr>
                                                <td>
                                                    <a href="<?php echo esc_url( get_permalink( $reg->event_id ) ); ?>">
                                                        <?php echo esc_html( get_the_title( $reg->event_id ) ); ?>
                                                    </a>
                                                </td>
                                                <td class="small text-muted">
                                                    <?php
                                                    if ( ! empty( $reg->date ) ) {
                                                        echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $reg->date ) ) );
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status      = ! empty( $reg->status ) ? $reg->status : 'pending';
                                                    $badge_class = 'bg-secondary';
                                                    if ( 'confirmed' === $status ) {
                                                        $badge_class = 'bg-success';
                                                    } elseif ( 'cancelled' === $status ) {
                                                        $badge_class = 'bg-danger';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo esc_attr( $badge_class ); ?>">
                                                        <?php
                                                        switch ( $status ) {
                                                            case 'confirmed':
                                                                esc_html_e( 'Confirmada', 'fcsd' );
                                                                break;
                                                            case 'cancelled':
                                                                esc_html_e( 'Cancel·lada', 'fcsd' );
                                                                break;
                                                            default:
                                                                esc_html_e( 'Pendent', 'fcsd' );
                                                                break;
                                                        }
                                                        ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <a href="<?php echo esc_url( get_permalink( $reg->event_id ) ); ?>" class="btn btn-sm btn-outline-secondary">
                                                        <?php esc_html_e( 'Veure detall', 'fcsd' ); ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 🔹 TAB: SINERGIACRM -->
                    <div class="tab-pane fade" id="tab-sinergia" role="tabpanel" aria-labelledby="tab-sinergia-tab">
                        <?php if ( empty( $sinergia_data ) ) : ?>
                            <p class="text-muted mb-0">
                                <?php esc_html_e( 'Aquest compte encara no té dades vinculades de SinergiaCRM.', 'fcsd' ); ?>
                            </p>
                        <?php else : ?>
                            <p class="text-muted">
                                <?php esc_html_e( 'Aquestes dades provenen de SinergiaCRM (mòdul Contacts). Per modificar-les de manera oficial, cal fer-ho des de Sinergia.', 'fcsd' ); ?>
                            </p>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6 class="fw-semibold mb-1"><?php esc_html_e( 'Nom complet', 'fcsd' ); ?></h6>
                                    <p class="mb-0">
                                        <?php
                                        $full = trim(
                                            ( isset( $sinergia_data['first_name'] ) ? $sinergia_data['first_name'] . ' ' : '' ) .
                                            ( isset( $sinergia_data['last_name'] ) ? $sinergia_data['last_name'] : '' )
                                        );
                                        echo esc_html( $full );
                                        ?>
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="fw-semibold mb-1"><?php esc_html_e( 'Email', 'fcsd' ); ?></h6>
                                    <p class="mb-0">
                                        <?php echo esc_html( $sinergia_data['email'] ?? '' ); ?>
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="fw-semibold mb-1"><?php esc_html_e( 'Telèfon', 'fcsd' ); ?></h6>
                                    <p class="mb-0">
                                        <?php echo esc_html( $sinergia_data['phone'] ?? '' ); ?>
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="fw-semibold mb-1"><?php esc_html_e( 'Adreça', 'fcsd' ); ?></h6>
                                    <p class="mb-0">
                                        <?php
                                        $parts = array();
                                        if ( ! empty( $sinergia_data['street'] ) ) {
                                            $parts[] = $sinergia_data['street'];
                                        }
                                        if ( ! empty( $sinergia_data['postcode'] ) ) {
                                            $parts[] = $sinergia_data['postcode'];
                                        }
                                        if ( ! empty( $sinergia_data['city'] ) ) {
                                            $parts[] = $sinergia_data['city'];
                                        }
                                        echo esc_html( implode( ', ', $parts ) );
                                        ?>
                                    </p>
                                </div>
                                <div class="col-md-6 mb-0">
                                    <h6 class="fw-semibold mb-1"><?php esc_html_e( 'ID Sinergia', 'fcsd' ); ?></h6>
                                    <p class="mb-0">
                                        <?php echo esc_html( $sinergia_id ); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div><!-- /.tab-content -->

            </div><!-- /.card -->
        </form>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'fcsd_profile', 'fcsd_profile_shortcode' );




/**
 * Shortcode [fcsd_intranet_link]: show link to intranet if user can access.
 */
function fcsd_intranet_link_shortcode() {

    if ( ! is_user_logged_in() ) {
        return '';
    }

    $user = wp_get_current_user();

    // Keep roles in sync for FCSD emails (legacy/imported users).
    fcsd_maybe_add_worker_role( $user );

    $role_access  = ( in_array( 'worker', (array) $user->roles, true ) || in_array( 'intranet_admin', (array) $user->roles, true ) || in_array( 'administrator', (array) $user->roles, true ) );
    $email_access = (bool) preg_match( '/@fcsd\.org$/i', (string) $user->user_email );

    if ( $role_access || $email_access ) {
        $url = fcsd_get_option( 'fcsd_intranet_url' );
        if ( $url ) {
            return '<p><a href="' . esc_url( $url ) . '" class="btn btn-outline-primary" target="_blank" rel="noopener">' .
                esc_html__( 'Accedir a la intranet', 'fcsd' ) .
                '</a></p>';
        }
    }

    return '';
}
add_shortcode( 'fcsd_intranet_link', 'fcsd_intranet_link_shortcode' );


/**
 * Shortcode [fcsd_social_links]: display social links.
 */
function fcsd_social_links_shortcode() {

    ob_start();
    ?>
    <ul class="list-inline">
        <?php
        $socials = array(
            'twitter'   => 'bi-twitter-x',
            'facebook'  => 'bi-facebook',
            'instagram' => 'bi-instagram',
            'linkedin'  => 'bi-linkedin',
            'youtube'   => 'bi-youtube',
            'tiktok'    => 'bi-tiktok',
        );

        foreach ( $socials as $key => $icon_class ) {
            $url = fcsd_get_option( 'fcsd_social_' . $key );
            if ( $url ) {
                printf(
                    '<li class="list-inline-item"><a href="%s" target="_blank" rel="noopener"><i class="bi %s"></i></a></li>',
                    esc_url( $url ),
                    esc_attr( $icon_class )
                );
            }
        }
        ?>
    </ul>
    <?php
    return ob_get_clean();
}
add_shortcode( 'fcsd_social_links', 'fcsd_social_links_shortcode' );

