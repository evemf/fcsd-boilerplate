<?php
/**
 * Sincronización de persones entre SinergiaCRM (Contacts) i els usuaris de WordPress.
 *
 * Flux principal:
 *  - Quan es registra un usuari (hook user_register):
 *      · Cerquem primer a la caché local (taula wp_fcsd_sinergia_contacts) pel seu email.
 *      · Si existeix, importem dades a WP i marquem com vinculat.
 *      · Si no existeix a caché, fem fallback a l'API per si la caché està antiga.
 *      · Si tampoc existeix a l'API, NO creem Contact a Sinergia.
 *        → deixem l'usuari com NO vinculat.
 *
 * A més:
 *  - Es guarda l'ID del Contact de Sinergia a user_meta (fcsd_sinergia_contact_id).
 *  - Es guarda un flag fcsd_sinergia_linked (1/0).
 *  - Hi ha una funció per tornar a sincronitzar un usuari manualment des de Sinergia.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalitza una entry de get_entry_list() / get_entry() de Sinergia a un array simple associatiu.
 *
 * @param object|array $entry
 * @return array
 */
function fcsd_sinergia_normalize_contact_entry( $entry ) {
    if ( is_array( $entry ) ) {
        $entry = (object) $entry;
    }

    $out = array();

    if ( ! empty( $entry->id ) ) {
        $out['id'] = $entry->id;
    }

    if ( isset( $entry->name_value_list ) ) {
        $nvl = $entry->name_value_list;

        if ( is_object( $nvl ) ) {
            $nvl = (array) $nvl;
        }

        if ( is_array( $nvl ) ) {
            foreach ( $nvl as $field ) {
                if ( is_object( $field ) ) {
                    $name  = $field->name  ?? null;
                    $value = $field->value ?? null;
                } elseif ( is_array( $field ) ) {
                    $name  = $field['name']  ?? null;
                    $value = $field['value'] ?? null;
                } else {
                    continue;
                }

                if ( $name ) {
                    $out[ $name ] = $value;
                }
            }
        }
    }

    return $out;
}

/**
 * Busca un contacte per email a la caché local de Sinergia.
 *
 * @param string $email
 * @return array|null
 */
function fcsd_sinergia_find_contact_in_cache_by_email( $email ) {
    if ( ! function_exists( 'fcsd_sinergia_contacts_table' ) ) {
        return null;
    }

    global $wpdb;

    $table = fcsd_sinergia_contacts_table();

    $email = strtolower( trim( $email ) );
    if ( ! is_email( $email ) ) {
        return null;
    }

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

    if ( empty( $row['payload'] ) ) {
        return null;
    }

    $decoded = json_decode( $row['payload'], true );
    if ( ! is_array( $decoded ) ) {
        return null;
    }

    return $decoded;
}

/**
 * Importa dades bàsiques de Contact de Sinergia a un usuari de WP.
 *
 * @param int   $user_id
 * @param array $contact
 */
function fcsd_sinergia_import_contact_to_user( $user_id, array $contact ) {
    $first_name = $contact['first_name'] ?? '';
    $last_name  = $contact['last_name']  ?? '';
    $email      = $contact['email1']     ?? '';

    if ( $first_name ) {
        wp_update_user(
            array(
                'ID'         => $user_id,
                'first_name' => $first_name,
            )
        );
    }

    if ( $last_name ) {
        wp_update_user(
            array(
                'ID'        => $user_id,
                'last_name' => $last_name,
            )
        );
    }

    if ( $email && is_email( $email ) ) {
        wp_update_user(
            array(
                'ID'         => $user_id,
                'user_email' => $email,
            )
        );
    }

    if ( ! empty( $contact['id'] ) ) {
        update_user_meta( $user_id, 'fcsd_sinergia_contact_id', $contact['id'] );
        update_user_meta( $user_id, 'fcsd_sinergia_linked', 1 );
    }
}

/**
 * Fa login a Sinergia i retorna un contacte pel seu email (o null).
 *
 * @param string $email
 * @return array|null
 */
