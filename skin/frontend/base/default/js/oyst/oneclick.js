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
    if (typeof(spConfig) == 'undefined') {
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
