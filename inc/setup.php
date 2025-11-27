<?php

require_once __DIR__ . '/sinergia-events.php';

add_action('after_switch_theme', 'fcsd_sinergia_install_tables');

function fcsd_sinergia_install_tables() {
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();
    $contacts_table = $wpdb->prefix . 'fcsd_sinergia_contacts';
    $events_table   = $wpdb->prefix . 'fcsd_sinergia_events';

    // Tabla Contacts
    $sql_contacts = "CREATE TABLE $contacts_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        sinergia_id VARCHAR(36) NOT NULL,
        email VARCHAR(190) NULL,
        payload LONGTEXT NOT NULL,
        date_modified DATETIME NULL,
        synced_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY sinergia_id (sinergia_id),
        KEY email (email),
        KEY synced_at (synced_at)
    ) $charset_collate;";

    // Tabla Events/Esdeveniments
    $sql_events = "CREATE TABLE $events_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        sinergia_id VARCHAR(36) NOT NULL,
        payload LONGTEXT NOT NULL,
        date_modified DATETIME NULL,
        synced_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY sinergia_id (sinergia_id),
        KEY synced_at (synced_at)
    ) $charset_collate;";

    dbDelta($sql_contacts);
    dbDelta($sql_events);

    // Guarda versión de esquema para futuros updates
    update_option('fcsd_sinergia_db_version', '1.0.0', false);
}

add_action('after_setup_theme', 'fcsd_sinergia_maybe_install_tables', 5);

function fcsd_sinergia_maybe_install_tables() {
    global $wpdb;

    $contacts_table = $wpdb->prefix . 'fcsd_sinergia_contacts';
    $events_table   = $wpdb->prefix . 'fcsd_sinergia_events';

    $current_ver  = get_option('fcsd_sinergia_db_version');
    $need_install = ($current_ver !== '1.0.0');

    if ( ! $need_install ) {
        $need_install =
            ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $contacts_table)) !== $contacts_table)
         || ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $events_table))   !== $events_table);
    }

    if ( $need_install ) {
        fcsd_sinergia_install_tables();
    }
}
