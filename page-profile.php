<?php
/**
 * Template Name: Perfil
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

if ( ! is_user_logged_in() ) : ?>
  <div class="container content py-5 my-account-page">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <div class="card p-4 text-center">
          <h1 class="h3 mb-3"><?php esc_html_e( 'El meu perfil', 'fcsd' ); ?></h1>
          <p class="text-muted mb-4">
            <?php esc_html_e( "Has d'iniciar sessió per accedir al teu perfil i gestionar les teves dades.", 'fcsd' ); ?>
          </p>
          <?php
          $login_url = function_exists( 'fcsd_get_system_page_url' )
              ? add_query_arg( array( 'redirect_to' => get_permalink() ), fcsd_get_system_page_url( 'login' ) )
              : wp_login_url( get_permalink() );
          ?>
          <a href="<?php echo esc_url( $login_url ); ?>" class="btn btn-accent">
            <?php esc_html_e( 'Iniciar sessió', 'fcsd' ); ?>
          </a>
        </div>
      </div>
    </div>
  </div>
<?php
  get_footer();
  return;
endif;

// Usuario actual
$current_user = wp_get_current_user();
$user_id      = $current_user->ID;

// Metas
$phone       = get_user_meta( $user_id, 'phone', true );
$profile_bio = get_user_meta( $user_id, 'profile_bio', true );

// Adreça comandes
$shipping_first_name = get_user_meta( $user_id, 'shipping_first_name', true );
$shipping_last_name  = get_user_meta( $user_id, 'shipping_last_name', true );
$shipping_address_1  = get_user_meta( $user_id, 'shipping_address_1', true );
$shipping_address_2  = get_user_meta( $user_id, 'shipping_address_2', true );
$shipping_city       = get_user_meta( $user_id, 'shipping_city', true );
$shipping_postcode   = get_user_meta( $user_id, 'shipping_postcode', true );
$shipping_state      = get_user_meta( $user_id, 'shipping_state', true );
$shipping_country    = get_user_meta( $user_id, 'shipping_country', true );

// Foto
$profile_photo_id  = get_user_meta( $user_id, 'profile_photo_id', true );
$profile_photo_url = $profile_photo_id ? wp_get_attachment_image_url( $profile_photo_id, 'thumbnail' ) : '';

/* ---------------------------------------------------------------------
   Sinergia metas (ACTUALIZADO)
   --------------------------------------------------------------------- */
// Nuevo sistema (fcsd_*) + compat con el antiguo (sinergia_*)
$sinergia_id = get_user_meta( $user_id, 'fcsd_sinergia_contact_id', true );
if ( empty($sinergia_id) ) {
  $sinergia_id = get_user_meta( $user_id, 'sinergia_person_id', true );
}

// Para mostrar datos extra: primero intenta caché local por email
$sinergia_data = [];
if ( function_exists('fcsd_sinergia_find_cached_contact_by_email') ) {
  $sinergia_data = fcsd_sinergia_find_cached_contact_by_email( $current_user->user_email ) ?: [];
} else {
  // Compat antiguo
  $old = get_user_meta( $user_id, 'sinergia_person_data', true );
  $sinergia_data = is_array($old) ? $old : [];
}

// Pedidos / historial compras
$orders = [];
if ( class_exists( 'fcsd_Shop_Account' ) && method_exists( 'fcsd_Shop_Account', 'get_user_orders' ) ) {
  $orders = fcsd_Shop_Account::get_user_orders( $user_id );
} elseif ( function_exists( 'wc_get_orders' ) ) {
  // fallback e-commerce nativo
  $orders = wc_get_orders([
    'customer_id' => $user_id,
    'limit'       => -1,
    'orderby'     => 'date',
    'order'       => 'DESC',
  ]);
}

$profile_updated = false;
$profile_errors  = [];

