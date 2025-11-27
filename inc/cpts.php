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

    register_post_type('event', [
        'label' => __('Events', 'fcsd'),
        'public' => true,
        'menu_position' => 6,
        'menu_icon' => 'dashicons-portfolio',
        'supports' => ['title','editor','thumbnail','excerpt','page-attributes'],
        'has_archive' => true,
        'rewrite' => ['slug' => 'events']
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
});