function fcsd_sinergia_find_contact_in_api_by_email( $email ) {
    if ( ! function_exists( 'fcsd_sinergia_get_client' ) ) {
        return null;
    }

    $email = strtolower( trim( $email ) );
    if ( ! is_email( $email ) ) {
        return null;
    }

    $client = fcsd_sinergia_get_client();
    if ( is_wp_error( $client ) ) {
        return null;
    }

    // Cerquem 1 sol Contact amb aquest email
    $params = array(
        'module_name'  => 'Contacts',
        'query'        => "contacts.email1 = '{$email}'",
        'order_by'     => '',
        'offset'       => 0,
        'select_fields' => array(
            'id',
            'first_name',
            'last_name',
            'email1',
            'phone_mobile',
            'date_modified',
        ),
        'link_name_to_fields_array' => array(),
        'max_results'               => 1,
        'deleted'                   => 0,
    );

    $result = $client->getEntryList( $params );
    if ( is_wp_error( $result ) || empty( $result->entry_list ) || ! is_array( $result->entry_list ) ) {
        return null;
    }

    return fcsd_sinergia_normalize_contact_entry( $result->entry_list[0] );
}

/**
 * Quan es registra un usuari nou a WP, intentem enllaçar-lo amb un Contact ja existent a Sinergia.
 */
add_action(
    'user_register',
    function ( $user_id ) {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return;
        }

        $email = $user->user_email;

        // 1) Mirar primer a la caché
        $from_cache = fcsd_sinergia_find_contact_in_cache_by_email( $email );
        if ( is_array( $from_cache ) ) {
            fcsd_sinergia_import_contact_to_user( $user_id, $from_cache );
            return;
        }

        // 2) Fallback a l'API directa
        $from_api = fcsd_sinergia_find_contact_in_api_by_email( $email );
        if ( is_array( $from_api ) ) {
            fcsd_sinergia_import_contact_to_user( $user_id, $from_api );
            return;
        }

        // Si no trobem res, marquem com NO vinculat
        update_user_meta( $user_id, 'fcsd_sinergia_linked', 0 );
    },
    20
);

/**
 * Refresca les dades d'un usuari des de Sinergia (manual).
 *
 * @param int $user_id
 * @return bool
 */
function fcsd_sinergia_resync_user_from_sinergia( $user_id ) {
    $user = get_userdata( $user_id );
    if ( ! $user ) {
        return false;
    }

    $email = $user->user_email;

    $contact = fcsd_sinergia_find_contact_in_api_by_email( $email );
    if ( ! is_array( $contact ) ) {
        return false;
    }

    fcsd_sinergia_import_contact_to_user( $user_id, $contact );

    return true;
}

/**
 * AJAX per re-sincronitzar un usuari des del perfil d'usuari a WP-Admin.
 */
add_action(
    'wp_ajax_fcsd_sinergia_resync_user',
    function () {
        if ( ! current_user_can( 'edit_users' ) ) {
            wp_send_json_error(
                array(
                    'message' => 'No tens permisos.',
                )
            );
        }

        check_ajax_referer( 'fcsd_sinergia_resync_user', 'nonce' );

        $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
        if ( ! $user_id ) {
            wp_send_json_error(
                array(
                    'message' => 'Falta user_id.',
                )
            );
        }

        $ok = fcsd_sinergia_resync_user_from_sinergia( $user_id );
        if ( $ok ) {
            wp_send_json_success(
                array(
                    'message' => 'Usuari sincronitzat correctament.',
                )
            );
        } else {
            wp_send_json_error(
                array(
                    'message' => 'No s\'ha pogut sincronitzar l\'usuari.',
                )
            );
        }
    }
);

/* =========================================================================
   REGISTRES / INSCRIPCIONS (stic_Registrations relacionades amb Contacts)
   ========================================================================= */

/**
 * Obtén les inscripcions d'un contacte des de Sinergia, amb caché transient.
 *
 * Importante: esta versión usa el método estándar `get_relationships` de la API 4_1
 * con el link `stic_registrations_contacts`, que es lo que documenta Sinergia/SuiteCRM
 * para recuperar los `stic_Registrations` relacionados con un `Contact`.
 *
 * @param FCSD_Sinergia_APIClient $client
 * @param string                  $contact_id
 * @param bool                    $force      Si true, ignora caché transient
 *
 * @return array
 */
