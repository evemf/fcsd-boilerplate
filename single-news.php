<?php
/**
 * Single News template
 * - Renderiza noticias internas y las importadas (EXIT21) de forma legible.
 */
get_header();
?>
<main class="container content py-5 single-news">
  <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

    <?php
      $external_url = (string) get_post_meta(get_the_ID(), 'news_external_url', true);
      $source       = (string) get_post_meta(get_the_ID(), 'news_source', true);
      $lang         = (string) get_post_meta(get_the_ID(), 'news_language', true);
      $author       = (string) get_post_meta(get_the_ID(), 'news_author', true);
      $location     = (string) get_post_meta(get_the_ID(), 'news_location', true);
      $video        = (string) get_post_meta(get_the_ID(), 'news_video_url', true);
      $terms_cat    = get_the_terms(get_the_ID(), 'category');
      $terms_area   = get_the_terms(get_the_ID(), 'service_area');
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
      <header class="mb-4">
        <h1 class="mb-2"><?php the_title(); ?></h1>

        <div class="text-muted" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
          <span><?php echo esc_html( get_the_date() ); ?></span>
          <?php if ( $author ) : ?>
            <span>· <?php echo esc_html( $author ); ?></span>
          <?php endif; ?>
          <?php if ( $location ) : ?>
            <span>· <?php echo esc_html( $location ); ?></span>
          <?php endif; ?>
          <?php if ( $lang ) : ?>
            <span style="border:1px solid #ddd;border-radius:999px;padding:2px 10px;font-size:12px;">
              <?php echo esc_html( strtoupper($lang) ); ?>
            </span>
          <?php endif; ?>
          <?php if ( $source ) : ?>
            <span style="border:1px solid #ddd;border-radius:999px;padding:2px 10px;font-size:12px;">
              <?php echo esc_html( strtoupper($source) ); ?>
            </span>
          <?php endif; ?>
        </div>

        <?php if ( ! empty($terms_cat) && ! is_wp_error($terms_cat) ) : ?>
          <div class="mt-3" style="display:flex;flex-wrap:wrap;gap:8px;">
            <?php foreach ( $terms_cat as $t ) : ?>
              <a href="<?php echo esc_url( get_term_link($t) ); ?>"
                 style="display:inline-block;border:1px solid #eee;border-radius:999px;padding:4px 10px;text-decoration:none;">
                <?php echo esc_html( $t->name ); ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ( ! empty($terms_area) && ! is_wp_error($terms_area) ) : ?>
          <div class="mt-2" style="display:flex;flex-wrap:wrap;gap:8px;">
            <?php foreach ( $terms_area as $t ) : ?>
              <a href="<?php echo esc_url( get_term_link($t) ); ?>"
                 style="display:inline-block;border:1px solid #eee;border-radius:999px;padding:4px 10px;text-decoration:none;">
                <?php echo esc_html( $t->name ); ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ( has_post_thumbnail() ) : ?>
          <div class="mt-4">
            <?php the_post_thumbnail('large', ['class' => 'img-fluid', 'style' => 'border-radius:14px;']); ?>
          </div>
        <?php endif; ?>

        <?php if ( $external_url ) : ?>
          <div class="mt-4">
            <a class="btn btn-primary" href="<?php echo esc_url($external_url); ?>" target="_blank" rel="noopener noreferrer">
              <?php echo esc_html__( 'Veure a la font original', 'fcsd' ); ?>
            </a>
          </div>
        <?php endif; ?>
      </header>

      <div class="entry-content">
        <?php the_content(); ?>
      </div>

      <?php if ( $video ) : ?>
        <div class="mt-5">
          <?php
            // El importador guarda URL de YouTube/Vimeo; embed seguro.
            echo wp_oembed_get( esc_url($video) ) ?: '';
          ?>
        </div>
      <?php endif; ?>
    </article>

  <?php endwhile; endif; ?>
</main>
<?php get_footer(); ?>
