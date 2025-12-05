<?php
/* Template Name: Página organigrama */

get_header();
?>

<div class="contenedor-organigrama">

    <?php
    // 1) ORGANIGRAMA FÍSICO (IMAGEN)
    $show_fisico = get_option( 'org_show_fisico', '1' );
    $image_id    = get_option( 'org_fisico_image_id', 0 );

    if ( $show_fisico === '1' && $image_id ) : ?>
        <div class="organigrama-fisico">
            <?php echo wp_get_attachment_image( $image_id, 'large' ); ?>
        </div>
    <?php endif; ?>

    <?php
    // 2) ORGANIGRAMA DIGITAL (NODOS)
    $show_digital = get_option( 'org_show_digital', '1' );

    if ( $show_digital === '1' ) :

        $nodos = get_posts( array(
            'post_type'      => 'organigrama_nodo',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

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

            $data[ $id ] = array(
                'title'       => $nodo->post_title,
                'color'       => $color ? $color : 'azul',
                'peso'        => $peso ? $peso : 'normal',
                'parent'      => $nivel_sup ? intval( $nivel_sup ) : 0,
                'punto_union' => $punto_union ? $punto_union : 'inferior',
                'en_camino'   => $en_camino,
            );

            if ( $nivel_sup ) {
                $children[ $nivel_sup ][] = $id;
            } else {
                $roots[] = $id;
            }
        }

        // Función recursiva para pintar el arbol HTML
        function org_render_nodo_tree( $id, $data, $children ) {
            if ( ! isset( $data[ $id ] ) ) return;
            $n = $data[ $id ];
            ?>
            <div class="org-nodo org-color-<?php echo esc_attr( $n['color'] ); ?> org-peso-<?php echo esc_attr( $n['peso'] ); ?> <?php echo $n['en_camino'] ? 'org-en-camino' : ''; ?>"
                 data-union="<?php echo esc_attr( $n['punto_union'] ); ?>">
                <div class="org-nodo-inner">
                    <span class="org-nodo-title"><?php echo esc_html( $n['title'] ); ?></span>
                </div>
                <?php if ( ! empty( $children[ $id ] ) ) : ?>
                    <div class="org-nodo-children">
                        <?php foreach ( $children[ $id ] as $child_id ) {
                            org_render_nodo_tree( $child_id, $data, $children );
                        } ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php
        }
        ?>

        <div class="organigrama-digital">
            <?php foreach ( $roots as $root_id ) {
                org_render_nodo_tree( $root_id, $data, $children );
            } ?>
        </div>

    <?php endif; ?>

</div>

<?php
get_footer();
