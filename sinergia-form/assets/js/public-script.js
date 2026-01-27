// -----------------------------------------------------------------------------
// Política de privacidad: añadimos el checkbox como requerido a nivel JS
// -----------------------------------------------------------------------------
function addPrivacyPolicyToRequired() {
    var reqId = document.getElementById("req_id");
    if (reqId && reqId.value.indexOf("accept_policy") === -1) {
        reqId.value += "accept_policy;";
    }
}

// Por si algún script externo la espera en window
window.addPrivacyPolicyToRequired = addPrivacyPolicyToRequired;

// -----------------------------------------------------------------------------
// Carga principal
// -----------------------------------------------------------------------------
document.addEventListener("DOMContentLoaded", function () {
    if (typeof jQuery !== "undefined") {
        initializeScript(jQuery);
        addPrivacyPolicyToRequired();
    } else {
        // Si por lo que sea no está jQuery (raro en WP), lo cargamos desde CDN
        var script = document.createElement("script");
        script.src = "https://code.jquery.com/jquery-3.6.0.min.js";
        script.onload = function () {
            if (typeof jQuery !== "undefined") {
                initializeScript(jQuery);
                addPrivacyPolicyToRequired();
            }
        };
        document.head.appendChild(script);
    }
});

// -----------------------------------------------------------------------------
// Lógica de los formularios (adaptación del script original de Sinergia)
// -----------------------------------------------------------------------------
function initializeScript($) {
    var stic_Web_Forms_LBL_PROVIDE_WEB_FORM_FIELDS = "Ompliu els camps obligatoris";
    var stic_Web_Forms_LBL_INVALID_FORMAT = "Comproveu el format del camp";
    var stic_Web_Forms_LBL_SERVER_CONNECTION_ERROR = "Ha fallat la connexió amb el servidor";
    var stic_Web_Forms_LBL_SIZE_FILE_EXCEED = "La mida del fitxer no pot ser superior a ";
    var stic_Web_Forms_LBL_SUM_SIZE_FILES_EXCEED = "La suma de les mides dels fitxers no pot ser superior a ";
    var APP_LBL_REQUIRED_SYMBOL = "*";
    var APP_DATE_FORMAT = "%d/%m/%Y";

    function changeVisibility(field, visibility) {
        var o_td = document.getElementById("td_" + field);
        var o_td_lbl = document.getElementById("td_lbl_" + field);
        if (o_td) o_td.style.display = visibility;
        if (o_td_lbl) o_td_lbl.style.display = visibility;
    }

    function showField(field) { changeVisibility(field, "table-cell"); }
    function hideField(field) { changeVisibility(field, "none"); }

    function addRequired(field) {
        var reqs = document.getElementById("req_id").value;
        if (reqs.indexOf(field + ";") === -1) {
            document.getElementById("req_id").value += field + ";";
        }

        var requiredLabel = document.getElementById("lbl_" + field + "_required");
        if (!requiredLabel) {
            var rlParent = document.getElementById("td_lbl_" + field);
            if (rlParent) {
                var newLabel = document.createElement("span");
                newLabel.id = "lbl_" + field + "_required";
                newLabel.className = "required";
                newLabel.style.color = "rgb(255, 0, 0)";
                newLabel.innerText = APP_LBL_REQUIRED_SYMBOL;
                rlParent.appendChild(newLabel);
            }
        }
    }

    function removeRequired(field) {
        var reqs = document.getElementById("req_id").value;
        document.getElementById("req_id").value = reqs.replace(field + ";", "");
        var requiredLabel = document.getElementById("lbl_" + field + "_required");
        if (requiredLabel && requiredLabel.parentNode) {
            requiredLabel.parentNode.removeChild(requiredLabel);
        }
    }

    function checkFields() {
        if (!validateRequired() || !validateNifCif() || !validateMails() || !validateDates()) {
            return false;
        }

        var boolHidden = document.getElementById("bool_id");
        if (boolHidden && boolHidden.value.length) {
            var bools = boolHidden.value.substring(0, boolHidden.value.lastIndexOf(";"));
            var boolFields = bools.split(";");
            var nbrFields = boolFields.length;
            for (var i = 0; i < nbrFields; i++) {
                var element = document.getElementById(boolFields[i]);
                if (element) {
                    element.value = (element.value === "on" ? 1 : 0);
                }
            }
        }
        return true;
    }

    function validateDates() {
        var elements = $.find("input[type=text].date_input");
        if (elements && elements.length > 0) {
            for (var i = 0; i < elements.length; i++) {
                if (elements[i].value && !validateDate(elements[i].value)) {
                    var label = document.getElementById("lbl_" + elements[i].id);
                    alert(
                        stic_Web_Forms_LBL_INVALID_FORMAT +
                            ": " +
                            label.textContent.trim().replace(/:$/, "")
                    );
                    selectTextInput(elements[i]);
                    return false;
                }
            }
        }
        return true;
    }

    function validateDate(date) {
        var number = /\d+/g;
        var numbers = [];
        var match = number.exec(date);

        while (match != null) {
            numbers.push(match[0]);
            match = number.exec(date);
        }

        if (numbers.length !== 3) {
            return false;
        }

        var format = /\%Y|\%m|\%d/g;
        var fields = [];
        match = format.exec(APP_DATE_FORMAT);
        while (match != null) {
            fields.push(match[0]);
            match = format.exec(APP_DATE_FORMAT);
        }

        var idxFields = [];
        for (var i = 0; i < fields.length; i++) {
            idxFields[fields[i].replace("%", "")] = i;
        }

        var day = numbers[idxFields.d];
        var month = numbers[idxFields.m];
        var year = numbers[idxFields.Y];

        if (month.length !== 2 || day.length !== 2 || year.length !== 4) {
            return false;
        }

        if (date.replace(number, "") !== APP_DATE_FORMAT.replace(format, "")) {
            return false;
        }

        day = parseInt(day, 10);
        month = parseInt(month, 10);
        year = parseInt(year, 10);

        if (month > 12 || month < 1) return false;
        if (day < 1) return false;

        switch (month) {
            case 1: case 3: case 5: case 7:
            case 8: case 10: case 12:
                return day <= 31;
            case 2:
                return day <= 29;
            case 4: case 6: case 9: case 11:
                return day <= 30;
        }
    }

    function validateRequired() {
        var reqHidden = document.getElementById("req_id");
        if (!reqHidden) return true;

        var reqs = reqHidden.value;
        if (!reqs.length) return true;

        reqs = reqs.substring(0, reqs.lastIndexOf(";"));
        var reqFields = reqs.split(";");
        var nbrFields = reqFields.length;

        for (var i = 0; i < nbrFields; i++) {
            var element = document.getElementById(reqFields[i]);
            var lbl_element;
            var error = 0;

            if (element != null) {
                lbl_element = "#lbl_" + element.id;
                $(lbl_element).removeClass("current-required-field");

                switch (element.type) {
                    case "checkbox":
                        if (!element.checked) error = 1;
                        break;
                    case "select-one":
                        if (element.selectedIndex <= 0) error = 1;
                        break;
                    case "select-multiple":
                        var numOptionsSelected = $("select[id='input_selectmultiple'] option:selected").length;
                        if (element.selectedIndex <= 0 && numOptionsSelected <= 1) {
                            error = 1;
                        }
                        break;
                    default:
                        if (!element.value.length) error = 1;
                }
            } else {
                error = 1;
                var options = document.getElementsByName(reqFields[i]);
                if (!options.length) continue;

                lbl_element = "#lbl_" + options[0].name;
                $(lbl_element).removeClass("current-required-field");

                options.forEach(function (option) {
                    if (option.checked) {
                        error = 0;
                    }
                });
            }

            if (error) {
                alert(stic_Web_Forms_LBL_PROVIDE_WEB_FORM_FIELDS);
                $(lbl_element).addClass("current-required-field");
                if (element) {
                    selectTextInput(element);
                }
                return false;
            }
        }

        return true;
    }

    function validateMails() {
        var fields = [
            "Contacts___email1",
            "Contacts___email2",
            "Accounts___email1",
            "Accounts___email2"
        ];
        var ret = true;
        for (var i = 0; i < fields.length && ret; i++) {
            var emailInput = document.getElementById(fields[i]);
            if (emailInput != null) {
                ret = validateEmailAdd(emailInput);
            }
        }
        return ret;
    }

    function validateEmailAdd(obj) {
        if (!obj) return true;

        obj.value = obj.value.trim();
        if (obj.value.length > 0 && !isValidEmail(obj.value)) {
            var label = document.getElementById("lbl_" + obj.id);
            var labelText = label
                ? label.textContent.replace(/: +$/, "")
                : "Email";
            alert(stic_Web_Forms_LBL_INVALID_FORMAT + ": " + labelText);
            selectTextInput(obj);
            return false;
        }
        return true;
    }

    function isValidEmail(email) {
        var re =
            /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }

    function validateNifCif() {
        var validateIdentificationNumber = document.getElementById("validate_identification_number");

        if (validateIdentificationNumber && validateIdentificationNumber.value === "0") {
            return true;
        }

        var identificationType = $("#Contacts___stic_identification_type_c").val();
        if (identificationType == null || identificationType === "nif" || identificationType === "nie") {
            var nif = document.getElementById("Contacts___stic_identification_number_c");
            if (nif && nif.value && !isValidDNI(nif.value)) {
                var label = " ";
                if (nif.labels && nif.labels[0]) {
                    label +=
                        nif.labels[0].textContent.slice(-1) === ":"
                            ? nif.labels[0].textContent.substring(
                                  0,
                                  nif.labels[0].textContent.length - 1
                              )
                            : nif.labels[0].textContent;
                }
                alert(stic_Web_Forms_LBL_INVALID_FORMAT + label + ".");
                nif.focus();
                return false;
            }
        }

        var cif = document.getElementById("Accounts___stic_identification_number_c");
        if (cif && cif.value && !isValidCif(cif.value)) {
            var label2 = " ";
            if (cif.labels && cif.labels[0]) {
                label2 +=
                    cif.labels[0].textContent.slice(-1) === ":"
                        ? cif.labels[0].textContent.substring(
                              0,
                              cif.labels[0].textContent.length - 1
                          )
                        : cif.labels[0].textContent;
            }
            alert(stic_Web_Forms_LBL_INVALID_FORMAT + label2 + ".");
            cif.focus();
            return false;
        }

        return true;
    }

    function isNumberKey(evt) {
        var charCode = evt.which ? evt.which : event.keyCode;
        if (charCode !== 46 && charCode > 31 && (charCode < 48 || charCode > 57)) {
            return false;
        }

        if (charCode === 46) {
            var evento = evt || event;
            var dots = evento.currentTarget.value.match(/\./g);
            if (dots && dots.length > 0) {
                return false;
            }
            if (evento.currentTarget.value.length === 0) {
                evento.currentTarget.value = "0";
            }
        }

        return true;
    }

    function formatCurrency(input) {
        var value = Number(input.value);
        if (!isNaN(value)) {
            input.value = value.toFixed(2);
        }
    }

    function isValidCif(cif) {
        cif = cif.toUpperCase();
        var cifRegEx1 = /^[ABEH][0-9]{8}/i;
        var cifRegEx2 = /^[KPQS][0-9]{7}[A-J]/i;
        var cifRegEx3 = /^[CDFGJLMNRUVW][0-9]{7}[0-9A-J]/i;

        if (cif.match(cifRegEx1) || cif.match(cifRegEx2) || cif.match(cifRegEx3)) {
            var control = cif.charAt(cif.length - 1);
            var sum_A = 0;
            var sum_B = 0;
            for (var i = 1; i < 8; i++) {
                if (i % 2 === 0) {
                    sum_A += parseInt(cif.charAt(i), 10);
                } else {
                    var t = (parseInt(cif.charAt(i), 10) * 2).toString();
                    var p = 0;
                    for (var j = 0; j < t.length; j++) {
                        p += parseInt(t.charAt(j), 10);
                    }
                    sum_B += p;
                }
            }

            var sum_C = parseInt(sum_A + sum_B, 10) + "";
            var sum_D = (10 - parseInt(sum_C.charAt(sum_C.length - 1), 10)) % 10;
            var letters = "JABCDEFGHI";

            if (control >= "0" && control <= "9") {
                return control === String(sum_D);
            }
            return control.toUpperCase() === letters[sum_D];
        }
        return false;
    }

    function isValidDNI(dni) {
        var number;
        var lett;
        var letter;
        var regular_expression_dni = /^[XYZ]?\d{5,8}[A-Z]$/;
        dni = dni.toUpperCase();

        if (regular_expression_dni.test(dni) === true) {
            number = dni.substr(0, dni.length - 1);
            number = number.replace("X", 0);
            number = number.replace("Y", 1);
            number = number.replace("Z", 2);
            lett = dni.substr(dni.length - 1, 1);
            number = number % 23;

            letter = "TRWAGMYFPDXBNJZSQVHLCKET";
            letter = letter.substring(number, number + 1);

            return letter === lett;
        }
        return false;
    }

    function setSelectValue(select, value) {
        for (var i = 0; i < select.options.length; i++) {
            select.options[i].selected = (select.options[i].value === value);
        }
        select.prev_value = select.options[select.selectedIndex].value;
    }

    function selectTextInput(input) {
        if (typeof input.setSelectionRange !== "undefined") {
            input.setSelectionRange(0, input.value.length);
        }
        input.focus();
    }

    // En tu formulario no hay campos .document, así que simplificamos:
    var items = null;
    var formSizeArray = [];

    function getConfigVariables(data) {
        items = data;
        if (!items || (!items.uploadMaxFilesize && !items.postMaxSize)) {
            console.log(stic_Web_Forms_LBL_SERVER_CONNECTION_ERROR);
        }
    }

    function checkFormSize() {
        // Sin adjuntos: siempre OK
        return true;
    }

    // ---------------------------------------------------------------------
    // schedule_slot (agrupación de eventos en WP)
    // ---------------------------------------------------------------------
    function updateEventFields(event) {
        var selectedOption = event.target.selectedOptions[0];
        if (!selectedOption) return;

        var eventId = selectedOption.value || "";
        var assignedUserId = selectedOption.getAttribute("data-assigned-user-id") || "";

        var eventIdInput = document.getElementById("event_id");
        var assignedUserInput = document.getElementById("assigned_user_id");

        if (eventIdInput) eventIdInput.value = eventId;
        if (assignedUserInput) assignedUserInput.value = assignedUserId;
    }

    (function initScheduleSlot() {
        var scheduleSlot = document.getElementById("schedule_slot");
        if (!scheduleSlot) return;

        scheduleSlot.removeAttribute("onchange");
        scheduleSlot.addEventListener("change", updateEventFields);

        if (scheduleSlot.selectedIndex > 0) {
            scheduleSlot.dispatchEvent(new Event("change"));
        }
    })();

    // ---------------------------------------------------------------------
    // Política de privacidad
    // ---------------------------------------------------------------------
    function validatePrivacyPolicy() {
        var privacyCheckbox = document.getElementById("accept_policy");
        if (!privacyCheckbox || !privacyCheckbox.checked) {
            alert("Has d'acceptar la política de privacitat per poder enviar el formulari.");
            if (privacyCheckbox) privacyCheckbox.focus();
            return false;
        }
        return true;
    }

    // ---------------------------------------------------------------------
    // Envío del formulario
    // ---------------------------------------------------------------------
    var formHasAlreadyBeenSent = false;

    function submitForm(form) {
        if (!validatePrivacyPolicy()) {
            return false;
        }
        if (checkFields() && checkFormSize()) {
            if (typeof window.validateCaptchaAndSubmit !== "undefined") {
                window.validateCaptchaAndSubmit();
            } else if (!formHasAlreadyBeenSent) {
                formHasAlreadyBeenSent = true;
                form.submit();
            } else {
                console.log("Form is locked because it has already been sent.");
            }
        }
        return false;
    }

    // ---------------------------------------------------------------------
    // Exponer funciones globalmente (para inline handlers y otros scripts)
    // ---------------------------------------------------------------------
    window.showField = showField;
    window.hideField = hideField;
    window.addRequired = addRequired;
    window.removeRequired = removeRequired;

    window.checkFields = checkFields;
    window.validateRequired = validateRequired;
    window.validateMails = validateMails;
    window.validateNifCif = validateNifCif;
    window.validateDates = validateDates;

    window.validateEmailAdd = validateEmailAdd;
    window.isNumberKey = isNumberKey;
    window.formatCurrency = formatCurrency;
    window.setSelectValue = setSelectValue;
    window.getConfigVariables = getConfigVariables;
    window.checkFormSize = checkFormSize;
    window.updateEventFields = updateEventFields;
    window.validatePrivacyPolicy = validatePrivacyPolicy;
    window.submitForm = submitForm;

    // timeZone como hace Sinergia
    $("#timeZone").val(Intl.DateTimeFormat().resolvedOptions().timeZone);
}
