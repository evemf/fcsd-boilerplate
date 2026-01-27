<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin page "Sinergia" para configurar credenciales,
 * login y mostrar Contacts + Esdeveniments desde caché local.
 *
 * FIX PERF:
 * - No sesión global en init (evita locks).
 * - Sesión solo cuando hace falta (pantalla Sinergia / admin-post).
 * - En AJAX cerramos sesión tras validar nonce para no bloquear wp-admin.
 */

/**
 * Arranca sesión SOLO en la pantalla de Sinergia.
 */
add_action('admin_init', function () {
    if ( ! is_admin() ) return;

    if ( function_exists('get_current_screen') ) {
        $screen = get_current_screen();
        if ( $screen && $screen->id === 'toplevel_page_fcsd-sinergia' ) {
            if ( ! session_id() ) {
                session_start();
            }
        }
    }
}, 1);

add_action('admin_head', function () {
    $screen = get_current_screen();
    if ( ! $screen || $screen->id !== 'toplevel_page_fcsd-sinergia') {
        return;
    }
    ?>
    <style>
      #events-table th[data-sort-key],
      #contacts-table th[data-sort-key] {
        cursor: pointer;
        position: relative;
        padding-right: 18px;
      }

      #events-table th.sorted-asc::after,
      #contacts-table th.sorted-asc::after {
        content: "▲";
        position: absolute;
        right: 6px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 10px;
        opacity: 0.7;
      }

      #events-table th.sorted-desc::after,
      #contacts-table th.sorted-desc::after {
        content: "▼";
        position: absolute;
        right: 6px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 10px;
        opacity: 0.7;
      }
    </style>
    <?php
});


/**
 * Devuelve settings guardados.
 */
function fcsd_sinergia_get_settings() {
    $defaults = [
        'api_url'        => defined('FCSD_SINERGIA_API_URL') ? FCSD_SINERGIA_API_URL : '',
        'username'       => defined('FCSD_SINERGIA_USERNAME') ? FCSD_SINERGIA_USERNAME : '',
        'password'       => defined('FCSD_SINERGIA_PASSWORD') ? FCSD_SINERGIA_PASSWORD : '',
        'lang'           => defined('FCSD_SINERGIA_LANG') ? FCSD_SINERGIA_LANG : 'es_ES',
        'verbose'        => defined('FCSD_SINERGIA_VERBOSE') ? FCSD_SINERGIA_VERBOSE : false,
        'notify_on_save' => defined('FCSD_SINERGIA_NOTIFY_ON_SAVE') ? FCSD_SINERGIA_NOTIFY_ON_SAVE : false,
    ];

    $opt = get_option('fcsd_sinergia_settings', []);
    if ( ! is_array($opt) ) $opt = [];

    return array_merge($defaults, $opt);
}

/**
 * Guarda settings.
 */
function fcsd_sinergia_save_settings($settings) {
    update_option('fcsd_sinergia_settings', $settings);
}

/**
 * Admin menu
 */
add_action('admin_menu', function () {
    add_menu_page(
        'Sinergia',
        'Sinergia',
        'manage_options',
        'fcsd-sinergia',
        'fcsd_render_sinergia_admin_page',
        'dashicons-rest-api',
        58
    );
});

/**
 * Assets solo en esta pantalla
 */
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_fcsd-sinergia') return;

    if ( ! session_id() ) session_start();

    wp_enqueue_script(
        'fcsd-sinergia-admin',
        FCSD_THEME_URI . '/assets/js/sinergia-admin.js',
        ['jquery'],
        FCSD_VERSION,
        true
    );

    wp_localize_script('fcsd-sinergia-admin', 'fcsdSinergiaAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('fcsd_sinergia_nonce'),
        'logged'   => ! empty($_SESSION['fcsd_sinergia_session_id']) ? 'yes' : 'no',
    ]);
});

/**
 * Render admin page
 */
