<?php
/**
 * Organigrama FCSD
 *
 * - CPT jerárquico "org_node"
 * - Metabox de tipo (color) de nodo
 * - Render del árbol con líneas conectoras
 * - Ajustes (imagen + mostrar versión digital)
 * - Shortcode [fcsd_organigrama]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registrar CPT: org_node
 */
function fcsd_org_register_cpt() {
    $labels = [
        'name'               => __( 'Organigrama', 'fcsd' ),
        'singular_name'      => __( 'Node', 'fcsd' ),
        'menu_name'          => __( 'Organigrama', 'fcsd' ),
        'add_new'            => __( 'Afegir node', 'fcsd' ),
        'add_new_item'       => __( 'Afegir node a l\'organigrama', 'fcsd' ),
        'edit_item'          => __( 'Editar node', 'fcsd' ),
        'new_item'           => __( 'Nou node', 'fcsd' ),
        'view_item'          => __( 'Veure node', 'fcsd' ),
        'search_items'       => __( 'Cercar nodes', 'fcsd' ),
        'not_found'          => __( 'No s\'han trobat nodes.', 'fcsd' ),
        'not_found_in_trash' => __( 'No hi ha nodes a la paperera.', 'fcsd' ),
        'parent_item_colon'  => __( 'Node pare:', 'fcsd' ),
    ];

    $args = [
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_icon'          => 'dashicons-networking',
        'hierarchical'       => true, // permet pares/fills
        'supports'           => [ 'title', 'page-attributes' ],
        'show_in_rest'       => false,
    ];

    register_post_type( 'org_node', $args );
}
add_action( 'init', 'fcsd_org_register_cpt' );

/**
 * Tipus de node disponibles (controlen color/estil)
 */
function fcsd_org_get_node_types() {
    return [
        'patronat'              => __( 'Patronat', 'fcsd' ),
        'direccio'              => __( 'Direcció general', 'fcsd' ),
        'comite-general'        => __( 'Comitè general', 'fcsd' ),
        'comite-etic'           => __( 'Comitè ètic', 'fcsd' ),
        'servei-acompanyament'  => __( 'Servei d\'acompanyament', 'fcsd' ),
        'servei-conscienciacio' => __( 'Servei de conscienciació', 'fcsd' ),
        'servei-suport'         => __( 'Servei de suport', 'fcsd' ),
        'area'                  => __( 'Àrea', 'fcsd' ),
        'unitat'                => __( 'Unitat / servei específic', 'fcsd' ),
        'comite-especific'      => __( 'Comitè específic', 'fcsd' ),
    ];
}

/**
 * Metabox: tipus de node
 */
