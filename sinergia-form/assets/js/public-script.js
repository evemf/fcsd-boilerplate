document.addEventListener('DOMContentLoaded', function () {
    // Comprobar si jQuery ya está cargado (por ejemplo, por WordPress)
    if (typeof jQuery !== 'undefined') {
        console.log("jQuery ya está cargado, usando la versión existente.");
        initializeScript(jQuery);
        addPrivacyPolicyToRequired();
    } else {
        // Scripts externs
        loadExternalScript("https://fcsd.sinergiacrm.org/cache/include/javascript/sugar_grp1_jquery.js?v=c_0r5JnMRCy_X6dYTHX8pg", function () {
            // jQuery disponible
            if (typeof jQuery !== 'undefined') {
                console.log("jQuery cargado correctamente.");
                var $ = jQuery.noConflict(true);
                
                // YUI Sinergia
                loadExternalScript("https://fcsd.sinergiacrm.org/cache/include/javascript/sugar_grp1_yui.js?v=c_0r5JnMRCy_X6dYTHX8pg", function () {
                    loadExternalScript("https://fcsd.sinergiacrm.org/cache/include/javascript/sugar_grp1.js?v=c_0r5JnMRCy_X6dYTHX8pg", function () {
                        initializeScript($);
                    });
                });
            } else {
                console.error("Error al cargar jQuery");
                // CDN com alternativa
                loadExternalScript("https://code.jquery.com/jquery-3.6.0.min.js", function() {
                    if (typeof jQuery !== 'undefined') {
                        console.log("jQuery cargado desde CDN alternativo.");
                        initializeScript(jQuery);
                    } else {
                        console.error("No se pudo cargar jQuery de ninguna fuente.");
                    }
                });
            }
        });
    }
    
    // Carregar scripts
    function loadExternalScript(src, callback) {
        var script = document.createElement("script");
        script.type = "text/javascript";
        script.src = src;
        script.onload = function () {
            if (typeof callback === "function") callback();
        };
        script.onerror = function() {
            console.error("Error al cargar el script:", src);
            if (typeof callback === "function") callback(); // Llamar al callback incluso en error
        };
        document.head.appendChild(script);
    }
    
    function initializeScript($) {
                window.eventsData = window.eventsData || [];
                
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
                    if (o_td) {
                        o_td.style.display = visibility;
                    }

                    if (o_td_lbl) {
                        o_td_lbl.style.display = visibility;
                    }
                }

                function showField(field) {
                    changeVisibility(field, "table-cell");
                }

                function hideField(field) {
                    changeVisibility(field, "none");
                }

                function addRequired(field) {
                    var reqs = document.getElementById("req_id").value;
                    if (-1 == reqs.search(field + ";")) {
                        document.getElementById("req_id").value += field + ";";
                    }

                    var requiredLabel = document.getElementById("lbl_" + field + "_required");
                    if (!requiredLabel) {
                        var rlParent = document.getElementById("td_lbl_" + field);
                        if (rlParent) {
                            var newLabel = document.createElement("span");
                            newLabel.id = "lbl_" + field + "_required";
                            newLabel.className = "required";
                            newLabel.style.color = "color: rgb(255, 0, 0);";
                            newLabel.innerText = APP_LBL_REQUIRED_SYMBOL;
                            rlParent.appendChild(newLabel);
                        }
                    }
                }

                function removeRequired(field) {
                    var reqs = document.getElementById("req_id").value;
                    document.getElementById("req_id").value = reqs.replace(field + ";", "");
                    var requiredLabel = document.getElementById("lbl_" + field + "_required");
                    if (requiredLabel) {
                        requiredLabel.parentNode.removeChild(requiredLabel);
                    }
                }

                function checkFields() {
                    if (!validateRequired() || !validateNifCif() || !validateMails() || !validateDates()) {
                        return false;
                    } else {
                        var boolHidden = document.getElementById("bool_id");
                        if (boolHidden != null) {
                            var reqs = boolHidden.value;
                            if (reqs.length) {
                                var bools = reqs.substring(0, reqs.lastIndexOf(";"));
                                var boolFields = new Array();
                                var boolFields = bools.split(";");
                                var nbrFields = boolFields.length;
                                for (var i = 0; i < nbrFields; i++) {
                                    var element = document.getElementById(boolFields[i]);
                                    element.value == (element.value == "on" ? 1 : 0);
                                }
                            }
                        }
                        return true;
                    }
                }

                function validateDates() {
                    var elements = $.find("input[type=text].date_input");
                    if (elements && elements.length > 0) {
                        for (var i = 0; i < elements.length; i++) {
                            if (elements[i].value && !validateDate(elements[i].value)) {
                                var label = document.getElementById("lbl_" + elements[i].id);
                                alert(stic_Web_Forms_LBL_INVALID_FORMAT + ": " + label.textContent.trim().replace(/:$/, ""));
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

                    if (numbers.length != 3) {
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

                    if (month.length != 2 || day.length != 2 || year.length != 4) {
                        return false;
                    }

                    if (date.replace(number, "") != APP_DATE_FORMAT.replace(format, "")) {
                        return false;
                    }
                    day = parseInt(day);
                    month = parseInt(month);
                    year = parseInt(year);

                    if (month > 12 || month < 1) {
                        return false;
                    } else {
                        if (day < 1) {
                            return false;
                        }

                        switch (month) {
                            case 1:
                            case 3:
                            case 5:
                            case 7:
                            case 8:
                            case 10:
                            case 12:
                                return day <= 31;
                            case 2:
                                return day <= 29;
                            case 4:
                            case 6:
                            case 9:
                            case 11:
                                return day <= 30;
                        }
                    }
                }

                function validateRequired() {
                    var reqHidden = document.getElementById("req_id");
                    if (reqHidden != null) {
                        var reqs = reqHidden.value;
                        if (reqs.length) {
                            reqs = reqs.substring(0, reqs.lastIndexOf(";"));
                            var reqFields = new Array();
                            var reqFields = reqs.split(";");
                            nbrFields = reqFields.length;

                            for (var i = 0; i < nbrFields; i++) {
                                var lbl_element;
                                var element = document.getElementById(reqFields[i]);
                                var error = 0;
                                if (element != null) {
                                    lbl_element = "#lbl_" + element.id;
                                    $(lbl_element).removeClass("current-required-field");
                                    switch (element.type) {
                                        case "checkbox":
                                            if (element.checked == 0) {
                                                error = 1;
                                            }
                                            break;

                                        case "select-one":
                                            if (element.selectedIndex <= 0) {
                                                error = 1;
                                            }
                                            break;

                                        case "select-multiple":
                                            let numOptionsSelected = $("select[id='input_selectmultiple'] option:selected").length;
                                            if (element.selectedIndex <= 0 && numOptionsSelected <= 1) {
                                                error = 1;
                                            }
                                            break;

                                        default:
                                            if (element.value.length <= 0) {
                                                error = 1;
                                            }
                                    }
                                } else {
                                    error = 1;
                                    var options = document.getElementsByName(reqFields[i]);

                                    lbl_element = "#lbl_" + options[0].name;
                                    $(lbl_element).removeClass("current-required-field");

                                    options.forEach(function(option) {
                                        if (option.checked) {
                                            error = 0;
                                        }
                                    });
                                }

                                if (error) {
                                    alert(stic_Web_Forms_LBL_PROVIDE_WEB_FORM_FIELDS);
                                    $(lbl_element).addClass("current-required-field");
                                    selectTextInput(element);
                                    return false;
                                }
                            }
                        }
                    }
                    return true;
                }

                function validateMails() {
                    var fields = ["Contacts___email1", "Contacts___email2", "Accounts___email1", "Accounts___email2"];
                    var ret = true;
                    for (var i = 0; i < fields.length && ret; i++) {
                        emailInput = document.getElementById(fields[i]);
                        if (emailInput != undefined) {
                            ret = validateEmailAdd(emailInput);
                        }
                    }
                    return ret;
                }

                function validateEmailAdd(obj) {
                    if (!obj) return true;

                    obj.value = obj.value.trim();

                    if (obj.value.length > 0 && !isValidEmail(obj.value)) {
                        let label = document.querySelector('label[for="' + obj.id + '"]');
                        let labelText = label ? label.textContent.replace(/[:\*]+$/, "").trim() : "Email";
                        alert(stic_Web_Forms_LBL_INVALID_FORMAT + ": " + labelText);
                        selectTextInput(obj);
                        return false;
                    }
                    return true;
                }

                function isValidEmail(email) {
                    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                    return re.test(String(email).toLowerCase());
                }

                function validateNifCif() {
                    var validateIdentificationNumber = document.getElementById("validate_identification_number");

                    if (validateIdentificationNumber && validateIdentificationNumber.value == "0") {
                        console.log("Identification number validation is not required.");
                        return true;
                    }

                    var identificationType = $("#Contacts___stic_identification_type_c").val();
                    if (identificationType == null || identificationType == "nif" || identificationType == "nie") {
                        var nif = document.getElementById("Contacts___stic_identification_number_c");
                        if (nif && nif.value && !isValidDNI(nif.value)) {
                            label = " ";
                            if (nif.labels && nif.labels[0]) {
                                label += (nif.labels[0].textContent.slice(-1) == ":" ? nif.labels[0].textContent.substring(0, nif.labels[0].textContent.length - 1) : nif.labels[0].textContent);
                            }
                            alert(stic_Web_Forms_LBL_INVALID_FORMAT + label + ".");
                            nif.focus();
                            return false;
                        }
                    }

                    var cif = document.getElementById("Accounts___stic_identification_number_c");
                    if (cif && cif.value && !isValidCif(cif.value)) {
                        label = " ";
                        if (cif.labels && cif.labels[0]) {
                            label += (cif.labels[0].textContent.slice(-1) == ":" ? cif.labels[0].textContent.substring(0, cif.labels[0].textContent.length - 1) : cif.labels[0].textContent);
                        }
                        alert(stic_Web_Forms_LBL_INVALID_FORMAT + label + ".");
                        cif.focus();
                        return false;
                    }

                    return true;
                }

                function isNumberKey(evt) {
                    var charCode = evt.which ? evt.which : event.keyCode;
                    if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57)) {
                        return false;
                    }

                    if (charCode == 46) {
                        var evento = evt || event;
                        var dots = evento.currentTarget.value.match(/\./g); // If there is already a point, you cannot add another
                        if (dots && dots.length > 0) {
                            return false;
                        }
                        if (evento.currentTarget.value.length == 0) {
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
                    cifRegEx1 = /^[ABEH][0-9]{8}/i;
                    cifRegEx2 = /^[KPQS][0-9]{7}[A-J]/i;
                    cifRegEx3 = /^[CDFGJLMNRUVW][0-9]{7}[0-9A-J]/i;

                    if (cif.match(cifRegEx1) || cif.match(cifRegEx2) || cif.match(cifRegEx3)) {
                        control = cif.charAt(cif.length - 1);
                        sum_A = 0;
                        sum_B = 0;
                        for (i = 1; i < 8; i++) {
                            if (i % 2 == 0) {
                                sum_A += parseInt(cif.charAt(i));
                            } else {
                                t = (parseInt(cif.charAt(i)) * 2).toString();
                                p = 0;
                                for (j = 0; j < t.length; j++) {
                                    p += parseInt(t.charAt(j));
                                }
                                sum_B += p;
                            }
                        }

                        sum_C = parseInt(sum_A + sum_B) + ""; // Así se convierte en cadena
                        sum_D = (10 - parseInt(sum_C.charAt(sum_C.length - 1))) % 10;
                        letters = "JABCDEFGHI";

                        if (control >= "0" && control <= "9") {
                            return control == sum_D;
                        } else {
                            return control.toUpperCase() == letters[sum_D];
                        }
                    } else {
                        return false;
                    }
                }

                function isValidDNI(dni) {
                    var number, lett, letter;
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

                        return letter == lett;
                    } else {
                        return false;
                    }
                }

                function setSelectValue(select, value) {
                    for (var i = 0; i < select.options.length; i++) {
                        if (select.options[i].value == value) {
                            select.options[i].selected = true;
                        } else {
                            select.options[i].selected = false;
                        }
                    }
                    select.prev_value = select.options[select.selectedIndex].value;
                }


                function selectTextInput(input) {
                    if (typeof input.setSelectionRange != "undefined") {
                        input.setSelectionRange(0, input.value.length);
                    }
                    input.focus();
                }

                var items = {
                    uploadMaxFilesize: "10MB",
                    uploadMaxFilesizeBytes: 10 * 1024 * 1024,
                    postMaxSize: "20MB",
                    postMaxSizeBytes: 20 * 1024 * 1024 
                };
                var formSizeArray = [];

                try {
                    var $form = $("form");
                    if ($form.length > 0 && $form.attr("action")) {
                        var actionUrl = $form.attr("action");
                        var url = "";
                        
                        if (actionUrl.indexOf("=") > -1) {
                            url = actionUrl.split("=")[0] + "=stic_Web_Forms_attachment_limits_response";
                        } else {
                            url = actionUrl + (actionUrl.indexOf("?") > -1 ? "&" : "?") + "action=stic_Web_Forms_attachment_limits_response";
                        }
                        
                        $.ajax({
                            url: url,
                            dataType: "jsonp",
                            timeout: 5000,
                            success: function(data) {
                                getConfigVariables(data);
                            },
                            error: function() {
                                console.log("No se pudo obtener la configuración de límites del servidor. Usando valores predeterminados.");
                            }
                        });
                    }
                } catch (e) {
                    console.error("Error al intentar configurar los límites de archivos:", e);
                }

                function getConfigVariables(data) {
                    if (data && (data.uploadMaxFilesize || data.postMaxSize)) {
                        items = data;
                    } else {
                        console.log("Datos de configuración incompletos. Usando valores predeterminados.");
                    }
                }

                $(".document").change(function() {
                    try {
                        var numInput = this.id.substring(this.id.length - 1, this.id.length);
                        var errorZone = "#error_zone_" + numInput;
                        
                        if (this.files && this.files[0]) {
                            var fileName = this.files[0].name;
                            var fileSize = this.files[0].size;
                            
                            // Asegurarnos de que items existe y tiene la propiedad uploadMaxFilesizeBytes
                            if (items && items.uploadMaxFilesizeBytes && fileSize > items.uploadMaxFilesizeBytes) {
                                $(errorZone).html("<span>" + stic_Web_Forms_LBL_SIZE_FILE_EXCEED + items.uploadMaxFilesize + "B </span>");
                            } else {
                                $(errorZone).html("");
                            }
                            
                            // Guardar tamaño para cálculos posteriores
                            if (numInput && !isNaN(parseInt(numInput))) {
                                formSizeArray[parseInt(numInput) - 1] = fileSize;
                            }
                        } else {
                            $(errorZone).html("");
                            
                            if (numInput && !isNaN(parseInt(numInput))) {
                                formSizeArray[parseInt(numInput) - 1] = 0;
                            }
                        }
                    } catch (e) {
                        console.error("Error al procesar el cambio del archivo:", e);
                    }
                });

                function checkFormSize() {
                    var formSize = 0;
                    var fileZizeError = 0;

                    if (formSizeArray && formSizeArray.length) {
                        formSizeArray.forEach(function(inputSize) {
                            if (inputSize) {
                                if (items && items.uploadMaxFilesizeBytes && inputSize > items.uploadMaxFilesizeBytes) {
                                    fileZizeError = 1;
                                }
                                formSize = formSize + inputSize;
                            }
                        });

                        if (fileZizeError && items && items.uploadMaxFilesize) {
                            alert(stic_Web_Forms_LBL_SIZE_FILE_EXCEED + items.uploadMaxFilesize + "B");
                            return false;
                        }

                        if (items && items.postMaxSizeBytes && formSize > items.postMaxSizeBytes) {
                            alert(stic_Web_Forms_LBL_SUM_SIZE_FILES_EXCEED + items.postMaxSize + "B");
                            return false;
                        }
                    }
                    
                    return true;
                }

                function updateEventFields(event) {
                    const selectedOption = event.target.selectedOptions[0];
                    const eventId = selectedOption.value;
                    const assignedUserId = selectedOption.getAttribute('data-assigned-user-id');
                
                    document.getElementById('event_id').value = eventId;
                    document.getElementById('assigned_user_id').value = assignedUserId;
                }
                          
                var scheduleSlot = document.getElementById('schedule_slot');
                if (scheduleSlot) {
                    scheduleSlot.addEventListener('change', updateEventFields);
                    
                    if (scheduleSlot.selectedIndex > 0) {
                        scheduleSlot.dispatchEvent(new Event('change'));
                    }
                }

                $(document).ready(function() {
                    var scheduleSlot = document.getElementById('schedule_slot');
                    if (scheduleSlot) {
                        scheduleSlot.removeAttribute('onchange');
                         
                        scheduleSlot.addEventListener('change', function(e) {
                            updateEventFields(e);
                        });
                        
                        if (scheduleSlot.selectedIndex > 0) {
                            var event = new Event('change');
                            scheduleSlot.dispatchEvent(event);
                        }
                    }
                });

                $('#timeZone').val(Intl.DateTimeFormat().resolvedOptions().timeZone);
                var formHasAlreadyBeenSent = false;

                function validatePrivacyPolicy() {
                    var privacyCheckbox = document.getElementById("accept_policy");
                    
                    if (!privacyCheckbox || !privacyCheckbox.checked) {
                        alert("Has d'acceptar la política de privacitat per poder enviar el formulari.");
                        if (privacyCheckbox) {
                            privacyCheckbox.focus();
                        }
                        return false;
                    }
                    return true;
                }

                function submitForm(form) {
                    if (!validatePrivacyPolicy()) {
                        return false;
                    }
                    if (checkFields() && checkFormSize()) {
                        if (typeof validateCaptchaAndSubmit != "undefined") {
                            validateCaptchaAndSubmit();
                        } else {
                            if (formHasAlreadyBeenSent != true) {
                                formHasAlreadyBeenSent = true;
                                form.submit();
                            } else {
                                console.log("Form is locked because it has already been sent.");
                            }
                        }
                    }
                    return false;
                }

                function addPrivacyPolicyToRequired() {
                    var reqId = document.getElementById("req_id");
                    if (reqId && reqId.value.indexOf("accept_policy") === -1) {
                        reqId.value += "accept_policy;";
                    }
                }

                // Fem accessibles globalment
                window.submitForm = submitForm;
                window.checkFields = checkFields;
                window.checkFormSize = checkFormSize;
                window.validateRequired = validateRequired;
                window.validateNifCif = validateNifCif;
                window.validateMails = validateMails;
                window.validateDates = validateDates;
                window.getConfigVariables = getConfigVariables;
                window.updateEventFields = updateEventFields;
                window.validatePrivacyPolicy = validatePrivacyPolicy;
    } 
});