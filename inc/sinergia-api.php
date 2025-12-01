<?php
/**
 * Conexión básica con la API de SinergiaCRM (basado en SugarCRM v4_1 REST).
 *
 * - Define las constantes de conexión (puedes sobreescribirlas en wp-config.php).
 * - Proporciona un cliente PHP sencillo para llamar a la API.
 * - Expone un helper fcsd_sinergia_get_client() que devuelve un cliente ya logueado.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * CONFIGURACIÓN
 *
 * Lo ideal es definir estas constantes en wp-config.php, por ejemplo:
 *
 *   define( 'FCSD_SINERGIA_API_URL', 'https://tu-instancia.sinergiacrm.org/' );
 *   define( 'FCSD_SINERGIA_USERNAME', 'api-user' );
 *   define( 'FCSD_SINERGIA_PASSWORD', 'api-password' );
 *   define( 'FCSD_SINERGIA_LANG', 'ca_ES' );
 */

if ( ! defined( 'FCSD_SINERGIA_API_URL' ) ) {
    // Debe ser la URL base de Sinergia (sin "service/v4_1/rest.php").
    define( 'FCSD_SINERGIA_API_URL', '' );
}

if ( ! defined( 'FCSD_SINERGIA_USERNAME' ) ) {
    define( 'FCSD_SINERGIA_USERNAME', '' );
}

if ( ! defined( 'FCSD_SINERGIA_PASSWORD' ) ) {
    define( 'FCSD_SINERGIA_PASSWORD', '' );
}

if ( ! defined( 'FCSD_SINERGIA_LANG' ) ) {
    define( 'FCSD_SINERGIA_LANG', 'ca_ES' );
}

/**
 * Cliente ligero para la API REST de Sugar/Sinergia.
 */
class FCSD_Sinergia_APIClient {

    /**
     * URL completa del endpoint REST (service/v4_1/rest.php).
     *
     * @var string
     */
    protected $endpoint;

    /**
     * ID de sesión devuelto por login.
     *
     * @var string|null
     */
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
            // Si es base URL, añadimos el endpoint estándar.
            $this->endpoint = $base_url . '/service/v4_1/rest.php';
        }
    }


    /**
     * Llamada genérica a la API.
     *
     * @param string $method  Nombre del método (login, get_entry_list, set_entry, etc.).
     * @param array  $params  Parámetros del método.
     *
     * @return mixed|WP_Error
     */
    public function call( $method, $params = array() ) {
        $body = array(
            'method'        => $method,
            'input_type'    => 'JSON',
            'response_type' => 'JSON',
            'rest_data'     => wp_json_encode( $params ),
        );

        $response = wp_remote_post(
            $this->endpoint,
            array(
                'timeout' => 20,
                'body'    => $body,
            )
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'sinergia_http', sprintf( 'Error HTTP a SinergiaCRM: %s', $response->get_error_message() ) );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== (int) $code ) {
            return new WP_Error( 'sinergia_http_code', sprintf( 'SinergiaCRM ha devuelto un código HTTP %d.', $code ) );
        }

        $raw_body = wp_remote_retrieve_body( $response );
        $data     = json_decode( $raw_body );

        if ( null === $data && JSON_ERROR_NONE !== json_last_error() ) {
            return new WP_Error( 'sinergia_json', 'No se ha podido decodificar la respuesta JSON de SinergiaCRM.' );
        }

        // Algunos errores en Sugar/Sinergia vienen con propiedades "name" y "description".
        if ( is_object( $data ) && isset( $data->name ) && isset( $data->description ) && 'SugarApiException' === $data->name ) {
            return new WP_Error( 'sinergia_api', sprintf( 'Error en la API de SinergiaCRM: %s', $data->description ) );
        }

        return $data;
    }

    /**
     * Inicia sesión y guarda el sessionId en la instancia.
     *
     * @param string $username Usuario de la API.
     * @param string $password Contraseña de la API (texto plano; se envía como md5).
     * @param string $language Código de idioma (ej: "ca_ES").
     *
     * @return string|false sessionId o false en caso de error.
     */
    public function login( $username, $password, $language = 'ca_ES' ) {
        $params = array(
            'user_auth'       => array(
                'user_name' => $username,
                // SugarCRM espera el password en MD5.
                'password'  => md5( $password ),
                'version'   => '1',
            ),
            'application_name' => 'FCSD WordPress',
            'name_value_list'  => array(
                array(
                    'name'  => 'language',
                    'value' => $language,
                ),
            ),
        );

        $result = $this->call( 'login', $params );

        if ( is_wp_error( $result ) ) {
            return false;
        }

        if ( isset( $result->id ) && ! empty( $result->id ) ) {
            $this->sessionId = $result->id;
            return $this->sessionId;
        }

        return false;
    }

    /**
     * Cierra la sesión en Sinergia.
     *
     * @return void
     */
    public function logout() {
        if ( empty( $this->sessionId ) ) {
            return;
        }

        $this->call(
            'logout',
            array(
                'session' => $this->sessionId,
            )
        );

        $this->sessionId = null;
    }

    /**
     * Wrapper de get_entry_list.
     *
     * @param array $params Parámetros nativos de get_entry_list.
     *
     * @return mixed|WP_Error
     */
    public function getEntryList( $params ) {
        $params = array_merge(
            array(
                'session' => $this->sessionId,
            ),
            $params
        );

        return $this->call( 'get_entry_list', $params );
    }

    /**
     * Wrapper de get_entry.
     *
     * @param array $params Parámetros nativos de get_entry.
     *
     * @return mixed|WP_Error
     */
    public function getEntry( $params ) {
        $params = array_merge(
            array(
                'session' => $this->sessionId,
            ),
            $params
        );

        return $this->call( 'get_entry', $params );
    }

    /**
     * Wrapper de set_entry.
     *
     * @param array $params Parámetros nativos de set_entry.
     *
     * @return mixed|WP_Error
     */
    public function setEntry( $params ) {
        $params = array_merge(
            array(
                'session' => $this->sessionId,
            ),
            $params
        );

        return $this->call( 'set_entry', $params );
    }
}

