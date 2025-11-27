<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Helpers de tablas
 */
function fcsd_sinergia_contacts_table() {
  global $wpdb;
  return $wpdb->prefix . 'fcsd_sinergia_contacts';
}

function fcsd_sinergia_events_table() {
  global $wpdb;
  return $wpdb->prefix . 'fcsd_sinergia_events';
}

function fcsd_sinergia_registrations_table() {
  global $wpdb;
  return $wpdb->prefix . 'fcsd_sinergia_registrations';
}

/* =========================================================================
   CREACIÓN / ACTUALIZACIÓN AUTOMÁTICA DE TABLAS
   ========================================================================= */

/**
 * Crea o actualiza las tablas de caché de Sinergia.
 * - Contacts
 * - Events
 * - Registrations (NUEVA)
 */
function fcsd_sinergia_cache_install_tables() {
  global $wpdb;

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';

  $charset = $wpdb->get_charset_collate();

  $contacts = fcsd_sinergia_contacts_table();
  $events   = fcsd_sinergia_events_table();
  $regs     = fcsd_sinergia_registrations_table();

  // Contacts
  $sql_contacts = "CREATE TABLE $contacts (
    sinergia_id varchar(36) NOT NULL,
    email varchar(190) DEFAULT NULL,
    payload longtext NOT NULL,
    date_modified datetime DEFAULT NULL,
    synced_at datetime NOT NULL,
    PRIMARY KEY  (sinergia_id),
    KEY email (email),
    KEY date_modified (date_modified)
  ) $charset;";

  // Events
  $sql_events = "CREATE TABLE $events (
    sinergia_id varchar(36) NOT NULL,
    payload longtext NOT NULL,
    date_modified datetime DEFAULT NULL,
    synced_at datetime NOT NULL,
    PRIMARY KEY  (sinergia_id),
    KEY date_modified (date_modified)
  ) $charset;";

  // Registrations (NUEVA)
  $sql_regs = "CREATE TABLE $regs (
    sinergia_id varchar(36) NOT NULL,
    contact_id varchar(36) NOT NULL,
    event_id varchar(36) DEFAULT NULL,
    payload longtext NOT NULL,
    date_modified datetime DEFAULT NULL,
    synced_at datetime NOT NULL,
    PRIMARY KEY  (sinergia_id),
    KEY contact_id (contact_id),
    KEY event_id (event_id),
    KEY date_modified (date_modified)
  ) $charset;";

  dbDelta( $sql_contacts );
  dbDelta( $sql_events );
  dbDelta( $sql_regs );
}

/**
 * Al activar el tema.
 */
add_action( 'after_switch_theme', 'fcsd_sinergia_cache_install_tables' );

/**
 * Auto-repair: si algún admin borra tablas, se recrean.
 * No corre dbDelta en cada request: solo si falta alguna tabla.
 */
add_action( 'init', function() {
  global $wpdb;

  $needed = [
    fcsd_sinergia_contacts_table(),
    fcsd_sinergia_events_table(),
    fcsd_sinergia_registrations_table(),
  ];

  foreach ( $needed as $t ) {
    $exists = $wpdb->get_var( $wpdb->prepare("SHOW TABLES LIKE %s", $t) );
    if ( $exists !== $t ) {
      fcsd_sinergia_cache_install_tables();
      break;
    }
  }
}, 5 );

/* =========================================================================
   CONTACTS
   ========================================================================= */

/**
 * Upsert de contacto en caché local
 * Devuelve true si insert/update ok, false si falla.
 */
function fcsd_sinergia_cache_upsert_contact( array $person ) {
  global $wpdb;

  $table = fcsd_sinergia_contacts_table();

  $sin_id = sanitize_text_field($person['id'] ?? '');
  if ( ! $sin_id ) return false;

  $email = strtolower(trim($person['email1'] ?? ''));
  $email = is_email($email) ? $email : null;

  $payload = wp_json_encode($person);

  $date_modified = null;
  if ( ! empty($person['date_modified']) ) {
    $ts = strtotime($person['date_modified']);
    if ( $ts ) {
      $date_modified = gmdate('Y-m-d H:i:s', $ts);
    }
  }

  $synced_at = current_time('mysql');

  $sql = $wpdb->prepare(
    "INSERT INTO $table (sinergia_id, email, payload, date_modified, synced_at)
     VALUES (%s, %s, %s, %s, %s)
     ON DUPLICATE KEY UPDATE
        email = VALUES(email),
        payload = VALUES(payload),
        date_modified = VALUES(date_modified),
        synced_at = VALUES(synced_at)",
    $sin_id, $email, $payload, $date_modified, $synced_at
  );

  $wpdb->query($sql);

  if ( $wpdb->last_error ) {
    error_log('[Sinergia cache] Contact upsert DB error: ' . $wpdb->last_error);
    return false;
  }

  // Vincular automáticamente usuarios WP existentes por email
  if ( $email ) {
    $wp_user = get_user_by( 'email', $email );
    if ( $wp_user ) {
      $current_contact_id = get_user_meta( $wp_user->ID, 'fcsd_sinergia_contact_id', true );

      if ( empty($current_contact_id) || $current_contact_id !== $sin_id ) {
        if ( function_exists( 'fcsd_sinergia_update_user_from_person' ) ) {
          fcsd_sinergia_update_user_from_person( $wp_user->ID, $person );
        } else {
          update_user_meta( $wp_user->ID, 'fcsd_sinergia_contact_id', $sin_id );
          update_user_meta( $wp_user->ID, 'fcsd_sinergia_last_sync', current_time('mysql') );
          update_user_meta( $wp_user->ID, 'fcsd_sinergia_linked', 1 );
        }
      }
    }
  }

  return true;
}

