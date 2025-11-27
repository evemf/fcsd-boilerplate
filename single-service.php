<?php
/**
 * Single template per al CPT "service"
 */

get_header();
?>

<?php
while ( have_posts() ) :
    the_post();

    // Info d'àmbit (service_area)
    $area_data  = function_exists( 'fcsd_get_service_area_for_post' )
        ? fcsd_get_service_area_for_post()
        : null;

    $area_label      = $area_data && ! empty( $area_data['name'] ) ? $area_data['name'] : '';
    $hero_class      = 'service-hero';
    $hero_style      = '';
    $service_color   = $area_data['color']      ?? '#e7a15a';
    $service_soft    = $area_data['color_soft'] ?? 'rgba(231,161,90,0.10)';

    if ( $area_data && ! empty( $area_data['hero_class'] ) ) {
        $hero_class .= ' ' . $area_data['hero_class'];
    }

    if ( $area_data && ! empty( $area_data['hero_image'] ) ) {
        $hero_style = 'background-image:url(' . esc_url( $area_data['hero_image'] ) . ');';
    }
    ?>

    <section class="<?php echo esc_attr( $hero_class ); ?>"<?php echo $hero_style ? ' style="' . esc_attr( $hero_style ) . '"' : ''; ?>>
        <div class="service-hero__overlay">
            <?php if ( $area_label ) : ?>
                <div class="service-hero__kicker">
                    <?php echo esc_html( $area_label ); ?>
                </div>
            <?php endif; ?>

            <h1 class="service-hero__title" itemprop="name">
                <?php the_title(); ?>
            </h1>
        </div>
    </section>

    <div class="container content py-5">
        <article <?php post_class( 'service-entry' ); ?> itemscope itemtype="https://schema.org/Service">

            <?php if ( get_the_content() ) : ?>
                <section class="mb-5" aria-labelledby="presentacio">
                    <h2 id="presentacio" class="h5 text-muted">
                        <?php echo esc_html__( 'Presentació', 'fcsd' ); ?>
                    </h2>
                    <div class="bg-light p-3 rounded border" itemprop="description">
                        <?php the_content(); ?>
                    </div>
                </section>
            <?php endif; ?>

            <section
                class="ficha-tecnica service-ficha"
                aria-labelledby="ficha-tecnica"
                style="--service-area-color: <?php echo esc_attr( $service_color ); ?>; --service-area-color-soft: <?php echo esc_attr( $service_soft ); ?>;"
            >
                <h2 id="ficha-tecnica" class="h5 text-muted mb-3">
                    <?php echo esc_html__( 'Fitxa tècnica del servei', 'fcsd' ); ?>
                </h2>

                <div class="accordion service-accordion" id="serviceFichaAccordion">
                    <?php
                    $fields = [
                        'nom_servei'           => __( 'Nom del servei', 'fcsd' ),
                        'definicio_breu'       => __( 'Definició breu', 'fcsd' ),
                        'marc_normatiu'        => __( 'Marc normatiu i referents', 'fcsd' ),
                        'historia_i_missio'    => __( 'Història i missió', 'fcsd' ),
                        'a_quien_s_adreca'     => __( 'A qui s’adreça', 'fcsd' ),
                        'que_oferim'           => __( 'Què oferim', 'fcsd' ),
                        'programes_inclosos'   => __( 'Programes principals', 'fcsd' ),
                        'altres_activitats'    => __( 'Altres activitats relacionades', 'fcsd' ),
                        'frequencia_modalitat' => __( 'Durada / freqüència / modalitats', 'fcsd' ),
                        'sponsors'             => __( 'Cost o finançament', 'fcsd' ),
                        'col_laboradors'       => __( 'Reconeixements o aliances', 'fcsd' ),
                        'compliment_iso'       => __( 'Compliment amb normativa ISO', 'fcsd' ),
                        'prestacio_social'     => __( 'Prestació social / cartera de serveis', 'fcsd' ),
                        'registre_generalitat' => __( 'Registre Generalitat', 'fcsd' ),
                        'equip_professional'   => __( 'Equip professional', 'fcsd' ),
                        'adreca_i_contacte'    => __( 'Adreça i contacte', 'fcsd' ),
                        'videos'               => __( 'Vídeos o testimonis', 'fcsd' ),
                        'frase_explicativa'    => __( 'Frase explicativa en primera persona', 'fcsd' ),
                        'any_creacio'          => __( 'Any de creació', 'fcsd' ),
                        'canvis_nom'           => __( 'Canvis de nom', 'fcsd' ),
                        'destacats'            => __( 'Destacats', 'fcsd' ),
                        'frase_crida'          => __( 'Frase curta cridanera', 'fcsd' ),
                        'punts_clau'           => __( '3 punts clau diferencials', 'fcsd' ),
                        'que_fem_per_tu'       => __( 'Què fem per tu', 'fcsd' ),
                        'que_fem_entorn'       => __( 'Què fem per l\'entorn', 'fcsd' ),
                    ];

                    $i = 0;

                    foreach ( $fields as $key => $label ) {
                        $value = get_post_meta( get_the_ID(), $key, true );

                        if ( empty( $value ) ) {
                            continue;
                        }

                        $i++;
                        $heading_id = 'service-field-heading-' . $i;
                        $collapse_id = 'service-field-collapse-' . $i;
                        ?>
                        <div class="accordion-item service-accordion__item">
                            <h3 class="accordion-header" id="<?php echo esc_attr( $heading_id ); ?>">
                                <button
                                    class="accordion-button collapsed service-accordion__button"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#<?php echo esc_attr( $collapse_id ); ?>"
                                    aria-expanded="false"
                                    aria-controls="<?php echo esc_attr( $collapse_id ); ?>"
                                >
                                    <?php echo esc_html( $label ); ?>
                                </button>
                            </h3>
                            <div
                                id="<?php echo esc_attr( $collapse_id ); ?>"
                                class="accordion-collapse collapse"
                                aria-labelledby="<?php echo esc_attr( $heading_id ); ?>"
                                data-bs-parent="#serviceFichaAccordion"
                            >
                                <div class="accordion-body service-accordion__body">
                                    <?php echo wp_kses_post( wpautop( $value ) ); ?>
                                </div>
                            </div>
                        </div>
                        <?php
                    }

                    if ( 0 === $i ) :
                        ?>
                        <p class="text-muted mb-0"><?php esc_html_e( 'No hi ha informació tècnica disponible per aquest servei.', 'fcsd' ); ?></p>
                    <?php endif; ?>
                </div>
            </section>

            <?php
            // Documentació adjunta
            $document_url = get_post_meta( get_the_ID(), 'documentacio_pdf', true );

            if ( ! empty( $document_url ) ) :
                $filename = basename( $document_url );
                ?>
                <div class="mt-4">
                    <a href="<?php echo esc_url( $document_url ); ?>"
                       class="btn btn-outline-secondary"
                       download>
                        <i class="fa-solid fa-file-pdf me-2" aria-hidden="true"></i>
                        <?php
                        printf(
                            /* translators: %s: file name */
                            esc_html__( 'Descarrega el document (%s)', 'fcsd' ),
                            esc_html( $filename )
                        );
                        ?>
                    </a>
                </div>
            <?php endif; ?>

        </article>
    </div><!-- /.container -->

<?php
endwhile;

get_footer();
