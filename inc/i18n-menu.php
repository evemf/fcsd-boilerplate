<?php
/**
 * FCSD i18n menu
 * - Traduce títulos de ítems de menú (nav_menu_item) usando el mismo metabox i18n.
 * - Asegura que las URLs del menú llevan el prefijo de idioma y slugs base traducidos.
 */
defined('ABSPATH') || exit;

add_filter('wp_nav_menu_objects', function($items){
    if ( is_admin() ) return $items;
    if ( ! is_array($items) || empty($items) ) return $items;
    $lang = function_exists('fcsd_lang') ? fcsd_lang() : ( defined('FCSD_LANG') ? FCSD_LANG : FCSD_DEFAULT_LANG );

    foreach ($items as $item) {
        if ( ! ($item instanceof WP_Post) ) continue;

        // 0) Recalcular URL cuando el ítem apunta a un objeto WP (page/post/term)
        // Esto evita que el menú “se quede” con permalinks del idioma por defecto.
        // get_permalink/get_term_link ya pasan por nuestros filtros de i18n (slugs + prefijo).
        if ( ! empty($item->object_id) && ! empty($item->type) ) {
            $oid = (int) $item->object_id;
            if ( $oid > 0 ) {
                if ( $item->type === 'post_type' ) {
                    $perma = get_permalink($oid);
                    if ( $perma ) $item->url = $perma;
                } elseif ( $item->type === 'taxonomy' && ! empty($item->object) ) {
                    $link = get_term_link($oid, (string)$item->object);
                    if ( ! is_wp_error($link) ) $item->url = $link;
                }
            }
        }

        // 1) Título del menú (se guarda en el propio nav_menu_item)
        if ( $lang !== FCSD_DEFAULT_LANG ) {
            $t = get_post_meta((int)$item->ID, '_fcsd_i18n_title_' . $lang, true);
            if ( is_string($t) && $t !== '' ) {
                $item->title = $t;
            }
        }

        // 2) URL: añadir prefijo y traducir primer segmento si aplica (para URLs custom)
        if ( ! empty($item->url) && is_string($item->url) ) {
            $item->url = fcsd_add_lang_to_url($item->url);
        }
    }

    return $items;
}, 20);
