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