function fcsd_sinergia_get_contact_registrations( $client, $contact_id, $force = false ) {
    $contact_id = trim( (string) $contact_id );
    if ( ! $contact_id ) {
        return array();
    }

    $cache_key = 'fcsd_sinergia_regs_' . $contact_id;

    // Usa caché corta salvo que se fuerce el refresco explícito
    if ( ! $force ) {
        $cached = get_transient( $cache_key );
        if ( is_array( $cached ) ) {
            return $cached;
        }
    }

    // Validar que el cliente esté inicializado y con sesión
    if ( ! is_object( $client ) || empty( $client->sessionId ) ) {
        error_log( '[Sinergia Registrations] Client not properly initialized for contact ' . $contact_id );
        return array();
    }

    // ---------------------------------------------------------------------
    // 1) Intento principal: método estándar get_relationships (API v4_1)
    // ---------------------------------------------------------------------
    if ( method_exists( $client, 'call' ) ) {
        $params = array(
            'session'              => $client->sessionId,
            'module_name'          => 'Contacts',
            'module_id'            => $contact_id,
            'link_field_name'      => 'stic_registrations_contacts',
            'related_module_query' => '',
            'related_fields'       => apply_filters(
                'fcsd_sinergia_registration_select_fields',
                array(
                    'id',
                    'name',
                    'date_modified',
                    'assigned_user_name',
                    'stic_event_id_c',
                    'status_c',
                    'registration_date_c',
                    'attendance_percent_c',
                    'hours_attended_c',
                )
            ),
            'related_module_link_name_to_fields_array' => array(),
            'deleted'              => 0,
            'order_by'             => 'date_modified DESC',
        );

        // Llamamos directamente al método REST nativo
        $res = $client->call( 'get_relationships', $params );

        if ( is_wp_error( $res ) ) {
            error_log( '[Sinergia Registrations] get_relationships error: ' . $res->get_error_message() );
            set_transient( $cache_key, array(), 10 * MINUTE_IN_SECONDS );
            return array();
        }

        if ( empty( $res->entry_list ) || ! is_array( $res->entry_list ) ) {
            set_transient( $cache_key, array(), 30 * MINUTE_IN_SECONDS );
            return array();
        }

        $out = array();

        // Estructura estándar: entry_list[n]->records[m]
        foreach ( $res->entry_list as $module_entries ) {
            if ( is_object( $module_entries ) && isset( $module_entries->records ) && is_array( $module_entries->records ) ) {
                foreach ( $module_entries->records as $entry ) {
                    $normalized = fcsd_sinergia_normalize_contact_entry( $entry );
                    if ( ! empty( $normalized ) ) {
                        $out[] = $normalized;
                    }
                }
            } else {
                // Fallback defensivo por si el servidor ya devuelve directamente los registros
                $normalized = fcsd_sinergia_normalize_contact_entry( $module_entries );
                if ( ! empty( $normalized ) ) {
                    $out[] = $normalized;
                }
            }
        }

        set_transient( $cache_key, $out, 30 * MINUTE_IN_SECONDS );

        return $out;
    }

    // Si llegamos aquí es que no hay manera razonable de obtener las inscripciones
    error_log( '[Sinergia Registrations] No suitable method found to retrieve registrations for contact ' . $contact_id );
    set_transient( $cache_key, array(), 10 * MINUTE_IN_SECONDS );

    return array();
}

/**
 * Refresca y cachea en BD las inscripciones de un contacto.
 *
 * - Borra todas las inscripciones previas en la tabla local.
 * - Vuelve a guardar lo que devuelva la API en wp_fcsd_sinergia_registrations.
 *
 * @param FCSD_Sinergia_APIClient $client
 * @param string                  $contact_id
 * @return int Número de inscripciones guardadas
 */
function fcsd_sinergia_refresh_registrations_for_contact( $client, $contact_id ) {
    if ( ! function_exists( 'fcsd_sinergia_cache_delete_registrations_for_contact' ) ||
         ! function_exists( 'fcsd_sinergia_cache_upsert_registration' ) ) {
        return 0;
    }

    $contact_id = trim( (string) $contact_id );
    if ( ! $contact_id ) {
        return 0;
    }

    // Forzamos refresco desde la API (ignorando transient anterior)
    $regs = fcsd_sinergia_get_contact_registrations( $client, $contact_id, true );
    if ( ! is_array( $regs ) ) {
        $regs = array();
    }

    // Borramos todo lo anterior para ese contacto y volvemos a insertar
    fcsd_sinergia_cache_delete_registrations_for_contact( $contact_id );

    $saved = 0;

    foreach ( $regs as $reg ) {
        if ( ! is_array( $reg ) ) {
            continue;
        }

        $ok = fcsd_sinergia_cache_upsert_registration( $reg, $contact_id );
        if ( $ok ) {
            $saved++;
        }
    }

    return $saved;
}

