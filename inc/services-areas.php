<?php
/**
 * Configuració dels àmbits de servei (service_area)
 * i associació amb capçalera (classe + imatge + color).
 *
 * - Garanteix que existeixen els termes a la taxonomia service_area.
 * - Config estàtica (colors + imatges) definida al codi del tema.
 * - Proporciona helpers per obtenir la info d'àmbit d'un servei.
 */

/**
 * Config estàtica base dels àmbits (defaults).
 *
 * Després es fusiona amb els valors configurats al Customizer.
 */
function fcsd_get_service_areas_defaults() {
    // IMPORTANT:
    // - Aquests valors són “codi-font” del tema (fixes): colors + imatges de capçalera.
    // - Les imatges de CAPÇALERA (hero) viuen a /assets/images/ambits/ambit-<slug>.png
    // - Les imatges “de servei” (cards/altres) poden viure a /assets/images/services/service-<slug>.png
    return [
        'generic' => [
            'name'            => __( 'Institucional', 'fcsd' ),
            'description'     => __( 'Serveis d’àmbit institucional o transversal.', 'fcsd' ),
            'hero_class'      => 'service-hero--generic',
            'color'           => '#1D80C4',
            'hero_image_rel'  => '/assets/images/ambits/ambit-generic.png',
            'order'           => 10,
        ],
        'vida' => [
            'name'            => __( 'Vida independent', 'fcsd' ),
            'description'     => __( 'Serveis per a la vida autònoma i suport al dia a dia.', 'fcsd' ),
            'hero_class'      => 'service-hero--vida-independent',
            'color'           => '#E5007E',
            'hero_image_rel'  => '/assets/images/ambits/ambit-vida.png',
            'order'           => 20,
        ],
        'treball' => [
            'name'            => __( 'Treball', 'fcsd' ),
            'description'     => __( 'Serveis relacionats amb l’ocupació i el món laboral.', 'fcsd' ),
            'hero_class'      => 'service-hero--treball',
            'color'           => '#E45E1A',
            'hero_image_rel'  => '/assets/images/ambits/ambit-treball.png',
            'order'           => 30,
        ],
        'formacio' => [
            'name'            => __( 'Formació', 'fcsd' ),
            'description'     => __( 'Serveis de formació, capacitació i aprenentatge.', 'fcsd' ),
            'hero_class'      => 'service-hero--formacio',
            'color'           => '#7D68AC',
            'hero_image_rel'  => '/assets/images/ambits/ambit-formacio.png',
            'order'           => 40,
        ],
        'oci' => [
            'name'            => __( 'Oci', 'fcsd' ),
            'description'     => __( 'Serveis i activitats de lleure i oci.', 'fcsd' ),
            'hero_class'      => 'service-hero--oci',
            'color'           => '#C6D134',
            'hero_image_rel'  => '/assets/images/ambits/ambit-oci.png',
            'order'           => 50,
        ],
        'salut' => [
            'name'            => __( 'Salut', 'fcsd' ),
            'description'     => __( 'Serveis relacionats amb l’àmbit de la salut.', 'fcsd' ),
            'hero_class'      => 'service-hero--salut',
            'color'           => '#D51116',
            'hero_image_rel'  => '/assets/images/ambits/ambit-salut.png',
            'order'           => 60,
        ],
        'merchandising' => [
            'name'            => __( 'Merchandising', 'fcsd' ),
            'description'     => __( 'Productes i serveis de marxandatge solidari.', 'fcsd' ),
            'hero_class'      => 'service-hero--merchandising',
            'color'           => '#A8A7A7',
            'hero_image_rel'  => '/assets/images/ambits/ambit-merchandising.png',
            'order'           => 70,
        ],
        'exit' => [
            'name'            => __( 'Èxit 21', 'fcsd' ),
            'description'     => __( 'Projectes i serveis vinculats a Èxit 21.', 'fcsd' ),
            'hero_class'      => 'service-hero--exit-21',
            'color'           => '#FDC512',
            'hero_image_rel'  => '/assets/images/ambits/ambit-exit.png',
            'order'           => 80,
        ],
        'assemblea' => [
            'name'            => __( 'Assemblea DH', 'fcsd' ),
            'description'     => __( 'Serveis i projectes vinculats a l’Assemblea de Drets Humans.', 'fcsd' ),
            'hero_class'      => 'service-hero--assemblea-dh',
            'color'           => '#FDC512',
            'hero_image_rel'  => '/assets/images/ambits/ambit-assemblea.png',
            'order'           => 90,
        ],
        'voluntariat' => [
            'name'            => __( 'Voluntariat', 'fcsd' ),
            'description'     => __( 'Serveis i programes de voluntariat.', 'fcsd' ),
            'hero_class'      => 'service-hero--voluntariat',
            'color'           => '#2CA055',
            'hero_image_rel'  => '/assets/images/ambits/ambit-voluntariat.png',
            'order'           => 100,
        ],
    ];
}

/**
 * Config definitiva: defaults + Customizer.
 */
function fcsd_get_service_areas_config() {
    // Alias per claredat: ara és totalment estàtic.
    // Normalitzem només les URLs finals a partir de rutes relatives.
    $defaults = fcsd_get_service_areas_defaults();
    $config   = [];

    foreach ( $defaults as $slug => $data ) {
        $data['hero_image_url']    = ! empty( $data['hero_image_rel'] )
            ? get_template_directory_uri() . $data['hero_image_rel']
            : '';

        $config[ $slug ] = $data;
    }

    return $config;
}

