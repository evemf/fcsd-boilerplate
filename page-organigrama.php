<?php
/**
 * Template Name: Organigrama
 */

get_header();
?>

<main id="primary" class="site-main container py-5">
    <?php
    if ( have_posts() ) :
        while ( have_posts() ) :
            the_post();
            ?>

            <header class="page-header mb-4">
                <h1 class="page-title"><?php the_title(); ?></h1>
            </header>

            <div class="page-content mb-4">
                <?php the_content(); ?>
            </div>

            <?php
            $image_id     = (int) get_option( 'fcsd_org_image_id', 0 );
            $show_digital = (bool) get_option( 'fcsd_org_show_digital', true );

            if ( $image_id ) : ?>
                <section class="org-image-block mb-5 text-center">
                    <?php echo wp_get_attachment_image( $image_id, 'large', false, [ 'class' => 'org-image img-fluid' ] ); ?>
                </section>
            <?php endif; ?>

            <?php if ( $show_digital ) : ?>
                <section class="org-section">
                    <?php fcsd_org_render_tree_root(); ?>
                </section>
            <?php endif; ?>

            <?php
        endwhile;
    endif;
    ?>
</main>

<?php
get_footer();
