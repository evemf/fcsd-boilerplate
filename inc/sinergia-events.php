<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Busca un post de tipo event vinculado a un ID de Sinergia.
 */
function fcsd_sinergia_find_event_post_id_by_sinergia_id( $sinergia_event_id ) {
    $sinergia_event_id = trim( (string) $sinergia_event_id );
    if ( ! $sinergia_event_id ) {
        return 0;
    }

    $q = new WP_Query([
        'post_type'      => 'event',
        'post_status'    => 'any',
        'meta_key'       => 'fcsd_sinergia_event_id',
        'meta_value'     => $sinergia_event_id,
        'fields'         => 'ids',
        'posts_per_page' => 1,
        'no_found_rows'  => true,
    ]);

    if ( ! empty( $q->posts ) ) {
        return (int) $q->posts[0];
    }

    return 0;
}

/**
 * Metabox lateral amb info de Sinergia.
 */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'fcsd_event_sinergia',
        __('SinergiaCRM', 'fcsd'),
        'fcsd_event_sinergia_meta_box',
        'event',
        'side',
        'default'
    );
});

function fcsd_event_sinergia_meta_box( $post ) {
    wp_nonce_field( 'fcsd_event_sinergia_meta', 'fcsd_event_sinergia_meta_nonce' );

    $sin_id = get_post_meta( $post->ID, 'fcsd_sinergia_event_id', true );
    $start  = get_post_meta( $post->ID, 'fcsd_event_start', true );
    $end    = get_post_meta( $post->ID, 'fcsd_event_end', true );
    $price  = get_post_meta( $post->ID, 'fcsd_event_price', true );
    ?>
    <p>
        <label for="fcsd_sinergia_event_id"><strong><?php _e('ID Sinergia', 'fcsd'); ?></strong></label>
        <input type="text" id="fcsd_sinergia_event_id" name="fcsd_sinergia_event_id"
               value="<?php echo esc_attr( $sin_id ); ?>" class="widefat">
    </p>
    <p>
        <label for="fcsd_event_start"><?php _e('Data inici', 'fcsd'); ?></label>
        <input type="text" id="fcsd_event_start" name="fcsd_event_start"
               value="<?php echo esc_attr( $start ); ?>" class="widefat">
    </p>
    <p>
        <label for="fcsd_event_end"><?php _e('Data fi', 'fcsd'); ?></label>
        <input type="text" id="fcsd_event_end" name="fcsd_event_end"
               value="<?php echo esc_attr( $end ); ?>" class="widefat">
    </p>
    <p>
        <label for="fcsd_event_price"><?php _e('Preu', 'fcsd'); ?></label>
        <input type="text" id="fcsd_event_price" name="fcsd_event_price"
               value="<?php echo esc_attr( $price ); ?>" class="widefat">
    </p>
    <?php
}

add_action('save_post_event', function ( $post_id ) {
    if ( ! isset( $_POST['fcsd_event_sinergia_meta_nonce'] ) ||
         ! wp_verify_nonce( $_POST['fcsd_event_sinergia_meta_nonce'], 'fcsd_event_sinergia_meta' ) ) {
        return;
    }

    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( ! current_user_can('edit_post', $post_id ) ) return;

    $sin_id = isset($_POST['fcsd_sinergia_event_id']) ? sanitize_text_field($_POST['fcsd_sinergia_event_id']) : '';
    $start  = isset($_POST['fcsd_event_start'])       ? sanitize_text_field($_POST['fcsd_event_start'])       : '';
    $end    = isset($_POST['fcsd_event_end'])         ? sanitize_text_field($_POST['fcsd_event_end'])         : '';
    $price  = isset($_POST['fcsd_event_price'])       ? sanitize_text_field($_POST['fcsd_event_price'])       : '';

    update_post_meta( $post_id, 'fcsd_sinergia_event_id', $sin_id );
    update_post_meta( $post_id, 'fcsd_event_start',      $start );
    update_post_meta( $post_id, 'fcsd_event_end',        $end );
    update_post_meta( $post_id, 'fcsd_event_price',      $price );
});

/**
 * Devuelve todos los events que pertenecen a la misma formació
 * que el $post_id dado. Incluye el propio $post_id.
 */
function fcsd_sinergia_get_grouped_events( $post_id ) {
    $post_id = (int) $post_id;

    $terms = wp_get_post_terms( $post_id, 'event_formation', [
        'fields' => 'ids',
    ] );

    $args = [
        'post_type'      => 'event',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'meta_value',
        'meta_key'       => 'fcsd_event_start',
        'order'          => 'ASC',
    ];

    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        // Todos los events de la misma formació
        $args['tax_query'] = [[
            'taxonomy' => 'event_formation',
            'field'    => 'term_id',
            'terms'    => $terms,
        ]];
    } else {
        // Sin formació → solo este event
        $args['post__in'] = [ $post_id ];
    }

    $q = new WP_Query( $args );

    return $q->posts;
}


