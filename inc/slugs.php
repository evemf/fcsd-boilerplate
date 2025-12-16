<?php
/**
 * Mapa central de slugs traducibles.
 * - La clave es semántica (news, shop, etc.)
 * - Valores por idioma
 */
defined('ABSPATH') || exit;

function fcsd_slug_map(): array {
    return [
        'news' => [
            'ca' => 'noticies',
            'es' => 'noticias',
            'en' => 'news',
        ],
        'shop' => [
            'ca' => 'botiga',
            'es' => 'tienda',
            'en' => 'shop',
        ],
        'cart' => [
            'ca' => 'carro',
            'es' => 'carrito',
            'en' => 'cart',
        ],
        'checkout' => [
            'ca' => 'finalitzar-compra',
            'es' => 'finalizar-compra',
            'en' => 'checkout',
        ],
        'my_account' => [
            'ca' => 'el-meu-compte',
            'es' => 'mi-cuenta',
            'en' => 'my-account',
        ],
        'login' => [
            'ca' => 'iniciar-sessio',
            'es' => 'iniciar-sesion',
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
        'news_page' => [
            'ca' => 'actualitat',
            'es' => 'actualidad',
            'en' => 'news',
        ],
    ];
}

function fcsd_slug(string $key, ?string $lang = null): string {
    $lang = $lang ?: (defined('FCSD_LANG') ? FCSD_LANG : 'ca');
    $map = fcsd_slug_map();
    return $map[$key][$lang] ?? $map[$key][FCSD_DEFAULT_LANG] ?? $key;
}

/** Devuelve la key semántica a partir de un slug traducido (para routing entrante). */
function fcsd_slug_key_from_translated(string $slug): ?string {
    $slug = trim($slug, '/');
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
