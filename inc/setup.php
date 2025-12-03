<?php

require_once __DIR__ . '/sinergia-events.php';

// Nota: la creació i manteniment de les taules de Sinergia
// es fa exclusivament a inc/sinergia-cache.php.
// Aquest fitxer només conté helpers generals del tema.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Intentar augmentar límits de PHP des de WordPress (si el hosting ho permet).
function fcsd_raise_upload_limits() {
    @ini_set( 'upload_max_filesize', '64M' );
    @ini_set( 'post_max_size', '64M' );
    @ini_set( 'memory_limit', '128M' );
    @ini_set( 'max_execution_time', '120' );
    @ini_set( 'max_input_time', '120' );
}
add_action( 'init', 'fcsd_raise_upload_limits' );