function fcsd_org_add_meta_box() {
    add_meta_box(
        'fcsd_org_node_meta',
        __( 'Configuració del node', 'fcsd' ),
        'fcsd_org_node_meta_box_html',
        'org_node',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'fcsd_org_add_meta_box' );

function fcsd_org_node_meta_box_html( $post ) {
    wp_nonce_field( 'fcsd_org_save_meta', 'fcsd_org_meta_nonce' );

    $types   = fcsd_org_get_node_types();
    $current = get_post_meta( $post->ID, '_org_node_type', true );
    ?>
    <p>
        <label for="fcsd_org_node_type"><strong><?php esc_html_e( 'Tipus de node', 'fcsd' ); ?></strong></label>
    </p>
    <p>
        <select name="fcsd_org_node_type" id="fcsd_org_node_type" class="widefat">
            <option value=""><?php esc_html_e( '— Sense estil específic —', 'fcsd' ); ?></option>
            <?php foreach ( $types as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <p class="description">
        <?php esc_html_e( 'Controla el color de la capsa (Patronat, serveis, comitès…).', 'fcsd' ); ?>
    </p>
    <?php
}

/**
 * Guardar metadades del node
 */
function fcsd_org_save_node_meta( $post_id ) {
    if ( ! isset( $_POST['fcsd_org_meta_nonce'] ) || ! wp_verify_nonce( $_POST['fcsd_org_meta_nonce'], 'fcsd_org_save_meta' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( isset( $_POST['post_type'] ) && 'org_node' === $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }

    $type = isset( $_POST['fcsd_org_node_type'] ) ? sanitize_text_field( wp_unslash( $_POST['fcsd_org_node_type'] ) ) : '';

    if ( $type ) {
        update_post_meta( $post_id, '_org_node_type', $type );
    } else {
        delete_post_meta( $post_id, '_org_node_type' );
    }
}
add_action( 'save_post_org_node', 'fcsd_org_save_node_meta' );

/**
 * Classe CSS segons tipus
 */
function fcsd_org_get_type_class( $post_id ) {
    $type = get_post_meta( $post_id, '_org_node_type', true );
    if ( ! $type ) {
        return 'org-node--default';
    }

    return 'org-node--' . sanitize_html_class( $type );
}

/**
 * Renderitzar arrel de l'arbre
 */
function fcsd_org_render_tree_root() {
    $roots = get_posts(
        [
            'post_type'      => 'org_node',
            'post_status'    => 'publish',
            'post_parent'    => 0,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'numberposts'    => -1,
            'fields'         => 'ids',
        ]
    );

    if ( ! $roots ) {
        echo '<p>' . esc_html__( 'Encara no hi ha nodes definits a l\'organigrama.', 'fcsd' ) . '</p>';
        return;
    }

    echo '<ul class="org-tree org-tree--root">';
    foreach ( $roots as $root_id ) {
        fcsd_org_render_node( $root_id );
    }
    echo '</ul>';
}

/**
 * Render recursiu d'un node
 */
function fcsd_org_render_node( $post_id ) {
    $title    = get_the_title( $post_id );
    $class    = fcsd_org_get_type_class( $post_id );

    $children = get_children(
        [
            'post_parent' => $post_id,
            'post_type'   => 'org_node',
            'post_status' => 'publish',
            'orderby'     => 'menu_order',
            'order'       => 'ASC',
            'fields'      => 'ids',
        ]
    );

    echo '<li>';
    echo '<div class="org-node ' . esc_attr( $class ) . '">';
    echo esc_html( $title );
    echo '</div>';

    if ( ! empty( $children ) ) {
        echo '<ul>';
        foreach ( $children as $child_id ) {
            fcsd_org_render_node( $child_id );
        }
        echo '</ul>';
    }

    echo '</li>';
}

/**
 * Shortcode por si quieres usarlo en cualquier página/post: [fcsd_organigrama]
 */
function fcsd_org_shortcode() {
    $show_digital = (bool) get_option( 'fcsd_org_show_digital', true );

    ob_start();

    $image_id = (int) get_option( 'fcsd_org_image_id', 0 );
    if ( $image_id ) {
        echo '<div class="org-image-block">';
        echo wp_get_attachment_image( $image_id, 'large', false, [ 'class' => 'org-image' ] );
        echo '</div>';
    }

    if ( $show_digital ) {
        echo '<div class="org-wrapper">';
        fcsd_org_render_tree_root();
        echo '</div>';
    }

    return ob_get_clean();
}
add_shortcode( 'fcsd_organigrama', 'fcsd_org_shortcode' );

/* --------------------------------------------------
 * AJUSTES: imagen + activar versión digital
 * -------------------------------------------------*/

/**
 * Submenú de ajustes bajo "Organigrama"
 */
function fcsd_org_register_settings_page() {
    add_submenu_page(
        'edit.php?post_type=org_node',
        __( 'Ajustos de l\'organigrama', 'fcsd' ),
        __( 'Ajustos', 'fcsd' ),
        'manage_options',
        'fcsd-organigrama-settings',
        'fcsd_org_settings_page_html'
    );
}
add_action( 'admin_menu', 'fcsd_org_register_settings_page' );

/**
 * Página de ajustes
 */
function fcsd_org_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_POST['fcsd_org_settings_nonce'] ) && wp_verify_nonce( $_POST['fcsd_org_settings_nonce'], 'fcsd_org_save_settings' ) ) {

        $image_id = isset( $_POST['fcsd_org_image_id'] ) ? absint( $_POST['fcsd_org_image_id'] ) : 0;
        update_option( 'fcsd_org_image_id', $image_id );

        $show_digital = isset( $_POST['fcsd_org_show_digital'] ) ? 1 : 0;
        update_option( 'fcsd_org_show_digital', $show_digital );

        echo '<div class="updated"><p>' . esc_html__( 'Opcions desades.', 'fcsd' ) . '</p></div>';
    }

    $image_id     = (int) get_option( 'fcsd_org_image_id', 0 );
    $image_url    = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
    $show_digital = (bool) get_option( 'fcsd_org_show_digital', true );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Ajustos de l\'organigrama', 'fcsd' ); ?></h1>
        <p><?php esc_html_e( 'Configura la imatge de l\'organigrama i si vols mostrar la versió digital.', 'fcsd' ); ?></p>

        <form method="post">
            <?php wp_nonce_field( 'fcsd_org_save_settings', 'fcsd_org_settings_nonce' ); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="fcsd_org_image_id"><?php esc_html_e( 'Imatge de l\'organigrama', 'fcsd' ); ?></label>
                        </th>
                        <td>
                            <input type="hidden" name="fcsd_org_image_id" id="fcsd_org_image_id" value="<?php echo esc_attr( $image_id ); ?>" />
                            <div id="fcsd_org_image_preview" style="margin-bottom:10px;">
                                <?php if ( $image_url ) : ?>
                                    <img src="<?php echo esc_url( $image_url ); ?>" style="max-width:300px;height:auto;border:1px solid #ccc;" />
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button" id="fcsd_org_image_select">
                                <?php esc_html_e( 'Seleccionar imatge', 'fcsd' ); ?>
                            </button>
                            <button type="button" class="button" id="fcsd_org_image_remove" <?php if ( ! $image_id ) echo 'style="display:none"'; ?>>
                                <?php esc_html_e( 'Treure imatge', 'fcsd' ); ?>
                            </button>
                            <p class="description">
                                <?php esc_html_e( 'Aquesta imatge es mostrarà a la pàgina d\'organigrama com a versió gràfica/física.', 'fcsd' ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <?php esc_html_e( 'Versió digital', 'fcsd' ); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="fcsd_org_show_digital" value="1" <?php checked( $show_digital ); ?> />
                                <?php esc_html_e( 'Mostrar també l\'organigrama digital (HTML amb línies i jerarquia).', 'fcsd' ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'Si es desmarca, només es veurà la imatge física.', 'fcsd' ); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e( 'Desar canvis', 'fcsd' ); ?>
                </button>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Media uploader per la pantalla d'ajustos
 */
function fcsd_org_admin_media_assets( $hook ) {
    if ( strpos( $hook, 'fcsd-organigrama-settings' ) === false ) {
        return;
    }

    wp_enqueue_media();

    wp_add_inline_script(
        'jquery',
        "jQuery(function($){
            var frame;

            $('#fcsd_org_image_select').on('click', function(e){
                e.preventDefault();

                if (frame) {
                    frame.open();
                    return;
                }

                frame = wp.media({
                    title: '" . esc_js( __( 'Seleccionar imatge del organigrama', 'fcsd' ) ) . "',
                    button: { text: '" . esc_js( __( 'Usar aquesta imatge', 'fcsd' ) ) . "' },
                    multiple: false
                });

                frame.on('select', function(){
                    var attachment = frame.state().get('selection').first().toJSON();
                    var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                    $('#fcsd_org_image_id').val(attachment.id);
                    $('#fcsd_org_image_preview').html('<img src=\"'+url+'\" style=\"max-width:300px;height:auto;border:1px solid #ccc;\" />');
                    $('#fcsd_org_image_remove').show();
                });

                frame.open();
            });

            $('#fcsd_org_image_remove').on('click', function(e){
                e.preventDefault();
                $('#fcsd_org_image_id').val('');
                $('#fcsd_org_image_preview').empty();
                $(this).hide();
            });
        });"
    );
}
add_action( 'admin_enqueue_scripts', 'fcsd_org_admin_media_assets' );
