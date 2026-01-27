<?php
/**
 * Footer FCSD
 */
?>
    </main>

    <footer class="site-footer" role="contentinfo">
      <div class="container">

        <?php
        // ===== Valores por defecto =====
        $default_address = "Fundació Catalana Síndrome de Down\nComte Borrell, 201–203, entresòl\n08029 Barcelona\nEspanya";

        $default_tagline      = __( 'Treballem per la plena inclusió i igualtat de drets.', 'fcsd' );
        $default_donate_url   = 'https://fcsd.org/es/donativo-particular/';
        $default_donate_label = __( 'Donar', 'fcsd' );
        $default_phone        = '+34 93 215 74 23';
        $default_email        = 'general@fcsd.org';

        // ===== Valores desde el personalizador =====
        $address_raw = trim( (string) get_theme_mod( 'fcsd_footer_address', $default_address ) );
        $address_raw = $address_raw !== '' ? $address_raw : $default_address;

        $tagline      = get_theme_mod( 'fcsd_footer_tagline',      $default_tagline );
        $donate_url   = get_theme_mod( 'fcsd_footer_donate_url',   $default_donate_url );
        $donate_label = get_theme_mod( 'fcsd_footer_donate_label', $default_donate_label );
        $phone        = get_theme_mod( 'fcsd_footer_phone',        $default_phone );
        $email        = get_theme_mod( 'fcsd_footer_email',        $default_email );

        // Redes: reutilizamos las de la franja superior
        $twitter_url   = get_theme_mod( 'fcsd_social_twitter',  '' );
        $facebook_url  = get_theme_mod( 'fcsd_social_facebook', '' );
        $instagram_url = get_theme_mod( 'fcsd_social_instagram','' );
        $linkedin_url  = get_theme_mod( 'fcsd_social_linkedin', '' );
        $youtube_url   = get_theme_mod( 'fcsd_social_youtube',  '' );
        $tiktok_url    = get_theme_mod( 'fcsd_social_tiktok',   '' );

        // Formatos útiles
        $address_html = nl2br( esc_html( $address_raw ) );
        $address_line = preg_replace( '/\s+/', ' ', $address_raw );
        $address_q    = urlencode( $address_line );

        $phone_clean  = preg_replace( '/\s+/', '', $phone );
        ?>

        <!-- Zona superior: branding / contacto / redes -->
        <div class="c-footer__top">
          <!-- Branding + mensaje + donaciones -->
          <div class="c-footer__brand">
            <a class="c-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
              <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
            </a>

            <?php if ( $tagline ) : ?>
              <p class="c-footer__tagline">
                <?php echo esc_html( $tagline ); ?>
              </p>
            <?php endif; ?>

            <?php if ( $donate_url ) : ?>
              <p class="c-footer__donate">
                <a class="button" href="<?php echo esc_url( $donate_url ); ?>">
                  <?php echo esc_html( $donate_label ); ?>
                </a>
              </p>
            <?php endif; ?>
          </div>

          <!-- Contacto -->
          <div class="c-footer__contact">
            <ul class="c-footer__contact-list">
              <?php if ( $address_html ) : ?>
                <li>
                  <i class="bi bi-geo-alt" aria-hidden="true"></i>
                  <span><?php echo $address_html; ?></span>
                </li>
              <?php endif; ?>

              <?php if ( $phone ) : ?>
                <li>
                  <i class="bi bi-telephone" aria-hidden="true"></i>
                  <a href="tel:<?php echo esc_attr( $phone_clean ); ?>">
                    <?php echo esc_html( $phone ); ?>
                  </a>
                </li>
              <?php endif; ?>

              <?php if ( $email ) : ?>
                <li>
                  <i class="bi bi-envelope" aria-hidden="true"></i>
                  <a href="mailto:<?php echo esc_attr( $email ); ?>">
                    <?php echo esc_html( $email ); ?>
                  </a>
                </li>
              <?php endif; ?>
            </ul>
          </div>

          <!-- Xarxes socials (mismas que en la franja superior) -->
          <div class="c-footer__social">
            <ul class="social-icon" aria-label="<?php echo esc_attr__( 'Xarxes socials', 'fcsd' ); ?>">

              <?php if ( $twitter_url ) : ?>
                <li>
                  <a href="<?php echo esc_url( $twitter_url ); ?>" class="social-icon-link" aria-label="X (Twitter)" rel="noopener">
                    <i class="bi bi-twitter-x" aria-hidden="true"></i>
                  </a>
                </li>
              <?php endif; ?>

              <?php if ( $facebook_url ) : ?>
                <li>
                  <a href="<?php echo esc_url( $facebook_url ); ?>" class="social-icon-link" aria-label="Facebook" rel="noopener">
                    <i class="bi bi-facebook" aria-hidden="true"></i>
                  </a>
                </li>
              <?php endif; ?>

              <?php if ( $instagram_url ) : ?>
                <li>
                  <a href="<?php echo esc_url( $instagram_url ); ?>" class="social-icon-link" aria-label="Instagram" rel="noopener">
                    <i class="bi bi-instagram" aria-hidden="true"></i>
                  </a>
                </li>
              <?php endif; ?>

              <?php if ( $linkedin_url ) : ?>
                <li>
                  <a href="<?php echo esc_url( $linkedin_url ); ?>" class="social-icon-link" aria-label="LinkedIn" rel="noopener">
                    <i class="bi bi-linkedin" aria-hidden="true"></i>
                  </a>
                </li>
              <?php endif; ?>

              <?php if ( $youtube_url ) : ?>
                <li>
                  <a href="<?php echo esc_url( $youtube_url ); ?>" class="social-icon-link" aria-label="YouTube" rel="noopener">
                    <i class="bi bi-youtube" aria-hidden="true"></i>
                  </a>
                </li>
              <?php endif; ?>

              <?php if ( $tiktok_url ) : ?>
                <li>
                  <a href="<?php echo esc_url( $tiktok_url ); ?>" class="social-icon-link" aria-label="TikTok" rel="noopener">
                    <i class="bi bi-tiktok" aria-hidden="true"></i>
                  </a>
                </li>
              <?php endif; ?>

            </ul>
          </div>
        </div><!-- /.c-footer__top -->

        <!-- Mapa (template-part) -->
        <?php get_template_part( 'template-parts/map' ); ?>

        <!-- Franja inferior: copyright + menú legal (con modales) -->
        <div class="c-footer__bottom">
          <p class="copyright-text mb-0">
            &copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
          </p>

          <?php get_template_part( 'template-parts/legal' ); ?>
        </div>
        <div class="fcsd-chatbot">
          <button class="fcsd-chatbot-toggle">
              <i class="bi bi-chat-dots"></i>
          </button>
          <div class="fcsd-chatbot-window">
              <div class="fcsd-chatbot-header d-flex justify-content-between align-items-center">
                  <span><?php esc_html_e( 'Ayuda rápida', 'fcsd' ); ?></span>
                  <button class="fcsd-chatbot-close btn btn-sm btn-link p-0">
                      <i class="bi bi-x-lg"></i>
                  </button>
              </div>
              <div class="fcsd-chatbot-messages"></div>
              <form class="fcsd-chatbot-form">
                  <input type="text" class="form-control" placeholder="<?php esc_attr_e( 'Escribe tu pregunta…', 'fcsd' ); ?>">
              </form>
          </div>
      </div>


      </div><!-- /.container -->
    </footer>

    <?php wp_footer(); ?>
  </body>
</html>
