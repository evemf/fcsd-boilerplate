<?php
/**
 * Navbar de filtros de la tienda
 * Ubicación: template-parts/shop/navbar-filters.php
 */

// Valores seleccionados actualmente (para mantener el estado del filtro)
$current_cat   = isset( $_GET['product_cat'] ) ? (int) $_GET['product_cat'] : 0;
$current_color = isset( $_GET['color'] ) ? sanitize_key( wp_unslash( $_GET['color'] ) ) : '';
$price_min     = isset( $_GET['price_min'] ) ? (float) $_GET['price_min'] : '';
$price_max     = isset( $_GET['price_max'] ) ? (float) $_GET['price_max'] : '';
$search        = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

// Colores disponibles (si existe el helper en shop-core.php lo usamos)
$shop_colors = [];
if ( function_exists( 'fcsd_get_shop_colors' ) ) {
    $shop_colors = fcsd_get_shop_colors();
}
?>

<nav class="shop-filters navbar navbar-expand-lg bg-body-tertiary mb-4 rounded-3 shadow-sm">
    <div class="container-fluid">

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#shopFilters"
                aria-controls="shopFilters"
                aria-expanded="false"
                aria-label="<?php esc_attr_e( 'Mostrar filtres', 'fcsd' ); ?>">
            <span class="navbar-toggler-icon"></span>
            <span class="ms-2 small"><?php esc_html_e( 'Filtres', 'fcsd' ); ?></span>
        </button>

        <div class="collapse navbar-collapse" id="shopFilters">
            <form class="w-100 d-flex flex-column flex-lg-row flex-wrap gap-3 align-items-end mt-3 mt-lg-0"
                  method="get"
                  action="<?php echo esc_url( get_post_type_archive_link( 'product' ) ); ?>">

                <!-- Buscador -->
                <div class="flex-grow-1" style="min-width:180px;">
                    <label for="shop-search" class="form-label small mb-1">
                        <?php esc_html_e( 'Buscar', 'fcsd' ); ?>
                    </label>
                    <input type="search"
                           class="form-control form-control-sm"
                           id="shop-search"
                           name="s"
                           value="<?php echo esc_attr( $search ); ?>"
                           placeholder="<?php esc_attr_e( 'Cerca productes…', 'fcsd' ); ?>">
                    <input type="hidden" name="post_type" value="product">
                </div>

                <!-- Categoría -->
                <div style="min-width:180px;">
                    <label for="shop-cat" class="form-label small mb-1">
                        <?php esc_html_e( 'Categoria', 'fcsd' ); ?>
                    </label>
                    <select id="shop-cat"
                            name="product_cat"
                            class="form-select form-select-sm">
                        <option value=""><?php esc_html_e( 'Totes', 'fcsd' ); ?></option>
                        <?php
                        $terms = get_terms(
                            [
                                'taxonomy'   => 'product_cat',
                                'hide_empty' => true,
                            ]
                        );
                        if ( ! is_wp_error( $terms ) ) :
                            foreach ( $terms as $term ) :
                                ?>
                                <option value="<?php echo esc_attr( $term->term_id ); ?>"
                                    <?php selected( $current_cat, $term->term_id ); ?>>
                                    <?php echo esc_html( $term->name ); ?>
                                </option>
                            <?php
                            endforeach;
                        endif;
                        ?>
                    </select>
                </div>

                <!-- Color (lista de colores predefinidos) -->
                <div style="min-width:180px;">
                    <label class="form-label small mb-1">
                        <?php esc_html_e( 'Color', 'fcsd' ); ?>
                    </label>

                    <div class="d-flex flex-wrap gap-2">

                        <!-- Opción: cualquier color -->
                        <label class="btn btn-outline-secondary btn-sm mb-0">
                            <input type="radio"
                                   class="btn-check"
                                   name="color"
                                   value=""
                                <?php checked( $current_color, '' ); ?>>
                            <span><?php esc_html_e( 'Qualsevol', 'fcsd' ); ?></span>
                        </label>

                        <?php if ( ! empty( $shop_colors ) ) : ?>
                            <?php foreach ( $shop_colors as $slug => $data ) : ?>
                                <?php
                                $hex   = isset( $data['hex'] ) ? $data['hex'] : '#000000';
                                $label = isset( $data['label'] ) ? $data['label'] : $slug;
                                ?>
                                <label class="btn btn-outline-secondary btn-sm mb-0 d-flex align-items-center gap-2">
                                    <input type="radio"
                                           class="btn-check"
                                           name="color"
                                           value="<?php echo esc_attr( $slug ); ?>"
                                        <?php checked( $current_color, $slug ); ?>>

                                    <span class="d-inline-block rounded-circle"
                                          style="width:14px;height:14px;background-color:<?php echo esc_attr( $hex ); ?>;"></span>

                                    <span><?php echo esc_html( $label ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Precio -->
                <div class="d-flex flex-row flex-wrap gap-2">
                    <div>
                        <label for="price-min" class="form-label small mb-1">
                            <?php esc_html_e( 'Preu mín.', 'fcsd' ); ?>
                        </label>
                        <input type="number"
                               step="0.01"
                               min="0"
                               id="price-min"
                               name="price_min"
                               class="form-control form-control-sm"
                               value="<?php echo esc_attr( $price_min ); ?>">
                    </div>

                    <div>
                        <label for="price-max" class="form-label small mb-1">
                            <?php esc_html_e( 'Preu màx.', 'fcsd' ); ?>
                        </label>
                        <input type="number"
                               step="0.01"
                               min="0"
                               id="price-max"
                               name="price_max"
                               class="form-control form-control-sm"
                               value="<?php echo esc_attr( $price_max ); ?>">
                    </div>
                </div>

                <!-- Botones -->
                <div class="ms-lg-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <?php esc_html_e( 'Aplicar filtres', 'fcsd' ); ?>
                    </button>

                    <a class="btn btn-outline-secondary btn-sm"
                       href="<?php echo esc_url( get_post_type_archive_link( 'product' ) ); ?>">
                        <?php esc_html_e( 'Netejar', 'fcsd' ); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
</nav>
