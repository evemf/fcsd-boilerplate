<?php
add_action('init', function(){

    register_post_type('service', [
        'label' => __('Services', 'fcsd'),
        'public' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-hammer',
        'supports' => ['title','editor','thumbnail','excerpt','page-attributes'],
        'has_archive' => true,
        'rewrite' => ['slug' => 'serveis']
    ]);

    register_taxonomy('service_area', 'service', [
        'label' => __('Service Areas', 'fcsd'),
        'public' => true,
        'hierarchical' => true,
        'rewrite' => ['slug' => 'area']
    ]);

    register_post_type('product', [
        'label' => __('Products', 'fcsd'),
        'public' => true,
        'menu_position' => 7,
        'menu_icon' => 'dashicons-cart',
        'supports' => ['title','editor','thumbnail','excerpt','custom-fields','page-attributes'],
        'has_archive' => true,
        'rewrite' => ['slug' => 'shop']
    ]);

    register_post_type('news', [
        'label' => __('News', 'fcsd'),
        'public' => true,
        'menu_position' => 8,
        'menu_icon' => 'dashicons-megaphone',
        'supports' => ['title','editor','thumbnail','excerpt','page-attributes'],
        'has_archive' => true,
        'rewrite' => ['slug' => 'actualitat']
    ]);

    register_post_type('transparency', [
        'label' => __('Transparency', 'fcsd'),
        'public' => true,
        'menu_position' => 9,
        'menu_icon' => 'dashicons-visibility',
        'supports' => ['title','editor','thumbnail','excerpt','page-attributes'],
        'has_archive' => true,
        'rewrite' => ['slug' => 'transparencia']
    ]);

    register_post_type('event', [
        'label'         => __('Formacions i esdeveniments', 'fcsd'),
        'labels'        => [
            'name'          => __('Formacions i esdeveniments', 'fcsd'),
            'singular_name' => __('Esdeveniment', 'fcsd'),
            'add_new'       => __('Afegir esdeveniment', 'fcsd'),
            'add_new_item'  => __('Afegir nou esdeveniment', 'fcsd'),
            'edit_item'     => __('Editar esdeveniment', 'fcsd'),
        ],
        'public'        => true,
        'show_in_menu'  => true,
        'menu_position' => 8,
        'menu_icon'     => 'dashicons-calendar-alt',
        'supports'      => ['title','editor','excerpt','thumbnail'],
        'has_archive'   => true,
        'rewrite'       => ['slug' => 'formacions-i-events'],
        'show_in_rest'  => true,
    ]);

    register_taxonomy('event_formation', 'event', [
        'label'             => __('Formacions', 'fcsd'),
        'public'            => true,
        'hierarchical'      => false,
        'show_admin_column' => true,
        'rewrite'           => ['slug' => 'formacions'],
        'show_in_rest'      => true,
    ]);

    // CPT interno para PERSONA (membres del Patronat)
    register_post_type('persona', [
        'label'         => __('Membres del Patronat', 'fcsd'),
        'labels'        => [
            'name'          => __('Membres del Patronat', 'fcsd'),
            'singular_name' => __('Membre del Patronat', 'fcsd'),
            'add_new'       => __('Afegir membre', 'fcsd'),
            'add_new_item'  => __('Afegir membre del Patronat', 'fcsd'),
            'edit_item'     => __('Editar membre del Patronat', 'fcsd'),
        ],

        // Interno: sin URLs ni archivo; sólo para gestionar personas desde el admin
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => false,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
        'has_archive'         => false,
        'rewrite'             => false,

        'menu_position'       => 8,
        'menu_icon'           => 'dashicons-groups',
        'supports'            => ['title','editor','thumbnail','excerpt','page-attributes'],
        'show_in_rest'        => true,
    ]);

    // Cronologia: anys (menú principal "Cronologia")
    register_post_type( 'timeline_year', [
        'label'               => __( 'Anys cronologia', 'fcsd' ),
        'labels'              => [
            'name'          => __( 'Anys cronologia', 'fcsd' ),
            'singular_name' => __( 'Any', 'fcsd' ),
            'add_new_item'  => __( 'Afegir any', 'fcsd' ),
            'edit_item'     => __( 'Editar any', 'fcsd' ),
            'new_item'      => __( 'Nou any', 'fcsd' ),
            'menu_name'     => __( 'Cronologia', 'fcsd' ), // texto del menú principal
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true, // top level
        'menu_icon'           => 'dashicons-calendar-alt',
        'supports'            => [ 'title' ], // el títol serà "1982", "1983", ...
        'has_archive'         => false,
        'rewrite'             => false,
        'show_in_rest'        => true,
    ] );

    // Cronologia: esdeveniments (submenú dentro de "Cronologia")
    register_post_type( 'timeline_event', [
        'label'               => __( 'Esdeveniments cronologia', 'fcsd' ),
        'labels'              => [
            'name'               => __( 'Esdeveniments cronologia', 'fcsd' ),
            'singular_name'      => __( 'Esdeveniment', 'fcsd' ),
            'add_new_item'       => __( 'Afegir esdeveniment', 'fcsd' ),
            'edit_item'          => __( 'Editar esdeveniment', 'fcsd' ),
            'new_item'           => __( 'Nou esdeveniment', 'fcsd' ),
            'view_item'          => __( 'Veure esdeveniment', 'fcsd' ),
            'search_items'       => __( 'Cercar esdeveniments', 'fcsd' ),
            'not_found'          => __( 'Cap esdeveniment trobat', 'fcsd' ),
            'not_found_in_trash' => __( 'Cap esdeveniment a la paperera', 'fcsd' ),
            'menu_name'          => __( 'Esdeveniments', 'fcsd' ), // texto del submenú
        ],
        'public'              => false, // no single.php ni arxiu públic
        'show_ui'             => true,
        // lo colgamos bajo el menú de timeline_year (Cronologia)
        'show_in_menu'        => 'edit.php?post_type=timeline_year',
        'menu_icon'           => 'dashicons-backup',
        'supports'            => [ 'title', 'editor', 'excerpt', 'page-attributes' ],
        'has_archive'         => false,
        'rewrite'             => false,
        'show_in_rest'        => true,
    ] );

});

// -------------------------------
// Metabox: any associat a l'esdeveniment
// -------------------------------
function fcsd_timeline_event_add_metabox() {
    add_meta_box(
        'fcsd_timeline_event_year',
        __( 'Any de la cronologia', 'fcsd' ),
        'fcsd_timeline_event_year_metabox_cb',
        'timeline_event',
        'side',
        'high'
    );
}
add_action( 'add_meta_boxes_timeline_event', 'fcsd_timeline_event_add_metabox' );

function fcsd_timeline_event_year_metabox_cb( $post ) {
    wp_nonce_field( 'fcsd_timeline_event_year_save', 'fcsd_timeline_event_year_nonce' );

    $selected_year_id = (int) get_post_meta( $post->ID, 'timeline_year_id', true );

    $years = get_posts( [
        'post_type'        => 'timeline_year',
        'post_status'      => 'publish',
        'numberposts'      => -1,
        'orderby'          => 'title',
        'order'            => 'ASC',
        'suppress_filters' => false,
    ] );
    ?>
    <p>
        <label for="timeline_year_id">
            <?php esc_html_e( 'Selecciona l\'any d\'aquest esdeveniment', 'fcsd' ); ?>
        </label>
    </p>
    <p>
        <select name="timeline_year_id" id="timeline_year_id" class="widefat">
            <option value="0"><?php esc_html_e( '— Sense any —', 'fcsd' ); ?></option>
            <?php foreach ( $years as $year ) : ?>
                <option value="<?php echo esc_attr( $year->ID ); ?>" <?php selected( $selected_year_id, $year->ID ); ?>>
                    <?php echo esc_html( get_the_title( $year ) ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <?php
}

function fcsd_timeline_event_year_save( $post_id ) {
    // Autosave / permisos / nonce
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! isset( $_POST['fcsd_timeline_event_year_nonce'] ) ||
         ! wp_verify_nonce( $_POST['fcsd_timeline_event_year_nonce'], 'fcsd_timeline_event_year_save' ) ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['timeline_year_id'] ) ) {
        $year_id = (int) $_POST['timeline_year_id'];

        if ( $year_id > 0 ) {
            update_post_meta( $post_id, 'timeline_year_id', $year_id );
        } else {
            delete_post_meta( $post_id, 'timeline_year_id' );
        }
    }
}
add_action( 'save_post_timeline_event', 'fcsd_timeline_event_year_save' );


