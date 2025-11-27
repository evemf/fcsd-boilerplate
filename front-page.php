<?php get_header(); ?>
<div class="container content py-5">
  <h1 class="display-5 fw-semibold mb-3"><?php _e('Pàgina d\'inici','fcsd'); ?></h1>
  <p class="lead"><?php echo wp_kses_post( fcsd_get_option('fcsd_home_dummy','') ); ?></p>
  <p><?php _e('Aquesta és una pàgina estàtica amb contingut provisional.','fcsd'); ?></p>
</div>
<?php get_footer(); ?>
