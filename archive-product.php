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

    <div class="shop-grid row">
        <?php if ( have_posts() ) : ?>
            <?php while ( have_posts() ) : the_post(); ?>
                <?php get_template_part( 'template-parts/product', 'card' ); ?>
            <?php endwhile; ?>
        <?php else : ?>
            <p><?php esc_html_e( 'No hay productos disponibles.', 'fcsd' ); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();
