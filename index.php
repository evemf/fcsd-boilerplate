<?php get_header(); ?>
<div class="container content py-5">
<?php if(have_posts()): while(have_posts()): the_post(); ?>
  <article id="post-<?php the_ID(); ?>" <?php post_class('mb-4'); ?>>
    <h2 class="h4"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
    <div class="text-muted small mb-2"><?php the_time(get_option('date_format')); ?></div>
    <?php the_excerpt(); ?>
  </article>
  <hr>
<?php endwhile; the_posts_pagination(); else: ?>
  <p><?php _e('No s\'ha trobat cap contingut.','fcsd'); ?></p>
<?php endif; ?>
</div>
<?php get_footer(); ?>