// Guardado
if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['fcsd_profile_nonce'] ) ) {

  if ( ! wp_verify_nonce( $_POST['fcsd_profile_nonce'], 'fcsd_update_profile' ) ) {
    $profile_errors[] = __( 'Ha ocurrido un problema al validar el formulario. Inténtalo de nuevo.', 'fcsd' );
  } else {

    $display_name = isset($_POST['display_name']) ? sanitize_text_field($_POST['display_name']) : '';
    $email        = isset($_POST['user_email']) ? sanitize_email($_POST['user_email']) : '';
    $phone_new    = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $bio_new      = isset($_POST['profile_bio']) ? wp_kses_post($_POST['profile_bio']) : '';

    if ( empty($display_name) ) $profile_errors[] = __( 'El nombre no pot estar buit.', 'fcsd' );
    if ( empty($email) || !is_email($email) ) $profile_errors[] = __( 'Aquest email no és vàlid.', 'fcsd' );

    $shipping_first_name_new = isset($_POST['shipping_first_name']) ? sanitize_text_field($_POST['shipping_first_name']) : '';
    $shipping_last_name_new  = isset($_POST['shipping_last_name']) ? sanitize_text_field($_POST['shipping_last_name']) : '';
    $shipping_address_1_new  = isset($_POST['shipping_address_1']) ? sanitize_text_field($_POST['shipping_address_1']) : '';
    $shipping_address_2_new  = isset($_POST['shipping_address_2']) ? sanitize_text_field($_POST['shipping_address_2']) : '';
    $shipping_city_new       = isset($_POST['shipping_city']) ? sanitize_text_field($_POST['shipping_city']) : '';
    $shipping_postcode_new   = isset($_POST['shipping_postcode']) ? sanitize_text_field($_POST['shipping_postcode']) : '';
    $shipping_state_new      = isset($_POST['shipping_state']) ? sanitize_text_field($_POST['shipping_state']) : '';
    $shipping_country_new    = isset($_POST['shipping_country']) ? sanitize_text_field($_POST['shipping_country']) : '';

    $pass1 = $_POST['pass1'] ?? '';
    $pass2 = $_POST['pass2'] ?? '';

    if ( $pass1 || $pass2 ) {
      if ( $pass1 !== $pass2 ) $profile_errors[] = __( 'Les contrasenyes no coincideixen.', 'fcsd' );
      elseif ( strlen($pass1) < 8 ) $profile_errors[] = __( 'La contrasenya ha te tenir al menys 8 caracters.', 'fcsd' );
    }

    if ( !empty($_FILES['profile_photo']['name']) ) {
      require_once ABSPATH.'wp-admin/includes/file.php';
      require_once ABSPATH.'wp-admin/includes/media.php';
      require_once ABSPATH.'wp-admin/includes/image.php';

      $attachment_id = media_handle_upload('profile_photo', 0);
      if ( is_wp_error($attachment_id) ) {
        $profile_errors[] = __( 'No se pudo subir la imagen.', 'fcsd' );
      } else {
        update_user_meta($user_id, 'profile_photo_id', $attachment_id);
        $profile_photo_id  = $attachment_id;
        $profile_photo_url = wp_get_attachment_image_url($profile_photo_id, 'thumbnail');
      }
    }

    if ( empty($profile_errors) ) {

      wp_update_user([
        'ID'           => $user_id,
        'display_name' => $display_name,
        'user_email'   => $email,
      ]);

      update_user_meta($user_id, 'phone', $phone_new);
      update_user_meta($user_id, 'profile_bio', $bio_new);

      update_user_meta($user_id, 'shipping_first_name', $shipping_first_name_new);
      update_user_meta($user_id, 'shipping_last_name', $shipping_last_name_new);
      update_user_meta($user_id, 'shipping_address_1', $shipping_address_1_new);
      update_user_meta($user_id, 'shipping_address_2', $shipping_address_2_new);
      update_user_meta($user_id, 'shipping_city', $shipping_city_new);
      update_user_meta($user_id, 'shipping_postcode', $shipping_postcode_new);
      update_user_meta($user_id, 'shipping_state', $shipping_state_new);
      update_user_meta($user_id, 'shipping_country', $shipping_country_new);

      if ( $pass1 && $pass1 === $pass2 ) {
        wp_set_password($pass1, $user_id);
        wp_set_auth_cookie($user_id);
      }

      // refrescar memoria
      $current_user = wp_get_current_user();
      $phone = $phone_new;
      $profile_bio = $bio_new;
      $shipping_first_name = $shipping_first_name_new;
      $shipping_last_name  = $shipping_last_name_new;
      $shipping_address_1  = $shipping_address_1_new;
      $shipping_address_2  = $shipping_address_2_new;
      $shipping_city       = $shipping_city_new;
      $shipping_postcode   = $shipping_postcode_new;
      $shipping_state      = $shipping_state_new;
      $shipping_country    = $shipping_country_new;

      // Recalcular Sinergia metas tras cambios de email
      $sinergia_id = get_user_meta( $user_id, 'fcsd_sinergia_contact_id', true );
      if ( empty($sinergia_id) ) {
        $sinergia_id = get_user_meta( $user_id, 'sinergia_person_id', true );
      }
      if ( function_exists('fcsd_sinergia_find_cached_contact_by_email') ) {
        $sinergia_data = fcsd_sinergia_find_cached_contact_by_email( $current_user->user_email ) ?: [];
      }

      $profile_updated = true;
    }
  }
}

