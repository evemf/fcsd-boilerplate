<?php

// Construye los items del menú principal a partir de CPTs (para fallback simple)
function fcsd_main_nav_items(){
    $items = [
        ['label' => __('Qui som','fcsd'),       'url' => get_permalink(get_option('page_on_front')) ?: home_url('/')],
        ['label' => __('Serveis','fcsd'),       'url' => get_post_type_archive_link('service'),      'mega' => 'service'],
        ['label' => __('Events','fcsd'),     'url' => get_post_type_archive_link('event')],
        ['label' => __('Botiga','fcsd'),        'url' => get_post_type_archive_link('fcsd_product')],
        ['label' => __('Transparència','fcsd'), 'url' => get_post_type_archive_link('transparency')],
        [
            'label' => __('Actualitat','fcsd'),
            'url'   => function_exists('fcsd_default_slug')
                ? fcsd_get_page_url_by_slug( fcsd_default_slug('news') )
                : fcsd_get_page_url_by_slug( 'noticies' ),
        ],
        [
            'label' => __('Contacte','fcsd'),
            'url'   => function_exists('fcsd_default_slug')
                ? fcsd_get_page_url_by_slug( fcsd_default_slug('contact') )
                : fcsd_get_page_url_by_slug( 'contacte' ),
        ],
    ];
    return array_filter($items, fn($it)=>!empty($it['url']));
}

// Mega menú "Qui som" (puedes adaptarlo a páginas reales)
function fcsd_render_mega_quisom(){ ?>
  <div class="mega dropdown-menu border-0 shadow w-100" id="mega-quisom" aria-labelledby="navQuiSom">
    <div class="container-fluid py-4">
      <div class="row g-3">
        <div class="col-12 col-md-4">
          <h6 class="mega-title"><?php _e('La Fundació','fcsd'); ?></h6>
          <ul class="mega-list">
            <li><a href="<?php echo esc_url( function_exists('fcsd_get_page_url_by_slug') ? fcsd_get_page_url_by_slug('patronat') : site_url('/patronat/') ); ?>"><?php _e('Patronat','fcsd'); ?></a></li>
            <li><a href="<?php echo esc_url( function_exists('fcsd_get_page_url_by_slug') ? fcsd_get_page_url_by_slug('organigrama') : site_url('/organigrama/') ); ?>"><?php _e('Organigrama','fcsd'); ?></a></li>
            <li><a href="<?php echo esc_url( function_exists('fcsd_get_page_url_by_slug') ? fcsd_get_page_url_by_slug('historia') : site_url('/historia/') ); ?>"><?php _e('Història','fcsd'); ?></a></li>
          </ul>
        </div>
        <div class="col-12 col-md-4">
          <h6 class="mega-title"><?php _e('Recursos','fcsd'); ?></h6>
          <ul class="mega-list">
            <li><a href="<?php echo esc_url( function_exists('fcsd_default_slug') ? fcsd_get_page_url_by_slug( fcsd_default_slug('memories') ) : home_url('/') ); ?>"><?php _e('Memòries','fcsd'); ?></a></li>
            <li><a href="<?php echo esc_url( function_exists('fcsd_default_slug') ? fcsd_get_page_url_by_slug( fcsd_default_slug('press') ) : home_url('/') ); ?>"><?php _e('Premsa','fcsd'); ?></a></li>
          </ul>
        </div>
        <div class="col-12 col-md-4">
          <h6 class="mega-title"><?php _e('Participa','fcsd'); ?></h6>
          <ul class="mega-list">
            <li><a href="<?php echo esc_url( function_exists('fcsd_default_slug') ? fcsd_get_page_url_by_slug( fcsd_default_slug('volunteering') ) : home_url('/') ); ?>"><?php _e('Voluntariat','fcsd'); ?></a></li>
            <li><a href="<?php echo esc_url( function_exists('fcsd_default_slug') ? fcsd_get_page_url_by_slug( fcsd_default_slug('alliances') ) : home_url('/') ); ?>"><?php _e('Aliances','fcsd'); ?></a></li>
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
 * Retorna UNA URL d'imatge (legacy) per a un servei segons la seva "service_area".
 *
 * Nota: per a capçaleres (hero) ja fem composició automàtica si un servei té 2 àmbits.
 */
function fcsd_get_service_area_bg_image_url( $post_id = null ) {
    // Helper legacy: retorna UNA sola imatge (per components que encara no suporten
    // la composició 2-àmbits). Prioritat:
    // 1) imatge de servei del primer àmbit (config estàtica)
    // 2) fallback a la genèrica.
    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }

    if ( function_exists( 'fcsd_get_service_area_for_post' ) ) {
        $area_data = fcsd_get_service_area_for_post( $post_id );
        if ( $area_data && ! empty( $area_data['service_images'] ) && is_array( $area_data['service_images'] ) ) {
            $imgs = array_values( array_filter( $area_data['service_images'] ) );
            if ( ! empty( $imgs[0] ) ) {
                return esc_url( $imgs[0] );
            }
        }
    }

    $relative = '/assets/images/services/service-generic.png';
    $absolute = get_stylesheet_directory() . $relative;
    if ( file_exists( $absolute ) ) {
        return get_stylesheet_directory_uri() . $relative;
    }

    return '';
}

