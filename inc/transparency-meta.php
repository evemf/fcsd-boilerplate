<?php
// inc/transparency.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Encola el media frame + JS sólo en el editor del CPT transparency
 */
add_action( 'admin_enqueue_scripts', function( $hook ) {
    global $post;

    if ( ( $hook === 'post-new.php' || $hook === 'post.php' )
        && isset( $post )
        && $post->post_type === 'transparency'
    ) {
        wp_enqueue_media();
        wp_enqueue_script(
            'fcsd-transparency-admin',
            FCSD_THEME_URI . '/assets/js/transparency-admin.js',
            [ 'jquery' ],
            FCSD_VERSION,
            true
        );
    }
} );

/**
 * Metabox principal para Transparència
 */
add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'fcsd_transparency_meta',
        __( 'Dades de transparència', 'fcsd' ),
        'fcsd_render_transparency_metabox',
        'transparency',
        'normal',
        'high'
    );
} );

/**
 * Render del metabox
 */
function fcsd_render_transparency_metabox( WP_Post $post ) {

    wp_nonce_field( 'fcsd_transparency_save_meta', 'fcsd_transparency_meta_nonce' );

    $type      = get_post_meta( $post->ID, '_fcsd_transparency_type', true ) ?: 'year';
    $year      = get_post_meta( $post->ID, '_fcsd_transparency_year', true );
    $audit_id  = (int) get_post_meta( $post->ID, '_fcsd_audit_pdf_id', true );
    $mem_id    = (int) get_post_meta( $post->ID, '_fcsd_memoria_pdf_id', true );
    $single_id = (int) get_post_meta( $post->ID, '_fcsd_single_pdf_id', true );

    $audit_name  = $audit_id  ? get_the_title( $audit_id )  : '';
    $mem_name    = $mem_id    ? get_the_title( $mem_id )    : '';
    $single_name = $single_id ? get_the_title( $single_id ) : '';
    ?>
    <style>
        .fcsd-transparency-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit,minmax(260px,1fr));
            gap: 20px;
            margin-top: 10px;
        }
        .fcsd-transparency-meta-grid .field {
            margin-bottom: .75rem;
        }
        .fcsd-transparency-meta-grid label {
            font-weight: 600;
        }
        .fcsd-transparency-file-name {
            display: inline-block;
            margin-left: .5rem;
            font-style: italic;
            color: #666;
        }
        .fcsd-transparency-help {
            font-size: 12px;
            color: #666;
            margin-top: .25rem;
        }
    </style>

    <p>
        <label for="fcsd_transparency_type"><strong><?php esc_html_e( 'Tipus d’element', 'fcsd' ); ?></strong></label><br>
        <select name="fcsd_transparency_type" id="fcsd_transparency_type">
            <option value="year" <?php selected( $type, 'year' ); ?>>
                <?php esc_html_e( 'Any (Auditoria + Memòria)', 'fcsd' ); ?>
            </option>
            <option value="single" <?php selected( $type, 'single' ); ?>>
                <?php esc_html_e( 'Recuadre únic (PDF)', 'fcsd' ); ?>
            </option>
            <option value="accordion" <?php selected( $type, 'accordion' ); ?>>
                <?php esc_html_e( 'Apartat desplegable', 'fcsd' ); ?>
            </option>
        </select>
        <div class="fcsd-transparency-help">
            <?php esc_html_e( 'Any = un registre per any amb auditoria i memòria. Recuadre únic = un box rosa amb un sol PDF. Desplegable = usa el títol i el contingut del post.', 'fcsd' ); ?>
        </div>
    </p>

    <div class="fcsd-transparency-meta-grid">

        <div class="field">
            <label for="fcsd_transparency_year"><?php esc_html_e( 'Any', 'fcsd' ); ?></label><br>
            <input type="number"
                   id="fcsd_transparency_year"
                   name="fcsd_transparency_year"
                   value="<?php echo esc_attr( $year ); ?>"
                   min="2000"
                   max="2100"
                   style="width: 120px;">
            <div class="fcsd-transparency-help">
                <?php esc_html_e( 'Només es fa servir per al tipus "Any".', 'fcsd' ); ?>
            </div>
        </div>

        <div class="field">
            <label><?php esc_html_e( 'PDF únic (recuadre rosa)', 'fcsd' ); ?></label><br>
            <input type="hidden"
                   id="fcsd_single_pdf_id"
                   name="fcsd_single_pdf_id"
                   value="<?php echo esc_attr( $single_id ); ?>">
            <button type="button"
                    class="button fcsd-upload-pdf"
                    data-target="fcsd_single_pdf_id"
                    data-title="<?php esc_attr_e( 'Selecciona PDF', 'fcsd' ); ?>">
                <?php esc_html_e( 'Seleccionar PDF', 'fcsd' ); ?>
            </button>
            <span class="fcsd-transparency-file-name">
                <?php echo esc_html( $single_name ); ?>
            </span>
            <div class="fcsd-transparency-help">
                <?php esc_html_e( 'Es mostrarà com un bloc rosa amb el títol del post.', 'fcsd' ); ?>
            </div>
        </div>

        <div class="field">
            <label><?php esc_html_e( 'PDF Auditoria de comptes', 'fcsd' ); ?></label><br>
            <input type="hidden"
                   id="fcsd_audit_pdf_id"
                   name="fcsd_audit_pdf_id"
                   value="<?php echo esc_attr( $audit_id ); ?>">
            <button type="button"
                    class="button fcsd-upload-pdf"
                    data-target="fcsd_audit_pdf_id"
                    data-title="<?php esc_attr_e( 'Selecciona PDF d\'auditoria', 'fcsd' ); ?>">
                <?php esc_html_e( 'Seleccionar PDF', 'fcsd' ); ?>
            </button>
            <span class="fcsd-transparency-file-name">
                <?php echo esc_html( $audit_name ); ?>
            </span>
            <div class="fcsd-transparency-help">
                <?php esc_html_e( 'Per als elements de tipus "Any".', 'fcsd' ); ?>
            </div>
        </div>

        <div class="field">
            <label><?php esc_html_e( 'PDF Memòria', 'fcsd' ); ?></label><br>
            <input type="hidden"
                   id="fcsd_memoria_pdf_id"
                   name="fcsd_memoria_pdf_id"
                   value="<?php echo esc_attr( $mem_id ); ?>">
            <button type="button"
                    class="button fcsd-upload-pdf"
                    data-target="fcsd_memoria_pdf_id"
                    data-title="<?php esc_attr_e( 'Selecciona PDF de memòria', 'fcsd' ); ?>">
                <?php esc_html_e( 'Seleccionar PDF', 'fcsd' ); ?>
            </button>
            <span class="fcsd-transparency-file-name">
                <?php echo esc_html( $mem_name ); ?>
            </span>
            <div class="fcsd-transparency-help">
                <?php esc_html_e( 'Per als elements de tipus "Any".', 'fcsd' ); ?>
            </div>
        </div>

    </div>

    <p class="fcsd-transparency-help">
        <?php esc_html_e( 'Per als elements de tipus "Apartat desplegable" utilitza el títol i l’editor de continguts del post.', 'fcsd' ); ?>
    </p>
    <?php
}

