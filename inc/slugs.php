<?php
/**
 * Mapa central de slugs traducibles.
 * - La clave es semántica (news, shop, etc.)
 * - Valores por idioma
 */
defined('ABSPATH') || exit;

function fcsd_slug_map(): array {
    return [
        // Secciones principales (para routing + menús)
        'about' => [
            'ca' => 'qui-som',
            'es' => 'quienes-somos',
            'en' => 'about',
        ],
        'patronat' => [
            'ca' => 'patronat',
            'es' => 'patronato',
            'en' => 'board-of-trustees',
        ],
        'organigrama' => [
            'ca' => 'organigrama',
            'es' => 'organigrama',
            'en' => 'organisation-chart',
        ],
        'history' => [
            'ca' => 'historia',
            'es' => 'historia',
            'en' => 'history',
        ],
        'intranet' => [
            'ca' => 'intranet',
            'es' => 'intranet',
            'en' => 'intranet',
        ],
        'offers' => [
            'ca' => 'ofertes',
            'es' => 'ofertas',
            'en' => 'offers',
        ],
        'calendar_actes' => [
            'ca' => 'calendar-actes',
            'es' => 'calendario-actos',
            'en' => 'events-calendar',
        ],
        'calendar_work' => [
            'ca' => 'calendar-work',
            'es' => 'calendario-laboral',
            'en' => 'work-calendar',
        ],
        'memories' => [
            'ca' => 'memories',
            'es' => 'memorias',
            'en' => 'annual-reports',
        ],
        'press' => [
            'ca' => 'premsa',
            'es' => 'prensa',
            'en' => 'press',
        ],
        'volunteering' => [
            'ca' => 'voluntariat',
            'es' => 'voluntariado',
            'en' => 'volunteering',
        ],
        'alliances' => [
            'ca' => 'aliances',
            'es' => 'alianzas',
            'en' => 'partnerships',
        ],
        'services' => [
            'ca' => 'serveis',
            'es' => 'servicios',
            'en' => 'services',
        ],
        'news' => [
            'ca' => 'noticies',
            'es' => 'noticias',
            'en' => 'news',
        ],
        'events' => [
            'ca' => 'formacions-i-esdeveniments',
            'es' => 'formaciones-y-eventos',
            'en' => 'training-and-events',
        ],
        'shop' => [
            'ca' => 'botiga',
            'es' => 'tienda',
            'en' => 'shop',
        ],
        // Segment "product" dins de la botiga (single product):
        //   /botiga/producte/<slug>/
        //   /es/tienda/producto/<slug>/
        //   /en/shop/product/<slug>/
        'shop_product' => [
            'ca' => 'producte',
            'es' => 'producto',
            'en' => 'product',
        ],
        'cart' => [
            'ca' => 'cistella',
            'es' => 'carrito',
            'en' => 'cart',
        ],
        'checkout' => [
            // Mantingut per compatibilitat amb el tema existent.
            'ca' => 'checkout',
            'es' => 'checkout',
            'en' => 'checkout',
        ],
        'my_account' => [
            'ca' => 'mi-cuenta',
            'es' => 'mi-cuenta',
            'en' => 'my-account',
        ],
        'login' => [
            'ca' => 'accedir',
            'es' => 'acceder',
            'en' => 'login',
        ],
        'register' => [
            'ca' => 'registre',
            'es' => 'registro',
            'en' => 'register',
        ],
        'profile' => [
            'ca' => 'perfil',
            'es' => 'perfil',
            'en' => 'profile',
        ],
        'contact' => [
            'ca' => 'contacte',
            'es' => 'contacto',
            'en' => 'contact',
        ],
        // Nota: NO existe una página separada "Actualitat/Actualidad/Current-affairs".
        // El listado de noticias vive en la sección "news" (slug traducible: noticies/noticias/news).
    ];
}

function fcsd_slug(string $key, ?string $lang = null): string {
    $lang = $lang ?: ( function_exists('fcsd_lang') ? fcsd_lang() : ( defined('FCSD_LANG') ? FCSD_LANG : 'ca' ) );
    $map = fcsd_slug_map();
    return $map[$key][$lang] ?? $map[$key][FCSD_DEFAULT_LANG] ?? $key;
}

/** Devuelve la key semántica a partir de un slug traducido (para routing entrante). */
function fcsd_slug_key_from_translated(string $slug): ?string {
    $slug = trim($slug, '/');

    // Aliases legacy (histórico del proyecto) que deben rutear como páginas de sistema.
    // Esto es importante porque algunas instalaciones pueden tener la página canónica
    // creada antiguamente con otro slug (p.ej. `perfil-usuari`) y, aun así, las URLs
    // públicas deben ser /perfil, /es/perfil, /en/profile.
    $legacy = [
        // Perfil
        'perfil-usuari'  => 'profile',
        'perfil-usuario' => 'profile',

        // Login/registro (por si existe legado)
        'iniciar-sessio' => 'login',
        'iniciar-sesion' => 'login',
        'sign-in'        => 'login',

        'registrar'      => 'register',
        'registro'       => 'register',
    ];
    if ( isset($legacy[$slug]) ) {
        return $legacy[$slug];
    }
    foreach (fcsd_slug_map() as $key => $langs) {
        foreach ($langs as $v) {
            if ($v === $slug) return $key;
        }
    }
    return null;
}

/** Devuelve el slug "canónico" (por defecto en ca) para una key semántica. */
function fcsd_default_slug(string $key): string {
    $map = fcsd_slug_map();
    return $map[$key][FCSD_DEFAULT_LANG] ?? $key;
}
