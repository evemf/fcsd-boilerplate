<?php
/**
 * CUSTOM POST TYPE: NODOS DEL ORGANIGRAMA DIGITAL
 */
add_action( 'init', 'org_register_nodo_cpt' );
function org_register_nodo_cpt() {
    $labels = array(
        'name'               => 'Nodos organigrama',
        'singular_name'      => 'Nodo organigrama',
        'menu_name'          => 'Organigrama',
        'add_new'            => 'Añadir nodo',
        'add_new_item'       => 'Añadir nodo',
        'edit_item'          => 'Editar nodo',
        'new_item'           => 'Nuevo nodo',
        'view_item'          => 'Ver nodo',
        'search_items'       => 'Buscar nodos',
        'not_found'          => 'No se han encontrado nodos',
        'not_found_in_trash' => 'No hay nodos en la papelera',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'capability_type'    => 'post',
        'hierarchical'       => false,
        'supports'           => array( 'title' ),
        'menu_position'      => 25,
        'menu_icon'          => 'dashicons-networking',
    );

    register_post_type( 'organigrama_nodo', $args );
}

/**
 * METABOX PARA PROPIEDADES DEL NODO
 */
add_action( 'add_meta_boxes', 'org_add_nodo_metaboxes' );
function org_add_nodo_metaboxes() {
    add_meta_box(
        'org_nodo_propiedades',
        'Propiedades del nodo',
        'org_render_nodo_metabox',
        'organigrama_nodo',
        'normal',
        'high'
    );
}

