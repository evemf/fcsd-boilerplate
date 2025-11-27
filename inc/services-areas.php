<?php
/**
 * Configuració dels àmbits de servei (service_area)
 * i associació amb capçalera (classe + imatge + color).
 *
 * - Garanteix que existeixen els termes a la taxonomia service_area.
 * - Llegeix colors i imatges del Customizer (amb valors per defecte).
 * - Proporciona helpers per obtenir la info d'àmbit d'un servei.
 */

/**
 * Config estàtica base dels àmbits (defaults).
 *
 * Després es fusiona amb els valors configurats al Customizer.
 */
function fcsd_get_service_areas_defaults() {
    return [
        'generic' => [
            'name'        => __( 'Genèric', 'fcsd' ),
            'description' => __( 'Serveis d’àmbit genèric o transversal.', 'fcsd' ),
            'hero_class'  => 'service-hero--generic',
            'color'       => '#6CB2DD', // Institucional
            'image_relative' => '/assets/images/services/service-generic.png',
        ],
        'vida-independent' => [
            'name'        => __( 'Vida independent', 'fcsd' ),
            'description' => __( 'Serveis per a la vida autònoma i suport al dia a dia.', 'fcsd' ),
            'hero_class'  => 'service-hero--vida-independent',
            'color'       => '#FA4C9D',
            'image_relative' => '/assets/images/services/service-vida-independent.png',
        ],
        'treball' => [
            'name'        => __( 'Treball', 'fcsd' ),
            'description' => __( 'Serveis relacionats amb l’ocupació i el món laboral.', 'fcsd' ),
            'hero_class'  => 'service-hero--treball',
            'color'       => '#F87D4A',
            'image_relative' => '/assets/images/services/service-treball.png',
        ],
        'formacio' => [
            'name'        => __( 'Formació', 'fcsd' ),
            'description' => __( 'Serveis de formació, capacitació i aprenentatge.', 'fcsd' ),
            'hero_class'  => 'service-hero--formacio',
            'color'       => '#7657AC',
            'image_relative' => '/assets/images/services/service-formacio.png',
        ],
        'oci' => [
            'name'        => __( 'Oci', 'fcsd' ),
            'description' => __( 'Serveis i activitats de lleure i oci.', 'fcsd' ),
            'hero_class'  => 'service-hero--oci',
            'color'       => '#C4D200',
            'image_relative' => '/assets/images/services/service-oci.png',
        ],
        'salut' => [
            'name'        => __( 'Salut', 'fcsd' ),
            'description' => __( 'Serveis relacionats amb l’àmbit de la salut.', 'fcsd' ),
            'hero_class'  => 'service-hero--salut',
            'color'       => '#E80000',
            'image_relative' => '/assets/images/services/service-salut.png',
        ],
        'merchandising' => [
            'name'        => __( 'Merchandising', 'fcsd' ),
            'description' => __( 'Productes i serveis de marxandatge solidari.', 'fcsd' ),
            'hero_class'  => 'service-hero--merchandising',
            'color'       => '#A8A7A7',
            'image_relative' => '/assets/images/services/service-merchandising.png',
        ],
        'exit-21' => [
            'name'        => __( 'EXIT 21', 'fcsd' ),
            'description' => __( 'Projectes i serveis vinculats a EXIT21.', 'fcsd' ),
            'hero_class'  => 'service-hero--exit-21',
            'color'       => '#FFC100',
            'image_relative' => '/assets/images/services/service-exit-21.png',
        ],
        'assemblea-dh' => [
            'name'        => __( 'Assemblea DH', 'fcsd' ),
            'description' => __( 'Serveis i projectes vinculats a l’Assemblea de Drets Humans.', 'fcsd' ),
            'hero_class'  => 'service-hero--assemblea-dh',
            'color'       => '#FFC100',
            'image_relative' => '/assets/images/services/service-assemblea-dh.png',
        ],
        'voluntariat' => [
            'name'        => __( 'Voluntariat', 'fcsd' ),
            'description' => __( 'Serveis i programes de voluntariat.', 'fcsd' ),
            'hero_class'  => 'service-hero--voluntariat',
            'color'       => '#00A44B',
            'image_relative' => '/assets/images/services/service-voluntariat.png',
        ],
    ];
}

/**
 * Config definitiva: defaults + Customizer.
 */
function fcsd_get_service_areas_config() {
    $defaults = fcsd_get_service_areas_defaults();
    $config   = [];

    foreach ( $defaults as $slug => $data ) {
        // Color desde el Customizer
        $color_mod = get_theme_mod( "fcsd_service_area_{$slug}_color", $data['color'] );

        // Imagen desde el Customizer
        $image_mod = get_theme_mod( "fcsd_service_area_{$slug}_image", '' );

        $data['color'] = $color_mod ?: $data['color'];

        if ( $image_mod ) {
            $data['image_url'] = $image_mod;
        } else {
            $data['image_url'] = get_template_directory_uri() . $data['image_relative'];
        }

        $config[ $slug ] = $data;
    }

    return $config;
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

    $terms = get_the_terms( $post_id, 'service_area' );
    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return null;
    }

    $term    = $terms[0];
    $slug    = $term->slug;
    $config  = fcsd_get_service_areas_config();
    $default = fcsd_get_service_areas_defaults();

    $fallback_color = $default['generic']['color'] ?? '#e7a15a';

    if ( ! isset( $config[ $slug ] ) ) {
        $color = $fallback_color;

        return [
            'slug'        => $slug,
            'name'        => $term->name,
            'description' => $term->description,
            'hero_class'  => 'service-hero--' . sanitize_html_class( $slug ),
            'color'       => $color,
            'color_soft'  => fcsd_hex_to_rgba( $color, 0.10 ),
            'hero_image'  => get_template_directory_uri() . '/assets/images/services/service-' . $slug . '.png',
            'term'        => $term,
        ];
    }

    $data              = $config[ $slug ];
    $data['slug']      = $slug;
    $data['term']      = $term;
    $data['hero_image'] = $data['image_url'];

    $color = ! empty( $data['color'] ) ? $data['color'] : $fallback_color;
    $data['color']      = $color;
    $data['color_soft'] = fcsd_hex_to_rgba( $color, 0.10 );

    if ( empty( $data['name'] ) ) {
        $data['name'] = $term->name;
    }
    if ( empty( $data['description'] ) ) {
        $data['description'] = $term->description;
    }

    return $data;
}
