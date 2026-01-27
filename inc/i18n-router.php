<?php
/**
 * FCSD i18n router
 * - Quita el prefijo de idioma del request antes de que WP resuelva rewrites
 * - Traduce slugs entrantes (es/en) al slug canónico (ca) para que los rewrites funcionen
 */
defined('ABSPATH') || exit;

add_filter('query_vars', function($vars){
    $vars[] = 'fcsd_lang';
    return $vars;
});


/**
 * Devuelve el ID de la primera página publicada que use un template concreto.
 */
function fcsd_get_page_id_by_template(string $template): int {
    $found = get_posts([
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'meta_key'       => '_wp_page_template',
        'meta_value'     => $template,
        'fields'         => 'ids',
        'posts_per_page' => 1,
        'no_found_rows'  => true,
    ]);
    return !empty($found[0]) ? (int)$found[0] : 0;
}

/**
 * Devuelve el ID “real” de una página de sistema.
 *
 * En instalaciones que vienen de versiones anteriores del tema, el slug canónico
 * puede no coincidir con el mapa actual (p.ej. `perfil-usuari`). Además, puede
 * faltar el meta _fcsd_i18n_slug_{lang} si no se ejecutó el hook de activación.
 */
function fcsd_get_system_page_id(string $key, string $template): int {
    $pid = fcsd_get_page_id_by_template($template);
    if ($pid) return $pid;

    // Fallback: slug canónico (ca) según mapa.
    if (function_exists('fcsd_default_slug')) {
        $slug_ca = fcsd_default_slug($key);
        $p = get_page_by_path($slug_ca, OBJECT, 'page');
        if ($p instanceof WP_Post) return (int) $p->ID;
    }

    // Legacy conocidos.
    $legacy = [];
    if ($key === 'profile')  $legacy = ['perfil-usuari','perfil-usuario'];
    if ($key === 'login')    $legacy = ['iniciar-sessio','iniciar-sesion'];
    if ($key === 'register') $legacy = ['registrar'];
    foreach ($legacy as $ls) {
        $p = get_page_by_path($ls, OBJECT, 'page');
        if ($p instanceof WP_Post) return (int) $p->ID;
    }

    return 0;
}
add_action('parse_request', function($wp){
    if ( is_admin() ) return;

    $req = ltrim((string) $wp->request, '/'); // p.ej: "es/noticias/foo"
    if ($req === '') return;

    $parts = explode('/', $req);
    $lang = $parts[0] ?? '';
    if ( ! $lang || ! isset(FCSD_LANGUAGES[$lang]) ) return;

    // Guardamos el idioma para lecturas posteriores
    $wp->query_vars['fcsd_lang'] = $lang;

    // Quitamos el prefijo de idioma del request
    array_shift($parts);

    // Si el primer slug tras el idioma es una traducción, lo mapeamos al slug canónico (ca)
    if ( ! empty($parts[0]) ) {
        $maybe = $parts[0];
        $key = fcsd_slug_key_from_translated($maybe);
        if ( $key ) {
            $parts[0] = fcsd_default_slug($key);
        }
    }

    $wp->request = implode('/', array_filter($parts, fn($p)=>$p!=='' ));
}, 0);


