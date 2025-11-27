<?php
// template-parts/shop/recommendations.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$products = $args['products'] ?? [];
if ( ! $products ) {
    return;
}
?>

<section class="container product-recommendations mt-5">
    <h2 class="h4 mb-3">
        <?php esc_html_e( 'Te puede interesar', 'fcsd' ); ?>
    </h2>

    <div class="row">
        <?php
        $query = new WP_Query( [
            'post_type'      => 'product',
            'post__in'       => array_map( 'intval', $products ),
            'orderby'        => 'post__in',
            'posts_per_page' => count( $products ),
        ] );

        if ( $query->have_posts() ) :
            while ( $query->have_posts() ) :
                $query->the_post();
                get_template_part( 'template-parts/product-card');
            endwhile;
        endif;

        wp_reset_postdata();
        ?>
    </div>
</section>