function fcsd_render_sinergia_admin_page() {

    if ( ! session_id() ) session_start();

    $settings  = fcsd_sinergia_get_settings();
    $logged_in = ! empty($_SESSION['fcsd_sinergia_session_id']);

    $last_contacts = function_exists('fcsd_sinergia_cache_last_sync')
        ? fcsd_sinergia_cache_last_sync('contacts')
        : null;

    $last_events = function_exists('fcsd_sinergia_cache_last_sync')
        ? fcsd_sinergia_cache_last_sync('events')
        : null;
    ?>
    <div class="wrap">
        <h1>Sinergia Dashboard</h1>

        <div id="sinergia-login-status" style="margin:10px 0;padding:10px;border-left:4px solid #ccc;background:#fff;">
            Comprovant connexió amb Sinergia...
        </div>

        <nav class="nav-tab-wrapper" style="margin-bottom:15px;">
            <a href="#" class="nav-tab nav-tab-active" data-tab="login">Login</a>
            <a href="#" class="nav-tab <?php echo $logged_in ? '' : 'nav-tab-disabled'; ?>" data-tab="contacts"
               <?php echo $logged_in ? '' : 'style="pointer-events:none;opacity:.5"'; ?>>
                Contacts
            </a>
            <a href="#" class="nav-tab <?php echo $logged_in ? '' : 'nav-tab-disabled'; ?>" data-tab="events"
               <?php echo $logged_in ? '' : 'style="pointer-events:none;opacity:.5"'; ?>>
                Esdeveniments
            </a>

            <?php if ($logged_in): ?>
                <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" style="float:right;margin-top:6px;">
                    <input type="hidden" name="action" value="fcsd_sinergia_logout">
                    <?php wp_nonce_field('fcsd_sinergia_nonce', 'fcsd_sinergia_logout_nonce'); ?>
                    <button type="submit" class="button button-secondary">Tancar sessió</button>
                </form>
            <?php endif; ?>
        </nav>

        <div id="tab-login" class="fcsd-tab-pane">
            <?php if ($logged_in): ?>
                <p><strong>Hola, <?php echo esc_html($_SESSION['fcsd_sinergia_user_name']); ?>!</strong></p>
            <?php endif; ?>

            <h2>Credencials / Endpoint</h2>
            <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="fcsd_sinergia_save_and_login">
                <?php wp_nonce_field('fcsd_sinergia_nonce', 'fcsd_sinergia_save_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th><label>API URL</label></th>
                        <td>
                            <input name="api_url" type="text" class="regular-text" required
                                   value="<?php echo esc_attr($settings['api_url']); ?>"
                                   placeholder="https://.../custom/service/v4_1_SticCustom/rest.php">
                            <p class="description">Puede ser base URL o endpoint completo /rest.php.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Usuari</label></th>
                        <td><input name="username" type="text" class="regular-text" required value="<?php echo esc_attr($settings['username']); ?>"></td>
                    </tr>
                    <tr>
                        <th><label>Contrasenya</label></th>
                        <td><input name="password" type="password" class="regular-text" required value="<?php echo esc_attr($settings['password']); ?>"></td>
                    </tr>
                    <tr>
                        <th><label>Idioma</label></th>
                        <td><input name="lang" type="text" class="regular-text" value="<?php echo esc_attr($settings['lang']); ?>"></td>
                    </tr>
                    <tr>
                        <th><label>Verbose</label></th>
                        <td><label><input name="verbose" type="checkbox" <?php checked($settings['verbose']); ?>> Activar logs</label></td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php echo $logged_in ? 'Guardar credencials' : 'Guardar i iniciar sessió'; ?>
                    </button>
                </p>
            </form>

            <?php if (isset($_GET['login']) && $_GET['login']==='failed'): ?>
                <p style="color:red;">Error d'autenticació. Verifica credencials i URL.</p>
            <?php endif; ?>
        </div>

        <div id="tab-contacts" class="fcsd-tab-pane" style="display:none;">
            <div style="margin-bottom:10px;">
                <button class="button button-primary" id="btn-sync-contacts">Sincronitzar Contacts</button>
                <span id="contacts-last-sync" style="margin-left:8px;">
                    Última actualització:
                    <?php
                       echo $last_contacts
                        ? esc_html( wp_date('d/m/Y H:i', strtotime($last_contacts . ' UTC'), new DateTimeZone('Europe/Madrid')) )
                        : '—';
                    ?>
                </span>
                <span id="contacts-sync-status" style="margin-left:8px;"></span>
            </div>

            <div id="fcsd-contacts-content"><em>Carregant contacts...</em></div>
        </div>

        <div id="tab-events" class="fcsd-tab-pane" style="display:none;">
            <div style="margin-bottom:10px;">
                <button class="button button-primary" id="btn-sync-events">Sincronitzar Esdeveniments</button>
                <span id="events-last-sync" style="margin-left:8px;">
                    Última actualització:
                    <?php
                       echo $last_events
                        ? esc_html( wp_date('d/m/Y H:i', strtotime($last_events . ' UTC'), new DateTimeZone('Europe/Madrid')) )
                        : '—';
                    ?>
                </span>
                <span id="events-sync-status" style="margin-left:8px;"></span>
            </div>

            <div id="fcsd-events-content"><em>Carregant esdeveniments...</em></div>
        </div>
    </div>
    <?php
}

/**
 * Save settings + login
 */
add_action('admin_post_fcsd_sinergia_save_and_login', function () {

    if ( ! session_id() ) session_start();

    if (
        empty($_POST['fcsd_sinergia_save_nonce']) ||
        ! wp_verify_nonce($_POST['fcsd_sinergia_save_nonce'], 'fcsd_sinergia_nonce')
    ) wp_die('Nonce invàlid.');

    if ( ! current_user_can('manage_options') ) wp_die('No tens permisos.');

    $settings = [
        'api_url'        => sanitize_text_field($_POST['api_url'] ?? ''),
        'username'       => sanitize_text_field($_POST['username'] ?? ''),
        'password'       => (string) ($_POST['password'] ?? ''),
        'lang'           => sanitize_text_field($_POST['lang'] ?? 'es_ES'),
        'verbose'        => ! empty($_POST['verbose']),
        'notify_on_save' => false,
    ];

    fcsd_sinergia_save_settings($settings);

    $client = new FCSD_Sinergia_APIClient($settings['api_url']);
    $sid    = $client->login($settings['username'], $settings['password'], $settings['lang']);

    if ($sid) {
        $_SESSION['fcsd_sinergia_session_id']     = $sid;
        $_SESSION['fcsd_sinergia_user_name']     = $settings['username'];
        $_SESSION['fcsd_sinergia_user_password'] = $settings['password'];

        session_write_close();

        wp_redirect(admin_url('admin.php?page=fcsd-sinergia&tab=contacts'));
        exit;
    }

    session_write_close();

    wp_redirect(admin_url('admin.php?page=fcsd-sinergia&tab=login&login=failed'));
    exit;
});

/**
 * Logout
 */
add_action('admin_post_fcsd_sinergia_logout', function () {

    if ( ! session_id() ) session_start();

    if (
        empty($_POST['fcsd_sinergia_logout_nonce']) ||
        ! wp_verify_nonce($_POST['fcsd_sinergia_logout_nonce'], 'fcsd_sinergia_nonce')
    ) wp_die('Nonce invàlid.');

    if ( ! empty($_SESSION['fcsd_sinergia_session_id']) ) {
        $settings = fcsd_sinergia_get_settings();
        $client = new FCSD_Sinergia_APIClient($settings['api_url']);
        $client->sessionId = $_SESSION['fcsd_sinergia_session_id'];
        $client->logout();

        session_unset();
        session_destroy();
    } else {
        session_write_close();
    }

    wp_redirect(admin_url('admin.php?page=fcsd-sinergia&tab=login'));
    exit;
});

/**
 * AJAX: PING login real (para UI clara)
 */
add_action('wp_ajax_fcsd_sinergia_ping', function(){
    check_ajax_referer('fcsd_sinergia_nonce', 'nonce');

    if ( session_id() ) {
        session_write_close();
    }

    $settings = fcsd_sinergia_get_settings();
    if(empty($settings['api_url']) || empty($settings['username']) || empty($settings['password'])){
        wp_send_json_success(['logged'=>false,'reason'=>'missing_creds']);
    }

    $client = new FCSD_Sinergia_APIClient($settings['api_url']);
    $sid = $client->login($settings['username'], $settings['password'], $settings['lang']);

    if(!$sid){
        wp_send_json_success(['logged'=>false,'reason'=>'bad_login']);
    }

    $client->logout();
    wp_send_json_success(['logged'=>true]);
});


/**
 * AJAX: Contacts desde CACHÉ (tablas) con paginación
 * búsqueda global (todas las páginas)
 * inscripcions on-demand per fila (sense columna dedicada)
 * desplegable por fila
 */
add_action('wp_ajax_fcsd_sinergia_get_contacts', function () {
    check_ajax_referer('fcsd_sinergia_nonce', 'nonce');

    if ( session_id() ) session_write_close();
    if ( ! current_user_can('manage_options') ) wp_send_json_error(['message'=>'No tens permisos.']);

    if ( ! function_exists('fcsd_sinergia_get_cached_contacts_page') ) {
        wp_send_json_error(['message'=>'Capa de caché no disponible.']);
    }

    $page     = isset($_POST['page']) ? (int) $_POST['page'] : 1;
    $per_page = 50;

    $search    = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
    $search_lc = strtolower(trim($search));

    // Orden
    $sort_key = isset( $_POST['sort_key'] )
        ? sanitize_text_field( wp_unslash( $_POST['sort_key'] ) )
        : 'name';
    $sort_dir = ( isset( $_POST['sort_dir'] ) && $_POST['sort_dir'] === 'desc' ) ? 'desc' : 'asc';

    // Mapa sort_key → clave real
    $field_map = [
        'name'     => 'first_name',
        'surname'  => 'last_name',
        'email'    => 'email1',
        'mobile'   => 'phone_mobile',
        'modified' => 'date_modified',
    ];
    $internal_sort_key = isset( $field_map[ $sort_key ] ) ? $field_map[ $sort_key ] : $sort_key;

    $last_sync = fcsd_sinergia_cache_last_sync('contacts');

    // --- LÓGICA ORIGINAL DE CARGA ----------------------------------------

    if ( $search_lc !== '' ) {

        if ( function_exists('fcsd_sinergia_search_cached_contacts') ) {
            $total    = 0;
            $contacts = fcsd_sinergia_search_cached_contacts( $search_lc, $page, $per_page, $total );
            $total    = (int) $total;
        } else {
            $total    = function_exists('fcsd_sinergia_count_cached_contacts') ? fcsd_sinergia_count_cached_contacts() : 0;
            $contacts = fcsd_sinergia_get_cached_contacts_page( $page, $per_page );
        }

        $total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 1;
        $page        = max( 1, min( $page, max( 1, $total_pages ) ) );

    } else {
        $total    = function_exists('fcsd_sinergia_count_cached_contacts') ? fcsd_sinergia_count_cached_contacts() : 0;
        $contacts = fcsd_sinergia_get_cached_contacts_page( $page, $per_page );

        $total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 1;
        $page        = max( 1, min( $page, max( 1, $total_pages ) ) );
    }

    // --- ORDENAR SOLO LAS FILAS DE ESTA PÁGINA ---------------------------

    if ( is_array( $contacts ) && ! empty( $contacts ) ) {
        $date_keys = [ 'date_modified' ];

        usort( $contacts, function( $a, $b ) use ( $internal_sort_key, $sort_dir, $date_keys ) {
            $va = isset( $a[ $internal_sort_key ] ) ? $a[ $internal_sort_key ] : '';
            $vb = isset( $b[ $internal_sort_key ] ) ? $b[ $internal_sort_key ] : '';

            if ( in_array( $internal_sort_key, $date_keys, true ) ) {
                $va = strtotime( (string) $va ) ?: 0;
                $vb = strtotime( (string) $vb ) ?: 0;
            } else {
                $va = strtolower( (string) $va );
                $vb = strtolower( (string) $vb );
            }

            if ( $va == $vb ) {
                return 0;
            }

            $result = ( $va < $vb ) ? -1 : 1;

            return ( 'asc' === $sort_dir ) ? $result : -$result;
        });
    }

    // --- HTML -------------------------------------------------------------

    $html  = '<div class="wrap">';
    $html .= '<h2>Llistat de Contacts ('.$total.')</h2>';

    if($last_sync){
        $html .= '<p><small>Última actualització local: '.esc_html(date_i18n('d/m/Y H:i', strtotime($last_sync))).'</small></p>';
    } else {
        $html .= '<p><small>No hi ha cap sincronització encara.</small></p>';
    }

    $html .= '<form id="contact-search-form" method="get" style="margin-bottom:10px;">'
           . '<input type="text" id="contact-search" name="s" '
           . 'placeholder="Cerca a tots els camps..." '
           . 'value="' . ( isset( $search ) ? esc_attr( $search ) : '' ) . '" '
           . 'style="margin-right:5px;padding:5px;width:300px;" />'
           . '<button type="submit" class="button">Buscar</button>'
           . '</form>';

    if ( ! empty($contacts) ) {
        $html .= '<table class="wp-list-table widefat fixed striped" id="contacts-table">
            <thead><tr>
                <th data-sort-type="text" data-sort-key="name">Nom</th>
                <th data-sort-type="text" data-sort-key="surname">Cognoms</th>
                <th data-sort-type="text" data-sort-key="email">Email</th>
                <th data-sort-type="text" data-sort-key="mobile">Mòbil</th>
                <th data-sort-type="date" data-sort-key="modified">Modificat</th>
            </tr></thead><tbody>';

        foreach ($contacts as $c) {
            if (!is_array($c)) continue;

            $contact_id = $c['id'] ?? '';

            $html .= '<tr class="contact-row" data-contact-id="'.esc_attr($contact_id).'">
                <td>'.esc_html($c['first_name'] ?? '—').'</td>
                <td>'.esc_html($c['last_name'] ?? '—').'</td>
                <td>'.esc_html($c['email1'] ?? '—').'</td>
                <td>'.esc_html($c['phone_mobile'] ?? '—').'</td>
                <td>'.esc_html($c['date_modified'] ?? '—').'</td>
            </tr>';

            $html .= '<tr class="registrations-row" data-contact-id="'.esc_attr($contact_id).'" style="display:none;">
                <td colspan="5">
                    <div class="registrations-container"><em>Carregant inscripcions...</em></div>
                </td>
            </tr>';
        }

        $html .= '</tbody></table>';

        if ( $total_pages > 1 ) {
            $html .= '<div class="tablenav"><div class="tablenav-pages fcsd-contacts-pagination">';
            $html .= '<span class="displaying-num">'.esc_html($total).' registres</span> ';
            $html .= '<span class="pagination-links">';

            if ( $page > 1 ) {
                $html .= '<a class="button fcsd-sinergia-page" data-target="contacts" data-page="'.($page-1).'">« Anterior</a> ';
            }

            $html .= '<span class="paging-input">'.esc_html($page).' / '.esc_html($total_pages).'</span>';

            if ( $page < $total_pages ) {
                $html .= ' <a class="button fcsd-sinergia-page" data-target="contacts" data-page="'.($page+1).'">Siguiente »</a>';
            }

            $html .= '</span></div></div>';
        }

        $html .= '</div>';
    } else {
        if ( $search_lc !== '' ) {
            $html .= '<p>No hi ha resultats per a aquesta cerca.</p></div>';
        } else {
            $html .= '<p>No hi ha contacts locals. Prem "Sincronitzar".</p></div>';
        }
    }

    wp_send_json_success([
        'html'        => $html,
        'page'        => $page,
        'total_pages' => $total_pages,
        'total'       => $total,
        'search'      => $search,
    ]);
});



/**
 * AJAX: INSCRIPCIONS on-demand (ADMIN)
 * Refresca SOLO un contacto si hay login válido (force=1)
 */
add_action('wp_ajax_fcsd_sinergia_get_contact_registrations_admin', function () {
    check_ajax_referer('fcsd_sinergia_nonce', 'nonce');

    if ( session_id() ) session_write_close();
    if ( ! current_user_can('manage_options') ) wp_send_json_error(['message'=>'No tens permisos.']);

    $contact_id = sanitize_text_field($_POST['contact_id'] ?? '');
    $force = !empty($_POST['force']);

    if ( ! $contact_id ) {
        wp_send_json_error(['message'=>'Missing contact_id']);
    }

    // Si pedimos force, refrescamos contra API (solo este contacto)
    if ( $force && function_exists('fcsd_sinergia_refresh_registrations_for_contact') ) {
        $settings = fcsd_sinergia_get_settings();
        $client   = new FCSD_Sinergia_APIClient($settings['api_url']);
        $sid = $client->login($settings['username'],$settings['password'],$settings['lang']);
        if ( $sid ) {
            fcsd_sinergia_refresh_registrations_for_contact($client, $contact_id);
            $client->logout();
        }
    }

    $regs = function_exists('fcsd_sinergia_get_cached_registrations_for_contact')
        ? fcsd_sinergia_get_cached_registrations_for_contact($contact_id)
        : [];

    ob_start();
    if ( empty($regs) ) {
        echo '<p class="description" style="margin:0;">No hi ha inscripcions.</p>';
    } else {
        echo '<ul style="margin:0 0 0 16px;list-style:disc;">';
        foreach ($regs as $r) {
            if (!is_array($r)) continue;
            $title = esc_html($r['name'] ?? '');
            $status= esc_html($r['status_c'] ?? '');
            $date  = esc_html($r['registration_date_c'] ?? ($r['date_modified'] ?? ''));
            echo "<li><strong>{$title}</strong>";
            if ($date) echo " — {$date}";
            if ($status) echo " <em>({$status})</em>";
            echo "</li>";
        }
        echo '</ul>';
    }
    $html = ob_get_clean();

    $count = function_exists('fcsd_sinergia_count_cached_registrations_for_contact')
        ? (int) fcsd_sinergia_count_cached_registrations_for_contact($contact_id)
        : (is_array($regs) ? count($regs) : 0);

    wp_send_json_success([
        'html'  => $html,
        'count' => $count
    ]);
});


/**
 * AJAX: Esdeveniments desde CACHÉ (tablas) con paginación
 */
add_action('wp_ajax_fcsd_sinergia_get_events', function () {
    check_ajax_referer('fcsd_sinergia_nonce', 'nonce');

    if ( session_id() ) session_write_close();
    if ( ! current_user_can('manage_options') ) wp_send_json_error(['message'=>'No tens permisos.']);

    if ( ! function_exists('fcsd_sinergia_get_cached_events_page') ) {
        wp_send_json_error(['message'=>'Capa de caché no disponible.']);
    }

    $page     = isset( $_POST['page'] ) ? (int) $_POST['page'] : 1;
    $per_page = 50;

    // Búsqueda
    $search = isset( $_POST['search'] )
        ? trim( sanitize_text_field( wp_unslash( $_POST['search'] ) ) )
        : '';

    // Orden (desde el JS)
    $sort_key = isset( $_POST['sort_key'] )
        ? sanitize_text_field( wp_unslash( $_POST['sort_key'] ) )
        : 'start_date';
    $sort_dir = ( isset( $_POST['sort_dir'] ) && $_POST['sort_dir'] === 'desc' ) ? 'desc' : 'asc';

    $last_sync = fcsd_sinergia_cache_last_sync( 'events' );

    // --- LÓGICA ORIGINAL DE CARGA DESDE CACHÉ -----------------------------

    if ( $search !== '' && function_exists( 'fcsd_sinergia_search_cached_events' ) ) {
        // Búsqueda global en la tabla de caché de eventos
        $total  = 0;
        $events = fcsd_sinergia_search_cached_events( $search, $page, $per_page, $total );
        $total  = (int) $total;
    } else {
        // Listado normal, sin filtro de búsqueda
        $total  = function_exists( 'fcsd_sinergia_count_cached_events' )
            ? fcsd_sinergia_count_cached_events()
            : 0;
        $events = fcsd_sinergia_get_cached_events_page( $page, $per_page );
    }

    $total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 1;
    $page        = max( 1, min( $page, max( 1, $total_pages ) ) );

    // --- ORDENAR SOLO LAS FILAS DE ESTA PÁGINA ---------------------------

    if ( is_array( $events ) && ! empty( $events ) ) {
        $numeric_keys = [ 'price' ];
        $date_keys    = [ 'start_date', 'end_date' ];

        usort( $events, function ( $a, $b ) use ( $sort_key, $sort_dir, $numeric_keys, $date_keys ) {
            $va = isset( $a[ $sort_key ] ) ? $a[ $sort_key ] : '';
            $vb = isset( $b[ $sort_key ] ) ? $b[ $sort_key ] : '';

            // numérico
            if ( in_array( $sort_key, $numeric_keys, true ) ) {
                $va = floatval( str_replace( ',', '.', preg_replace( '/[^\d,.\-]/', '', (string) $va ) ) );
                $vb = floatval( str_replace( ',', '.', preg_replace( '/[^\d,.\-]/', '', (string) $vb ) ) );
            }
            // fecha
            elseif ( in_array( $sort_key, $date_keys, true ) ) {
                $va = strtotime( (string) $va ) ?: 0;
                $vb = strtotime( (string) $vb ) ?: 0;
            } else {
                // texto
                $va = strtolower( (string) $va );
                $vb = strtolower( (string) $vb );
            }

            if ( $va == $vb ) {
                return 0;
            }

            $result = ( $va < $vb ) ? -1 : 1;

            return ( 'asc' === $sort_dir ) ? $result : -$result;
        });
    }

    // --- HTML -------------------------------------------------------------

    $html  = '<div class="wrap">';
    $html .= '<h2>Llistat d\'Esdeveniments (' . intval( $total ) . ')</h2>';

    if ( $last_sync ) {
        $html .= '<p><small>Última actualització local: ' .
                 esc_html( date_i18n( 'd/m/Y H:i', strtotime( $last_sync ) ) ) .
                 '</small></p>';
    } else {
        $html .= '<p><small>No hi ha cap sincronització encara.</small></p>';
    }

    $html .= '<form id="event-search-form" method="get" style="margin-bottom:10px;">'
           . '<input type="text" id="event-search" name="s" '
           . 'placeholder="Cerca a tots els camps..." '
           . 'value="' . esc_attr( $search ) . '" '
           . 'style="margin-right:5px;padding:5px;width:300px;" />'
           . '<button type="submit" class="button">Buscar</button>'
           . '</form>';

    if ( ! empty($events) ) {
        $html .= '<table class="wp-list-table widefat fixed striped" id="events-table" data-page="'.esc_attr($page).'">
            <thead><tr>
                <th data-sort-type="text"   data-sort-key="name">Nom</th>
                <th data-sort-type="text"   data-sort-key="type">Tipus</th>
                <th data-sort-type="date"   data-sort-key="start_date">Inici</th>
                <th data-sort-type="date"   data-sort-key="end_date">Fi</th>
                <th data-sort-type="text"   data-sort-key="status">Estat</th>
                <th data-sort-type="number" data-sort-key="price">Preu</th>
                <th data-sort-type="text"   data-sort-key="assigned_user_name">Assignat</th>
                <th data-sort-type="text"   data-sort-key="wp_event">Event WP</th>
                <th>Accions</th>
            </tr></thead><tbody>';

        foreach ($events as $e) {
            if (!is_array($e)) continue;

            $sin_id = $e['id'] ?? '';
            $event_post_id = $sin_id && function_exists('fcsd_sinergia_find_event_post_id_by_sinergia_id')
                ? fcsd_sinergia_find_event_post_id_by_sinergia_id( $sin_id )
                : 0;

            $wp_event_col = '—';
            if ( $event_post_id ) {
                $title = get_the_title( $event_post_id );
                $edit  = get_edit_post_link( $event_post_id );
                if ( $edit ) {
                    $wp_event_col = '<a href="'.esc_url($edit).'">'.esc_html($title).'</a>';
                } else {
                    $wp_event_col = esc_html($title);
                }
            }

            $action_btn = '—';
            if ( $sin_id ) {
                $label = $event_post_id ? __('Despublicar', 'fcsd') : __('Publicar', 'fcsd');
                $action_btn = '<button type="button" class="button fcsd-sync-add-event"
                                    data-sinergia-event-id="'.esc_attr($sin_id).'"
                                    data-event-post-id="'.esc_attr($event_post_id).'">'.
                              esc_html($label).'</button>';
            }

            $html .= '<tr>
                <td>'.esc_html($e['name'] ?? '').'</td>
                <td>'.esc_html($e['type'] ?? '').'</td>
                <td>'.esc_html($e['start_date'] ?? '').'</td>
                <td>'.esc_html($e['end_date'] ?? '').'</td>
                <td>'.esc_html($e['status'] ?? '').'</td>
                <td>'.esc_html($e['price'] ?? '').'</td>
                <td>'.esc_html($e['assigned_user_name'] ?? '').'</td>
                <td>'.$wp_event_col.'</td>
                <td>'.$action_btn.'</td>
            </tr>';
        }

        $html .= '</tbody></table>';

        if ( $total_pages > 1 ) {
            $html .= '<div class="tablenav"><div class="tablenav-pages fcsd-events-pagination">';
            $html .= '<span class="displaying-num">'.esc_html($total).' registres</span> ';
            $html .= '<span class="pagination-links">';

            if ( $page > 1 ) {
                $html .= '<a class="button fcsd-sinergia-page" data-target="events" data-page="'.($page-1).'">« Anterior</a> ';
            }

            $html .= '<span class="paging-input">'.esc_html($page).' / '.esc_html($total_pages).'</span>';

            if ( $page < $total_pages ) {
                $html .= ' <a class="button fcsd-sinergia-page" data-target="events" data-page="'.($page+1).'">Siguiente »</a>';
            }

            $html .= '</span></div></div>';
        }

        $html .= '</div>';
     } else {
        if ( $search !== '' ) {
            $html .= '<p>No hi ha resultats per a aquesta cerca.</p></div>';
        } else {
            $html .= '<p>No hi ha esdeveniments locals. Prem "Sincronitzar".</p></div>';
        }
    }

    wp_send_json_success([
        'html'        => $html,
        'page'        => $page,
        'total_pages' => $total_pages,
        'total'       => $total,
    ]);
});



/**
 * AJAX: Crear/actualitzar un post type event des d'un esdeveniment de Sinergia.
 */
add_action('wp_ajax_fcsd_sinergia_upsert_event_post', function () {
    check_ajax_referer('fcsd_sinergia_nonce', 'nonce');

    if ( session_id() ) session_write_close();
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error(['message' => 'No tens permisos.']);
    }

    $sin_id = sanitize_text_field( $_POST['sinergia_event_id'] ?? '' );
    if ( ! $sin_id ) {
        wp_send_json_error(['message' => 'Falta sinergia_event_id.']);
    }

    if ( ! function_exists('fcsd_sinergia_get_cached_event_by_id') ) {
        wp_send_json_error(['message' => 'Capa de caché de esdeveniments no disponible.']);
    }

    $event_data = fcsd_sinergia_get_cached_event_by_id( $sin_id );
    if ( empty( $event_data ) ) {
        wp_send_json_error(['message' => 'Esdeveniment no trobat a la caché local. Sincronitza els esdeveniments.']);
    }

    $post_id = function_exists('fcsd_sinergia_find_event_post_id_by_sinergia_id')
        ? fcsd_sinergia_find_event_post_id_by_sinergia_id( $sin_id )
        : 0;

    $postarr = [
        'post_type'   => 'event',
        'post_status' => 'publish',
        'post_title'  => $event_data['name'] ?? '',
        'post_content'=> '',
    ];

    if ( $post_id ) {
        $postarr['ID'] = $post_id;
        $post_id = wp_update_post( $postarr, true );
    } else {
        $post_id = wp_insert_post( $postarr, true );
    }

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error(['message' => $post_id->get_error_message()]);
    }

    // Guardar metadades bàsiques
    update_post_meta( $post_id, 'fcsd_sinergia_event_id', $sin_id );
    update_post_meta( $post_id, 'fcsd_event_start',      $event_data['start_date'] ?? '' );
    update_post_meta( $post_id, 'fcsd_event_end',        $event_data['end_date'] ?? '' );
    update_post_meta( $post_id, 'fcsd_event_price',      $event_data['price'] ?? '' );

    wp_send_json_success([
        'post_id'   => $post_id,
        'title'     => get_the_title( $post_id ),
        'edit_link' => get_edit_post_link( $post_id ),
    ]);
});

