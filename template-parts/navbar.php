<?php
/**
 * Main nav (WP Menus) + mega menus
 *
 * - El menú principal se gestiona desde Apariencia > Menús.
 * - El theme añade mega-menús automáticamente a los items que correspondan.
 */
defined('ABSPATH') || exit;

// Fallback mínimo (si no hay menú asignado)
function fcsd_fallback_primary_menu() {
    $items = function_exists('fcsd_main_nav_items') ? fcsd_main_nav_items() : [];
    if ( empty($items) ) return;

    foreach ($items as $it) {
        echo '<li class="nav-item"><a class="nav-link" href="' . esc_url($it['url']) . '">' . esc_html($it['label']) . '</a></li>';
    }
}
?>

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
        <?php
        wp_nav_menu([
            'theme_location' => 'primary',
            'container'      => false,
            'items_wrap'     => '%3$s',
            'depth'          => 3,
            'fallback_cb'    => 'fcsd_fallback_primary_menu',
            'walker'         => class_exists('FCSD_Nav_Walker_Mega') ? new FCSD_Nav_Walker_Mega() : null,
        ]);
        ?>
      </ul>
    </div>
  </div>
</nav>
