<?php
// inc/ecommerce/product-meta.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Encola el colorpicker sólo en el editor de productos
 */
add_action( 'admin_enqueue_scripts', function( $hook ) {
    global $post;

    if ( ( $hook === 'post-new.php' || $hook === 'post.php' ) && isset( $post ) && $post->post_type === 'fcsd_product' ) {
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
    }
} );

/**
 * Metabox principal: "Dades del producte"
 */
add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'fcsd_product_data',
        __( 'Dades del producte', 'fcsd' ),
        'fcsd_render_product_data_metabox',
        'fcsd_product',
        'normal',
        'high'
    );
} );

/**
 * Render del formulario de datos de producto
 */
function fcsd_render_product_data_metabox( $post ) {
    wp_nonce_field( 'fcsd_save_product_data', 'fcsd_product_data_nonce' );

    $price_regular = get_post_meta( $post->ID, '_fcsd_price_regular', true );
    $price_sale    = get_post_meta( $post->ID, '_fcsd_price_sale', true );
    $price_member  = get_post_meta( $post->ID, '_fcsd_price_member', true );

    $product_type  = get_post_meta( $post->ID, '_fcsd_product_type', true ); // physical|online|subscription|service

    $sku           = get_post_meta( $post->ID, '_fcsd_sku', true );
    $stock         = get_post_meta( $post->ID, '_fcsd_stock', true );
    ?>

    <div class="fcsd-product-meta">
        <style>
            .fcsd-product-meta .field-group {margin-bottom:1rem;}
            .fcsd-product-meta label {font-weight:600; display:block; margin-bottom:.25rem;}
            .fcsd-product-meta small {display:block; color:#666; font-size:12px;}
            .fcsd-product-meta input[type="text"],
            .fcsd-product-meta input[type="number"],
            .fcsd-product-meta select {width:100%; max-width:400px;}
            .fcsd-product-meta .columns {display:flex; flex-wrap:wrap; gap:2rem;}
            .fcsd-product-meta .col {flex:1 1 220px;}
        </style>

        <div class="columns">
            <div class="col">
                <h4><?php esc_html_e( 'Preus', 'fcsd' ); ?></h4>

                <div class="field-group">
                    <label for="fcsd_price_regular"><?php esc_html_e( 'Preu base (€)', 'fcsd' ); ?></label>
                    <input type="number" step="0.01" min="0" id="fcsd_price_regular" name="fcsd_price_regular"
                           value="<?php echo esc_attr( $price_regular ); ?>">
                    <small><?php esc_html_e( 'Preu principal del producte.', 'fcsd' ); ?></small>
                </div>

                <div class="field-group">
                    <label for="fcsd_price_sale"><?php esc_html_e( 'Preu rebaixat (€)', 'fcsd' ); ?></label>
                    <input type="number" step="0.01" min="0" id="fcsd_price_sale" name="fcsd_price_sale"
                           value="<?php echo esc_attr( $price_sale ); ?>">
                    <small><?php esc_html_e( 'Opcional. Si és inferior al preu base, es mostrarà com a oferta.', 'fcsd' ); ?></small>
                </div>

                <div class="field-group">
                    <label for="fcsd_price_member"><?php esc_html_e( 'Preu per usuaris registrats (€)', 'fcsd' ); ?></label>
                    <input type="number" step="0.01" min="0" id="fcsd_price_member" name="fcsd_price_member"
                           value="<?php echo esc_attr( $price_member ); ?>">
                    <small><?php esc_html_e( 'Opcional. Si hi ha sessió iniciada i aquest preu és > 0, s\'utilitza en lloc del preu base/rebaixat.', 'fcsd' ); ?></small>
                </div>
            </div>

            <div class="col">
                <h4><?php esc_html_e( 'Tipus de producte', 'fcsd' ); ?></h4>

                <div class="field-group">
                    <label for="fcsd_product_type"><?php esc_html_e( 'Tipus', 'fcsd' ); ?></label>
                    <select id="fcsd_product_type" name="fcsd_product_type">
                        <?php
                        $options = [
                            'physical'      => __( 'Producte físic', 'fcsd' ),
                            'online'        => __( 'Servei / producte online', 'fcsd' ),
                            'subscription'  => __( 'Subscripció', 'fcsd' ),
                            'service'       => __( 'Servei puntual', 'fcsd' ),
                        ];
                        if ( ! $product_type ) {
                            $product_type = 'physical';
                        }
                        foreach ( $options as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $product_type, $key ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small><?php esc_html_e( 'Condiciona si cal adreça física, enviament, etc.', 'fcsd' ); ?></small>
                </div>
            </div>

            <div class="col">
                <h4><?php esc_html_e( 'Dades internes', 'fcsd' ); ?></h4>

                <div class="field-group">
                    <label for="fcsd_sku"><?php esc_html_e( 'SKU / Referència', 'fcsd' ); ?></label>
                    <input type="text" id="fcsd_sku" name="fcsd_sku"
                           value="<?php echo esc_attr( $sku ); ?>">
                </div>

                <div class="field-group">
                    <label for="fcsd_stock"><?php esc_html_e( 'Estoc', 'fcsd' ); ?></label>
                    <input type="number" id="fcsd_stock" name="fcsd_stock" min="0"
                           value="<?php echo esc_attr( $stock ); ?>">
                    <small><?php esc_html_e( 'Opcional. Només informatiu, la lògica de control d\'estoc es pot afegir més endavant.', 'fcsd' ); ?></small>
                </div>
            </div>
        </div>
    </div>

    <?php
}

/**
 * Guardar los datos del metabox principal
 */
add_action( 'save_post_fcsd_product', function ( $post_id ) {
    if ( empty( $_POST['fcsd_product_data_nonce'] ) ||
         ! wp_verify_nonce( $_POST['fcsd_product_data_nonce'], 'fcsd_save_product_data' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $fields = [
        'fcsd_price_regular' => '_fcsd_price_regular',
        'fcsd_price_sale'    => '_fcsd_price_sale',
        'fcsd_price_member'  => '_fcsd_price_member',
        'fcsd_product_type'  => '_fcsd_product_type',
        'fcsd_sku'           => '_fcsd_sku',
        'fcsd_stock'         => '_fcsd_stock',
    ];

    foreach ( $fields as $form_key => $meta_key ) {
        if ( isset( $_POST[ $form_key ] ) ) {
            $value = wp_unslash( $_POST[ $form_key ] );

            if ( in_array( $form_key, [ 'fcsd_price_regular', 'fcsd_price_sale', 'fcsd_price_member' ], true ) ) {
                $value = str_replace( ',', '.', $value );
                $value = $value !== '' ? (float) $value : '';
            } elseif ( 'fcsd_stock' === $form_key ) {
                $value = (int) $value;
            } else {
                $value = sanitize_text_field( $value );
            }

            update_post_meta( $post_id, $meta_key, $value );
        }
    }

    // Mantener compatibilidad con el meta _price usado por el carrito actual
    $price_regular = get_post_meta( $post_id, '_fcsd_price_regular', true );
    if ( $price_regular !== '' ) {
        update_post_meta( $post_id, '_price', $price_regular );
    }
} );


// ========================================================================
//  METABOX LATERAL: COLORES DISPONIBLES (array de hex con colorpicker)
// ========================================================================
add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'fcsd_product_colors',
        __( 'Colores disponibles', 'fcsd' ),
        'fcsd_product_colors_metabox',
        'fcsd_product',
        'side',
        'default'
    );
} );

function fcsd_product_colors_metabox( $post ) {
    wp_nonce_field( 'fcsd_save_product_colors', 'fcsd_product_colors_nonce' );

    $colors = get_post_meta( $post->ID, '_fcsd_product_colors', true );
    if ( ! is_array( $colors ) ) {
        $colors = [];
    }
    ?>
    <div id="fcsd-colors-wrapper">
        <?php if ( empty( $colors ) ) : ?>
            <div class="fcsd-color-row">
                <input type="text" class="fcsd-color-field" name="fcsd_product_colors[]" value="#ffffff" />
                <button type="button" class="button fcsd-remove-color">&times;</button>
            </div>
        <?php else : ?>
            <?php foreach ( $colors as $color ) : ?>
                <div class="fcsd-color-row">
                    <input type="text" class="fcsd-color-field" name="fcsd_product_colors[]" value="<?php echo esc_attr( $color ); ?>" />
                    <button type="button" class="button fcsd-remove-color">&times;</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <p>
        <button type="button" class="button" id="fcsd-add-color">
            <?php _e( 'Añadir color', 'fcsd' ); ?>
        </button>
    </p>

    <style>
        #fcsd-colors-wrapper .fcsd-color-row {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
            gap: 4px;
        }
        #fcsd-colors-wrapper .fcsd-color-field {
            width: 80px;
        }
    </style>

    <script>
        (function($){
            $(document).ready(function(){

                function initColorPickers(context) {
                    $(context).find('.fcsd-color-field').wpColorPicker();
                }

                // Inicial
                initColorPickers(document);

                $('#fcsd-add-color').on('click', function(){
                    var $wrapper = $('#fcsd-colors-wrapper');
                    var row = $('<div class="fcsd-color-row">' +
                        '<input type="text" class="fcsd-color-field" name="fcsd_product_colors[]" value="#ffffff" />' +
                        '<button type="button" class="button fcsd-remove-color">&times;</button>' +
                        '</div>');
                    $wrapper.append(row);
                    initColorPickers(row);
                });

                $('#fcsd-colors-wrapper').on('click', '.fcsd-remove-color', function(){
                    $(this).closest('.fcsd-color-row').remove();
                });

            });
        })(jQuery);
    </script>
    <?php
}