/**
 * Busca un contacto cacheado por email
 */
function fcsd_sinergia_find_cached_contact_by_email( $email ) {
  global $wpdb;

  $table = fcsd_sinergia_contacts_table();

  $email = strtolower(trim($email));
  if ( ! is_email($email) ) return null;

  $row = $wpdb->get_row(
    $wpdb->prepare(
      "SELECT payload
       FROM $table
       WHERE email=%s
       ORDER BY date_modified DESC
       LIMIT 1",
      $email
    ),
    ARRAY_A
  );

  if ( ! $row || empty($row['payload']) ) {
    return null;
  }

  $decoded = json_decode($row['payload'], true);
  return is_array($decoded) ? $decoded : null;
}

/* =========================================================================
   EVENTS
   ========================================================================= */

/**
 * Upsert de evento en caché local
 * Devuelve true si insert/update ok, false si falla.
 */
function fcsd_sinergia_cache_upsert_event( array $event ) {
  global $wpdb;

  $table = fcsd_sinergia_events_table();

  $sin_id = sanitize_text_field($event['id'] ?? '');
  if ( ! $sin_id ) return false;

  $payload = wp_json_encode($event);

  $date_modified = null;
  if ( ! empty($event['date_modified']) ) {
    $ts = strtotime($event['date_modified']);
    if ( $ts ) {
      $date_modified = gmdate('Y-m-d H:i:s', $ts);
    }
  }

  $synced_at = current_time('mysql');

  $sql = $wpdb->prepare(
    "INSERT INTO $table (sinergia_id, payload, date_modified, synced_at)
     VALUES (%s, %s, %s, %s)
     ON DUPLICATE KEY UPDATE
        payload = VALUES(payload),
        date_modified = VALUES(date_modified),
        synced_at = VALUES(synced_at)",
    $sin_id, $payload, $date_modified, $synced_at
  );

  $wpdb->query($sql);

  if ( $wpdb->last_error ) {
    error_log('[Sinergia cache] Event upsert DB error: ' . $wpdb->last_error);
    return false;
  }

  return true;
}

/* =========================================================================
   REGISTRATIONS (NUEVO) caché persistente
   ========================================================================= */

/**
 * Upsert de inscripción cacheada.
 *
 * @param array  $reg        Normalizada name_value_list
 * @param string $contact_id Id del contacto padre
 * @return bool
 */
function fcsd_sinergia_cache_upsert_registration( array $reg, $contact_id ) {
  global $wpdb;

  $table = fcsd_sinergia_registrations_table();

  $sin_id = sanitize_text_field($reg['id'] ?? '');
  if ( ! $sin_id ) return false;

  $contact_id = sanitize_text_field($contact_id);
  if ( ! $contact_id ) return false;

  $event_id = sanitize_text_field($reg['stic_event_id_c'] ?? '');

  $payload = wp_json_encode($reg);

  $date_modified = null;
  if ( ! empty($reg['date_modified']) ) {
    $ts = strtotime($reg['date_modified']);
    if ( $ts ) {
      $date_modified = gmdate('Y-m-d H:i:s', $ts);
    }
  }

  $synced_at = current_time('mysql');

  $sql = $wpdb->prepare(
    "INSERT INTO $table (sinergia_id, contact_id, event_id, payload, date_modified, synced_at)
     VALUES (%s, %s, %s, %s, %s, %s)
     ON DUPLICATE KEY UPDATE
        contact_id = VALUES(contact_id),
        event_id = VALUES(event_id),
        payload = VALUES(payload),
        date_modified = VALUES(date_modified),
        synced_at = VALUES(synced_at)",
    $sin_id, $contact_id, $event_id, $payload, $date_modified, $synced_at
  );

  $wpdb->query($sql);

  if ( $wpdb->last_error ) {
    error_log('[Sinergia cache] Registration upsert DB error: ' . $wpdb->last_error);
    return false;
  }

  return true;
}

