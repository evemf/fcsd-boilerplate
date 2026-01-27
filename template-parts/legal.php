<?php
/**
 * Template part: enlaces legales + modal legal reutilizable
 *
 * JS (assets/js/legal-modal.js) espera:
 * - Botones .footer-legal-link con data-legal-key="privacy|cookies|legal|copyright"
 * - Overlay con id="fcsd-legal-overlay"
 */
?>

<ul class="c-footer__legal">
  <li>
    <button type="button" class="footer-legal-link" data-legal-key="privacy">
      <?php esc_html_e( 'Política de privacitat', 'fcsd' ); ?>
    </button>
  </li>
  <li>
    <button type="button" class="footer-legal-link" data-legal-key="cookies">
      <?php esc_html_e( 'Política de cookies', 'fcsd' ); ?>
    </button>
  </li>
  <li>
    <button type="button" class="footer-legal-link" data-legal-key="legal">
      <?php esc_html_e( 'Avís legal', 'fcsd' ); ?>
    </button>
  </li>
  <li>
    <button type="button" class="footer-legal-link" data-legal-key="copyright">
      <?php esc_html_e( 'Copyright', 'fcsd' ); ?>
    </button>
  </li>
</ul>

<!-- Modal legal reutilizable -->
<div id="fcsd-legal-overlay" class="legal-overlay" hidden>
  <div class="legal-modal" role="dialog" aria-modal="true" aria-labelledby="fcsd-legal-title">
    <button type="button" class="legal-modal__close" aria-label="<?php esc_attr_e( 'Tancar', 'fcsd' ); ?>">
      &times;
    </button>
    <h2 id="fcsd-legal-title" class="legal-modal__title"></h2>
    <div class="legal-modal__body"></div>
  </div>
</div>
