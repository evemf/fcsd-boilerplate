<?php
/**
 * Helpers para contenido multidioma (APIs / arrays normalizados).
 */
defined('ABSPATH') || exit;

/**
 * Devuelve el valor por idioma con fallback al idioma por defecto.
 * Espera arrays del tipo: ['ca' => '...', 'es' => '...', 'en' => '...']
 */
function fcsd_t(array $field, string $fallback = ''): string {
    $v = $field[FCSD_LANG] ?? $field[FCSD_DEFAULT_LANG] ?? $fallback;
    return is_string($v) ? $v : $fallback;
}


function fcsd_get_post_i18n(int $post_id, string $field): ?string {
    if ( FCSD_LANG === FCSD_DEFAULT_LANG ) return null;

    $key = '_fcsd_i18n_' . $field . '_' . FCSD_LANG;
    $v = get_post_meta($post_id, $key, true);
    if ( is_string($v) && $v !== '' ) return $v;

    return null;
}

// Título traducible por meta (_fcsd_i18n_title_es|en)
add_filter('the_title', function($title, $post_id){
    if ( is_admin() ) return $title;
    if ( ! $post_id ) return $title;
    $t = fcsd_get_post_i18n((int)$post_id, 'title');
    return $t ?? $title;
}, 5, 2);

// Contenido traducible por meta (_fcsd_i18n_content_es|en)
add_filter('the_content', function($content){
    if ( is_admin() ) return $content;
    $post_id = get_the_ID();
    if ( ! $post_id ) return $content;
    $c = fcsd_get_post_i18n((int)$post_id, 'content');
    return $c ?? $content;
}, 1);
