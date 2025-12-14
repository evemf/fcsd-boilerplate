<?php
get_header();

/* ======================================================
 * NOTÍCIES – carrusel existent
 * ====================================================== */
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
<section class="fcsd-home-news-strip" aria-label="<?php esc_attr_e( 'Notícies destacades', 'fcsd' ); ?>">
  <div class="fcsd-home-news-strip__inner">
    <?php while ( $carousel_news->have_posts() ) : $carousel_news->the_post(); ?>
      <article class="fcsd-home-news-strip__item">
        <a class="fcsd-home-news-strip__link" href="<?php the_permalink(); ?>">
          <div class="fcsd-home-news-strip__thumb">
            <?php if ( has_post_thumbnail() ) : ?>
              <?php the_post_thumbnail( 'thumbnail' ); ?>
            <?php else : ?>
              <span class="fcsd-home-news-strip__thumb-ph" aria-hidden="true"></span>
            <?php endif; ?>
          </div>
          <h3 class="fcsd-home-news-strip__title"><?php the_title(); ?></h3>
        </a>
      </article>
    <?php endwhile; wp_reset_postdata(); ?>
  </div>
</section>
<?php endif; ?>


<!-- ======================================================
     HERO VIDEO – FULL WIDTH + AUTOPLAY (UN SOLO IFRAME)
====================================================== -->
<section class="fcsd-hero-split" aria-label="<?php esc_attr_e( 'Vídeo destacat', 'fcsd' ); ?>">

  <!-- VIDEO -->
  <div class="fcsd-hero-split__media">
    <iframe
      src="https://www.youtube.com/embed/2LGbN02uhiE?autoplay=1&mute=1&playsinline=1&controls=0&rel=0&modestbranding=1&loop=1&playlist=2LGbN02uhiE"
      title="Vídeo Fundació"
      frameborder="0"
      allow="autoplay; encrypted-media; picture-in-picture"
      referrerpolicy="strict-origin-when-cross-origin"
      allowfullscreen>
    </iframe>
  </div>

  <!-- OVERLAY / BIENVENIDA -->
  <div class="fcsd-home-welcome">
    <div class="container">
      <div
        class="fcsd-home-welcome__panel paper-border"
        tabindex="0"
        role="group"
        aria-label="<?php esc_attr_e( 'Benvingudes i benvinguts', 'fcsd' ); ?>">

        <h1 class="mb-3">
          <?php echo wp_kses_post(
            fcsd_get_option(
              'home_intro',
              __( 'Acompanyem persones i famílies perquè puguin construir una vida més autònoma, plena i connectada.', 'fcsd' )
            )
          ); ?>
        </h1>

        <a class="btn btn-primary" href="<?php echo esc_url( home_url( '/qui-som/' ) ); ?>">
          <?php _e( 'Qui som', 'fcsd' ); ?>
        </a>

      </div>
    </div>
  </div>

</section>


<!-- ======================================================
     CONTINGUT
====================================================== -->
<main class="container content py-5">

<?php
$ambits = [
  [ 'title' => 'Institucional/Genèric', 'color' => '#1D80C4', 'url' => home_url( '/institucional-generic/' ) ],
  [ 'title' => 'Vida Independent',      'color' => '#E5007E', 'url' => home_url( '/vida-independent/' ) ],
  [ 'title' => 'Treball',               'color' => '#E45E1A', 'url' => home_url( '/treball/' ) ],
  [ 'title' => 'Formació',              'color' => '#7D68AC', 'url' => home_url( '/formacio/' ) ],
  [ 'title' => 'Oci',                   'color' => '#C6D134', 'url' => home_url( '/oci/' ) ],
  [ 'title' => 'Salut',                 'color' => '#D51116', 'url' => home_url( '/salut/' ) ],
  [ 'title' => 'Merchandising',         'color' => '#A8A7A7', 'url' => home_url( '/merchandising/' ) ],
  [ 'title' => 'Èxit 21',               'color' => '#FDC512', 'url' => home_url( '/exit-21/' ) ],
  [ 'title' => 'Assemblea DH',          'color' => '#FDC512', 'url' => home_url( '/assemblea-dh/' ) ],
  [ 'title' => 'Voluntariat',           'color' => '#2CA055', 'url' => home_url( '/voluntariat/' ) ],
];
?>

<section class="fcsd-ambits" aria-label="<?php esc_attr_e( 'Àmbits', 'fcsd' ); ?>">
  <h2 class="mb-4"><?php _e( 'Àmbits de treball', 'fcsd' ); ?></h2>

  <div class="fcsd-ambits__grid">
    <?php foreach ( $ambits as $a ) : ?>
      <div class="fcsd-ambit">
        <h3 class="fcsd-ambit__title"><?php echo esc_html( $a['title'] ); ?></h3>

        <div class="fcsd-coin"
             role="button"
             tabindex="0"
             aria-pressed="false"
             style="--coin: <?php echo esc_attr( $a['color'] ); ?>">

          <div class="fcsd-coin__inner">
            <span class="fcsd-coin__face fcsd-coin__front"></span>
            <span class="fcsd-coin__face fcsd-coin__back">
              <a class="fcsd-coin__link" href="<?php echo esc_url( $a['url'] ); ?>">
                <?php _e( 'Saber més', 'fcsd' ); ?>
              </a>
            </span>
          </div>

        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

</main>

<?php get_footer(); ?>
