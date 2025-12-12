<?php
get_header();

// Notícies seleccionades per al carrusel (metabox)
$carousel_news = new WP_Query([
  'post_type'      => 'news',
  'posts_per_page' => 6,
  'no_found_rows'  => true,
  'meta_key'       => '_fcsd_show_in_home_carousel',
  'meta_value'     => '1',
  'orderby'        => 'date',
  'order'          => 'DESC',
]);
?>

<?php if ( $carousel_news->have_posts() ) : ?>
  <section class="fcsd-home-news-strip" aria-label="<?php echo esc_attr__( 'Notícies destacades', 'fcsd' ); ?>">
    <div class="fcsd-home-news-strip__inner">
      <?php while ( $carousel_news->have_posts() ) : $carousel_news->the_post(); ?>
        <article class="fcsd-home-news-strip__item">
          <a class="fcsd-home-news-strip__link" href="<?php the_permalink(); ?>">
            <div class="fcsd-home-news-strip__thumb">
              <?php if ( has_post_thumbnail() ) : ?>
                <?php the_post_thumbnail('thumbnail', ['loading' => 'lazy']); ?>
              <?php else : ?>
                <span class="fcsd-home-news-strip__thumb-ph" aria-hidden="true"></span>
              <?php endif; ?>
            </div>

            <h3 class="fcsd-home-news-strip__title"><?php the_title(); ?></h3>
          </a>
        </article>
      <?php endwhile; ?>
      <?php wp_reset_postdata(); ?>
    </div>
  </section>

  <style>
    .fcsd-home-news-strip{ background:#000; }
    .fcsd-home-news-strip__inner{
      display:flex;
      gap:28px;
      align-items:flex-start;
      padding:18px 22px;
      overflow-x:auto;
      scroll-snap-type:x mandatory;
      -webkit-overflow-scrolling:touch;
    }
    .fcsd-home-news-strip__item{
      flex:0 0 auto;
      min-width:280px;
      scroll-snap-align:start;
    }
    .fcsd-home-news-strip__link{
      display:flex;
      gap:12px;
      align-items:flex-start;
      text-decoration:none;
      color:#fff;
    }
    .fcsd-home-news-strip__thumb{
      width:64px;
      height:64px;
      flex:0 0 64px;
    }
    .fcsd-home-news-strip__thumb img,
    .fcsd-home-news-strip__thumb-ph{
      width:64px;
      height:64px;
      display:block;
      object-fit:cover;
      background:#2a2a2a;
    }
    .fcsd-home-news-strip__title{
      margin:0;
      font-size:18px;
      line-height:1.15;
      font-weight:700;
      color:#fff;
      max-width:240px;
    }

    /* scrollbar discreta */
    .fcsd-home-news-strip__inner::-webkit-scrollbar{ height:8px; }
    .fcsd-home-news-strip__inner::-webkit-scrollbar-thumb{ background:#2a2a2a; border-radius:999px; }
  </style>
<?php endif; ?>

<main class="container content py-5">

  <h1 class="display-5 fw-semibold mb-3"><?php _e( 'Pàgina d\'inici', 'fcsd' ); ?></h1>
  <p class="lead"><?php echo wp_kses_post( fcsd_get_option( 'fcsd_home_dummy', '' ) ); ?></p>

</main>

<?php get_footer(); ?>