/**
 * AJAX: Esborrar (despublicar) el post type event vinculat a un esdeveniment de Sinergia.
 */
add_action('wp_ajax_fcsd_sinergia_delete_event_post', function () {
    check_ajax_referer('fcsd_sinergia_nonce', 'nonce');

    if ( session_id() ) {
        session_write_close();
    }

    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error(['message' => 'No tens permisos.']);
    }

    $post_id = 0;

    // Prioritza event_post_id si ve directament del JS
    if ( ! empty( $_POST['event_post_id'] ) ) {
        $post_id = (int) $_POST['event_post_id'];
    }
    // Alternativa: buscar pel sinergia_event_id
    elseif ( ! empty( $_POST['sinergia_event_id'] ) && function_exists('fcsd_sinergia_find_event_post_id_by_sinergia_id') ) {
        $sin_id  = sanitize_text_field( $_POST['sinergia_event_id'] );
        $post_id = fcsd_sinergia_find_event_post_id_by_sinergia_id( $sin_id );
    }

    if ( ! $post_id ) {
        wp_send_json_error(['message' => 'No s\'ha trobat cap event a WordPress per eliminar.']);
    }

    $deleted = wp_delete_post( $post_id, true ); // true = esborrar definitivament

    if ( ! $deleted ) {
        wp_send_json_error(['message' => 'No s\'ha pogut esborrar l\'event.']);
    }

    wp_send_json_success([
        'deleted'  => true,
        'post_id'  => $post_id,
    ]);
});



