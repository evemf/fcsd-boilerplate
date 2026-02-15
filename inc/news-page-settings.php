<?php
/**
 * FCSD – Ajustes "Página de noticias" (CPT: news)
 *
 * Objetivo:
 * - Importación: NO se filtra (se importan todas las noticias del feed).
 * - Página/listado de noticias: filtrar por las categorías seleccionadas aquí.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

const FCSD_NEWS_PAGE_CATS_OPTION = 'fcsd_news_page_category_slugs';

/**
 * Slugs seleccionados para mostrar en página/listado de noticias.
 * Fallback: ['fcsd'] si no hay selección.
 */
function fcsd_news_page_selected_category_slugs(): array {
    $slugs = get_option( FCSD_NEWS_PAGE_CATS_OPTION, [] );
    if ( ! is_array( $slugs ) ) {
        $slugs = [];
    }
    $slugs = array_values( array_filter( array_map( 'sanitize_title', $slugs ) ) );
    return $slugs ?: [ 'fcsd' ];
}

/**
 * Categorías detectadas en noticias EXIT21 (meta news_source=exit21).
 */
function fcsd_news_page_detect_exit21_categories(): array {
    $q = new WP_Query([
        'post_type'      => 'news',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_query'     => [
            [
                'key'     => 'news_source',
                'value'   => 'exit21',
                'compare' => '=',
            ],
        ],
    ]);

    $ids = is_array( $q->posts ) ? $q->posts : [];
    if ( empty( $ids ) ) {
        return [];
    }

    $terms = wp_get_object_terms( $ids, 'category', [
        'orderby' => 'name',
        'order'   => 'ASC',
    ]);

    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return [];
    }

    $uniq = [];
    foreach ( $terms as $t ) {
        $uniq[ $t->term_id ] = $t;
    }
    return array_values( $uniq );
}

add_action( 'admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=news',
        __( 'Página de noticias', 'fcsd' ),
        __( 'Página de noticias', 'fcsd' ),
        'manage_options',
        'fcsd-news-page-settings',
        'fcsd_render_news_page_settings'
    );
} );

function fcsd_render_news_page_settings() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'fcsd' ) );
    }

    if ( isset( $_POST['fcsd_news_page_settings_submit'] ) ) {
        check_admin_referer( 'fcsd_news_page_settings_save', 'fcsd_news_page_settings_nonce' );

        $slugs = $_POST['fcsd_news_page_category_slugs'] ?? [];
        if ( ! is_array( $slugs ) ) {
            $slugs = [];
        }
        $slugs = array_values( array_filter( array_map( 'sanitize_title', $slugs ) ) );
        update_option( FCSD_NEWS_PAGE_CATS_OPTION, $slugs, false );

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Ajustes guardados.', 'fcsd' ) . '</p></div>';
    }

    $selected = get_option( FCSD_NEWS_PAGE_CATS_OPTION, [] );
    if ( ! is_array( $selected ) ) {
        $selected = [];
    }
    $selected = array_values( array_filter( array_map( 'sanitize_title', $selected ) ) );

    $terms = fcsd_news_page_detect_exit21_categories();
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Página de noticias', 'fcsd' ); ?></h1>
        <p><?php echo esc_html__( 'Selecciona qué categorías del feed quieres mostrar en la página/listado de noticias. La importación sigue trayendo todas las noticias.', 'fcsd' ); ?></p>

        <form method="post">
            <?php wp_nonce_field( 'fcsd_news_page_settings_save', 'fcsd_news_page_settings_nonce' ); ?>

            <table class="form-table" role="presentation">
                <tbody>
                <tr>
                    <th scope="row"><?php echo esc_html__( 'Categorías a mostrar', 'fcsd' ); ?></th>
                    <td>
                        <?php if ( empty( $terms ) ) : ?>
                            <p><?php echo esc_html__( 'No se han detectado categorías del feed todavía (o no hay noticias importadas de EXIT21).', 'fcsd' ); ?></p>
                        <?php else : ?>
                            <fieldset>
                                <?php foreach ( $terms as $t ) :
                                    $slug = sanitize_title( $t->slug ?: $t->name );
                                    ?>
                                    <label style="display:block; margin:4px 0;">
                                        <input type="checkbox"
                                               name="fcsd_news_page_category_slugs[]"
                                               value="<?php echo esc_attr( $slug ); ?>"
                                               <?php checked( in_array( $slug, $selected, true ) ); ?> />
                                        <strong><?php echo esc_html( $t->name ); ?></strong>
                                        <code style="margin-left:6px;"><?php echo esc_html( $slug ); ?></code>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                            <p class="description"><?php echo esc_html__( 'Si no seleccionas ninguna, por defecto se mostrará la categoría "fcsd".', 'fcsd' ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                </tbody>
            </table>

            <p class="submit">
                <button type="submit" name="fcsd_news_page_settings_submit" class="button button-primary">
                    <?php echo esc_html__( 'Guardar cambios', 'fcsd' ); ?>
                </button>
            </p>
        </form>
    </div>
    <?php
}
