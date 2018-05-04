/**
 * This file is part of Oyst_OneClick for Magento.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author Oyst <plugin@oyst.com> <@oyst>
 * @category Oyst
 * @package Oyst_OneClick
 * @copyright Copyright (c) 2017 Oyst (http://www.oyst.com)
 */

/**
 * OneClick Customs JS
 */
"use strict";

function showMessage(txt, type) {
    var html = "<ul class=\"messages\"><li class=\"" + type + "\"-msg\"><ul><li>" + txt + "</li></ul></li></ul>";
    $("messages").update(html);
    var url = window.location.href.split("#")[0];
    window.location.replace(url + "#html-body");
}

if (Validation) {
    Validation.add("validate-oyst-url", "Please enter a valid URL finishing by \"/\"", function (v) {
        return v.endsWith("/");
    });

    Validation.add("validate-oyst-shipment-delay", "Please enter the shipment delay", function (v, element) {
        var id = element.getAttribute("id");
        var carrier = id.replace("oyst_oneclick_carrier_delay", "");
        var shipmentType = document.getElementById("oyst_oneclick_carrier_mapping" + carrier);

        if (null !== shipmentType && "0" !== shipmentType.options[shipmentType.selectedIndex].value && "" === v) {
            return false;
        }

        return true;
    });

    Validation.add("validate-oyst-shipment-name", "Please enter the shipment name", function (v, element) {
        var id = element.getAttribute("id");
        var carrier = id.replace("oyst_oneclick_carrier_name", "");
        var shipmentType = document.getElementById("oyst_oneclick_carrier_mapping" + carrier);

        if (null !== shipmentType && "0" !== shipmentType.options[shipmentType.selectedIndex].value && "" === v) {
            return false;
        }

        return true;
    });

    Validation.add("validate-oyst-shipment-type", "Shipment is set as default", function (v, element) {

        var shipment = document.getElementById("oyst_oneclick_shipments_default_carrier").value;
        var carrier = document.getElementById("oyst_oneclick_carrier_mapping_" + shipment);

        if (carrier.getAttribute("id") === element.getAttribute("id") && "0" === carrier.value) {
            return false;
        }

        return true;
    });
}

function getUrl(url) {
    var fields = ["name", "rows"];

    fields.forEach(function (element) {
        var fieldValue = document.getElementById("oyst-log-file-" + element).value;
        if (fieldValue) {
            url += element + "/" + fieldValue + "/";
        }
    });

    window.location = url;
}
