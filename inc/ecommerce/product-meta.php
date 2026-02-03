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
    $has_variants  = (int) get_post_meta( $post->ID, '_fcsd_has_variants', true );
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
    <label>
        <input type="checkbox" name="fcsd_has_variants" value="1" <?php checked( $has_variants, 1 ); ?> />
        <?php esc_html_e( 'Aquest producte té variants (talla/color)', 'fcsd' ); ?>
    </label>
    <small><?php esc_html_e( 'Si està marcat, l\'estoc es controla per cada variant segons les talles i/o colors disponibles.', 'fcsd' ); ?></small>
</div>

<div class="field-group">
    <label for="fcsd_stock"><?php esc_html_e( 'Estoc (genèric / total)', 'fcsd' ); ?></label>
    <input type="number" id="fcsd_stock" name="fcsd_stock" min="0" value="<?php echo esc_attr( $stock ); ?>" <?php echo $has_variants ? 'readonly' : ''; ?> />
    <small><?php esc_html_e( 'Si NO hi ha variants, aquest camp és el que controla l\'estoc. Si hi ha variants, es calcula automàticament com a suma de l\'estoc per variant.', 'fcsd' ); ?></small>
</div>

<script>
    (function(){
        const cb = document.querySelector('input[name="fcsd_has_variants"]');
        const stock = document.getElementById('fcsd_stock');
        if(!cb || !stock) return;
        function sync(){
            if(cb.checked){ stock.setAttribute('readonly','readonly'); }
            else { stock.removeAttribute('readonly'); }
        }
        cb.addEventListener('change', sync);
        sync();
    })();
</script>
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
];

    foreach ( $fields as $form_key => $meta_key ) {
        if ( isset( $_POST[ $form_key ] ) ) {
            $value = wp_unslash( $_POST[ $form_key ] );

            if ( in_array( $form_key, [ 'fcsd_price_regular', 'fcsd_price_sale', 'fcsd_price_member' ], true ) ) {
                $value = str_replace( ',', '.', $value );
                $value = $value !== '' ? (float) $value : '';
            } else {
                $value = sanitize_text_field( $value );
            }

            update_post_meta( $post_id, $meta_key, $value );
        }
    }

    // Variants & stock
    $has_variants = isset( $_POST['fcsd_has_variants'] ) ? 1 : 0;
    update_post_meta( $post_id, '_fcsd_has_variants', $has_variants );

    // If product has variants: save per-variant stock and set total stock as sum.
    // If product has no variants: save only generic stock and clear variants.
    if ( $has_variants ) {
        $variant_stock = [];
        $total = 0;

        // Expect posted structure: fcsd_stock_variant[color][size]
        if ( isset( $_POST['fcsd_stock_variant'] ) && is_array( $_POST['fcsd_stock_variant'] ) ) {
            foreach ( $_POST['fcsd_stock_variant'] as $color_key => $sizes ) {
                $color_key = sanitize_text_field( wp_unslash( $color_key ) );
                if ( ! is_array( $sizes ) ) {
                    $sizes = [ '' => $sizes ];
                }
                foreach ( $sizes as $size_key => $qty ) {
                    $size_key = sanitize_text_field( wp_unslash( $size_key ) );
                    $qty = (int) $qty;
                    if ( $qty < 0 ) {
                        $qty = 0;
                    }

                    // Normalize color to #RRGGBB (or empty)
                    $norm_color = trim( $color_key );
                    if ( $norm_color !== '' && $norm_color[0] !== '#' ) {
                        $norm_color = '#' . $norm_color;
                    }

                    $key = 'color=' . $norm_color . ';size=' . $size_key;
                    $variant_stock[ $key ] = $qty;
                    $total += $qty;
                }
            }
        }

        update_post_meta( $post_id, '_fcsd_stock_variants', $variant_stock );
        update_post_meta( $post_id, '_fcsd_stock', $total );

    } else {
        // Save generic stock
        $generic = isset( $_POST['fcsd_stock'] ) ? (int) $_POST['fcsd_stock'] : 0;
        if ( $generic < 0 ) {
            $generic = 0;
        }
        update_post_meta( $post_id, '_fcsd_stock', $generic );
        delete_post_meta( $post_id, '_fcsd_stock_variants' );
    }

    // Mantener compatibilidad con el meta _price usado por el carrito actual
    $price_regular = get_post_meta( $post_id, '_fcsd_price_regular', true );
    if ( $price_regular !== '' ) {
        update_post_meta( $post_id, '_price', $price_regular );
    }
} );


// ========================================================================
//  METABOX: ESTOC PER VARIANTS (segons colors i/o talles disponibles)
// ========================================================================
add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'fcsd_product_variant_stock',
        __( 'Estoc per variants', 'fcsd' ),
        'fcsd_product_variant_stock_metabox',
        'fcsd_product',
        'normal',
        'default'
    );
} );

