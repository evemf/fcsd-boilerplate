<?php
/**
 * Helpers per a la taxonomia event_formation (Formacions)
 */

defined( 'ABSPATH' ) || exit;

/**
 * Retorna el valor traduït d'un camp del terme (name|description) segons idioma.
 * Guardem les traduccions a term meta:
 * - fcsd_i18n_name_ca / _es / _en
 * - fcsd_i18n_description_ca / _es / _en
 *
 * @param WP_Term|int $term
 * @param string $field 'name'|'description'
 * @param string|null $lang  'ca'|'es'|'en' (per defecte, fcsd_lang() si existeix)
 */
function fcsd_event_formation_i18n_field( $term, string $field = 'name', ?string $lang = null ): string {
    $term_obj = is_numeric( $term ) ? get_term( (int) $term, 'event_formation' ) : $term;
    if ( ! ( $term_obj instanceof WP_Term ) ) {
        return '';
    }

    if ( $lang === null ) {
        $lang = function_exists( 'fcsd_lang' ) ? fcsd_lang() : 'ca';
    }
    if ( ! in_array( $lang, [ 'ca', 'es', 'en' ], true ) ) {
        $lang = 'ca';
    }

    $field = $field === 'description' ? 'description' : 'name';
    $meta_key = 'fcsd_i18n_' . $field . '_' . $lang;
    $translated = get_term_meta( $term_obj->term_id, $meta_key, true );
    if ( is_string( $translated ) && $translated !== '' ) {
        return $translated;
    }

    // fallback: camp natiu WP
    return $field === 'description' ? (string) $term_obj->description : (string) $term_obj->name;
}

/**
 * HTML per a icona (mòbil) i imatge (desktop).
 *
 * Term meta:
 * - fcsd_event_formation_icon (string, p.ej. emoji o dashicon class)
 * - fcsd_event_formation_image_id (attachment ID)
 */
function fcsd_event_formation_media_html( $term ): string {
    $term_obj = is_numeric( $term ) ? get_term( (int) $term, 'event_formation' ) : $term;
    if ( ! ( $term_obj instanceof WP_Term ) ) {
        return '';
    }

    $icon = get_term_meta( $term_obj->term_id, 'fcsd_event_formation_icon', true );
    $icon = is_string( $icon ) ? trim( $icon ) : '';

    $image_id = (int) get_term_meta( $term_obj->term_id, 'fcsd_event_formation_image_id', true );

    $parts = [];

    if ( $icon !== '' ) {
        // Si és una classe dashicons, permetem "dashicons dashicons-..."
        if ( strpos( $icon, 'dashicons' ) === 0 ) {
            $parts[] = '<span class="fcsd-term-icon fcsd-term-icon--dashicons ' . esc_attr( $icon ) . '" aria-hidden="true"></span>';
        } else {
            $parts[] = '<span class="fcsd-term-icon" aria-hidden="true">' . esc_html( $icon ) . '</span>';
        }
    }

    if ( $image_id > 0 ) {
        $img = wp_get_attachment_image( $image_id, 'thumbnail', false, [
            'class'   => 'fcsd-term-image',
            'loading' => 'lazy',
            'alt'     => '',
        ] );
        if ( $img ) {
            $parts[] = $img;
        }
    }

    return implode( '', $parts );
}
