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
 * @param productId
 * @param oneClickUrl
 */
function oystOneClick(productId, oneClickUrl) {
    window.__OYST__ = window.__OYST__ || {};
    window.__OYST__.getOneClickURL = function (cb, opts) {
        opts = opts || {};

        ready(function () {
            var qty = $$("input[name='qty']")[0].value || 1;

            var form = new FormData();
            form.append("productRef", productId);
            form.append("quantity", qty);
            form.append("variationRef", getSimpleProductId());

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
            xhr.setRequestHeader('cache-control', settings.cacheControl);
            xhr.onload = function () {
                if (200 === xhr.status) {
                    var data = JSON.parse(xhr.responseText);
                    var isErrorsInForm = null;
                    // If not the preload of button run form validation to know if they are errors
                    if (!opts.preload) {
                        var isErrorsInForm = !isOystOneClickButtonFormValid('product_addtocart_form');
                    }
                    cb(isErrorsInForm, data.url);
                }
            };
            xhr.send(form);
        });
    };
}

/**
 * Wait for dom ready
 *
 * @param fn
 */
function ready(fn) {
    if (document.attachEvent ? 'complete' === document.readyState : 'loading' !== document.readyState) {
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}

/**
 * Return the simple product id from a configurable product
 *
 * @returns {null}
 */
function getSimpleProductId() {
    if ('undefined' === typeof(spConfig)) {
        return null;
    }

    var productCandidates = [];

    spConfig.settings.forEach(function (select, selectIndex) {
        var attributeId = select.id.replace('attribute', '');
        var selectedValue = select.options[select.selectedIndex].value;

        spConfig.config.attributes[attributeId].options.forEach(function (option, optionIndex) {
            if (option.id == selectedValue) {
                var optionProducts = option.products;

                if (0 == productCandidates.length) {
                    productCandidates = optionProducts;
                } else {
                    var productIntersection = [];
                    optionProducts.forEach(function (productId, productIndex) {
                        if (-1 < productCandidates.indexOf(productId)) {
                            productIntersection.push(productId);
                        }
                    });
                    productCandidates = productIntersection;
                }
            }
        });
    });

    return (1 == productCandidates.length) ? productCandidates[0] : null;
}

/**
 * Test if the form is valid
 *
 * return bool
 */
function isOystOneClickButtonFormValid(formName) {
    var form = new VarienForm(formName);

    return form.validator.validate();
}