/**
 * Helper para perfil: mezcla inscripciones locales + Sinergia.
 *
 * @param int   $user_id
 * @param array $local_regs
 * @return array
 */
function fcsd_sinergia_merge_registrations_for_user( $user_id, array $local_regs = array() ) {
    $sinergia_id = get_user_meta( $user_id, 'fcsd_sinergia_contact_id', true );
    if ( empty( $sinergia_id ) ) {
        return $local_regs;
    }

    if ( ! function_exists( 'fcsd_sinergia_get_cached_registrations_for_contact' ) ) {
        return $local_regs;
    }

    $sinergia_regs = fcsd_sinergia_get_cached_registrations_for_contact( $sinergia_id );
    if ( ! is_array( $sinergia_regs ) ) {
        $sinergia_regs = array();
    }

    // Simplemente concatenem (pots fer merge més intel·ligent si cal)
    return array_merge( $local_regs, $sinergia_regs );
}

/**
 * Devuelve las inscripciones visibles para el usuario,
 * normalizadas y filtradas para mostrar solo las que
 * apuntan a un CPT event existente.
 *
 * Cada item devuelto tiene:
 * - title
 * - date
 * - status
 * - permalink
 *
 * @param int   $user_id
 * @param array $local_regs  Inscripcions locals ja normalitzades (opcional)
 * @return array
 */
