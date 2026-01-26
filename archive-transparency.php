<?php
/**
 * Archive del CPT Transparència
 * URL: /transparencia (slug definido en inc/cpts.php)
 */

get_header();
?>

<div class="container content py-5 transparency-archive">

  <header class="mb-4">
    <h1 class="mb-3"><?php post_type_archive_title(); ?></h1>
  </header>

  <?php
  // ------------------------------
  // 1) Entradas por año (auditoria + memòria)
  // ------------------------------
  $year_posts = get_posts( [
      'post_type'      => 'transparency',
      'numberposts'    => -1,
      'meta_key'       => '_fcsd_transparency_year',
      'orderby'        => 'meta_value_num',
      'order'          => 'DESC',
      'meta_query'     => [
          [
              'key'   => '_fcsd_transparency_type',
              'value' => 'year',
          ],
      ],
  ] );

  $audits   = [];
  $memories = [];

  foreach ( $year_posts as $p ) {
      $year     = get_post_meta( $p->ID, '_fcsd_transparency_year', true );
      $audit_id = (int) get_post_meta( $p->ID, '_fcsd_audit_pdf_id', true );
      $mem_id   = (int) get_post_meta( $p->ID, '_fcsd_memoria_pdf_id', true );

      if ( $audit_id ) {
          $url = wp_get_attachment_url( $audit_id );
          if ( $url ) {
              $audits[] = [
                  'year' => $year ?: get_the_title( $p ),
                  'url'  => $url,
              ];
          }
      }

      if ( $mem_id ) {
          $url = wp_get_attachment_url( $mem_id );
          if ( $url ) {
              $memories[] = [
                  'year' => $year ?: get_the_title( $p ),
                  'url'  => $url,
              ];
          }
      }
  }

  // ------------------------------
  // 2) Recuadros rosas (PDF único)
  // ------------------------------
  $box_posts = get_posts( [
      'post_type'      => 'transparency',
      'numberposts'    => -1,
      'orderby'        => 'menu_order',
      'order'          => 'ASC',
      'meta_query'     => [
          [
              'key'   => '_fcsd_transparency_type',
              'value' => 'single',
          ],
      ],
  ] );

  // ------------------------------
  // 3) Apartados desplegables (accordion)
  // ------------------------------
  $accordion_posts = get_posts( [
      'post_type'      => 'transparency',
      'numberposts'    => -1,
      'orderby'        => 'menu_order',
      'order'          => 'ASC',
      'meta_query'     => [
          [
              'key'   => '_fcsd_transparency_type',
              'value' => 'accordion',
          ],
      ],
  ] );
  ?>

  <?php if ( $audits || $memories ) : ?>
    <div class="row g-4 mb-4 transparency-year-row">

      <div class="col-md-6">
        <div class="p-3 border rounded h-100">
          <h2 class="h4 mb-3"><?php esc_html_e( 'Auditoria de comptes', 'fcsd' ); ?></h2>
          <ul class="list-unstyled mb-0">
            <?php foreach ( $audits as $item ) : ?>
              <li>
                <a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener">
                  <?php
                    printf(
                        esc_html__( 'Auditoria %s', 'fcsd' ),
                        esc_html( $item['year'] )
                    );
                  ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <div class="col-md-6">
        <div class="p-3 border rounded h-100">
          <h2 class="h4 mb-3"><?php esc_html_e( 'Memòries d’activitats', 'fcsd' ); ?></h2>
          <ul class="list-unstyled mb-0">
            <?php foreach ( $memories as $item ) : ?>
              <li>
                <a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener">
                  <?php
                    printf(
                        esc_html__( 'Memòria %s', 'fcsd' ),
                        esc_html( $item['year'] )
                    );
                  ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

    </div>
  <?php endif; ?>

  <?php if ( $box_posts ) : ?>
    <div class="row g-3 mb-4 transparency-box-row">
      <?php foreach ( $box_posts as $p ) :
          $single_id = (int) get_post_meta( $p->ID, '_fcsd_single_pdf_id', true );
          $url       = $single_id ? wp_get_attachment_url( $single_id ) : '';
          if ( ! $url ) {
              continue;
          }
      ?>
        <div class="col-6 col-md-2">
          <a href="<?php echo esc_url( $url ); ?>"
             class="d-flex align-items-center justify-content-center text-center text-white text-decoration-none p-3"
             style="background:#E7A15A; min-height:140px;"
             target="_blank"
             rel="noopener">
            <span class="fw-bold text-uppercase small">
              <?php echo esc_html( get_the_title( $p ) ); ?>
            </span>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ( $accordion_posts ) : ?>
    <div class="mt-4 transparency-accordion-wrapper">
      <div class="accordion" id="transparencyAccordion">
        <?php foreach ( $accordion_posts as $index => $p ) :
            $item_id   = (int) $p->ID;
            $collapse_id = 'transparency-collapse-' . $item_id;
            $heading_id  = 'transparency-heading-' . $item_id;
        ?>
          <div class="accordion-item">
            <h2 class="accordion-header" id="<?php echo esc_attr( $heading_id ); ?>">
              <button class="accordion-button collapsed"
                      type="button"
                      data-bs-toggle="collapse"
                      data-bs-target="#<?php echo esc_attr( $collapse_id ); ?>"
                      aria-expanded="false"
                      aria-controls="<?php echo esc_attr( $collapse_id ); ?>">
                <?php echo esc_html( get_the_title( $p ) ); ?>
              </button>
            </h2>
            <div id="<?php echo esc_attr( $collapse_id ); ?>"
                 class="accordion-collapse collapse"
                 aria-labelledby="<?php echo esc_attr( $heading_id ); ?>"
                 data-bs-parent="#transparencyAccordion">
              <div class="accordion-body">
                <?php echo apply_filters( 'the_content', $p->post_content ); ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

</div>

<?php get_footer(); ?>