// METABOXES PARA PERSONA (PATRONAT)

function fcsd_persona_add_metaboxes() {
    add_meta_box(
        'fcsd_persona_details',
        __('Detalls del Patronat', 'fcsd'),
        'fcsd_persona_details_metabox_callback',
        'persona',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'fcsd_persona_add_metaboxes');

function fcsd_persona_details_metabox_callback($post) {
    wp_nonce_field('fcsd_persona_details_save', 'fcsd_persona_details_nonce');

    $cargo       = get_post_meta($post->ID, 'persona_cargo', true);
    $linkedin    = get_post_meta($post->ID, 'persona_linkedin', true);
    $otros_links = get_post_meta($post->ID, 'persona_otros_links', true);
    ?>
    <p>
        <label for="persona_cargo"><strong><?php _e('Càrrec al Patronat', 'fcsd'); ?></strong></label><br>
        <input type="text"
               name="persona_cargo"
               id="persona_cargo"
               class="widefat"
               value="<?php echo esc_attr($cargo); ?>"
               placeholder="<?php esc_attr_e('Presidència, Vicepresidència, Tresoreria, Vocal…', 'fcsd'); ?>">
    </p>

    <p>
        <label for="persona_linkedin"><strong><?php _e('Enllaç a LinkedIn', 'fcsd'); ?></strong></label><br>
        <input type="url"
               name="persona_linkedin"
               id="persona_linkedin"
               class="widefat"
               value="<?php echo esc_url($linkedin); ?>"
               placeholder="https://www.linkedin.com/in/...">
    </p>

    <p>
        <label for="persona_otros_links"><strong><?php _e('Altres enllaços (un per línia)', 'fcsd'); ?></strong></label><br>
        <textarea name="persona_otros_links"
                  id="persona_otros_links"
                  class="widefat"
                  rows="3"
                  placeholder="<?php esc_attr_e("https://exemple.com\nhttps://twitter.com/...", 'fcsd'); ?>"><?php
            echo esc_textarea($otros_links);
        ?></textarea>
    </p>
    <?php
}

function fcsd_persona_details_save($post_id) {
    // nonce
    if (
        ! isset($_POST['fcsd_persona_details_nonce']) ||
        ! wp_verify_nonce($_POST['fcsd_persona_details_nonce'], 'fcsd_persona_details_save')
    ) {
        return;
    }

    // autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // permisos
    if (isset($_POST['post_type']) && 'persona' === $_POST['post_type']) {
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    // Cargo
    if (isset($_POST['persona_cargo'])) {
        update_post_meta($post_id, 'persona_cargo', sanitize_text_field($_POST['persona_cargo']));
    }

    // LinkedIn
    if (isset($_POST['persona_linkedin'])) {
        update_post_meta($post_id, 'persona_linkedin', esc_url_raw($_POST['persona_linkedin']));
    }

    // Otros links
    if (isset($_POST['persona_otros_links'])) {
        update_post_meta($post_id, 'persona_otros_links', sanitize_textarea_field($_POST['persona_otros_links']));
    }
}
add_action('save_post_persona', 'fcsd_persona_details_save');


// -------------------------------
// Metabox: News -> mostrar al carrusel de la Home
// -------------------------------

function fcsd_news_home_carousel_add_metabox() {
    add_meta_box(
        'fcsd_news_home_carousel',
        __("Carrusel de la pàgina d'inici", 'fcsd'),
        'fcsd_news_home_carousel_metabox_cb',
        'news',
        'side',
        'high'
    );
}
add_action('add_meta_boxes_news', 'fcsd_news_home_carousel_add_metabox');

function fcsd_news_home_carousel_metabox_cb($post) {
    wp_nonce_field('fcsd_news_home_carousel_save', 'fcsd_news_home_carousel_nonce');

    $checked = (get_post_meta($post->ID, '_fcsd_show_in_home_carousel', true) === '1');
    ?>
    <p style="margin:0 0 8px 0;">
        <?php esc_html_e('Mostrar esta notícia al carrusel de la pàgina d\'inici', 'fcsd'); ?>
    </p>
    <label style="display:flex; gap:8px; align-items:center;">
        <input type="checkbox" name="fcsd_show_in_home_carousel" value="1" <?php checked($checked); ?> />
        <?php esc_html_e('Sí, mostrar', 'fcsd'); ?>
    </label>
    <?php
}

function fcsd_news_home_carousel_save($post_id) {
    // Autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Nonce
    if (
        !isset($_POST['fcsd_news_home_carousel_nonce']) ||
        !wp_verify_nonce($_POST['fcsd_news_home_carousel_nonce'], 'fcsd_news_home_carousel_save')
    ) {
        return;
    }

    // Permisos
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    update_post_meta(
        $post_id,
        '_fcsd_show_in_home_carousel',
        isset($_POST['fcsd_show_in_home_carousel']) ? '1' : '0'
    );
}
add_action('save_post_news', 'fcsd_news_home_carousel_save');

// -------------------------------------------
// Admin list: columna con checkbox (News -> Home carousel)
// -------------------------------------------

// 1) Añadir columna
add_filter('manage_edit-news_columns', function ($columns) {
    // Inserta la columna antes de "date" si existe
    $new = [];
    foreach ($columns as $key => $label) {
        if ($key === 'date') {
            $new['fcsd_home_carousel'] = __('Home', 'fcsd');
        }
        $new[$key] = $label;
    }
    if (!isset($new['fcsd_home_carousel'])) {
        $new['fcsd_home_carousel'] = __('Home', 'fcsd');
    }
    return $new;
});

// 2) Pintar checkbox
add_action('manage_news_posts_custom_column', function ($column, $post_id) {
    if ($column !== 'fcsd_home_carousel') return;

    $checked = (get_post_meta($post_id, '_fcsd_show_in_home_carousel', true) === '1');
    $nonce = wp_create_nonce('fcsd_toggle_news_carousel_' . $post_id);

    echo '<label style="display:inline-flex;align-items:center;gap:8px;">';
    echo '<input type="checkbox" class="fcsd-news-home-toggle" data-post-id="' . esc_attr($post_id) . '" data-nonce="' . esc_attr($nonce) . '" ' . checked($checked, true, false) . ' />';
    echo '<span class="screen-reader-text">' . esc_html__('Mostrar en Home', 'fcsd') . '</span>';
    echo '</label>';
}, 10, 2);

// 3) AJAX handler para guardar el meta
add_action('wp_ajax_fcsd_toggle_news_home_carousel', function () {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'forbidden'], 403);
    }

    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    $value   = isset($_POST['value']) ? sanitize_text_field($_POST['value']) : '0';
    $nonce   = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';

    if (!$post_id || !wp_verify_nonce($nonce, 'fcsd_toggle_news_carousel_' . $post_id)) {
        wp_send_json_error(['message' => 'bad_nonce'], 400);
    }

    if (get_post_type($post_id) !== 'news') {
        wp_send_json_error(['message' => 'bad_post_type'], 400);
    }

    update_post_meta($post_id, '_fcsd_show_in_home_carousel', ($value === '1') ? '1' : '0');
    wp_send_json_success(['saved' => true]);
});

// 4) JS inline solo en la pantalla del listado de News
add_action('admin_footer-edit.php', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->id !== 'edit-news') return;
    ?>
    <script>
      (function () {
        const els = document.querySelectorAll('.fcsd-news-home-toggle');
        if (!els.length) return;

        els.forEach(cb => {
          cb.addEventListener('change', async (e) => {
            const el = e.currentTarget;
            const postId = el.dataset.postId;
            const nonce  = el.dataset.nonce;
            const value  = el.checked ? '1' : '0';

            el.disabled = true;

            try {
              const form = new URLSearchParams();
              form.set('action', 'fcsd_toggle_news_home_carousel');
              form.set('post_id', postId);
              form.set('nonce', nonce);
              form.set('value', value);

              const res = await fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: form.toString()
              });

              const json = await res.json();
              if (!json || !json.success) {
                // rollback visual
                el.checked = !el.checked;
                alert('No s’ha pogut desar el canvi.');
              }
            } catch (err) {
              el.checked = !el.checked;
              alert('Error de xarxa al desar el canvi.');
            } finally {
              el.disabled = false;
            }
          });
        });
      })();
    </script>
    <?php
});