/**
 * AJAX: Sincroniza Contacts -> guarda en tablas
 * NO sincroniza inscripciones (estas van on-demand por contacto)
 * Añadido: robustez extra con $entries para evitar 500 si la API devuelve algo raro
 */
add_action('wp_ajax_fcsd_sinergia_sync_contacts', function () {
    check_ajax_referer('fcsd_sinergia_nonce', 'nonce');

    // Important: que no se quede la sessió PHP bloqueando altres peticions
    if ( session_id() ) {
        session_write_close();
    }

    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error( [ 'message' => 'No perms' ] );
    }

    if ( ! function_exists('fcsd_sinergia_cache_upsert_contact') ) {
        wp_send_json_error( [ 'message' => 'Capa de caché no disponible.' ] );
    }

    // Evitar timeouts de PHP (el timeout que veus al screenshot és del HTTP cap a Sinergia)
    if ( function_exists( 'wp_raise_memory_limit' ) ) {
        wp_raise_memory_limit( 'admin' );
    }
    @set_time_limit(0);
    ignore_user_abort(true);

    $settings = fcsd_sinergia_get_settings();
    $client   = new FCSD_Sinergia_APIClient( $settings['api_url'] );

    $sid = $client->login( $settings['username'], $settings['password'], $settings['lang'] );
    if ( ! $sid ) {
        wp_send_json_error( [ 'message' => 'Login Sinergia KO' ] );
    }

    // ---------- PARÁMETROS DE BATCH ----------
    // offset nos llega desde el JS, por defecto 0
    $offset    = isset( $_POST['offset'] ) ? max( 0, intval( $_POST['offset'] ) ) : 0;
    $page_size = 200; // ← aquí controlas cuántos contacts per crida

    $saved      = 0;
    $saved_regs = 0; // lo mantenemos por compatibilidad con el código anterior
    $iterations = 1; // aquesta crida AJAX és una única iteració

    error_log( '[Sinergia Contacts Sync] Batch sync start. Offset=' . $offset . ' PageSize=' . $page_size );

    $params = [
        'module_name'   => 'Contacts',
        'query'         => 'contacts.deleted = 0',
        'order_by'      => 'contacts.date_modified DESC',
        'offset'        => $offset,
        'select_fields' => apply_filters( 'fcsd_sinergia_contact_select_fields', [] ),
        'link_name_to_fields_array' => [],
        'max_results'   => $page_size,
        'deleted'       => 0,
    ];

    $list = $client->getEntryList( $params );

    if ( is_wp_error( $list ) ) {
        error_log( '[Sinergia Contacts Sync] Error en getEntryList (offset ' . $offset . '): ' . $list->get_error_message() );
        $client->logout();

        wp_send_json_error( [
            'message' => 'Error en getEntryList: ' . $list->get_error_message(),
        ] );
    }

    if ( ! is_object( $list ) ) {
        error_log( '[Sinergia Contacts Sync] Resposta buida o no vàlida en getEntryList (no és objecte). Offset=' . $offset );
        $client->logout();

        wp_send_json_error( [
            'message' => 'Resposta buida o no vàlida de Sinergia.',
        ] );
    }

    $entries = ( ! empty( $list->entry_list ) && is_array( $list->entry_list ) )
        ? $list->entry_list
        : [];

    $count = count( $entries );
    error_log( "[Sinergia Contacts Sync] Retrieved $count contacts at offset $offset" );

    if ( $count ) {
        foreach ( $entries as $entry ) {
            $person = function_exists( 'fcsd_sinergia_normalize_contact_entry' )
                ? fcsd_sinergia_normalize_contact_entry( $entry )
                : [];

            if ( ! empty( $person['id'] ) && count( $person ) > 1 ) {
                $result = fcsd_sinergia_cache_upsert_contact( $person );
                if ( $result ) {
                    $saved++;
                }

                // Inscripcions per contacte (si ho tens activat via filtre)
                if ( apply_filters( 'fcsd_sinergia_sync_registrations_bulk', false ) ) {
                    // fcsd_sinergia_get_contact_registrations( $client, $person['id'] );
                }
            }
        }
    }

    // ---------- CÀLCUL DE SI HEM ACABAT ----------
    $finished    = false;
    $next_offset = null;

    if ( isset( $list->next_offset ) ) {
        $next_offset = (int) $list->next_offset;

        if ( $next_offset < 0 || $count === 0 ) {
            $finished = true;
            error_log( "[Sinergia Contacts Sync] Finished. Last offset $offset, count $count" );
        }
    } else {
        // Si l'API no dóna next_offset, deduïm per tamany de la pàgina
        if ( $count < $page_size ) {
            $finished = true;
            error_log( "[Sinergia Contacts Sync] Finished by page size. Last offset $offset, count $count" );
        } else {
            $next_offset = $offset + $page_size;
        }
    }

    // Només actualitzem la data de darrera sincronització quan ja hem acabat totes les pàgines
    $last = null;
    if ( $finished ) {
        fcsd_sinergia_cache_set_last_sync( 'contacts' );
        $last = fcsd_sinergia_cache_last_sync( 'contacts' );
    }

    $client->logout();

    // Comptem quants contacts tenim a la caché per mostrar el total
    $total_cache = function_exists( 'fcsd_sinergia_count_cached_contacts' )
        ? fcsd_sinergia_count_cached_contacts()
        : 0;

    wp_send_json_success( [
        'total'           => $total_cache,
        'saved'           => $saved,          // guardats en aquest batch
        'saved_regs'      => $saved_regs,     // per compatibilitat
        'iterations'      => $iterations,
        'finished'        => $finished,
        'next_offset'     => $finished ? null : $next_offset,
        'last_sync'       => $last,
        'last_sync_human' => $last ? date_i18n( 'd/m/Y H:i', strtotime( $last ) ) : '–',
    ] );
});



