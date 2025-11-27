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
    $fields = [
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
    ];

    // Render de tots els camps
    foreach ( $fields as $key ) {
        $label_raw = str_replace( '_', ' ', ucfirst( $key ) );
        $label     = __( $label_raw, 'fcsd' );

        $type = in_array( $key, $textarea_fields, true ) ? 'textarea' : 'text';

        fcsd_render_service_field( $label, $key, $values, $type );
    }

    echo '</div>';
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
    ];

    foreach ( $fields as $f ) {
        if ( 'documentacio_pdf' === $f ) {
            if ( isset( $_POST[ $f ] ) && $_POST[ $f ] !== '' ) {
                update_post_meta( $post_id, $f, esc_url_raw( wp_unslash( $_POST[ $f ] ) ) );
            } elseif ( isset( $_POST[ $f ] ) && $_POST[ $f ] === '' ) {
                delete_post_meta( $post_id, $f );
            }
            continue;
        }

        if ( isset( $_POST[ $f ] ) ) {
            update_post_meta( $post_id, $f, sanitize_text_field( wp_unslash( $_POST[ $f ] ) ) );
        }
    }
}
add_action( 'save_post', 'fcsd_save_service_meta' );
