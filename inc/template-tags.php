<?php

// Construye los items del menú principal a partir de CPTs (para fallback simple)
function fcsd_main_nav_items(){
    $items = [
        ['label' => __('Qui som','fcsd'),       'url' => get_permalink(get_option('page_on_front')) ?: home_url('/')],
        ['label' => __('Serveis','fcsd'),       'url' => get_post_type_archive_link('service'),      'mega' => 'service'],
        ['label' => __('Events','fcsd'),     'url' => get_post_type_archive_link('event')],
        ['label' => __('Botiga','fcsd'),        'url' => get_post_type_archive_link('product')],
        ['label' => __('Transparència','fcsd'), 'url' => get_post_type_archive_link('transparency')],
        ['label' => __('Actualitat','fcsd'),    'url' => get_post_type_archive_link('news')],
        ['label' => __('Contacte','fcsd'),      'url' => site_url('/contacte')],
    ];
    return array_filter($items, fn($it)=>!empty($it['url']));
}

// Mega menú "Qui som" (puedes adaptarlo a páginas reales)
function fcsd_render_mega_quisom(){ ?>
  <div class="mega dropdown-menu border-0 shadow w-100" id="mega-quisom" aria-labelledby="navQuiSom">
    <div class="container-fluid py-4">
      <div class="row g-4">
        <div class="col-12 col-md-3">
          <h6 class="mega-title"><?php _e('La Fundació','fcsd'); ?></h6>
          <ul class="mega-list">
            <li><a href="#"><?php _e('Missió i valors','fcsd'); ?></a></li>
            <li><a href="#"><?php _e('Equip','fcsd'); ?></a></li>
            <li><a href="#"><?php _e('Història','fcsd'); ?></a></li>
          </ul>
        </div>
        <div class="col-12 col-md-3">
          <h6 class="mega-title"><?php _e('Recursos','fcsd'); ?></h6>
          <ul class="mega-list">
            <li><a href="#"><?php _e('Memòries','fcsd'); ?></a></li>
            <li><a href="#"><?php _e('Premsa','fcsd'); ?></a></li>
          </ul>
        </div>
        <div class="col-12 col-md-3">
          <h6 class="mega-title"><?php _e('Valors','fcsd'); ?></h6>
          <ul class="mega-list">
            <li><a href="#"><?php _e('Inclusió','fcsd'); ?></a></li>
            <li><a href="#"><?php _e('Innovació','fcsd'); ?></a></li>
            <li><a href="#"><?php _e('Comunitat','fcsd'); ?></a></li>
          </ul>
        </div>
        <div class="col-12 col-md-3">
          <h6 class="mega-title"><?php _e('Participa','fcsd'); ?></h6>
          <ul class="mega-list">
            <li><a href="#"><?php _e('Voluntariat','fcsd'); ?></a></li>
            <li><a href="#"><?php _e('Aliances','fcsd'); ?></a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
<?php }

// Mega menú "Serveis" dinámico desde CPT + taxonomia
function fcsd_render_mega_serveis(){ ?>
  <div class="mega dropdown-menu border-0 shadow w-100" id="mega-serveis" aria-labelledby="navServeis">
    <div class="container-fluid py-4">
      <div class="row g-4">

        <div class="col-12 col-md-3">
          <h6 class="mega-title"><?php _e('Àrees','fcsd'); ?></h6>
          <ul class="mega-list">
            <?php
            $areas = get_terms([
              'taxonomy'   => 'service_area',
              'hide_empty' => false,
              'number'     => 6,
            ]);
            if (!is_wp_error($areas)) {
              foreach ($areas as $area) {
                echo '<li><a href="'.esc_url(get_term_link($area)).'">'.esc_html($area->name).'</a></li>';
              }
            }
            ?>
          </ul>
        </div>

        <div class="col-12 col-md-3">
          <h6 class="mega-title"><?php _e('Serveis destacats','fcsd'); ?></h6>
          <ul class="mega-list">
            <?php
            $q = new WP_Query([
              'post_type'      => 'service',
              'posts_per_page' => 6,
              'orderby'        => 'date',
              'order'          => 'DESC',
            ]);
            if ($q->have_posts()){
              while($q->have_posts()){ $q->the_post();
                echo '<li><a href="'.esc_url(get_permalink()).'">'.esc_html(get_the_title()).'</a></li>';
              }
              wp_reset_postdata();
            }
            ?>
          </ul>
        </div>

        <div class="col-12 col-md-3">
          <h6 class="mega-title"><?php _e('Altres serveis','fcsd'); ?></h6>
          <ul class="mega-list">
            <?php
            $q = new WP_Query([
              'post_type'      => 'service',
              'posts_per_page' => 6,
              'offset'         => 6,
            ]);
            if ($q->have_posts()){
              while($q->have_posts()){ $q->the_post();
                echo '<li><a href="'.esc_url(get_permalink()).'">'.esc_html(get_the_title()).'</a></li>';
              }
              wp_reset_postdata();
            }
            ?>
          </ul>
        </div>

        <div class="col-12 col-md-3">
          <div class="mega-cta p-4 rounded">
            <p class="mb-2"><?php _e('Descobreix què estem fent ara mateix.','fcsd'); ?></p>
            <a class="btn btn-accent btn-sm" href="<?php echo esc_url( get_post_type_archive_link('service') ); ?>">
              <?php _e('Explora tots els serveis','fcsd'); ?>
            </a>
          </div>
        </div>

      </div>
    </div>
  </div>
<?php }

/**
 * Retorna la URL de la imatge de fons per a un servei segons la seva "service_area".
 *
 * Cerca primer una imatge personalitzada (ex. via Customizer: service_area_<slug>_image)
 * i, si no n'hi ha, fa fallback a /assets/images/services/ambit-<slug>.png
 * o, si tampoc existeix, a ambit-generic.png.
 */
function fcsd_get_service_area_bg_image_url( $post_id = null ) {
    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }

    $bg_url = '';

    // 1) Obtenim el terme principal de la taxonomia "service_area"
    $terms = get_the_terms( $post_id, 'service_area' );

    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        // Agafem el primer terme
        $primary_area = $terms[0];
        $slug         = $primary_area->slug;

        // 2) Opcional: imatge personalitzada des del Customizer (service_area_<slug>_image)
        $custom = get_theme_mod( 'service_area_' . $slug . '_image' );
        if ( $custom ) {
          $bg_url = esc_url( $custom );
        }

        // 3) Fallback a la imatge per defecte del tema: /assets/images/services/ambit-<slug>.png
        if ( ! $bg_url ) {
            $relative = '/assets/images/services/service-' . $slug . '.png';
            $absolute = get_stylesheet_directory() . $relative;

            if ( file_exists( $absolute ) ) {
                $bg_url = get_stylesheet_directory_uri() . $relative;
            }
        }
    }

    // 4) Si no hi ha cap imatge trobada, fem servir la genèrica
    if ( ! $bg_url ) {
        $relative = '/assets/images/ambits/ambit-generic.png';
        $absolute = get_stylesheet_directory() . $relative;

        if ( file_exists( $absolute ) ) {
            $bg_url = get_stylesheet_directory_uri() . $relative;
        }
    }

    return $bg_url;
}