/**
 * AJAX: Sincroniza Events -> guarda en tablas
 * Añadido 'saved' => $total para que el JS no muestre undefined
 */
add_action('wp_ajax_fcsd_sinergia_sync_events', function(){
    check_ajax_referer('fcsd_sinergia_nonce','nonce');

    if ( session_id() ) {
        session_write_close();
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No perms']);
    }

    if ( ! function_exists('fcsd_sinergia_cache_upsert_event') ) {
        wp_send_json_error(['message'=>'Capa de caché no disponible.']);
    }

    $settings = fcsd_sinergia_get_settings();
    $client   = new FCSD_Sinergia_APIClient($settings['api_url']);

    $sid = $client->login($settings['username'],$settings['password'],$settings['lang']);
    if ( ! $sid ) {
        wp_send_json_error(['message'=>'Login Sinergia KO']);
    }

    // IMPORTANTE: Sugar/Suite suele limitar a 100 resultados por página
    $offset       = 0;
    $total        = 0;
    $page_size    = 200;
    $iterations   = 0;
    $max_iterations = 500;

    do {
        $iterations++;

        if ( $iterations > $max_iterations ) {
            error_log('[Sinergia Events Sync] WARNING: Stopped after ' . $max_iterations . ' iterations');
            break;
        }

        $params = [
            'module_name' => 'stic_Events',
            'query'       => "stic_events.deleted=0",
            'order_by'    => 'date_modified DESC',
            'offset'      => $offset,
            'select_fields' => [
                'id',
                'name',
                'type',
                'start_date',
                'end_date',
                'status',
                'price',
                'assigned_user_name',
                'assigned_user_id',
                'date_modified',
            ],
            'max_results' => $page_size,
            'deleted'     => 0,
        ];

        $list = $client->getEntryList($params);

        if ( is_wp_error($list) ) {
            error_log('[Sinergia Events Sync] Error en getEntryList: ' . $list->get_error_message());
            break;
        }

        if ( ! is_object($list) ) {
            error_log('[Sinergia Events Sync] Resposta buida o no vàlida en getEntryList (no és objecte).');
            break;
        }

        $entries = (!empty($list->entry_list) && is_array($list->entry_list))
            ? $list->entry_list
            : [];

        $count = count($entries);

                if ( $count ) {
            foreach ( $entries as $entry ) {
                $vals = [];

                if ( ! empty( $entry->name_value_list ) ) {
                    foreach ( $entry->name_value_list as $nv ) {
                        $vals[ $nv->name ] = $nv->value;
                    }
                }

                // ID de l'esdeveniment
                $vals['id'] = $entry->id;

                // 👇 Fallback robust: si no tenim assigned_user_id però sí username ("Assignat"),
                // el resolenem via API d'usuaris de Sinergia.
                if (
                    empty( $vals['assigned_user_id'] )
                    && ! empty( $vals['assigned_user_name'] )
                    && function_exists( 'fcsd_sinergia_get_user_id_by_username' )
                ) {
                    $resolved = fcsd_sinergia_get_user_id_by_username( $vals['assigned_user_name'] );
                    if ( ! empty( $resolved ) ) {
                        $vals['assigned_user_id'] = $resolved;
                    }
                }

                // Guardem a la taula de caché (payload JSON inclou assigned_user_id)
                fcsd_sinergia_cache_upsert_event( $vals );
                $total++;
            }
        }


        if ( ! isset($list->next_offset) ) {
            // Sin next_offset, asumimos que no hay más páginas
            break;
        }

        $next_offset = (int) $list->next_offset;

        if ( $next_offset < 0 || $count === 0 ) {
            // Hemos llegado al final
            break;
        }

        $offset = $next_offset;

        // Pequeño throttle para no saturar el servidor / Sinergia
        usleep(100000); // 0,1s

    } while ( true );

    fcsd_sinergia_cache_set_last_sync('events');
    $client->logout();

    $last = fcsd_sinergia_cache_last_sync('events');

    wp_send_json_success([
        'total'           => $total,
        'saved'           => $total,
        'last_sync'       => $last,
        'last_sync_human' => $last ? date_i18n('d/m/Y H:i', strtotime($last)) : '—',
    ]);
});


