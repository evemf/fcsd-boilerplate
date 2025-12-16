<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php wp_body_open(); ?>

<?php
$logo_generic = fcsd_get_option( 'fcsd_logo' );
$logo_light   = fcsd_get_option( 'fcsd_logo_light' );
$logo_dark    = fcsd_get_option( 'fcsd_logo_dark' );

$has_search   = (bool) fcsd_get_option( 'fcsd_enable_search', true );
$has_contrast = (bool) fcsd_get_option( 'fcsd_enable_contrast', true );
?>

<!-- Top bar -->
<div class="topbar py-1 small">
  <div class="container-fluid d-flex align-items-center justify-content-between">

    <!-- Enlaces de la franja superior -->
    <ul class="topbar__links list-inline mb-0">
      <?php
      wp_nav_menu(
          array(
              'theme_location' => 'topbar',
              'container'      => false,
              'items_wrap'     => '%3$s',
              'depth'          => 1,
              'fallback_cb'    => false,
          )
      );
      ?>
    </ul>

    <div class="d-flex align-items-center gap-3">

<!-- Idiomas -->
<nav class="topbar__langs" aria-label="<?php esc_attr_e('Idiomes', 'fcsd'); ?>">
  <ul class="list-inline mb-0">
    <li class="list-inline-item"><a href="<?php echo esc_url( fcsd_switch_lang_url('ca') ); ?>" rel="alternate" hreflang="ca">CA</a></li>
    <li class="list-inline-item"><a href="<?php echo esc_url( fcsd_switch_lang_url('es') ); ?>" rel="alternate" hreflang="es">ES</a></li>
    <li class="list-inline-item"><a href="<?php echo esc_url( fcsd_switch_lang_url('en') ); ?>" rel="alternate" hreflang="en">EN</a></li>
  </ul>
</nav>



      <!-- Redes sociales -->
      <nav aria-label="<?php esc_attr_e( 'Xarxes socials', 'fcsd' ); ?>">
        <ul class="list-inline mb-0 topbar__social">
          <?php
          $socials = array(
              'twitter'  => 'bi-twitter-x',
              'facebook' => 'bi-facebook',
              'instagram'=> 'bi-instagram',
              'linkedin' => 'bi-linkedin',
              'youtube'  => 'bi-youtube',
              'tiktok'   => 'bi-tiktok',
          );
          foreach ( $socials as $key => $icon_class ) {
              $url = fcsd_get_option( 'fcsd_social_' . $key );
              if ( $url ) {
                  printf(
                      '<li class="list-inline-item"><a href="%s" class="icon-link" aria-label="%s" target="_blank" rel="noopener"><i class="bi %s"></i></a></li>',
                      esc_url( $url ),
                      esc_attr( ucfirst( $key ) ),
                      esc_attr( $icon_class )
                  );
              }
          }
          ?>
        </ul>
      </nav>

      <!-- Botón de contraste -->
      <?php if ( $has_contrast ) : ?>
      <button id="contrastToggle"
              class="btn btn-outline-accent btn-sm"
              type="button"
              aria-pressed="false"
              aria-label="<?php esc_attr_e( 'Alterna contrast', 'fcsd' ); ?>">
        <?php _e( 'Contrast', 'fcsd' ); ?>
      </button>
      <?php endif; ?>

    </div>
  </div>
</div>

<!-- Middle bar -->
<div class="middlebar py-2 border-bottom">
  <div class="container-fluid d-grid align-items-center" style="grid-template-columns: 1fr auto 1fr;">

    <!-- Search -->
    <?php if ( $has_search ) : ?>
    <form class="d-none d-md-flex align-items-center gap-2"
          role="search"
          method="get"
          action="<?php echo esc_url( home_url( '/' ) ); ?>"
          aria-label="<?php esc_attr_e( 'Cerca al lloc', 'fcsd' ); ?>">
      <i class="bi bi-search opacity-75"></i>
      <input id="search" class="form-control form-control-sm search-input"
             type="search"
             name="s"
             placeholder="<?php esc_attr_e( 'Cerca', 'fcsd' ); ?>"
             value="<?php echo get_search_query(); ?>">
    </form>
    <?php endif; ?>

    <!-- Logo (generic + optional light/dark for contrast) -->
    <a class="navbar-brand mx-auto text-accent fs-5 d-flex align-items-center gap-2"
       href="<?php echo esc_url( home_url( '/' ) ); ?>"
       aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">

      <?php
      // If contrast is enabled and at least one of the contrast logos is set,
      // we output light + dark variants and let CSS/JS decide which one to show.
      if ( $has_contrast && ( $logo_light || $logo_dark ) ) :
          if ( $logo_light ) :
              ?>
              <img src="<?php echo esc_url( $logo_light ); ?>"
                   alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
                   class="fcsd-logo fcsd-logo-light"
                   style="max-height:32px;">
          <?php
          endif;
          if ( $logo_dark ) :
              ?>
              <img src="<?php echo esc_url( $logo_dark ); ?>"
                   alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
                   class="fcsd-logo fcsd-logo-dark"
                   style="max-height:32px;">
          <?php
          endif;
      else :
          // Fallback: single generic logo, or site name if not set.
          if ( $logo_generic ) :
              ?>
              <img src="<?php echo esc_url( $logo_generic ); ?>"
                   alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
                   style="max-height:32px;">
          <?php else : ?>
              <span class="fw-semibold"><?php bloginfo( 'name' ); ?></span>
          <?php
          endif;
      endif;
      ?>
    </a>

    <!-- User + cart -->
<?php
$logged_in = is_user_logged_in();
if ( function_exists( 'fcsd_get_page_url_by_slug' ) ) {
    $profile_url = fcsd_get_page_url_by_slug( 'perfil-usuari' );
    $login_url   = fcsd_get_page_url_by_slug( 'accedir' );
    $cart_url    = fcsd_get_page_url_by_slug( 'cart' );
} else {
    $profile_url = home_url( '/' );
    $login_url   = wp_login_url();
    $cart_url    = home_url( '/' );
}

// Obtener el número de productos en el carrito
$cart_count = fcsd_Shop_Cart::get_cart_count();
?>
<div class="d-flex align-items-center justify-content-end gap-3">
  <a href="<?php echo esc_url( $logged_in ? $profile_url : $login_url ); ?>"
     class="icon-link <?php echo $logged_in ? 'text-success' : ''; ?>"
     aria-label="<?php esc_attr_e( 'Compte d\'usuari', 'fcsd' ); ?>">
    <i class="bi bi-person-circle fs-5"></i>
  </a>
  <a href="<?php echo esc_url( $cart_url ); ?>"
     class="position-relative icon-link"
     aria-label="<?php esc_attr_e( 'Cistella', 'fcsd' ); ?>">
    <i class="bi bi-bag fs-5"></i>
    <?php if ( $cart_count > 0 ) : ?>
    <span class="position-absolute translate-middle badge rounded-pill bg-accent"
          style="top:-.25rem; left:80%;"><?php echo esc_html( $cart_count ); ?></span>
    <?php endif; ?>
  </a>
</div>

  </div>
</div>

<?php
// Navbar principal y mega-menús modularizada en un template part.
get_template_part( 'template-parts/navbar' );
?>

<main id="content">