function fcsd_sinergia_get_normalized_user_registrations( $user_id, array $local_regs = array() ) {
    $user_id = (int) $user_id;
    if ( $user_id <= 0 ) {
        return array();
    }

    // 1) Base: inscripcions locals ja en format correcte
    $out = array();
    foreach ( $local_regs as $reg ) {
        if ( ! is_array( $reg ) ) {
            continue;
        }
        if ( empty( $reg['title'] ) ) {
            continue;
        }
        $out[] = $reg;
    }

    // 2) Afegim inscripcions de Sinergia només si hi ha contact vinculat
    $sinergia_id = get_user_meta( $user_id, 'fcsd_sinergia_contact_id', true );
    if ( ! $sinergia_id ) {
        // Compatibilitat amb meta antic
        $sinergia_id = get_user_meta( $user_id, 'sinergia_person_id', true );
    }

    if ( ! $sinergia_id || ! function_exists( 'fcsd_sinergia_get_cached_registrations_for_contact' ) ) {
        return $out;
    }

    $regs = fcsd_sinergia_get_cached_registrations_for_contact( $sinergia_id );
    if ( ! is_array( $regs ) ) {
        $regs = array();
    }

    foreach ( $regs as $r ) {
        if ( ! is_array( $r ) ) {
            continue;
        }

        // -----------------------------
        //   IDENTIFICAR EVENT SINERGIA
        // -----------------------------
        $event_id = '';
        if ( ! empty( $r['stic_event_id_c'] ) ) {
            $event_id = trim( (string) $r['stic_event_id_c'] );
        } elseif ( ! empty( $r['event_id'] ) ) {
            // per si a la payload ve amb una altra clau
            $event_id = trim( (string) $r['event_id'] );
        }

        // Intentem trobar CPT event vinculat per ID de Sinergia
        $event_post_id = 0;

        if ( $event_id && function_exists( 'fcsd_sinergia_find_event_post_id_by_sinergia_id' ) ) {
            $event_post_id = (int) fcsd_sinergia_find_event_post_id_by_sinergia_id( $event_id );
        }

        // Títol que ve de Sinergia (sovint: "NomCognoms – TítolEsdeveniment")
        $raw_name = isset( $r['name'] ) ? trim( (string) $r['name'] ) : '';

        // Si per ID no trobem res, intentem per títol
        if ( ! $event_post_id && $raw_name ) {

            // 1) Intentem buscar pel títol complet
            $title_candidates = array( $raw_name );

            // 2) Si porta " - " o " – ", ens quedem amb la part final (el nom de l'esdeveniment)
            if ( preg_match( '/(.+)[\-–](.+)/u', $raw_name, $m ) ) {
                $possible_title = trim( $m[2] );
                if ( $possible_title && $possible_title !== $raw_name ) {
                    $title_candidates[] = $possible_title;
                }
            }

            $title_candidates = array_unique( $title_candidates );

            foreach ( $title_candidates as $title_search ) {
                $q = new WP_Query( array(
                    'post_type'      => 'event',
                    'post_status'    => 'publish',
                    'posts_per_page' => 1,
                    's'              => $title_search,
                ) );

                if ( ! empty( $q->posts ) ) {
                    $event_post_id = (int) $q->posts[0]->ID;
                    break;
                }
            }
        }

        // -----------------------------
        //   TÍTOL I ENLLAÇ A MOSTRAR
        // -----------------------------
        $title     = '';
        $permalink = '';

        if ( $event_post_id ) {
            // Hi ha CPT vinculat → fem servir el títol i enllaç del post
            $title     = get_the_title( $event_post_id );
            $permalink = get_permalink( $event_post_id );
        }

        // Fallback: sense CPT, fem servir el nom que ve de Sinergia
        if ( ! $title && $raw_name ) {
            $title = $raw_name;
        }

        // Si ni així tenim títol, no la mostrem
        if ( ! $title ) {
            continue;
        }

        // Data de la inscripció (CRM)
        $date   = ! empty( $r['registration_date_c'] ) ? $r['registration_date_c'] : ( $r['date_modified'] ?? '' );
        $status = $r['status_c'] ?? '';

        // --------------------------------
        //   DATES I ESTAT DE L'ESDEVENIMENT
        // --------------------------------
        $event_start = '';
        $event_end   = '';
        $event_state = ''; // 'active', 'finished', 'upcoming' o buit

        $raw_start = '';
        $raw_end   = '';

        // 1) Primer intent: metadades del CPT event (com a single-event.php)
        if ( $event_post_id ) {
            $raw_start = get_post_meta( $event_post_id, 'fcsd_event_start', true );
            $raw_end   = get_post_meta( $event_post_id, 'fcsd_event_end', true );
        }

        // 2) Si al CPT no hi ha dates → fallback a la CACHÉ d'esdeveniments (taula local)
        if ( ( ! $raw_start && ! $raw_end ) && $event_id && function_exists( 'fcsd_sinergia_get_cached_event_by_id' ) ) {
            $event_data = fcsd_sinergia_get_cached_event_by_id( $event_id );
            if ( is_array( $event_data ) ) {
                if ( ! empty( $event_data['start_date'] ) ) {
                    $raw_start = $event_data['start_date'];
                }
                if ( ! empty( $event_data['end_date'] ) ) {
                    $raw_end = $event_data['end_date'];
                }
            }
        }

        // Dates que aniran directament al perfil
        $event_start = $raw_start ?: '';
        $event_end   = $raw_end   ?: '';

        // 3) Calculem estat en funció de les dates
        if ( $raw_start || $raw_end ) {
            $now      = current_time( 'timestamp' );
            $start_ts = $raw_start ? strtotime( $raw_start ) : false;
            $end_ts   = $raw_end   ? strtotime( $raw_end )   : false;

            if ( $start_ts && $end_ts ) {
                if ( $now < $start_ts ) {
                    $event_state = 'upcoming';
                } elseif ( $now > $end_ts ) {
                    $event_state = 'finished';
                } else {
                    $event_state = 'active';
                }
            } elseif ( $start_ts ) {
                $event_state = ( $now < $start_ts ) ? 'upcoming' : 'active';
            } elseif ( $end_ts ) {
                $event_state = ( $now <= $end_ts ) ? 'active' : 'finished';
            }
        }

        // -----------------------------
        //   AFEGIM REGISTRE NORMALITZAT
        // -----------------------------
        $out[] = array(
            'title'       => $title,
            'permalink'   => $permalink,
            'date'        => $date,
            'status'      => $status,
            'event_start' => $event_start,
            'event_end'   => $event_end,
            'event_state' => $event_state,
        );
    }

    return $out;
}









