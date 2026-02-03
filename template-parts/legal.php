<?php
/**
 * Template part: enlaces legales + modal legal reutilizable
 *
 * JS (assets/js/legal-modal.js) espera:
 * - Botones .footer-legal-link con data-legal-key="privacy|cookies|legal|copyright"
 * - Overlay con id="fcsd-legal-overlay"
 */
?>

<?php $fcsd_legal = function_exists( 'fcsd_get_legal_texts' ) ? fcsd_get_legal_texts() : []; ?>

<ul class="c-footer__legal">
  <li>
    <button type="button" class="footer-legal-link" data-legal-key="privacy">
      <?php echo esc_html( $fcsd_legal['privacy']['title'] ?? __( 'Política de privacitat', 'fcsd' ) ); ?>
    </button>
  </li>
  <li>
    <button type="button" class="footer-legal-link" data-legal-key="cookies">
      <?php echo esc_html( $fcsd_legal['cookies']['title'] ?? __( 'Política de cookies', 'fcsd' ) ); ?>
    </button>
  </li>
  <li>
    <button type="button" class="footer-legal-link" data-legal-key="legal">
      <?php echo esc_html( $fcsd_legal['legal']['title'] ?? __( 'Avís legal', 'fcsd' ) ); ?>
    </button>
  </li>
  <li>
    <button type="button" class="footer-legal-link" data-legal-key="copyright">
      <?php echo esc_html( $fcsd_legal['copyright']['title'] ?? __( 'Copyright', 'fcsd' ) ); ?>
    </button>
  </li>
</ul>

<!-- Modal legal reutilizable -->
<div id="fcsd-legal-overlay" class="legal-overlay" hidden>
  <div class="legal-modal" role="dialog" aria-modal="true" aria-labelledby="fcsd-legal-title">
    <button type="button" class="legal-modal__close" aria-label="<?php echo esc_attr( $fcsd_legal['closeText'] ?? __( 'Tancar', 'fcsd' ) ); ?>">
      &times;
    </button>
    <h2 id="fcsd-legal-title" class="legal-modal__title"></h2>
    <div class="legal-modal__body"></div>
  </div>
</div>
