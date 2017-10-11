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
}
