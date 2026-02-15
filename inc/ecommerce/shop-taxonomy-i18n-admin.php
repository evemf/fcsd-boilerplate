<?php
/**
 * Admin UI for product category (fcsd_product_cat) i18n fields.
 *
 * The theme stores translations in term meta:
 *   _fcsd_i18n_name_{lang}
 *   _fcsd_i18n_slug_{lang}
 *
 * This file adds fields in WP Admin so editors can manage them without plugins.
 */

defined('ABSPATH') || exit;

/**
 * Render fields in "Add new category" screen.
 */
function fcsd_product_cat_add_i18n_fields(): void {
    if ( ! taxonomy_exists('fcsd_product_cat') ) return;

    wp_nonce_field( 'fcsd_product_cat_i18n', 'fcsd_product_cat_i18n_nonce' );

    $fields = [
        'es' => [ 'label' => 'Español' ],
        'en' => [ 'label' => 'English' ],
    ];

    foreach ( $fields as $lang => $cfg ) :
        ?>
        <div class="form-field term-fcsd-i18n-name-wrap">
            <label for="fcsd_i18n_name_<?php echo esc_attr($lang); ?>">
                <?php echo esc_html( 'Nombre (' . $cfg['label'] . ')' ); ?>
            </label>
            <input type="text" name="fcsd_i18n_name_<?php echo esc_attr($lang); ?>" id="fcsd_i18n_name_<?php echo esc_attr($lang); ?>" value="" />
            <p class="description">
                <?php echo esc_html( 'Opcional. Si lo dejas vacío, se usará el nombre canónico.' ); ?>
            </p>
        </div>

        <div class="form-field term-fcsd-i18n-slug-wrap">
            <label for="fcsd_i18n_slug_<?php echo esc_attr($lang); ?>">
                <?php echo esc_html( 'Slug (' . $cfg['label'] . ')' ); ?>
            </label>
            <input type="text" name="fcsd_i18n_slug_<?php echo esc_attr($lang); ?>" id="fcsd_i18n_slug_<?php echo esc_attr($lang); ?>" value="" />
            <p class="description">
                <?php echo esc_html( 'Opcional. Si lo dejas vacío, se usará el slug canónico.' ); ?>
            </p>
        </div>
        <?php
    endforeach;
}

/**
 * Render fields in "Edit category" screen.
 */
function fcsd_product_cat_edit_i18n_fields( WP_Term $term ): void {
    if ( $term->taxonomy !== 'fcsd_product_cat' ) return;

    wp_nonce_field( 'fcsd_product_cat_i18n', 'fcsd_product_cat_i18n_nonce' );

    $fields = [
        'es' => [ 'label' => 'Español' ],
        'en' => [ 'label' => 'English' ],
    ];

    foreach ( $fields as $lang => $cfg ) :
        $name = (string) get_term_meta( (int) $term->term_id, '_fcsd_i18n_name_' . $lang, true );
        $slug = (string) get_term_meta( (int) $term->term_id, '_fcsd_i18n_slug_' . $lang, true );
        ?>
        <tr class="form-field term-fcsd-i18n-name-wrap">
            <th scope="row">
                <label for="fcsd_i18n_name_<?php echo esc_attr($lang); ?>">
                    <?php echo esc_html( 'Nombre (' . $cfg['label'] . ')' ); ?>
                </label>
            </th>
            <td>
                <input type="text" name="fcsd_i18n_name_<?php echo esc_attr($lang); ?>" id="fcsd_i18n_name_<?php echo esc_attr($lang); ?>" value="<?php echo esc_attr( $name ); ?>" />
                <p class="description">
                    <?php echo esc_html( 'Opcional. Si lo dejas vacío, se usará el nombre canónico.' ); ?>
                </p>
            </td>
        </tr>

        <tr class="form-field term-fcsd-i18n-slug-wrap">
            <th scope="row">
                <label for="fcsd_i18n_slug_<?php echo esc_attr($lang); ?>">
                    <?php echo esc_html( 'Slug (' . $cfg['label'] . ')' ); ?>
                </label>
            </th>
            <td>
                <input type="text" name="fcsd_i18n_slug_<?php echo esc_attr($lang); ?>" id="fcsd_i18n_slug_<?php echo esc_attr($lang); ?>" value="<?php echo esc_attr( $slug ); ?>" />
                <p class="description">
                    <?php echo esc_html( 'Opcional. Si lo dejas vacío, se usará el slug canónico.' ); ?>
                </p>
            </td>
        </tr>
        <?php
    endforeach;
}

/**
 * Save termmeta on create/edit.
 */
function fcsd_product_cat_save_i18n_fields( int $term_id ): void {
    // If nonce isn't present (bulk ops, quick edits), don't block save.
    if ( isset($_POST['fcsd_product_cat_i18n_nonce']) ) {
        $nonce = sanitize_text_field( wp_unslash( $_POST['fcsd_product_cat_i18n_nonce'] ) );
        if ( ! wp_verify_nonce( $nonce, 'fcsd_product_cat_i18n' ) ) {
            return;
        }
    }

    if ( ! current_user_can( 'manage_categories' ) ) {
        return;
    }

    foreach ( ['es','en'] as $lang ) {
        $name_key = 'fcsd_i18n_name_' . $lang;
        $slug_key = 'fcsd_i18n_slug_' . $lang;

        if ( array_key_exists( $name_key, $_POST ) ) {
            $val = trim( (string) wp_unslash( $_POST[ $name_key ] ) );
            $val = sanitize_text_field( $val );
            if ( $val === '' ) {
                delete_term_meta( $term_id, '_fcsd_i18n_name_' . $lang );
            } else {
                update_term_meta( $term_id, '_fcsd_i18n_name_' . $lang, $val );
            }
        }

        if ( array_key_exists( $slug_key, $_POST ) ) {
            $val = trim( (string) wp_unslash( $_POST[ $slug_key ] ) );
            $val = sanitize_title( $val );
            if ( $val === '' ) {
                delete_term_meta( $term_id, '_fcsd_i18n_slug_' . $lang );
            } else {
                update_term_meta( $term_id, '_fcsd_i18n_slug_' . $lang, $val );
            }
        }
    }
}

// Hooks
add_action( 'fcsd_product_cat_add_form_fields', 'fcsd_product_cat_add_i18n_fields' );
add_action( 'fcsd_product_cat_edit_form_fields', 'fcsd_product_cat_edit_i18n_fields' );
add_action( 'created_fcsd_product_cat', 'fcsd_product_cat_save_i18n_fields', 10, 1 );
add_action( 'edited_fcsd_product_cat', 'fcsd_product_cat_save_i18n_fields', 10, 1 );