function fcsd_product_variant_stock_metabox( $post ) {
    $has_variants = (int) get_post_meta( $post->ID, '_fcsd_has_variants', true );
    $colors = get_post_meta( $post->ID, '_fcsd_product_colors', true );
    $sizes  = get_post_meta( $post->ID, '_fcsd_product_sizes', true );
    $colors = is_array( $colors ) ? $colors : [];
    $sizes  = is_array( $sizes ) ? $sizes : [];

    $variant_stock = get_post_meta( $post->ID, '_fcsd_stock_variants', true );
    $variant_stock = is_array( $variant_stock ) ? $variant_stock : [];

    echo '<p>';
    esc_html_e( 'Defineix primer els colors i/o talles als metaboxes laterals. Si el producte està marcat com a "té variants", aquí podràs posar l\'estoc per cada opció o combinació.', 'fcsd' );
    echo '</p>';

    // Render the table markup even if the meta hasn't been saved yet.
    // Gutenberg sometimes delays meta persistence; a small JS toggle makes the UI reliable.
    $wrap_style = $has_variants ? '' : 'style="display:none"';
    if ( ! $has_variants ) {
        echo '<p class="fcsd-variant-hint"><em>';
        esc_html_e( 'Ara mateix el producte està marcat com a "sense variants". Marca "té variants" (a Dades del producte) per gestionar estoc per talla/color.', 'fcsd' );
        echo '</em></p>';
    }

    echo '<div id="fcsd-variant-stock-fields" ' . $wrap_style . '>';

    // If no colors & no sizes, show a hint
    if ( empty( $colors ) && empty( $sizes ) ) {
        echo '<p><em>';
        esc_html_e( 'No hi ha colors ni talles definits. Afegeix-ne almenys un per poder gestionar variants.', 'fcsd' );
        echo '</em></p>';
        echo '</div>';
        echo '<script>(function(){var cb=document.querySelector("input[name=\"fcsd_has_variants\"]");var box=document.getElementById("fcsd-variant-stock-fields");if(!cb||!box)return;function sync(){box.style.display=cb.checked?"block":"none";}cb.addEventListener("change",sync);sync();})();</script>';
        return;
    }

    // Helper to get stock value
    $get_qty = function( $color, $size ) use ( $variant_stock ) {
        $key = 'color=' . $color . ';size=' . $size;
        return isset( $variant_stock[ $key ] ) ? (int) $variant_stock[ $key ] : 0;
    };

    echo '<style>.fcsd-variant-stock-table{border-collapse:collapse;width:100%;max-width:900px}.fcsd-variant-stock-table th,.fcsd-variant-stock-table td{border:1px solid #ddd;padding:8px;text-align:left}.fcsd-variant-stock-table input[type=number]{width:100px}.fcsd-variant-hint{margin-top:6px}</style>';

    echo '<table class="fcsd-variant-stock-table">';

    // Three cases:
    // 1) colors + sizes => grid
    // 2) only colors => list
    // 3) only sizes => list
    if ( ! empty( $colors ) && ! empty( $sizes ) ) {
        echo '<thead><tr><th>' . esc_html__( 'Color / Talla', 'fcsd' ) . '</th>';
        foreach ( $sizes as $size ) {
            echo '<th>' . esc_html( $size ) . '</th>';
        }
        echo '</tr></thead><tbody>';

        foreach ( $colors as $color ) {
            $color = trim( $color );
            echo '<tr>';
            echo '<td><span style="display:inline-block;width:14px;height:14px;border:1px solid #999;background:' . esc_attr( $color ) . ';vertical-align:middle;margin-right:6px"></span>' . esc_html( $color ) . '</td>';
            foreach ( $sizes as $size ) {
                $qty = $get_qty( $color, $size );
                echo '<td><input type="number" min="0" name="fcsd_stock_variant[' . esc_attr( $color ) . '][' . esc_attr( $size ) . ']" value="' . esc_attr( $qty ) . '" /></td>';
            }
            echo '</tr>';
        }

        echo '</tbody>';

    } elseif ( ! empty( $colors ) ) {
        echo '<thead><tr><th>' . esc_html__( 'Color', 'fcsd' ) . '</th><th>' . esc_html__( 'Estoc', 'fcsd' ) . '</th></tr></thead><tbody>';
        foreach ( $colors as $color ) {
            $color = trim( $color );
            $qty = $get_qty( $color, '' );
            echo '<tr>';
            echo '<td><span style="display:inline-block;width:14px;height:14px;border:1px solid #999;background:' . esc_attr( $color ) . ';vertical-align:middle;margin-right:6px"></span>' . esc_html( $color ) . '</td>';
            echo '<td><input type="number" min="0" name="fcsd_stock_variant[' . esc_attr( $color ) . '][' . esc_attr( '' ) . ']" value="' . esc_attr( $qty ) . '" /></td>';
            echo '</tr>';
        }
        echo '</tbody>';

    } else {
        echo '<thead><tr><th>' . esc_html__( 'Talla', 'fcsd' ) . '</th><th>' . esc_html__( 'Estoc', 'fcsd' ) . '</th></tr></thead><tbody>';
        foreach ( $sizes as $size ) {
            $qty = $get_qty( '', $size );
            echo '<tr>';
            echo '<td>' . esc_html( $size ) . '</td>';
            echo '<td><input type="number" min="0" name="fcsd_stock_variant[' . esc_attr( '' ) . '][' . esc_attr( $size ) . ']" value="' . esc_attr( $qty ) . '" /></td>';
            echo '</tr>';
        }
        echo '</tbody>';
    }

    echo '</table>';
    echo '</div>';
    echo '<script>(function(){var cb=document.querySelector("input[name=\"fcsd_has_variants\"]");var box=document.getElementById("fcsd-variant-stock-fields");if(!cb||!box)return;function sync(){box.style.display=cb.checked?"block":"none";}cb.addEventListener("change",sync);sync();})();</script>';
}

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