$user_registrations = function_exists('fcsd_get_user_registrations')
  ? fcsd_get_user_registrations($user_id)
  : [];

// ------------------------------------------------------------------
// INSCRIPCIONS DE SINERGIA PER AQUEST USUARI (lazy + refresc segur)
// ------------------------------------------------------------------
if ( ! empty( $sinergia_id )
     && function_exists( 'fcsd_sinergia_get_cached_registrations_for_contact' ) ) {

  // 1) Primer intentem llegir de la caché persistent
  $cached_regs = fcsd_sinergia_get_cached_registrations_for_contact( $sinergia_id );

  // 2) Si no hi ha res cachejat, fem UN refresc via API per a aquest contacte
  if ( empty( $cached_regs )
       && function_exists( 'fcsd_sinergia_refresh_registrations_for_contact' )
       && function_exists( 'fcsd_sinergia_get_client' ) ) {

    $client = fcsd_sinergia_get_client();

    if ( ! is_wp_error( $client ) ) {
      // Això fa UNA trucada a Sinergia per aquest contact_id
      fcsd_sinergia_refresh_registrations_for_contact( $client, $sinergia_id );

      // Tornem a llegir de la caché (ara actualitzada)
      $cached_regs = fcsd_sinergia_get_cached_registrations_for_contact( $sinergia_id );
    }
  }
}


// mezclar con inscripciones de Sinergia
if ( function_exists( 'fcsd_sinergia_get_normalized_user_registrations' ) ) {
  $user_registrations = fcsd_sinergia_get_normalized_user_registrations(
    $user_id,
    $user_registrations
  );
} elseif ( function_exists('fcsd_sinergia_merge_registrations_for_user') ) {
  // Backward compat si el helper nou no existeix per algun motiu
  $user_registrations = fcsd_sinergia_merge_registrations_for_user(
    $user_id,
    $user_registrations
  );
}



/**
 * Helpers para pedidos “mixtos” (custom vs WC_Order)
 */
