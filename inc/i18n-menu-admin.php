<?php
/**
 * FCSD i18n menu admin
 * - Añade campos de traducción para títulos de ítems de menú (nav_menu_item)
 * - Guarda en postmeta: _fcsd_i18n_title_{lang}
 */
defined('ABSPATH') || exit;

// Render fields in Appearance > Menus
add_action('wp_nav_menu_item_custom_fields', function($item_id, $item, $depth, $args){
    if ( ! is_admin() ) return;
    if ( ! defined('FCSD_LANGUAGES') || ! defined('FCSD_DEFAULT_LANG') ) return;

    $langs = array_keys(FCSD_LANGUAGES);
    foreach ($langs as $lang) {
        if ($lang === FCSD_DEFAULT_LANG) continue;

        $value = (string) get_post_meta((int)$item_id, '_fcsd_i18n_title_' . $lang, true);
        ?>
        <p class="description description-wide">
            <label for="edit-menu-item-fcsd-i18n-title-<?php echo esc_attr($lang); ?>-<?php echo esc_attr($item_id); ?>">
                <?php echo esc_html( sprintf( __('Títol (%s)', 'fcsd'), strtoupper($lang) ) ); ?><br>
                <input type="text"
                       id="edit-menu-item-fcsd-i18n-title-<?php echo esc_attr($lang); ?>-<?php echo esc_attr($item_id); ?>"
                       class="widefat code edit-menu-item-custom"
                       name="menu-item-fcsd-i18n-title-<?php echo esc_attr($lang); ?>[<?php echo esc_attr($item_id); ?>]"
                       value="<?php echo esc_attr($value); ?>">
            </label>
        </p>
        <?php
    }
}, 10, 4);


// Save fields
add_action('wp_update_nav_menu_item', function($menu_id, $menu_item_db_id, $args){
    if ( ! is_admin() ) return;
    if ( ! defined('FCSD_LANGUAGES') || ! defined('FCSD_DEFAULT_LANG') ) return;

    $langs = array_keys(FCSD_LANGUAGES);
    foreach ($langs as $lang) {
        if ($lang === FCSD_DEFAULT_LANG) continue;

        $key = 'menu-item-fcsd-i18n-title-' . $lang;
        if ( ! isset($_POST[$key]) || ! is_array($_POST[$key]) ) continue;

        $raw = $_POST[$key][$menu_item_db_id] ?? '';
        $title = sanitize_text_field( (string) wp_unslash($raw) );

        if ( $title !== '' ) {
            update_post_meta((int)$menu_item_db_id, '_fcsd_i18n_title_' . $lang, $title);
        } else {
            delete_post_meta((int)$menu_item_db_id, '_fcsd_i18n_title_' . $lang);
        }
    }
}, 10, 3);