/**
 * Guardado seguro de metadatos
 */
function fcsd_save_transparency_meta( $post_id ) {

    if ( ! isset( $_POST['fcsd_transparency_meta_nonce'] )
        || ! wp_verify_nonce( $_POST['fcsd_transparency_meta_nonce'], 'fcsd_transparency_save_meta' )
    ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( isset( $_POST['post_type'] ) && $_POST['post_type'] === 'transparency' ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    } else {
        return;
    }

    // Tipo
    $type = isset( $_POST['fcsd_transparency_type'] )
        ? sanitize_text_field( wp_unslash( $_POST['fcsd_transparency_type'] ) )
        : 'year';

    update_post_meta( $post_id, '_fcsd_transparency_type', $type );

    // Año
    $year = isset( $_POST['fcsd_transparency_year'] )
        ? (int) $_POST['fcsd_transparency_year']
        : 0;

    if ( $year ) {
        update_post_meta( $post_id, '_fcsd_transparency_year', $year );
    } else {
        delete_post_meta( $post_id, '_fcsd_transparency_year' );
    }

    // PDFs
    $audit_id  = isset( $_POST['fcsd_audit_pdf_id'] )   ? (int) $_POST['fcsd_audit_pdf_id']   : 0;
    $mem_id    = isset( $_POST['fcsd_memoria_pdf_id'] ) ? (int) $_POST['fcsd_memoria_pdf_id'] : 0;
    $single_id = isset( $_POST['fcsd_single_pdf_id'] )  ? (int) $_POST['fcsd_single_pdf_id']  : 0;

    $meta_keys = [
        '_fcsd_audit_pdf_id'   => $audit_id,
        '_fcsd_memoria_pdf_id' => $mem_id,
        '_fcsd_single_pdf_id'  => $single_id,
    ];

    foreach ( $meta_keys as $key => $value ) {
        if ( $value ) {
            update_post_meta( $post_id, $key, $value );
        } else {
            delete_post_meta( $post_id, $key );
        }
    }
}
add_action( 'save_post_transparency', 'fcsd_save_transparency_meta' );
