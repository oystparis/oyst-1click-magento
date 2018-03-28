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

/**
 * Main function to init 1-Click
 *
 * @param {String} oneClickUrl  Backend conf oneclick payment url
 */
function oystOneClick(oneClickUrl, quoteId) {
    window.__OYST__ = window.__OYST__ || {};
    window.__OYST__.getOneClickURL = function (cb, opts) {
        opts = opts || {};

        ready(function () {
            var form = new FormData();
            form.append("preload", opts.preload);
            form.append("isCheckoutCart", true);
            form.append("quoteId", Number(quoteId));

            var settings = {
                async: true,
                cacheControl: "no-cache",
                data: form,
                method: "POST",
                mimeType: "multipart/form-data",
                url: oneClickUrl
            };

            var xhr = new XMLHttpRequest();
            xhr.open(settings.method, settings.url, settings.async);
            xhr.setRequestHeader("cache-control", settings.cacheControl);
            xhr.onload = function () {
                if (200 === xhr.status) {
                    var data = JSON.parse(xhr.responseText);
                    var isErrorsInForm = null;
                    cb(isErrorsInForm, data.url);
                }
            };
            xhr.send(form);
        });
    };
}

/**
 * Wait for DOM ready
 *
 * @param fn
 */
function ready(fn) {
    if (document.attachEvent ? "complete" === document.readyState : "loading" !== document.readyState) {
        fn();
    } else {
        document.addEventListener("DOMContentLoaded", fn);
    }
}