/**
 * CLIENT API
 */
if ( ! class_exists( 'FCSD_Sinergia_APIClient' ) ) :

class FCSD_Sinergia_APIClient {

    public $endpoint;
    public $sessionId = null;

    /**
     * FCSD_Sinergia_APIClient constructor.
     *
     * @param string $base_url URL base de Sinergia (sin "service/v4_1/rest.php").
     */
    public function __construct( $base_url ) {
        $base_url = rtrim( $base_url, "/ \t\n\r\0\x0B" );

        // Si ya es un endpoint completo (.../rest.php), no le añadimos nada.
        if ( preg_match( '#/rest\.php$#', $base_url ) ) {
            $this->endpoint = $base_url;
        } else {
            // Si es base URL, añadimos el endpoint estándar
            $this->endpoint = $base_url . '/service/v4_1/rest.php';
        }
    }

    protected function request( $method, $parameters = array() ) {
        $args = array(
            'body'      => array(
                'method'        => $method,
                'input_type'    => 'JSON',
                'response_type' => 'JSON',
                'rest_data'     => wp_json_encode( $parameters ),
            ),
            'timeout'   => 60,
            'sslverify' => false,
        );

        $response = wp_remote_post( $this->endpoint, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );
        if ( ! $body ) {
            return new WP_Error( 'empty_response', 'Resposta buida de Sinergia.' );
        }

        $data = json_decode( $body );

        if ( isset( $data->name ) && $data->name === 'Invalid Session ID' ) {
            return new WP_Error( 'invalid_session', 'Sessió no vàlida a Sinergia.' );
        }

        return $data;
    }

    public function login( $username, $password, $lang = 'ca_ES' ) {
        $params  = array(
            'user_auth' => array(
                'user_name' => $username,
                'password'  => md5( $password ),
                'version'   => '1',
            ),
            'application_name' => 'WordPress',
            'name_value_list'  => array(
                array(
                    'name'  => 'language',
                    'value' => $lang,
                ),
            ),
        );
        $result  = $this->request( 'login', $params );

        if ( is_wp_error( $result ) ) {
            return false;
        }

        if ( ! empty( $result->id ) ) {
            $this->sessionId = $result->id;
            return $this->sessionId;
        }

        return false;
    }

    public function logout() {
        if ( ! $this->sessionId ) {
            return;
        }
        $params = array(
            'session' => $this->sessionId,
        );
        $this->request( 'logout', $params );
        $this->sessionId = null;
    }

    public function getEntryList( $params ) {
        if ( ! $this->sessionId && ! empty( $params['session'] ) ) {
            $this->sessionId = $params['session'];
        }
        $params['session'] = $this->sessionId;

        return $this->request( 'get_entry_list', $params );
    }

}

endif;