function fcsd_profile_order_id($order){
  if (is_object($order) && method_exists($order,'get_id')) return $order->get_id();
  if (is_object($order) && isset($order->id)) return $order->id;
  if (is_array($order) && isset($order['id'])) return $order['id'];
  return '';
}
function fcsd_profile_order_date($order){
  if (is_object($order) && method_exists($order,'get_date_created')) {
    $d = $order->get_date_created();
    return $d ? $d->date_i18n(get_option('date_format')) : '';
  }
  if (is_object($order) && isset($order->created_at)) return $order->created_at;
  if (is_array($order) && isset($order['created_at'])) return $order['created_at'];
  return '';
}
function fcsd_profile_order_total($order){
  if (is_object($order) && method_exists($order,'get_total')) return $order->get_total();
  if (is_object($order) && isset($order->total)) return $order->total;
  if (is_array($order) && isset($order['total'])) return $order['total'];
  return '';
}
function fcsd_profile_order_status($order){
  if (is_object($order) && method_exists($order,'get_status')) return $order->get_status();
  if (is_object($order) && isset($order->status)) return $order->status;
  if (is_array($order) && isset($order['status'])) return $order['status'];
  return '';
}

?>

<div class="container content py-5 my-account-page">

  <!-- CABECERA HORIZONTAL -->
  <div class="card account-card p-4 mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-3">
        <div class="account-avatar">
          <?php if ( $profile_photo_url ) : ?>
            <img src="<?php echo esc_url($profile_photo_url); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>">
          <?php else : ?>
            <div class="account-avatar__initials">
              <?php echo esc_html( strtoupper( substr( $current_user->display_name ?: $current_user->user_login, 0, 1 ) ) ); ?>
            </div>
          <?php endif; ?>
        </div>

        <div>
          <h1 class="h4 mb-1">
            <?php echo esc_html( $current_user->display_name ?: $current_user->user_login ); ?>
          </h1>

          <div class="d-flex flex-wrap gap-2 align-items-center">
            <div class="text-muted small">
              <?php
                printf(
                  esc_html__( 'Membre desde %s', 'fcsd' ),
                  esc_html( date_i18n( get_option('date_format'), strtotime($current_user->user_registered) ) )
                );
              ?>
            </div>

            <?php if ( ! empty( $sinergia_id ) ) : ?>
              <span class="badge bg-success"><?php esc_html_e('Connectat a SinergiaCRM','fcsd'); ?></span>
            <?php else : ?>
              <span class="badge bg-secondary"><?php esc_html_e('No connectat a SinergiaCRM','fcsd'); ?></span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2">
        <?php if ( function_exists('wc_get_page_permalink') ) : ?>
          <a class="btn btn-outline-secondary btn-sm"
             href="<?php echo esc_url( wc_get_page_permalink('myaccount') ); ?>">
            <?php esc_html_e('Comandes i facturació','fcsd'); ?>
          </a>
        <?php endif; ?>

        <a class="btn btn-outline-danger btn-sm"
           href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>">
          <?php esc_html_e('Tanca sessió','fcsd'); ?>
        </a>
      </div>
    </div>
  </div>

  <?php if ( $profile_updated ) : ?>
    <div class="alert alert-success">
      <?php esc_html_e('El teu perfil s\'ha actualitzat correctament.','fcsd'); ?>
    </div>
  <?php endif; ?>

  <?php if ( !empty($profile_errors) ) : ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ( $profile_errors as $error ) : ?>
          <li><?php echo esc_html($error); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="account-form">
    <?php wp_nonce_field('fcsd_update_profile','fcsd_profile_nonce'); ?>

    <!-- TABS -->
    <ul class="nav nav-tabs fcsd-account-tabs mb-3" id="profileTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-perfil-button" data-bs-toggle="tab" data-bs-target="#tab-perfil" type="button" role="tab">
          <?php esc_html_e('Dades personals','fcsd'); ?>
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-direccion-button" data-bs-toggle="tab" data-bs-target="#tab-direccion" type="button" role="tab">
          <?php esc_html_e('Adreça','fcsd'); ?>
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-seguridad-button" data-bs-toggle="tab" data-bs-target="#tab-seguridad" type="button" role="tab">
          <?php esc_html_e('Seguretat i accés','fcsd'); ?>
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-inscripciones-button" data-bs-toggle="tab" data-bs-target="#tab-inscripciones" type="button" role="tab">
          <?php esc_html_e('Formacions i esdeveniments','fcsd'); ?>
        </button>
      </li>

      <!-- TAB: HISTÒRIC DE COMPRES -->
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-compras-button" data-bs-toggle="tab" data-bs-target="#tab-compras" type="button" role="tab">
          <?php esc_html_e('Històric de compres','fcsd'); ?>
        </button>
      </li>

      <!-- TAB: SINERGIA -->
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-sinergia-button" data-bs-toggle="tab" data-bs-target="#tab-sinergia" type="button" role="tab">
          <?php esc_html_e('SinergiaCRM','fcsd'); ?>
        </button>
      </li>
    </ul>

    <div class="tab-content" id="profileTabsContent">

      <div class="tab-pane fade show active" id="tab-perfil" role="tabpanel" aria-labelledby="tab-perfil-button">
        <section class="card account-card p-4 mb-4">
          <h2 class="h5 mb-3"><?php esc_html_e('Dades personals','fcsd'); ?></h2>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label"><?php esc_html_e('Nom','fcsd'); ?></label>
              <input type="text" name="display_name" class="form-control" value="<?php echo esc_attr($current_user->display_name); ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label"><?php esc_html_e('Correu electrònic','fcsd'); ?></label>
              <input type="email" name="user_email" class="form-control" value="<?php echo esc_attr($current_user->user_email); ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label"><?php esc_html_e('Telèfon','fcsd'); ?></label>
              <input type="text" name="phone" class="form-control" value="<?php echo esc_attr($phone); ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label"><?php esc_html_e('Foto de perfil','fcsd'); ?></label>
              <input type="file" name="profile_photo" class="form-control">
              <?php if ( $profile_photo_url ) : ?>
                <img class="mt-2 rounded" src="<?php echo esc_url($profile_photo_url); ?>" width="64" height="64" alt="">
              <?php endif; ?>
            </div>

            <div class="col-12">
              <label class="form-label"><?php esc_html_e('Biografia','fcsd'); ?></label>
              <textarea name="profile_bio" class="form-control" rows="4"><?php echo esc_textarea($profile_bio); ?></textarea>
            </div>
          </div>
        </section>
      </div>

      <div class="tab-pane fade" id="tab-direccion" role="tabpanel" aria-labelledby="tab-direccion-button">
        <section class="card account-card p-4 mb-4">
          <h2 class="h5 mb-3"><?php esc_html_e('Adreça','fcsd'); ?></h2>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label"><?php esc_html_e('Nom','fcsd'); ?></label>
              <input type="text" name="shipping_first_name" class="form-control" value="<?php echo esc_attr($shipping_first_name); ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label"><?php esc_html_e('Cognoms','fcsd'); ?></label>
              <input type="text" name="shipping_last_name" class="form-control" value="<?php echo esc_attr($shipping_last_name); ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label"><?php esc_html_e('Adreça','fcsd'); ?></label>
              <input type="text" name="shipping_address_1" class="form-control" value="<?php echo esc_attr($shipping_address_1); ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label"><?php esc_html_e("Adreça (línia 2)",'fcsd'); ?></label>
              <input type="text" name="shipping_address_2" class="form-control" value="<?php echo esc_attr($shipping_address_2); ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label"><?php esc_html_e('Ciutat','fcsd'); ?></label>
              <input type="text" name="shipping_city" class="form-control" value="<?php echo esc_attr($shipping_city); ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label"><?php esc_html_e('Codi postal','fcsd'); ?></label>
              <input type="text" name="shipping_postcode" class="form-control" value="<?php echo esc_attr($shipping_postcode); ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label"><?php esc_html_e('Província / Estat','fcsd'); ?></label>
              <input type="text" name="shipping_state" class="form-control" value="<?php echo esc_attr($shipping_state); ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label"><?php esc_html_e('País','fcsd'); ?></label>
              <input type="text" name="shipping_country" class="form-control" value="<?php echo esc_attr($shipping_country); ?>">
            </div>
          </div>
        </section>
      </div>

      <div class="tab-pane fade" id="tab-seguridad" role="tabpanel" aria-labelledby="tab-seguridad-button">
        <section class="card account-card p-4 mb-4">
          <h2 class="h5 mb-3"><?php esc_html_e('Seguretat i accés','fcsd'); ?></h2>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label"><?php esc_html_e('Nova contrasenya','fcsd'); ?></label>
              <input type="password" name="pass1" class="form-control" autocomplete="new-password">
            </div>

            <div class="col-md-6">
              <label class="form-label"><?php esc_html_e('Repetir contrasenya','fcsd'); ?></label>
              <input type="password" name="pass2" class="form-control" autocomplete="new-password">
            </div>
          </div>
        </section>
      </div>

     <div class="tab-pane fade" id="tab-inscripciones" role="tabpanel" aria-labelledby="tab-inscripciones-button">
        <section class="card account-card p-4 mb-4">
          <h2 class="h5 mb-3"><?php esc_html_e( 'Inscripcions', 'fcsd' ); ?></h2>

          <?php if ( empty( $user_registrations ) ) : ?>
            <p class="mb-0 text-muted"><?php esc_html_e( 'No tens inscripcions encara.', 'fcsd' ); ?></p>
          <?php else : ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead>
                  <tr>
                    <th><?php esc_html_e( 'Esdeveniment / Formació', 'fcsd' ); ?></th>
                    <th><?php esc_html_e( 'Data', 'fcsd' ); ?></th>
                    <th><?php esc_html_e( 'Estat inscripció', 'fcsd' ); ?></th>
                    <th><?php esc_html_e( 'Estat esdeveniment', 'fcsd' ); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ( $user_registrations as $reg ) :

                    $title     = isset( $reg['title'] ) ? trim( (string) $reg['title'] ) : '';
                    $permalink = ! empty( $reg['permalink'] ) ? esc_url( $reg['permalink'] ) : '';

                    $event_start = $reg['event_start'] ?? '';
                    $event_end   = $reg['event_end'] ?? '';
                    $display_date = $event_start && $event_end
                      ? $event_start . ' – ' . $event_end
                      : ( $event_start ?: ( $event_end ?: ( $reg['date'] ?? '' ) ) );

                    // Estado inscripción
                    $status_raw   = strtolower( trim( $reg['status'] ?? '' ) );
                    $status_label = '';
                    $status_class = 'badge bg-secondary';

                    if ( $status_raw === '' ) {
                      $status_label = __( 'Confirmat', 'fcsd' );
                      $status_class = 'badge bg-success';
                    } elseif ( in_array( $status_raw, [ 'confirmat', 'confirmada', 'confirmed', 'confirmado', 'confirmada crm' ] ) ) {
                      $status_label = __( 'Confirmat', 'fcsd' );
                      $status_class = 'badge bg-success';
                    } elseif ( str_contains( $status_raw, 'espera' ) || str_contains( $status_raw, 'wait' ) || str_contains( $status_raw, 'pendent' ) || str_contains( $status_raw, 'pending' ) ) {
                      $status_label = __( 'En espera', 'fcsd' );
                      $status_class = 'badge bg-warning text-dark';
                    } else {
                      $status_label = ucwords( $status_raw );
                    }

                    // Estado evento
                    $event_state       = $reg['event_state'] ?? '';
                    $event_state_label = '';
                    $event_state_class = 'badge bg-secondary';

                    switch ( $event_state ) {
                      case 'active':
                        $event_state_label = __( 'Actiu', 'fcsd' );
                        $event_state_class = 'badge bg-success';
                        break;
                      case 'finished':
                        $event_state_label = __( 'Finalitzat', 'fcsd' );
                        $event_state_class = 'badge bg-secondary';
                        break;
                      case 'upcoming':
                        $event_state_label = __( 'Pendent', 'fcsd' );
                        $event_state_class = 'badge bg-info text-dark';
                        break;
                    }
                  ?>
                    <tr>
                      <td>
                        <?php if ( $permalink ) : ?>
                          <a href="<?php echo $permalink; ?>">
                            <?php echo esc_html( $title ); ?>
                          </a>
                        <?php else : ?>
                          <?php echo esc_html( $title ); ?>
                        <?php endif; ?>
                      </td>
                      <td><?php echo esc_html( $display_date ); ?></td>
                      <td><span class="<?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
                      <td><span class="<?php echo esc_attr( $event_state_class ); ?>"><?php echo esc_html( $event_state_label ); ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </section>
      </div>


      <!-- TAB: COMPRES -->
      <div class="tab-pane fade" id="tab-compras" role="tabpanel" aria-labelledby="tab-compras-button">
        <section class="card account-card p-4 mb-4">
          <h2 class="h5 mb-3"><?php esc_html_e('Històric de compres','fcsd'); ?></h2>

          <?php if ( empty( $orders ) ) : ?>
            <p class="mb-0 text-muted"><?php esc_html_e('Encara no has fet cap compra.','fcsd'); ?></p>
          <?php else : ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead>
                  <tr>
                    <th><?php esc_html_e('ID','fcsd'); ?></th>
                    <th><?php esc_html_e('Data','fcsd'); ?></th>
                    <th><?php esc_html_e('Total','fcsd'); ?></th>
                    <th><?php esc_html_e('Estat','fcsd'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ( $orders as $order ) :
                    $oid = fcsd_profile_order_id($order);
                    $date = fcsd_profile_order_date($order);
                    $total = fcsd_profile_order_total($order);
                    $status = fcsd_profile_order_status($order);
                  ?>
                    <tr>
                      <td>#<?php echo esc_html( $oid ); ?></td>
                      <td><?php echo esc_html( $date ); ?></td>
                      <td>
                        <?php
                          if ( $total !== '' ) {
                            echo esc_html( number_format_i18n( (float)$total, 2 ) ) . ' €';
                          }
                        ?>
                      </td>
                      <td><?php echo esc_html( $status ); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </section>
      </div>

      <!-- TAB: SINERGIA -->
      <div class="tab-pane fade" id="tab-sinergia" role="tabpanel" aria-labelledby="tab-sinergia-button">
        <section class="card account-card p-4 mb-4">
          <h2 class="h5 mb-3"><?php esc_html_e('SinergiaCRM','fcsd'); ?></h2>

          <?php if ( empty( $sinergia_id ) ) : ?>
            <div class="alert alert-secondary mb-0">
              <?php esc_html_e("El teu usuari no està sincronitzat amb SinergiaCRM. Si creus que això és un error, contacta amb suport.",'fcsd'); ?>
            </div>
          <?php else : ?>
            <div class="alert alert-success">
              <?php esc_html_e('Usuari sincronitzat amb SinergiaCRM.','fcsd'); ?>
            </div>

            <?php if ( ! empty( $sinergia_data ) ) : ?>
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <tbody>
                    <?php foreach ( $sinergia_data as $key => $value ) : ?>
                      <?php if ( is_scalar( $value ) && $value !== '' ) : ?>
                        <tr>
                          <th class="text-nowrap"><?php echo esc_html( ucwords( str_replace('_',' ', $key ) ) ); ?></th>
                          <td><?php echo esc_html( (string) $value ); ?></td>
                        </tr>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else : ?>
              <p class="text-muted mb-0"><?php esc_html_e('No hi ha dades addicionals de Sinergia.','fcsd'); ?></p>
            <?php endif; ?>
          <?php endif; ?>
        </section>
      </div>

    </div><!-- /.tab-content -->

    <div class="d-flex justify-content-end mt-3">
      <button type="submit" class="btn btn-accent">
        <?php esc_html_e('Desar canvis','fcsd'); ?>
      </button>
    </div>

  </form>

</div>

<?php get_footer(); ?>
