<?php
/* Template Name: Página organigrama */

get_header();
?>

<div class="container my-5">
    <div class="contenedor-organigrama">

        <?php
        // 1) ORGANIGRAMA FÍSICO (IMAGEN)
        $show_fisico = get_option( 'org_show_fisico', '1' );
        $image_id    = get_option( 'org_fisico_image_id', 0 );

        if ( $show_fisico === '1' && $image_id ) :
            $image_url = wp_get_attachment_image_url( $image_id, 'full' );
            ?>
            <div class="organigrama-fisico mb-5">
                <h2 class="text-center mb-4">Organigrama</h2>
                <div class="org-image-block text-center">
                    <img src="<?php echo esc_url( $image_url ); ?>" 
                         alt="Organigrama FCSD" 
                         class="org-image img-fluid" 
                         style="max-width: 100%; height: auto;">
                </div>
            </div>
        <?php endif; ?>

        <?php
        // 2) ORGANIGRAMA DIGITAL (NODOS)
        $show_digital = get_option( 'org_show_digital', '1' );

        if ( $show_digital === '1' ) :

            $nodos = get_posts( array(
                'post_type'      => 'organigrama_nodo',
                'posts_per_page' => -1,
                'meta_key'       => '_org_orden',
                'orderby'        => array(
                    'meta_value_num' => 'ASC',
                    'title'          => 'ASC',
                ),
            ) );


            if ( ! empty( $nodos ) ) :
                // Preparamos arrays de datos y relaciones
                $data     = array();
                $children = array();
                $roots    = array();

                foreach ( $nodos as $nodo ) {
                    $id = $nodo->ID;

                    $color       = get_post_meta( $id, '_org_color', true );
                    $peso        = get_post_meta( $id, '_org_peso', true );
                    $nivel_sup   = get_post_meta( $id, '_org_nivel_superior', true );
                    $punto_union = get_post_meta( $id, '_org_punto_union', true );
                    $en_camino   = get_post_meta( $id, '_org_en_camino', true ) === '1';
                    $orden       = get_post_meta( $id, '_org_orden', true );

                    // NUEVO: dirección en que deben mostrarse los hijos de este nodo
                    $children_dir = get_post_meta( $id, '_org_lower_level_dir', true );
                    if ( ! $children_dir ) {
                        // por defecto horizontal para mantener compatibilidad
                        $children_dir = 'horizontal';
                    }

                    $data[ $id ] = array(
                        'title'        => $nodo->post_title,
                        'color'        => $color ? $color : 'azul',
                        'peso'         => $peso ? $peso : 'normal',
                        'parent'       => $nivel_sup ? intval( $nivel_sup ) : 0,
                        'punto_union'  => $punto_union ? $punto_union : 'inferior',
                        'en_camino'    => $en_camino,
                        'orden'        => $orden ? intval( $orden ) : 0,
                        'children_dir' => $children_dir, // NUEVO
                    );

                    if ( $nivel_sup ) {
                        if ( ! isset( $children[ $nivel_sup ] ) ) {
                            $children[ $nivel_sup ] = array();
                        }
                        $children[ $nivel_sup ][] = $id;
                    } else {
                        $roots[] = $id;
                    }
                }

                /**
                 * Función recursiva para pintar el árbol HTML
                 */
                function org_render_nodo_tree( $id, $data, $children, $level = 0 ) {
                    if ( ! isset( $data[ $id ] ) ) return;
                    $n = $data[ $id ];
                    
                    $classes = array(
                        'org-nodo',
                        'org-color-' . esc_attr( $n['color'] ),
                        'org-peso-' . esc_attr( $n['peso'] ),
                        'org-level-' . $level
                    );
                    
                    if ( $n['en_camino'] ) {
                        $classes[] = 'org-en-camino';
                    }
                    ?>
                    <div class="<?php echo implode( ' ', $classes ); ?>"
                         data-union="<?php echo esc_attr( $n['punto_union'] ); ?>"
                         data-id="<?php echo esc_attr( $id ); ?>">
                        <div class="org-nodo-inner">
                            <span class="org-nodo-title"><?php echo esc_html( $n['title'] ); ?></span>
                        </div>
                        <?php if ( ! empty( $children[ $id ] ) ) : 
                            // NUEVO: clase según la dirección de los hijos
                            $dir = isset( $n['children_dir'] ) ? $n['children_dir'] : 'horizontal';
                            $children_class = ( $dir === 'vertical' ) ? 'org-children-vertical' : 'org-children-horizontal';
                            ?>
                            <div class="org-nodo-children <?php echo esc_attr( $children_class ); ?>"
                                 data-dir="<?php echo esc_attr( $dir ); ?>">
                                <?php foreach ( $children[ $id ] as $child_id ) {
                                    org_render_nodo_tree( $child_id, $data, $children, $level + 1 );
                                } ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php
                }
                ?>

                <div class="organigrama-digital-section mt-5">
                    <h2 class="text-center mb-4">Estructura organizativa</h2>
                    <div class="organigrama-digital-wrapper">
                        <div class="organigrama-digital">
                            <?php foreach ( $roots as $root_id ) {
                                org_render_nodo_tree( $root_id, $data, $children );
                            } ?>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

        <?php endif; ?>

    </div>
</div>

<?php
get_footer();
