<?php
/**
 * Template per a la pàgina de contacte (/contacte)
 */

get_header();
?>

<main id="primary" class="site-main container py-5">
    <header class="mb-4">
        <h1 class="h1"><?php the_title(); ?></h1>
    </header>

    <div class="row">
        <div class="col-lg-8">
            <?php
            // Contenido editable en el admin (opcional).
            if ( have_posts() ) :
                while ( have_posts() ) :
                    the_post();
                    if ( '' !== trim( get_the_content() ) ) :
                        ?>
                        <div class="mb-4">
                            <?php the_content(); ?>
                        </div>
                        <?php
                    endif;
                endwhile;
            endif;

            // Formulario de contacto:
            if ( function_exists( 'fcsd_render_contact_form' ) ) {
                echo fcsd_render_contact_form();
            } else {
                // fallback si algún día solo quieres usar el shortcode
                echo do_shortcode( '[fcsd_contact_form]' );
            }
            ?>
        </div>
    </div>
</main>

<?php
get_footer();