/**
 * Devuelve un cliente autenticado listo para usar o un WP_Error si algo falla.
 *
 * @return FCSD_Sinergia_APIClient|WP_Error
 */
function fcsd_sinergia_get_client() {
    if ( empty( FCSD_SINERGIA_API_URL ) || empty( FCSD_SINERGIA_USERNAME ) || empty( FCSD_SINERGIA_PASSWORD ) ) {
        return new WP_Error( 'sinergia_config', __( 'Configuració de Sinergia incompleta.', 'fcsd' ) );
    }

    $client = new FCSD_Sinergia_APIClient( FCSD_SINERGIA_API_URL );
    $sid    = $client->login( FCSD_SINERGIA_USERNAME, FCSD_SINERGIA_PASSWORD, FCSD_SINERGIA_LANG );

    if ( ! $sid ) {
        return new WP_Error( 'sinergia_login', __( 'No s\'ha pogut iniciar sessió a Sinergia.', 'fcsd' ) );
    }

    return $client;
}

/**
 * Retorna l'ID intern de Sinergia d'un usuari (Users.id) a partir del seu user_name.
 *
 * Es cacheja en transients per evitar crides repetides a l'API.
 *
 * @param string $username
 * @return string ID de Sinergia o cadena buida si no es troba.
 */
function fcsd_sinergia_get_user_id_by_username( $username ) {
    $username = trim( (string) $username );
    if ( $username === '' ) {
        return '';
    }

    $cache_key = 'fcsd_sinergia_user_id_' . md5( $username );
    $cached    = get_transient( $cache_key );

    if ( false !== $cached ) {
        // Guardem també la caché negativa (cadena buida)
        return (string) $cached;
    }

    $client = fcsd_sinergia_get_client();
    if ( is_wp_error( $client ) ) {
        // No fem cache d'això per poder reintentar més endavant
        return '';
    }

    // Escapem com a mínim cometes per no rebentar la query
    $safe_username = str_replace( array( "'", '"' ), array( "\\'", '' ), $username );

    $params = array(
        'module_name'   => 'Users',
        'query'         => "users.user_name = '" . $safe_username . "' AND users.deleted = 0",
        'order_by'      => '',
        'offset'        => 0,
        'select_fields' => array( 'id', 'user_name' ),
        'max_results'   => 1,
        'deleted'       => 0,
    );

    $result = $client->getEntryList( $params );

    $id = '';

    if ( is_object( $result ) && ! empty( $result->entry_list ) && is_array( $result->entry_list ) ) {
        $first = $result->entry_list[0] ?? null;
        if ( is_object( $first ) && ! empty( $first->id ) ) {
            $id = (string) $first->id;
        }
    } elseif ( is_array( $result ) && ! empty( $result['entry_list'] ) && is_array( $result['entry_list'] ) ) {
        $first = $result['entry_list'][0] ?? null;
        if ( is_array( $first ) && ! empty( $first['id'] ) ) {
            $id = (string) $first['id'];
        } elseif ( is_object( $first ) && ! empty( $first->id ) ) {
            $id = (string) $first->id;
        }
    }

    if ( '' !== $id ) {
        // Cache 7 dies
        set_transient( $cache_key, $id, DAY_IN_SECONDS * 7 );
        return $id;
    }

    // Cache negativa 1 dia per no martellejar l'API
    set_transient( $cache_key, '', DAY_IN_SECONDS );

    return '';
}