/**
 * Renderitza el "footer" d'informació imprescindible d'un servei.
 *
 * Mostra contacte, horari, adreça i (opcionalment) els logos d'apoi / suport.
 *
 * @param int  $post_id  ID del servei.
 * @param bool $compact  Si true, ús pensat per a cards de l'arxiu.
 */
function fcsd_render_service_info_footer( $post_id = 0, $compact = false ) {
    $post_id = $post_id ? (int) $post_id : (int) get_the_ID();
    if ( ! $post_id ) {
        return;
    }

    $get_meta = function_exists( 'fcsd_get_meta_i18n' )
        ? 'fcsd_get_meta_i18n'
        : null;

    $phone   = $get_meta ? $get_meta( $post_id, 'fcsd_service_contact_phone', true ) : get_post_meta( $post_id, 'fcsd_service_contact_phone', true );
    $email   = $get_meta ? $get_meta( $post_id, 'fcsd_service_contact_email', true ) : get_post_meta( $post_id, 'fcsd_service_contact_email', true );
    $hours   = $get_meta ? $get_meta( $post_id, 'fcsd_service_hours', true ) : get_post_meta( $post_id, 'fcsd_service_hours', true );
    $address = $get_meta ? $get_meta( $post_id, 'fcsd_service_address', true ) : get_post_meta( $post_id, 'fcsd_service_address', true );

    // Target ("Adreçat a") reutilitza camp existent.
    $audience = $get_meta ? $get_meta( $post_id, 'a_quien_s_adreca', true ) : get_post_meta( $post_id, 'a_quien_s_adreca', true );

    // Fallback bàsic si encara s'està usant el camp legacy "adreca_i_contacte".
    if ( ( ! $phone || ! $email || ! $address ) ) {
        $legacy = (string) ( $get_meta ? $get_meta( $post_id, 'adreca_i_contacte', true ) : get_post_meta( $post_id, 'adreca_i_contacte', true ) );
        if ( $legacy ) {
            if ( ! $email && preg_match( '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $legacy, $m ) ) {
                $email = $m[0];
            }
            if ( ! $phone && preg_match( '/(\+?\d[\d\s().-]{6,}\d)/', $legacy, $m ) ) {
                $phone = trim( $m[1] );
            }
            if ( ! $address ) {
                $address = $legacy;
            }
        }
    }

    $support_ids_raw = (string) ( $get_meta ? $get_meta( $post_id, 'fcsd_service_support_images', true ) : get_post_meta( $post_id, 'fcsd_service_support_images', true ) );
    $support_ids     = array_filter( array_map( 'absint', preg_split( '/\s*,\s*/', $support_ids_raw ) ) );

    // Conveni amb / Col·laboració amb (JSON: [{label, url}])
    $conveni_json = (string) ( $get_meta ? $get_meta( $post_id, 'fcsd_service_conveni_items', true ) : get_post_meta( $post_id, 'fcsd_service_conveni_items', true ) );
    $collab_json  = (string) ( $get_meta ? $get_meta( $post_id, 'fcsd_service_collaboracio_items', true ) : get_post_meta( $post_id, 'fcsd_service_collaboracio_items', true ) );

    $conveni_items = [];
    if ( $conveni_json ) {
        $decoded = json_decode( $conveni_json, true );
        if ( is_array( $decoded ) ) {
            foreach ( $decoded as $it ) {
                if ( empty( $it['label'] ) ) {
                    continue;
                }
                $conveni_items[] = [
                    'label' => (string) $it['label'],
                    'url'   => isset( $it['url'] ) ? (string) $it['url'] : '',
                ];
            }
        }
    }

    $collab_items = [];
    if ( $collab_json ) {
        $decoded = json_decode( $collab_json, true );
        if ( is_array( $decoded ) ) {
            foreach ( $decoded as $it ) {
                if ( empty( $it['label'] ) ) {
                    continue;
                }
                $collab_items[] = [
                    'label' => (string) $it['label'],
                    'url'   => isset( $it['url'] ) ? (string) $it['url'] : '',
                ];
            }
        }
    }

    $has_any = ( $phone || $email || $hours || $address || $audience || ! empty( $support_ids ) || ! empty( $conveni_items ) || ! empty( $collab_items ) );
    if ( ! $has_any ) {
        return;
    }

    $phone_clean = $phone ? preg_replace( '/\s+/', '', (string) $phone ) : '';

    $classes = 'service-info-footer';
    if ( $compact ) {
        $classes .= ' service-info-footer--compact';
    }

    ?>
    <section class="<?php echo esc_attr( $classes ); ?>" aria-label="<?php echo esc_attr__( 'Informació imprescindible', 'fcsd' ); ?>">
        <div class="service-info-footer__inner">
            <h3 class="service-info-footer__title"><?php echo esc_html__( 'Informació imprescindible', 'fcsd' ); ?></h3>

            <div class="service-info-footer__grid">
                <div class="service-info-footer__col">
                    <h4 class="service-info-footer__heading"><?php echo esc_html__( 'Contacte', 'fcsd' ); ?></h4>
                    <div class="service-info-footer__text">
                        <?php if ( $phone ) : ?>
                            <p class="mb-1"><a href="tel:<?php echo esc_attr( $phone_clean ); ?>"><?php echo esc_html( $phone ); ?></a></p>
                        <?php endif; ?>
                        <?php if ( $email ) : ?>
                            <p class="mb-1"><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></p>
                        <?php endif; ?>
                        <?php if ( $address ) : ?>
                            <div class="service-info-footer__address"><?php echo wp_kses_post( wpautop( $address ) ); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="service-info-footer__col">
                    <h4 class="service-info-footer__heading"><?php echo esc_html__( 'Horari d’atenció al públic', 'fcsd' ); ?></h4>
                    <div class="service-info-footer__text">
                        <?php if ( $hours ) : ?>
                            <?php echo wp_kses_post( wpautop( $hours ) ); ?>
                        <?php else : ?>
                            <p class="mb-0"><?php echo esc_html__( 'Visites concertades', 'fcsd' ); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="service-info-footer__col">
                    <h4 class="service-info-footer__heading"><?php echo esc_html__( 'Adreçat a', 'fcsd' ); ?></h4>
                    <div class="service-info-footer__text">
                        <?php if ( $audience ) : ?>
                            <?php echo wp_kses_post( wpautop( $audience ) ); ?>
                        <?php else : ?>
                            <p class="mb-0"><?php echo esc_html__( '—', 'fcsd' ); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ( ! empty( $support_ids ) || ! empty( $conveni_items ) || ! empty( $collab_items ) ) : ?>
                <div class="service-info-footer__partners" role="group" aria-label="<?php echo esc_attr__( 'Aliances i suport', 'fcsd' ); ?>">

                    <?php if ( ! empty( $support_ids ) ) : ?>
                        <div class="service-info-footer__partner-col service-info-footer__partner-col--support">
                            <span class="service-info-footer__support-label"><?php echo esc_html__( 'Amb el suport de:', 'fcsd' ); ?></span>
                            <div class="service-info-footer__support-logos" role="list">
                                <?php foreach ( $support_ids as $id ) :
                                    $img = wp_get_attachment_image( $id, 'medium', false, [ 'class' => 'service-info-footer__logo', 'role' => 'listitem' ] );
                                    if ( $img ) {
                                        echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    }
                                endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $conveni_items ) ) : ?>
                        <div class="service-info-footer__partner-col">
                            <span class="service-info-footer__support-label"><?php echo esc_html__( 'Conveni amb:', 'fcsd' ); ?></span>
                            <ul class="service-info-footer__partner-list">
                                <?php foreach ( $conveni_items as $it ) :
                                    $label = trim( (string) $it['label'] );
                                    if ( '' === $label ) {
                                        continue;
                                    }
                                    $url = trim( (string) $it['url'] );
                                    echo '<li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    if ( $url ) {
                                        echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $label ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    } else {
                                        echo esc_html( $label );
                                    }
                                    echo '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $collab_items ) ) : ?>
                        <div class="service-info-footer__partner-col">
                            <span class="service-info-footer__support-label"><?php echo esc_html__( 'Col·laboració amb:', 'fcsd' ); ?></span>
                            <ul class="service-info-footer__partner-list">
                                <?php foreach ( $collab_items as $it ) :
                                    $label = trim( (string) $it['label'] );
                                    if ( '' === $label ) {
                                        continue;
                                    }
                                    $url = trim( (string) $it['url'] );
                                    echo '<li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    if ( $url ) {
                                        echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $label ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    } else {
                                        echo esc_html( $label );
                                    }
                                    echo '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
}
