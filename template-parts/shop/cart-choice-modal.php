<?php
/**
 * Modal reutilizable: al añadir un producto al carrito (botiga).
 *
 * Reutiliza las clases del modal legal del footer:
 * - .legal-overlay / .legal-modal
 *
 * JS: assets/js/cart-choice-modal.js
 */
defined('ABSPATH') || exit;
?>
<div id="fcsd-cart-choice-overlay" class="legal-overlay" hidden>
  <div class="legal-modal" role="dialog" aria-modal="true" aria-labelledby="fcsd-cart-choice-title">
    <button type="button" class="legal-modal__close js-fcsd-cart-choice-close" aria-label="<?php echo esc_attr__( 'Tancar', 'fcsd' ); ?>">
      &times;
    </button>
    <h2 id="fcsd-cart-choice-title" class="legal-modal__title">
      <?php echo esc_html__( 'Producte afegit a la cistella', 'fcsd' ); ?>
    </h2>
    <div class="legal-modal__body">
      <p><?php echo esc_html__( 'Què vols fer ara?', 'fcsd' ); ?></p>
      <div class="fcsd-cart-choice-actions">
        <button type="button" class="button button-primary js-fcsd-go-cart">
          <?php echo esc_html__( 'Anar a la cistella', 'fcsd' ); ?>
        </button>
        <button type="button" class="button js-fcsd-continue">
          <?php echo esc_html__( 'Continuar comprant', 'fcsd' ); ?>
        </button>
      </div>
    </div>
  </div>
</div>
