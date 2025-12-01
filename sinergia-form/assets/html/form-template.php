<?php
/**
 * Template de formulari d'inscripció a esdeveniments (versió gran).
 *
 * Variables disponibles:
 * - $event_id            → ID d’esdeveniment Sinergia (GUID) del post actual.
 * - $assigned_user_id    → Usuari assignat per defecte.
 * - $schedule_slots      → (array) llista d’esdeveniments agrupats (si n’hi ha més d’un).
 *        Cada element:
 *        [
 *          'sinergia_id'      => '...', // event_id de Sinergia
 *          'assigned_user_id' => '...', // usuari assignat
 *          'label'            => '...', // text a mostrar
 *        ]
 * - $events_data_json    → JSON amb info extra (si cal).
 */

$has_multiple_slots = ! empty( $schedule_slots ) && is_array( $schedule_slots ) && count( $schedule_slots ) > 1;

// Valor per defecte dels ocults event_id i assigned_user_id
$default_event_id         = $event_id;
$default_assigned_user_id = $assigned_user_id;

// Si hi ha agrupació (schedule_slots), agafem el primer com a valor per defecte
if ( ! empty( $schedule_slots ) && is_array( $schedule_slots ) ) {
    $first_slot = reset( $schedule_slots );
    if ( ! empty( $first_slot['sinergia_id'] ) ) {
        $default_event_id = $first_slot['sinergia_id'];
    }
    if ( ! empty( $first_slot['assigned_user_id'] ) ) {
        $default_assigned_user_id = $first_slot['assigned_user_id'];
    }
}

// Camps requerits per Sinergia (tal qual al webform original)
$req_fields = array(
    'Contacts___first_name',
    'Contacts___last_name',
    'Contacts___email1',
    'Contacts___stic_identification_type_c',
);
$req_id_value = implode( ';', $req_fields ) . ';';

