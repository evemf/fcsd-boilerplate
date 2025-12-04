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
        'post_type'        => 'timeline_year',
        'post_status'      => 'publish',
        'numberposts'      => -1,
        'orderby'          => 'title', // el títol serà "1982", "1983", etc.
        'order'            => 'ASC',
        'suppress_filters' => false,
      ] );
      ?>

      <?php if ( $history_years ) : ?>

        <?php
        // Preparem dades de cada any + esdeveniments per reutilitzar-les
        $years_data = [];

        foreach ( $history_years as $year ) {
          $year_id = $year->ID;

          $events = get_posts( [
            'post_type'        => 'timeline_event',
            'post_status'      => 'publish',
            'numberposts'      => -1,
            'meta_key'         => 'timeline_year_id',
            'meta_value'       => $year_id,
            'orderby'          => 'menu_order',
            'order'            => 'ASC',
            'suppress_filters' => false,
          ] );

          $years_data[] = [
            'id'         => $year_id,
            'title'      => get_the_title( $year ),
            'events'     => $events,
            'has_events' => ! empty( $events ),
          ];
        }
        ?>

        <?php if ( ! empty( $years_data ) ) : ?>

          <!-- ===== Desktop: fila d'anys horitzontal + un sol panell ===== -->
          <div class="history-timeline history-timeline--desktop" data-history-timeline-desktop>

            <div class="history-years-nav" role="tablist" aria-label="<?php esc_attr_e( 'Cronologia per anys', 'fcsd' ); ?>">
              <?php foreach ( $years_data as $index => $year_data ) : ?>
                <?php
                $is_active = ( 0 === $index );
                $year_key  = 'year-' . $year_data['id'];
                $tab_id    = 'history-tab-' . $year_key;
                $panel_id  = 'history-tabpanel-' . $year_key;
                ?>
                <button
                  type="button"
                  class="history-year-pill<?php echo $is_active ? ' is-active' : ''; ?>"
                  id="<?php echo esc_attr( $tab_id ); ?>"
                  role="tab"
                  aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                  aria-controls="<?php echo esc_attr( $panel_id ); ?>"
                  tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
                  data-history-tab="<?php echo esc_attr( $year_key ); ?>"
                >
                  <?php echo esc_html( $year_data['title'] ); ?>
                </button>
              <?php endforeach; ?>
            </div>

            <div class="history-year-panel-wrapper">
              <?php foreach ( $years_data as $index => $year_data ) : ?>
                <?php
                $is_active = ( 0 === $index );
                $year_key  = 'year-' . $year_data['id'];
                $tab_id    = 'history-tab-' . $year_key;
                $panel_id  = 'history-tabpanel-' . $year_key;
                ?>
                <section
                  id="<?php echo esc_attr( $panel_id ); ?>"
                  class="history-year-panel"
                  role="tabpanel"
                  aria-labelledby="<?php echo esc_attr( $tab_id ); ?>"
                  data-history-panel="<?php echo esc_attr( $year_key ); ?>"
                  <?php if ( ! $is_active ) : ?>hidden<?php endif; ?>
                >
                  <?php if ( $year_data['has_events'] ) : ?>
                    <ul class="history-events">
                      <?php foreach ( $year_data['events'] as $event ) : ?>
                        <?php
                        $event_title   = get_the_title( $event );
                        $event_excerpt = get_the_excerpt( $event );
                        $event_desc_raw = trim( wp_strip_all_tags( $event_excerpt ) );

                        if ( '' === $event_desc_raw ) {
                          $event_desc_raw = trim( wp_strip_all_tags( $event->post_content ) );
                        }
                        ?>
                        <li class="history-event">
                          <p class="history-event__title">
                            <?php echo esc_html( $event_title ); ?>
                          </p>

                          <?php if ( '' !== $event_desc_raw ) : ?>
                            <p class="history-event__description">
                              <?php echo esc_html( $event_desc_raw ); ?>
                            </p>
                          <?php endif; ?>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php else : ?>
                    <p class="history-year__empty">
                      <?php esc_html_e( 'Encara no hi ha esdeveniments registrats per aquest any.', 'fcsd' ); ?>
                    </p>
                  <?php endif; ?>
                </section>
              <?php endforeach; ?>
            </div>

          </div><!-- /.history-timeline--desktop -->

          <!-- ===== Mòbil: acordeó vertical (un panell per any) ===== -->
          <div class="history-timeline history-timeline--mobile" data-history-timeline-mobile>

            <?php foreach ( $years_data as $index => $year_data ) : ?>
              <?php
              $is_open  = ( 0 === $index );
              $year_key = 'year-' . $year_data['id'];
              $panel_id = 'history-mobile-' . $year_key;
              $is_empty = ! $year_data['has_events'];
              ?>
              <section class="history-year<?php echo $is_empty ? ' history-year--empty' : ''; ?>">
                <button
                  class="history-year__toggle"
                  type="button"
                  aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
                  aria-controls="<?php echo esc_attr( $panel_id ); ?>"
                >
                  <span class="history-year__label">
                    <?php echo esc_html( $year_data['title'] ); ?>
                  </span>
                  <span class="history-year__chevron" aria-hidden="true"></span>
                </button>

                <div
                  id="<?php echo esc_attr( $panel_id ); ?>"
                  class="history-year__panel"
                  <?php if ( ! $is_open ) : ?>hidden<?php endif; ?>
                >
                  <?php if ( $year_data['has_events'] ) : ?>
                    <ul class="history-events">
                      <?php foreach ( $year_data['events'] as $event ) : ?>
                        <?php
                        $event_title   = get_the_title( $event );
                        $event_excerpt = get_the_excerpt( $event );
                        $event_desc_raw = trim( wp_strip_all_tags( $event_excerpt ) );

                        if ( '' === $event_desc_raw ) {
                          $event_desc_raw = trim( wp_strip_all_tags( $event->post_content ) );
                        }
                        ?>
                        <li class="history-event">
                          <p class="history-event__title">
                            <?php echo esc_html( $event_title ); ?>
                          </p>

                          <?php if ( '' !== $event_desc_raw ) : ?>
                            <p class="history-event__description">
                              <?php echo esc_html( $event_desc_raw ); ?>
                            </p>
                          <?php endif; ?>
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

          </div><!-- /.history-timeline--mobile -->

        <?php endif; // ! empty( $years_data ) ?>

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
  // ===== Desktop: tabs =====
  const desktopTimeline = document.querySelector('[data-history-timeline-desktop]');
  if (desktopTimeline) {
    const tabButtons = Array.from(desktopTimeline.querySelectorAll('[data-history-tab]'));
    const panels = Array.from(desktopTimeline.querySelectorAll('[data-history-panel]'));

    tabButtons.forEach(function(button) {
      button.addEventListener('click', function() {
        const target = button.getAttribute('data-history-tab');

        tabButtons.forEach(function(btn) {
          const isActive = btn === button;
          btn.classList.toggle('is-active', isActive);
          btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
          btn.setAttribute('tabindex', isActive ? '0' : '-1');
        });

        panels.forEach(function(panel) {
          const match = panel.getAttribute('data-history-panel') === target;
          if (match) {
            panel.removeAttribute('hidden');
          } else {
            panel.setAttribute('hidden', 'hidden');
          }
        });
      });
    });
  }

  // ===== Mòbil: acordeó =====
  const mobileTimeline = document.querySelector('[data-history-timeline-mobile]');
  if (mobileTimeline) {
    mobileTimeline.addEventListener('click', function(event) {
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
  }
})();
</script>

<?php
get_footer();
