<?php
/**
 * Main nav + mega menus
 *
 * Template part: template-parts/header/navbar.php
 */
?>

<!-- Main nav + mega menus -->
<nav class="navbar navbar-expand-md mainnav border-bottom border-accent-subtle" aria-label="<?php esc_attr_e( 'Navegació principal', 'fcsd' ); ?>">
  <div class="container-fluid">
    <button class="navbar-toggler ms-2" type="button"
            data-bs-toggle="collapse" data-bs-target="#mainnavContent"
            aria-controls="mainnavContent" aria-expanded="false"
            aria-label="<?php esc_attr_e( 'Commuta navegació', 'fcsd' ); ?>">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainnavContent">
      <ul class="navbar-nav mx-auto gap-md-2">

        <!-- Qui som amb mega -->
        <li class="nav-item dropdown position-static">
          <a class="nav-link dropdown-toggle"
             href="<?php echo esc_url( get_permalink( get_option( 'page_on_front' ) ) ?: home_url( '/' ) ); ?>"
             id="navQuiSom"
             data-mega="mega-quisom"
             role="button"
             aria-expanded="false">
             <?php _e( 'Qui som', 'fcsd' ); ?>
          </a>
          <?php fcsd_render_mega_quisom(); ?>
        </li>

        <!-- Serveis amb mega dinàmic -->
        <li class="nav-item dropdown position-static">
          <a class="nav-link dropdown-toggle"
             href="<?php echo esc_url( get_post_type_archive_link( 'service' ) ); ?>"
             id="navServeis"
             data-mega="mega-serveis"
             role="button"
             aria-expanded="false">
             <?php _e( 'Serveis', 'fcsd' ); ?>
          </a>
          <?php fcsd_render_mega_serveis(); ?>
        </li>

        <!-- Resta d'ítems simples -->
        <li class="nav-item">
          <a class="nav-link" href="<?php echo esc_url( get_post_type_archive_link( 'event' ) ); ?>">
            <?php _e( 'Formacions i esdeveniments', 'fcsd' ); ?>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="<?php echo esc_url( get_post_type_archive_link( 'product' ) ); ?>">
            <?php _e( 'Botiga', 'fcsd' ); ?>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="<?php echo esc_url( get_post_type_archive_link( 'transparency' ) ); ?>">
            <?php _e( 'Transparència', 'fcsd' ); ?>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="<?php echo esc_url( get_post_type_archive_link( 'news' ) ); ?>">
            <?php _e( 'Actualitat', 'fcsd' ); ?>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="<?php echo esc_url( site_url( '/contacte' ) ); ?>">
            <?php _e( 'Contacte', 'fcsd' ); ?>
          </a>
        </li>

      </ul>
    </div>
  </div>
</nav>
