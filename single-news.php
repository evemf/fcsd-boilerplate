<?php
get_header();
the_post();

$post_id   = get_the_ID();
$post_type = get_post_type( $post_id );

// Tiempo de lectura estimado
$content      = get_post_field( 'post_content', $post_id );
$word_count   = str_word_count( wp_strip_all_tags( $content ) );
$reading_time = max( 1, ceil( $word_count / 200 ) );

// Campos personalizados opcionales
$source_url   = get_post_meta( $post_id, '_news_source_url', true );
$source_label = get_post_meta( $post_id, '_news_source_label', true ); // p.ej. "Marca", "Mundo Deportivo"
$subtitle     = get_post_meta( $post_id, '_news_subtitle', true );
?>
<main class="container single-news">
  <article <?php post_class( 'news-article' ); ?>
           itemscope
           itemtype="https://schema.org/NewsArticle">

    <header class="entry-header">
      <?php if ( function_exists( 'fcsd_breadcrumbs' ) ) : ?>
        <nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Ruta de navegación', 'textdomain' ); ?>">
          <?php fcsd_breadcrumbs(); ?>
        </nav>
      <?php endif; ?>

      <?php
      // Categoría principal como "píldora"
      $primary_cat = null;
      $cats        = get_the_terms( $post_id, 'news_category' );
      if ( $cats && ! is_wp_error( $cats ) ) {
          $primary_cat = $cats[0];
      }
      ?>
      <?php if ( $primary_cat ) : ?>
        <div class="entry-labels">
          <a class="label label-category"
             href="<?php echo esc_url( get_term_link( $primary_cat ) ); ?>">
            <?php echo esc_html( $primary_cat->name ); ?>
          </a>
        </div>
      <?php endif; ?>

      <h1 class="entry-title" itemprop="headline">
        <?php the_title(); ?>
      </h1>

      <?php if ( $subtitle ) : ?>
        <p class="entry-subtitle">
          <?php echo esc_html( $subtitle ); ?>
        </p>
      <?php endif; ?>

      <div class="entry-meta">
        <span class="meta-item meta-date">
          <time class="published"
                datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"
                itemprop="datePublished">
            <?php echo esc_html( get_the_date() ); ?>
          </time>
        </span>

        <?php if ( get_the_modified_time( 'U' ) !== get_the_time( 'U' ) ) : ?>
          <span class="meta-item meta-updated">
            ·
            <time class="updated"
                  datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"
                  itemprop="dateModified">
              <?php
              printf(
                  /* translators: %s: date */
                  esc_html__( 'Actualizado el %s', 'textdomain' ),
                  esc_html( get_the_modified_date() )
              );
              ?>
            </time>
          </span>
        <?php endif; ?>

        <?php
        // Autores (taxonomy news_author)
        $authors = get_the_terms( $post_id, 'news_author' );
        if ( $authors && ! is_wp_error( $authors ) ) :
            $links = array_map(
                function ( $t ) {
                    return '<a href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . '</a>';
                },
                $authors
            );
            ?>
          <span class="meta-item meta-authors">
            ·
            <span class="label"><?php esc_html_e( 'Por', 'textdomain' ); ?></span>
            <span class="value">
              <?php echo wp_kses_post( implode( ', ', $links ) ); ?>
            </span>
          </span>
        <?php endif; ?>

        <span class="meta-item meta-reading-time">
          ·
          <?php
          printf(
              _n( '%s minuto de lectura', '%s minutos de lectura', $reading_time, 'textdomain' ),
              intval( $reading_time )
          );
          ?>
        </span>
      </div>

      <?php if ( has_post_thumbnail() ) : ?>
        <figure class="entry-thumb" itemprop="image" itemscope itemtype="https://schema.org/ImageObject">
          <?php the_post_thumbnail( 'large', [ 'itemprop' => 'url' ] ); ?>
          <?php
          $caption = get_the_post_thumbnail_caption();
          if ( $caption ) :
              ?>
            <figcaption class="entry-thumb-caption">
              <?php echo esc_html( $caption ); ?>
            </figcaption>
          <?php endif; ?>
        </figure>
      <?php endif; ?>

      <div class="entry-actions">
        <div class="share-inline">
          <span class="share-label"><?php esc_html_e( 'Compartir', 'textdomain' ); ?>:</span>
          <?php
          $share_url   = urlencode( get_permalink() );
          $share_title = urlencode( get_the_title() );
          ?>
          <a class="share-link share-twitter"
             href="https://twitter.com/intent/tweet?url=<?php echo esc_attr( $share_url ); ?>&text=<?php echo esc_attr( $share_title ); ?>"
             target="_blank" rel="noopener">
            X / Twitter
          </a>
          <a class="share-link share-facebook"
             href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( $share_url ); ?>"
             target="_blank" rel="noopener">
            Facebook
          </a>
          <a class="share-link share-whatsapp"
             href="https://wa.me/?text=<?php echo esc_attr( $share_title . '%20' . $share_url ); ?>"
             target="_blank" rel="noopener">
            WhatsApp
          </a>
        </div>
      </div>
    </header>

    <div class="entry-body">
      <div class="entry-content" itemprop="articleBody">
        <?php the_content(); ?>
      </div>

      <aside class="entry-sidebar">
        <?php
        // Mostrar categorías extra (si hay más de una)
        if ( $cats && ! is_wp_error( $cats ) && count( $cats ) > 1 ) :
            $extra_cats = array_slice( $cats, 1 );
            ?>
          <section class="meta-block meta-categories">
            <h2 class="meta-block-title"><?php esc_html_e( 'Más sobre', 'textdomain' ); ?></h2>
            <ul class="meta-list meta-list-categories">
              <?php foreach ( $extra_cats as $cat ) : ?>
                <li>
                  <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
                    <?php echo esc_html( $cat->name ); ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>
        <?php endif; ?>

        <?php
        // Etiquetas (puedes cambiar 'post_tag' por 'news_tag' si usas taxonomy específica)
        $tags = get_the_terms( $post_id, 'post_tag' );
        if ( $tags && ! is_wp_error( $tags ) ) :
            ?>
          <section class="meta-block meta-tags">
            <h2 class="meta-block-title"><?php esc_html_e( 'Etiquetas', 'textdomain' ); ?></h2>
            <ul class="meta-list meta-list-tags">
              <?php foreach ( $tags as $tag ) : ?>
                <li>
                  <a href="<?php echo esc_url( get_term_link( $tag ) ); ?>">
                    <?php echo esc_html( $tag->name ); ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>
        <?php endif; ?>

        <?php if ( $source_url ) : ?>
          <section class="meta-block meta-source">
            <h2 class="meta-block-title"><?php esc_html_e( 'Fuente original', 'textdomain' ); ?></h2>
            <p>
              <a target="_blank" rel="noopener"
                 href="<?php echo esc_url( $source_url ); ?>">
                <?php
                if ( $source_label ) {
                    echo esc_html( $source_label );
                } else {
                    echo esc_html( parse_url( $source_url, PHP_URL_HOST ) );
                }
                ?>
              </a>
            </p>
          </section>
        <?php endif; ?>
      </aside>
    </div>

    <footer class="entry-footer">
      <section class="entry-author-box">
        <?php if ( $authors && ! is_wp_error( $authors ) ) : ?>
          <h2 class="author-box-title"><?php esc_html_e( 'Sobre el autor', 'textdomain' ); ?></h2>
          <?php foreach ( $authors as $author_term ) : ?>
            <article class="author-card">
              <h3 class="author-name">
                <a href="<?php echo esc_url( get_term_link( $author_term ) ); ?>">
                  <?php echo esc_html( $author_term->name ); ?>
                </a>
              </h3>
              <?php if ( ! empty( $author_term->description ) ) : ?>
                <p class="author-bio">
                  <?php echo esc_html( $author_term->description ); ?>
                </p>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>

      <?php
      // Noticias relacionadas por categoría
      if ( $primary_cat ) :
          $related_args = [
              'post_type'           => $post_type,
              'posts_per_page'      => 3,
              'post__not_in'        => [ $post_id ],
              'ignore_sticky_posts' => true,
              'tax_query'           => [
                  [
                      'taxonomy' => 'news_category',
                      'field'    => 'term_id',
                      'terms'    => $primary_cat->term_id,
                  ],
              ],
          ];
          $related_q = new WP_Query( $related_args );
          if ( $related_q->have_posts() ) :
              ?>
            <section class="related-news">
              <h2 class="related-title"><?php esc_html_e( 'Te puede interesar', 'textdomain' ); ?></h2>
              <div class="related-grid">
                <?php
                while ( $related_q->have_posts() ) :
                    $related_q->the_post();
                    ?>
                  <article <?php post_class( 'related-item' ); ?>>
                    <a href="<?php the_permalink(); ?>" class="related-link">
                      <div class="related-thumb">
                        <?php
                        if ( has_post_thumbnail() ) {
                            the_post_thumbnail( 'medium' );
                        }
                        ?>
                      </div>
                      <div class="related-body">
                        <h3 class="related-item-title"><?php the_title(); ?></h3>
                        <time class="related-date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                          <?php echo esc_html( get_the_date() ); ?>
                        </time>
                      </div>
                    </a>
                  </article>
                <?php endwhile; ?>
              </div>
            </section>
            <?php
            wp_reset_postdata();
          endif;
      endif;
      ?>

      <nav class="post-nav" aria-label="<?php esc_attr_e( 'Navegación entre noticias', 'textdomain' ); ?>">
        <div class="post-nav-prev">
          <?php previous_post_link( '%link', '← ' . esc_html__( 'Anterior', 'textdomain' ) ); ?>
        </div>
        <div class="post-nav-next">
          <?php next_post_link( '%link', esc_html__( 'Siguiente', 'textdomain' ) . ' →' ); ?>
        </div>
      </nav>
    </footer>

  </article>

  <?php if ( comments_open() || get_comments_number() ) : ?>
    <section class="news-comments">
      <?php comments_template(); ?>
    </section>
  <?php endif; ?>
</main>
<?php get_footer(); ?>
