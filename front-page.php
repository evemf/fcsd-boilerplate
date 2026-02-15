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
  // Filtra por idioma (Exit21: solo CA/ES; internas: visibles, traducibles por meta i18n).
  'meta_query'     => function_exists('fcsd_news_frontend_lang_meta_query') ? fcsd_news_frontend_lang_meta_query() : [],
  'orderby'        => 'date',
  'order'          => 'DESC',
]);
?>

<?php if ( $carousel_news->have_posts() ) : ?>
<?php
  $marquee_enabled = (bool) get_theme_mod( 'home_news_marquee_enable', true );
  $marquee_speed   = (int)  get_theme_mod( 'home_news_marquee_speed', 28 );
  if ( $marquee_speed < 10 ) { $marquee_speed = 10; }
  if ( $marquee_speed > 120 ) { $marquee_speed = 120; }
  $marquee_pause   = (bool) get_theme_mod( 'home_news_marquee_pause', true );
  $strip_classes   = 'fcsd-home-news-strip' . ( $marquee_enabled ? ' is-marquee' : '' ) . ( $marquee_pause ? ' is-pausable' : '' );
?>
<section class="<?php echo esc_attr( $strip_classes ); ?>"
         aria-label="<?php esc_attr_e( 'Notícies destacades', 'fcsd' ); ?>"
         data-marquee="<?php echo $marquee_enabled ? '1' : '0'; ?>"
         data-marquee-speed="<?php echo esc_attr( $marquee_speed ); ?>">
  <div class="fcsd-home-news-strip__viewport" tabindex="0">
    <div class="fcsd-home-news-strip__track">
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
  </div>
</section>
<?php endif; ?>


<!-- ======================================================
     HERO – estil “bàner” (com a la captura de Vodafone)
     - Columna esquerra: text + CTA
     - Columna dreta: media (iframe YouTube)
====================================================== -->
<section class="fcsd-hero-split fcsd-hero-banner" aria-label="<?php esc_attr_e( 'Destacat', 'fcsd' ); ?>">
  <div class="container fcsd-hero-banner__inner">

    <!-- Copy -->
    <div class="fcsd-hero-banner__copy" role="group" tabindex="0">
      <h1 class="fcsd-hero-banner__title">
        <?php echo wp_kses_post(
          fcsd_get_option(
            'home_intro',
            __( 'Acompanyem a persones amb SD a construir una vida més autònoma, plena i connectada.', 'fcsd' )
          )
        ); ?>
      </h1>

      <div class="fcsd-hero-banner__actions">
        <?php
          $about_slug = function_exists('fcsd_default_slug') ? fcsd_default_slug('about') : 'qui-som';
          $about_url  = function_exists('fcsd_get_page_url_by_slug') ? fcsd_get_page_url_by_slug( $about_slug ) : home_url( '/' );
          $cta_url    = fcsd_get_option( 'home_cta_url', $about_url );
          $cta_label  = fcsd_get_option( 'home_cta_label', __( 'Qui som', 'fcsd' ) );
        ?>
        <a class="btn btn-primary" href="<?php echo esc_url( $cta_url ?: $about_url ); ?>">
          <?php echo esc_html( $cta_label ); ?>
        </a>
      </div>
    </div>

    <!-- Media -->
    <div class="fcsd-hero-banner__media" aria-label="<?php esc_attr_e( 'Vídeo destacat', 'fcsd' ); ?>">
      <div class="fcsd-hero-banner__media-inner">
        <iframe
          src="https://www.youtube.com/embed/2LGbN02uhiE?start=10&autoplay=1&mute=1&playsinline=1&controls=1&rel=0&modestbranding=1&loop=1&playlist=2LGbN02uhiE"
          title="<?php echo esc_attr__( 'Vídeo Fundació', 'fcsd' ); ?>"
          frameborder="0"
          allow="autoplay; encrypted-media; picture-in-picture"
          referrerpolicy="strict-origin-when-cross-origin"
          allowfullscreen>
        </iframe>
      </div>
    </div>

  </div>
</section>

<section class="fcsd-ambits--dark">
<div class="fcsd-wave-cut" aria-hidden="true">
    <svg viewBox="0 0 1440 80" preserveAspectRatio="none" focusable="false">
      <!-- Relleno: color de la sección anterior (blanco/neutral). Hace el “corte” -->
      <path class="fcsd-wave-cut__fill"
        d="M0,0 H1440 V44
           C1320,20 1200,68 1080,44
           S840,20 720,44
           S480,68 360,44
           S120,20 0,44
           V0 Z" />
      <!-- Trazo: accent color -->
      <path class="fcsd-wave-cut__stroke"
        d="M0,44
           C120,20 240,68 360,44
           S600,20 720,44
           S960,68 1080,44
           S1320,20 1440,44" />
    </svg>
  </div>
      </section>
<!-- ======================================================
     CONTINGUT
====================================================== -->
<main class="container content py-5">

<?php
$ambits = [];

if ( function_exists( 'fcsd_get_service_areas_config' ) ) {
  $config = fcsd_get_service_areas_config();
  // Ordenació fixa definida al codi (camp 'order').
  uasort( $config, static function( $a, $b ) {
    return (int) ( $a['order'] ?? 0 ) <=> (int) ( $b['order'] ?? 0 );
  } );

  foreach ( $config as $slug => $data ) {
    // Excloem el genèric si no el vols a la home; si el vols, comenta aquesta línia.
    // if ( $slug === 'generic' ) { continue; }

    $term = get_term_by( 'slug', $slug, 'service_area' );
    $url  = $term && ! is_wp_error( $term ) ? get_term_link( $term ) : '';

    $ambits[] = [
      'title' => $data['name'] ?? $slug,
      'color' => $data['color'] ?? '#e7a15a',
      'url'   => is_wp_error( $url ) ? '' : $url,
    ];
  }
}
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
