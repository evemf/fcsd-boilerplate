<?php
/**
 * Ecommerce helpers
 *
 * Reusable helpers used across the shop templates.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Extract readable text from WPBakery/Visual Composer content.
 *
 * We prioritise the content inside [vc_column_text] blocks because they
 * usually contain the editorial copy. If none are found, we fall back to
 * a generic "best effort" cleanup of the raw content.
 *
 * @param string $raw Raw post content.
 * @return string Clean, human readable text.
 */
function fcsd_extract_vc_column_text( string $raw ): string {
    if ( $raw === '' ) {
        return '';
    }

    // Normalise and decode HTML entities.
    $s = html_entity_decode( $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $s = str_replace( [ "\r\n", "\r" ], "\n", $s );

    // Some imports escape shortcodes as [[vc_row]] or similar.
    $s = str_replace( [ '[[', ']]' ], [ '[', ']' ], $s );

    // Extract vc_column_text blocks.
    if ( preg_match_all( '/\[vc_column_text[^\]]*\](.*?)\[\/vc_column_text\]/is', $s, $m ) && ! empty( $m[1] ) ) {
        $best = '';
        foreach ( $m[1] as $chunk ) {
            $chunk = trim( (string) $chunk );
            if ( mb_strlen( $chunk ) > mb_strlen( $best ) ) {
                $best = $chunk;
            }
        }

        $best = html_entity_decode( $best, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $best = strip_shortcodes( $best );
        $best = wp_strip_all_tags( $best );

        // Normalise whitespace and common import artefacts (" n ").
        $best = str_replace( [ "\t", "\n" ], ' ', $best );
        $best = preg_replace( '/\s+n\s+/u', ' ', $best );
        $best = preg_replace( '/\s+/u', ' ', $best );

        return trim( $best );
    }

    // Fallback: generic cleanup.
    $s = strip_shortcodes( $s );
    $s = wp_strip_all_tags( $s );
    $s = str_replace( [ "\t", "\n" ], ' ', $s );
    $s = preg_replace( '/\s+/u', ' ', $s );

    return trim( $s );
}

/**
 * Get a clean product preview text for cards / listings.
 *
 * @param int $product_id Product post ID.
 * @param int $words Number of words to trim to.
 * @return string
 */
function fcsd_get_product_card_preview( int $product_id, int $words = 18 ): string {
    $raw = (string) get_post_field( 'post_content', $product_id );
    $text = fcsd_extract_vc_column_text( $raw );

    if ( $text === '' ) {
        // As last resort, try excerpt.
        $text = (string) get_post_field( 'post_excerpt', $product_id );
        $text = wp_strip_all_tags( strip_shortcodes( html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
        $text = preg_replace( '/\s+/u', ' ', $text );
        $text = trim( $text );
    }

    if ( $text === '' ) {
        return '';
    }

    return wp_trim_words( $text, $words );
}

/**
 * Get a safe HTML long description for a product.
 *
 * The project avoids depending on page builders, but imported products may
 * contain WPBakery/VC shortcodes. The MU-importer stores a cleaned HTML
 * version in the `_fcsd_description_html` meta. We use that whenever available
 * and fall back to a best-effort extraction from post_content.
 *
 * @param int $product_id Product post ID.
 * @return string HTML.
 */
function fcsd_get_product_description_html( int $product_id ): string {
    $meta = get_post_meta( $product_id, '_fcsd_description_html', true );
    if ( is_string( $meta ) && trim( $meta ) !== '' ) {
        // The importer already runs wpautop + kses.
        return (string) $meta;
    }

    $raw = (string) get_post_field( 'post_content', $product_id );
    if ( $raw === '' ) {
        return '';
    }

    $s = html_entity_decode( $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $s = str_replace( [ "\r\n", "\r" ], "\n", $s );
    $s = str_replace( [ '[[', ']]' ], [ '[', ']' ], $s );

    // Prefer vc_column_text blocks, but keep inner HTML.
    if ( preg_match_all( '/\[vc_column_text[^\]]*\](.*?)\[\/vc_column_text\]/is', $s, $m ) && ! empty( $m[1] ) ) {
        $best = '';
        foreach ( $m[1] as $chunk ) {
            $chunk = trim( (string) $chunk );
            if ( mb_strlen( $chunk ) > mb_strlen( $best ) ) {
                $best = $chunk;
            }
        }
        $best = html_entity_decode( $best, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $best = preg_replace( '/^\s*n\s*$/m', '', $best );
        return (string) wpautop( wp_kses_post( $best ) );
    }

    // Fallback: strip any shortcode-like tags, even if not registered.
    $s = preg_replace( '/\[[a-zA-Z0-9_\-]+[^\]]*\]/', '', $s );
    $s = preg_replace( '/\[\/[a-zA-Z0-9_\-]+\]/', '', $s );
    $s = preg_replace( '/^\s*n\s*$/m', '', $s );

    return (string) wpautop( wp_kses_post( trim( $s ) ) );
}
