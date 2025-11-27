<?php
/**
 * Template part: mapa del footer
 */

// Repetimos la lógica mínima para el mapa, igual que en footer.
$default_address = "Fundació Catalana Síndrome de Down\nComte Borrell, 201–203, entresòl\n08029 Barcelona\nEspanya";

$address_raw = trim( (string) get_theme_mod( 'fcsd_footer_address', $default_address ) );
$address_raw = $address_raw !== '' ? $address_raw : $default_address;

$address_line = preg_replace( '/\s+/', ' ', $address_raw );
$address_q    = urlencode( $address_line );
?>

<?php if ( ! empty( $address_line ) ) : ?>
  <div class="c-footer__map" aria-label="<?php echo esc_attr__( 'Mapa de localització', 'fcsd' ); ?>">
    <iframe
      class="google-map"
      title="<?php echo esc_attr__( 'Mapa de la seu', 'fcsd' ); ?>"
      src="https://www.google.com/maps?output=embed&q=<?php echo esc_attr( $address_q ); ?>"
      width="100%"
      height="300"
      loading="lazy"
      referrerpolicy="no-referrer-when-downgrade"
      allowfullscreen
    ></iframe>
    <p class="c-footer__map-actions">
      <a class="button button--ghost" target="_blank" rel="noopener"
         href="https://www.google.com/maps/search/?api=1&query=<?php echo esc_attr( $address_q ); ?>">
        <?php echo esc_html__( 'Com arribar-hi', 'fcsd' ); ?>
      </a>
    </p>
  </div>
<?php endif; ?>
