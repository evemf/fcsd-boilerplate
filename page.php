<?php get_header(); ?>
<div class="container content py-5">
  <?php if(have_posts()): while(have_posts()): the_post(); ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
      <h1 class="mb-3"><?php the_title(); ?></h1>
      <?php the_content(); ?>
    </article>
  <?php endwhile; endif; ?>
</div>
<?php get_footer(); ?>