// -----------------------------------------------------------------------------
// Admin UX: columna ID Sinergia + badges Pare/Fill + Quick Edit (post_parent)
// -----------------------------------------------------------------------------

/**
 * Columnas del listado del CPT `event`.
 */
add_filter('manage_event_posts_columns', function($columns) {
    // Insertar tras el título
    $new = [];
    foreach ($columns as $key => $label) {
        $new[$key] = $label;
        if ($key === 'title') {
            $new['fcsd_event_type']   = __('Tipus', 'fcsd');      // Pare / Fill
            $new['fcsd_sinergia_id']  = __('ID Sinergia', 'fcsd');
            $new['fcsd_event_parent'] = __('Pare', 'fcsd');
        }
    }
    return $new;
}, 20);

/**
 * Renderizado de columnas custom.
 */
add_action('manage_event_posts_custom_column', function($column, $post_id) {

    if ($column === 'fcsd_sinergia_id') {
        $sin_id = (string) get_post_meta($post_id, 'fcsd_sinergia_event_id', true);
        echo $sin_id !== '' ? esc_html($sin_id) : '&mdash;';
        return;
    }

    if ($column === 'fcsd_event_type') {
        $is_child = (int) wp_get_post_parent_id($post_id) > 0;
        $label = $is_child ? __('Fill', 'fcsd') : __('Pare', 'fcsd');
        $class = $is_child ? 'fcsd-badge fcsd-badge--child' : 'fcsd-badge fcsd-badge--parent';
        echo '<span class="' . esc_attr($class) . '">' . esc_html($label) . '</span>';
        return;
    }

    if ($column === 'fcsd_event_parent') {
        $parent_id = (int) wp_get_post_parent_id($post_id);
        if ($parent_id > 0) {
            $title = get_the_title($parent_id);
            echo '<span class="fcsd-event-parent-label" data-parent-id="' . esc_attr($parent_id) . '">' . esc_html($title) . '</span>';
        } else {
            echo '<span class="fcsd-event-parent-label" data-parent-id="0">&mdash;</span>';
        }
        return;
    }

}, 10, 2);

/**
 * Quick Edit: selector de post pare.
 */
add_action('quick_edit_custom_box', function($column_name, $post_type) {
    if ($post_type !== 'event' || $column_name !== 'fcsd_event_parent') {
        return;
    }

    // Padres: events top-level (post_parent=0)
    $parents = get_posts([
        'post_type'      => 'event',
        'post_status'    => ['publish','draft','pending','private'],
        'posts_per_page' => -1,
        'post_parent'    => 0,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'fields'         => 'ids',
    ]);

    echo '<fieldset class="inline-edit-col-right"><div class="inline-edit-col">';
    echo '<label class="inline-edit-group">';
    echo '<span class="title">' . esc_html__('Pare', 'fcsd') . '</span>';
    echo '<select name="fcsd_event_parent" class="fcsd-event-parent-select">';
    echo '<option value="0">' . esc_html__('— Cap (és pare) —', 'fcsd') . '</option>';
    foreach ($parents as $pid) {
        $t = get_the_title($pid);
        echo '<option value="' . esc_attr($pid) . '">' . esc_html($t) . '</option>';
    }
    echo '</select>';
    echo '</label>';
    echo '</div></fieldset>';
}, 10, 2);

/**
 * Guardar post_parent desde Quick Edit.
 */
add_action('save_post_event', function($post_id) {
    // Quick edit envía editpost nonce, no nuestro.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (!isset($_POST['fcsd_event_parent'])) {
        return;
    }

    $parent_id = (int) $_POST['fcsd_event_parent'];

    // Evitar auto-parent (invalido)
    if ($parent_id === (int) $post_id) {
        $parent_id = 0;
    }

    // Solo permitir padres del mismo CPT
    if ($parent_id > 0) {
        $p = get_post($parent_id);
        if (!$p || $p->post_type !== 'event') {
            $parent_id = 0;
        }
    }

    // Solo actualizar si cambió
    $current = (int) wp_get_post_parent_id($post_id);
    if ($current !== $parent_id) {
        wp_update_post([
            'ID'          => $post_id,
            'post_parent' => $parent_id,
        ]);
    }
}, 20);

/**
 * Assets de admin: estilos de badge + JS de Quick Edit.
 */
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'edit.php') return;

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'event') return;

    wp_enqueue_style(
        'fcsd-event-admin-badges',
        get_template_directory_uri() . '/assets/css/admin-event-badges.css',
        [],
        defined('FCSD_VERSION') ? FCSD_VERSION : '1.0.0'
    );

    wp_enqueue_script(
        'fcsd-event-admin-quickedit',
        get_template_directory_uri() . '/assets/js/admin-event-quickedit.js',
        ['jquery', 'inline-edit-post'],
        defined('FCSD_VERSION') ? FCSD_VERSION : '1.0.0',
        true
    );
});
