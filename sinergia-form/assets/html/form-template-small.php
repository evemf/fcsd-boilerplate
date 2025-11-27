<form action="https://fcsd.sinergiacrm.org/index.php?entryPoint=stic_Web_Forms_save" name="WebToLeadForm" method="POST" id="WebToLeadForm">
    <input type="hidden" id="event_id" name="event_id" value="<?php echo esc_attr($event_id); ?>" />
    <input type="hidden" id="redirect_url" name="redirect_url" value="https://fcsd.org/ca/inscripcio-registrada-correctament/" />
    <input type="hidden" id="redirect_ko_url" name="redirect_ko_url" value="https://fcsd.org/ca/inscripcio-error/" />
    <input type="hidden" id="validate_identification_number" name="validate_identification_number" value="1" />
    <input type="hidden" id="assigned_user_id" name="assigned_user_id" value="<?php echo esc_attr($assigned_user_id); ?>" />
    <input type="hidden" id="req_id" name="req_id" value="Contacts___first_name;Contacts___last_name;Contacts___email1;Contacts___stic_identification_type_c;" />
    <input type="hidden" id="bool_id" name="bool_id" value="" />
    <input type="hidden" id="webFormClass" name="webFormClass" value="EventInscription" />
    <input type="hidden" id="stic_Payment_Commitments___payment_type" name="stic_Payment_Commitments___payment_type" value="" />
    <input type="hidden" id="stic_Payment_Commitments___periodicity" name="stic_Payment_Commitments___periodicity" value="punctual" />
    <input type="hidden" id="language" name="language" value="ca_ES" />
    <input type="hidden" id="defParams" name="defParams" value="%7B%22include_payment_commitment%22%3A0%2C%22include_organization%22%3A0%2C%22account_code_mandatory%22%3A0%2C%22include_registration%22%3A0%2C%22account_name_optional%22%3A0%2C%22email_template_id%22%3A%22%22%2C%22include_recaptcha%22%3A0%2C%22recaptcha_configKeys%22%3A%5B%5D%2C%22recaptcha_selected%22%3A%22%22%7D" />
    <input type="hidden" id="timeZone" name="timeZone" value="" />

    <table class="tableForm">
        <tr><td colspan="4"><h2>Inscriu-te</h2></td></tr>
        <tr>
            <td class="column_25"><label for="Contacts___first_name">Nom:<span class="required">*</span></label></td>
            <td colspan="4"><input id="Contacts___first_name" name="Contacts___first_name" type="text" required /></td>
            <td colspan="2"><div id="error_Contacts___first_name" class="error-message" style="display:none;color:red;">Camp obligatori</div></td>
        </tr>
        <tr>
            <td class="column_25"><label for="Contacts___last_name">Cognoms:<span class="required">*</span></label></td>
            <td colspan="4"><input id="Contacts___last_name" name="Contacts___last_name" type="text" required /></td>
            <td colspan="2"><div id="error_Contacts___last_name" class="error-message" style="display:none;color:red;">Camp obligatori</div></td>
        </tr>
        <tr>
            <td class="column_25"><label for="Contacts___email1">Adreça de correu electrònic:<span class="required">*</span></label></td>
            <td colspan="4"><input id="Contacts___email1" name="Contacts___email1" type="email" required /></td>
            <td colspan="2"><div id="error_Contacts___email1" class="error-message" style="display:none;color:red;">Email no vàlid</div></td>
        </tr>
        <tr>
            <td class="column_25"><label for="Contacts___stic_identification_type_c">Tipus d'identificació:<span class="required">*</span></label></td>
            <td colspan="4">
                <select id="Contacts___stic_identification_type_c" name="Contacts___stic_identification_type_c" required>
                    <option value=""></option>
                    <option value="nie">NIE</option>
                    <option value="nif">NIF</option>
                    <option value="passport">Passaport</option>
                    <option value="other">Altres</option>
                    <option value="perRevisar">Per revisar</option>
                    <option value="NoEnDisposa">No en disposa</option>
                </select>
            </td>
            <td colspan="2"><div id="error_Contacts___stic_identification_type_c" class="error-message" style="display:none;color:red;">Camp obligatori</div></td>
        </tr>
        <tr>
            <td class="column_25"><label for="Contacts___stic_identification_number_c">Nº d'identificació:<span class="required">*</span></label></td>
            <td colspan="4"><input id="Contacts___stic_identification_number_c" name="Contacts___stic_identification_number_c" type="text" required /></td>
            <td colspan="2"><div id="error_Contacts___stic_identification_number_c" class="error-message" style="display:none;color:red;">Número d'identificació no vàlid</div></td>
        </tr>
        <tr>
            <td colspan="6">
                <label>
                    <input type="checkbox" id="accept_policy" name="accept_policy" required />
                    Accepto la <a href="/politica-de-privacitat" target="_blank">política de privacitat</a> *
                </label>
            </td>
        </tr>
        <tr><td>&nbsp;</td><td><input class="button" type="button" onclick="submitForm(this.form)" value="Envia" /></td></tr>
    </table>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const eventsData = /*__EVENTS_DATA__*/;  
    const eventIdField = document.getElementById('event_id');
    const assignedUserIdField = document.getElementById('assigned_user_id');
    
    if (eventIdField && eventsData && eventsData.length > 0) {
        eventIdField.value = eventsData[0].id || '';
    }
    
    if (assignedUserIdField && eventsData && eventsData.length > 0) {
        assignedUserIdField.value = eventsData[0].assigned_user_id || '';
    } 
});
</script>