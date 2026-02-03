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

        // --- Aliances / partners (nou) ---
        // Es mostren al front al costat dels logos de suport.
        'fcsd_service_conveni_items',       // JSON (llista d'items: label + url opcional)
        'fcsd_service_collaboracio_items',  // JSON

        // --- Fitxa tècnica: imatges extra (nou) ---
        // IDs d'adjunts separats per comes.
        'fcsd_service_technical_extra_images',
    ];

    // Valors actuals (CA) + valors traduïbles (ES/EN)
    $values = [];
    foreach ( $fields as $f ) {
        $values[ $f ] = get_post_meta( $post->ID, $f, true );

        // Càrrega de traduccions (metes: _fcsd_i18n_meta_{key}_{lang})
        if ( defined( 'FCSD_LANGUAGES' ) && defined( 'FCSD_DEFAULT_LANG' ) ) {
            foreach ( array_keys( FCSD_LANGUAGES ) as $lng ) {
                if ( $lng === FCSD_DEFAULT_LANG ) {
                    continue;
                }
                $tkey                   = '_fcsd_i18n_meta_' . $f . '_' . $lng;
                $values[ $f . '__' . $lng ] = get_post_meta( $post->ID, $tkey, true );
            }
        }
    }

    $document_url = get_post_meta( $post->ID, 'documentacio_pdf', true );
    if ( defined( 'FCSD_LANGUAGES' ) && defined( 'FCSD_DEFAULT_LANG' ) ) {
        foreach ( array_keys( FCSD_LANGUAGES ) as $lng ) {
            if ( $lng === FCSD_DEFAULT_LANG ) {
                continue;
            }
            $values[ 'documentacio_pdf__' . $lng ] = get_post_meta( $post->ID, '_fcsd_i18n_meta_documentacio_pdf_' . $lng, true );
        }
    }

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

    // Camp per al document adjunt (CA) + opcions per idioma
    echo '<div class="servei-col">';
    echo '<label for="documentacio_pdf">' . esc_html__( 'Documentació adjunta (PDF, DOC...)', 'fcsd' ) . '</label>';
    echo '<input type="text" id="documentacio_pdf" name="documentacio_pdf" value="' . esc_attr( $document_url ) . '" />';
    echo '<input type="button" class="button" value="' . esc_attr__( 'Puja o selecciona un arxiu', 'fcsd' ) . '" id="upload_pdf_button" />';

    // Versions ES/EN (mateix document o documents diferents si cal)
    if ( defined( 'FCSD_LANGUAGES' ) && defined( 'FCSD_DEFAULT_LANG' ) ) {
        foreach ( array_keys( FCSD_LANGUAGES ) as $lng ) {
            if ( $lng === FCSD_DEFAULT_LANG ) {
                continue;
            }
            $fname = 'documentacio_pdf__' . $lng;
            $val   = isset( $values[ $fname ] ) ? (string) $values[ $fname ] : '';
            echo '<p style="margin:10px 0 6px"><label for="' . esc_attr( $fname ) . '"><strong>' . esc_html( sprintf( __( 'Document (%s)', 'fcsd' ), strtoupper( $lng ) ) ) . '</strong></label></p>';
            echo '<input type="text" id="' . esc_attr( $fname ) . '" name="' . esc_attr( $fname ) . '" value="' . esc_attr( $val ) . '" />';
        }
    }

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

    // --------------------------------------------------
    // Conveni amb / Col·laboració amb (repetibles)
    // --------------------------------------------------
    $conveni_json = isset( $values['fcsd_service_conveni_items'] ) ? (string) $values['fcsd_service_conveni_items'] : '';
    $collab_json  = isset( $values['fcsd_service_collaboracio_items'] ) ? (string) $values['fcsd_service_collaboracio_items'] : '';

    echo '<div class="servei-col" style="flex-basis:100%;">';
    echo '<h4 style="margin:18px 0 10px;">' . esc_html__( 'Conveni amb', 'fcsd' ) . '</h4>';
    echo '<input type="hidden" id="fcsd_service_conveni_items" name="fcsd_service_conveni_items" value="' . esc_attr( $conveni_json ) . '" />';
    echo '<div id="fcsd_conveni_repeater" style="display:flex;flex-direction:column;gap:10px;"></div>';
    echo '<button type="button" class="button" id="fcsd_conveni_add">' . esc_html__( 'Afegir ítem', 'fcsd' ) . '</button>';
    echo '<p class="description" style="margin-top:6px;">' . esc_html__( 'Afegeix un o més textos (opcionalment amb enllaç).', 'fcsd' ) . '</p>';
    echo '</div>';

    echo '<div class="servei-col" style="flex-basis:100%;">';
    echo '<h4 style="margin:18px 0 10px;">' . esc_html__( 'Col·laboració amb', 'fcsd' ) . '</h4>';
    echo '<input type="hidden" id="fcsd_service_collaboracio_items" name="fcsd_service_collaboracio_items" value="' . esc_attr( $collab_json ) . '" />';
    echo '<div id="fcsd_collab_repeater" style="display:flex;flex-direction:column;gap:10px;"></div>';
    echo '<button type="button" class="button" id="fcsd_collab_add">' . esc_html__( 'Afegir ítem', 'fcsd' ) . '</button>';
    echo '<p class="description" style="margin-top:6px;">' . esc_html__( 'Afegeix un o més textos (opcionalment amb enllaç).', 'fcsd' ) . '</p>';
    echo '</div>';

    // --------------------------------------------------
    // Fitxa tècnica: imatges extra (multi)
    // --------------------------------------------------
    $extra_ids = isset( $values['fcsd_service_technical_extra_images'] ) ? (string) $values['fcsd_service_technical_extra_images'] : '';
    echo '<div class="servei-col" style="flex-basis:100%;">';
    echo '<h4 style="margin:18px 0 10px;">' . esc_html__( 'Imatges extra per la fitxa tècnica', 'fcsd' ) . '</h4>';
    echo '<label for="fcsd_service_technical_extra_images" style="font-weight:normal;">' . esc_html__( 'Selecciona una o més imatges', 'fcsd' ) . '</label>';
    echo '<input type="text" id="fcsd_service_technical_extra_images" name="fcsd_service_technical_extra_images" value="' . esc_attr( $extra_ids ) . '" />';
    echo '<p class="description">' . esc_html__( 'Es guardaran com a IDs separats per comes i es mostraran com a “cajitas” dins la fitxa tècnica.', 'fcsd' ) . '</p>';
    echo '<input type="button" class="button" value="' . esc_attr__( 'Selecciona imatges', 'fcsd' ) . '" id="fcsd_tech_images_button" />';
    echo '<div id="fcsd_tech_images_preview" style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;">';
    if ( $extra_ids ) {
        $ids = array_filter( array_map( 'absint', explode( ',', $extra_ids ) ) );
        foreach ( $ids as $id ) {
            $thumb = wp_get_attachment_image( $id, 'thumbnail', false, [ 'style' => 'max-width:80px;height:auto;border:1px solid #ddd;padding:4px;background:#fff;' ] );
            if ( $thumb ) {
                echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
        }
    }
    echo '</div>';
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

    // --------------------------------------------------
    // Traduccions ES/EN dels camps del servei (opcionals)
    // --------------------------------------------------
    if ( defined( 'FCSD_LANGUAGES' ) && defined( 'FCSD_DEFAULT_LANG' ) ) {
        foreach ( array_keys( FCSD_LANGUAGES ) as $lng ) {
            if ( $lng === FCSD_DEFAULT_LANG ) {
                continue;
            }
            echo '<div style="flex-basis:100%;margin-top:18px;">';
            echo '<details style="background:#fff;border:1px solid #e5e5e5;border-radius:8px;padding:10px;">';
            echo '<summary style="cursor:pointer;font-weight:600;">' . esc_html( sprintf( __( 'Traducció de camps (%s)', 'fcsd' ), strtoupper( $lng ) ) ) . '</summary>';
            echo '<p class="description" style="margin:10px 0 0;">' . esc_html__( 'Omple només els camps que vulguis traduir. Si deixes un camp buit, es mostrarà el valor de CA.', 'fcsd' ) . '</p>';

            // Camps (incloent els de footer i partners): guardem a _fcsd_i18n_meta_{key}_{lang}.
            foreach ( $fields as $key ) {
                $field_name = $key . '__' . $lng;

                if ( 'youtube_video_url' === $key ) {
                    $label = __( 'Vídeo de YouTube (URL)', 'fcsd' );
                    $type  = 'url';
                } elseif ( 'videos' === $key ) {
                    $label = __( 'Vídeos o testimonis', 'fcsd' );
                    $type  = in_array( $key, $textarea_fields, true ) ? 'textarea' : 'text';
                } elseif ( 0 === strpos( $key, 'fcsd_service_' ) ) {
                    // Labels ya existen arriba (footer/partners/extra images). Usamos una etiqueta fija por clave.
                    $label_raw = str_replace( '_', ' ', ucfirst( $key ) );
                    $label     = __( $label_raw, 'fcsd' );
                    $type      = in_array( $key, $textarea_fields, true ) ? 'textarea' : 'text';
                } else {
                    $label_raw = str_replace( '_', ' ', ucfirst( $key ) );
                    $label     = __( $label_raw, 'fcsd' );
                    $type      = in_array( $key, $textarea_fields, true ) ? 'textarea' : 'text';
                }

                // Repetibles JSON: mantener input oculto pero editable como texto.
                if ( in_array( $key, [ 'fcsd_service_conveni_items', 'fcsd_service_collaboracio_items' ], true ) ) {
                    $type = 'textarea';
                }

                fcsd_render_service_field( $label, $key, $values, $type, $field_name, $field_name );
            }

            echo '</details>';
            echo '</div>';
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

        // -----------------------------
        // Repeater helpers (Conveni/Col·laboració)
        // -----------------------------
        function safeParse(json){
            try {
                var v = JSON.parse(json || '[]');
                return Array.isArray(v) ? v : [];
            } catch(e){
                return [];
            }
        }

        function renderRepeater(containerSel, hiddenSel, items){
            var $c = $(containerSel);
            $c.empty();
            if (!items.length){
                items = [{label:'', url:''}];
            }
            items.forEach(function(it, idx){
                var row = $('<div />').css({display:'grid', gridTemplateColumns:'2fr 2fr auto', gap:'10px', alignItems:'center'});
                var $label = $('<input type="text" />').attr('placeholder','<?php echo esc_js( __( 'Text', 'fcsd' ) ); ?>').val(it.label || '');
                var $url = $('<input type="url" />').attr('placeholder','<?php echo esc_js( __( 'URL (opcional)', 'fcsd' ) ); ?>').val(it.url || '');
                var $del = $('<button type="button" class="button" />').text('<?php echo esc_js( __( 'Eliminar', 'fcsd' ) ); ?>');
                $del.on('click', function(){
                    items.splice(idx, 1);
                    if (!items.length) items = [{label:'', url:''}];
                    sync();
                });
                $label.on('input', function(){ items[idx].label = $(this).val(); sync(false); });
                $url.on('input', function(){ items[idx].url = $(this).val(); sync(false); });
                row.append($label, $url, $del);
                $c.append(row);
            });

            function sync(rerender){
                // neteja items buits al moment de sync fort
                if (rerender !== false){
                    items = items.filter(function(it){
                        return (it && (String(it.label||'').trim() || String(it.url||'').trim()));
                    });
                    if (!items.length) items = [{label:'', url:''}];
                }
                $(hiddenSel).val(JSON.stringify(items));
                if (rerender !== false){
                    renderRepeater(containerSel, hiddenSel, items);
                }
            }

            // Sync inicial
            $(hiddenSel).val(JSON.stringify(items));
        }

        var conveniItems = safeParse($('#fcsd_service_conveni_items').val());
        var collabItems  = safeParse($('#fcsd_service_collaboracio_items').val());
        renderRepeater('#fcsd_conveni_repeater', '#fcsd_service_conveni_items', conveniItems);
        renderRepeater('#fcsd_collab_repeater', '#fcsd_service_collaboracio_items', collabItems);

        $('#fcsd_conveni_add').on('click', function(){
            var items = safeParse($('#fcsd_service_conveni_items').val());
            items.push({label:'', url:''});
            renderRepeater('#fcsd_conveni_repeater', '#fcsd_service_conveni_items', items);
        });

        $('#fcsd_collab_add').on('click', function(){
            var items = safeParse($('#fcsd_service_collaboracio_items').val());
            items.push({label:'', url:''});
            renderRepeater('#fcsd_collab_repeater', '#fcsd_service_collaboracio_items', items);
        });

        // -----------------------------
        // Media frame: imatges extra de fitxa tècnica (multi)
        // -----------------------------
        var techFrame;
        $('#fcsd_tech_images_button').on('click', function(e){
            e.preventDefault();
            if (techFrame) {
                techFrame.open();
                return;
            }
            techFrame = wp.media({
                title: '<?php echo esc_js( __( 'Selecciona imatges per la fitxa tècnica', 'fcsd' ) ); ?>',
                button: { text: '<?php echo esc_js( __( 'Usa aquestes imatges', 'fcsd' ) ); ?>' },
                multiple: true
            });
            techFrame.on('select', function(){
                var selection = techFrame.state().get('selection');
                var ids = [];
                var html = '';
                selection.each(function(attachment){
                    attachment = attachment.toJSON();
                    if (attachment.id) ids.push(attachment.id);
                    if (attachment.sizes && attachment.sizes.thumbnail) {
                        html += '<img src="'+attachment.sizes.thumbnail.url+'" style="max-width:80px;height:auto;border:1px solid #ddd;padding:4px;background:#fff;" />';
                    }
                });
                $('#fcsd_service_technical_extra_images').val(ids.join(','));
                $('#fcsd_tech_images_preview').html(html);
            });
            techFrame.open();
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
function fcsd_render_service_field( $label, $key, $values, $type = 'text', $field_name = null, $field_id = null ) {
    $name  = $field_name ? (string) $field_name : (string) $key;
    $id    = $field_id ? (string) $field_id : $name;
    $value = isset( $values[ $name ] ) ? $values[ $name ] : ( isset( $values[ $key ] ) ? $values[ $key ] : '' );

    echo '<div class="servei-col">';
    echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';

    if ( 'textarea' === $type ) {
        echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea>';
    } else {
        echo '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" />';
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

        // Partners
        'fcsd_service_conveni_items',
        'fcsd_service_collaboracio_items',

        // Fitxa tècnica: imatges extra
        'fcsd_service_technical_extra_images',
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
            } elseif ( 'fcsd_service_technical_extra_images' === $f ) {
                $ids = array_filter( array_map( 'absint', preg_split( '/\s*,\s*/', (string) $raw ) ) );
                $san = implode( ',', $ids );
            } elseif ( 'fcsd_service_conveni_items' === $f || 'fcsd_service_collaboracio_items' === $f ) {
                // JSON de llista d'items: [{label:"...", url:"..."}, ...]
                $decoded = json_decode( (string) $raw, true );
                if ( ! is_array( $decoded ) ) {
                    $decoded = [];
                }
                $clean = [];
                foreach ( $decoded as $it ) {
                    if ( ! is_array( $it ) ) {
                        continue;
                    }
                    $label = isset( $it['label'] ) ? sanitize_text_field( (string) $it['label'] ) : '';
                    $url   = isset( $it['url'] ) ? esc_url_raw( (string) $it['url'] ) : '';
                    if ( '' === trim( $label ) && '' === trim( $url ) ) {
                        continue;
                    }
                    $clean[] = [
                        'label' => $label,
                        'url'   => $url,
                    ];
                }
                $san = wp_json_encode( $clean );
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

    // Guardat de traduccions ES/EN (meta: _fcsd_i18n_meta_{key}_{lang})
    if ( defined( 'FCSD_LANGUAGES' ) && defined( 'FCSD_DEFAULT_LANG' ) ) {
        foreach ( array_keys( FCSD_LANGUAGES ) as $lng ) {
            if ( $lng === FCSD_DEFAULT_LANG ) {
                continue;
            }

            foreach ( $fields as $f ) {
                $post_key = $f . '__' . $lng;
                if ( ! array_key_exists( $post_key, $_POST ) ) {
                    continue;
                }

                $raw = wp_unslash( $_POST[ $post_key ] );
                $raw = is_string( $raw ) ? $raw : '';

                // Sanitització coherent amb el guardat principal.
                if ( 'youtube_video_url' === $f || 'documentacio_pdf' === $f ) {
                    $san = esc_url_raw( (string) $raw );
                } elseif ( in_array( $f, [ 'fcsd_service_conveni_items', 'fcsd_service_collaboracio_items' ], true ) ) {
                    $decoded = json_decode( (string) $raw, true );
                    if ( ! is_array( $decoded ) ) {
                        $decoded = [];
                    }
                    $clean = [];
                    foreach ( $decoded as $it ) {
                        if ( ! is_array( $it ) ) {
                            continue;
                        }
                        $label = isset( $it['label'] ) ? sanitize_text_field( (string) $it['label'] ) : '';
                        $url   = isset( $it['url'] ) ? esc_url_raw( (string) $it['url'] ) : '';
                        if ( '' === trim( $label ) && '' === trim( $url ) ) {
                            continue;
                        }
                        $clean[] = [
                            'label' => $label,
                            'url'   => $url,
                        ];
                    }
                    $san = wp_json_encode( $clean );
                } else {
                    $san = in_array( $f, $textarea_fields, true ) ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
                }

                $meta_key = '_fcsd_i18n_meta_' . $f . '_' . $lng;
                if ( '' === (string) $san ) {
                    delete_post_meta( $post_id, $meta_key );
                } else {
                    update_post_meta( $post_id, $meta_key, $san );
                }
            }
        }
    }
}
add_action( 'save_post', 'fcsd_save_service_meta' );