function org_render_nodo_metabox( $post ) {
    wp_nonce_field( 'org_save_nodo_metabox', 'org_nodo_nonce' );

    $color         = get_post_meta( $post->ID, '_org_color', true );
    $peso          = get_post_meta( $post->ID, '_org_peso', true );
    $nivel_sup     = get_post_meta( $post->ID, '_org_nivel_superior', true );
    $punto_union   = get_post_meta( $post->ID, '_org_punto_union', true );
    $en_camino     = get_post_meta( $post->ID, '_org_en_camino', true );
    $children_dir  = get_post_meta( $post->ID, '_org_children_dir', true ); // NUEVO
    if ( ! $children_dir ) {
        $children_dir = 'vertical'; // valor por defecto
    }

    $nodos = get_posts( array(
        'post_type'      => 'organigrama_nodo',
        'posts_per_page' => -1,
        'post_status'    => 'publish', // opcional, pero suele ser buena idea
        'meta_key'       => '_org_orden',
        'orderby'        => array(
            'meta_value_num' => 'ASC',
            'title'          => 'ASC',
        ),
    ) );

    ?>
    <p>
        <label for="org_color"><strong>Color</strong></label><br>
        <select name="org_color" id="org_color">
            <option value="">—</option>
            <option value="azul" <?php selected( $color, 'azul' ); ?>>Azul</option>
            <option value="azul-claro" <?php selected( $color, 'azul-claro' ); ?>>Azul claro</option>
            <option value="amarillo" <?php selected( $color, 'amarillo' ); ?>>Amarillo</option>
            <option value="granate" <?php selected( $color, 'granate' ); ?>>Granate</option>
            <option value="rosa" <?php selected( $color, 'rosa' ); ?>>Rosa</option>
            <option value="rosa-claro" <?php selected( $color, 'rosa-claro' ); ?>>Rosa claro</option>
            <option value="lila" <?php selected( $color, 'lila' ); ?>>Lila</option>
            <option value="turquesa" <?php selected( $color, 'turquesa' ); ?>>Turquesa</option>
        </select>
    </p>

    <p>
        <label for="org_peso"><strong>Peso</strong></label><br>
        <select name="org_peso" id="org_peso">
            <option value="normal" <?php selected( $peso, 'normal' ); ?>>Normal</option>
            <option value="importante" <?php selected( $peso, 'importante' ); ?>>Importante</option>
        </select>
    </p>

        <p>
        <label for="org_orden"><strong>Orden (entre hermanos)</strong></label><br>
        <input type="number" name="org_orden" id="org_orden"
               value="<?php echo esc_attr( get_post_meta( $post->ID, '_org_orden', true ) ); ?>"
               style="width: 100px;">
        <span class="description">Se usa para ordenar los nodos al mismo nivel.</span>
    </p>

    <p>
        <label for="org_nivel_superior"><strong>Nivel superior (padre)</strong></label><br>
        <select name="org_nivel_superior" id="org_nivel_superior">
            <option value="">— Sin padre (nivel máximo) —</option>
            <?php foreach ( $nodos as $nodo ) : ?>
                <option value="<?php echo esc_attr( $nodo->ID ); ?>" <?php selected( $nivel_sup, $nodo->ID ); ?>>
                    <?php echo esc_html( $nodo->post_title ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>

    <p>
        <label for="org_punto_union"><strong>Punto de unión</strong></label><br>
        <select name="org_punto_union" id="org_punto_union">
            <option value="inferior" <?php selected( $punto_union, 'inferior' ); ?>>Borde inferior</option>
            <option value="superior" <?php selected( $punto_union, 'superior' ); ?>>Borde superior</option>
            <option value="izquierdo" <?php selected( $punto_union, 'izquierdo' ); ?>>Borde izquierdo</option>
            <option value="derecho" <?php selected( $punto_union, 'derecho' ); ?>>Borde derecho</option>
        </select>
    </p>
    <p>
        <label for="org_children_dir"><strong>Dirección de los nodos hijos</strong></label><br>
        <select name="org_children_dir" id="org_children_dir">
            <option value="vertical" <?php selected( $children_dir, 'vertical' ); ?>>Vertical</option>
            <option value="horizontal" <?php selected( $children_dir, 'horizontal' ); ?>>Horizontal</option>
        </select>
        <span class="description">Cómo se colocan los hijos directos de este nodo.</span>
    </p>

    <p>
        <label for="org_en_camino">
            <input type="checkbox" id="org_en_camino" name="org_en_camino" value="1" <?php checked( $en_camino, '1' ); ?>>
            Nodo en camino / lateral
        </label>
    </p>
    <?php
}

add_action( 'save_post_organigrama_nodo', 'org_save_nodo_metabox' );
function org_save_nodo_metabox( $post_id ) {
    if ( ! isset( $_POST['org_nodo_nonce'] ) || ! wp_verify_nonce( $_POST['org_nodo_nonce'], 'org_save_nodo_metabox' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $color       = isset( $_POST['org_color'] ) ? sanitize_text_field( $_POST['org_color'] ) : '';
    $peso        = isset( $_POST['org_peso'] ) ? sanitize_text_field( $_POST['org_peso'] ) : 'normal';
    $nivel_sup   = isset( $_POST['org_nivel_superior'] ) ? intval( $_POST['org_nivel_superior'] ) : '';
    $punto_union = isset( $_POST['org_punto_union'] ) ? sanitize_text_field( $_POST['org_punto_union'] ) : 'inferior';
    $en_camino   = isset( $_POST['org_en_camino'] ) ? '1' : '0';
    $orden       = isset( $_POST['org_orden'] ) ? intval( $_POST['org_orden'] ) : 0;
    $children_dir = isset( $_POST['org_children_dir'] ) ? sanitize_text_field( $_POST['org_children_dir'] ) : 'vertical';


    update_post_meta( $post_id, '_org_color', $color );
    update_post_meta( $post_id, '_org_peso', $peso );
    update_post_meta( $post_id, '_org_nivel_superior', $nivel_sup );
    update_post_meta( $post_id, '_org_punto_union', $punto_union );
    update_post_meta( $post_id, '_org_en_camino', $en_camino );
    update_post_meta( $post_id, '_org_orden', $orden );
    update_post_meta( $post_id, '_org_children_dir', $children_dir ); 
}

/**
 * PÁGINA DE AJUSTES: CHECKBOX FÍSICO/DIGITAL + IMAGEN FÍSICA
 */
add_action( 'admin_menu', 'org_add_organigrama_options_page' );
function org_add_organigrama_options_page() {
    add_submenu_page(
        'edit.php?post_type=organigrama_nodo',
        'Ajustes organigrama',
        'Ajustes organigrama',
        'manage_options',
        'organigrama-ajustes',
        'org_render_organigrama_options_page'
    );
}

function org_render_organigrama_options_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_POST['org_ajustes_submit'] ) && check_admin_referer( 'org_ajustes_save', 'org_ajustes_nonce' ) ) {
        update_option( 'org_show_fisico', isset( $_POST['org_show_fisico'] ) ? '1' : '0' );
        update_option( 'org_show_digital', isset( $_POST['org_show_digital'] ) ? '1' : '0' );
        update_option( 'org_fisico_image_id', isset( $_POST['org_fisico_image_id'] ) ? intval( $_POST['org_fisico_image_id'] ) : 0 );
        echo '<div class="updated"><p>Ajustes guardados.</p></div>';
    }

    $show_fisico  = get_option( 'org_show_fisico', '1' );
    $show_digital = get_option( 'org_show_digital', '1' );
    $image_id     = get_option( 'org_fisico_image_id', 0 );
    $image_src    = $image_id ? wp_get_attachment_image_src( $image_id, 'medium' ) : false;
    ?>
    <div class="wrap">
        <h1>Ajustes organigrama</h1>
        <form method="post">
            <?php wp_nonce_field( 'org_ajustes_save', 'org_ajustes_nonce' ); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">Mostrar organigrama físico (imagen)</th>
                    <td>
                        <label>
                            <input type="checkbox" name="org_show_fisico" value="1" <?php checked( $show_fisico, '1' ); ?>>
                            Mostrar imagen en la página de organigrama
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">Imagen organigrama físico</th>
                    <td>
                        <div id="org_fisico_image_preview">
                            <?php if ( $image_src ) : ?>
                                <img src="<?php echo esc_url( $image_src[0] ); ?>" style="max-width:300px;height:auto;">
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="org_fisico_image_id" id="org_fisico_image_id" value="<?php echo esc_attr( $image_id ); ?>">
                        <button class="button" id="org_fisico_image_button">Seleccionar imagen</button>
                    </td>
                </tr>

                <tr>
                    <th scope="row">Mostrar organigrama digital</th>
                    <td>
                        <label>
                            <input type="checkbox" name="org_show_digital" value="1" <?php checked( $show_digital, '1' ); ?>>
                            Mostrar esquema generado automáticamente
                        </label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" name="org_ajustes_submit" class="button-primary">Guardar cambios</button>
            </p>
        </form>
    </div>
    <?php
}

/**
 * JS PARA EL SELECTOR DE IMAGEN EN LA PÁGINA DE AJUSTES
 */
add_action( 'admin_enqueue_scripts', 'org_admin_enqueue_media' );
function org_admin_enqueue_media( $hook ) {
    if ( $hook !== 'organigrama_nodo_page_organigrama-ajustes' ) {
        return;
    }

    wp_enqueue_media();
    wp_add_inline_script( 'jquery-core', "
        jQuery(function($){
            $('#org_fisico_image_button').on('click', function(e){
                e.preventDefault();
                var frame = wp.media({
                    title: 'Seleccionar imagen de organigrama físico',
                    button: { text: 'Usar esta imagen' },
                    multiple: false
                });

                frame.on('select', function(){
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#org_fisico_image_id').val( attachment.id );
                    $('#org_fisico_image_preview').html('<img src=\"' + attachment.sizes.medium.url + '\" style=\"max-width:300px;height:auto;\" />');
                });

                frame.open();
            });
        });
    " );
}