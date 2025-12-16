<?php
/**
 * FCSD i18n admin
 * - Metabox para gestionar traducciones sin plugins
 * - Guarda en postmeta: _fcsd_i18n_title_{lang}, _fcsd_i18n_content_{lang}, _fcsd_i18n_slug_{lang}
 */
defined('ABSPATH') || exit;

/**
 * Qué post types soportamos.
 * - Páginas + posts + CPTs públicos (incluye product si existe)
 */
function fcsd_i18n_supported_post_types(): array {
    $types = get_post_types([ 'public' => true ], 'names');
    // Eliminamos adjuntos por defecto.
    unset($types['attachment']);
    return array_values($types);
}

add_action('add_meta_boxes', function(){
    if ( ! is_admin() ) return;

    foreach ( fcsd_i18n_supported_post_types() as $pt ) {
        add_meta_box(
            'fcsd_i18n_box',
            __('Traducciones (FCSD)', 'fcsd'),
            'fcsd_i18n_render_metabox',
            $pt,
            'normal',
            'high'
        );
    }
});

function fcsd_i18n_render_metabox(WP_Post $post): void {
    wp_nonce_field('fcsd_i18n_save', 'fcsd_i18n_nonce');

    $default = defined('FCSD_DEFAULT_LANG') ? FCSD_DEFAULT_LANG : 'ca';
    $langs = defined('FCSD_LANGUAGES') ? array_keys(FCSD_LANGUAGES) : [$default];

    echo '<p style="margin-top:0">' . esc_html__('Rellena sólo los idiomas que quieras. Si un campo está vacío, se usa el contenido por defecto.', 'fcsd') . '</p>';

    foreach ($langs as $lang) {
        if ($lang === $default) continue;

        $t = (string) get_post_meta($post->ID, '_fcsd_i18n_title_' . $lang, true);
        $c = (string) get_post_meta($post->ID, '_fcsd_i18n_content_' . $lang, true);
        $s = (string) get_post_meta($post->ID, '_fcsd_i18n_slug_' . $lang, true);

        echo '<hr style="margin:18px 0">';
        echo '<h3 style="margin:0 0 10px 0">' . esc_html(strtoupper($lang)) . '</h3>';

        echo '<p style="margin:0 0 8px 0"><label><strong>' . esc_html__('Slug', 'fcsd') . '</strong></label></p>';
        echo '<input type="text" style="width:100%" name="fcsd_i18n_slug_' . esc_attr($lang) . '" value="' . esc_attr($s) . '" placeholder="' . esc_attr__('p. ej. tienda, shop…', 'fcsd') . '">';

        echo '<p style="margin:14px 0 8px 0"><label><strong>' . esc_html__('Título', 'fcsd') . '</strong></label></p>';
        echo '<input type="text" style="width:100%" name="fcsd_i18n_title_' . esc_attr($lang) . '" value="' . esc_attr($t) . '">';

        echo '<p style="margin:14px 0 8px 0"><label><strong>' . esc_html__('Contenido', 'fcsd') . '</strong></label></p>';
        // Editor simple (rápido y compatible). Para rendimiento/UX, evitamos wp_editor en cada idioma.
        echo '<textarea style="width:100%;min-height:160px" name="fcsd_i18n_content_' . esc_attr($lang) . '">' . esc_textarea($c) . '</textarea>';
    }
}


add_action('save_post', function($post_id){
    if ( ! is_admin() ) return;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision($post_id) ) return;
    if ( ! isset($_POST['fcsd_i18n_nonce']) || ! wp_verify_nonce((string)$_POST['fcsd_i18n_nonce'], 'fcsd_i18n_save') ) return;

    $default = defined('FCSD_DEFAULT_LANG') ? FCSD_DEFAULT_LANG : 'ca';
    $langs = defined('FCSD_LANGUAGES') ? array_keys(FCSD_LANGUAGES) : [$default];

    foreach ($langs as $lang) {
        if ($lang === $default) continue;

        // slug
        $slug_key = 'fcsd_i18n_slug_' . $lang;
        if ( array_key_exists($slug_key, $_POST) ) {
            $slug = sanitize_title( (string) wp_unslash($_POST[$slug_key]) );
            if ( $slug !== '' ) {
                update_post_meta($post_id, '_fcsd_i18n_slug_' . $lang, $slug);
            } else {
                delete_post_meta($post_id, '_fcsd_i18n_slug_' . $lang);
            }
        }

        // title
        $title_key = 'fcsd_i18n_title_' . $lang;
        if ( array_key_exists($title_key, $_POST) ) {
            $title = sanitize_text_field( (string) wp_unslash($_POST[$title_key]) );
            if ( $title !== '' ) {
                update_post_meta($post_id, '_fcsd_i18n_title_' . $lang, $title);
            } else {
                delete_post_meta($post_id, '_fcsd_i18n_title_' . $lang);
            }
        }

        // content
        $content_key = 'fcsd_i18n_content_' . $lang;
        if ( array_key_exists($content_key, $_POST) ) {
            $content = (string) wp_unslash($_POST[$content_key]);
            // Permitimos HTML como el contenido normal.
            $content = wp_kses_post($content);
            if ( trim($content) !== '' ) {
                update_post_meta($post_id, '_fcsd_i18n_content_' . $lang, $content);
            } else {
                delete_post_meta($post_id, '_fcsd_i18n_content_' . $lang);
            }
        }
    }
}, 10);
