<?php
/**
 * FCSD i18n: canonical redirects
 *
 * Para un sistema de idiomas por prefijo (/es/, /en/), WordPress puede intentar
 * "canonizar" URLs y eliminar segmentos que no reconoce, provocando 301 al idioma
 * por defecto o a rutas sin prefijo.
 *
 * Esta capa desactiva redirect_canonical cuando detecta un prefijo de idioma.
 */
defined('ABSPATH') || exit;

add_filter('redirect_canonical', function($redirect_url, $requested_url){
    // Obtén el path solicitado de forma robusta
    $path = '';
    if (!empty($requested_url)) {
        $path = (string) parse_url($requested_url, PHP_URL_PATH);
    }
    if ($path === '' && !empty($_SERVER['REQUEST_URI'])) {
        $path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    $path = trim($path, '/');
    if ($path === '') return $redirect_url;

    $first = explode('/', $path)[0] ?? '';
    if ($first && defined('FCSD_LANGUAGES') && isset(FCSD_LANGUAGES[$first])) {
        // No canonices cuando hay prefijo de idioma
        return false;
    }
    return $redirect_url;
}, 10, 2);
