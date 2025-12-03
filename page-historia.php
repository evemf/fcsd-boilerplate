<?php
/**
 * Template Name: Història
 */

get_header();
?>

<div class="container content py-5 fcsd-history">
  <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

      <header class="history-header mb-4">
        <h1 class="mb-3"><?php the_title(); ?></h1>
        <div class="history-intro">
          <?php the_content(); ?>
        </div>
      </header>

      <?php
      // Recuperem tots els anys de la cronologia
      $history_years = get_posts( [
        'post_type'      => 'timeline_year',
        'post_status'    => 'publish',
        'numberposts'    => -1,
        'orderby'        => 'title', // el títol serà "1982", "1983", etc.
        'order'          => 'ASC',
        'suppress_filters' => false,
      ] );
      ?>

      <?php if ( $history_years ) : ?>
        <div class="history-timeline" data-history-timeline>
          <?php foreach ( $history_years as $index => $year ) :
            $year_id    = $year->ID;
            $panel_id   = 'history-year-' . $year_id;

            $events = get_posts( [
              'post_type'      => 'timeline_event',
              'post_status'    => 'publish',
              'numberposts'    => -1,
              'meta_key'       => 'timeline_year_id',
              'meta_value'     => $year_id,
              'orderby'        => 'menu_order',
              'order'          => 'ASC',
              'suppress_filters' => false,
            ] );

            $has_events = ! empty( $events );
          ?>
            <section class="history-year <?php echo $has_events ? '' : 'history-year--empty'; ?>">
              <button
                class="history-year__toggle"
                type="button"
                aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                aria-controls="<?php echo esc_attr( $panel_id ); ?>"
              >
                <span class="history-year__label">
                  <?php echo esc_html( get_the_title( $year ) ); ?>
                </span>
                <span class="history-year__chevron" aria-hidden="true"></span>
              </button>

              <div
                id="<?php echo esc_attr( $panel_id ); ?>"
                class="history-year__panel"
                <?php if ( $index !== 0 ) : ?>
                  hidden
                <?php endif; ?>
              >
                <?php if ( $has_events ) : ?>
                  <ul class="history-events">
                    <?php foreach ( $events as $event ) : ?>
                      <li class="history-event">
                        <h3 class="history-event__title">
                          <?php echo esc_html( get_the_title( $event ) ); ?>
                        </h3>
                        <div class="history-event__content">
                          <?php
                          // Contingut complet de l'esdeveniment
                          echo apply_filters( 'the_content', $event->post_content );
                          ?>
                        </div>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php else : ?>
                  <p class="history-year__empty">
                    <?php esc_html_e( 'Encara no hi ha esdeveniments registrats per aquest any.', 'fcsd' ); ?>
                  </p>
                <?php endif; ?>
              </div>
            </section>
          <?php endforeach; ?>
        </div>
      <?php else : ?>
        <p>
          <?php esc_html_e( 'Encara no s\'ha configurat cap any de la cronologia.', 'fcsd' ); ?>
        </p>
      <?php endif; ?>

    </article>
  <?php endwhile; endif; ?>
</div>

<script>
(function() {
  const timeline = document.querySelector('[data-history-timeline]');
  if (!timeline) return;

  timeline.addEventListener('click', function(event) {
    const toggle = event.target.closest('.history-year__toggle');
    if (!toggle) return;

    const panelId  = toggle.getAttribute('aria-controls');
    const panel    = document.getElementById(panelId);
    const expanded = toggle.getAttribute('aria-expanded') === 'true';

    toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');

    if (panel) {
      if (expanded) {
        panel.setAttribute('hidden', 'hidden');
      } else {
        panel.removeAttribute('hidden');
      }
    }
  });
})();
</script>

<?php
get_footer();