?>
<form action="https://fcsd.sinergiacrm.org/index.php?entryPoint=stic_Web_Forms_save"
      name="WebToLeadForm"
      method="POST"
      id="WebToLeadForm">

    <!-- Camps ocults que Sinergia necessita -->
    <input type="hidden" id="assigned_user_id" name="assigned_user_id"
       value="<?php echo esc_attr( $default_assigned_user_id ); ?>" />

    <input type="hidden" id="redirect_url" name="redirect_url"
           value="https://fcsd.org/ca/inscripcio-registrada-correctament/" />

    <input type="hidden" id="redirect_ko_url" name="redirect_ko_url"
           value="https://fcsd.org/ca/inscripcio-error/" />

    <input type="hidden" id="validate_identification_number"
           name="validate_identification_number" value="1" />

    <input type="hidden" id="assigned_user_id" name="assigned_user_id"
           value="<?php echo esc_attr( $default_assigned_user_id ); ?>" />

    <!-- Camps requerits segons Sinergia -->
    <input type="hidden" id="req_id" name="req_id"
           value="<?php echo esc_attr( $req_id_value ); ?>" />

    <input type="hidden" id="bool_id" name="bool_id" value="" />

    <input type="hidden" id="webFormClass" name="webFormClass"
           value="EventInscription" />

    <input type="hidden" id="stic_Payment_Commitments___payment_type"
           name="stic_Payment_Commitments___payment_type" value="" />

    <input type="hidden" id="stic_Payment_Commitments___periodicity"
           name="stic_Payment_Commitments___periodicity" value="punctual" />

    <input type="hidden" id="language" name="language" value="ca_ES" />

    <input type="hidden" id="defParams" name="defParams"
           value="%7B%22include_payment_commitment%22%3A0%2C%22include_organization%22%3A0%2C%22account_code_mandatory%22%3A0%2C%22include_registration%22%3A0%2C%22account_name_optional%22%3A0%2C%22email_template_id%22%3A%22%22%2C%22include_recaptcha%22%3A0%2C%22recaptcha_configKeys%22%3A%5B%5D%2C%22recaptcha_selected%22%3A%22%22%7D" />

    <input type="hidden" id="timeZone" name="timeZone" value="" />

    <table class="tableForm">
        <tr class="header">
            <td colspan="4"><h2>Inscriu-te</h2></td>
        </tr>

        <tr>
            <td colspan="4">&nbsp;</td>
        </tr>

        <!-- PERSONA -->

        <tr>
            <td class="column_25">
                <label id="lbl_Contacts___first_name" for="Contacts___first_name">
                    Nom:
                </label>
                <span id="lbl_Contacts___first_name_required" class="required">*</span>
            </td>
            <td class="column_25" id="td_Contacts___first_name">
                <input id="Contacts___first_name" name="Contacts___first_name"
                       type="text" />
            </td>
        </tr>

        <tr>
            <td class="column_25">
                <label id="lbl_Contacts___last_name" for="Contacts___last_name">
                    Cognoms:
                </label>
                <span id="lbl_Contacts___last_name_required" class="required">*</span>
            </td>
            <td class="column_25" id="td_Contacts___last_name">
                <input id="Contacts___last_name" name="Contacts___last_name"
                       type="text" />
            </td>
        </tr>

        <tr>
            <td class="column_25">
                <label id="lbl_Contacts___email1" for="Contacts___email1">
                    Adreça de correu electrònic:
                </label>
                <span id="lbl_Contacts___email1_required" class="required">*</span>
            </td>
            <td class="column_25" id="td_Contacts___email1">
                <input id="Contacts___email1" name="Contacts___email1"
                       type="text"
                       onchange="validateEmailAdd(this);" />
            </td>
        </tr>

        <tr>
            <td class="column_25" id="td_lbl_Contacts___stic_identification_type_c">
                <label id="lbl_Contacts___stic_identification_type_c"
                       for="Contacts___stic_identification_type_c">
                    Tipus d'identificació:
                </label>
                <span id="lbl_Contacts___stic_identification_type_c_required"
                      class="required">*</span>
            </td>
            <td class="column_25" id="td_Contacts___stic_identification_type_c">
                <select id="Contacts___stic_identification_type_c"
                        name="Contacts___stic_identification_type_c">
                    <option value=""></option>
                    <option value="nie">NIE</option>
                    <option value="nif">NIF</option>
                    <option value="passport">Passaport</option>
                    <option value="other">Altres</option>
                    <option value="perRevisar">Per revisar</option>
                    <option value="NoEnDisposa">No en disposa</option>
                </select>
            </td>
        </tr>

        <tr>
            <td class="column_25" id="td_lbl_Contacts___stic_identification_number_c">
                <label id="lbl_Contacts___stic_identification_number_c"
                       for="Contacts___stic_identification_number_c">
                    Número d'identificació: *
                </label>
            </td>
            <td class="column_25" id="td_Contacts___stic_identification_number_c">
                <input id="Contacts___stic_identification_number_c"
                       name="Contacts___stic_identification_number_c"
                       type="text" />
            </td>
        </tr>

        <tr>
            <td class="column_25" id="td_lbl_Contacts___phone_mobile">
                <label id="lbl_Contacts___phone_mobile"
                       for="Contacts___phone_mobile">
                    Mòbil:
                </label>
            </td>
            <td class="column_25" id="td_Contacts___phone_mobile">
                <input id="Contacts___phone_mobile" name="Contacts___phone_mobile"
                       type="text" />
            </td>
        </tr>

        <tr>
            <td class="column_25" id="td_lbl_Contacts___description">
                <label id="lbl_Contacts___description"
                       for="Contacts___description">
                    Observacions dades generals:
                </label>
            </td>
            <td class="column_25" id="td_Contacts___description">
                <textarea id="Contacts___description"
                          name="Contacts___description"></textarea>
            </td>
        </tr>

        <?php if ( $has_multiple_slots ) : ?>
            <!-- DESPLEGABLE DE HORARIS / ESDEVENIMENTS AGRUPATS -->
            <tr>
                <td class="column_25">
                    <label for="schedule_slot">
                        Horari / sessió:
                    </label>
                </td>
                <td class="column_25">
                    <select id="schedule_slot" name="schedule_slot" required>
                        <option value="">
                            <?php esc_html_e( 'Selecciona un horari', 'fcsd' ); ?>
                        </option>
                        <?php foreach ( $schedule_slots as $slot ) : ?>
                            <option
                                value="<?php echo esc_attr( $slot['sinergia_id'] ); ?>"
                                data-assigned-user-id="<?php echo esc_attr( $slot['assigned_user_id'] ); ?>"
                                <?php selected( $slot['sinergia_id'], $default_event_id ); ?>
                            >
                                <?php echo esc_html( $slot['label'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        <?php endif; ?>

        <!-- POLÍTICA DE PRIVACITAT (només web, Sinergia l'ignora) -->
        <tr>
            <td colspan="4">
                <label>
                    <input type="checkbox" id="accept_policy" name="accept_policy" />
                    Accepto la
                    <a href="/politica-de-privacitat" target="_blank">
                        política de privacitat
                    </a> *
                </label>
            </td>
        </tr>

        <tr>
            <td>&nbsp;</td>
            <td>
                <input class="button" type="button"
                       onclick="submitForm(this.form);"
                       name="Submit"
                       value="Envia" />
            </td>
        </tr>
    </table>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Per si vols usar-ho des d'altres scripts
    window.eventsData = <?php echo ! empty( $events_data_json ) ? $events_data_json : '[]'; ?>;
});
</script>
