<?php
// archive-product.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header(); ?>

<div class="shop-archive container">
    <h1 class="shop-title">
        <?php
        // If this archive is being used while a product category is set, show the term title instead of generic "Products".
        $term_obj = null;

        if ( is_tax( 'fcsd_product_cat' ) ) {
            $term_obj = get_queried_object();
        } elseif ( get_query_var( 'fcsd_product_cat' ) ) {
            $term_obj = get_term_by( 'slug', get_query_var( 'fcsd_product_cat' ), 'fcsd_product_cat' );
        }

        if ( $term_obj && ! is_wp_error( $term_obj ) ) {
            echo esc_html( $term_obj->name );
        } else {
            post_type_archive_title();
        }
        ?>
    </h1>

      <?php
    // Show filters only on the generic shop archive (not on category pages / landings).
    if ( ! is_tax( 'fcsd_product_cat' ) && ! get_query_var( 'fcsd_product_cat' ) ) {
        get_template_part( 'template-parts/shop/navbar', 'filters' );
    }?>

    <div class="shop-grid row">
        <?php if ( have_posts() ) : ?>
            <?php while ( have_posts() ) : the_post(); ?>
                <?php get_template_part( 'template-parts/product', 'card' ); ?>
            <?php endwhile; ?>

            <div class="shop-pagination mt-4">
                <?php
                the_posts_pagination([
                    'mid_size'  => 1,
                    'prev_text' => esc_html__( 'Anterior', 'fcsd' ),
                    'next_text' => esc_html__( 'Següent', 'fcsd' ),
                ]);
                ?>
            </div>
        <?php else : ?>
            <p><?php esc_html_e( 'No hi ha productes disponibles.', 'fcsd' ); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();
