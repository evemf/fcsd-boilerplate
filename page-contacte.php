<?php
/**
 * Page template for the contact page.
 *
 * Why this file exists:
 * - WordPress uses the template hierarchy `page-{slug}.php`.
 * - We keep `page-contacte.php` for the Catalan slug `/contacte/`.
 * - We keep `page-contact.php` for the English slug `/contact/`.
 *
 * Both slugs render the same UI, so we delegate to a single source of truth.
 */

defined( 'ABSPATH' ) || exit;

// Reuse the shared template.
require locate_template( 'page-contact.php' );