/**
 * Borra todas las inscripciones de un contacto (antes de reinsertar).
 */
function fcsd_sinergia_cache_delete_registrations_for_contact( $contact_id ) {
  global $wpdb;
  $table = fcsd_sinergia_registrations_table();
  $contact_id = sanitize_text_field($contact_id);
  if ( ! $contact_id ) return;
  $wpdb->delete( $table, ['contact_id' => $contact_id], ['%s'] );
}

/**
 * Devuelve inscripciones cacheadas de un contacto.
 */
function fcsd_sinergia_get_cached_registrations_for_contact( $contact_id ) {
  global $wpdb;

  $table = fcsd_sinergia_registrations_table();
  $contact_id = sanitize_text_field($contact_id);
  if ( ! $contact_id ) return [];

  $rows = $wpdb->get_results(
    $wpdb->prepare(
      "SELECT payload
       FROM $table
       WHERE contact_id=%s
       ORDER BY date_modified DESC",
      $contact_id
    ),
    ARRAY_A
  );

  if ( empty($rows) ) return [];

  $out = [];
  foreach ( $rows as $row ) {
    if ( empty($row['payload']) ) continue;
    $decoded = json_decode($row['payload'], true);
    if ( is_array($decoded) ) $out[] = $decoded;
  }

  return $out;
}

/**
 * Cuenta inscripciones cacheadas de un contacto.
 */
function fcsd_sinergia_count_cached_registrations_for_contact( $contact_id ) {
  global $wpdb;
  $table = fcsd_sinergia_registrations_table();
  $contact_id = sanitize_text_field($contact_id);
  if ( ! $contact_id ) return 0;
  return (int) $wpdb->get_var(
    $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE contact_id=%s", $contact_id)
  );
}

/* =========================================================================
   PAGINACIÓN REAL EN CACHÉ (CONTACTS / EVENTS)
   ========================================================================= */

function fcsd_sinergia_count_cached_contacts() {
  global $wpdb;
  $table = fcsd_sinergia_contacts_table();
  return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
}

function fcsd_sinergia_get_cached_contacts_page( $page = 1, $per_page = 50 ) {
  global $wpdb;
  $table = fcsd_sinergia_contacts_table();

  $page     = max(1, (int)$page);
  $per_page = max(1, (int)$per_page);
  $offset   = ($page - 1) * $per_page;

  $rows = $wpdb->get_results(
    $wpdb->prepare(
      "SELECT payload
       FROM $table
       ORDER BY date_modified DESC
       LIMIT %d OFFSET %d",
      $per_page, $offset
    ),
    ARRAY_A
  );

  if ( empty($rows) ) return [];

  $contacts = [];
  foreach ( $rows as $row ) {
    if ( empty($row['payload']) ) continue;
    $decoded = json_decode($row['payload'], true);
    if ( is_array($decoded) && !empty($decoded) ) {
      $contacts[] = $decoded;
    }
  }

  return $contacts;
}

function fcsd_sinergia_count_cached_events() {
  global $wpdb;
  $table = fcsd_sinergia_events_table();
  return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
}

function fcsd_sinergia_get_cached_events_page( $page = 1, $per_page = 50 ) {
  global $wpdb;
  $table = fcsd_sinergia_events_table();

  $page     = max(1, (int)$page);
  $per_page = max(1, (int)$per_page);
  $offset   = ($page - 1) * $per_page;

  $rows = $wpdb->get_results(
    $wpdb->prepare(
      "SELECT payload
       FROM $table
       ORDER BY date_modified DESC
       LIMIT %d OFFSET %d",
      $per_page, $offset
    ),
    ARRAY_A
  );

  if ( empty($rows) ) return [];

  $events = [];
  foreach ( $rows as $row ) {
    if ( empty($row['payload']) ) continue;
    $decoded = json_decode($row['payload'], true);
    if ( is_array($decoded) && !empty($decoded) ) {
      $events[] = $decoded;
    }
  }

  return $events;
}

/**
 * Cerca global d'esdeveniments a la caché local.
 *
 * - $search: text a cercar (en qualsevol camp serialitzat al JSON).
 * - $page / $per_page: paginació.
 * - $total: es retorna per referència el nombre total de coincidències.
 */