add_filter('request', function($qv){
    if ( is_admin() ) return $qv;
    if ( FCSD_LANG === FCSD_DEFAULT_LANG ) return $qv;

    $lang = FCSD_LANG;

    /**
     * Normalización crítica del routing.
     *
     * WordPress resuelve rewrites ANTES de disparar el action `parse_request`.
     * Para URLs tipo `/es/` o `/en/`, WP suele interpretar el request como un
     * `pagename`/`name` igual al propio código de idioma ("es"/"en").
     *
     * Si no existe una página/post con ese slug, WP acaba en 404 y puede
     * ejecutar `redirect_guess_404_permalink()`, provocando redirecciones
     * “raras” hacia cualquier contenido existente.
     *
     * Aquí corregimos el query vars para que `/es/` y `/en/` sean HOME real y
     * para que `/es/algo` se resuelva como `algo`.
     */
    $path = '/';
    if ( isset($_SERVER['REQUEST_URI']) ) {
        $path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }
    $path = trim($path, '/');

    // Caso exacto: /es o /en -> HOME (sin query vars)
    if ( $path === $lang ) {
        return [];
    }

    // Caso: WP ha resuelto pagename/name incluyendo el prefijo (es/foo)
    // o el propio idioma (es). Lo limpiamos.
    foreach (['pagename','name'] as $k) {
        if ( ! empty($qv[$k]) && is_string($qv[$k]) ) {
            if ( $qv[$k] === $lang ) {
                unset($qv[$k]);
                continue;
            }
            $prefix = $lang . '/';
            if ( $prefix !== '' && strpos($qv[$k], $prefix) === 0 ) {
                $qv[$k] = substr($qv[$k], strlen($prefix));
            }
        }
    }

    
    // ------------------------------------------------------------------
    // Páginas de sistema (perfil/login/registro)
    // ------------------------------------------------------------------
    // Garantiza que las URLs públicas (/es/registro, /en/register, etc.)
    // resuelvan al template correcto aunque:
    // - el slug real en BD sea distinto (legacy)
    // - falten los metadatos i18n
    // - WP haya resuelto mal el pagename
    $system = [
        'profile'  => 'page-profile.php',
        'login'    => 'page-login.php',
        'register' => 'page-register.php',
        // Ecommerce
        'cart'     => 'page-cart.php',
        'checkout' => 'page-checkout.php',
        'shop'     => 'archive-fcsd_product.php',
    ];

    // Detectar slug del request sin prefijo idioma
    $req_slug = '';
    if (isset($_SERVER['REQUEST_URI'])) {
        $p = trim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $parts = $p === '' ? [] : explode('/', $p);
        if (!empty($parts[0]) && isset(FCSD_LANGUAGES[$parts[0]])) {
            array_shift($parts);
        }
        $req_slug = $parts[0] ?? '';
    }

    $pn = '';
    if (!empty($qv['pagename']) && is_string($qv['pagename'])) $pn = $qv['pagename'];
    if ($pn === '' && $req_slug !== '') $pn = $req_slug;

    if ($pn !== '' && function_exists('fcsd_slug')) {
        foreach ($system as $key => $tpl) {
            $slugs = array_unique(array_filter([
                fcsd_slug($key, $lang),
                fcsd_default_slug($key),
                // Legacy perfil
                ($key==='profile' ? 'perfil-usuari' : ''),
                ($key==='profile' ? 'perfil-usuario' : ''),
            ]));

            if (in_array($pn, $slugs, true)) {
                // Para templates no-page (archive), solo reescribimos el pagename.
                if (strpos($tpl, 'page-') === 0) {
                    $pid = fcsd_get_system_page_id($key, $tpl);
                    if ($pid) {
                        $qv['page_id'] = $pid;
                        unset($qv['pagename'], $qv['name']);
                    }
                } else {
                    // shop archive
                    if ($key === 'shop') {
                        $qv['post_type'] = 'fcsd_product';
                        unset($qv['pagename'], $qv['name']);
                    }
                }
                if ($pid) {
                    $qv['page_id'] = $pid;
                    unset($qv['pagename'], $qv['name']);
                }
                break;
            }
        }
    }

// Páginas: pagename
    if ( ! empty($qv['pagename']) && is_string($qv['pagename']) ) {
        $slug = $qv['pagename'];
        $found = get_posts([
            'post_type'   => 'page',
            'post_status' => 'publish',
            'meta_key'    => '_fcsd_i18n_slug_' . $lang,
            'meta_value'  => $slug,
            'fields'      => 'ids',
            'numberposts' => 1,
        ]);
        if ( ! empty($found[0]) ) {
            $canonical = get_post_field('post_name', (int)$found[0]);
            if ( $canonical ) $qv['pagename'] = $canonical;
        }
    }

    // Entradas/CPT: name
    if ( ! empty($qv['name']) && is_string($qv['name']) ) {
        $slug = $qv['name'];
        $found = get_posts([
            'post_type'   => 'any',
            'post_status' => 'publish',
            'meta_key'    => '_fcsd_i18n_slug_' . $lang,
            'meta_value'  => $slug,
            'fields'      => 'ids',
            'numberposts' => 1,
        ]);
        if ( ! empty($found[0]) ) {
            $canonical = get_post_field('post_name', (int)$found[0]);
            if ( $canonical ) $qv['name'] = $canonical;
        }
    }

    return $qv;
}, 20);


/**
 * Redirects 301 de slugs legacy (p.ej. /perfil-usuari/) a la URL pública traducida.
 */
add_action('template_redirect', function(){
    if ( is_admin() || wp_doing_ajax() ) return;
    $path = isset($_SERVER['REQUEST_URI']) ? (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
    $path = trim($path, '/');
    if ($path === '') return;

    $parts = explode('/', $path);
    $lang = FCSD_DEFAULT_LANG;
    if (!empty($parts[0]) && isset(FCSD_LANGUAGES[$parts[0]])) {
        $lang = $parts[0];
        array_shift($parts);
    }
    $first = $parts[0] ?? '';
    if ($first === '') return;

    $legacy = ['perfil-usuari','perfil-usuario'];
    if (in_array($first, $legacy, true)) {
        $target = fcsd_slug('profile', $lang);
        $home = rtrim((string) get_option('home'), '/');
        $to = $home . ($lang===FCSD_DEFAULT_LANG ? '' : '/' . $lang) . '/' . $target . '/';
        wp_safe_redirect($to, 301);
        exit;
    }
});
