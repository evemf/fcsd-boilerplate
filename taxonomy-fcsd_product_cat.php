<?php
// archive-product.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header(); ?>

<div class="shop-archive container">
    <h1 class="shop-title">
        <?php post_type_archive_title(); ?>
    </h1>

      <?php
    get_template_part( 'template-parts/shop/navbar', 'filters' );
    ?>

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
