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

    // Frase curta cridanera (per la capçalera del servei)
    $service_tagline = get_post_meta( get_the_ID(), 'frase_crida', true );

    if ( $area_data && ! empty( $area_data['hero_class'] ) ) {
        $hero_class .= ' ' . $area_data['hero_class'];
    }

    // Fons de capçalera (hero):
    // - 1 àmbit: 1 imatge.
    // - 2 àmbits: composició automàtica (2 imatges a 50/50) sense necessitat d'una imatge “combinada”.
    if ( $area_data && ! empty( $area_data['hero_images'] ) && is_array( $area_data['hero_images'] ) ) {
        $imgs = array_values( array_filter( $area_data['hero_images'] ) );
        if ( count( $imgs ) === 1 ) {
            $hero_style = 'background-image:url(' . esc_url( $imgs[0] ) . ');';
        } elseif ( count( $imgs ) >= 2 ) {
            $hero_style = sprintf(
                'background-image:url(%1$s),url(%2$s);background-size:50%% 100%%,50%% 100%%;background-position:left center,right center;background-repeat:no-repeat,no-repeat;',
                esc_url( $imgs[0] ),
                esc_url( $imgs[1] )
            );
        }
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

            <?php if ( ! empty( $service_tagline ) ) : ?>
                <div class="service-hero__tagline" aria-label="<?php echo esc_attr__( 'Frase curta cridanera', 'fcsd' ); ?>">
                    <?php echo wp_kses_post( wpautop( $service_tagline ) ); ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="container content py-5">
        <article <?php post_class( 'service-entry' ); ?> itemscope itemtype="https://schema.org/Service">

            <?php if ( get_the_content() ) : ?>
                <section class="mb-5" aria-labelledby="presentacio">
                    <h2 id="presentacio" class="h5 text-muted">
                        <?php echo esc_html__( 'Presentació', 'fcsd' ); ?>
                    </h2>
                    <div class="p-3 rounded border" itemprop="description">
                        <?php the_content(); ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php
            // Vídeo (YouTube) — el backend només ha d'enganxar l'URL.
            // Prioritat: camp nou "youtube_video_url".
            // Compatibilitat: si no existeix, intentem detectar una URL de YouTube dins del camp legacy "videos".
            $youtube_url = trim( (string) get_post_meta( get_the_ID(), 'youtube_video_url', true ) );

            if ( empty( $youtube_url ) ) {
                $legacy_videos = (string) get_post_meta( get_the_ID(), 'videos', true );
                if ( $legacy_videos ) {
                    // Agafa la primera URL que sembli de YouTube.
                    if ( preg_match( '/https?:\/\/(?:www\.)?(?:youtube\.com|youtu\.be)\/[\w\-\?&=\/%#\.]+/i', $legacy_videos, $m ) ) {
                        $youtube_url = trim( $m[0] );
                    }
                }
            }

            // Generem l'embed via oEmbed (més segur i responsiu amb WordPress).
            $youtube_embed = '';
            if ( $youtube_url ) {
                $youtube_embed = wp_oembed_get( $youtube_url, [
                    'width'  => 1280,
                    'height' => 720,
                ] );

                // Fallback: shortcode d'embed si l'oEmbed no respon (per plugins/entorns restringits).
                if ( empty( $youtube_embed ) ) {
                    $youtube_embed = do_shortcode( '[embed]' . esc_url( $youtube_url ) . '[/embed]' );
                }
            }

            /**
             * IMPORTANT: per poder iniciar el vídeo amb so després d'una interacció
             * (scroll/click), necessitem l'API de YouTube habilitada.
             * Injectem enablejsapi + playsinline i assegurem un id estable.
             */
            if ( ! empty( $youtube_embed ) && false !== stripos( $youtube_embed, '<iframe' ) ) {
                // 1) Forcem un id a l'iframe (si no en té).
                if ( ! preg_match( '/\sid\s*=\s*"[^"]+"/i', $youtube_embed ) ) {
                    $youtube_embed = preg_replace( '/<iframe\b/i', '<iframe id="service-youtube-iframe"', $youtube_embed, 1 );
                }

                // 2) Afegim query params a src.
                if ( preg_match( '/src\s*=\s*"([^"]+)"/i', $youtube_embed, $m ) ) {
                    $src = html_entity_decode( $m[1], ENT_QUOTES );
                    $src = add_query_arg(
                        [
                            'enablejsapi'      => '1',
                            'playsinline'      => '1',
                            'rel'              => '0',
                            'modestbranding'   => '1',
                            // Autoplay es farà via JS després d'interacció (no aquí).
                        ],
                        $src
                    );
                    $youtube_embed = str_replace( $m[0], 'src="' . esc_attr( $src ) . '"', $youtube_embed );
                }

                // 3) Donem permisos explícits d'autoplay.
                if ( ! preg_match( '/\sallow\s*=\s*"/i', $youtube_embed ) ) {
                    $youtube_embed = preg_replace( '/<iframe\b/i', '<iframe allow="autoplay; fullscreen; picture-in-picture"', $youtube_embed, 1 );
                }
            }
            ?>

            <?php if ( ! empty( $youtube_embed ) ) : ?>
                <section class="service-video" aria-label="<?php echo esc_attr__( 'Vídeo del servei', 'fcsd' ); ?>">
                    <div class="service-video__frame" role="group" aria-roledescription="<?php echo esc_attr__( 'Pantalla de vídeo', 'fcsd' ); ?>">
                        <div class="service-video__screen">
                            <button class="service-video__overlay" type="button">
                                <span class="service-video__overlay-inner">
                                    <span class="service-video__play" aria-hidden="true">▶</span>
                                    <span class="service-video__overlay-text"><?php echo esc_html__( 'Reproduir vídeo', 'fcsd' ); ?></span>
                                </span>
                            </button>
                            <?php echo $youtube_embed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <section
                class="ficha-tecnica service-ficha"
                aria-labelledby="ficha-tecnica"
                style="--service-area-color: <?php echo esc_attr( $service_color ); ?>; --service-area-color-soft: <?php echo esc_attr( $service_soft ); ?>;"
            >
                <h2 id="ficha-tecnica" class="h5 mb-3">
                    <?php echo esc_html__( 'Fitxa tècnica del servei', 'fcsd' ); ?>
                </h2>

                <?php
                $fields = [
                        'definicio_breu'       => __( 'Definició breu', 'fcsd' ),
                        'marc_normatiu'        => __( 'Marc normatiu i referents', 'fcsd' ),
                        'historia_i_missio'    => __( 'Història i missió', 'fcsd' ),
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
                        'videos'               => __( 'Vídeos o testimonis', 'fcsd' ),
                        'frase_explicativa'    => __( 'Frase explicativa en primera persona', 'fcsd' ),
                        'any_creacio'          => __( 'Any de creació', 'fcsd' ),
                        'canvis_nom'           => __( 'Canvis de nom', 'fcsd' ),
                        'destacats'            => __( 'Destacats', 'fcsd' ),
                        // 'frase_crida' es mostra a la capçalera (hero), no a la fitxa tècnica.
                        'punts_clau'           => __( '3 punts clau diferencials', 'fcsd' ),
                        'que_fem_per_tu'       => __( 'Què fem per tu', 'fcsd' ),
                        'que_fem_entorn'       => __( 'Què fem per l\'entorn', 'fcsd' ),
                    ];

                $has_rows = false;
                ?>

                <?php
                // --------------------------------------------------
                // Fitxa tècnica: barregem camps existents + imatges extra
                // --------------------------------------------------
                $items = [];
                foreach ( $fields as $key => $label ) {
                    $value = get_post_meta( get_the_ID(), $key, true );

                    // Si ja hem mostrat el vídeo com a embed i el camp legacy "videos" només
                    // conté un enllaç de YouTube, evitem duplicar informació a la fitxa.
                    if ( 'videos' === $key && ! empty( $youtube_embed ) && ! empty( $youtube_url ) ) {
                        $v = trim( (string) $value );
                        if ( $v && preg_match( '/^(https?:\/\/(?:www\.)?(?:youtube\.com|youtu\.be)\/.+)$/i', $v ) ) {
                            if ( trim( $youtube_url ) === $v ) {
                                continue;
                            }
                        }
                    }

                    if ( empty( $value ) ) {
                        continue;
                    }

                    $items[] = [
                        'type'  => 'text',
                        'label' => $label,
                        'html'  => wp_kses_post( wpautop( $value ) ),
                    ];
                }

                $extra_ids_raw = (string) get_post_meta( get_the_ID(), 'fcsd_service_technical_extra_images', true );
                $extra_ids     = array_filter( array_map( 'absint', preg_split( '/\s*,\s*/', $extra_ids_raw ) ) );

                $extra_items = [];
                foreach ( $extra_ids as $img_id ) {
                    $img = wp_get_attachment_image( $img_id, 'large', false, [ 'class' => 'service-ficha-item__image' ] );
                    if ( ! $img ) {
                        continue;
                    }
                    $title = get_the_title( $img_id );
                    $extra_items[] = [
                        'type'  => 'image',
                        'label' => $title ? $title : __( 'Imatge', 'fcsd' ),
                        'html'  => $img,
                    ];
                }

                // Inserim les imatges extra en posicions "aleatòries" però estables per post.
                if ( ! empty( $extra_items ) && ! empty( $items ) ) {
                    mt_srand( (int) get_the_ID() );
                    foreach ( $extra_items as $ex ) {
                        $pos = mt_rand( 0, count( $items ) );
                        array_splice( $items, $pos, 0, [ $ex ] );
                    }
                    mt_srand();
                } elseif ( ! empty( $extra_items ) ) {
                    $items = array_merge( $items, $extra_items );
                }
                ?>

                <div class="service-ficha-grid" role="table" aria-label="<?php echo esc_attr__( 'Fitxa tècnica del servei', 'fcsd' ); ?>">
                    <?php foreach ( $items as $row ) :
                        $has_rows = true;
                        ?>
                        <section class="service-ficha-item" role="row">
                            <h3 class="service-ficha-item__label" role="cell">
                                <?php echo esc_html( (string) $row['label'] ); ?>
                            </h3>
                            <div class="service-ficha-item__value" role="cell">
                                <?php echo $row['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </div>
                        </section>
                    <?php endforeach; ?>

                    <?php if ( ! $has_rows ) : ?>
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

            <?php
            // Informació imprescindible: s'ha de mostrar com a footer (no com a "cajitas").
            if ( function_exists( 'fcsd_render_service_info_footer' ) ) {
                fcsd_render_service_info_footer( get_the_ID(), false );
            }
            ?>

        </article>
    </div><!-- /.container -->

<?php
endwhile;

get_footer();
