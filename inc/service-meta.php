<?php
/**
 * Metabox per al CPT "service" (heretat de l'antic "serveis").
 * Copia adaptada de som_fcsd/inc/serveis.php.
 */

// --------------------------------------------------
// Metabox
// --------------------------------------------------
add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'service_details',
        __( 'Detalls del Servei', 'fcsd' ),
        'fcsd_render_service_metabox',
        'service',
        'normal',
        'high'
    );
} );

/**
 * Render del formulari amb totes les columnes i camps.
 *
 * @param WP_Post $post
 */
function fcsd_render_service_metabox( $post ) {
    // Nonce per a guardar
    wp_nonce_field( 'fcsd_save_service', 'fcsd_service_nonce' );

    // Mateixos camps que a som_fcsd/inc/serveis.php
    // + Camps nous per a "Informació imprescindible" (footer del servei).
    $fields = [
        // Nou: URL d'un vídeo (YouTube) per mostrar a la fitxa del servei.
        // Manté compatibilitat amb el camp legacy "videos".
        'youtube_video_url',
        'nom_servei',
        'definicio_breu',
        'marc_normatiu',
        'historia_i_missio',
        'a_quien_s_adreca',
        'que_oferim',
        'programes_inclosos',
        'altres_activitats',
        'frequencia_modalitat',
        'sponsors',
        'col_laboradors',
        'compliment_iso',
        'prestacio_social',
        'registre_generalitat',
        'equip_professional',
        'adreca_i_contacte',
        'videos',
        'frase_explicativa',
        'any_creacio',
        'canvis_nom',
        'destacats',
        'frase_crida',
        'punts_clau',
        'que_fem_per_tu',
        'que_fem_entorn',

        // --- Informació imprescindible (nou) ---
        // Es mostren al front com a footer del servei (no com a "cajitas" de metabox).
        'fcsd_service_contact_phone',
        'fcsd_service_contact_email',
        'fcsd_service_hours',
        'fcsd_service_address',
        'fcsd_service_support_images', // IDs d'adjunts separats per comes.
    ];

    // Valors actuals
    $values = [];
    foreach ( $fields as $f ) {
        $values[ $f ] = get_post_meta( $post->ID, $f, true );
    }
    $document_url = get_post_meta( $post->ID, 'documentacio_pdf', true );

    // Enqueue del media frame, per si de cas
    if ( function_exists( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }

    // CSS Inline per a fer la graella de 2 columnes
    echo '<style>
    .servei-grid { display: flex; flex-wrap: wrap; gap: 20px; }
    .servei-col { flex: 1 1 45%; min-width: 300px; }
    .servei-col label { font-weight: bold; display: block; margin-top: 10px; }
    .servei-col textarea, .servei-col input { width: 100%; }
    @media (max-width: 600px) {
        .servei-col { flex: 1 1 100%; }
    }
    </style>';

    echo '<div class="servei-grid">';

    // Camp per al document adjunt
    echo '<div class="servei-col">';
    echo '<label for="documentacio_pdf">' . esc_html__( 'Documentació adjunta (PDF, DOC...)', 'fcsd' ) . '</label>';
    echo '<input type="text" id="documentacio_pdf" name="documentacio_pdf" value="' . esc_attr( $document_url ) . '" />';
    echo '<input type="button" class="button" value="' . esc_attr__( 'Puja o selecciona un arxiu', 'fcsd' ) . '" id="upload_pdf_button" />';
    echo '</div>';

    ?>

    <script>
    jQuery(document).ready(function($){
        $('#upload_pdf_button').on('click', function(e){
            e.preventDefault();

            var file_frame = wp.media({
                title: '<?php echo esc_js( __( 'Selecciona un document', 'fcsd' ) ); ?>',
                button: { text: '<?php echo esc_js( __( 'Usa aquest document', 'fcsd' ) ); ?>' },
                multiple: false
            });

            file_frame.on('select', function(){
                var attachment = file_frame.state().get('selection').first().toJSON();
                $('#documentacio_pdf').val(attachment.url);
            });

            file_frame.open();
        });
    });
    </script>

    <?php

    // Camps que han de ser textarea (igual que a som_fcsd)
    $textarea_fields = [
        'definicio_breu',
        'marc_normatiu',
        'historia_i_missio',
        'que_oferim',
        'destacats',
        'punts_clau',
        'videos',
        'que_fem_per_tu',
        'que_fem_entorn',
        'programes_inclosos',
        'altres_activitats',
        'a_quien_s_adreca',
        'col_laboradors',
        'sponsors',
        'equip_professional',
        'adreca_i_contacte',
        'frequencia_modalitat',

        // Nous
        'fcsd_service_hours',
        'fcsd_service_address',
    ];

    // Ajuda visual: el camp nou de vídeo és un input de tipus URL.
    // (No el posem a $textarea_fields.)

    // --- UI d'Informació imprescindible (footer) ---
    echo '<div style="flex-basis:100%;margin-top:10px;">';
    echo '<hr style="margin:10px 0 15px;" />';
    echo '<h3 style="margin:0 0 10px;">' . esc_html__( 'Informació imprescindible (footer del servei)', 'fcsd' ) . '</h3>';

    fcsd_render_service_field( __( 'Telèfon de contacte', 'fcsd' ), 'fcsd_service_contact_phone', $values, 'text' );
    fcsd_render_service_field( __( 'Email de contacte', 'fcsd' ), 'fcsd_service_contact_email', $values, 'email' );
    fcsd_render_service_field( __( 'Horari d’atenció al públic', 'fcsd' ), 'fcsd_service_hours', $values, 'textarea' );
    fcsd_render_service_field( __( 'Adreça', 'fcsd' ), 'fcsd_service_address', $values, 'textarea' );

    // Suport / logos (multi)
    $support_ids = isset( $values['fcsd_service_support_images'] ) ? (string) $values['fcsd_service_support_images'] : '';
    echo '<div class="servei-col" style="flex-basis:100%;">';
    echo '<label for="fcsd_service_support_images">' . esc_html__( 'Imatges d\'apoi / suport (logos)', 'fcsd' ) . '</label>';
    echo '<input type="text" id="fcsd_service_support_images" name="fcsd_service_support_images" value="' . esc_attr( $support_ids ) . '" />';
    echo '<p class="description">' . esc_html__( 'Selecciona una o més imatges. Es guardaran com a IDs separats per comes.', 'fcsd' ) . '</p>';
    echo '<input type="button" class="button" value="' . esc_attr__( 'Selecciona imatges', 'fcsd' ) . '" id="fcsd_support_images_button" />';
    echo '<div id="fcsd_support_images_preview" style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;">';
    if ( $support_ids ) {
        $ids = array_filter( array_map( 'absint', explode( ',', $support_ids ) ) );
        foreach ( $ids as $id ) {
            $thumb = wp_get_attachment_image( $id, 'thumbnail', false, [ 'style' => 'max-width:80px;height:auto;border:1px solid #ddd;padding:4px;background:#fff;' ] );
            if ( $thumb ) {
                echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
        }
    }
    echo '</div>';
    echo '</div>';

    echo '</div>'; // end full width block

    // --- Resta de camps (legacy) ---
    foreach ( $fields as $key ) {
        // Saltem els camps de footer: ja s'han renderitzat a dalt.
        if ( 0 === strpos( $key, 'fcsd_service_' ) ) {
            continue;
        }

        // Etiquetes més humanes per a alguns camps.
        if ( 'youtube_video_url' === $key ) {
            $label = __( 'Vídeo de YouTube (URL)', 'fcsd' );
        } elseif ( 'videos' === $key ) {
            $label = __( 'Vídeos o testimonis (text legacy)', 'fcsd' );
        } else {
            $label_raw = str_replace( '_', ' ', ucfirst( $key ) );
            $label     = __( $label_raw, 'fcsd' );
        }

        if ( 'youtube_video_url' === $key ) {
            $type = 'url';
        } else {
            $type = in_array( $key, $textarea_fields, true ) ? 'textarea' : 'text';
        }

        fcsd_render_service_field( $label, $key, $values, $type );

        // Descripció breu sota el camp de vídeo.
        if ( 'youtube_video_url' === $key ) {
            echo '<p class="description" style="margin-top:-10px;">' . esc_html__( 'Enganxa aquí l’URL d’un vídeo de YouTube (p. ex. https://www.youtube.com/watch?v=...). Es mostrarà automàticament a la pàgina del servei.', 'fcsd' ) . '</p>';
        }
    }

    echo '</div>';

    // Media frame per seleccionar logos (multi)
    ?>
    <script>
    jQuery(document).ready(function($){
        var supportFrame;
        $('#fcsd_support_images_button').on('click', function(e){
            e.preventDefault();
            if (supportFrame) {
                supportFrame.open();
                return;
            }
            supportFrame = wp.media({
                title: '<?php echo esc_js( __( 'Selecciona imatges de suport', 'fcsd' ) ); ?>',
                button: { text: '<?php echo esc_js( __( 'Usa aquestes imatges', 'fcsd' ) ); ?>' },
                multiple: true
            });
            supportFrame.on('select', function(){
                var selection = supportFrame.state().get('selection');
                var ids = [];
                var html = '';
                selection.each(function(attachment){
                    attachment = attachment.toJSON();
                    if (attachment.id) ids.push(attachment.id);
                    if (attachment.sizes && attachment.sizes.thumbnail) {
                        html += '<img src="'+attachment.sizes.thumbnail.url+'" style="max-width:80px;height:auto;border:1px solid #ddd;padding:4px;background:#fff;" />';
                    }
                });
                $('#fcsd_service_support_images').val(ids.join(','));
                $('#fcsd_support_images_preview').html(html);
            });
            supportFrame.open();
        });
    });
    </script>
    <?php
}

/**
 * Render d’un camp individual (input o textarea).
 *
 * @param string $label
 * @param string $key
 * @param array  $values
 * @param string $type
 */
function fcsd_render_service_field( $label, $key, $values, $type = 'text' ) {
    $value = isset( $values[ $key ] ) ? $values[ $key ] : '';

    echo '<div class="servei-col">';
    echo '<label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';

    if ( 'textarea' === $type ) {
        echo '<textarea id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">' . esc_textarea( $value ) . '</textarea>';
    } else {
        echo '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
    }

    echo '</div>';
}

// --------------------------------------------------
// Guardat dels metadades
// --------------------------------------------------
function fcsd_save_service_meta( $post_id ) {
    // Només per al CPT service
    if ( get_post_type( $post_id ) !== 'service' ) {
        return;
    }

    // Comprovacions bàsiques
    if ( ! isset( $_POST['fcsd_service_nonce'] ) || ! wp_verify_nonce( $_POST['fcsd_service_nonce'], 'fcsd_save_service' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Mateix conjunt de camps que al render
    $fields = [
        'youtube_video_url',
        'nom_servei',
        'definicio_breu',
        'marc_normatiu',
        'historia_i_missio',
        'a_quien_s_adreca',
        'que_oferim',
        'programes_inclosos',
        'altres_activitats',
        'frequencia_modalitat',
        'sponsors',
        'col_laboradors',
        'compliment_iso',
        'prestacio_social',
        'registre_generalitat',
        'equip_professional',
        'adreca_i_contacte',
        'videos',
        'frase_explicativa',
        'any_creacio',
        'canvis_nom',
        'destacats',
        'frase_crida',
        'punts_clau',
        'que_fem_per_tu',
        'que_fem_entorn',
        'documentacio_pdf',

        // Informació imprescindible (footer)
        'fcsd_service_contact_phone',
        'fcsd_service_contact_email',
        'fcsd_service_hours',
        'fcsd_service_address',
        'fcsd_service_support_images',
    ];

    // Camps que venen de textarea a l'admin (per preservar salts de línia).
    $textarea_fields = [
        'definicio_breu',
        'marc_normatiu',
        'historia_i_missio',
        'que_oferim',
        'destacats',
        'punts_clau',
        'videos',
        'que_fem_per_tu',
        'que_fem_entorn',
        'programes_inclosos',
        'altres_activitats',
        'a_quien_s_adreca',
        'col_laboradors',
        'sponsors',
        'equip_professional',
        'adreca_i_contacte',
        'frequencia_modalitat',

        // Nous
        'fcsd_service_hours',
        'fcsd_service_address',
    ];

    foreach ( $fields as $f ) {
        if ( 'youtube_video_url' === $f ) {
            if ( isset( $_POST[ $f ] ) && $_POST[ $f ] !== '' ) {
                update_post_meta( $post_id, $f, esc_url_raw( wp_unslash( $_POST[ $f ] ) ) );
            } elseif ( isset( $_POST[ $f ] ) && $_POST[ $f ] === '' ) {
                delete_post_meta( $post_id, $f );
            }
            continue;
        }

        if ( 'documentacio_pdf' === $f ) {
            if ( isset( $_POST[ $f ] ) && $_POST[ $f ] !== '' ) {
                update_post_meta( $post_id, $f, esc_url_raw( wp_unslash( $_POST[ $f ] ) ) );
            } elseif ( isset( $_POST[ $f ] ) && $_POST[ $f ] === '' ) {
                delete_post_meta( $post_id, $f );
            }
            continue;
        }

        if ( isset( $_POST[ $f ] ) ) {
            $raw = wp_unslash( $_POST[ $f ] );

            if ( 'fcsd_service_contact_email' === $f ) {
                $san = sanitize_email( $raw );
            } elseif ( 'fcsd_service_support_images' === $f ) {
                // Normalitzem a llista d'IDs separats per comes.
                $ids = array_filter( array_map( 'absint', preg_split( '/\s*,\s*/', (string) $raw ) ) );
                $san = implode( ',', $ids );
            } else {
                $san = in_array( $f, $textarea_fields, true ) ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
            }

            if ( '' === (string) $san ) {
                delete_post_meta( $post_id, $f );
            } else {
                update_post_meta( $post_id, $f, $san );
            }
        }
    }
}
add_action( 'save_post', 'fcsd_save_service_meta' );