function fcsd_sinergia_search_cached_events( $search, $page = 1, $per_page = 50, &$total = 0 ) {
    global $wpdb;

    $table = fcsd_sinergia_events_table();

    $page     = max( 1, (int) $page );
    $per_page = max( 1, (int) $per_page );
    $offset   = ( $page - 1 ) * $per_page;

    $search = trim( (string) $search );
    if ( $search === '' ) {
        // Si no hi ha text, deleguem en la funció de pàgina normal
        $total  = fcsd_sinergia_count_cached_events();
        return fcsd_sinergia_get_cached_events_page( $page, $per_page );
    }

    $like = '%' . $wpdb->esc_like( $search ) . '%';

    // Comptar coincidències: busquem al JSON complet (payload)
    $where_sql = $wpdb->prepare(
        'WHERE payload LIKE %s',
        $like
    );

    $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table $where_sql" );

    if ( ! $total ) {
        return array();
    }

    // Obtenim la pàgina concreta de resultats
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT payload
               FROM $table
               $where_sql
           ORDER BY date_modified DESC
              LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ),
        ARRAY_A
    );

    if ( ! $rows ) {
        return array();
    }

    $events = array();

    foreach ( $rows as $row ) {
        if ( empty( $row['payload'] ) ) {
            continue;
        }

        $decoded = json_decode( $row['payload'], true );
        if ( is_array( $decoded ) && ! empty( $decoded ) ) {
            $events[] = $decoded;
        }
    }

    return $events;
}

/**
 * Devuelve un único esdeveniment cacheado por ID de Sinergia.
 */
function fcsd_sinergia_get_cached_event_by_id( $sinergia_id ) {
  global $wpdb;

  $table = fcsd_sinergia_events_table();
  $sinergia_id = sanitize_text_field( $sinergia_id );
  if ( ! $sinergia_id ) return [];

  $row = $wpdb->get_row(
    $wpdb->prepare("SELECT payload FROM $table WHERE sinergia_id=%s LIMIT 1", $sinergia_id),
    ARRAY_A
  );

  if ( empty( $row['payload'] ) ) return [];

  $decoded = json_decode( $row['payload'], true );
  return is_array( $decoded ) ? $decoded : [];
}


/* =========================================================================
   COMPAT: funciones antiguas (siguen existiendo)
   ========================================================================= */

function fcsd_sinergia_get_cached_contacts( $limit = 500 ) {
  if ( (int)$limit === -1 ) {
    $total = fcsd_sinergia_count_cached_contacts();
    return fcsd_sinergia_get_cached_contacts_page(1, max(1, $total));
  }
  return fcsd_sinergia_get_cached_contacts_page(1, max(1, (int)$limit));
}

function fcsd_sinergia_search_cached_contacts( $search, $page = 1, $per_page = 50, &$total = 0 ) {
  global $wpdb;
  $table = fcsd_sinergia_contacts_table();

  $page     = max( 1, (int) $page );
  $per_page = max( 1, (int) $per_page );
  $offset   = ( $page - 1 ) * $per_page;

  $search   = (string) $search;
  $like     = '%' . $wpdb->esc_like( $search ) . '%';

  // Contar total de coincidencias (email o qualsevol camp del JSON)
  $where_sql = $wpdb->prepare(
    "WHERE email LIKE %s OR payload LIKE %s",
    $like,
    $like
  );

  $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table $where_sql" );

  if ( ! $total ) {
    return array();
  }

  // Obtener página concreta de resultados
  $rows = $wpdb->get_results(
    $wpdb->prepare(
      "SELECT payload
         FROM $table
         $where_sql
        ORDER BY date_modified DESC
        LIMIT %d OFFSET %d",
      $per_page,
      $offset
    ),
    ARRAY_A
  );

  if ( ! $rows ) {
    return array();
  }

  $contacts = array();
  foreach ( $rows as $row ) {
    if ( empty( $row['payload'] ) ) {
      continue;
    }
    $decoded = json_decode( $row['payload'], true );
    if ( is_array( $decoded ) && ! empty( $decoded ) ) {
      $contacts[] = $decoded;
    }
  }

  return $contacts;
}

function fcsd_sinergia_get_cached_events( $limit = 500 ) {
  if ( (int)$limit === -1 ) {
    $total = fcsd_sinergia_count_cached_events();
    return fcsd_sinergia_get_cached_events_page(1, max(1, $total));
  }
  return fcsd_sinergia_get_cached_events_page(1, max(1, (int)$limit));
}

/* =========================================================================
   Gestión de timestamps de sincronización
   ========================================================================= */

function fcsd_sinergia_cache_last_sync( $type ) {
  return get_option("fcsd_sinergia_{$type}_last_sync");
}

function fcsd_sinergia_cache_set_last_sync( $type ) {
  update_option("fcsd_sinergia_{$type}_last_sync", current_time('mysql', true), false);
}

function fcsd_sinergia_cache_is_stale( $type, $seconds = 86400 ) {
  $last = fcsd_sinergia_cache_last_sync($type);
  if ( ! $last ) return true;

  return ( time() - strtotime($last) ) > $seconds;
}