/**
 * Retorna els àmbits (terms) associats a un servei, limitat a màxim 2.
 * Si hi ha més de 2 termes assignats, ens quedem amb els dos primers per ordre alfabètic de slug.
 */
function fcsd_get_service_area_terms_for_post( $post_id = null ) {
    if ( null === $post_id ) {
        $post_id = get_the_ID();
    }
    if ( ! $post_id ) {
        return [];
    }

    $terms = get_the_terms( $post_id, 'service_area' );
    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return [];
    }

    usort( $terms, static function( $a, $b ) {
        return strcmp( (string) $a->slug, (string) $b->slug );
    } );

    return array_slice( $terms, 0, 2 );
}

/**
 * Helper: hex a rgba() amb alfa.
 */
function fcsd_hex_to_rgba( $hex, $alpha = 1.0 ) {
    $hex = trim( $hex );
    if ( 0 === strpos( $hex, '#' ) ) {
        $hex = substr( $hex, 1 );
    }

    if ( strlen( $hex ) === 3 ) {
        $r = hexdec( str_repeat( substr( $hex, 0, 1 ), 2 ) );
        $g = hexdec( str_repeat( substr( $hex, 1, 1 ), 2 ) );
        $b = hexdec( str_repeat( substr( $hex, 2, 1 ), 2 ) );
    } else {
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );
    }

    $alpha = max( 0, min( 1, (float) $alpha ) );

    return sprintf( 'rgba(%d,%d,%d,%.3f)', $r, $g, $b, $alpha );
}

/**
 * Crea els termes de service_area si no existeixen.
 */
function fcsd_ensure_service_areas_terms() {

    if ( ! taxonomy_exists( 'service_area' ) ) {
        return;
    }

    if ( get_option( 'fcsd_service_areas_created' ) ) {
        return;
    }

    $config = fcsd_get_service_areas_defaults();

    foreach ( $config as $slug => $data ) {
        if ( term_exists( $slug, 'service_area' ) || term_exists( $data['name'], 'service_area' ) ) {
            continue;
        }

        wp_insert_term(
            $data['name'],
            'service_area',
            [
                'slug'        => $slug,
                'description' => isset( $data['description'] ) ? $data['description'] : '',
            ]
        );
    }

    update_option( 'fcsd_service_areas_created', 1 );
}
add_action( 'init', 'fcsd_ensure_service_areas_terms', 20 );

/**
 * Retorna la configuració d’àmbit associada a un servei.
 */
function fcsd_get_service_area_for_post( $post_id = null ) {
    if ( null === $post_id ) {
        $post_id = get_the_ID();
    }

    if ( ! $post_id ) {
        return null;
    }

    $terms = fcsd_get_service_area_terms_for_post( $post_id );
    if ( empty( $terms ) ) {
        return null;
    }

    $config   = fcsd_get_service_areas_config();
    $defaults = fcsd_get_service_areas_defaults();
    $fallback = $defaults['generic'] ?? [ 'color' => '#e7a15a', 'hero_image_url' => '' ];

    // Normalitzem dades per cada terme.
    $areas = [];
    foreach ( $terms as $term ) {
        $slug = (string) $term->slug;
        $data = $config[ $slug ] ?? null;

        if ( ! $data ) {
            $areas[] = [
                'slug'           => $slug,
                'name'           => $term->name,
                'description'    => $term->description,
                'hero_class'     => 'service-hero--' . sanitize_html_class( $slug ),
                'color'          => $fallback['color'],
                'hero_image_url' => get_template_directory_uri() . '/assets/images/ambits/ambit-' . $slug . '.png',
                'term'           => $term,
            ];
            continue;
        }

        $areas[] = [
            'slug'              => $slug,
            'name'              => $data['name'] ?? $term->name,
            'description'       => $data['description'] ?? $term->description,
            'hero_class'        => $data['hero_class'] ?? ( 'service-hero--' . sanitize_html_class( $slug ) ),
            'color'             => $data['color'] ?? $fallback['color'],
            'hero_image_url'    => $data['hero_image_url'] ?? '',
            'term'              => $term,
        ];
    }

    // Cas 1 àmbit.
    if ( count( $areas ) === 1 ) {
        $a = $areas[0];
        $color = $a['color'] ?? $fallback['color'];
        return [
            'slug'         => $a['slug'],
            'name'         => $a['name'],
            'description'  => $a['description'],
            'hero_class'   => $a['hero_class'],
            'color'        => $color,
            'color_soft'   => fcsd_hex_to_rgba( $color, 0.10 ),
            'hero_images'  => array_filter( [ $a['hero_image_url'] ] ),
            'terms'        => [ $a['term'] ],
        ];
    }

    // Cas 2 àmbits: composició lògica sense necessitar una imatge “combinada” al filesystem.
    $a1 = $areas[0];
    $a2 = $areas[1];

    $c1 = $a1['color'] ?? $fallback['color'];
    $c2 = $a2['color'] ?? $fallback['color'];

    return [
        'slug'            => $a1['slug'] . '+' . $a2['slug'],
        'name'            => trim( $a1['name'] . ' · ' . $a2['name'] ),
        'description'     => '',
        'hero_class'      => trim( $a1['hero_class'] . ' ' . $a2['hero_class'] ),
        'color'           => $c1,
        'color_secondary' => $c2,
        'color_soft'      => fcsd_hex_to_rgba( $c1, 0.10 ),
        'color_soft_secondary' => fcsd_hex_to_rgba( $c2, 0.10 ),
        'hero_images'     => array_values( array_filter( [ $a1['hero_image_url'], $a2['hero_image_url'] ] ) ),
        'terms'           => [ $a1['term'], $a2['term'] ],
    ];
}