/**
 * Guardar colores del colorpicker (array de hex)
 */
add_action( 'save_post_fcsd_product', function( $post_id ) {

    if ( ! isset( $_POST['fcsd_product_colors_nonce'] ) ||
         ! wp_verify_nonce( $_POST['fcsd_product_colors_nonce'], 'fcsd_save_product_colors' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['fcsd_product_colors'] ) && is_array( $_POST['fcsd_product_colors'] ) ) {
        $colors = array_map( function( $c ) {
            $c = trim( $c );
            if ( $c !== '' && $c[0] !== '#' ) {
                $c = '#' . $c;
            }
            return sanitize_text_field( $c );
        }, $_POST['fcsd_product_colors'] );

        $colors = array_filter( $colors ); // quitar vacíos
        update_post_meta( $post_id, '_fcsd_product_colors', $colors );
    } else {
        delete_post_meta( $post_id, '_fcsd_product_colors' );
    }
} );


// ========================================================================
//  METABOX LATERAL: TALLAS DISPONIBLES (checkboxes fijos)
// ========================================================================
add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'fcsd_product_sizes',
        __( 'Tallas disponibles', 'fcsd' ),
        'fcsd_product_sizes_metabox',
        'fcsd_product',
        'side',
        'default'
    );
} );

function fcsd_product_sizes_metabox( $post ) {
    wp_nonce_field( 'fcsd_save_product_sizes', 'fcsd_product_sizes_nonce' );

    $sizes = get_post_meta( $post->ID, '_fcsd_product_sizes', true );
    if ( ! is_array( $sizes ) ) {
        $sizes = [];
    }

    $all_sizes = [ 'U', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL' ];
    ?>
    <p><?php _e( 'Selecciona les talles aplicables a aquest producte.', 'fcsd' ); ?></p>
    <?php foreach ( $all_sizes as $size ) : ?>
        <p>
            <label>
                <input type="checkbox" name="fcsd_product_sizes[]" value="<?php echo esc_attr( $size ); ?>"
                    <?php checked( in_array( $size, $sizes, true ) ); ?> />
                <?php echo esc_html( $size ); ?>
            </label>
        </p>
    <?php endforeach; ?>
    <?php
}

/**
 * Guardar tallas (array de strings)
 */
add_action( 'save_post_fcsd_product', function( $post_id ) {

    if ( ! isset( $_POST['fcsd_product_sizes_nonce'] ) ||
         ! wp_verify_nonce( $_POST['fcsd_product_sizes_nonce'], 'fcsd_save_product_sizes' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['fcsd_product_sizes'] ) && is_array( $_POST['fcsd_product_sizes'] ) ) {
        $sizes = array_map( 'sanitize_text_field', $_POST['fcsd_product_sizes'] );
        $sizes = array_unique( $sizes );
        update_post_meta( $post_id, '_fcsd_product_sizes', $sizes );
    } else {
        delete_post_meta( $post_id, '_fcsd_product_sizes' );
    }
} );
